<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TypeServiceControllerTest extends WebTestCase
{
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/type/service');

        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }

    public function testNewPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/type/service/new');

        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testShowPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/type/service/1');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testEditPageRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/type/service/1/edit');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testDeleteRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('POST', '/type/service/1', [
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

        $client->request('GET', '/type/service/999999');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testEditPageWithNonExistentId(): void
    {
        $client = static::createClient();

        $client->request('GET', '/type/service/999999/edit');

        // Should redirect to login or return 404
        $this->assertTrue(
            $client->getResponse()->isRedirect() ||
            $client->getResponse()->isNotFound()
        );
    }

    public function testDeleteWithInvalidCsrfToken(): void
    {
        $client = static::createClient();

        $client->request('POST', '/type/service/999999', [
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

        $client->request('GET', '/type/service');

        // Route should redirect to login (ROLE_RH required)
        $this->assertResponseRedirects(
            null,
            302,
            'Route /type/service should redirect to login'
        );
    }

    public function testNewRouteIsProtected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/type/service/new');

        // Route should redirect to login (ROLE_RH required)
        $this->assertResponseRedirects(
            null,
            302,
            'Route /type/service/new should redirect to login'
        );
    }

    public function testIndexWithSearchParameter(): void
    {
        $client = static::createClient();

        $client->request('GET', '/type/service?search=formation');

        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testIndexWithSortParameter(): void
    {
        $client = static::createClient();

        $client->request('GET', '/type/service?sortBy=categorie&sortDir=desc');

        // Should redirect to login
        $this->assertResponseRedirects();
    }

    public function testAllRoutesAreProtected(): void
    {
        $client = static::createClient();

        $routes = [
            '/type/service',
            '/type/service/new',
        ];

        foreach ($routes as $route) {
            $client->request('GET', $route);

            // All routes should redirect to login (ROLE_RH required)
            $this->assertResponseRedirects(
                null,
                302,
                "Route $route should redirect to login"
            );
        }
    }
}
