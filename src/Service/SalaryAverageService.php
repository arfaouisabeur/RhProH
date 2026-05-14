<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SalaryAverageService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Get average monthly salary based on country (using World Bank GDP per capita)
     */
    public function getAverageSalary(string $countryCode): ?float
    {
        try {
            // 🔥 API URL
            $url = "https://api.worldbank.org/v2/country/$countryCode/indicator/NY.GDP.PCAP.CD?format=json";

            // 🔥 REQUEST
            $response = $this->client->request('GET', $url);

            // 🔥 CONVERT TO ARRAY
            $data = $response->toArray();

            // 🔥 CHECK DATA EXISTS
            if (!isset($data[1])) {
                return null;
            }

            // 🔥 LOOP TO FIND FIRST VALID VALUE
            foreach ($data[1] as $record) {

                if ($record['value'] !== null) {

                    $yearlySalary = $record['value'];

                    // 🔥 CONVERT YEAR → MONTH
                    $monthlySalary = $yearlySalary / 12;

                    return round($monthlySalary, 2);
                }
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }
}