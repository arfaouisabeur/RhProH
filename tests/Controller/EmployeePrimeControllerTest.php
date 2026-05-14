<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EmployeePrimeControllerTest extends WebTestCase
{
    public function testIndexPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/employe/primes/');
        
        // Should redirect to login if not authenticated
        $this->assertResponseRedirects();
    }
}
