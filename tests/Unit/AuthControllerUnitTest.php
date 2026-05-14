<?php

namespace App\Tests\Unit;

use App\Controller\AuthController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerUnitTest extends TestCase
{
    public function testAuthControllerExists(): void
    {
        $this->assertTrue(class_exists(AuthController::class));
    }

    public function testLoginMethodExists(): void
    {
        $this->assertTrue(method_exists(AuthController::class, 'login'));
    }

    public function testRegisterCandidatMethodExists(): void
    {
        $this->assertTrue(method_exists(AuthController::class, 'registerCandidat'));
    }

    public function testRegisterEmployeMethodExists(): void
    {
        $this->assertTrue(method_exists(AuthController::class, 'registerEmploye'));
    }

    public function testConnectGoogleMethodExists(): void
    {
        $this->assertTrue(method_exists(AuthController::class, 'connectGoogle'));
    }

    public function testConnectGoogleCheckMethodExists(): void
    {
        $this->assertTrue(method_exists(AuthController::class, 'connectGoogleCheck'));
    }

    public function testLogoutMethodExists(): void
    {
        $this->assertTrue(method_exists(AuthController::class, 'logout'));
    }

    public function testControllerExtendsAbstractController(): void
    {
        $reflection = new \ReflectionClass(AuthController::class);
        $parentClass = $reflection->getParentClass();
        
        $this->assertNotFalse($parentClass);
        $this->assertEquals('Symfony\Bundle\FrameworkBundle\Controller\AbstractController', $parentClass->getName());
    }
}