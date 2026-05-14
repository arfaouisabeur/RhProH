<?php

namespace App\Tests\Repository;

use App\Entity\CongeTt;
use App\Repository\CongeTtRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CongeTtRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?CongeTtRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(CongeTt::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }

    private function createEmploye(): array
    {
        $user = new \App\Entity\User();
        $user->setEmail('employe' . uniqid() . '@example.com');
        $user->setMotDePasse('password');
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setRole('EMPLOYE');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $employe = new \App\Entity\Employe();
        $employe->setUser($user);
        $employe->setMatricule('MAT' . uniqid());
        $employe->setPosition('Développeur');
        $employe->setDateEmbauche(new \DateTimeImmutable('2023-01-01'));
        $this->entityManager->persist($employe);
        $this->entityManager->flush();

        return [$employe, $user];
    }

    public function testRepositoryIsInstanceOfCongeTtRepository(): void
    {
        $this->assertInstanceOf(CongeTtRepository::class, $this->repository);
    }

    public function testCanFindAllCongeTts(): void
    {
        $result = $this->repository->findAll();

        $this->assertIsArray($result);
    }

    public function testCanFindCongeTtsByStatut(): void
    {
        $result = $this->repository->findBy(['statut' => 'En attente']);

        $this->assertIsArray($result);
    }

    public function testCanPersistAndFindCongeTt(): void
    {
        [$employe, $user] = $this->createEmploye();

        $congeTt = new CongeTt();
        $congeTt->setTypeConge('Congé annuel');
        $congeTt->setDateDebut(new \DateTimeImmutable('2024-07-01'));
        $congeTt->setDateFin(new \DateTimeImmutable('2024-07-15'));
        $congeTt->setStatut('En attente');
        $congeTt->setDescription('Congé d\'été.');
        $congeTt->setEmploye($employe);

        $this->entityManager->persist($congeTt);
        $this->entityManager->flush();

        $foundCongeTt = $this->repository->find($congeTt->getId());

        $this->assertNotNull($foundCongeTt);
        $this->assertEquals('Congé annuel', $foundCongeTt->getTypeConge());
        $this->assertEquals('En attente', $foundCongeTt->getStatut());
        $this->assertEquals('Congé d\'été.', $foundCongeTt->getDescription());

        // Clean up
        $this->entityManager->remove($foundCongeTt);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCanFindCongeTtByStatut(): void
    {
        [$employe, $user] = $this->createEmploye();

        $congeTt = new CongeTt();
        $congeTt->setTypeConge('Congé maladie');
        $congeTt->setDateDebut(new \DateTimeImmutable('2024-03-10'));
        $congeTt->setDateFin(new \DateTimeImmutable('2024-03-17'));
        $congeTt->setStatut('Accepté');
        $congeTt->setEmploye($employe);

        $this->entityManager->persist($congeTt);
        $this->entityManager->flush();

        $foundCongeTts = $this->repository->findBy(['statut' => 'Accepté']);

        $this->assertGreaterThanOrEqual(1, count($foundCongeTts));
        $this->assertEquals('Accepté', $foundCongeTts[0]->getStatut());

        // Clean up
        $this->entityManager->remove($congeTt);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCanFindCongeTtByTypeConge(): void
    {
        [$employe, $user] = $this->createEmploye();

        $congeTt = new CongeTt();
        $congeTt->setTypeConge('Télétravail');
        $congeTt->setDateDebut(new \DateTimeImmutable('2024-04-01'));
        $congeTt->setDateFin(new \DateTimeImmutable('2024-04-05'));
        $congeTt->setStatut('En attente');
        $congeTt->setEmploye($employe);

        $this->entityManager->persist($congeTt);
        $this->entityManager->flush();

        $foundCongeTts = $this->repository->findBy(['type_conge' => 'Télétravail']);

        $this->assertGreaterThanOrEqual(1, count($foundCongeTts));
        $this->assertEquals('Télétravail', $foundCongeTts[0]->getTypeConge());

        // Clean up
        $this->entityManager->remove($congeTt);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCanFindOneCongeTtByMultipleCriteria(): void
    {
        [$employe, $user] = $this->createEmploye();

        $congeTt = new CongeTt();
        $congeTt->setTypeConge('Congé sans solde');
        $congeTt->setDateDebut(new \DateTimeImmutable('2024-08-01'));
        $congeTt->setDateFin(new \DateTimeImmutable('2024-08-31'));
        $congeTt->setStatut('Refusé');
        $congeTt->setEmploye($employe);

        $this->entityManager->persist($congeTt);
        $this->entityManager->flush();

        $foundCongeTt = $this->repository->findOneBy([
            'type_conge' => 'Congé sans solde',
            'statut'     => 'Refusé',
        ]);

        $this->assertNotNull($foundCongeTt);
        $this->assertEquals('Congé sans solde', $foundCongeTt->getTypeConge());
        $this->assertEquals('Refusé', $foundCongeTt->getStatut());

        // Clean up
        $this->entityManager->remove($foundCongeTt);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCanUpdateCongeTt(): void
    {
        [$employe, $user] = $this->createEmploye();

        $congeTt = new CongeTt();
        $congeTt->setTypeConge('Congé annuel');
        $congeTt->setDateDebut(new \DateTimeImmutable('2024-09-01'));
        $congeTt->setDateFin(new \DateTimeImmutable('2024-09-14'));
        $congeTt->setStatut('En attente');
        $congeTt->setEmploye($employe);

        $this->entityManager->persist($congeTt);
        $this->entityManager->flush();

        $id = $congeTt->getId();
        $employeId = $employe->getUserId();
        $userId = $user->getId();

        // Update
        $congeTt->setStatut('Accepté');
        $this->entityManager->flush();

        // Verify update
        $this->entityManager->clear();
        $updatedCongeTt = $this->repository->find($id);

        $this->assertEquals('Accepté', $updatedCongeTt->getStatut());

        // Clean up - re-fetch entities after clear()
        $employeToRemove = $this->entityManager->getRepository(\App\Entity\Employe::class)->find($employeId);
        $userToRemove    = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);

        $this->entityManager->remove($updatedCongeTt);
        $this->entityManager->remove($employeToRemove);
        $this->entityManager->remove($userToRemove);
        $this->entityManager->flush();
    }

    public function testCanDeleteCongeTt(): void
    {
        [$employe, $user] = $this->createEmploye();

        $congeTt = new CongeTt();
        $congeTt->setTypeConge('Congé annuel');
        $congeTt->setDateDebut(new \DateTimeImmutable('2024-10-01'));
        $congeTt->setDateFin(new \DateTimeImmutable('2024-10-07'));
        $congeTt->setStatut('En attente');
        $congeTt->setEmploye($employe);

        $this->entityManager->persist($congeTt);
        $this->entityManager->flush();

        $id = $congeTt->getId();

        // Delete
        $this->entityManager->remove($congeTt);
        $this->entityManager->flush();

        // Verify deletion
        $deletedCongeTt = $this->repository->find($id);
        $this->assertNull($deletedCongeTt);

        // Clean up
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCanPersistCongeTtWithoutDescription(): void
    {
        [$employe, $user] = $this->createEmploye();

        $congeTt = new CongeTt();
        $congeTt->setTypeConge('Congé annuel');
        $congeTt->setDateDebut(new \DateTimeImmutable('2024-11-01'));
        $congeTt->setDateFin(new \DateTimeImmutable('2024-11-10'));
        $congeTt->setStatut('En attente');
        $congeTt->setEmploye($employe);

        $this->entityManager->persist($congeTt);
        $this->entityManager->flush();

        $foundCongeTt = $this->repository->find($congeTt->getId());

        $this->assertNotNull($foundCongeTt);
        $this->assertNull($foundCongeTt->getDescription());

        // Clean up
        $this->entityManager->remove($foundCongeTt);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testFindReturnsNullForNonExistentId(): void
    {
        $nonExistentId = 999999;
        $congeTt = $this->repository->find($nonExistentId);

        $this->assertNull($congeTt);
    }

    public function testFindOneByReturnsNullWhenNoMatch(): void
    {
        $congeTt = $this->repository->findOneBy([
            'type_conge' => 'TypeInexistant',
            'statut'     => 'StatutInexistant',
        ]);

        $this->assertNull($congeTt);
    }

    public function testCanFindMultipleCongeTts(): void
    {
        [$employe, $user] = $this->createEmploye();

        $congeTt1 = new CongeTt();
        $congeTt1->setTypeConge('Congé annuel');
        $congeTt1->setDateDebut(new \DateTimeImmutable('2024-01-10'));
        $congeTt1->setDateFin(new \DateTimeImmutable('2024-01-20'));
        $congeTt1->setStatut('En attente');
        $congeTt1->setEmploye($employe);

        $congeTt2 = new CongeTt();
        $congeTt2->setTypeConge('Congé maladie');
        $congeTt2->setDateDebut(new \DateTimeImmutable('2024-02-05'));
        $congeTt2->setDateFin(new \DateTimeImmutable('2024-02-10'));
        $congeTt2->setStatut('Accepté');
        $congeTt2->setEmploye($employe);

        $this->entityManager->persist($congeTt1);
        $this->entityManager->persist($congeTt2);
        $this->entityManager->flush();

        $congeTts = $this->repository->findAll();

        $this->assertGreaterThanOrEqual(2, count($congeTts));

        // Clean up
        $this->entityManager->remove($congeTt1);
        $this->entityManager->remove($congeTt2);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
