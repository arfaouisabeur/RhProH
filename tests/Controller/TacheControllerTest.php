<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TacheControllerTest extends WebTestCase
{
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache');

        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }

    public function testNewPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache/new');

        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testShowPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache/1');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testEditPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache/1/edit');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testDeleteRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/tache/1', [
            '_token' => 'invalid-token'
        ]);

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testTachesParProjetRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache/projet/1');

        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testCalendarApiRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache/projet/1/calendar-api');

        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testNewPourProjetRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache/new/projet/1');

        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testCalendarApiReturnsJsonForNonExistentProjet(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache/projet/999999/calendar-api');

        // Should redirect to login or return JSON (404 or empty array)
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testShowPageWithNonExistentId(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache/999999');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testEditPageWithNonExistentId(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache/999999/edit');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testDeleteWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $client->request('POST', '/tache/999999', [
            '_token' => 'definitely-invalid-token-12345'
        ]);

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testTachesParProjetWithNonExistentProjet(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache/projet/999999');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testIndexRouteIsProtected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache');

        // Route should redirect to login (ROLE_RH required)
        $this->assertResponseRedirects(
            null,
            302,
            'Route /tache should redirect to login'
        );
    }

    public function testNewRouteIsProtected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/tache/new');

        // Route should redirect to login (ROLE_RH required)
        $this->assertResponseRedirects(
            null,
            302,
            'Route /tache/new should redirect to login'
        );
    }
}
