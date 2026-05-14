"""
API Flask — Prédiction du taux de participation.

Utilisation :
    python api_flask.py

L'API tourne sur : http://localhost:5000

Endpoint :
    POST /predict
    Body JSON : {
        "titre": "Team Building Outdoor",
        "lieu": "Marsa, Tunis",
        "date_debut": "2026-06-15"
    }

Réponse JSON : {
    "prediction": 1,
    "label": "Beaucoup de participants",
    "probabilite": 0.82,
    "niveau": "eleve",
    "conseil": "Cet événement devrait attirer beaucoup de participants !"
}
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import joblib
import numpy as np
from datetime import datetime

app = Flask(__name__)
CORS(app)  # Autoriser les requêtes depuis Symfony

# ── Charger le modèle et l'encodeur ──────────────────────────────────────────
try:
    model    = joblib.load('ml_model/model_participation.pkl')
    le_type  = joblib.load('ml_model/label_encoder_type.pkl')
    print("✅ Modèle chargé avec succès")
except FileNotFoundError:
    print("❌ Modèle introuvable — lance d'abord train_model.py")
    model   = None
    le_type = None

# ── Helpers ───────────────────────────────────────────────────────────────────

def detect_type(titre):
    titre = titre.lower()
    if any(k in titre for k in ['team building', 'teambuilding']): return 'team_building'
    if any(k in titre for k in ['formation', 'atelier', 'workshop', 'training']): return 'formation'
    if any(k in titre for k in ['conférence', 'conference', 'séminaire', 'seminaire', 'journée']): return 'conference'
    if any(k in titre for k in ['sport', 'football', 'karting', 'tournoi']): return 'sport'
    if any(k in titre for k in ['soirée', 'gala', 'fête', 'dîner', 'karoke', 'pique-nique', 'famille', 'loisir']): return 'loisir'
    return 'autre'

def is_premium_lieu(lieu):
    lieu = lieu.lower()
    keywords = ['hôtel', 'hotel', 'cité des sciences', 'lac', 'gammarth', 'hammamet', 'berges']
    return 1 if any(k in lieu for k in keywords) else 0

def is_annule(titre):
    return 1 if '[annulé]' in titre.lower() or 'annule' in titre.lower() else 0

def build_conseil(label, type_ev, jour_semaine, mois, is_premium):
    conseils = []
    if label == 0:
        conseils.append("⚠️ Cet événement risque d'avoir peu de participants.")
        if jour_semaine < 4:
            conseils.append("💡 Essayez de le planifier en fin de semaine.")
        if not is_premium:
            conseils.append("💡 Un lieu plus attractif pourrait augmenter la participation.")
        if type_ev == 'formation':
            conseils.append("💡 Les formations ont tendance à moins attirer — pensez à le rendre optionnel/fun.")
    else:
        conseils.append("✅ Cet événement devrait attirer beaucoup de participants !")
        if type_ev == 'team_building':
            conseils.append("🎉 Les team buildings sont très populaires.")
        if jour_semaine >= 4:
            conseils.append("📅 Fin de semaine = plus de participation.")
    return " ".join(conseils)

# ── Route principale ──────────────────────────────────────────────────────────

@app.route('/predict', methods=['POST'])
def predict():
    if model is None:
        return jsonify({'error': 'Modèle non chargé. Lance train_model.py d\'abord.'}), 500

    data = request.get_json()

    if not data:
        return jsonify({'error': 'Corps JSON manquant'}), 400

    titre     = data.get('titre', '')
    lieu      = data.get('lieu', '')
    date_str  = data.get('date_debut', '')

    # Parser la date
    try:
        date_obj    = datetime.strptime(date_str[:10], '%Y-%m-%d')
        mois        = date_obj.month
        jour_semaine = date_obj.weekday()  # 0=lundi, 6=dimanche
    except (ValueError, TypeError):
        mois        = datetime.now().month
        jour_semaine = datetime.now().weekday()

    # Extraire les features
    type_ev     = detect_type(titre)
    is_premium  = is_premium_lieu(lieu)
    is_weekend  = 1 if jour_semaine >= 4 else 0
    is_ete      = 1 if mois in [6, 7, 8] else 0
    is_hiver    = 1 if mois in [12, 1, 2] else 0
    annule      = is_annule(titre)

    # Encoder le type
    try:
        type_encoded = le_type.transform([type_ev])[0]
    except ValueError:
        type_encoded = le_type.transform(['autre'])[0]

    # Construire le vecteur de features
    X = np.array([[
        mois,
        jour_semaine,
        type_encoded,
        is_premium,
        is_weekend,
        is_ete,
        is_hiver,
        annule,
    ]])

    # Prédiction
    prediction   = int(model.predict(X)[0])
    probabilites = model.predict_proba(X)[0]
    proba        = float(probabilites[prediction])

    # Niveau de confiance
    if proba >= 0.80:
        niveau = 'eleve'
    elif proba >= 0.60:
        niveau = 'moyen'
    else:
        niveau = 'faible'

    label  = 'Beaucoup de participants' if prediction == 1 else 'Peu de participants'
    conseil = build_conseil(prediction, type_ev, jour_semaine, mois, is_premium)

    return jsonify({
        'prediction':   prediction,
        'label':        label,
        'probabilite':  round(proba, 2),
        'pourcentage':  round(proba * 100),
        'niveau':       niveau,
        'type_detecte': type_ev,
        'conseil':      conseil,
    })

@app.route('/health', methods=['GET'])
def health():
    return jsonify({'status': 'ok', 'model_loaded': model is not None})

# ── Lancement ─────────────────────────────────────────────────────────────────
if __name__ == '__main__':
    print("🚀 API Flask démarrée sur http://localhost:5000")
    app.run(debug=True, port=5000)
