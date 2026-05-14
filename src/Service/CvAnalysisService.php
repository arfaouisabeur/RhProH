<?php

namespace App\Service;

use App\Entity\Candidature;
use Symfony\Component\HttpClient\HttpClient;

/**
 * CvAnalysisService
 * =================
 * Service unifié d'analyse de CV :
 *
 *  ÉTAPE 1 — Extraction des skills via le modèle NER local (Flask/Python)
 *            → Appelle http://127.0.0.1:5000/extract-path
 *            → Retourne la liste des compétences du candidat
 *
 *  ÉTAPE 2 — Score de matching via Groq/LLaMA
 *            → Compare les skills extraits avec la description de l'offre
 *            → Retourne un score 0-100 + analyse narrative
 *
 * Prérequis :
 *   - API NLP Flask démarrée : python 4_api.py  (dans nlp_skills/)
 *   - GROQ_API_KEY dans .env.local
 *   - NLP_API_URL dans .env.local (défaut: http://127.0.0.1:5000)
 */
class CvAnalysisService
{
    private string $groqApiKey;
    private string $cvDirectory;
    private string $nlpApiUrl;

    private array $groqModels = [
        'llama-3.1-8b-instant',
        'llama3-70b-8192',
        'gemma2-9b-it',
        'llama-3.3-70b-versatile',
    ];

    public function __construct(
        string $groqApiKey,
        string $cvDirectory,
        string $nlpApiUrl = 'http://127.0.0.1:5000'
    ) {
        $this->groqApiKey  = $groqApiKey;
        $this->cvDirectory = $cvDirectory;
        $this->nlpApiUrl   = rtrim($nlpApiUrl, '/');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API PUBLIQUE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Analyse complète d'une candidature.
     *
     * @return array{score: int, skills: string, analysis: string}
     */
    public function analyse(Candidature $candidature): array
    {
        // Vérifier que le CV existe
        $cvPath = $candidature->getCvPath();
        if (!$cvPath) {
            throw new \RuntimeException('Aucun CV associé à cette candidature.');
        }

        $fullPath = $this->cvDirectory . '/' . $cvPath;
        if (!file_exists($fullPath)) {
            throw new \RuntimeException("Fichier CV introuvable : {$cvPath}");
        }

        // ── Étape 1 : Extraire les skills via le modèle NER local ─────────────
        $skills = $this->extractSkillsWithNlpModel($fullPath);

        // ── Étape 2 : Calculer le score via Groq ─────────────────────────────
        $offre       = $candidature->getOffreEmploi();
        $offreTitre  = $offre ? $offre->getTitre()        : 'Non précisé';
        $offreDesc   = $offre ? $offre->getDescription()  : 'Non précisée';
        $offreLieu   = $offre ? ($offre->getLocalisation() ?? '') : '';
        $offreContrat = $offre ? ($offre->getTypeContrat() ?? '') : '';

        $result = $this->calculateMatchWithGroq(
            $skills,
            $offreTitre,
            $offreDesc,
            $offreLieu,
            $offreContrat
        );

        return [
            'score'    => $result['score'],
            'skills'   => $skills,       // CSV des skills extraits par NER
            'analysis' => $result['analysis'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ÉTAPE 1 — EXTRACTION SKILLS (modèle NER local via Flask)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Appelle l'API Flask NLP pour extraire les skills du CV PDF.
     * Si l'API n'est pas disponible, utilise pdftotext en fallback.
     *
     * @return string Skills séparés par des virgules
     */
    private function extractSkillsWithNlpModel(string $cvFullPath): string
    {
        // Essayer l'API Flask NLP
        try {
            $client   = HttpClient::create(['timeout' => 60]);
            $response = $client->request('POST', $this->nlpApiUrl . '/extract-path', [
                'json' => ['path' => $cvFullPath],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();

                // Récupérer la liste des skills
                $rawSkills = array_map(
                    fn($s) => is_array($s) ? ($s['text'] ?? '') : (string)$s,
                    $data['skills'] ?? []
                );
                
                $validSkills = [];
                foreach ($rawSkills as $s) {
                    $s = trim($s);
                    // Ignorer les blocs de texte entiers détectés par erreur par le modèle ML
                    if (strlen($s) >= 2 && strlen($s) <= 35) {
                        $validSkills[] = $s;
                    }
                }

                if (!empty($validSkills)) {
                    return implode(', ', $validSkills);
                }

            }
        } catch (\Exception $e) {
            // API NLP non disponible → fallback vers extraction texte simple
        }

        // ── Fallback : extraction basique par dictionnaire ────────────────────
        return $this->extractSkillsFallback($cvFullPath);
    }

    /**
     * Fallback : extrait le texte PDF et cherche les skills par dictionnaire.
     * Utilisé quand l'API Flask n'est pas disponible.
     */
    private function extractSkillsFallback(string $cvPath): string
    {
        $text = $this->extractPdfText($cvPath);
        if (empty($text)) {
            return '';
        }

        // Dictionnaire de skills courants
        $knownSkills = [
            'Python','Java','JavaScript','TypeScript','PHP','C++','C#','Ruby','Go','Kotlin','Swift',
            'React','Vue.js','Angular','Next.js','HTML5','CSS3','Bootstrap','jQuery','GraphQL',
            'Django','Flask','FastAPI','Spring Boot','Laravel','Symfony','Node.js','Express.js',
            'MySQL','PostgreSQL','MongoDB','Redis','Elasticsearch','Oracle','SQLite','MariaDB',
            'Docker','Kubernetes','AWS','Azure','Google Cloud','Terraform','Ansible','Jenkins',
            'Git','GitHub','GitLab','Linux','Ubuntu','Nginx','Apache',
            'TensorFlow','PyTorch','scikit-learn','pandas','NumPy','Keras','BERT',
            'Agile','Scrum','Jira','REST','GraphQL','CI/CD','DevOps',
        ];

        $found    = [];
        $textLow  = strtolower($text);

        foreach ($knownSkills as $skill) {
            if (stripos($textLow, strtolower($skill)) !== false) {
                $found[] = $skill;
            }
        }

        return implode(', ', array_unique($found));
    }

    /**
     * Extrait le texte brut d'un fichier PDF.
     */
    private function extractPdfText(string $path): string
    {
        // pdftotext (poppler)
        $escaped = escapeshellarg($path);
        $output  = shell_exec("pdftotext {$escaped} - 2>/dev/null");
        if (!empty(trim($output ?? ''))) {
            return $this->cleanText($output);
        }

        // Lecture brute en fallback
        $content = file_get_contents($path);
        if ($content === false) return '';
        preg_match_all('/[a-zA-ZÀ-ÿ0-9\s\.,;:@\-\+]{4,}/', $content, $m);
        return $this->cleanText(implode(' ', $m[0] ?? []));
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim(substr($text, 0, 6000));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ÉTAPE 2 — SCORE DE MATCHING (Groq/LLaMA)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Calcule le score de matching entre les skills du candidat et l'offre.
     *
     * @return array{score: int, analysis: string}
     */
    private function calculateMatchWithGroq(
        string $skills,
        string $offreTitre,
        string $offreDesc,
        string $offreLieu,
        string $offreContrat
    ): array {
        if (empty($this->groqApiKey)) {
            // Pas de clé Groq → score basique par comptage
            return $this->calculateBasicScore($skills, $offreTitre . ' ' . $offreDesc);
        }

        // Filtrer les faux positifs avant d'envoyer à Groq
        $filteredSkills = $this->filterFalsePositives($skills);

        $prompt = <<<PROMPT
Tu es un expert RH senior. Analyse le matching entre ce candidat et cette offre.

RÈGLES DE SCORING STRICTES :
- Score 85-100 : le candidat maîtrise 80%+ des technologies de l'offre
- Score 65-84  : le candidat maîtrise 60-79% des technologies
- Score 45-64  : le candidat maîtrise 40-59% des technologies
- Score < 45   : moins de 40% de correspondance
- Base-toi UNIQUEMENT sur les compétences techniques listées, pas sur le titre du poste
- Un candidat avec Python, Django, React, Docker, AWS doit avoir un score > 80 pour une offre Full Stack Python/React

COMPÉTENCES TECHNIQUES DU CANDIDAT :
{$filteredSkills}

OFFRE D'EMPLOI :
- Titre : {$offreTitre}
- Contrat : {$offreContrat}
- Lieu : {$offreLieu}
- Description : {$offreDesc}

Retourne UNIQUEMENT ce JSON valide (sans markdown, sans texte avant ou après) :
{
  "score": <entier 0-100 selon les règles ci-dessus>,
  "points_forts": "<2-3 compétences techniques spécifiques qui correspondent à l'offre>",
  "points_amelioration": "<1-2 technologies manquantes spécifiques demandées dans l'offre>",
  "recommandation": "<Excellent profil / Bon profil / Profil partiel / Profil insuffisant>",
  "analyse": "<3-4 phrases d'analyse RH objective basée uniquement sur les compétences listées>"
}
PROMPT;

        $client    = HttpClient::create(['timeout' => 30]);
        $lastError = '';

        foreach ($this->groqModels as $model) {
            try {
                $response = $client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->groqApiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'       => $model,
                        'messages'    => [['role' => 'user', 'content' => $prompt]],
                        'max_tokens'  => 600,
                        'temperature' => 0.3,
                    ],
                ]);

                if ($response->getStatusCode() !== 200) {
                    $lastError = "HTTP {$response->getStatusCode()}";
                    continue;
                }

                $data    = $response->toArray();
                $content = $data['choices'][0]['message']['content'] ?? '';

                // Nettoyer markdown
                $content = preg_replace('/^```json\s*/m', '', $content);
                $content = preg_replace('/^```\s*/m', '',  $content);
                $content = trim($content);

                $parsed = json_decode($content, true);

                // Extraire JSON si emballé dans du texte
                if (json_last_error() !== JSON_ERROR_NONE || !isset($parsed['score'])) {
                    if (preg_match('/\{[\s\S]*"score"[\s\S]*\}/m', $content, $m)) {
                        $parsed = json_decode($m[0], true);
                    }
                }

                if (json_last_error() === JSON_ERROR_NONE && isset($parsed['score'])) {
                    $parts = [];
                    if (!empty($parsed['analyse']))              $parts[] = $parsed['analyse'];
                    if (!empty($parsed['points_forts']))         $parts[] = "✅ Points forts : " . $parsed['points_forts'];
                    if (!empty($parsed['points_amelioration'])) $parts[] = "⚠️ Manques : " . $parsed['points_amelioration'];
                    if (!empty($parsed['recommandation']))       $parts[] = "📋 " . $parsed['recommandation'];

                    return [
                        'score'    => max(0, min(100, (int)$parsed['score'])),
                        'analysis' => implode("\n\n", $parts),
                    ];
                }

                $lastError = "JSON invalide";

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }
        }

        // Fallback si Groq échoue
        return $this->calculateBasicScore($skills, $offreTitre . ' ' . $offreDesc);
    }

    /**
     * Filtre les faux positifs du modèle NER
     * (langues, villes, mois, mots génériques détectés à tort comme skills)
     */
    private function filterFalsePositives(string $skills): string
    {
        $falsePositives = [
            // Langues naturelles
            'Français', 'Anglais', 'Arabe', 'Allemand', 'Espagnol', 'Italien',
            'maternelle', 'courant', 'professionnel', 'natif', 'bilingue',
            // Certifications génériques
            'Certifié', 'Certifiée', 'Certified', 'TOEIC', 'TOEFL', 'IELTS',
            // Mois et dates
            'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
            'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
            // Villes tunisiennes
            'Tunis', 'Sfax', 'Sousse', 'Kairouan', 'Bizerte', 'Sfaxien',
            // Mots génériques
            'revues de code', 'collaboration', 'communication',
        ];

        $skillsList = array_map('trim', explode(',', $skills));
        $filtered   = array_filter($skillsList, function($skill) use ($falsePositives) {
            $skillTrim = trim($skill);
            foreach ($falsePositives as $fp) {
                if (strcasecmp($skillTrim, $fp) === 0) {
                    return false;
                }
            }
            return strlen($skillTrim) >= 2;
        });

        return implode(', ', $filtered);
    }

    /**
     * Calcule un score basique par comptage de mots en commun.
     * Utilisé comme fallback si Groq est inaccessible.
     */
    private function calculateBasicScore(string $skills, string $offreText): array
    {
        if (empty($skills)) {
            return ['score' => 0, 'analysis' => 'Aucune compétence détectée dans le CV.'];
        }

        $skillsList  = array_map('trim', explode(',', strtolower($skills)));
        $offreWords  = strtolower($offreText);
        $matches     = 0;

        foreach ($skillsList as $skill) {
            if ($skill && stripos($offreWords, $skill) !== false) {
                $matches++;
            }
        }

        $total = max(count($skillsList), 1);
        $score = min(100, (int)(($matches / $total) * 100));

        return [
            'score'    => $score,
            'analysis' => sprintf(
                "%d compétences détectées dans le CV. %d correspondent à l'offre (%d%% de matching).",
                $total, $matches, $score
            ),
        ];
    }
}
