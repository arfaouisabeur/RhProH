<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private string $baseUrl = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiKey
    ) {}

    public function getWeatherData(string $lat, string $lon): ?array
    {
        if ($this->apiKey === 'your_api_key_here' || empty($this->apiKey)) {
            return null;
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl, [
                'query' => [
                    'lat'   => $lat,
                    'lon'   => $lon,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                    'lang'  => 'fr'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = $response->toArray();

            // Calcul de la favorabilité
            $temp = $data['main']['temp'] ?? 0;
            $description = $data['weather'][0]['description'] ?? '';
            $wind = ($data['wind']['speed'] ?? 0) * 3.6; // m/s to km/h
            $rain = $data['rain']['1h'] ?? 0;

            $isFavorable = ($temp >= 15 && $rain === 0 && $wind < 25 && !str_contains(strtolower($description), 'pluie'));

            return [
                'temp'        => round($temp),
                'description' => ucfirst($description),
                'icon'        => $data['weather'][0]['icon'] ?? '01d',
                'wind'        => round($wind),
                'humidity'    => $data['main']['humidity'] ?? 0,
                'isFavorable' => $isFavorable,
                'city'        => $data['name'] ?? 'Localisation'
            ];

        } catch (\Exception $e) {
            return null;
        }
    }
}
