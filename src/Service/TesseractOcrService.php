<?php

namespace App\Service;

/**
 * TesseractOcrService — OCR local avec Tesseract (gratuit, illimité, pas d'API).
 * Fallback automatique si Tesseract n'est pas installé.
 */
class TesseractOcrService
{
    private ?string $tesseractPath = null;

    public function __construct()
    {
        // Chemins d'installation Tesseract sur Windows
        $paths = [
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            'tesseract', // Si dans PATH
        ];

        foreach ($paths as $path) {
            if ($path === 'tesseract') {
                // Tester si dans PATH
                exec('tesseract --version 2>&1', $output, $code);
                if ($code === 0) {
                    $this->tesseractPath = 'tesseract';
                    break;
                }
            } elseif (file_exists($path)) {
                $this->tesseractPath = $path;
                break;
            }
        }

        if ($this->tesseractPath) {
            error_log('[TesseractOCR] Tesseract trouvé: ' . $this->tesseractPath);
        } else {
            error_log('[TesseractOCR] Tesseract non installé — OCR local indisponible');
        }
    }

    public function isAvailable(): bool
    {
        return $this->tesseractPath !== null;
    }

    /**
     * Analyse un fichier image/PDF avec Tesseract local.
     */
    public function analyserDocumentPath(string $filePath): array
    {
        $empty = ['succes' => false, 'texte' => '', 'medecin' => null, 'periode' => null, 'debug' => ''];

        if (!$this->isAvailable()) {
            return array_merge($empty, ['debug' => 'Tesseract non installé']);
        }

        if (!file_exists($filePath)) {
            return array_merge($empty, ['debug' => 'Fichier introuvable']);
        }

        // Créer un fichier de sortie temporaire
        $outputBase = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('tess_');
        $outputFile = $outputBase . '.txt';

        // Commande Tesseract : -l fra = français, --psm 6 = bloc de texte uniforme
        $cmd = sprintf(
            '"%s" "%s" "%s" -l fra --psm 6 2>&1',
            $this->tesseractPath,
            $filePath,
            $outputBase
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            error_log('[TesseractOCR] Erreur exec: ' . implode("\n", $output));
            return array_merge($empty, ['debug' => 'Erreur Tesseract: ' . implode(' ', $output)]);
        }

        if (!file_exists($outputFile)) {
            return array_merge($empty, ['debug' => 'Fichier de sortie non créé']);
        }

        $texte = file_get_contents($outputFile);
        @unlink($outputFile);

        if (empty(trim($texte))) {
            return array_merge($empty, ['debug' => 'Texte vide — document illisible']);
        }

        error_log('[TesseractOCR] Texte extrait: ' . strlen($texte) . ' chars');

        $medecin = $this->extraireMedecin($texte);
        $periode = $this->extrairePeriode($texte);

        // Validation : mots médicaux
        $motsCles = [
            'certificat', 'médecin', 'medecin', 'docteur', 'arrêt', 'arret',
            'maladie', 'dr.', 'dr ', 'cabinet', 'patient', 'repos',
        ];

        $succes   = false;
        $texteMin = mb_strtolower($texte);
        foreach ($motsCles as $mot) {
            if (str_contains($texteMin, mb_strtolower($mot))) {
                $succes = true;
                break;
            }
        }

        if (!$succes && strlen(trim($texte)) > 20) {
            $succes = true;
        }

        return [
            'succes'  => $succes,
            'texte'   => $texte,
            'medecin' => $medecin,
            'periode' => $periode,
            'debug'   => 'Tesseract OK — ' . strlen($texte) . ' chars',
        ];
    }

    private function extraireMedecin(string $texte): ?string
    {
        $NOM = "[A-ZÀ-Ÿa-zà-ÿ][A-ZÀ-Ÿa-zà-ÿ'\\-]{1,}";
        $NOM_COMPLET = "$NOM(?:\\s+$NOM){0,3}";

        $patterns = [
            '/\\bDr\\.?\\s+(' . $NOM_COMPLET . ')/u',
            '/\\bDocteur\\s+(' . $NOM_COMPLET . ')/ui',
            '/\\bM[ée]decin\\s*[:\\-]\\s*(' . $NOM_COMPLET . ')/ui',
            '/\\bsoussign[ée][e]?\\s+(?:Dr\\.?\\s+)?(' . $NOM_COMPLET . ')/ui',
            '/\\bnom\\s+du\\s+m[ée]decin\\s*[:\\-]\\s*(' . $NOM_COMPLET . ')/ui',
            '/\\bcachet\\s+(?:du\\s+)?(?:Dr\\.?\\s+)?(' . $NOM_COMPLET . ')/ui',
            '/\\bje\\s+soussign[ée][e]?\\s+(?:Dr\\.?\\s+|Docteur\\s+)?(' . $NOM_COMPLET . ')/ui',
            '/\\bm[ée]decin\\s+traitant\\s*[:\\-]\\s*(' . $NOM_COMPLET . ')/ui',
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $texte, $m)) {
                $nom = trim($m[1]);
                $nom = preg_replace('/\\s+(certifie|atteste|que|le|la|du|de|au|en|par|pour|avec|est|sont|a|an)\\b.*/ui', '', $nom);
                $nom = trim($nom);
                if (strlen($nom) >= 3) {
                    return 'Dr. ' . ucwords(mb_strtolower($nom));
                }
            }
        }

        return null;
    }

    private function extrairePeriode(string $texte): ?string
    {
        if (preg_match('/du\\s+(\\d{1,2}[\\/\\-\\.]\\d{1,2}[\\/\\-\\.]\\d{2,4})\\s+au\\s+(\\d{1,2}[\\/\\-\\.]\\d{1,2}[\\/\\-\\.]\\d{2,4})/i', $texte, $m)) {
            return 'du ' . $m[1] . ' au ' . $m[2];
        }
        if (preg_match('/(\\d{1,2}[\\/\\-\\.]\\d{1,2}[\\/\\-\\.]\\d{2,4})\\s*[-–àa]\\s*(\\d{1,2}[\\/\\-\\.]\\d{1,2}[\\/\\-\\.]\\d{2,4})/i', $texte, $m)) {
            return 'du ' . $m[1] . ' au ' . $m[2];
        }
        if (preg_match('/(\\d+)\\s+jours?\\s+d[\'e\\s]?(?:arr[êe]t|repos)/iu', $texte, $m)) {
            return $m[1] . ' jour(s) d\'arrêt';
        }
        if (preg_match_all('/(\\d{1,2}[\\/\\-\\.]\\d{1,2}[\\/\\-\\.]\\d{2,4})/', $texte, $matches) && count($matches[1]) >= 2) {
            return 'du ' . $matches[1][0] . ' au ' . $matches[1][1];
        }
        return null;
    }
}
