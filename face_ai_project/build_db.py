import mysql.connector
from deepface import DeepFace
import pickle
import os

# ============================================================
# ÉTAPE 1 : CONNEXION À LA BASE DE DONNÉES MySQL
# ============================================================
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="pidevf"  # 👈 change si besoin
)

cursor = conn.cursor()
print("✅ Connecté à MySQL")


# ============================================================
# ÉTAPE 2 : RÉCUPÉRER LES UTILISATEURS AVEC UN AVATAR
# ============================================================
cursor.execute("""
    SELECT id, avatar_path 
    FROM users 
    WHERE avatar_path IS NOT NULL 
    AND avatar_path != ''
""")
users = cursor.fetchall()
print(f"✅ {len(users)} utilisateurs trouvés avec un avatar")


# ============================================================
# ÉTAPE 3 : GÉNÉRER LES EMBEDDINGS POUR CHAQUE UTILISATEUR
# ============================================================
embeddings = []
BASE_PATH = "../public"

for user_id, img_path in users:

    # --- Construire le chemin complet proprement ---
    clean_path = img_path.lstrip('/\\')  # enlève les slashes en début
    full_path = os.path.join(BASE_PATH, clean_path)

    # --- Vérifier que le fichier existe bien ---
    if not os.path.exists(full_path):
        print(f"⚠️  Fichier manquant pour user {user_id} : {full_path}")
        continue

    # --- Générer l'embedding facial avec DeepFace ---
    try:
        result = DeepFace.represent(
            img_path=full_path,
            model_name="Facenet512",
            enforce_detection=False
        )

        embedding = result[0]["embedding"]

        embeddings.append({
            "id": user_id,
            "path": full_path,
            "embedding": embedding
        })

        print(f"✅ User {user_id} traité avec succès")

    except Exception as e:
        print(f"❌ Erreur pour user {user_id} : {e}")


# ============================================================
# ÉTAPE 4 : SAUVEGARDER LA BASE D'EMBEDDINGS
# ============================================================
os.makedirs("embeddings", exist_ok=True)

with open("embeddings/db.pkl", "wb") as f:
    pickle.dump(embeddings, f)

print(f"\n🎉 Base créée avec {len(embeddings)} utilisateurs enregistrés !")


# ============================================================
# ÉTAPE 5 : FERMER LA CONNEXION
# ============================================================
cursor.close()
conn.close()
print("✅ Connexion MySQL fermée")