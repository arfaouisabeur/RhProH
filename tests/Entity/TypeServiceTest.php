<?php

namespace App\Tests\Entity;

use App\Entity\TypeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TypeServiceTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testTypeServiceCanBeCreated(): void
    {
        $typeService = new TypeService();
        $this->assertInstanceOf(TypeService::class, $typeService);
        $this->assertNull($typeService->getId());
    }

    public function testSetAndGetNom(): void
    {
        $typeService = new TypeService();
        $typeService->setNom('Formation professionnelle');

        $this->assertEquals('Formation professionnelle', $typeService->getNom());
    }

    public function testSetAndGetCategorie(): void
    {
        $typeService = new TypeService();
        $typeService->setCategorie('RH');

        $this->assertEquals('RH', $typeService->getCategorie());
    }

    public function testSetAndGetDescription(): void
    {
        $typeService = new TypeService();
        $typeService->setDescription('Service de formation interne pour les employés.');

        $this->assertEquals('Service de formation interne pour les employés.', $typeService->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $typeService = new TypeService();
        $typeService->setDescription(null);

        $this->assertNull($typeService->getDescription());
    }

    public function testDemandeServicesCollectionIsInitialized(): void
    {
        $typeService = new TypeService();

        $this->assertNotNull($typeService->getDemandeServices());
        $this->assertCount(0, $typeService->getDemandeServices());
    }

    public function testFluentInterface(): void
    {
        $typeService = new TypeService();

        $result = $typeService
            ->setNom('Matériel informatique')
            ->setCategorie('Logistique')
            ->setDescription('Demande de matériel pour les employés.');

        $this->assertSame($typeService, $result);
    }

    public function testDifferentCategorieValues(): void
    {
        $typeService = new TypeService();

        $categories = ['RH', 'Logistique', 'Informatique', 'Finance', 'Juridique'];

        foreach ($categories as $categorie) {
            $typeService->setCategorie($categorie);
            $this->assertEquals($categorie, $typeService->getCategorie());
        }
    }

    public function testNullableFields(): void
    {
        $typeService = new TypeService();

        $this->assertNull($typeService->getId());
        $this->assertNull($typeService->getDescription());
    }

    public function testDemandeServicesCollectionIsEmpty(): void
    {
        $typeService = new TypeService();

        $this->assertTrue($typeService->getDemandeServices()->isEmpty());
    }
}
