<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EmployeeContractControllerTest extends WebTestCase
{
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/employe/contracts/');
        
        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }
    
    public function testPdfGenerationRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/employe/contracts/1/pdf');
        
        // Should redirect to login or throw access denied
        $this->assertTrue(
            $client->getResponse()->isRedirect() || 
            $client->getResponse()->getStatusCode() === 403 ||
            $client->getResponse()->getStatusCode() === 404
        );
    }
}
