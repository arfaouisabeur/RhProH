<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service de prédiction du taux de participation.
 * Appelle l'API Flask (Python / scikit-learn) en local.
 */
class ParticipationPredictionService
{
    private const FLASK_API_URL = 'http://127.0.0.1:5000/predict';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    /**
     * Prédit le taux de participation d'un événement.
     *
     * @param string $titre      Titre de l'événement
     * @param string $lieu       Lieu de l'événement
     * @param string $dateDebut  Date de début (YYYY-MM-DD)
     *
     * @return array{
     *   prediction: int,
     *   label: string,
     *   probabilite: float,
     *   pourcentage: int,
     *   niveau: string,
     *   type_detecte: string,
     *   conseil: string,
     *   source: string
     * }
     */
    public function predict(string $titre, string $lieu, string $dateDebut): array
    {
        try {
            $response = $this->httpClient->request('POST', self::FLASK_API_URL, [
                'json'    => [
                    'titre'      => $titre,
                    'lieu'       => $lieu,
                    'date_debut' => $dateDebut,
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray();
            $data['source'] = 'ml_model';

            return $data;

        } catch (\Throwable $e) {
            error_log('Service Prediction Error: ' . $e->getMessage());
            // Fallback si l'API Flask est indisponible
            return $this->fallback($titre, $lieu, $dateDebut);
        }
    }

    /**
     * Fallback par règles simples si l'API Flask est indisponible.
     */
    private function fallback(string $titre, string $lieu, string $dateDebut): array
    {
        $score = 0;
        $titre = mb_strtolower($titre);
        $lieu  = mb_strtolower($lieu);

        // Type d'événement
        if (str_contains($titre, 'team building') || str_contains($titre, 'loisir') || str_contains($titre, 'soirée')) {
            $score += 2;
        }
        if (str_contains($titre, '[annulé]') || str_contains($titre, 'test')) {
            $score -= 3;
        }

        // Lieu premium
        if (str_contains($lieu, 'hôtel') || str_contains($lieu, 'hotel') || str_contains($lieu, 'lac')) {
            $score += 1;
        }

        // Jour de la semaine
        try {
            $date        = new \DateTime($dateDebut);
            $jourSemaine = (int) $date->format('N'); // 1=lundi, 7=dimanche
            if ($jourSemaine >= 5) $score += 1; // vendredi/samedi
        } catch (\Exception $e) {}

        $prediction = $score >= 1 ? 1 : 0;

        return [
            'prediction'   => $prediction,
            'label'        => $prediction === 1 ? 'Beaucoup de participants' : 'Peu de participants',
            'probabilite'  => $prediction === 1 ? 0.65 : 0.60,
            'pourcentage'  => $prediction === 1 ? 65 : 60,
            'niveau'       => 'moyen',
            'type_detecte' => 'inconnu',
            'conseil'      => $prediction === 1
                ? '✅ Cet événement devrait bien fonctionner.'
                : '⚠️ Cet événement risque d\'avoir peu de participants.',
            'source'       => 'fallback',
        ];
    }
}