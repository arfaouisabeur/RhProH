<?php

namespace App\Tests\Repository;

use App\Entity\Employe;
use App\Entity\Projet;
use App\Entity\RH;
use App\Entity\User;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProjetRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?ProjetRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(Projet::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }

    private function createDependencies(): array
    {
        // Créer un User pour le RH
        $userRh = new User();
        $userRh->setEmail('rh' . uniqid() . '@example.com');
        $userRh->setMotDePasse('password');
        $userRh->setNom('RH');
        $userRh->setPrenom('Test');
        $userRh->setRole('RH');
        $this->entityManager->persist($userRh);
        $this->entityManager->flush();

        $rh = new RH();
        $rh->setUser($userRh);
        $this->entityManager->persist($rh);
        $this->entityManager->flush();

        // Créer un User pour l'Employe
        $userEmp = new User();
        $userEmp->setEmail('emp' . uniqid() . '@example.com');
        $userEmp->setMotDePasse('password');
        $userEmp->setNom('Dupont');
        $userEmp->setPrenom('Jean');
        $userEmp->setRole('EMPLOYE');
        $this->entityManager->persist($userEmp);
        $this->entityManager->flush();

        $employe = new Employe();
        $employe->setUser($userEmp);
        $employe->setMatricule('MAT' . uniqid());
        $employe->setPosition('Développeur');
        $employe->setDateEmbauche(new \DateTimeImmutable('2024-01-01'));
        $this->entityManager->persist($employe);
        $this->entityManager->flush();

        return [$rh, $employe, $userRh, $userEmp];
    }

    public function testRepositoryIsInstanceOfProjetRepository(): void
    {
        $this->assertInstanceOf(ProjetRepository::class, $this->repository);
    }

    public function testSearchByTitre(): void
    {
        [$rh, $employe, $userRh, $userEmp] = $this->createDependencies();

        $projet = new Projet();
        $projet->setTitre('Nouveau Site Web');
        $projet->setStatut('en_cours');
        $projet->setDateDebut(new \DateTimeImmutable('+1 day'));
        $projet->setDateFin(new \DateTimeImmutable('+30 days'));
        $projet->setRh($rh);
        $projet->setResponsableEmploye($employe);

        $this->entityManager->persist($projet);
        $this->entityManager->flush();

        $results = $this->repository->search('nouveau');

        $this->assertGreaterThanOrEqual(1, count($results));
        $found = false;
        foreach ($results as $r) {
            if ($r->getId() === $projet->getId()) {
                $found = true;
                $this->assertEquals('Nouveau Site Web', $r->getTitre());
            }
        }
        $this->assertTrue($found);

        // Clean up
        $this->entityManager->remove($projet);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userEmp);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testSearchByResponsableName(): void
    {
        [$rh, $employe, $userRh, $userEmp] = $this->createDependencies();

        $projet = new Projet();
        $projet->setTitre('Application Mobile');
        $projet->setStatut('a_faire');
        $projet->setDateDebut(new \DateTimeImmutable('+1 day'));
        $projet->setDateFin(new \DateTimeImmutable('+60 days'));
        $projet->setRh($rh);
        $projet->setResponsableEmploye($employe);

        $this->entityManager->persist($projet);
        $this->entityManager->flush();

        // Search by user last name 'Dupont'
        $results = $this->repository->search('dupont');

        $this->assertGreaterThanOrEqual(1, count($results));
        $found = false;
        foreach ($results as $r) {
            if ($r->getId() === $projet->getId()) {
                $found = true;
                $this->assertEquals('Application Mobile', $r->getTitre());
            }
        }
        $this->assertTrue($found);

        // Clean up
        $this->entityManager->remove($projet);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userEmp);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testSearchByStatut(): void
    {
        [$rh, $employe, $userRh, $userEmp] = $this->createDependencies();

        $projet1 = new Projet();
        $projet1->setTitre('Projet A');
        $projet1->setStatut('en_cours');
        $projet1->setDateDebut(new \DateTimeImmutable('+1 day'));
        $projet1->setDateFin(new \DateTimeImmutable('+30 days'));
        $projet1->setRh($rh);

        $projet2 = new Projet();
        $projet2->setTitre('Projet B');
        $projet2->setStatut('terminee');
        $projet2->setDateDebut(new \DateTimeImmutable('+1 day'));
        $projet2->setDateFin(new \DateTimeImmutable('+30 days'));
        $projet2->setRh($rh);

        $this->entityManager->persist($projet1);
        $this->entityManager->persist($projet2);
        $this->entityManager->flush();

        // Search with status filter
        $results = $this->repository->search(null, 'terminee');

        $this->assertIsArray($results);
        $found1 = false;
        $found2 = false;
        foreach ($results as $r) {
            if ($r->getId() === $projet1->getId()) {
                $found1 = true;
            }
            if ($r->getId() === $projet2->getId()) {
                $found2 = true;
                $this->assertEquals('terminee', $r->getStatut());
            }
        }
        $this->assertFalse($found1); // Should not find Projet A
        $this->assertTrue($found2);  // Should find Projet B

        // Clean up
        $this->entityManager->remove($projet1);
        $this->entityManager->remove($projet2);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userEmp);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testGetStatusStats(): void
    {
        [$rh, $employe, $userRh, $userEmp] = $this->createDependencies();

        $projet1 = new Projet();
        $projet1->setTitre('Stats Projet 1');
        $projet1->setStatut('en_cours');
        $projet1->setDateDebut(new \DateTimeImmutable('+1 day'));
        $projet1->setDateFin(new \DateTimeImmutable('+30 days'));
        $projet1->setRh($rh);

        $projet2 = new Projet();
        $projet2->setTitre('Stats Projet 2');
        $projet2->setStatut('en_cours');
        $projet2->setDateDebut(new \DateTimeImmutable('+1 day'));
        $projet2->setDateFin(new \DateTimeImmutable('+30 days'));
        $projet2->setRh($rh);

        $projet3 = new Projet();
        $projet3->setTitre('Stats Projet 3');
        $projet3->setStatut('a_faire');
        $projet3->setDateDebut(new \DateTimeImmutable('+1 day'));
        $projet3->setDateFin(new \DateTimeImmutable('+30 days'));
        $projet3->setRh($rh);

        $this->entityManager->persist($projet1);
        $this->entityManager->persist($projet2);
        $this->entityManager->persist($projet3);
        $this->entityManager->flush();

        $stats = $this->repository->getStatusStats();

        $this->assertIsArray($stats);

        $enCoursCount = 0;
        $aFaireCount = 0;

        foreach ($stats as $stat) {
            if ($stat['statut'] === 'en_cours') {
                $enCoursCount = $stat['count'];
            } elseif ($stat['statut'] === 'a_faire') {
                $aFaireCount = $stat['count'];
            }
        }

        $this->assertGreaterThanOrEqual(2, $enCoursCount);
        $this->assertGreaterThanOrEqual(1, $aFaireCount);

        // Clean up
        $this->entityManager->remove($projet1);
        $this->entityManager->remove($projet2);
        $this->entityManager->remove($projet3);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userEmp);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }
}
