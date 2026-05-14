from deepface import DeepFace

result = DeepFace.verify(
    img1_path="face1.jpg",
    img2_path="face2.jpg",
    model_name="Facenet512"   # ✅ lighter & stable
)

print(result)
