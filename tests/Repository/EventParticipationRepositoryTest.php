<?php

namespace App\Tests\Repository;

use App\Entity\Employe;
use App\Entity\Evenement;
use App\Entity\EventParticipation;
use App\Entity\User;
use App\Repository\EventParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests de repository pour EventParticipationRepository.
 *
 * Utilise KernelTestCase + base de données de test.
 * Opérations testées :
 * - find(), findAll(), findBy(), findOneBy()
 * - persist + flush (CREATE)
 * - update statut (UPDATE)
 * - remove + flush (DELETE)
 * - Filtrage par événement et par employé
 */
class EventParticipationRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface         $entityManager;
    private ?EventParticipationRepository   $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(EventParticipation::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository    = null;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function createUserEtEmploye(): array
    {
        $user = new User();
        $user->setEmail('employe_' . uniqid() . '@test.com');
        $user->setMotDePasse('password123');
        $user->setNom('TestNom');
        $user->setPrenom('TestPrenom');
        $user->setRole('EMPLOYE');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $employe = new Employe();
        $employe->setUser($user);
        $employe->setMatricule('MAT_' . uniqid());
        $employe->setPosition('Développeur');
        $employe->setDateEmbauche(new \DateTimeImmutable('2023-01-01'));
        $this->entityManager->persist($employe);
        $this->entityManager->flush();

        return [$employe, $user];
    }

    private function createEvenement(): Evenement
    {
        $ev = new Evenement();
        $ev->setTitre('Événement Test_' . uniqid());
        $ev->setLieu('Tunis');
        $ev->setDateDebut('2025-09-01');
        $ev->setDateFin('2025-09-03');
        $this->entityManager->persist($ev);
        $this->entityManager->flush();
        return $ev;
    }

    private function createParticipation(
        Evenement $evenement,
        Employe   $employe,
        string    $statut = 'en_attente'
    ): EventParticipation {
        $p = new EventParticipation();
        $p->setEvenement($evenement);
        $p->setEmploye($employe);
        $p->setStatut($statut);
        $p->setDateInscription((new \DateTimeImmutable())->format('Y-m-d'));
        return $p;
    }

    // =========================================================================
    // TESTS DE BASE
    // =========================================================================

    public function testRepositoryEstInstanceDeEventParticipationRepository(): void
    {
        $this->assertInstanceOf(EventParticipationRepository::class, $this->repository);
    }

    public function testFindAllRetourneUnTableau(): void
    {
        $result = $this->repository->findAll();
        $this->assertIsArray($result);
    }

    public function testFindRetourneNullPourIdInexistant(): void
    {
        $result = $this->repository->find(999999);
        $this->assertNull($result);
    }

    public function testFindOneByRetourneNullSiAucuneCorrespondance(): void
    {
        $result = $this->repository->findOneBy(['statut' => 'statut_inexistant_xyz']);
        $this->assertNull($result);
    }

    // =========================================================================
    // TEST CREATE
    // =========================================================================

    public function testPersistEtTrouverUneParticipation(): void
    {
        [$employe, $user] = $this->createUserEtEmploye();
        $evenement        = $this->createEvenement();

        $p = $this->createParticipation($evenement, $employe, 'en_attente');
        $this->entityManager->persist($p);
        $this->entityManager->flush();

        $found = $this->repository->find($p->getId());

        $this->assertNotNull($found);
        $this->assertEquals('en_attente', $found->getStatut());
        $this->assertSame($evenement->getId(), $found->getEvenement()->getId());
        $this->assertSame($employe->getUserId(), $found->getEmploye()->getUserId());

        // Clean up
        $this->entityManager->remove($found);
        $this->entityManager->remove($evenement);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testNouvelleParticipationAStatutEnAttente(): void
    {
        [$employe, $user] = $this->createUserEtEmploye();
        $evenement        = $this->createEvenement();

        $p = $this->createParticipation($evenement, $employe);
        $this->entityManager->persist($p);
        $this->entityManager->flush();

        $found = $this->repository->find($p->getId());

        $this->assertEquals('en_attente', $found->getStatut());

        // Clean up
        $this->entityManager->remove($found);
        $this->entityManager->remove($evenement);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST READ : findBy
    // =========================================================================

    public function testFindByStatutRetourneLesParticipationsCorrespondantes(): void
    {
        [$employe, $user] = $this->createUserEtEmploye();
        $evenement        = $this->createEvenement();

        $p = $this->createParticipation($evenement, $employe, 'accepte');
        $this->entityManager->persist($p);
        $this->entityManager->flush();

        $results = $this->repository->findBy(['statut' => 'accepte']);

        $this->assertGreaterThanOrEqual(1, count($results));
        foreach ($results as $r) {
            $this->assertEquals('accepte', $r->getStatut());
        }

        // Clean up
        $this->entityManager->remove($p);
        $this->entityManager->remove($evenement);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testFindByEvenementRetourneSesParticipations(): void
    {
        [$employe, $user] = $this->createUserEtEmploye();
        $evenement        = $this->createEvenement();

        $p = $this->createParticipation($evenement, $employe);
        $this->entityManager->persist($p);
        $this->entityManager->flush();

        $results = $this->repository->findBy(['evenement' => $evenement]);

        $this->assertGreaterThanOrEqual(1, count($results));

        // Clean up
        $this->entityManager->remove($p);
        $this->entityManager->remove($evenement);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testFindOneByEvenementEtEmployeRetourneLaBonneParticipation(): void
    {
        [$employe, $user] = $this->createUserEtEmploye();
        $evenement        = $this->createEvenement();

        $p = $this->createParticipation($evenement, $employe, 'en_attente');
        $this->entityManager->persist($p);
        $this->entityManager->flush();

        $found = $this->repository->findOneBy([
            'evenement' => $evenement,
            'employe'   => $employe,
        ]);

        $this->assertNotNull($found);
        $this->assertEquals('en_attente', $found->getStatut());

        // Clean up
        $this->entityManager->remove($p);
        $this->entityManager->remove($evenement);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST UPDATE (changement de statut)
    // =========================================================================

    public function testMettreAJourStatutVersAccepte(): void
    {
        [$employe, $user] = $this->createUserEtEmploye();
        $evenement        = $this->createEvenement();

        $p = $this->createParticipation($evenement, $employe, 'en_attente');
        $this->entityManager->persist($p);
        $this->entityManager->flush();

        $id = $p->getId();

        $p->setStatut('accepte');
        $this->entityManager->flush();

        $this->entityManager->clear();
        $updated = $this->repository->find($id);

        $this->assertEquals('accepte', $updated->getStatut());

        // Clean up
        $evId   = $evenement->getId();
        $empId  = $employe->getUserId();
        $userId = $user->getId();

        $this->entityManager->remove($updated);
        $this->entityManager->remove($this->entityManager->getRepository(Evenement::class)->find($evId));
        $this->entityManager->remove($this->entityManager->getRepository(Employe::class)->find($empId));
        $this->entityManager->remove($this->entityManager->getRepository(User::class)->find($userId));
        $this->entityManager->flush();
    }

    public function testMettreAJourStatutVersRefuse(): void
    {
        [$employe, $user] = $this->createUserEtEmploye();
        $evenement        = $this->createEvenement();

        $p = $this->createParticipation($evenement, $employe, 'en_attente');
        $this->entityManager->persist($p);
        $this->entityManager->flush();

        $id = $p->getId();

        $p->setStatut('refuse');
        $this->entityManager->flush();

        $this->entityManager->clear();
        $updated = $this->repository->find($id);

        $this->assertEquals('refuse', $updated->getStatut());

        // Clean up
        $evId   = $evenement->getId();
        $empId  = $employe->getUserId();
        $userId = $user->getId();

        $this->entityManager->remove($updated);
        $this->entityManager->remove($this->entityManager->getRepository(Evenement::class)->find($evId));
        $this->entityManager->remove($this->entityManager->getRepository(Employe::class)->find($empId));
        $this->entityManager->remove($this->entityManager->getRepository(User::class)->find($userId));
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST DELETE
    // =========================================================================

    public function testSupprimerUneParticipation(): void
    {
        [$employe, $user] = $this->createUserEtEmploye();
        $evenement        = $this->createEvenement();

        $p = $this->createParticipation($evenement, $employe);
        $this->entityManager->persist($p);
        $this->entityManager->flush();

        $id = $p->getId();

        $this->entityManager->remove($p);
        $this->entityManager->flush();

        $deleted = $this->repository->find($id);
        $this->assertNull($deleted);

        // Clean up
        $this->entityManager->remove($evenement);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST : Plusieurs participations pour un même événement
    // =========================================================================

    public function testPlusieursParticipationsPourUnEvenement(): void
    {
        [$employe1, $user1] = $this->createUserEtEmploye();
        [$employe2, $user2] = $this->createUserEtEmploye();
        $evenement          = $this->createEvenement();

        $p1 = $this->createParticipation($evenement, $employe1, 'en_attente');
        $p2 = $this->createParticipation($evenement, $employe2, 'accepte');

        $this->entityManager->persist($p1);
        $this->entityManager->persist($p2);
        $this->entityManager->flush();

        $results = $this->repository->findBy(['evenement' => $evenement]);

        $this->assertGreaterThanOrEqual(2, count($results));

        // Clean up
        $this->entityManager->remove($p1);
        $this->entityManager->remove($p2);
        $this->entityManager->remove($evenement);
        $this->entityManager->remove($employe1);
        $this->entityManager->remove($user1);
        $this->entityManager->remove($employe2);
        $this->entityManager->remove($user2);
        $this->entityManager->flush();
    }

    public function testDateInscriptionEstPersisteeCorrectement(): void
    {
        [$employe, $user] = $this->createUserEtEmploye();
        $evenement        = $this->createEvenement();

        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $p     = $this->createParticipation($evenement, $employe);
        $p->setDateInscription($today);

        $this->entityManager->persist($p);
        $this->entityManager->flush();

        $found = $this->repository->find($p->getId());

        $this->assertEquals($today, $found->getDateInscription());

        // Clean up
        $this->entityManager->remove($found);
        $this->entityManager->remove($evenement);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
