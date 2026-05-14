<?php

namespace App\Tests\Repository;

use App\Entity\Tache;
use App\Repository\TacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TacheRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?TacheRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(Tache::class);

        // Ensure schema is up-to-date (in case UserRepositoryTest dropped it)
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema($metadata, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }

    private function createProjetAndEmploye(): array
    {
        // Créer un User pour le RH
        $userRh = new \App\Entity\User();
        $userRh->setEmail('rh' . uniqid() . '@example.com');
        $userRh->setMotDePasse('password');
        $userRh->setNom('RH');
        $userRh->setPrenom('Test');
        $userRh->setRole('RH');
        $this->entityManager->persist($userRh);
        $this->entityManager->flush();

        $rh = new \App\Entity\RH();
        $rh->setUser($userRh);
        $this->entityManager->persist($rh);
        $this->entityManager->flush();

        $projet = new \App\Entity\Projet();
        $projet->setTitre('Projet Test');
        $projet->setStatut('en_cours');
        $projet->setDateDebut(new \DateTimeImmutable('2024-01-01'));
        $projet->setDateFin(new \DateTimeImmutable('2024-12-31'));
        $projet->setRh($rh);
        $this->entityManager->persist($projet);
        $this->entityManager->flush();

        // Créer un User pour l'Employe
        $user = new \App\Entity\User();
        $user->setEmail('test' . uniqid() . '@example.com');
        $user->setMotDePasse('password');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setRole('EMPLOYE');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $employe = new \App\Entity\Employe();
        $employe->setUser($user);
        $employe->setMatricule('MAT' . uniqid());
        $employe->setPosition('Développeur');
        $employe->setDateEmbauche(new \DateTimeImmutable('2024-01-01'));
        $this->entityManager->persist($employe);
        $this->entityManager->flush();

        return [$projet, $employe, $user, $rh, $userRh];
    }

    public function testRepositoryIsInstanceOfTacheRepository(): void
    {
        $this->assertInstanceOf(TacheRepository::class, $this->repository);
    }

    public function testCanFindAllTaches(): void
    {
        $result = $this->repository->findAll();

        $this->assertIsArray($result);
    }

    public function testCanFindTachesByStatut(): void
    {
        $result = $this->repository->findBy(['statut' => 'a_faire']);

        $this->assertIsArray($result);
    }

    public function testCanPersistAndFindTache(): void
    {
        [$projet, $employe, $user, $rh, $userRh] = $this->createProjetAndEmploye();

        $tache = new Tache();
        $tache->setTitre('Analyse des besoins fonctionnels');
        $tache->setStatut('a_faire');
        $tache->setDescription('Description suffisamment longue pour la validation.');
        $tache->setDateDebut(new \DateTimeImmutable('2024-01-01'));
        $tache->setDateFin(new \DateTimeImmutable('2024-06-30'));
        $tache->setLevel('moyenne');
        $tache->setProjet($projet);
        $tache->setEmploye($employe);

        $this->entityManager->persist($tache);
        $this->entityManager->flush();

        $foundTache = $this->repository->find($tache->getId());

        $this->assertNotNull($foundTache);
        $this->assertEquals('Analyse des besoins fonctionnels', $foundTache->getTitre());
        $this->assertEquals('a_faire', $foundTache->getStatut());
        $this->assertEquals('Description suffisamment longue pour la validation.', $foundTache->getDescription());
        $this->assertEquals('moyenne', $foundTache->getLevel());

        // Clean up
        $this->entityManager->remove($foundTache);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->remove($projet);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testCanFindTacheByStatut(): void
    {
        [$projet, $employe, $user, $rh, $userRh] = $this->createProjetAndEmploye();

        $tache = new Tache();
        $tache->setTitre('Développement du module paie');
        $tache->setStatut('en_cours');
        $tache->setDescription('Description suffisamment longue pour la validation.');
        $tache->setDateDebut(new \DateTimeImmutable('2024-02-01'));
        $tache->setDateFin(new \DateTimeImmutable('2024-05-31'));
        $tache->setLevel('haute');
        $tache->setProjet($projet);
        $tache->setEmploye($employe);

        $this->entityManager->persist($tache);
        $this->entityManager->flush();

        $foundTaches = $this->repository->findBy(['statut' => 'en_cours']);

        $this->assertGreaterThanOrEqual(1, count($foundTaches));
        $this->assertEquals('en_cours', $foundTaches[0]->getStatut());

        // Clean up
        $this->entityManager->remove($tache);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->remove($projet);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testCanFindTacheByLevel(): void
    {
        [$projet, $employe, $user, $rh, $userRh] = $this->createProjetAndEmploye();

        $tache = new Tache();
        $tache->setTitre('Correction des bugs critiques');
        $tache->setStatut('a_faire');
        $tache->setDescription('Description suffisamment longue pour la validation.');
        $tache->setDateDebut(new \DateTimeImmutable('2024-03-01'));
        $tache->setDateFin(new \DateTimeImmutable('2024-04-30'));
        $tache->setLevel('critique');
        $tache->setProjet($projet);
        $tache->setEmploye($employe);

        $this->entityManager->persist($tache);
        $this->entityManager->flush();

        $foundTaches = $this->repository->findBy(['level' => 'critique']);

        $this->assertGreaterThanOrEqual(1, count($foundTaches));
        $this->assertEquals('critique', $foundTaches[0]->getLevel());

        // Clean up
        $this->entityManager->remove($tache);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->remove($projet);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testCanFindOneTacheByMultipleCriteria(): void
    {
        [$projet, $employe, $user, $rh, $userRh] = $this->createProjetAndEmploye();

        $tache = new Tache();
        $tache->setTitre('Rédaction de la documentation');
        $tache->setStatut('terminee');
        $tache->setDescription('Description suffisamment longue pour la validation.');
        $tache->setDateDebut(new \DateTimeImmutable('2024-04-01'));
        $tache->setDateFin(new \DateTimeImmutable('2024-07-31'));
        $tache->setLevel('basse');
        $tache->setProjet($projet);
        $tache->setEmploye($employe);

        $this->entityManager->persist($tache);
        $this->entityManager->flush();

        $foundTache = $this->repository->findOneBy([
            'statut' => 'terminee',
            'level'  => 'basse',
        ]);

        $this->assertNotNull($foundTache);
        $this->assertEquals('terminee', $foundTache->getStatut());
        $this->assertEquals('basse', $foundTache->getLevel());

        // Clean up
        $this->entityManager->remove($foundTache);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->remove($projet);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testCanUpdateTache(): void
    {
        [$projet, $employe, $user, $rh, $userRh] = $this->createProjetAndEmploye();

        $tache = new Tache();
        $tache->setTitre('Intégration des APIs externes');
        $tache->setStatut('a_faire');
        $tache->setDescription('Description suffisamment longue pour la validation.');
        $tache->setDateDebut(new \DateTimeImmutable('2024-05-01'));
        $tache->setDateFin(new \DateTimeImmutable('2024-08-31'));
        $tache->setLevel('haute');
        $tache->setProjet($projet);
        $tache->setEmploye($employe);

        $this->entityManager->persist($tache);
        $this->entityManager->flush();

        $id = $tache->getId();
        $employeId = $employe->getUserId();
        $userId = $user->getId();
        $projetId = $projet->getId();
        $rhId = $rh->getUserId();
        $userRhId = $userRh->getId();

        // Update
        $tache->setStatut('terminee');
        $tache->setLevel('moyenne');
        $this->entityManager->flush();

        // Verify update
        $this->entityManager->clear();
        $updatedTache = $this->repository->find($id);

        $this->assertEquals('terminee', $updatedTache->getStatut());
        $this->assertEquals('moyenne', $updatedTache->getLevel());

        // Clean up - re-fetch entities after clear()
        $employeToRemove = $this->entityManager->getRepository(\App\Entity\Employe::class)->find($employeId);
        $userToRemove = $this->entityManager->getRepository(\App\Entity\User::class)->find($userId);
        $projetToRemove = $this->entityManager->getRepository(\App\Entity\Projet::class)->find($projetId);
        $rhToRemove = $this->entityManager->getRepository(\App\Entity\RH::class)->find($rhId);
        $userRhToRemove = $this->entityManager->getRepository(\App\Entity\User::class)->find($userRhId);

        $this->entityManager->remove($updatedTache);
        $this->entityManager->remove($employeToRemove);
        $this->entityManager->remove($userToRemove);
        $this->entityManager->remove($projetToRemove);
        $this->entityManager->remove($rhToRemove);
        $this->entityManager->remove($userRhToRemove);
        $this->entityManager->flush();
    }

    public function testCanDeleteTache(): void
    {
        [$projet, $employe, $user, $rh, $userRh] = $this->createProjetAndEmploye();

        $tache = new Tache();
        $tache->setTitre('Tests unitaires du module RH');
        $tache->setStatut('a_faire');
        $tache->setDescription('Description suffisamment longue pour la validation.');
        $tache->setDateDebut(new \DateTimeImmutable('2024-06-01'));
        $tache->setDateFin(new \DateTimeImmutable('2024-09-30'));
        $tache->setLevel('moyenne');
        $tache->setProjet($projet);
        $tache->setEmploye($employe);

        $this->entityManager->persist($tache);
        $this->entityManager->flush();

        $id = $tache->getId();

        // Delete
        $this->entityManager->remove($tache);
        $this->entityManager->flush();

        // Verify deletion
        $deletedTache = $this->repository->find($id);
        $this->assertNull($deletedTache);

        // Clean up
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->remove($projet);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testCanPersistTacheWithoutPrime(): void
    {
        [$projet, $employe, $user, $rh, $userRh] = $this->createProjetAndEmploye();

        $tache = new Tache();
        $tache->setTitre('Déploiement en production');
        $tache->setStatut('a_faire');
        $tache->setDescription('Description suffisamment longue pour la validation.');
        $tache->setDateDebut(new \DateTimeImmutable('2024-07-01'));
        $tache->setDateFin(new \DateTimeImmutable('2024-10-31'));
        $tache->setLevel('haute');
        $tache->setProjet($projet);
        $tache->setEmploye($employe);

        $this->entityManager->persist($tache);
        $this->entityManager->flush();

        $foundTache = $this->repository->find($tache->getId());

        $this->assertNotNull($foundTache);
        $this->assertNull($foundTache->getPrime());

        // Clean up
        $this->entityManager->remove($foundTache);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->remove($projet);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testFindReturnsNullForNonExistentId(): void
    {
        $nonExistentId = 999999;
        $tache = $this->repository->find($nonExistentId);

        $this->assertNull($tache);
    }

    public function testFindOneByReturnsNullWhenNoMatch(): void
    {
        $tache = $this->repository->findOneBy([
            'statut' => 'statut_inexistant',
            'level'  => 'level_inexistant',
        ]);

        $this->assertNull($tache);
    }

    public function testCanFindMultipleTaches(): void
    {
        [$projet, $employe, $user, $rh, $userRh] = $this->createProjetAndEmploye();

        $tache1 = new Tache();
        $tache1->setTitre('Première tâche de test');
        $tache1->setStatut('a_faire');
        $tache1->setDescription('Description suffisamment longue pour la validation.');
        $tache1->setDateDebut(new \DateTimeImmutable('2024-01-01'));
        $tache1->setDateFin(new \DateTimeImmutable('2024-03-31'));
        $tache1->setLevel('basse');
        $tache1->setProjet($projet);
        $tache1->setEmploye($employe);

        $tache2 = new Tache();
        $tache2->setTitre('Deuxième tâche de test');
        $tache2->setStatut('en_cours');
        $tache2->setDescription('Description suffisamment longue pour la validation.');
        $tache2->setDateDebut(new \DateTimeImmutable('2024-02-01'));
        $tache2->setDateFin(new \DateTimeImmutable('2024-04-30'));
        $tache2->setLevel('haute');
        $tache2->setProjet($projet);
        $tache2->setEmploye($employe);

        $this->entityManager->persist($tache1);
        $this->entityManager->persist($tache2);
        $this->entityManager->flush();

        $taches = $this->repository->findAll();

        $this->assertGreaterThanOrEqual(2, count($taches));

        // Clean up
        $this->entityManager->remove($tache1);
        $this->entityManager->remove($tache2);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->remove($projet);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }

    public function testCanFindTachesOrderedByDateDebut(): void
    {
        [$projet, $employe, $user, $rh, $userRh] = $this->createProjetAndEmploye();

        $tache1 = new Tache();
        $tache1->setTitre('Tâche mars');
        $tache1->setStatut('a_faire');
        $tache1->setDescription('Description suffisamment longue pour la validation.');
        $tache1->setDateDebut(new \DateTimeImmutable('2024-03-01'));
        $tache1->setDateFin(new \DateTimeImmutable('2024-06-30'));
        $tache1->setLevel('moyenne');
        $tache1->setProjet($projet);
        $tache1->setEmploye($employe);

        $tache2 = new Tache();
        $tache2->setTitre('Tâche janvier');
        $tache2->setStatut('a_faire');
        $tache2->setDescription('Description suffisamment longue pour la validation.');
        $tache2->setDateDebut(new \DateTimeImmutable('2024-01-01'));
        $tache2->setDateFin(new \DateTimeImmutable('2024-04-30'));
        $tache2->setLevel('haute');
        $tache2->setProjet($projet);
        $tache2->setEmploye($employe);

        $this->entityManager->persist($tache1);
        $this->entityManager->persist($tache2);
        $this->entityManager->flush();

        $taches = $this->repository->findBy([], ['date_debut' => 'ASC']);

        $this->assertGreaterThanOrEqual(2, count($taches));

        // Clean up
        $this->entityManager->remove($tache1);
        $this->entityManager->remove($tache2);
        $this->entityManager->remove($employe);
        $this->entityManager->remove($user);
        $this->entityManager->remove($projet);
        $this->entityManager->remove($rh);
        $this->entityManager->remove($userRh);
        $this->entityManager->flush();
    }
}
