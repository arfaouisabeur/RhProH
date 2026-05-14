<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ReviewAnalysisService
{
    private const SUMMARY_MODEL = 'https://api-inference.huggingface.co/models/facebook/bart-large-cnn';
    private const SENTIMENT_MODEL = 'https://api-inference.huggingface.co/models/nlptown/bert-base-multilingual-uncased-sentiment';
    private const KEYWORD_MODEL = 'https://api-inference.huggingface.co/models/yanekyuk/bert-uncased-keyword-extractor';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $huggingFaceToken
    ) {}

    private function callApiViaCurl(string $url, array $payload): array
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
        // Bypasser le DNS empoisonné local via le moteur PHP
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

        if (isset($data['error'])) {
            throw new \Exception($data['error']);
        }

        return $data ?: [];
    }

    /**
     * Analyse un ensemble d'avis pour un événement.
     */
    public function analyzeEventReviews(array $ratings): array
    {
        $count = count($ratings);
        if ($count === 0) {
            return [
                'summary' => 'Aucun avis disponible pour cet événement.',
                'sentiment' => ['label' => 'neutre', 'score' => 0],
                'themes' => [],
                'pros' => [],
                'cons' => [],
                'recommendation' => 'Encouragez les participants à laisser des commentaires.'
            ];
        }

        $allText = "";
        $posText = "";
        $negText = "";
        $totalStars = 0;

        foreach ($ratings as $r) {
            $txt = $r->getCommentaire();
            $stars = (int) $r->getEtoiles();
            $totalStars += $stars;
            $allText .= $txt . " ";
            if ($stars >= 4) $posText .= $txt . " ";
            if ($stars <= 2) $negText .= $txt . " ";
        }

        // 1. Sentiment Dominant MATHEMATIQUE (Plus fiable que l'API seule)
        $avgStars = $totalStars / $count;
        $sentimentLabel = ($avgStars >= 3.5) ? 'positif' : (($avgStars <= 2.5) ? 'negatif' : 'neutre');
        $sentimentScore = round(($avgStars / 5) * 100);

        $sentiment = [
            'label' => $sentimentLabel,
            'score' => $sentimentScore,
            'stars' => round($avgStars, 1)
        ];

        // 2. Résumé (BART) - On continue d'utiliser l'IA pour le texte cumulé
        $summary = $this->getSummary(trim($allText), $sentiment);

        // 3. Extraction par catégories - On évite les doublons transversaux
        $pros = !empty($posText) ? $this->getKeywords(trim($posText)) : [];
        $cons = !empty($negText) ? $this->getKeywords(trim($negText)) : [];
        
        // Supprimer des "cons" ce qui est déjà dans "pros" pour éviter l'illogisme
        $cons = array_diff($cons, $pros);
        
        $themes = array_unique(array_merge($pros, $cons));

        // 4. Recommandation finale
        $recommendation = $this->generateDetailedRecommendation($sentiment, $pros, $cons);

        return [
            'summary' => $summary,
            'sentiment' => $sentiment,
            'themes' => array_values($themes),
            'pros' => array_values($pros),
            'cons' => array_values($cons),
            'recommendation' => $recommendation,
            'total_reviews' => $count
        ];
    }

    private function getSummary(string $text, array $sentiment = []): string
    {
        $fallback = "Les retours sont encore trop peu nombreux pour une synthèse détaillée.";
        if (isset($sentiment['label'])) {
            if ($sentiment['label'] === 'positif') $fallback = "L'événement reçoit des éloges unanimes pour son organisation et son ambiance.";
            if ($sentiment['label'] === 'negatif') $fallback = "Plusieurs participants ont relevé des points critiques nécessitant une attention rapide.";
            if ($sentiment['label'] === 'neutre')  $fallback = "Les avis sont mitigés, avec des points positifs compensant quelques zones d'ombre.";
        }

        if (mb_strlen($text) < 30) {
            return $fallback;
        }

        try {
            $data = $this->callApiViaCurl(self::SUMMARY_MODEL, [
                'inputs' => $text,
                'parameters' => ['max_length' => 100, 'min_length' => 20],
                'options' => ['wait_for_model' => true]
            ]);

            if (isset($data[0]['summary_text'])) return $data[0]['summary_text'];
            return $fallback;
        } catch (\Exception $e) {
            return $fallback;
        }
    }

    private function getKeywords(string $text): array
    {
        if (mb_strlen($text) < 3) return [];

        $keywords = [];

        // 1. Appel IA (Si texte assez long)
        if (mb_strlen($text) > 15) {
            try {
                $data = $this->callApiViaCurl(self::KEYWORD_MODEL, [
                    'inputs' => mb_substr($text, 0, 512),
                    'options' => ['wait_for_model' => true]
                ]);
                foreach ($data as $item) {
                    if (isset($item['word'])) {
                        $w = strtolower(trim($item['word']));
                        if (strlen($w) > 3 && !in_array($w, ['this', 'that', 'with', 'event', 'have', 'from', 'been'])) {
                            $keywords[] = ucfirst($w);
                        }
                    }
                }
            } catch (\Exception $e) {}
        }
        
        // 2. Fallback par Dictionnaire de Regex
        $patterns = [
            'Organisation' => '/organis|plann|gestion|temps|moment|horaire/i',
            'Logistique'   => '/logist|lieu|buffet|repas|salle|endroit|cadre|nourriture|boisson|manger/i',
            'Contenu'      => '/contenu|sujet|formation|appris|info|activit|exercice|pratiq/i',
            'Ambiance'     => '/ambiance|accueil|equipe|sympa|cool|fun|equipe|collague/i',
            'Intervenant'  => '/formateur|prof|coach|interven|animateur/i',
        ];
        foreach ($patterns as $k => $p) {
            if (preg_match($p, $text)) $keywords[] = $k;
        }

        // 3. Fallback FINAL : Fréquence de mots (On prend ce qui est dit !)
        if (count($keywords) < 2) {
            $words = str_word_count(strtolower($text), 1);
            $stopWords = ['cette', 'était', 'avec', 'dans', 'pour', 'plus', 'très', 'était', 'mais', 'bien', 'fait', 'tous', 'tout'];
            $counts = array_count_values(array_filter($words, function($w) use ($stopWords) {
                return strlen($w) > 3 && !in_array($w, $stopWords);
            }));
            arsort($counts);
            foreach (array_slice(array_keys($counts), 0, 3) as $w) {
                $keywords[] = ucfirst($w);
            }
        }

        // Filtre final : unique et max 5
        return array_slice(array_unique($keywords), 0, 5);
    }


    private function generateDetailedRecommendation(array $sentiment, array $pros, array $cons): string
    {
        if ($sentiment['label'] === 'positif') {
            $msg = "L'événement est un vrai succès ! ";
            if (!empty($pros)) $msg .= "Les employés ont particulièrement aimé : " . implode(', ', $pros) . ". ";
            $msg .= "Conseil : Maintenez ces standards pour les prochaines sessions.";
            return $msg;
        } elseif ($sentiment['label'] === 'negatif') {
            $msg = "Attention : plusieurs points d'insatisfaction ont été détectés. ";
            if (!empty($cons)) $msg .= "Il faut impérativement corriger les problèmes sur : " . implode(', ', $cons) . ". ";
            $msg .= "Conseil : Organisez un débriefing avec l'équipe logistique.";
            return $msg;
        }

        return "Le ressenti est globalement équilibré. Travaillez sur la cohérence des thèmes abordés.";
    }
}

function implémenter_themes($themes) {
    if (empty($themes)) return null;
    return "les thèmes de : " . implode(', ', $themes);
}
