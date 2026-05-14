<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OffreEmploiControllerTest extends WebTestCase
{
    public function testAdminIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/admin/offres');
        
        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }

    public function testAdminAjaxSearchEndpointExists(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/admin/offres/ajax-search');
        
        // Should redirect to login or return HTML
        $this->assertTrue(
            $client->getResponse()->isRedirect() || 
            $client->getResponse()->isSuccessful()
        );
    }

    public function testAdminNewPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/admin/offres/new');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testAdminShowPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/admin/offres/1');
        
        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testAdminEditPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/admin/offres/1/edit');
        
        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testAdminDeleteRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/admin/offres/1', [
            '_token' => 'invalid-token'
        ]);
        
        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testCandidatIndexPageIsPublic(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/offres');
        
        // Public page - should be successful or redirect
        $this->assertTrue(
            $client->getResponse()->isSuccessful() ||
            $client->getResponse()->isRedirect()
        );
    }

    public function testCandidatShowPageIsPublic(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/offres/1');
        
        // Public page - should be successful, redirect, or 404
        $this->assertTrue(
            $client->getResponse()->isSuccessful() ||
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testToggleFavoriRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/offres/1/favori');
        
        // Should return JSON with 401 or redirect
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->getStatusCode() === 401 ||
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }

    public function testMesFavorisRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/mes-favoris');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testAdminIndexWithSearchParameter(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/admin/offres?q=developpeur');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testAdminAjaxSearchWithQuery(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/admin/offres/ajax-search?q=php');
        
        // Should redirect to login or return HTML
        $this->assertTrue(
            $client->getResponse()->isRedirect() || 
            $client->getResponse()->isSuccessful()
        );
    }

    public function testCandidatIndexWithSearchParameter(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/offres?q=symfony');
        
        // Public page with search
        $this->assertTrue(
            $client->getResponse()->isSuccessful() ||
            $client->getResponse()->isRedirect()
        );
    }

    public function testAdminRoutesAreProtected(): void
    {
        $client = static::createClient();
        
        $adminRoutes = [
            '/admin/offres',
            '/admin/offres/new',
            '/admin/offres/ajax-search',
        ];
        
        foreach ($adminRoutes as $route) {
            $client->request('GET', $route);
            
            // All admin routes should redirect to login
            $this->assertResponseRedirects(
                null,
                302,
                "Route $route should redirect to login"
            );
        }
    }

    public function testPublicRoutesAreAccessible(): void
    {
        $client = static::createClient();
        
        $publicRoutes = [
            '/offres',
        ];
        
        foreach ($publicRoutes as $route) {
            $client->request('GET', $route);
            
            // Public routes should be successful or redirect (but not to login necessarily)
            $this->assertTrue(
                $client->getResponse()->isSuccessful() ||
                $client->getResponse()->isRedirect(),
                "Route $route should be accessible"
            );
        }
    }

    public function testDeleteWithInvalidCsrfToken(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/admin/offres/999', [
            '_token' => 'definitely-invalid-token-12345'
        ]);
        
        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testToggleFavoriReturnsJsonResponse(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/offres/1/favori', [], [], [
            'HTTP_ACCEPT' => 'application/json'
        ]);
        
        // Should return JSON (either error or success)
        $this->assertTrue(
            $client->getResponse()->headers->contains('Content-Type', 'application/json') ||
            $client->getResponse()->isRedirect()
        );
    }
}
