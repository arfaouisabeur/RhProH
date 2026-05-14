"""
3_cv_processor.py — Traitement de CV complets
==============================================
Prend un fichier PDF de CV, extrait le texte, le découpe en phrases,
passe chaque partie dans le modèle NER et retourne les skills dédupliqués.

Ce script est le cœur du système — il gère :
  - Extraction PDF (pdftotext + fallback)
  - Nettoyage et segmentation intelligente du CV
  - Traitement NER par blocs (pour les longs CVs)
  - Déduplication et normalisation des skills
  - Calcul de score de confiance

Usage:
    python 3_cv_processor.py chemin/vers/cv.pdf
    python 3_cv_processor.py --test        (test interne sans PDF)

Ou en mode API (appelé depuis 4_api.py):
    from cv_processor import extract_skills_from_file
"""

import spacy
import re
import os
import sys
import json
import subprocess
from pathlib import Path


MODEL_PATH = os.environ.get("MODEL_PATH", "models/skill_ner_best")
if not Path(MODEL_PATH).exists():
    MODEL_PATH = "models/skill_ner"

_nlp = None  # Lazy loading


def get_nlp():
    global _nlp
    if _nlp is None:
        if not Path(MODEL_PATH).exists():
            raise RuntimeError(
                f"Modèle NER introuvable: {MODEL_PATH}\n"
                "Lance: python 1_build_dataset.py && python 2_train_ner.py"
            )
        _nlp = spacy.load(MODEL_PATH)
    return _nlp


# ─── EXTRACTION PDF ───────────────────────────────────────────────────────────

def extract_pdf_text(pdf_path: str) -> str:
    """Extrait le texte d'un PDF avec pdftotext (ou fallback)."""
    # Méthode 1 : pdftotext (meilleure qualité)
    try:
        result = subprocess.run(
            ["pdftotext", "-layout", pdf_path, "-"],
            capture_output=True, text=True, timeout=30, encoding="utf-8"
        )
        if result.returncode == 0 and result.stdout.strip():
            return result.stdout
    except (FileNotFoundError, subprocess.TimeoutExpired):
        pass

    # Méthode 2 : pdftotext sans -layout
    try:
        result = subprocess.run(
            ["pdftotext", pdf_path, "-"],
            capture_output=True, text=True, timeout=30, encoding="utf-8"
        )
        if result.returncode == 0 and result.stdout.strip():
            return result.stdout
    except (FileNotFoundError, subprocess.TimeoutExpired):
        pass

    # Méthode 3 : PyPDF2 si disponible
    try:
        import PyPDF2
        text_parts = []
        with open(pdf_path, "rb") as f:
            reader = PyPDF2.PdfReader(f)
            for page in reader.pages:
                text_parts.append(page.extract_text() or "")
        return "\n".join(text_parts)
    except ImportError:
        pass

    # Méthode 4 : lecture brute (dernier recours)
    try:
        with open(pdf_path, "rb") as f:
            content = f.read()
        text = content.decode("utf-8", errors="ignore")
        text = re.sub(r'[^\x20-\x7E\xC0-\xFF\n\t]', ' ', text)
        return text
    except Exception:
        return ""


# ─── NETTOYAGE ET SEGMENTATION ────────────────────────────────────────────────

def clean_cv_text(text: str) -> str:
    """Nettoie le texte extrait d'un CV."""
    # Supprimer les caractères non imprimables
    text = re.sub(r'[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]', ' ', text)
    # Normaliser les espaces
    text = re.sub(r'[ \t]+', ' ', text)
    # Normaliser les sauts de ligne (max 2 consécutifs)
    text = re.sub(r'\n{3,}', '\n\n', text)
    # Supprimer les lignes de tirets/points (séparateurs visuels)
    text = re.sub(r'^[-=_*•·]{3,}\s*$', '', text, flags=re.MULTILINE)
    return text.strip()


def segment_cv(text: str) -> list:
    """
    Découpe un CV en segments intelligents.
    Retourne une liste de segments à traiter séparément par le NER.
    """
    segments = []

    # Découper sur les sauts de ligne d'abord
    lines = text.split('\n')

    current_block = []
    for line in lines:
        line = line.strip()
        if not line:
            if current_block:
                segments.append(' '.join(current_block))
                current_block = []
        else:
            current_block.append(line)

    if current_block:
        segments.append(' '.join(current_block))

    # Filtrer les segments vides ou trop courts
    segments = [s for s in segments if len(s.strip()) >= 3]

    # Pour les segments très longs, les redécouper sur les virgules/points
    final_segments = []
    MAX_LEN = 300
    for seg in segments:
        if len(seg) <= MAX_LEN:
            final_segments.append(seg)
        else:
            # Diviser sur les virgules si c'est une liste de skills
            if seg.count(',') > 3:
                # Garder le segment en entier mais aussi des sous-segments
                final_segments.append(seg)
                # Ajouter les chunks pour ne pas rater des skills
                parts = re.split(r'[,;]', seg)
                chunk = []
                for part in parts:
                    chunk.append(part.strip())
                    if len(', '.join(chunk)) > MAX_LEN:
                        final_segments.append(', '.join(chunk))
                        chunk = []
                if chunk:
                    final_segments.append(', '.join(chunk))
            else:
                # Découper sur les points
                sentences = re.split(r'[.!?]+', seg)
                for s in sentences:
                    s = s.strip()
                    if len(s) >= 3:
                        final_segments.append(s)

    return final_segments


# ─── EXTRACTION DES SKILLS ────────────────────────────────────────────────────

def normalize_skill(skill: str) -> str:
    """Normalise un skill (casse, espaces)."""
    skill = skill.strip()
    skill = re.sub(r'\s+', ' ', skill)
    # Garder la casse d'origine sauf si tout est en majuscules
    if skill.isupper() and len(skill) > 4:
        skill = skill.capitalize()
    return skill


def extract_skills_from_text(text: str) -> dict:
    """
    Extrait tous les skills d'un texte de CV.

    Returns:
        {
            "skills": ["Python", "Django", "Docker", ...],
            "skills_csv": "Python, Django, Docker, ...",
            "count": 15,
            "by_segment": [{"segment": "...", "skills": [...]}]
        }
    """
    nlp      = get_nlp()
    text     = clean_cv_text(text)
    segments = segment_cv(text)

    all_skills   = {}  # skill_lower → skill_original (pour déduplication)
    by_segment   = []

    for seg in segments:
        if not seg.strip():
            continue

        doc    = nlp(seg)
        skills = []

        for ent in doc.ents:
            if ent.label_ == "SKILL":
                norm = normalize_skill(ent.text)
                if len(norm) >= 2:  # Ignorer les skills trop courts
                    skills.append(norm)
                    # Déduplication insensible à la casse
                    key = norm.lower()
                    if key not in all_skills:
                        all_skills[key] = norm

        if skills:
            by_segment.append({
                "segment": seg[:100] + ("..." if len(seg) > 100 else ""),
                "skills":  skills,
            })

    # Liste finale triée (les plus longs en premier = plus spécifiques)
    skills_list = sorted(all_skills.values(), key=len, reverse=True)

    return {
        "skills":      skills_list,
        "skills_csv":  ", ".join(skills_list),
        "count":       len(skills_list),
        "segments_processed": len(segments),
        "by_segment":  by_segment,
    }


def extract_skills_from_file(file_path: str) -> dict:
    """Extrait les skills d'un fichier PDF ou texte."""
    path = Path(file_path)

    if not path.exists():
        raise FileNotFoundError(f"Fichier introuvable: {file_path}")

    if path.suffix.lower() == ".pdf":
        text = extract_pdf_text(file_path)
    else:
        text = path.read_text(encoding="utf-8", errors="ignore")

    if not text.strip():
        raise ValueError("Impossible d'extraire le texte du fichier")

    result         = extract_skills_from_text(text)
    result["file"] = path.name
    return result


# ─── TEST INTERNE ─────────────────────────────────────────────────────────────

CV_TEST = """
Mohamed Ben Ali
Développeur Full Stack | Email: m.benali@gmail.com | +216 55 123 456

RÉSUMÉ PROFESSIONNEL
Développeur Full Stack avec 4 ans d'expérience en Python, Django et React.
Passionné par les architectures microservices, le DevOps et le Cloud.
Certifié AWS Solutions Architect.

COMPÉTENCES TECHNIQUES
Langages : Python, Java, JavaScript, TypeScript, PHP
Frontend : React, Vue.js, HTML5, CSS3, Bootstrap, Redux, GraphQL
Backend : Django, Flask, FastAPI, Node.js, Spring Boot
Bases de données : MySQL, PostgreSQL, MongoDB, Redis, Elasticsearch
DevOps : Docker, Kubernetes, Jenkins, GitLab CI, GitHub Actions
Cloud : AWS, Azure, Terraform, Ansible
Outils : Git, GitHub, Jira, Postman, Swagger, VS Code

EXPÉRIENCE PROFESSIONNELLE
Développeur Full Stack Senior — Wevioo, Tunis (2021-Présent)
- Développement d'une plateforme web avec React et Django REST Framework
- Mise en place d'une infrastructure Docker et Kubernetes sur AWS
- Implémentation CI/CD avec Jenkins et GitHub Actions
- Optimisation PostgreSQL et mise en cache Redis

Développeur Backend — Ooredoo Tunisia (2020-2021)
- APIs REST avec Python FastAPI et MongoDB
- Déploiement sur Azure avec Terraform
- Monitoring Prometheus et Grafana

FORMATION
Master Génie Logiciel — INSAT Tunis (2018-2020)
Licence Informatique — FST Tunis (2015-2018)

CERTIFICATIONS
AWS Certified Solutions Architect — Associate (2022)
Certified Kubernetes Administrator (CKA) (2023)

LANGUES
Français: Courant | Anglais: Professionnel (TOEIC 850) | Arabe: Natif
"""


def main():
    if len(sys.argv) > 1 and sys.argv[1] != "--test":
        # Mode fichier
        pdf_path = sys.argv[1]
        print(f"\n→ Traitement du CV: {pdf_path}")
        result = extract_skills_from_file(pdf_path)
    else:
        # Mode test interne
        print("\n→ Test interne avec CV simulé")
        result = extract_skills_from_text(CV_TEST)

    print(f"\n{'='*65}")
    print(f"  RÉSULTATS D'EXTRACTION")
    print(f"{'='*65}")
    print(f"  Skills détectés  : {result['count']}")
    print(f"  Segments traités : {result.get('segments_processed', '?')}")
    print(f"\n  SKILLS:")
    for skill in result["skills"]:
        print(f"    • {skill}")
    print(f"\n  CSV: {result['skills_csv']}")

    print(f"\n  DÉTAIL PAR SEGMENT:")
    for seg_info in result.get("by_segment", [])[:8]:
        print(f"  [{seg_info['segment'][:60]}]")
        print(f"    → {seg_info['skills']}")

    # Sauvegarder le résultat
    with open("data/extraction_result.json", "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    print(f"\n✓ Résultat sauvegardé: data/extraction_result.json")


if __name__ == "__main__":
    main()
