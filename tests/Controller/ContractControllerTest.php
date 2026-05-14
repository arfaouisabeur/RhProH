<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContractControllerTest extends WebTestCase
{
    public function testIndexPageLoads(): void
    {
        $client = static::createClient();
        
        // This test requires authentication, so we'll test the redirect
        $client->request('GET', '/rh/contracts/');
        
        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }
    
    public function testNewPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/rh/contracts/new');
        
        // Should redirect to login
        $this->assertResponseRedirects();
    }
    
    public function testCheckActiveEndpointExists(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/rh/contracts/check-active/1');
        
        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() || 
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }
    
    public function testAverageSalaryApiEndpointExists(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/rh/contracts/api/average-salary?country=TN');
        
        // Should redirect to login or return JSON
        $this->assertTrue(
            $client->getResponse()->isRedirect() || 
            $client->getResponse()->headers->contains('Content-Type', 'application/json')
        );
    }
}
