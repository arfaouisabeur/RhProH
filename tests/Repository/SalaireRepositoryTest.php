<?php

namespace App\Tests\Repository;

use App\Entity\Salaire;
use App\Entity\Contract;
use App\Repository\SalaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SalaireRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?SalaireRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(Salaire::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }

    public function testRepositoryIsInstanceOfSalaireRepository(): void
    {
        $this->assertInstanceOf(SalaireRepository::class, $this->repository);
    }

    public function testCanPersistAndFindSalaire(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Janvier');
        $salaire->setAnnee('2024');
        $salaire->setMontant('5000');
        $salaire->setStatut('Payé');
        $salaire->setDatePaiement('2024-01-15');

        $this->entityManager->persist($salaire);
        $this->entityManager->flush();

        $foundSalaire = $this->repository->find($salaire->getId());

        $this->assertNotNull($foundSalaire);
        $this->assertEquals('Janvier', $foundSalaire->getMois());
        $this->assertEquals('2024', $foundSalaire->getAnnee());
        $this->assertEquals('5000', $foundSalaire->getMontant());
        $this->assertEquals('Payé', $foundSalaire->getStatut());
        $this->assertEquals('2024-01-15', $foundSalaire->getDatePaiement());

        // Clean up
        $this->entityManager->remove($foundSalaire);
        $this->entityManager->flush();
    }

    public function testCanFindAllSalaires(): void
    {
        $salaire1 = new Salaire();
        $salaire1->setMois('Janvier');
        $salaire1->setAnnee('2024');
        $salaire1->setMontant('5000');
        $salaire1->setStatut('Payé');

        $salaire2 = new Salaire();
        $salaire2->setMois('Février');
        $salaire2->setAnnee('2024');
        $salaire2->setMontant('5500');
        $salaire2->setStatut('En attente');

        $this->entityManager->persist($salaire1);
        $this->entityManager->persist($salaire2);
        $this->entityManager->flush();

        $salaires = $this->repository->findAll();

        $this->assertGreaterThanOrEqual(2, count($salaires));

        // Clean up
        $this->entityManager->remove($salaire1);
        $this->entityManager->remove($salaire2);
        $this->entityManager->flush();
    }

    public function testCanFindSalaireByMois(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Mars');
        $salaire->setAnnee('2024');
        $salaire->setMontant('6000');
        $salaire->setStatut('Payé');

        $this->entityManager->persist($salaire);
        $this->entityManager->flush();

        $foundSalaires = $this->repository->findBy(['mois' => 'Mars']);

        $this->assertGreaterThanOrEqual(1, count($foundSalaires));
        $this->assertEquals('Mars', $foundSalaires[0]->getMois());

        // Clean up
        $this->entityManager->remove($salaire);
        $this->entityManager->flush();
    }

    public function testCanFindSalaireByAnnee(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Avril');
        $salaire->setAnnee('2025');
        $salaire->setMontant('5200');
        $salaire->setStatut('Payé');

        $this->entityManager->persist($salaire);
        $this->entityManager->flush();

        $foundSalaires = $this->repository->findBy(['annee' => '2025']);

        $this->assertGreaterThanOrEqual(1, count($foundSalaires));
        $this->assertEquals('2025', $foundSalaires[0]->getAnnee());

        // Clean up
        $this->entityManager->remove($salaire);
        $this->entityManager->flush();
    }

    public function testCanFindSalaireByStatut(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Mai');
        $salaire->setAnnee('2024');
        $salaire->setMontant('4800');
        $salaire->setStatut('En attente');

        $this->entityManager->persist($salaire);
        $this->entityManager->flush();

        $foundSalaires = $this->repository->findBy(['statut' => 'En attente']);

        $this->assertGreaterThanOrEqual(1, count($foundSalaires));
        $this->assertEquals('En attente', $foundSalaires[0]->getStatut());

        // Clean up
        $this->entityManager->remove($salaire);
        $this->entityManager->flush();
    }

    public function testCanFindOneSalaireByMultipleCriteria(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Juin');
        $salaire->setAnnee('2024');
        $salaire->setMontant('5300');
        $salaire->setStatut('Payé');

        $this->entityManager->persist($salaire);
        $this->entityManager->flush();

        $foundSalaire = $this->repository->findOneBy([
            'mois' => 'Juin',
            'annee' => '2024',
            'statut' => 'Payé'
        ]);

        $this->assertNotNull($foundSalaire);
        $this->assertEquals('Juin', $foundSalaire->getMois());
        $this->assertEquals('2024', $foundSalaire->getAnnee());
        $this->assertEquals('Payé', $foundSalaire->getStatut());

        // Clean up
        $this->entityManager->remove($foundSalaire);
        $this->entityManager->flush();
    }

    public function testCanUpdateSalaire(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Juillet');
        $salaire->setAnnee('2024');
        $salaire->setMontant('5000');
        $salaire->setStatut('En attente');

        $this->entityManager->persist($salaire);
        $this->entityManager->flush();

        $id = $salaire->getId();

        // Update
        $salaire->setStatut('Payé');
        $salaire->setDatePaiement('2024-07-15');
        $this->entityManager->flush();

        // Verify update
        $this->entityManager->clear();
        $updatedSalaire = $this->repository->find($id);

        $this->assertEquals('Payé', $updatedSalaire->getStatut());
        $this->assertEquals('2024-07-15', $updatedSalaire->getDatePaiement());

        // Clean up
        $this->entityManager->remove($updatedSalaire);
        $this->entityManager->flush();
    }

    public function testCanDeleteSalaire(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Août');
        $salaire->setAnnee('2024');
        $salaire->setMontant('5100');
        $salaire->setStatut('Payé');

        $this->entityManager->persist($salaire);
        $this->entityManager->flush();

        $id = $salaire->getId();

        // Delete
        $this->entityManager->remove($salaire);
        $this->entityManager->flush();

        // Verify deletion
        $deletedSalaire = $this->repository->find($id);
        $this->assertNull($deletedSalaire);
    }

    public function testFindReturnsNullForNonExistentId(): void
    {
        $nonExistentId = 999999;
        $salaire = $this->repository->find($nonExistentId);

        $this->assertNull($salaire);
    }

    public function testFindOneByReturnsNullWhenNoMatch(): void
    {
        $salaire = $this->repository->findOneBy([
            'mois' => 'NonExistentMonth',
            'annee' => '9999'
        ]);

        $this->assertNull($salaire);
    }
}
