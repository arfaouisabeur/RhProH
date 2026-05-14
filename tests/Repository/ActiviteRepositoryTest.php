<?php

namespace App\Tests\Repository;

use App\Entity\Activite;
use App\Entity\Evenement;
use App\Repository\ActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests de repository pour ActiviteRepository.
 *
 * Utilise KernelTestCase + base de données de test.
 * Opérations testées :
 * - find(), findAll(), findBy(), findOneBy()
 * - persist + flush (CREATE)
 * - update + flush (UPDATE)
 * - remove + flush (DELETE)
 * - Filtrage par événement parent
 * - Cascade : suppression d'un événement supprime ses activités
 */
class ActiviteRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?ActiviteRepository     $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(Activite::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository    = null;
    }

    // =========================================================================
    // HELPER : crée un événement parent persisté
    // =========================================================================

    private function createEvenement(): Evenement
    {
        $ev = new Evenement();
        $ev->setTitre('Forum Test_' . uniqid());
        $ev->setLieu('Tunis');
        $ev->setDateDebut('2025-10-01');
        $ev->setDateFin('2025-10-05');
        $this->entityManager->persist($ev);
        $this->entityManager->flush();
        return $ev;
    }

    private function createActivite(Evenement $evenement, string $titre = 'Atelier Test', ?string $description = null): Activite
    {
        $a = new Activite();
        $a->setTitre($titre);
        $a->setDescription($description);
        $a->setEvenement($evenement);
        return $a;
    }

    // =========================================================================
    // TESTS DE BASE
    // =========================================================================

    public function testRepositoryEstInstanceDeActiviteRepository(): void
    {
        $this->assertInstanceOf(ActiviteRepository::class, $this->repository);
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
        $result = $this->repository->findOneBy(['titre' => 'TitreQuiNExistePas_ABC_XYZ']);
        $this->assertNull($result);
    }

    // =========================================================================
    // TEST CREATE
    // =========================================================================

    public function testPersistEtTrouverUneActivite(): void
    {
        $evenement = $this->createEvenement();
        $activite  = $this->createActivite($evenement, 'Atelier Symfony', 'Introduction à Symfony 7.');

        $this->entityManager->persist($activite);
        $this->entityManager->flush();

        $found = $this->repository->find($activite->getId());

        $this->assertNotNull($found);
        $this->assertEquals('Atelier Symfony', $found->getTitre());
        $this->assertEquals('Introduction à Symfony 7.', $found->getDescription());
        $this->assertSame($evenement->getId(), $found->getEvenement()->getId());

        // Clean up
        $this->entityManager->remove($found);
        $this->entityManager->remove($evenement);
        $this->entityManager->flush();
    }

    public function testPersistActiviteSansDescription(): void
    {
        $evenement = $this->createEvenement();
        $activite  = $this->createActivite($evenement, 'Conférence sans description');

        $this->entityManager->persist($activite);
        $this->entityManager->flush();

        $found = $this->repository->find($activite->getId());

        $this->assertNotNull($found);
        $this->assertNull($found->getDescription());

        // Clean up
        $this->entityManager->remove($found);
        $this->entityManager->remove($evenement);
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST READ : findBy
    // =========================================================================

    public function testFindByEvenementRetourneSesActivites(): void
    {
        $evenement = $this->createEvenement();
        $a1        = $this->createActivite($evenement, 'Atelier 1');
        $a2        = $this->createActivite($evenement, 'Atelier 2');

        $this->entityManager->persist($a1);
        $this->entityManager->persist($a2);
        $this->entityManager->flush();

        $results = $this->repository->findBy(['evenement' => $evenement]);

        $this->assertGreaterThanOrEqual(2, count($results));

        $titres = array_map(fn($a) => $a->getTitre(), $results);
        $this->assertContains('Atelier 1', $titres);
        $this->assertContains('Atelier 2', $titres);

        // Clean up
        $this->entityManager->remove($a1);
        $this->entityManager->remove($a2);
        $this->entityManager->remove($evenement);
        $this->entityManager->flush();
    }

    public function testFindOneByTitreRetourneLaBonneActivite(): void
    {
        $evenement = $this->createEvenement();
        $titre     = 'Activité_Unique_' . uniqid();
        $activite  = $this->createActivite($evenement, $titre);

        $this->entityManager->persist($activite);
        $this->entityManager->flush();

        $found = $this->repository->findOneBy(['titre' => $titre]);

        $this->assertNotNull($found);
        $this->assertEquals($titre, $found->getTitre());

        // Clean up
        $this->entityManager->remove($found);
        $this->entityManager->remove($evenement);
        $this->entityManager->flush();
    }

    public function testFindByEvenementRetourneVideSiAucuneActivite(): void
    {
        $evenement = $this->createEvenement(); // Pas d'activités ajoutées

        $results = $this->repository->findBy(['evenement' => $evenement]);

        $this->assertIsArray($results);
        $this->assertCount(0, $results);

        // Clean up
        $this->entityManager->remove($evenement);
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST UPDATE
    // =========================================================================

    public function testMettreAJourLeTitreDUneActivite(): void
    {
        $evenement = $this->createEvenement();
        $activite  = $this->createActivite($evenement, 'Titre Original');

        $this->entityManager->persist($activite);
        $this->entityManager->flush();

        $id = $activite->getId();

        // Modification
        $activite->setTitre('Titre Modifié');
        $this->entityManager->flush();

        $this->entityManager->clear();
        $updated = $this->repository->find($id);

        $this->assertEquals('Titre Modifié', $updated->getTitre());

        // Clean up
        $evId = $evenement->getId();
        $this->entityManager->remove($updated);
        $this->entityManager->remove($this->entityManager->getRepository(Evenement::class)->find($evId));
        $this->entityManager->flush();
    }

    public function testMettreAJourLaDescription(): void
    {
        $evenement = $this->createEvenement();
        $activite  = $this->createActivite($evenement, 'Atelier avec description');
        $activite->setDescription('Description initiale.');

        $this->entityManager->persist($activite);
        $this->entityManager->flush();

        $id = $activite->getId();

        $activite->setDescription('Description mise à jour.');
        $this->entityManager->flush();

        $this->entityManager->clear();
        $updated = $this->repository->find($id);

        $this->assertEquals('Description mise à jour.', $updated->getDescription());

        // Clean up
        $evId = $evenement->getId();
        $this->entityManager->remove($updated);
        $this->entityManager->remove($this->entityManager->getRepository(Evenement::class)->find($evId));
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST DELETE
    // =========================================================================

    public function testSupprimerUneActivite(): void
    {
        $evenement = $this->createEvenement();
        $activite  = $this->createActivite($evenement, 'Activité à Supprimer');

        $this->entityManager->persist($activite);
        $this->entityManager->flush();

        $id = $activite->getId();

        $this->entityManager->remove($activite);
        $this->entityManager->flush();

        $deleted = $this->repository->find($id);
        $this->assertNull($deleted);

        // Clean up
        $this->entityManager->remove($evenement);
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST CASCADE : suppression de l'événement parent
    // =========================================================================

    public function testSuppressionEvenementSupprimeSesActivitesEnCascade(): void
    {
        $evenement = $this->createEvenement();
        $a1        = $this->createActivite($evenement, 'Cascade Activité 1');
        $a2        = $this->createActivite($evenement, 'Cascade Activité 2');

        $evenement->addActivite($a1);
        $evenement->addActivite($a2);

        $this->entityManager->persist($a1);
        $this->entityManager->persist($a2);
        $this->entityManager->flush();

        $idA1 = $a1->getId();
        $idA2 = $a2->getId();
        $idEv = $evenement->getId();

        // Suppression de l'événement → cascade sur activités
        $this->entityManager->remove($evenement);
        $this->entityManager->flush();

        $this->assertNull($this->repository->find($idA1));
        $this->assertNull($this->repository->find($idA2));
        $this->assertNull($this->entityManager->getRepository(Evenement::class)->find($idEv));
    }

    // =========================================================================
    // TEST : Plusieurs activités
    // =========================================================================

    public function testTrouverPlusieursActivitesDeEvenementsDifferents(): void
    {
        $ev1 = $this->createEvenement();
        $ev2 = $this->createEvenement();

        $a1 = $this->createActivite($ev1, 'Activité EV1');
        $a2 = $this->createActivite($ev2, 'Activité EV2');

        $this->entityManager->persist($a1);
        $this->entityManager->persist($a2);
        $this->entityManager->flush();

        // Chaque événement ne doit avoir que ses propres activités
        $activitesEv1 = $this->repository->findBy(['evenement' => $ev1]);
        $activitesEv2 = $this->repository->findBy(['evenement' => $ev2]);

        $this->assertGreaterThanOrEqual(1, count($activitesEv1));
        $this->assertGreaterThanOrEqual(1, count($activitesEv2));

        // Les activités ne se mélangent pas
        $titresEv1 = array_map(fn($a) => $a->getTitre(), $activitesEv1);
        $titresEv2 = array_map(fn($a) => $a->getTitre(), $activitesEv2);

        $this->assertNotContains('Activité EV2', $titresEv1);
        $this->assertNotContains('Activité EV1', $titresEv2);

        // Clean up
        $this->entityManager->remove($a1);
        $this->entityManager->remove($a2);
        $this->entityManager->remove($ev1);
        $this->entityManager->remove($ev2);
        $this->entityManager->flush();
    }
}
