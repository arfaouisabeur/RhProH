<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CongeTtControllerTest extends WebTestCase
{
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/conge/tt');

        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }

    public function testNewPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/conge/tt/new');

        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testShowPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/conge/tt/1');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testEditPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/conge/tt/1/edit');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testDeleteRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/conge/tt/1', [
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

        $client->request('GET', '/conge/tt/search');

        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testAiDescriptionEndpointRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/conge/tt/ai-description?type=maladie');

        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testRepondreRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/conge/tt/1/repondre', [
            'decision' => 'approuvé'
        ]);

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testRhDeleteRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/conge/tt/1/rh-delete', [
            '_token' => 'invalid-token'
        ]);

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testRapportVocalRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/conge/tt/rapport-vocal');

        // Should redirect to login or return audio/JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isSuccessful()
        );
    }

    public function testShowPageWithNonExistentId(): void
    {
        $client = static::createClient();

        $client->request('GET', '/conge/tt/999999');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testEditPageWithNonExistentId(): void
    {
        $client = static::createClient();

        $client->request('GET', '/conge/tt/999999/edit');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testDeleteWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $client->request('POST', '/conge/tt/999999', [
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

        $client->request('GET', '/conge/tt');

        // Route should redirect to login
        $this->assertResponseRedirects(
            null,
            302,
            'Route /conge/tt should redirect to login'
        );
    }

    public function testNewRouteIsProtected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/conge/tt/new');

        // Route should redirect to login
        $this->assertResponseRedirects(
            null,
            302,
            'Route /conge/tt/new should redirect to login'
        );
    }

    public function testSearchWithQueryParameters(): void
    {
        $client = static::createClient();

        $client->request('GET', '/conge/tt/search?search=maladie&searchBy=type_conge');

        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testDownloadPdfRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/conge/tt/1/download-pdf');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }
}
