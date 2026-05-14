<?php

namespace App\Tests\Repository;

use App\Entity\DemandeService;
use App\Entity\TypeService;
use App\Repository\DemandeServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DemandeServiceRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?DemandeServiceRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(DemandeService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }

    private function createEmployeAndTypeService(): array
    {
        $user = new \App\Entity\User();
        $user->setEmail('employe' . uniqid() . '@example.com');
        $user->setMotDePasse('password');
        $user->setNom('Martin');
        $user->setPrenom('Sophie');
        $user->setRole('EMPLOYE');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $employe = new \App\Entity\Employe();
        $employe->setUser($user);
        $employe->setMatricule('MAT' . uniqid());
        $employe->setPosition('Analyste');
        $employe->setDateEmbauche(new \DateTimeImmutable('2022-06-01'));
        $this->entityManager->persist($employe);
        $this->entityManager->flush();

        $typeService = new TypeService();
        $typeService->setNom('Formation ' . uniqid());
        $typeService->setCategorie('RH');
        $this->entityManager->persist($typeService);
        $this->entityManager->flush();

        return [$employe, $user, $typeService];
    }

    public function testRepositoryIsInstanceOfDemandeServiceRepository(): void
    {
        $this->assertInstanceOf(DemandeServiceRepository::class, $this->repository);
    }

    public function testCanFindAllDemandeServices(): void
    {
        $result = $this->repository->findAll();

        $this->assertIsArray($result);
    }

    public function testCanPersistAndFindDemandeService(): void
    {
        [$employe, $user, $typeService] = $this->createEmployeAndTypeService();

        $demande = new DemandeService();
        $demande->setTitre('Formation développement web');
        $demande->setDescription('Besoin d\'une formation en Symfony.');
        $demande->setDateDemande('2024-05-01');
        $demande->setStatut('En attente');
        $demande->setEmploye($employe);
        $demande->setType($typeService);

        $this->entityManager->persist($demande);
        $this->entityManager->flush();

        $foundDemande = $this->repository->find($demande->getId());

        $this->assertNotNull($foundDemande);
        $this->assertEquals('Formation développement web', $foundDemande->getTitre());
        $this->assertEquals('En attente', $foundDemande->getStatut());
        $this->assertEquals('2024-05-01', $foundDemande->getDateDemande());

        // Clean up
        $this->entityManager->remove($foundDemande);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCanFindDemandeServiceByStatut(): void
    {
        [$employe, $user, $typeService] = $this->createEmployeAndTypeService();

        $demande = new DemandeService();
        $demande->setTitre('Achat de matériel');
        $demande->setDateDemande('2024-06-01');
        $demande->setStatut('Accepté');
        $demande->setEmploye($employe);
        $demande->setType($typeService);

        $this->entityManager->persist($demande);
        $this->entityManager->flush();

        $foundDemandes = $this->repository->findBy(['statut' => 'Accepté']);

        $this->assertGreaterThanOrEqual(1, count($foundDemandes));
        $this->assertEquals('Accepté', $foundDemandes[0]->getStatut());

        // Clean up
        $this->entityManager->remove($demande);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCanFindOneDemandeServiceByMultipleCriteria(): void
    {
        [$employe, $user, $typeService] = $this->createEmployeAndTypeService();

        $demande = new DemandeService();
        $demande->setTitre('Demande de télétravail');
        $demande->setDateDemande('2024-07-01');
        $demande->setStatut('Refusé');
        $demande->setPriorite('haute');
        $demande->setEmploye($employe);
        $demande->setType($typeService);

        $this->entityManager->persist($demande);
        $this->entityManager->flush();

        $foundDemande = $this->repository->findOneBy([
            'statut'   => 'Refusé',
            'priorite' => 'haute',
        ]);

        $this->assertNotNull($foundDemande);
        $this->assertEquals('Refusé', $foundDemande->getStatut());
        $this->assertEquals('haute', $foundDemande->getPriorite());

        // Clean up
        $this->entityManager->remove($foundDemande);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCanUpdateDemandeService(): void
    {
        [$employe, $user, $typeService] = $this->createEmployeAndTypeService();

        $demande = new DemandeService();
        $demande->setTitre('Demande initiale');
        $demande->setDateDemande('2024-08-01');
        $demande->setStatut('En attente');
        $demande->setEmploye($employe);
        $demande->setType($typeService);

        $this->entityManager->persist($demande);
        $this->entityManager->flush();

        $id = $demande->getId();
        $typeServiceId = $typeService->getId();
        $employeId = $employe->getUserId();
        $userId = $user->getId();

        // Update
        $demande->setStatut('Accepté');
        $demande->setEtapeWorkflow('validation_finale');
        $this->entityManager->flush();

        // Verify update
        $this->entityManager->clear();
        $updatedDemande = $this->repository->find($id);

        $this->assertEquals('Accepté', $updatedDemande->getStatut());
        $this->assertEquals('validation_finale', $updatedDemande->getEtapeWorkflow());

        // Clean up - re-fetch entities after clear()
        $typeServiceToRemove = $this->entityManager->getRepository(TypeService::class)->find($typeServiceId);
        $employeToRemove     = $this->entityManager->getRepository(\App\Entity\Employe::class)->find($employeId);
        $userToRemove        = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);

        $this->entityManager->remove($updatedDemande);
        $this->entityManager->remove($typeServiceToRemove);
        $this->entityManager->remove($employeToRemove);
        $this->entityManager->remove($userToRemove);
        $this->entityManager->flush();
    }

    public function testCanDeleteDemandeService(): void
    {
        [$employe, $user, $typeService] = $this->createEmployeAndTypeService();

        $demande = new DemandeService();
        $demande->setTitre('Demande à supprimer');
        $demande->setDateDemande('2024-09-01');
        $demande->setStatut('En attente');
        $demande->setEmploye($employe);
        $demande->setType($typeService);

        $this->entityManager->persist($demande);
        $this->entityManager->flush();

        $id = $demande->getId();

        // Delete
        $this->entityManager->remove($demande);
        $this->entityManager->flush();

        // Verify deletion
        $deletedDemande = $this->repository->find($id);
        $this->assertNull($deletedDemande);

        // Clean up
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCanPersistDemandeServiceWithoutDescription(): void
    {
        [$employe, $user, $typeService] = $this->createEmployeAndTypeService();

        $demande = new DemandeService();
        $demande->setTitre('Demande sans description');
        $demande->setDateDemande('2024-10-01');
        $demande->setStatut('En attente');
        $demande->setEmploye($employe);
        $demande->setType($typeService);

        $this->entityManager->persist($demande);
        $this->entityManager->flush();

        $foundDemande = $this->repository->find($demande->getId());

        $this->assertNotNull($foundDemande);
        $this->assertNull($foundDemande->getDescription());

        // Clean up
        $this->entityManager->remove($foundDemande);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testFindReturnsNullForNonExistentId(): void
    {
        $nonExistentId = 999999;
        $demande = $this->repository->find($nonExistentId);

        $this->assertNull($demande);
    }

    public function testFindOneByReturnsNullWhenNoMatch(): void
    {
        $demande = $this->repository->findOneBy([
            'statut'   => 'StatutInexistant',
            'priorite' => 'PrioriteInexistante',
        ]);

        $this->assertNull($demande);
    }

    public function testCanFindMultipleDemandeServices(): void
    {
        [$employe, $user, $typeService] = $this->createEmployeAndTypeService();

        $demande1 = new DemandeService();
        $demande1->setTitre('Première demande');
        $demande1->setDateDemande('2024-01-15');
        $demande1->setStatut('En attente');
        $demande1->setEmploye($employe);
        $demande1->setType($typeService);

        $demande2 = new DemandeService();
        $demande2->setTitre('Deuxième demande');
        $demande2->setDateDemande('2024-02-20');
        $demande2->setStatut('Accepté');
        $demande2->setEmploye($employe);
        $demande2->setType($typeService);

        $this->entityManager->persist($demande1);
        $this->entityManager->persist($demande2);
        $this->entityManager->flush();

        $demandes = $this->repository->findAll();

        $this->assertGreaterThanOrEqual(2, count($demandes));

        // Clean up
        $this->entityManager->remove($demande1);
        $this->entityManager->remove($demande2);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
