# chatbot_ml/train_chatbot.py

import pickle
from sklearn.pipeline import Pipeline
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.svm import LinearSVC
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report
from donnees_chatbot import DONNEES_ENTRAINEMENT

print("Démarrage de l'entraînement...")

# Séparer questions et intentions
questions  = [item[0] for item in DONNEES_ENTRAINEMENT]
intentions = [item[1] for item in DONNEES_ENTRAINEMENT]

# Construire le pipeline ML
modele = Pipeline([
    ('tfidf', TfidfVectorizer(
        ngram_range    = (1, 2),
        min_df         = 1,
        analyzer       = 'word',
        strip_accents  = 'unicode',
        lowercase      = True
    )),
    ('clf', LinearSVC(
        C          = 1.0,
        max_iter   = 1000,
        random_state = 42
    ))
])

# Entraîner le modèle sur TOUTES les données
modele.fit(questions, intentions)

# Évaluer sur les données d'entraînement
score = modele.score(questions, intentions)
print(f"Précision (train) : {score * 100:.1f}%")

# Sauvegarder
with open('modele_chatbot_rhpro.pkl', 'wb') as f:
    pickle.dump(modele, f)

print("Modèle sauvegardé : modele_chatbot_rhpro.pkl")

# Tests rapides
print("\nTests :")
tests = [
    "tâches bloquées",
    "employé surchargé",
    "projets en retard",
    "bonjour",
    "statistiques générales",
]
for t in tests:
    print(f"  '{t}' → {modele.predict([t])[0]}")