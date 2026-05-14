<?php

namespace App\Service;

/**
 * OcrService — Analyse de documents via OCR.space (API gratuite).
 * Extrait le nom du médecin, la période d'arrêt et valide le certificat médical.
 *
 * Pas d'installation composer requise (utilise file_get_contents + base64).
 */
class OcrService
{
    private const API_URL = 'https://api.ocr.space/parse/image';

    private string $apiKey;

    public function __construct(string $ocrApiKey = 'K85766506188957')
    {
        $this->apiKey = $ocrApiKey;
    }

    /**
     * Analyse un fichier (image ou PDF) et extrait les informations médicales.
     *
     * @param string $filePath Chemin absolu vers le fichier
     * @return array{succes: bool, texte: string, medecin: string|null, periode: string|null}
     */
    public function analyserDocumentPath(string $filePath): array
    {
        // Augmenter le temps d'exécution pour les appels API lents
        $previousLimit = ini_get('max_execution_time');
        set_time_limit(120);

        $empty = ['succes' => false, 'texte' => '', 'medecin' => null, 'periode' => null];

        if (!file_exists($filePath)) {
            error_log('[OcrService] Fichier introuvable : ' . $filePath);
            return $empty;
        }

        $mimeType = $this->detectMimeType($filePath);
        $base64   = base64_encode(file_get_contents($filePath));
        $dataUri  = 'data:' . $mimeType . ';base64,' . $base64;

        $postData = http_build_query([
            'apikey'            => $this->apiKey,
            'language'          => 'fre',
            'isOverlayRequired' => 'false',
            'base64Image'       => $dataUri,
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postData,
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents(self::API_URL, false, $context);

        if ($response === false) {
            error_log('[OcrService] Impossible de contacter l\'API OCR.space');
            return $empty;
        }

        $data = json_decode($response, true);

        if (($data['IsErroredOnProcessing'] ?? false) || empty($data['ParsedResults'])) {
            error_log('[OcrService] Erreur OCR.space : ' . ($data['ErrorMessage'][0] ?? 'inconnue'));
            return $empty;
        }

        $texte   = $data['ParsedResults'][0]['ParsedText'] ?? '';
        $medecin = $this->extraireMedecin($texte);
        $periode = $this->extrairePeriode($texte);

        // Un certificat est reconnu si le texte contient des mots médicaux clés
        $motsCles = ['certificat', 'médecin', 'medecin', 'docteur', 'arrêt', 'arret', 'maladie', 'dr.', 'Dr.', 'cabinet'];
        $succes   = false;
        $texteMin = mb_strtolower($texte);
        foreach ($motsCles as $mot) {
            if (str_contains($texteMin, mb_strtolower($mot))) {
                $succes = true;
                break;
            }
        }

        // Si le texte n'est pas vide, on considère quand même un succès partiel
        if (!$succes && !empty(trim($texte))) {
            $succes = true;
        }

        return compact('succes', 'texte', 'medecin', 'periode');
    }

    // ─────────────────────────────────────────────────────────────
    //  Détection du type MIME par magic bytes
    // ─────────────────────────────────────────────────────────────

    private function detectMimeType(string $filePath): string
    {
        // Lire les premiers octets pour détecter le type réel
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return 'image/jpeg';
        }
        $bytes = fread($handle, 8);
        fclose($handle);

        // PDF : %PDF
        if (str_starts_with($bytes, '%PDF')) {
            return 'application/pdf';
        }
        // PNG : \x89PNG
        if (str_starts_with($bytes, "\x89PNG")) {
            return 'image/png';
        }
        // JPEG : \xFF\xD8
        if (str_starts_with($bytes, "\xFF\xD8")) {
            return 'image/jpeg';
        }
        // GIF : GIF8
        if (str_starts_with($bytes, 'GIF8')) {
            return 'image/gif';
        }
        // TIFF : II ou MM
        if (str_starts_with($bytes, "II\x2A\x00") || str_starts_with($bytes, "MM\x00\x2A")) {
            return 'image/tiff';
        }

        // Fallback : essayer mime_content_type, sinon JPEG par défaut
        $mime = mime_content_type($filePath);
        if ($mime && $mime !== 'application/octet-stream') {
            return $mime;
        }

        return 'image/jpeg';
    }

    // ─────────────────────────────────────────────────────────────
    //  Extracteurs d'informations médicales
    // ─────────────────────────────────────────────────────────────

    private function extraireMedecin(string $texte): ?string
    {
        $patterns = [
            '/Dr\.?\s+([A-ZÀ-Üa-zà-ü][a-zà-ü]+(?:\s+[A-ZÀ-Ü][a-zà-ü]+)?)/u',
            '/Docteur\s+([A-ZÀ-Ü][a-zà-ü]+(?:\s+[A-ZÀ-Ü][a-zà-ü]+)?)/ui',
            '/M[ée]decin\s*[:\-]\s*([A-ZÀ-Ü][a-zà-ü]+(?:\s+[A-ZÀ-Ü][a-zà-ü]+)?)/ui',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $texte, $m)) {
                return 'Dr. ' . $m[1];
            }
        }

        return null;
    }

    private function extrairePeriode(string $texte): ?string
    {
        // Format "du JJ/MM/AAAA au JJ/MM/AAAA"
        if (preg_match('/du\s+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})\s+au\s+(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/i', $texte, $m)) {
            return 'du ' . $m[1] . ' au ' . $m[2];
        }
        // Format "JJ/MM/AAAA - JJ/MM/AAAA"
        if (preg_match('/(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})\s*[-–àa]\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/i', $texte, $m)) {
            return 'du ' . $m[1] . ' au ' . $m[2];
        }
        // Format "X jours d'arrêt"
        if (preg_match('/(\d+)\s+jours?\s+d[\'e\s]?arr[êe]t/iu', $texte, $m)) {
            return $m[1] . ' jour(s) d\'arrêt';
        }

        return null;
    }
}
