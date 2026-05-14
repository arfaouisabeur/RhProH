<?php

namespace App\Tests\Repository;

use App\Entity\Reponse;
use App\Entity\CongeTt;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReponseRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?ReponseRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(Reponse::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }

    private function createRhAndEmploye(): array
    {
        $userRh = new \App\Entity\User();
        $userRh->setEmail('rh' . uniqid() . '@example.com');
        $userRh->setMotDePasse('password');
        $userRh->setNom('Responsable');
        $userRh->setPrenom('RH');
        $userRh->setRole('RH');
        $this->entityManager->persist($userRh);
        $this->entityManager->flush();

        $rh = new \App\Entity\RH();
        $rh->setUser($userRh);
        $this->entityManager->persist($rh);
        $this->entityManager->flush();

        $userEmploye = new \App\Entity\User();
        $userEmploye->setEmail('employe' . uniqid() . '@example.com');
        $userEmploye->setMotDePasse('password');
        $userEmploye->setNom('Durand');
        $userEmploye->setPrenom('Paul');
        $userEmploye->setRole('EMPLOYE');
        $this->entityManager->persist($userEmploye);
        $this->entityManager->flush();

        $employe = new \App\Entity\Employe();
        $employe->setUser($userEmploye);
        $employe->setMatricule('MAT' . uniqid());
        $employe->setPosition('Comptable');
        $employe->setDateEmbauche(new \DateTimeImmutable('2021-03-01'));
        $this->entityManager->persist($employe);
        $this->entityManager->flush();

        return [$rh, $userRh, $employe, $userEmploye];
    }

    private function createCongeTt(\App\Entity\Employe $employe): CongeTt
    {
        $congeTt = new CongeTt();
        $congeTt->setTypeConge('Congé annuel');
        $congeTt->setDateDebut(new \DateTimeImmutable('2024-06-01'));
        $congeTt->setDateFin(new \DateTimeImmutable('2024-06-10'));
        $congeTt->setStatut('En attente');
        $congeTt->setEmploye($employe);
        $this->entityManager->persist($congeTt);
        $this->entityManager->flush();

        return $congeTt;
    }

    public function testRepositoryIsInstanceOfReponseRepository(): void
    {
        $this->assertInstanceOf(ReponseRepository::class, $this->repository);
    }

    public function testCanFindAllReponses(): void
    {
        $result = $this->repository->findAll();

        $this->assertIsArray($result);
    }

    public function testCanPersistAndFindReponse(): void
    {
        [$rh, $userRh, $employe, $userEmploye] = $this->createRhAndEmploye();
        $congeTt = $this->createCongeTt($employe);

        $reponse = new Reponse();
        $reponse->setDecision('approuvé');
        $reponse->setCommentaire('Demande accordée.');
        $reponse->setRh($rh);
        $reponse->setEmploye($employe);
        $reponse->setCongeTt($congeTt);

        $this->entityManager->persist($reponse);
        $this->entityManager->flush();

        $foundReponse = $this->repository->find($reponse->getId());

        $this->assertNotNull($foundReponse);
        $this->assertEquals('approuvé', $foundReponse->getDecision());
        $this->assertEquals('Demande accordée.', $foundReponse->getCommentaire());

        // Clean up
        $this->entityManager->remove($foundReponse);
        $this->entityManager->remove($congeTt);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($userEmploye);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testCanFindReponseByDecision(): void
    {
        [$rh, $userRh, $employe, $userEmploye] = $this->createRhAndEmploye();
        $congeTt = $this->createCongeTt($employe);

        $reponse = new Reponse();
        $reponse->setDecision('refusé');
        $reponse->setCommentaire('Motif insuffisant.');
        $reponse->setRh($rh);
        $reponse->setEmploye($employe);
        $reponse->setCongeTt($congeTt);

        $this->entityManager->persist($reponse);
        $this->entityManager->flush();

        $foundReponses = $this->repository->findBy(['decision' => 'refusé']);

        $this->assertGreaterThanOrEqual(1, count($foundReponses));
        $this->assertEquals('refusé', $foundReponses[0]->getDecision());

        // Clean up
        $this->entityManager->remove($reponse);
        $this->entityManager->remove($congeTt);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($userEmploye);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testCanFindReponseByCongeTt(): void
    {
        [$rh, $userRh, $employe, $userEmploye] = $this->createRhAndEmploye();
        $congeTt = $this->createCongeTt($employe);

        $reponse = new Reponse();
        $reponse->setDecision('approuvé');
        $reponse->setRh($rh);
        $reponse->setEmploye($employe);
        $reponse->setCongeTt($congeTt);

        $this->entityManager->persist($reponse);
        $this->entityManager->flush();

        $foundReponse = $this->repository->findOneBy(['conge_tt' => $congeTt]);

        $this->assertNotNull($foundReponse);
        $this->assertEquals('approuvé', $foundReponse->getDecision());

        // Clean up
        $this->entityManager->remove($reponse);
        $this->entityManager->remove($congeTt);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($userEmploye);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testCanUpdateReponse(): void
    {
        [$rh, $userRh, $employe, $userEmploye] = $this->createRhAndEmploye();
        $congeTt = $this->createCongeTt($employe);

        $reponse = new Reponse();
        $reponse->setDecision('approuvé');
        $reponse->setRh($rh);
        $reponse->setEmploye($employe);
        $reponse->setCongeTt($congeTt);

        $this->entityManager->persist($reponse);
        $this->entityManager->flush();

        $id = $reponse->getId();
        $congeTtId = $congeTt->getId();
        $employeId = $employe->getUserId();
        $userEmployeId = $userEmploye->getId();
        $rhId = $rh->getUserId();
        $userRhId = $userRh->getId();

        // Update
        $reponse->setDecision('refusé');
        $reponse->setCommentaire('Décision modifiée.');
        $this->entityManager->flush();

        // Verify update
        $this->entityManager->clear();
        $updatedReponse = $this->repository->find($id);

        $this->assertEquals('refusé', $updatedReponse->getDecision());
        $this->assertEquals('Décision modifiée.', $updatedReponse->getCommentaire());

        // Clean up - re-fetch entities after clear()
        $congeTtToRemove   = $this->entityManager->getRepository(CongeTt::class)->find($congeTtId);
        $employeToRemove   = $this->entityManager->getRepository(\App\Entity\Employe::class)->find($employeId);
        $userEmpToRemove   = $this->entityManager->getRepository(\App\Entity\User::class)->find($userEmployeId);
        $rhToRemove        = $this->entityManager->getRepository(\App\Entity\RH::class)->find($rhId);
        $userRhToRemove    = $this->entityManager->getRepository(\App\Entity\User::class)->find($userRhId);

        $this->entityManager->remove($updatedReponse);
        $this->entityManager->remove($congeTtToRemove);
        $this->entityManager->remove($employeToRemove);
        $this->entityManager->remove($userEmpToRemove);
        $this->entityManager->remove($rhToRemove);
        $this->entityManager->remove($userRhToRemove);
        $this->entityManager->flush();
    }

    public function testCanDeleteReponse(): void
    {
        [$rh, $userRh, $employe, $userEmploye] = $this->createRhAndEmploye();
        $congeTt = $this->createCongeTt($employe);

        $reponse = new Reponse();
        $reponse->setDecision('approuvé');
        $reponse->setRh($rh);
        $reponse->setEmploye($employe);
        $reponse->setCongeTt($congeTt);

        $this->entityManager->persist($reponse);
        $this->entityManager->flush();

        $id = $reponse->getId();

        // Delete
        $this->entityManager->remove($reponse);
        $this->entityManager->flush();

        // Verify deletion
        $deletedReponse = $this->repository->find($id);
        $this->assertNull($deletedReponse);

        // Clean up
        $this->entityManager->remove($congeTt);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($userEmploye);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testCanPersistReponseWithoutCommentaire(): void
    {
        [$rh, $userRh, $employe, $userEmploye] = $this->createRhAndEmploye();
        $congeTt = $this->createCongeTt($employe);

        $reponse = new Reponse();
        $reponse->setDecision('approuvé');
        $reponse->setRh($rh);
        $reponse->setEmploye($employe);
        $reponse->setCongeTt($congeTt);

        $this->entityManager->persist($reponse);
        $this->entityManager->flush();

        $foundReponse = $this->repository->find($reponse->getId());

        $this->assertNotNull($foundReponse);
        $this->assertNull($foundReponse->getCommentaire());

        // Clean up
        $this->entityManager->remove($foundReponse);
        $this->entityManager->remove($congeTt);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($userEmploye);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testFindReturnsNullForNonExistentId(): void
    {
        $nonExistentId = 999999;
        $reponse = $this->repository->find($nonExistentId);

        $this->assertNull($reponse);
    }

    public function testFindOneByReturnsNullWhenNoMatch(): void
    {
        $reponse = $this->repository->findOneBy([
            'decision' => 'DecisionInexistante',
        ]);

        $this->assertNull($reponse);
    }
}
