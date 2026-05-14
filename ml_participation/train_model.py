"""
Script d'entraînement du modèle de prédiction du taux de participation.

Utilisation :
    pip install scikit-learn pandas joblib flask flask-cors
    python train_model.py

Résultat :
    - model_participation.pkl  (le modèle entraîné)
    - label_encoders.pkl       (les encodeurs de catégories)
"""

import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder
from sklearn.metrics import classification_report, accuracy_score
import joblib
import os

# ── 1. Charger le dataset ─────────────────────────────────────────────────────
print("📂 Chargement du dataset...")
df = pd.read_csv('dataset_evenements.csv')
print(f"   {len(df)} exemples chargés")
print(df.head())

# ── 2. Feature Engineering ────────────────────────────────────────────────────
print("\n⚙️  Préparation des features...")

# Détecter le type d'événement depuis le titre si non renseigné
def detect_type(titre):
    titre = titre.lower()
    if any(k in titre for k in ['team building', 'teambuilding']): return 'team_building'
    if any(k in titre for k in ['formation', 'atelier', 'workshop', 'training']): return 'formation'
    if any(k in titre for k in ['conférence', 'conference', 'séminaire', 'seminaire', 'journée']): return 'conference'
    if any(k in titre for k in ['sport', 'football', 'karting', 'tournoi']): return 'sport'
    if any(k in titre for k in ['soirée', 'gala', 'fête', 'dîner', 'karoke', 'loisir', 'pique-nique', 'famille']): return 'loisir'
    return 'autre'

# Détecter si lieu est premium (hôtel, centre de conférence...)
def is_premium_lieu(lieu):
    lieu = lieu.lower()
    keywords = ['hôtel', 'hotel', 'cité des sciences', 'lac', 'gammarth', 'hammamet', 'berges']
    return 1 if any(k in lieu for k in keywords) else 0

# Détecter si c'est un événement annulé
def is_annule(titre):
    return 1 if '[annulé]' in titre.lower() or 'annule' in titre.lower() else 0

df['is_annule']     = df['titre'].apply(is_annule)
df['is_premium']    = df['lieu'].apply(is_premium_lieu)
df['is_weekend']    = df['jour_semaine'].apply(lambda x: 1 if x >= 4 else 0)
df['is_ete']        = df['mois'].apply(lambda x: 1 if x in [6, 7, 8] else 0)
df['is_hiver']      = df['mois'].apply(lambda x: 1 if x in [12, 1, 2] else 0)

# Encodage du type d'événement
le_type = LabelEncoder()
df['type_encoded'] = le_type.fit_transform(df['type_evenement'])

# ── 3. Définir X et y ─────────────────────────────────────────────────────────
features = [
    'mois',
    'jour_semaine',
    'type_encoded',
    'is_premium',
    'is_weekend',
    'is_ete',
    'is_hiver',
    'is_annule',
]

X = df[features]
y = df['participants']  # 0 = peu, 1 = beaucoup

print(f"   Features : {features}")
print(f"   Distribution : {y.value_counts().to_dict()}")

# ── 4. Entraînement ───────────────────────────────────────────────────────────
print("\n🤖 Entraînement du modèle...")

X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42
)

model = RandomForestClassifier(
    n_estimators=100,
    max_depth=5,
    random_state=42,
    class_weight='balanced'
)

model.fit(X_train, y_train)

# ── 5. Évaluation ─────────────────────────────────────────────────────────────
print("\n📊 Évaluation du modèle...")
y_pred = model.predict(X_test)
acc = accuracy_score(y_test, y_pred)
print(f"   Accuracy : {acc:.2%}")
print("\n   Rapport détaillé :")
print(classification_report(y_test, y_pred, target_names=['Peu de participants', 'Beaucoup de participants']))

# Importance des features
print("\n📈 Importance des features :")
for feat, imp in sorted(zip(features, model.feature_importances_), key=lambda x: -x[1]):
    bar = '█' * int(imp * 40)
    print(f"   {feat:<20} {bar} {imp:.3f}")

# ── 6. Sauvegarde ─────────────────────────────────────────────────────────────
print("\n💾 Sauvegarde du modèle...")

os.makedirs('ml_model', exist_ok=True)

joblib.dump(model, 'ml_model/model_participation.pkl')
joblib.dump(le_type, 'ml_model/label_encoder_type.pkl')

print("   ✅ ml_model/model_participation.pkl")
print("   ✅ ml_model/label_encoder_type.pkl")
print("\n🎉 Modèle prêt ! Lance maintenant : python api_flask.py")
