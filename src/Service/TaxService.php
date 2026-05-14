<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TaxService
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->apiKey = '2f3xSClf2NWCQprryKa3qyVb348L9pjifNEgHqV5';
    }

    public function calculateNet(float $gross, string $country = 'US'): array
    {
        $country = strtoupper($country);

        // Use manual calculation for all countries
        return match ($country) {
            'TN' => $this->calculateTunisiaTax($gross),
            'FR' => $this->calculateFranceTax($gross),
            'US' => $this->calculateUSATax($gross),
            'GB', 'UK' => $this->calculateUKTax($gross),
            'CA' => $this->calculateCanadaTax($gross),
            'RO' => $this->calculateRomaniaTax($gross),
            'DE' => $this->calculateGermanyTax($gross),
            'IT' => $this->calculateItalyTax($gross),
            'ES' => $this->calculateSpainTax($gross),
            'AE' => $this->calculateUAETax($gross),
            'SA' => $this->calculateSaudiTax($gross),
            'MA' => $this->calculateMoroccoTax($gross),
            'DZ' => $this->calculateAlgeriaTax($gross),
            'EG' => $this->calculateEgyptTax($gross),
            default => $this->calculateDefaultTax($gross)
        };
    }

    /**
     * Tunisia - Progressive tax rates
     */
    private function calculateTunisiaTax(float $gross): array
    {
        $brackets = [
            ['min' => 0, 'max' => 5000, 'rate' => 0.13],
            ['min' => 5000, 'max' => 20000, 'rate' => 0.26],
            ['min' => 20000, 'max' => 30000, 'rate' => 0.28],
            ['min' => 30000, 'max' => 50000, 'rate' => 0.32],
            ['min' => 50000, 'max' => PHP_FLOAT_MAX, 'rate' => 0.35]
        ];

        return $this->applyBrackets($gross, $brackets);
    }

    /**
     * France - Progressive tax rates
     */
    private function calculateFranceTax(float $gross): array
    {
        $brackets = [
            ['min' => 0, 'max' => 10777, 'rate' => 0.09],
            ['min' => 10777, 'max' => 27478, 'rate' => 0.11],
            ['min' => 27478, 'max' => 78570, 'rate' => 0.30],
            ['min' => 78570, 'max' => 168994, 'rate' => 0.41],
            ['min' => 168994, 'max' => PHP_FLOAT_MAX, 'rate' => 0.45]
        ];

        return $this->applyBrackets($gross, $brackets);
    }

    /**
     * USA - Federal tax rates
     */
    private function calculateUSATax(float $gross): array
    {
        $brackets = [
            ['min' => 0, 'max' => 11000, 'rate' => 0.10],
            ['min' => 11000, 'max' => 44725, 'rate' => 0.12],
            ['min' => 44725, 'max' => 95375, 'rate' => 0.22],
            ['min' => 95375, 'max' => 182100, 'rate' => 0.24],
            ['min' => 182100, 'max' => 231250, 'rate' => 0.32],
            ['min' => 231250, 'max' => 578125, 'rate' => 0.35],
            ['min' => 578125, 'max' => PHP_FLOAT_MAX, 'rate' => 0.37]
        ];

        return $this->applyBrackets($gross, $brackets);
    }

    /**
     * UK - Progressive tax rates
     */
    private function calculateUKTax(float $gross): array
    {
        $brackets = [
            ['min' => 0, 'max' => 12570, 'rate' => 0.11],
            ['min' => 12570, 'max' => 50270, 'rate' => 0.20],
            ['min' => 50270, 'max' => 125140, 'rate' => 0.40],
            ['min' => 125140, 'max' => PHP_FLOAT_MAX, 'rate' => 0.45]
        ];

        return $this->applyBrackets($gross, $brackets);
    }

    /**
     * Canada - Federal tax rates
     */
    private function calculateCanadaTax(float $gross): array
    {
        $brackets = [
            ['min' => 0, 'max' => 53359, 'rate' => 0.15],
            ['min' => 53359, 'max' => 106717, 'rate' => 0.205],
            ['min' => 106717, 'max' => 165430, 'rate' => 0.26],
            ['min' => 165430, 'max' => 235675, 'rate' => 0.29],
            ['min' => 235675, 'max' => PHP_FLOAT_MAX, 'rate' => 0.33]
        ];

        return $this->applyBrackets($gross, $brackets);
    }

    /**
     * Romania - Flat tax rate
     */
    private function calculateRomaniaTax(float $gross): array
    {
        $rate = 0.10; // 10% flat tax
        $tax = $gross * $rate;
        
        return [
            'gross' => round($gross, 2),
            'tax' => round($tax, 2),
            'net' => round($gross - $tax, 2)
        ];
    }

    /**
     * Germany - Progressive tax rates
     */
    private function calculateGermanyTax(float $gross): array
    {
        $brackets = [
            ['min' => 0, 'max' => 10908, 'rate' => 0.08],
            ['min' => 10908, 'max' => 62809, 'rate' => 0.14],
            ['min' => 62809, 'max' => 277825, 'rate' => 0.42],
            ['min' => 277825, 'max' => PHP_FLOAT_MAX, 'rate' => 0.45]
        ];

        return $this->applyBrackets($gross, $brackets);
    }

    /**
     * Italy - Progressive tax rates
     */
    private function calculateItalyTax(float $gross): array
    {
        $brackets = [
            ['min' => 0, 'max' => 15000, 'rate' => 0.23],
            ['min' => 15000, 'max' => 28000, 'rate' => 0.25],
            ['min' => 28000, 'max' => 50000, 'rate' => 0.35],
            ['min' => 50000, 'max' => PHP_FLOAT_MAX, 'rate' => 0.43]
        ];

        return $this->applyBrackets($gross, $brackets);
    }

    /**
     * Spain - Progressive tax rates
     */
    private function calculateSpainTax(float $gross): array
    {
        $brackets = [
            ['min' => 0, 'max' => 12450, 'rate' => 0.19],
            ['min' => 12450, 'max' => 20200, 'rate' => 0.24],
            ['min' => 20200, 'max' => 35200, 'rate' => 0.30],
            ['min' => 35200, 'max' => 60000, 'rate' => 0.37],
            ['min' => 60000, 'max' => 300000, 'rate' => 0.45],
            ['min' => 300000, 'max' => PHP_FLOAT_MAX, 'rate' => 0.47]
        ];

        return $this->applyBrackets($gross, $brackets);
    }

    /**
     * UAE - No income tax
     */
    private function calculateUAETax(float $gross): array
    {
        return [
            'gross' => round($gross, 2),
            'tax' => 0,
            'net' => round($gross, 2)
        ];
    }

    /**
     * Saudi Arabia - No income tax
     */
    private function calculateSaudiTax(float $gross): array
    {
        return [
            'gross' => round($gross, 2),
            'tax' => 0,
            'net' => round($gross, 2)
        ];
    }

    /**
     * Morocco - Progressive tax rates
     */
    private function calculateMoroccoTax(float $gross): array
    {
        $brackets = [
            ['min' => 0, 'max' => 30000, 'rate' => 0.07],
            ['min' => 30000, 'max' => 50000, 'rate' => 0.10],
            ['min' => 50000, 'max' => 60000, 'rate' => 0.20],
            ['min' => 60000, 'max' => 80000, 'rate' => 0.30],
            ['min' => 80000, 'max' => 180000, 'rate' => 0.34],
            ['min' => 180000, 'max' => PHP_FLOAT_MAX, 'rate' => 0.38]
        ];

        return $this->applyBrackets($gross, $brackets);
    }

    /**
     * Algeria - Progressive tax rates
     */
    private function calculateAlgeriaTax(float $gross): array
    {
        $brackets = [
            ['min' => 0, 'max' => 240000, 'rate' => 0.11],
            ['min' => 240000, 'max' => 480000, 'rate' => 0.20],
            ['min' => 480000, 'max' => 960000, 'rate' => 0.30],
            ['min' => 960000, 'max' => 1920000, 'rate' => 0.35],
            ['min' => 1920000, 'max' => PHP_FLOAT_MAX, 'rate' => 0.35]
        ];

        return $this->applyBrackets($gross, $brackets);
    }

    /**
     * Egypt - Progressive tax rates
     */
    private function calculateEgyptTax(float $gross): array
    {
        $brackets = [
            ['min' => 0, 'max' => 15000, 'rate' => 0.011],
            ['min' => 15000, 'max' => 30000, 'rate' => 0.025],
            ['min' => 30000, 'max' => 45000, 'rate' => 0.10],
            ['min' => 45000, 'max' => 60000, 'rate' => 0.15],
            ['min' => 60000, 'max' => 200000, 'rate' => 0.20],
            ['min' => 200000, 'max' => 400000, 'rate' => 0.225],
            ['min' => 400000, 'max' => PHP_FLOAT_MAX, 'rate' => 0.25]
        ];

        return $this->applyBrackets($gross, $brackets);
    }

    /**
     * Default - Simple 20% tax
     */
    private function calculateDefaultTax(float $gross): array
    {
        $rate = 0.20;
        $tax = $gross * $rate;
        
        return [
            'gross' => round($gross, 2),
            'tax' => round($tax, 2),
            'net' => round($gross - $tax, 2)
        ];
    }

    /**
     * Apply tax brackets to gross income
     */
    private function applyBrackets(float $gross, array $brackets): array
    {
        $tax = 0;

        foreach ($brackets as $bracket) {
            if ($gross > $bracket['min']) {
                $taxableInBracket = min($gross, $bracket['max']) - $bracket['min'];
                $tax += $taxableInBracket * $bracket['rate'];
            }
        }

        $net = $gross - $tax;

        return [
            'gross' => round($gross, 2),
            'tax' => round($tax, 2),
            'net' => round($net, 2)
        ];
    }
}