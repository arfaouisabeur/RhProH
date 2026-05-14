from flask import Flask, request, jsonify
from deepface import DeepFace
import pickle
import numpy as np
import cv2
import base64
import os

app = Flask(__name__)

# Charger la base d'embeddings au démarrage
with open("embeddings/db.pkl", "rb") as f:
    db = pickle.load(f)

print(f"✅ {len(db)} utilisateurs chargés")

def cosine_similarity(a, b):
    a, b = np.array(a), np.array(b)
    return np.dot(a, b) / (np.linalg.norm(a) * np.linalg.norm(b))

@app.route("/recognize", methods=["POST"])
def recognize():
    data = request.json
    image_base64 = data.get("image")

    if not image_base64:
        return jsonify({"error": "Aucune image reçue"}), 400

    try:
        # Décoder l'image base64
        img_data = base64.b64decode(image_base64.split(",")[1])
        nparr = np.frombuffer(img_data, np.uint8)
        img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)

        # Redimensionner si trop grande
        max_size = 800
        h, w = img.shape[:2]
        if h > max_size or w > max_size:
            scale = max_size / max(h, w)
            img = cv2.resize(img, (int(w * scale), int(h * scale)))

        # Sauvegarder temporairement
        temp_path = "embeddings/temp_recognize.jpg"
        cv2.imwrite(temp_path, img)

        # Générer l'embedding du visage capturé
        result = DeepFace.represent(
            img_path=temp_path,
            model_name="Facenet512",
            enforce_detection=True  # True car on veut un vrai visage
        )
        os.remove(temp_path)

        input_embedding = result[0]["embedding"]

        # Comparer avec tous les utilisateurs
        best_match = None
        best_score = -1
        THRESHOLD = 0.7  # seuil de similarité (0 à 1)

        for user in db:
            score = cosine_similarity(input_embedding, user["embedding"])
            if score > best_score:
                best_score = score
                best_match = user

        if best_score >= THRESHOLD:
            return jsonify({
                "success": True,
                "user_id": best_match["id"],
                "score": round(float(best_score), 4)
            })
        else:
            return jsonify({
                "success": False,
                "message": "Visage non reconnu",
                "score": round(float(best_score), 4)
            })

    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == "__main__":
    app.run(port=5002, debug=True)