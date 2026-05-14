<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SentimentService
{
    // Modèle gratuit HuggingFace pour le français
    private const HF_API_URL = 'https://api-inference.huggingface.co/models/nlptown/bert-base-multilingual-uncased-sentiment';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $huggingFaceToken
    ) {}

    private function callHuggingFaceViaCurl(string $url, array $payload): array
    {
        $jsonPayload = json_encode($payload);
        $token = trim($this->huggingFaceToken);
        $ip = gethostbyname('router.huggingface.co');
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            'Content-Type: application/json'
        ]);
        // Unblock DNS poisoning via PHP curl
        curl_setopt($ch, CURLOPT_RESOLVE, ["api-inference.huggingface.co:443:$ip"]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $output = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("CURL Error: " . $error);
        }

        $data = json_decode($output, true);
        if (isset($data['error'])) throw new \Exception($data['error']);

        return $data ?: [];
    }

    /**
     * Analyse le sentiment d'un commentaire.
     * Retourne un tableau :
     *   [
     *     'label'       => 'positif' | 'neutre' | 'negatif',
     *     'score'       => float (0-1),
     *     'stars'       => int (1-5 étoiles HuggingFace),
     *     'coherent'    => bool (commentaire cohérent avec la note),
     *     'emoji'       => string,
     *     'color'       => string (classe CSS),
     *   ]
     */
    public function analyzeComment(string $commentaire, int $etoilesUtilisateur): array
    {
        try {
            $data = $this->callHuggingFaceViaCurl(self::HF_API_URL, [
                'inputs' => $commentaire,
                'options' => ['wait_for_model' => true]
            ]);

            // HuggingFace retourne un tableau de scores par étoile (1-5)
            // Format : [[{"label":"1 star","score":0.01}, {"label":"2 stars","score":0.05}, ...]]
            $scores = $data[0] ?? [];

            if (empty($scores)) {
                return $this->fallback($commentaire, $etoilesUtilisateur);
            }

            // Trouver le label avec le score le plus élevé
            $best = array_reduce($scores, function ($carry, $item) {
                return ($carry === null || $item['score'] > $carry['score']) ? $item : $carry;
            }, null);

            $starsHF = (int) $best['label'][0]; // "3 stars" -> 3

            // Convertir en positif/neutre/négatif
            $sentiment = match (true) {
                $starsHF >= 4 => 'positif',
                $starsHF <= 2 => 'negatif',
                default       => 'neutre',
            };

            // Vérifier la cohérence avec la note de l'utilisateur
            $coherent = $this->isCoherent($sentiment, $etoilesUtilisateur);

            return [
                'label'    => $sentiment,
                'score'    => round($best['score'], 2),
                'stars'    => $starsHF,
                'coherent' => $coherent,
                'emoji'    => $this->getEmoji($sentiment),
                'color'    => $this->getCssClass($sentiment),
                'source'   => 'huggingface',
            ];

        } catch (\Throwable $e) {
            // Si l'API est indisponible, fallback sur une analyse par mots-clés
            return $this->fallback($commentaire, $etoilesUtilisateur);
        }
    }

    /**
     * Analyse plusieurs commentaires d'un événement en une fois.
     * Retourne un résumé global.
     */
    public function analyzeEvent(array $ratings): array
    {
        $results = [];
        $positif = 0;
        $negatif = 0;
        $neutre  = 0;
        $incoherents = 0;

        foreach ($ratings as $rating) {
            $result = $this->analyzeComment(
                $rating->getCommentaire(),
                (int) $rating->getEtoiles()
            );
            $results[$rating->getId()] = $result;

            match ($result['label']) {
                'positif' => $positif++,
                'negatif' => $negatif++,
                default   => $neutre++,
            };

            if (!$result['coherent']) {
                $incoherents++;
            }
        }

        $total = count($ratings);

        return [
            'details'          => $results,
            'total'            => $total,
            'positif'          => $positif,
            'negatif'          => $negatif,
            'neutre'           => $neutre,
            'pct_positif'      => $total > 0 ? round($positif / $total * 100) : 0,
            'pct_negatif'      => $total > 0 ? round($negatif / $total * 100) : 0,
            'incoherents'      => $incoherents,
            'sentiment_global' => $this->globalSentiment($positif, $negatif, $neutre),
        ];
    }

    /**
     * Détecte automatiquement le nombre d'étoiles (1-5) à partir du texte du commentaire.
     * Appelle HuggingFace, ou tombe en fallback si l'API est indisponible.
     */
    public function detectStars(string $commentaire): array
    {
        try {
            $data = $this->callHuggingFaceViaCurl(self::HF_API_URL, [
                'inputs' => $commentaire,
                'options' => ['wait_for_model' => true]
            ]);
            $scores = $data[0] ?? [];

            if (empty($scores)) {
                return $this->fallbackStars($commentaire);
            }

            // Trouver le label avec le score le plus élevé
            $best = array_reduce($scores, function ($carry, $item) {
                return ($carry === null || $item['score'] > $carry['score']) ? $item : $carry;
            }, null);

            $stars = (int) $best['label'][0]; // "3 stars" -> 3

            $sentiment = match (true) {
                $stars >= 4 => 'positif',
                $stars <= 2 => 'negatif',
                default     => 'neutre',
            };

            return [
                'stars'     => $stars,
                'label'     => $sentiment,
                'emoji'     => $this->getEmoji($sentiment),
                'color'     => $this->getCssClass($sentiment),
                'score'     => round($best['score'], 2),
                'source'    => 'huggingface',
            ];

        } catch (\Throwable $e) {
            return $this->fallbackStars($commentaire);
        }
    }

    /**
     * Fallback par mots-clés pour detectStars() si l'API est indisponible.
     */
    private function fallbackStars(string $commentaire): array
    {
        $text = mb_strtolower($commentaire);

        $positifWords = ['super', 'excellent', 'parfait', 'bravo', 'génial', 'top', 'bien',
                         'agréable', 'fantastique', 'merci', 'bonne', 'bon', 'satisfait', 'recommande',
                         'adoré', 'incroyable', 'magnifique'];
        $negatifWords = ['nul', 'mauvais', 'décevant', 'terrible', 'horrible', 'pire',
                         'ennuyeux', 'raté', 'problème', 'déçu', 'désorganisé', 'médiocre'];

        $scorePos = 0;
        $scoreNeg = 0;
        foreach ($positifWords as $w) { if (str_contains($text, $w)) $scorePos++; }
        foreach ($negatifWords as $w) { if (str_contains($text, $w)) $scoreNeg++; }

        if ($scorePos > $scoreNeg) {
            $stars = min(5, 3 + $scorePos);
            $sentiment = 'positif';
        } elseif ($scoreNeg > $scorePos) {
            $stars = max(1, 3 - $scoreNeg);
            $sentiment = 'negatif';
        } else {
            $stars = 3;
            $sentiment = 'neutre';
        }

        return [
            'stars'     => $stars,
            'label'     => $sentiment,
            'emoji'     => $this->getEmoji($sentiment),
            'color'     => $this->getCssClass($sentiment),
            'score'     => 0.0,
            'source'    => 'fallback',
        ];
    }

    // ─── Helpers privés ───────────────────────────────────────────────────────

    /**
     * Fallback par mots-clés si l'API HuggingFace est indisponible.
     */
    private function fallback(string $commentaire, int $etoilesUtilisateur): array
    {
        $text = mb_strtolower($commentaire);

        $positifWords = ['super', 'excellent', 'parfait', 'bravo', 'génial', 'top', 'bien',
                         'agréable', 'fantastique', 'merci', 'bonne', 'bon', 'satisfait', 'recommande'];
        $negatifWords = ['nul', 'mauvais', 'décevant', 'terrible', 'horrible', 'pire',
                         'ennuyeux', 'raté', 'problème', 'déçu', 'désorganisé', 'médiocre'];

        $scorePos = 0;
        $scoreNeg = 0;
        foreach ($positifWords as $w) { if (str_contains($text, $w)) $scorePos++; }
        foreach ($negatifWords as $w) { if (str_contains($text, $w)) $scoreNeg++; }

        if ($scorePos > $scoreNeg) {
            $sentiment = 'positif';
        } elseif ($scoreNeg > $scorePos) {
            $sentiment = 'negatif';
        } else {
            // Si aucun mot trouvé, se baser sur les étoiles de l'utilisateur
            $sentiment = match (true) {
                $etoilesUtilisateur >= 4 => 'positif',
                $etoilesUtilisateur <= 2 => 'negatif',
                default                  => 'neutre',
            };
        }

        return [
            'label'    => $sentiment,
            'score'    => 0.0,
            'stars'    => $etoilesUtilisateur,
            'coherent' => true,
            'emoji'    => $this->getEmoji($sentiment),
            'color'    => $this->getCssClass($sentiment),
            'source'   => 'fallback',
        ];
    }

    private function isCoherent(string $sentimentAI, int $etoilesUtilisateur): bool
    {
        return match ($sentimentAI) {
            'positif' => $etoilesUtilisateur >= 3,
            'negatif' => $etoilesUtilisateur <= 3,
            default   => true,
        };
    }

    private function globalSentiment(int $pos, int $neg, int $neu): string
    {
        if ($pos > $neg && $pos > $neu) return 'positif';
        if ($neg > $pos && $neg > $neu) return 'negatif';
        return 'neutre';
    }

    private function getEmoji(string $sentiment): string
    {
        return match ($sentiment) {
            'positif' => '😊',
            'negatif' => '😞',
            default   => '😐',
        };
    }

    private function getCssClass(string $sentiment): string
    {
        return match ($sentiment) {
            'positif' => 'sentiment-positif',
            'negatif' => 'sentiment-negatif',
            default   => 'sentiment-neutre',
        };
    }
}