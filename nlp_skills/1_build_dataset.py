"""
1_build_dataset_v2.py
=====================
Version améliorée pour enrichir le dataset NER de skills.
"""

import os
import random
from pathlib import Path

import spacy
from spacy.tokens import DocBin
from spacy.util import filter_spans

SKILLS = {
    "langages": [
        "Python", "Java", "JavaScript", "TypeScript", "PHP", "C", "C++", "C#",
        "Ruby", "Go", "Kotlin", "Swift", "Scala", "R", "MATLAB", "Dart",
        "Bash", "Shell", "Rust", "Groovy", "Perl", "Lua", "Assembly",
    ],
    "frontend": [
        "React", "React.js", "Vue.js", "Angular", "Next.js", "Nuxt.js",
        "Svelte", "HTML5", "CSS3", "Bootstrap", "Tailwind CSS", "jQuery",
        "Redux", "Vuex", "Pinia", "GraphQL", "Webpack", "Vite",
        "Jest", "Cypress", "Playwright", "D3.js", "Three.js",
    ],
    "backend": [
        "Django", "Flask", "FastAPI", "Spring Boot", "Laravel", "Symfony",
        "Express.js", "NestJS", "Node.js", "ASP.NET", "Rails", "Phoenix",
        "Gin", "Fiber", "Celery", "RabbitMQ", "Kafka", "gRPC",
        "Django REST Framework", "API Platform", "Hibernate",
    ],
    "mobile": [
        "Flutter", "React Native", "Android", "iOS", "Xamarin", "Ionic",
        "Swift", "Kotlin", "Expo",
    ],
    "databases": [
        "MySQL", "PostgreSQL", "MongoDB", "Oracle", "SQL Server", "SQLite",
        "Redis", "Elasticsearch", "Cassandra", "MariaDB", "Firebase",
        "DynamoDB", "CouchDB", "InfluxDB", "Neo4j",
    ],
    "devops": [
        "Docker", "Kubernetes", "AWS", "Azure", "Google Cloud", "GCP",
        "Terraform", "Ansible", "Jenkins", "GitLab CI", "GitHub Actions",
        "CI/CD", "Helm", "ArgoCD", "Prometheus", "Grafana", "ELK Stack",
        "Nginx", "Apache", "Linux", "Ubuntu", "CentOS", "Debian",
        "Vagrant", "Puppet", "Chef",
    ],
    "ml": [
        "TensorFlow", "PyTorch", "scikit-learn", "Keras", "pandas",
        "NumPy", "Matplotlib", "Seaborn", "Jupyter", "NLTK", "spaCy",
        "Hugging Face", "BERT", "GPT", "OpenCV", "YOLO", "MLflow",
        "DVC", "Apache Spark", "Hadoop", "Hive", "Airflow", "dbt",
        "XGBoost", "LightGBM",
    ],
    "tools": [
        "Git", "GitHub", "GitLab", "Bitbucket", "Jira", "Confluence",
        "Trello", "Postman", "Swagger", "SonarQube", "Maven", "Gradle",
        "npm", "Figma", "VS Code", "IntelliJ",
    ],
    "methods": [
        "Agile", "Scrum", "Kanban", "TDD", "BDD", "DevOps",
        "Microservices", "SOLID", "Design Patterns",
    ],
    "security": [
        "OWASP", "Pentesting", "Ethical hacking", "SSL/TLS", "OAuth",
        "JWT", "Keycloak", "Burp Suite", "Wireshark", "SIEM", "Splunk",
    ],
    "network": [
        "TCP/IP", "DNS", "DHCP", "VPN", "Firewall", "Cisco", "HTTP",
    ],
    "bi": [
        "Power BI", "Tableau", "QlikView", "SAP", "SQL", "Excel", "DAX",
    ],
    "certs": [
        "AWS Certified", "Google Cloud Certified", "Azure Certified",
        "CISSP", "CCNA", "CCNP", "PMP", "ITIL", "ISO 27001",
        "Certified ScrumMaster", "Oracle Certified",
        "Certified Kubernetes Administrator",
    ],
}

EXCLUDED_FROM_FILE = {
    "Français", "Anglais", "Arabe", "Allemand", "Espagnol",
    "Communication", "Leadership", "Travail en équipe",
    "Résolution de problèmes", "Autonomie", "Rigueur",
    "Adaptabilité", "Gestion de projet", "Créativité",
    "Prise de décision", "Esprit d'analyse",
}

def load_lines(path_str: str):
    path = Path(path_str)
    if not path.exists():
        return []
    with open(path, "r", encoding="utf-8") as f:
        return [line.strip() for line in f if line.strip()]

def load_skills_file():
    skills = []
    for candidate in ["data/skills_list.txt", "skills_list.txt"]:
        skills.extend(load_lines(candidate))
    skills = [s for s in skills if s not in EXCLUDED_FROM_FILE]
    return sorted(set(skills), key=len, reverse=True)

ALL_SKILLS = []
for values in SKILLS.values():
    ALL_SKILLS.extend(values)
ALL_SKILLS.extend(load_skills_file())
ALL_SKILLS = sorted(set(ALL_SKILLS), key=len, reverse=True)

def annotate(text: str):
    found = []
    text_lower = text.lower()
    for skill in ALL_SKILLS:
        sl = skill.lower()
        pos = 0
        while True:
            idx = text_lower.find(sl, pos)
            if idx == -1:
                break
            end = idx + len(skill)
            before = text[idx - 1] if idx > 0 else " "
            after = text[end] if end < len(text) else " "
            ok = (not before.isalpha()) and (not after.isalpha())
            if ok:
                overlap = any(not (end <= e[0] or idx >= e[1]) for e in found)
                if not overlap:
                    found.append((idx, end, "SKILL"))
            pos = idx + 1
    return sorted(found)

def mk(text: str):
    return (text, {"entities": annotate(text)})

MANUAL = []
for line in [
    "Python, Django, PostgreSQL, Docker",
    "React, TypeScript, Tailwind CSS, Vite",
    "Spring Boot, Java, Maven, PostgreSQL",
    "Flutter, Dart, Firebase, REST API",
    "AWS, Kubernetes, Terraform, Jenkins",
    "scikit-learn, pandas, NumPy, Matplotlib",
    "Développement d'une API avec FastAPI, Redis et PostgreSQL",
    "Création d'une application web avec Symfony, Twig et MySQL",
    "Mise en place d'une architecture Docker, Nginx et GitLab CI",
    "Développement frontend avec React, Redux et TypeScript",
    "Pipeline data avec Airflow, dbt, PostgreSQL et Power BI",
    "Monitoring avec Prometheus, Grafana et ELK Stack",
    "Sécurisation d'API avec JWT, OAuth et Keycloak",
    "Déploiement cloud sur AWS avec Terraform et Kubernetes",
    "Développement mobile avec React Native, Expo et Firebase",
    "Analyse NLP avec Python, spaCy, Hugging Face et BERT",
]:
    MANUAL.append(mk(line))

for line in [
    "Français : courant",
    "Anglais : professionnel",
    "Arabe : langue maternelle",
    "Tunis, Tunisie",
    "Sfax, Tunisie",
    "Disponible immédiatement",
    "Permis B",
    "Références sur demande",
    "Né en 1998",
    "Football, lecture, voyage",
]:
    MANUAL.append((line, {"entities": []}))

def load_extra_examples():
    return [mk(line) for line in load_lines("data/extra_cv_examples.txt")]

def gen_auto():
    random.seed(42)
    examples = []
    flat_skills = list(ALL_SKILLS)
    prefixes = [
        "Compétences : ", "Stack : ", "Technologies : ", "Outils : ",
        "Backend : ", "Frontend : ", "DevOps : ", "Cloud : ",
    ]
    for _ in range(120):
        n_skills = random.randint(2, 6)
        skills = random.sample(flat_skills, n_skills)
        examples.append(mk(", ".join(skills)))
    for _ in range(100):
        prefix = random.choice(prefixes)
        n_skills = random.randint(3, 7)
        skills = random.sample(flat_skills, n_skills)
        examples.append(mk(prefix + ", ".join(skills)))
    verbs = [
        "Développement d'applications avec",
        "Conception de solutions avec",
        "Mise en place d'une infrastructure avec",
        "Implémentation de services avec",
        "Déploiement d'applications avec",
        "Automatisation avec",
        "Administration de plateformes avec",
    ]
    contexts = [
        " pour le backend.",
        " en production.",
        " dans un environnement Agile.",
        " pour un projet RH.",
        " pour améliorer la performance.",
        ".",
    ]
    for _ in range(120):
        verb = random.choice(verbs)
        n_skills = random.randint(2, 4)
        skills = random.sample(flat_skills, n_skills)
        ctx = random.choice(contexts)
        if n_skills == 2:
            text = f"{verb} {skills[0]} et {skills[1]}{ctx}"
        elif n_skills == 3:
            text = f"{verb} {skills[0]}, {skills[1]} et {skills[2]}{ctx}"
        else:
            text = f"{verb} {skills[0]}, {skills[1]}, {skills[2]} et {skills[3]}{ctx}"
        examples.append(mk(text))
    roles = [
        "Développeur Full Stack", "Ingénieur DevOps", "Data Scientist",
        "Développeur Backend", "Développeur Frontend", "Ingénieur IA",
        "Analyste BI", "Développeur Mobile",
    ]
    for _ in range(80):
        role = random.choice(roles)
        n_skills = random.randint(3, 5)
        skills = random.sample(flat_skills, n_skills)
        years = random.randint(1, 10)
        text = f"{role} avec {years} ans d'expérience en {', '.join(skills[:-1])} et {skills[-1]}"
        examples.append(mk(text))
    return examples

def build(data, path, nlp):
    db = DocBin()
    count = 0
    for text, ann in data:
        doc = nlp.make_doc(text)
        ents = []
        for s, e, lbl in ann.get("entities", []):
            span = doc.char_span(s, e, label=lbl, alignment_mode="expand")
            if span:
                ents.append(span)
        doc.ents = filter_spans(ents)
        db.add(doc)
        count += 1
    db.to_disk(path)
    return count

def main():
    print("=" * 65)
    print(" Build Dataset CV v2 — enrichissement pour meilleur NER ")
    print("=" * 65)
    os.makedirs("data", exist_ok=True)
    nlp = spacy.blank("fr")
    auto_data = gen_auto()
    extra_data = load_extra_examples()
    all_data = MANUAL + auto_data + extra_data
    seen = set()
    unique = []
    for item in all_data:
        if item[0] not in seen:
            seen.add(item[0])
            unique.append(item)
    random.seed(42)
    random.shuffle(unique)
    with_sk = sum(1 for _, a in unique if a["entities"])
    no_sk = len(unique) - with_sk
    total_ents = sum(len(a["entities"]) for _, a in unique)
    print(f"✓ Skills finales : {len(ALL_SKILLS)}")
    print(f"✓ Total exemples : {len(unique)}")
    print(f"  → Avec skills : {with_sk}")
    print(f"  → Sans skills : {no_sk}")
    print(f"  → Total entités : {total_ents}")
    split = int(len(unique) * 0.8)
    train = unique[:split]
    dev = unique[split:]
    print(f"✓ train.spacy : {build(train, 'data/train.spacy', nlp)} docs")
    print(f"✓ dev.spacy   : {build(dev, 'data/dev.spacy', nlp)} docs")
    with open("data/skills_list.txt", "w", encoding="utf-8") as f:
        for skill in sorted(ALL_SKILLS):
            f.write(skill + "\n")
    print("✓ Dataset prêt")
    print("Lance : python 2_train_ner_v2.py")

if __name__ == "__main__":
    main()
