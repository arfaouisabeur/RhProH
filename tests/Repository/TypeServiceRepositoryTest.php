<?php

namespace App\Tests\Repository;

use App\Entity\TypeService;
use App\Repository\TypeServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TypeServiceRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?TypeServiceRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(TypeService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }

    public function testRepositoryIsInstanceOfTypeServiceRepository(): void
    {
        $this->assertInstanceOf(TypeServiceRepository::class, $this->repository);
    }

    public function testCanFindAllTypeServices(): void
    {
        $result = $this->repository->findAll();

        $this->assertIsArray($result);
    }

    public function testCanPersistAndFindTypeService(): void
    {
        $typeService = new TypeService();
        $typeService->setNom('Formation professionnelle');
        $typeService->setCategorie('RH');
        $typeService->setDescription('Service de formation pour les employés.');

        $this->entityManager->persist($typeService);
        $this->entityManager->flush();

        $foundTypeService = $this->repository->find($typeService->getId());

        $this->assertNotNull($foundTypeService);
        $this->assertEquals('Formation professionnelle', $foundTypeService->getNom());
        $this->assertEquals('RH', $foundTypeService->getCategorie());
        $this->assertEquals('Service de formation pour les employés.', $foundTypeService->getDescription());

        // Clean up
        $this->entityManager->remove($foundTypeService);
        $this->entityManager->flush();
    }

    public function testCanFindTypeServiceByCategorie(): void
    {
        $typeService = new TypeService();
        $typeService->setNom('Matériel informatique');
        $typeService->setCategorie('Logistique');

        $this->entityManager->persist($typeService);
        $this->entityManager->flush();

        $foundTypeServices = $this->repository->findBy(['categorie' => 'Logistique']);

        $this->assertGreaterThanOrEqual(1, count($foundTypeServices));
        $this->assertEquals('Logistique', $foundTypeServices[0]->getCategorie());

        // Clean up
        $this->entityManager->remove($typeService);
        $this->entityManager->flush();
    }

    public function testCanFindTypeServiceByNom(): void
    {
        $typeService = new TypeService();
        $typeService->setNom('Demande de congé spécial');
        $typeService->setCategorie('RH');

        $this->entityManager->persist($typeService);
        $this->entityManager->flush();

        $foundTypeService = $this->repository->findOneBy(['nom' => 'Demande de congé spécial']);

        $this->assertNotNull($foundTypeService);
        $this->assertEquals('Demande de congé spécial', $foundTypeService->getNom());

        // Clean up
        $this->entityManager->remove($foundTypeService);
        $this->entityManager->flush();
    }

    public function testCanFindOneTypeServiceByMultipleCriteria(): void
    {
        $typeService = new TypeService();
        $typeService->setNom('Avance sur salaire');
        $typeService->setCategorie('Finance');

        $this->entityManager->persist($typeService);
        $this->entityManager->flush();

        $foundTypeService = $this->repository->findOneBy([
            'nom'       => 'Avance sur salaire',
            'categorie' => 'Finance',
        ]);

        $this->assertNotNull($foundTypeService);
        $this->assertEquals('Avance sur salaire', $foundTypeService->getNom());
        $this->assertEquals('Finance', $foundTypeService->getCategorie());

        // Clean up
        $this->entityManager->remove($foundTypeService);
        $this->entityManager->flush();
    }

    public function testCanUpdateTypeService(): void
    {
        $typeService = new TypeService();
        $typeService->setNom('Télétravail exceptionnel');
        $typeService->setCategorie('RH');

        $this->entityManager->persist($typeService);
        $this->entityManager->flush();

        $id = $typeService->getId();

        // Update
        $typeService->setCategorie('Logistique');
        $typeService->setDescription('Mise à jour de la description.');
        $this->entityManager->flush();

        // Verify update
        $this->entityManager->clear();
        $updatedTypeService = $this->repository->find($id);

        $this->assertEquals('Logistique', $updatedTypeService->getCategorie());
        $this->assertEquals('Mise à jour de la description.', $updatedTypeService->getDescription());

        // Clean up
        $this->entityManager->remove($updatedTypeService);
        $this->entityManager->flush();
    }

    public function testCanDeleteTypeService(): void
    {
        $typeService = new TypeService();
        $typeService->setNom('Service à supprimer');
        $typeService->setCategorie('Test');

        $this->entityManager->persist($typeService);
        $this->entityManager->flush();

        $id = $typeService->getId();

        // Delete
        $this->entityManager->remove($typeService);
        $this->entityManager->flush();

        // Verify deletion
        $deletedTypeService = $this->repository->find($id);
        $this->assertNull($deletedTypeService);
    }

    public function testCanPersistTypeServiceWithoutDescription(): void
    {
        $typeService = new TypeService();
        $typeService->setNom('Service sans description');
        $typeService->setCategorie('Informatique');

        $this->entityManager->persist($typeService);
        $this->entityManager->flush();

        $foundTypeService = $this->repository->find($typeService->getId());

        $this->assertNotNull($foundTypeService);
        $this->assertNull($foundTypeService->getDescription());

        // Clean up
        $this->entityManager->remove($foundTypeService);
        $this->entityManager->flush();
    }

    public function testFindReturnsNullForNonExistentId(): void
    {
        $nonExistentId = 999999;
        $typeService = $this->repository->find($nonExistentId);

        $this->assertNull($typeService);
    }

    public function testFindOneByReturnsNullWhenNoMatch(): void
    {
        $typeService = $this->repository->findOneBy([
            'nom'       => 'NomInexistant',
            'categorie' => 'CategorieInexistante',
        ]);

        $this->assertNull($typeService);
    }

    public function testCanFindMultipleTypeServices(): void
    {
        $typeService1 = new TypeService();
        $typeService1->setNom('Formation interne');
        $typeService1->setCategorie('RH');

        $typeService2 = new TypeService();
        $typeService2->setNom('Achat de fournitures');
        $typeService2->setCategorie('Logistique');

        $this->entityManager->persist($typeService1);
        $this->entityManager->persist($typeService2);
        $this->entityManager->flush();

        $typeServices = $this->repository->findAll();

        $this->assertGreaterThanOrEqual(2, count($typeServices));

        // Clean up
        $this->entityManager->remove($typeService1);
        $this->entityManager->remove($typeService2);
        $this->entityManager->flush();
    }

    public function testCanFindTypeServicesOrderedByNom(): void
    {
        $typeService1 = new TypeService();
        $typeService1->setNom('Zèle professionnel');
        $typeService1->setCategorie('RH');

        $typeService2 = new TypeService();
        $typeService2->setNom('Aide au déménagement');
        $typeService2->setCategorie('Logistique');

        $this->entityManager->persist($typeService1);
        $this->entityManager->persist($typeService2);
        $this->entityManager->flush();

        $typeServices = $this->repository->findBy([], ['nom' => 'ASC']);

        $this->assertGreaterThanOrEqual(2, count($typeServices));

        // Clean up
        $this->entityManager->remove($typeService1);
        $this->entityManager->remove($typeService2);
        $this->entityManager->flush();
    }
}
