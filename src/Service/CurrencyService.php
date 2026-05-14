<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CurrencyService
{
    private HttpClientInterface $client;

    private string $base = 'TND';
    private string $currency = 'TND';
    private float $rate = 1.0;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function load(): void
    {
        try {
            // 🌍 GET USER LOCATION
            $response = $this->client->request('GET', 'https://ipapi.co/json/');
            $data = $response->toArray();

            $this->currency = $data['currency'] ?? $this->base;

            // 💱 GET RATES
            if ($this->currency !== $this->base) {
                $ratesResponse = $this->client->request(
                    'GET',
                    'https://open.er-api.com/v6/latest/' . $this->base
                );

                $ratesData = $ratesResponse->toArray();

                if (isset($ratesData['rates'][$this->currency])) {
                    $this->rate = $ratesData['rates'][$this->currency];
                } else {
                    $this->currency = $this->base;
                    $this->rate = 1.0;
                }
            }

        } catch (\Exception $e) {
            $this->currency = $this->base;
            $this->rate = 1.0;
        }
    }

    public function convert(float $amount): float
{
    return round($amount * $this->rate, 2);
}

public function convertToTnd(float $amount): float
{
    if ($this->rate == 0) {
        return $amount;
    }

    return round($amount / $this->rate, 2);
}

public function getCurrency(): string
{
    return $this->currency;
}
public function getRate(): float
{
    return $this->rate;
}
public function convertUsdToTnd(float $amount): float
{
    try {
        $response = $this->client->request(
            'GET',
            'https://open.er-api.com/v6/latest/USD'
        );

        $data = $response->toArray();

        $rateUsdToTnd = $data['rates']['TND'] ?? 1;

        return round($amount * $rateUsdToTnd, 2);

    } catch (\Exception $e) {
        return $amount;
    }
}
}
