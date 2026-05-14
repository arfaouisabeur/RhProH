"""
3_cv_processor.py
=================
Traitement de CV complets : extraction de texte PDF + NER skills.

Usage:
    python 3_cv_processor.py --test              (test interne)
    python 3_cv_processor.py mon_cv.pdf          (vrai CV)
"""

import spacy
import re
import os
import sys
import json
import subprocess
from pathlib import Path

# ─── MODÈLE ──────────────────────────────────────────────────────────────────
MODEL_PATH = "models/skill_ner_best"
if not Path(MODEL_PATH).exists():
    MODEL_PATH = "models/skill_ner"

_nlp = None

def get_nlp():
    global _nlp
    if _nlp is None:
        if not Path(MODEL_PATH).exists():
            raise RuntimeError(
                f"Modèle introuvable: {MODEL_PATH}\n"
                "Lance d'abord: python 1_build_dataset.py && python 2_train_ner.py"
            )
        _nlp = spacy.load(MODEL_PATH)
    return _nlp


# ─── EXTRACTION PDF ───────────────────────────────────────────────────────────
def extract_pdf_text(pdf_path: str) -> str:
    """Extrait le texte d'un PDF (pdftotext → PyPDF2 → lecture brute)."""

    # Méthode 1 : pdftotext (meilleure qualité, nécessite poppler)
    try:
        r = subprocess.run(
            ["pdftotext", pdf_path, "-"],
            capture_output=True, text=True, timeout=30, encoding="utf-8"
        )
        if r.returncode == 0 and r.stdout.strip():
            return r.stdout
    except (FileNotFoundError, subprocess.TimeoutExpired):
        pass

    # Méthode 2 : PyPDF2
    try:
        import PyPDF2
        parts = []
        with open(pdf_path, "rb") as f:
            reader = PyPDF2.PdfReader(f)
            for page in reader.pages:
                parts.append(page.extract_text() or "")
        text = "\n".join(parts)
        if text.strip():
            return text
    except Exception:
        pass

    # Méthode 3 : lecture brute
    try:
        with open(pdf_path, "rb") as f:
            raw = f.read()
        text = raw.decode("utf-8", errors="ignore")
        text = re.sub(r'[^\x20-\x7E\xC0-\xFF\n\t]', ' ', text)
        return text
    except Exception:
        return ""


# ─── NETTOYAGE ────────────────────────────────────────────────────────────────
def clean_text(text: str) -> str:
    text = re.sub(r'[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]', ' ', text)
    text = re.sub(r'[ \t]+', ' ', text)
    text = re.sub(r'\n{3,}', '\n\n', text)
    text = re.sub(r'^[-=_*•·]{3,}\s*$', '', text, flags=re.MULTILINE)
    return text.strip()


# ─── SEGMENTATION INTELLIGENTE ────────────────────────────────────────────────
def segment_cv(text: str) -> list:
    """
    Découpe un CV en segments pour le NER.
    Un CV a des patterns spéciaux : listes virgule, puces, phrases longues.
    """
    segments = []

    # Découper par blocs (séparés par lignes vides)
    blocks = re.split(r'\n\s*\n', text)

    for block in blocks:
        block = block.strip()
        if not block or len(block) < 3:
            continue

        # Si le bloc est court → segment direct
        if len(block) <= 250:
            segments.append(block)
            continue

        # Bloc long : découper par lignes
        lines = block.split('\n')
        current = []
        for line in lines:
            line = line.strip()
            if not line:
                if current:
                    segments.append(' '.join(current))
                    current = []
            else:
                current.append(line)
                # Forcer la coupure si trop long
                joined = ' '.join(current)
                if len(joined) > 200:
                    segments.append(joined)
                    current = []
        if current:
            segments.append(' '.join(current))

    # Post-traitement : découper les segments avec beaucoup de virgules
    final = []
    for seg in segments:
        if len(seg) > 300 and seg.count(',') > 5:
            # Liste de skills : couper en chunks de ~150 chars
            parts  = [p.strip() for p in re.split(r'[,;]', seg)]
            chunk  = []
            for p in parts:
                chunk.append(p)
                if len(', '.join(chunk)) > 150:
                    final.append(', '.join(chunk))
                    chunk = []
            if chunk:
                final.append(', '.join(chunk))
            # Garder aussi le segment complet
            final.append(seg[:300])
        else:
            final.append(seg)

    return [s for s in final if s and len(s.strip()) >= 3]


# ─── NORMALISATION ────────────────────────────────────────────────────────────
def normalize(skill: str) -> str:
    skill = skill.strip()
    skill = re.sub(r'\s+', ' ', skill)
    return skill


# ─── EXTRACTION SKILLS ────────────────────────────────────────────────────────
def extract_skills_from_text(text: str) -> dict:
    """Extrait les skills d'un texte (phrase courte ou CV complet)."""
    nlp      = get_nlp()
    text     = clean_text(text)
    segments = segment_cv(text)

    seen       = {}     # lower → original
    by_segment = []

    for seg in segments:
        if not seg.strip():
            continue
        doc    = nlp(seg)
        skills = []
        for ent in doc.ents:
            if ent.label_ == "SKILL":
                norm = normalize(ent.text)
                if len(norm) >= 2:
                    skills.append(norm)
                    key = norm.lower()
                    if key not in seen:
                        seen[key] = norm
        if skills:
            by_segment.append({
                "segment": seg[:80] + ("..." if len(seg) > 80 else ""),
                "skills":  skills,
            })

    skills_list = sorted(seen.values(), key=len, reverse=True)

    return {
        "skills":     skills_list,
        "skills_csv": ", ".join(skills_list),
        "count":      len(skills_list),
        "segments_processed": len(segments),
        "by_segment": by_segment,
    }


def extract_skills_from_file(file_path: str) -> dict:
    """Extrait les skills d'un fichier PDF."""
    path = Path(file_path)
    if not path.exists():
        raise FileNotFoundError(f"Fichier introuvable: {file_path}")

    if path.suffix.lower() == ".pdf":
        text = extract_pdf_text(str(file_path))
    else:
        text = path.read_text(encoding="utf-8", errors="ignore")

    if not text.strip():
        raise ValueError("Impossible d'extraire le texte du fichier")

    result         = extract_skills_from_text(text)
    result["file"] = path.name
    return result


# ─── CV DE TEST ───────────────────────────────────────────────────────────────
CV_TEST = """
Mohamed Ben Ali
Développeur Full Stack | m.benali@gmail.com | +216 55 123 456

RÉSUMÉ PROFESSIONNEL
Développeur Full Stack avec 4 ans d'expérience en Python, Django et React.
Certifié AWS Solutions Architect. Expert en architectures microservices et DevOps.

COMPÉTENCES TECHNIQUES
Langages : Python, Java, JavaScript, TypeScript, PHP
Frontend : React, Vue.js, Angular, HTML5, CSS3, Bootstrap, Redux, GraphQL
Backend : Django, Flask, FastAPI, Node.js, Spring Boot, Laravel
Bases de données : MySQL, PostgreSQL, MongoDB, Redis, Elasticsearch
DevOps : Docker, Kubernetes, Jenkins, GitLab CI, GitHub Actions, Terraform
Cloud : AWS, Azure, Google Cloud, Ansible
Outils : Git, GitHub, Jira, Postman, Swagger, VS Code
Méthodes : Agile, Scrum, TDD, Microservices

EXPÉRIENCE
Développeur Full Stack Senior — Wevioo, Tunis (2021-2024)
- Développement d'une plateforme web avec React et Django REST Framework
- Infrastructure Docker et Kubernetes déployée sur AWS
- CI/CD avec Jenkins et GitHub Actions
- Optimisation PostgreSQL et mise en cache Redis

Développeur Backend — Ooredoo Tunisia (2020-2021)
- APIs REST avec Python FastAPI et MongoDB
- Déploiement Azure avec Terraform et Ansible
- Monitoring Prometheus et Grafana

CERTIFICATIONS
AWS Certified Solutions Architect — Associate (2022)
Certified Kubernetes Administrator (CKA) (2023)

LANGUES
Français: Courant | Anglais: Professionnel | Arabe: Natif
"""


# ─── MAIN ─────────────────────────────────────────────────────────────────────
def main():
    if len(sys.argv) > 1 and sys.argv[1] != "--test":
        # Vrai fichier PDF
        path = sys.argv[1]
        print(f"\n→ Traitement: {path}")
        result = extract_skills_from_file(path)
    else:
        # Test interne
        print("\n→ Test interne avec CV simulé")
        result = extract_skills_from_text(CV_TEST)

    print(f"\n{'='*60}")
    print(f"  Skills détectés  : {result['count']}")
    print(f"  Segments traités : {result.get('segments_processed', '?')}")
    print(f"\n  LISTE DES SKILLS:")
    for s in result["skills"]:
        print(f"    • {s}")
    print(f"\n  CSV: {result['skills_csv'][:120]}...")

    print(f"\n  DÉTAIL PAR SEGMENT:")
    for info in result.get("by_segment", [])[:10]:
        print(f"  [{info['segment']}]")
        print(f"    → {info['skills']}")

    os.makedirs("data", exist_ok=True)
    with open("data/extraction_result.json", "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    print(f"\n✓ Résultat → data/extraction_result.json")


if __name__ == "__main__":
    main()
