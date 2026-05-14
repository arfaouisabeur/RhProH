"""
4_api.py — API Flask
====================
Usage:
    python 4_api.py
"""

import os
import sys
import importlib.util
from pathlib import Path
from flask import Flask, request, jsonify

# ── Importer 3_cv_processor.py directement ──────────────────────────────────
_dir  = os.path.dirname(os.path.abspath(__file__))
_spec = importlib.util.spec_from_file_location(
    "cv_processor",
    os.path.join(_dir, "3_cv_processor.py")
)
cv = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(cv)

app = Flask(__name__)


@app.route("/health", methods=["GET"])
def health():
    try:
        nlp = cv.get_nlp()
        return jsonify({"status": "ok", "model": cv.MODEL_PATH})
    except Exception as e:
        return jsonify({"status": "error", "error": str(e)}), 500


@app.route("/extract", methods=["POST"])
def extract():
    """Texte brut ou CV complet → skills."""
    data = request.get_json()
    if not data or "text" not in data:
        return jsonify({"error": "Champ 'text' requis"}), 400
    text = data["text"].strip()
    if not text:
        return jsonify({"error": "Texte vide"}), 400
    result = cv.extract_skills_from_text(text)
    return jsonify(result)


@app.route("/extract-path", methods=["POST"])
def extract_path():
    """Chemin vers fichier PDF → skills (utilisé par Symfony)."""
    data = request.get_json()
    if not data or "path" not in data:
        return jsonify({"error": "Champ 'path' requis"}), 400
    try:
        result = cv.extract_skills_from_file(data["path"])
        return jsonify(result)
    except FileNotFoundError as e:
        return jsonify({"error": str(e)}), 404
    except ValueError as e:
        return jsonify({"error": str(e)}), 422
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route("/extract-pdf", methods=["POST"])
def extract_pdf():
    """Upload PDF → skills."""
    if "file" not in request.files:
        return jsonify({"error": "Champ 'file' requis"}), 400
    f = request.files["file"]
    if not f.filename.lower().endswith(".pdf"):
        return jsonify({"error": "PDF requis"}), 400
    tmp = f"/tmp/cv_{os.getpid()}.pdf"
    f.save(tmp)
    try:
        result = cv.extract_skills_from_file(tmp)
        return jsonify(result)
    finally:
        if os.path.exists(tmp):
            os.remove(tmp)


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5000))
    host = os.environ.get("HOST", "127.0.0.1")
    print(f"\nOK: API sur http://{host}:{port}")
    print(f"  GET  /health       -> etat")
    print(f"  POST /extract      -> texte/CV -> skills")
    print(f"  POST /extract-path -> chemin PDF -> skills")
    print(f"  POST /extract-pdf  -> upload PDF -> skills\n")
    app.run(host=host, port=port, debug=False)
