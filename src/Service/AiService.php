<?php

namespace App\Service;

/**
 * AiService — Génère des descriptions via l'API Groq (LLaMA 3.3 70B).
 * Équivalent PHP du AIService.java du projet Java.
 *
 * Clé Groq gratuite : https://console.groq.com
 */
class AiService
{
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL   = 'llama-3.3-70b-versatile';
    
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = $_ENV['GROQ_API_KEY'] ?? throw new \RuntimeException('GROQ_API_KEY environment variable is required');
    }

    // ══════════════════════════════════════════════════════════════
    //  CONGÉ — Générer description + durée suggérée
    // ══════════════════════════════════════════════════════════════

    /**
     * Génère une description professionnelle pour une demande de congé.
     *
     * @return array{description: string, dureeJoursSuggeree: int}
     */
    public function genererDescriptionConge(string $typeConge): array
    {
        $prompt =
            "Tu es un assistant RH. Reponds UNIQUEMENT avec un objet JSON valide, sans markdown, sans explication.\n" .
            "Format obligatoire: {\"description\": \"Je souhaite...\", \"duree\": 7}\n\n" .
            "Un employe redige une demande de conge de type: " . $typeConge . "\n" .
            "Ecris une description de 2-3 phrases en francais a la premiere personne, professionnelle.\n" .
            "duree est le nombre de jours typique pour ce type de conge (entier).";

        $json = $this->appellerGroq($prompt);

        $description = $this->extraireString($json, 'description');
        $duree       = $this->extraireNombre($json, 'duree');

        return [
            'description'        => $description ?: 'Je sollicite un ' . $typeConge . ' conformement aux dispositions legales en vigueur.',
            'dureeJoursSuggeree' => $duree ? (int) $duree : 0,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  SERVICE — Générer description (pas de durée)
    // ══════════════════════════════════════════════════════════════

    /**
     * Génère une description professionnelle pour une demande de service.
     *
     * @return array{description: string}
     */
    public function genererDescriptionService(string $typeService): array
    {
        // Nettoyer les emojis éventuels (ex: "📦 Fournitures de bureau" → "Fournitures de bureau")
        $typeNettoye = preg_replace('/^\p{So}+\s*/u', '', $typeService);
        $typeNettoye = trim($typeNettoye);

        $prompt =
            "Tu es un assistant RH. Reponds UNIQUEMENT avec un objet JSON valide, sans markdown, sans explication.\n" .
            "Format obligatoire: {\"description\": \"Je souhaite...\"}\n\n" .
            "Un employe redige une demande de service de type: " . $typeNettoye . "\n" .
            "Ecris une description de 2-3 phrases en francais a la premiere personne, professionnelle et concrete.\n" .
            "La description doit parler uniquement de la demande de service (besoin materiel, logistique, formation, etc.), JAMAIS de conge ou absence.\n" .
            "Commence par 'Je sollicite' ou 'Je souhaite' ou 'Dans le cadre de'.";

        $json = $this->appellerGroq($prompt);

        $description = $this->extraireString($json, 'description');

        return [
            'description' => $description ?: 'Je sollicite une demande de ' . $typeNettoye . ' afin d\'optimiser mon environnement de travail.',
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  IA OPTION A — Recommandation de type de service
    // ══════════════════════════════════════════════════════════════

    /**
     * Analyse le besoin libre de l'employé et recommande le meilleur type de service.
     *
     * @param  string   $besoin      Description libre du besoin (ex: "mon PC ne démarre plus")
     * @param  string[] $typesDispos Liste des types disponibles en base (noms)
     * @return array{typeRecommande: string, categorie: string, confiance: int, explication: string}
     */
    public function recommanderTypeService(string $besoin, array $typesDispos): array
    {
        $listeTypes = implode(', ', $typesDispos);

        $prompt =
            "Tu es un assistant RH expert. Reponds UNIQUEMENT avec un objet JSON valide, sans markdown.\n" .
            "Format obligatoire: {\"typeRecommande\": \"...\", \"categorie\": \"...\", \"confiance\": 85, \"explication\": \"...\"}\n\n" .
            "Un employe exprime ce besoin: \"" . $besoin . "\"\n" .
            "Types de service disponibles dans notre systeme: " . $listeTypes . "\n\n" .
            "Choisis LE type de service le plus adapte parmi la liste ci-dessus (copie exactement le nom).\n" .
            "confiance: entier entre 0 et 100 (niveau de certitude de ta recommandation).\n" .
            "explication: 1-2 phrases concises en francais expliquant pourquoi ce type correspond.\n" .
            "Si aucun type ne correspond, mets confiance=0 et typeRecommande=null.";

        $json = $this->appellerGroq($prompt);
        $data = json_decode($json, true);

        return [
            'typeRecommande' => $data['typeRecommande'] ?? null,
            'categorie'      => $data['categorie']      ?? '',
            'confiance'      => isset($data['confiance']) ? (int) $data['confiance'] : 0,
            'explication'    => $data['explication']    ?? 'Recommandation basée sur votre demande.',
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  IA OPTION B — Analyse des réactions (rapport RH)
    // ══════════════════════════════════════════════════════════════

    /**
     * Génère un rapport d'analyse textuel basé sur les stats likes/dislikes.
     *
     * @param  array $statsParType  [ ['typeNom'=>'...', 'likes'=>N, 'dislikes'=>N], ... ]
     * @param  int   $totalReactions Nombre total de réactions
     * @return array{rapport: string, point_fort: string, point_faible: string, recommandation: string}
     */
    public function analyserReactions(array $statsParType, int $totalReactions): array
    {
        if (empty($statsParType) || $totalReactions === 0) {
            return [
                'rapport'        => 'Aucune réaction enregistrée pour le moment. Les employés n\'ont pas encore évalué les services.',
                'point_fort'     => 'N/A',
                'point_faible'   => 'N/A',
                'recommandation' => 'Encouragez les employés à évaluer les types de service disponibles.',
            ];
        }

        // Construire le résumé des stats pour le prompt
        $statsTexte = '';
        foreach ($statsParType as $stat) {
            $total = $stat['likes'] + $stat['dislikes'];
            $pct   = $total > 0 ? round($stat['likes'] / $total * 100) : 0;
            $statsTexte .= "- {$stat['typeNom']}: {$stat['likes']} likes, {$stat['dislikes']} dislikes ({$pct}% satisfaction)\n";
        }

        $prompt =
            "Tu es un analyste RH expert. Reponds UNIQUEMENT avec un objet JSON valide, sans markdown.\n" .
            "Format: {\"rapport\": \"...\", \"point_fort\": \"...\", \"point_faible\": \"...\", \"recommandation\": \"...\"}\n\n" .
            "Voici les statistiques de satisfaction des services ({$totalReactions} réactions au total):\n" .
            $statsTexte . "\n" .
            "rapport: paragraphe de 3-4 phrases analysant les tendances globales en francais.\n" .
            "point_fort: nom du service le mieux noté + raison succincte.\n" .
            "point_faible: nom du service le moins bien noté + problème identifié.\n" .
            "recommandation: 1-2 actions concretes que les RH peuvent entreprendre.";

        $json = $this->appellerGroq($prompt);
        $data = json_decode($json, true);

        return [
            'rapport'        => $data['rapport']        ?? 'Analyse indisponible.',
            'point_fort'     => $data['point_fort']     ?? 'N/A',
            'point_faible'   => $data['point_faible']   ?? 'N/A',
            'recommandation' => $data['recommandation'] ?? 'Continuez à collecter des données.',
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  APPEL HTTP GROQ
    // ══════════════════════════════════════════════════════════════

    private function appellerGroq(string $prompt): string
    {
        // Échapper le prompt pour l'inclure dans un JSON string
        $promptEsc = addcslashes($prompt, "\"\\");
        $promptEsc = str_replace(["\r\n", "\n", "\r", "\t"], ['\\n', '\\n', '', '\\t'], $promptEsc);

        $body = json_encode([
            'model'      => self::MODEL,
            'max_tokens' => 600,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            // Fix SSL Windows — cacert.pem à la racine du projet
            CURLOPT_CAINFO         => __DIR__ . '/../../cacert.pem',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw   = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $raw === false) {
            error_log('[AiService] cURL error: ' . $error);
            return $this->fallbackJson();
        }

        error_log('[AiService] Groq raw: ' . substr($raw, 0, 500));

        // Décoder la réponse Groq : extraire choices[0].message.content
        $decoded = json_decode($raw, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            error_log('[AiService] Champ content introuvable dans: ' . $raw);
            return $this->fallbackJson();
        }

        error_log('[AiService] Content: ' . $content);

        // Nettoyer balises markdown ```json ... ```
        $content = preg_replace('/```json\s*/i', '', $content);
        $content = preg_replace('/```/', '', $content);
        $content = trim($content);

        // Isoler l'objet JSON entre { et }
        $jStart = strpos($content, '{');
        $jEnd   = strrpos($content, '}');
        if ($jStart !== false && $jEnd !== false && $jEnd > $jStart) {
            $content = substr($content, $jStart, $jEnd - $jStart + 1);
        }

        return $content ?: $this->fallbackJson();
    }

    // ══════════════════════════════════════════════════════════════
    //  Parsers JSON robustes
    // ══════════════════════════════════════════════════════════════

    private function extraireString(string $json, string $champ): ?string
    {
        // Utiliser json_decode d'abord (plus fiable)
        $data = json_decode($json, true);
        if (is_array($data) && isset($data[$champ]) && is_string($data[$champ])) {
            return trim($data[$champ]);
        }

        // Fallback : extraction manuelle
        foreach (['"' . $champ . '":"', '"' . $champ . '": "'] as $p) {
            $idx = strpos($json, $p);
            if ($idx === false) continue;
            $i = $idx + strlen($p);
            $sb = '';
            while ($i < strlen($json)) {
                $c = $json[$i];
                if ($c === '\\' && $i + 1 < strlen($json)) {
                    $n = $json[$i + 1];
                    if ($n === '"') { $sb .= '"'; $i += 2; continue; }
                    if ($n === 'n') { $sb .= "\n"; $i += 2; continue; }
                    $sb .= $c; $i++; continue;
                }
                if ($c === '"') break;
                $sb .= $c; $i++;
            }
            $v = trim($sb);
            if ($v !== '') return $v;
        }

        return null;
    }

    private function extraireNombre(string $json, string $champ): ?string
    {
        $data = json_decode($json, true);
        if (is_array($data) && isset($data[$champ]) && is_numeric($data[$champ])) {
            return (string) $data[$champ];
        }
        return null;
    }

    private function fallbackJson(): string
    {
        return '{"description":"Je sollicite cette demande conformement aux dispositions en vigueur.","duree":7}';
    }
}
