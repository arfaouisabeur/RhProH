<?php

namespace App\Tests\Unit;

use App\Controller\RHController;
use PHPUnit\Framework\TestCase;

class RHControllerUnitTest extends TestCase
{
    public function testRHControllerExists(): void
    {
        $this->assertTrue(class_exists(RHController::class));
    }

    public function testDashboardMethodExists(): void
    {
        $this->assertTrue(method_exists(RHController::class, 'dashboard'));
    }

    public function testListCandidatsMethodExists(): void
    {
        $this->assertTrue(method_exists(RHController::class, 'listCandidats'));
    }

    public function testListEmployesMethodExists(): void
    {
        $this->assertTrue(method_exists(RHController::class, 'listEmployes'));
    }

    public function testExportPdfMethodExists(): void
    {
        $this->assertTrue(method_exists(RHController::class, 'exportPdf'));
    }

    public function testExportCsvMethodExists(): void
    {
        $this->assertTrue(method_exists(RHController::class, 'exportCsv'));
    }

    public function testEditUserMethodExists(): void
    {
        $this->assertTrue(method_exists(RHController::class, 'editUser'));
    }

    public function testToggleStatutMethodExists(): void
    {
        $this->assertTrue(method_exists(RHController::class, 'toggleStatut'));
    }

    public function testViewUserMethodExists(): void
    {
        $this->assertTrue(method_exists(RHController::class, 'viewUser'));
    }

    public function testControllerExtendsAbstractController(): void
    {
        $reflection = new \ReflectionClass(RHController::class);
        $parentClass = $reflection->getParentClass();
        
        $this->assertNotFalse($parentClass);
        $this->assertEquals('Symfony\Bundle\FrameworkBundle\Controller\AbstractController', $parentClass->getName());
    }

    public function testControllerHasRouteAttribute(): void
    {
        $reflection = new \ReflectionClass(RHController::class);
        $attributes = $reflection->getAttributes();
        
        // Check if class has attributes (routes, security, etc.)
        $this->assertNotEmpty($attributes);
    }
}