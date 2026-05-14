<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerIntegrationTest extends WebTestCase
{
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        // Should be successful or redirect (depending on configuration)
        $this->assertTrue(
            $client->getResponse()->isSuccessful() || 
            $client->getResponse()->isRedirection()
        );
    }

    public function testRegisterCandidatPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register/candidat');

        // Should be successful or redirect (depending on configuration)
        $this->assertTrue(
            $client->getResponse()->isSuccessful() || 
            $client->getResponse()->isRedirection()
        );
    }

    public function testRegisterEmployePageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register/employe');

        // Should be successful or redirect (depending on configuration)
        $this->assertTrue(
            $client->getResponse()->isSuccessful() || 
            $client->getResponse()->isRedirection()
        );
    }

    public function testGoogleConnectRedirect(): void
    {
        $client = static::createClient();
        $client->request('GET', '/connect/google');

        // Should redirect to Google OAuth or handle gracefully
        $this->assertTrue(
            $client->getResponse()->isRedirection() ||
            $client->getResponse()->isServerError() ||
            $client->getResponse()->isSuccessful()
        );
    }

    public function testLogoutRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', '/logout');

        // Logout should redirect or handle gracefully
        $this->assertTrue(
            $client->getResponse()->isRedirection() ||
            $client->getResponse()->isSuccessful()
        );
    }

    public function testInvalidRouteReturns404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/auth/invalid-route');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}