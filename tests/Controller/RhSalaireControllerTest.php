<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RhSalaireControllerTest extends WebTestCase
{
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/rh/salaires/');
        
        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }
    
    public function testNewPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/rh/salaires/new');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }
    
    public function testExportEndpointRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/rh/salaires/export');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }
}
