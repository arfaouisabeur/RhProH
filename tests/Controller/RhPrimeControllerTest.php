<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RhPrimeControllerTest extends WebTestCase
{
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/rh/primes/');
        
        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }
    
    public function testNewPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/rh/primes/new');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }
    
    public function testGetTachesApiEndpointExists(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/rh/primes/contracts/1/taches');
        
        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() || 
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }
}
