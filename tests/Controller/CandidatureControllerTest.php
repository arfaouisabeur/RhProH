<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CandidatureControllerTest extends WebTestCase
{
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/candidature');
        
        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }

    public function testAjaxSearchEndpointExists(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/candidature/ajax-search');
        
        // Should redirect to login or return HTML
        $this->assertTrue(
            $client->getResponse()->isRedirect() || 
            $client->getResponse()->isSuccessful()
        );
    }

    public function testGenerateLettreEndpointExists(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/candidature/generate-lettre', [
            'offre_id' => 1
        ]);
        
        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() || 
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testVerifyEndpointExists(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/candidature/verify/test-hash-123');
        
        // Should return a response (the endpoint exists and responds)
        $response = $client->getResponse();
        $this->assertNotNull($response);
        
        // Accept any response that's not a 404 (endpoint exists)
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function testNewPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/candidature/new');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testShowPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/candidature/1');
        
        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testEditPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/candidature/1/edit');
        
        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testDeleteRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/candidature/1', [
            '_token' => 'invalid-token'
        ]);
        
        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testChangeStatutRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/candidature/1/statut', [
            'statut' => 'acceptee',
            '_token' => 'invalid-token'
        ]);
        
        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testMesCandidaturesRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/mes-candidatures');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testPostulerRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/mes-candidatures/new/1');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testDownloadCertificatRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/mes-candidatures/1/certificat');
        
        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testRegenerateCertificatRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/mes-candidatures/1/regenerer-certificat');
        
        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testAnalyseCvRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/candidature/1/analyse-cv');
        
        // Should redirect to login or return JSON error
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testIndexWithSearchParameter(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/candidature?q=test');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testIndexWithStatutFilter(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/candidature?statut=en_attente');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testAjaxSearchWithParameters(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/candidature/ajax-search?q=test&statut=acceptee');
        
        // Should redirect to login or return HTML
        $this->assertTrue(
            $client->getResponse()->isRedirect() || 
            $client->getResponse()->isSuccessful()
        );
    }
}
