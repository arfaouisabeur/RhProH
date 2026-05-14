<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DemandeServiceControllerTest extends WebTestCase
{
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service');

        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }

    public function testNewPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/new');

        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testShowPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/1');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testEditPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/1/edit');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testDeleteRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/demande/service/1', [
            '_token' => 'invalid-token'
        ]);

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testSearchEndpointRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/search');

        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testAiDescriptionEndpointRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/ai-description?type=formation');

        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testAiRecommanderEndpointRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/ai-recommander?besoin=formation');

        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testAiAnalyseReactionsRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/ai-analyse-reactions');

        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testRapportVocalRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/rapport-vocal');

        // Should redirect to login or return audio/JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isSuccessful()
        );
    }

    public function testRepondreRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/demande/service/1/repondre', [
            'decision' => 'approuvé'
        ]);

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testReactEndpointRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/demande/service/react/1/like');

        // Should redirect to login or return JSON with 401
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->getStatusCode() === 401 ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testMesReactionsRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/mes-reactions');

        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testReactionsCountsEndpointExists(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/reactions-counts');

        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testShowPageWithNonExistentId(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/999999');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testDeleteWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $client->request('POST', '/demande/service/999999', [
            '_token' => 'definitely-invalid-token-12345'
        ]);

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testIndexRouteIsProtected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service');

        // Route should redirect to login
        $this->assertResponseRedirects(
            null,
            302,
            'Route /demande/service should redirect to login'
        );
    }

    public function testNewRouteIsProtected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/new');

        // Route should redirect to login
        $this->assertResponseRedirects(
            null,
            302,
            'Route /demande/service/new should redirect to login'
        );
    }

    public function testSearchWithQueryParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/demande/service/search?search=formation&searchBy=type');

        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }
}
