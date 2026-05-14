<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReponseControllerTest extends WebTestCase
{
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reponse');

        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }

    public function testNewPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reponse/new');

        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testShowPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reponse/1');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testEditPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reponse/1/edit');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testDeleteRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/reponse/1', [
            '_token' => 'invalid-token'
        ]);

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testShowPageWithNonExistentId(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reponse/999999');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testEditPageWithNonExistentId(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reponse/999999/edit');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testDeleteWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $client->request('POST', '/reponse/999999', [
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

        $client->request('GET', '/reponse');

        // Route should redirect to login (ROLE_RH required)
        $this->assertResponseRedirects(
            null,
            302,
            'Route /reponse should redirect to login'
        );
    }

    public function testNewRouteIsProtected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reponse/new');

        // Route should redirect to login
        $this->assertResponseRedirects(
            null,
            302,
            'Route /reponse/new should redirect to login'
        );
    }

    public function testAllRoutesAreProtected(): void
    {
        $client = static::createClient();

        $routes = [
            '/reponse',
            '/reponse/new',
        ];

        foreach ($routes as $route) {
            $client->request('GET', $route);

            // All routes should redirect to login
            $this->assertResponseRedirects(
                null,
                302,
                "Route $route should redirect to login"
            );
        }
    }
}
