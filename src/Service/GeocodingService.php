<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service de géocodage utilisant l'API Nominatim (OpenStreetMap).
 * Convertit une adresse textuelle en coordonnées GPS (latitude / longitude).
 * Entièrement gratuit, sans clé API.
 */
class GeocodingService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Géocode une adresse textuelle via Nominatim.
     *
     * @param string $address L'adresse à géocoder (ex: "Lac de Tunis")
     * @return array{lat: string, lon: string}|null Coordonnées ou null si non trouvé
     */
    public function geocode(string $address): ?array
    {
        $address = trim($address);

        if ($address === '') {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q'               => $address,
                    'format'          => 'json',
                    'limit'           => 1,
                    'accept-language' => 'fr',
                ],
                'headers' => [
                    'User-Agent' => 'RHPro-App/1.0 (leila@rhpro.local)',
                    'Accept'     => 'application/json',
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();

            if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
                return [
                    'lat' => (string) $data[0]['lat'],
                    'lon' => (string) $data[0]['lon'],
                ];
            }
        } catch (\Throwable $e) {
            // Silently fail — the event will simply have no coordinates
        }

        return null;
    }
}
