import cv2
import numpy as np
import sys
import os

# ============================================================
# ARGUMENTS depuis le controller Symfony
# argv[1] = chemin photo source
# argv[2] = chemin output avatar
# argv[3] = style (cartoon, pixel, anime, watercolor, 3d, sketch)
# ============================================================

if len(sys.argv) < 4:
    print("Usage: generate_avatar.py <source> <output> <style>")
    sys.exit(1)

source_path = sys.argv[1]
output_path = sys.argv[2]
style       = sys.argv[3]

# ── Vérifier que la photo source existe ──
if not os.path.exists(source_path):
    print(f"Image source introuvable : {source_path}")
    sys.exit(1)

# ── Créer le dossier output si nécessaire ──
os.makedirs(os.path.dirname(output_path), exist_ok=True)

# ── Charger l'image ──
img = cv2.imread(source_path)

if img is None:
    print(f"Impossible de lire l'image : {source_path}")
    sys.exit(1)

# ── Redimensionner ──
img = cv2.resize(img, (600, 600))

print(f"Style demandé : {style}")

# ============================================================
# STYLES
# ============================================================

def style_cartoon(img):
    """Style cartoon classique — lissage + contours"""
    color = cv2.bilateralFilter(img, 9, 250, 250)
    gray  = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    gray  = cv2.medianBlur(gray, 7)
    edges = cv2.adaptiveThreshold(
        gray, 255,
        cv2.ADAPTIVE_THRESH_MEAN_C,
        cv2.THRESH_BINARY, 9, 9
    )
    return cv2.bitwise_and(color, color, mask=edges)


def style_pixel(img):
    """Style pixel art — réduction + agrandissement"""
    small  = cv2.resize(img, (60, 60), interpolation=cv2.INTER_LINEAR)
    pixel  = cv2.resize(small, (600, 600), interpolation=cv2.INTER_NEAREST)
    return pixel


def style_anime(img):
    """Style anime — couleurs vives + contours fins"""
    color  = cv2.bilateralFilter(img, 15, 80, 80)
    # Saturation boostée
    hsv    = cv2.cvtColor(color, cv2.COLOR_BGR2HSV).astype(np.float32)
    hsv[:, :, 1] = np.clip(hsv[:, :, 1] * 1.8, 0, 255)
    color  = cv2.cvtColor(hsv.astype(np.uint8), cv2.COLOR_HSV2BGR)
    gray   = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    gray   = cv2.GaussianBlur(gray, (5, 5), 0)
    edges  = cv2.adaptiveThreshold(
        gray, 255,
        cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
        cv2.THRESH_BINARY, 7, 5
    )
    return cv2.bitwise_and(color, color, mask=edges)


def style_watercolor(img):
    """Style aquarelle — texture douce et floue"""
    result = img.copy()
    for _ in range(3):
        result = cv2.bilateralFilter(result, 9, 75, 75)
    result = cv2.stylization(result, sigma_s=60, sigma_r=0.45)
    return result


def style_3d(img):
    """Style 3D — relief + ombres via emboss"""
    color  = cv2.bilateralFilter(img, 9, 150, 150)
    # Emboss kernel
    kernel = np.array([
        [-2, -1, 0],
        [-1,  1, 1],
        [ 0,  1, 2]
    ], dtype=np.float32)
    gray   = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    emboss = cv2.filter2D(gray, -1, kernel) + 128
    emboss = cv2.cvtColor(emboss, cv2.COLOR_GRAY2BGR)
    # Mixer couleur + emboss
    result = cv2.addWeighted(color, 0.75, emboss, 0.25, 0)
    return result


def style_sketch(img):
    """Style esquisse au crayon — noir et blanc"""
    gray      = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    inv       = cv2.bitwise_not(gray)
    blurred   = cv2.GaussianBlur(inv, (21, 21), 0)
    inv_blur  = cv2.bitwise_not(blurred)
    sketch    = cv2.divide(gray, inv_blur, scale=256.0)
    # Reconvertir en BGR pour cohérence
    return cv2.cvtColor(sketch, cv2.COLOR_GRAY2BGR)


# ============================================================
# APPLIQUER LE STYLE
# ============================================================

style_map = {
    'cartoon':    style_cartoon,
    'pixel':      style_pixel,
    'anime':      style_anime,
    'watercolor': style_watercolor,
    '3d':         style_3d,
    'sketch':     style_sketch,
}

if style not in style_map:
    print(f"Style inconnu '{style}', utilisation du style cartoon par défaut")
    style = 'cartoon'

try:
    result = style_map[style](img)
except Exception as e:
    print(f"Erreur lors de l'application du style : {e}")
    sys.exit(1)

# ============================================================
# SAUVEGARDER
# ============================================================

success = cv2.imwrite(output_path, result)

if success:
    print(f"OK Avatar '{style}' genere : {output_path}")
    sys.exit(0)
else:
    print(f"ERREUR Impossible d'ecrire : {output_path}")
    sys.exit(1)