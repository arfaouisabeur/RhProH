import cv2
from deepface import DeepFace
import pickle
import numpy as np

# Load database
with open("embeddings/db.pkl", "rb") as f:
    db = pickle.load(f)

def cosine_similarity(a, b):
    return np.dot(a, b) / (np.linalg.norm(a) * np.linalg.norm(b))

cap = cv2.VideoCapture(0)

print("Camera started... press Q to quit")

while True:
    ret, frame = cap.read()

    if not ret:
        break

    try:
        # Save temporary frame
        temp_path = "temp.jpg"
        cv2.imwrite(temp_path, frame)

        # Get embedding from camera frame
        query_embedding = DeepFace.represent(
            img_path=temp_path,
            model_name="Facenet512",
            enforce_detection=False
        )[0]["embedding"]

        # Compare with DB
        best_match = "Unknown"
        best_score = -1

        for person in db:
            score = cosine_similarity(query_embedding, person["embedding"])

            if score > best_score:
                best_score = score
                best_match = person["name"]

        # Display result
        text = f"{best_match} ({best_score:.2f})"

        cv2.putText(frame, text, (50, 50),
                    cv2.FONT_HERSHEY_SIMPLEX, 1,
                    (0, 255, 0), 2)

    except Exception as e:
        cv2.putText(frame, "No face detected", (50, 50),
                    cv2.FONT_HERSHEY_SIMPLEX, 1,
                    (0, 0, 255), 2)

    cv2.imshow("Face AI Camera", frame)

    if cv2.waitKey(1) & 0xFF == ord('q'):
        break

cap.release()
cv2.destroyAllWindows()
