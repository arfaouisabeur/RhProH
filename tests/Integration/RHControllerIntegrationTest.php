<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RHControllerIntegrationTest extends WebTestCase
{
    public function testRHDashboardRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/rh/dashboard');

        // Should redirect to login or return 403/401
        $this->assertTrue(
            $client->getResponse()->isRedirection() ||
            $client->getResponse()->getStatusCode() === 403 ||
            $client->getResponse()->getStatusCode() === 401
        );
    }

    public function testRHCandidatsRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/rh/candidats');

        // Should redirect to login or return 403/401
        $this->assertTrue(
            $client->getResponse()->isRedirection() ||
            $client->getResponse()->getStatusCode() === 403 ||
            $client->getResponse()->getStatusCode() === 401
        );
    }

    public function testRHEmployesRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/rh/employes');

        // Should redirect to login or return 403/401
        $this->assertTrue(
            $client->getResponse()->isRedirection() ||
            $client->getResponse()->getStatusCode() === 403 ||
            $client->getResponse()->getStatusCode() === 401
        );
    }

    public function testExportPdfRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/rh/export/pdf/candidats');

        // Should redirect to login or return 403/401
        $this->assertTrue(
            $client->getResponse()->isRedirection() ||
            $client->getResponse()->getStatusCode() === 403 ||
            $client->getResponse()->getStatusCode() === 401
        );
    }

    public function testExportCsvRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/rh/export/csv/candidats');

        // Should redirect to login or return 403/401
        $this->assertTrue(
            $client->getResponse()->isRedirection() ||
            $client->getResponse()->getStatusCode() === 403 ||
            $client->getResponse()->getStatusCode() === 401
        );
    }

    public function testInvalidExportTypeReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/rh/export/pdf/invalid');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testToggleStatutRequiresPost(): void
    {
        $client = static::createClient();
        $client->request('GET', '/rh/user/1/toggle-statut');

        // Should return method not allowed or redirect
        $this->assertTrue(
            $client->getResponse()->getStatusCode() === 405 ||
            $client->getResponse()->isRedirection() ||
            $client->getResponse()->getStatusCode() === 403 ||
            $client->getResponse()->getStatusCode() === 401
        );
    }
}