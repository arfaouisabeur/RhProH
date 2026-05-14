<?php

namespace App\Service;

use App\Entity\Employe;
use App\Repository\EvenementRepository;
use App\Repository\EventParticipationRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service de recommandation d'événements basé sur les embeddings HuggingFace.
 *
 * Algorithme :
 *  1. Récupérer les événements auxquels l'employé a participé (accepté ou en attente)
 *  2. Construire un "profil textuel" de l'employé (concat titre + description des events passés)
 *  3. Appeler HuggingFace Inference API (sentence-transformers/all-MiniLM-L6-v2)
 *     pour obtenir les embeddings de : [profil employé] + [tous les events candidats]
 *  4. Calculer la similarité cosinus entre le profil et chaque event candidat
 *  5. Retourner les N events les plus proches, triés par score décroissant
 *
 * Aucune modification de la base de données n'est nécessaire.
 */
class RecommendationService
{
    // Modèle gratuit HuggingFace pour les embeddings multilingues
    private const HF_API_URL = 'https://api-inference.huggingface.co/pipeline/feature-extraction/sentence-transformers/all-MiniLM-L6-v2';

    // Nombre max de recommandations à retourner
    private const MAX_RECOMMENDATIONS = 3;

    // Score minimum de similarité pour être recommandé (0 à 1)
    private const MIN_SIMILARITY = 0.25;

    public function __construct(
        private HttpClientInterface          $httpClient,
        private EvenementRepository          $evenementRepository,
        private EventParticipationRepository $participationRepository,
        private string                       $huggingFaceToken
    ) {}

    /**
     * Retourne les événements recommandés pour un employé donné.
     *
     * @return array  Liste d'éléments : ['evenement' => Evenement, 'score' => float, 'raison' => string]
     */
    public function getRecommendations(Employe $employe): array
    {
        // ── 1. Récupérer les participations de l'employé ──────────────────────
        $participations = $this->participationRepository->findBy(['employe' => $employe]);

        $dejaPrisIds = [];
        $textesPasses = [];

        foreach ($participations as $p) {
            $ev = $p->getEvenement();
            if (!$ev) continue;

            $dejaPrisIds[] = $ev->getId();

            // Construire le texte représentatif de cet événement passé
            $textesPasses[] = $this->buildEventText($ev);
        }

        // Si l'employé n'a aucune participation → pas de recommandation possible
        if (empty($textesPasses)) {
            return [];
        }

        // ── 2. Récupérer les événements candidats (non déjà rejoints) ─────────
        $tousLesEvents = $this->evenementRepository->findAll();

        $candidats = array_filter($tousLesEvents, function ($ev) use ($dejaPrisIds) {
            return !in_array($ev->getId(), $dejaPrisIds, true);
        });

        if (empty($candidats)) {
            return [];
        }

        // ── 3. Construire le profil textuel de l'employé ──────────────────────
        // On concatène les textes des événements passés pour former un "goût" global
        $profilEmploye = implode('. ', $textesPasses);

        // ── 4. Préparer tous les textes à encoder en une seule requête ────────
        $textesCandidats = [];
        $candidatsList   = array_values($candidats); // réindexer

        foreach ($candidatsList as $ev) {
            $textesCandidats[] = $this->buildEventText($ev);
        }

        // Tous les textes : [profil, candidat1, candidat2, ...]
        $allTextes = array_merge([$profilEmploye], $textesCandidats);

        // ── 5. Appel HuggingFace pour obtenir les embeddings ──────────────────
        $embeddings = $this->fetchEmbeddings($allTextes);

        // Si l'API échoue → fallback TF-IDF léger
        if (empty($embeddings)) {
            return $this->fallbackTfIdf($profilEmploye, $candidatsList, $textesCandidats);
        }

        $profilEmbedding    = $embeddings[0];
        $candidatEmbeddings = array_slice($embeddings, 1);

        // ── 6. Calculer la similarité cosinus pour chaque candidat ────────────
        $scored = [];

        foreach ($candidatsList as $i => $ev) {
            if (!isset($candidatEmbeddings[$i])) continue;

            $score = $this->cosineSimilarity($profilEmbedding, $candidatEmbeddings[$i]);

            if ($score >= self::MIN_SIMILARITY) {
                $scored[] = [
                    'evenement' => $ev,
                    'score'     => round($score, 3),
                    'raison'    => $this->buildRaison($score),
                    'source'    => 'embeddings',
                ];
            }
        }

        // ── 7. Trier par score décroissant et limiter ─────────────────────────
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, self::MAX_RECOMMENDATIONS);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privés
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Construit un texte représentatif d'un événement pour l'embedding.
     */
    private function buildEventText(\App\Entity\Evenement $ev): string
    {
        $parts = [$ev->getTitre()];

        if ($ev->getLieu()) {
            $parts[] = $ev->getLieu();
        }

        if ($ev->getDescription()) {
            $parts[] = $ev->getDescription();
        }

        // Ajouter les noms des activités si disponibles
        foreach ($ev->getActivites() as $activite) {
            if (method_exists($activite, 'getNom')) {
                $parts[] = $activite->getNom();
            }
        }

        return implode('. ', $parts);
    }

    /**
     * Appelle l'API HuggingFace pour obtenir les embeddings de plusieurs textes.
     * Retourne un tableau de vecteurs (float[]) ou [] en cas d'erreur.
     */
    private function fetchEmbeddings(array $textes): array
    {
        try {
            $response = $this->httpClient->request('POST', self::HF_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->huggingFaceToken,
                    'Content-Type'  => 'application/json',
                ],
                'json'    => ['inputs' => $textes, 'options' => ['wait_for_model' => true]],
                'timeout' => 20,
            ]);

            $data = $response->toArray();

            // L'API retourne : [[float, ...], [float, ...], ...]
            if (!is_array($data) || empty($data) || !is_array($data[0])) {
                return [];
            }

            return $data;

        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Similarité cosinus entre deux vecteurs.
     * Retourne une valeur entre -1 et 1 (1 = identiques, 0 = orthogonaux).
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $dot  = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        $len = min(count($a), count($b));

        for ($i = 0; $i < $len; $i++) {
            $dot   += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $denom = sqrt($normA) * sqrt($normB);

        if ($denom < 1e-10) {
            return 0.0;
        }

        return $dot / $denom;
    }

    /**
     * Construit un message de raison lisible selon le score.
     */
    private function buildRaison(float $score): string
    {
        if ($score >= 0.75) return 'Très similaire à vos événements précédents';
        if ($score >= 0.55) return 'Similaire à vos centres d\'intérêt';
        if ($score >= 0.40) return 'Pourrait vous intéresser';
        return 'Suggéré pour vous';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Fallback TF-IDF léger (si HuggingFace indisponible)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fallback par mots-clés partagés (TF-IDF simplifié) si l'API est down.
     */
    private function fallbackTfIdf(
        string $profilEmploye,
        array  $candidatsList,
        array  $textesCandidats
    ): array {
        $profilMots = $this->tokenize($profilEmploye);

        $scored = [];

        foreach ($candidatsList as $i => $ev) {
            $candidatMots = $this->tokenize($textesCandidats[$i] ?? '');
            $communs      = count(array_intersect($profilMots, $candidatMots));

            if ($communs === 0) continue;

            $score = $communs / max(count($profilMots), count($candidatMots));

            if ($score >= 0.05) {
                $scored[] = [
                    'evenement' => $ev,
                    'score'     => round($score, 3),
                    'raison'    => $this->buildRaison($score),
                    'source'    => 'fallback',
                ];
            }
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, self::MAX_RECOMMENDATIONS);
    }

    /**
     * Tokenise un texte en mots significatifs (stopwords français retirés).
     */
    private function tokenize(string $text): array
    {
        $stopwords = [
            'le', 'la', 'les', 'de', 'du', 'des', 'un', 'une', 'et', 'en',
            'au', 'aux', 'à', 'par', 'pour', 'sur', 'dans', 'avec', 'est',
            'sont', 'sera', 'cette', 'ce', 'qui', 'que', 'se', 'il', 'elle',
        ];

        $text  = mb_strtolower(strip_tags($text));
        $mots  = preg_split('/[\s,.\-;:!?\/\'\"]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $mots  = array_filter($mots, fn($m) => mb_strlen($m) > 2 && !in_array($m, $stopwords));

        return array_unique(array_values($mots));
    }
}