<?php

namespace App\Tests\Repository;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests de repository pour EvenementRepository.
 *
 * Utilise KernelTestCase + base de données de test (pidevf_test).
 * Chaque test persiste des données puis les nettoie (clean up).
 *
 * Opérations testées :
 * - find(), findAll(), findBy(), findOneBy()
 * - persist + flush (CREATE)
 * - update + flush (UPDATE)
 * - remove + flush (DELETE)
 * - searchByKeyword() (méthode personnalisée)
 * - Retour null pour ID inexistant
 */
class EvenementRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?EvenementRepository    $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(Evenement::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository    = null;
    }

    // =========================================================================
    // HELPER : crée un événement complet valide
    // =========================================================================

    private function createEvenement(
        string $titre    = 'Événement Test',
        string $lieu     = 'Tunis',
        string $debut    = '2025-09-01',
        string $fin      = '2025-09-03'
    ): Evenement {
        $ev = new Evenement();
        $ev->setTitre($titre);
        $ev->setLieu($lieu);
        $ev->setDateDebut($debut);
        $ev->setDateFin($fin);
        return $ev;
    }

    // =========================================================================
    // TESTS DE BASE
    // =========================================================================

    public function testRepositoryEstInstanceDeEvenementRepository(): void
    {
        $this->assertInstanceOf(EvenementRepository::class, $this->repository);
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
        $result = $this->repository->findOneBy(['titre' => 'TitreQuiNExistePas_XYZ']);
        $this->assertNull($result);
    }

    // =========================================================================
    // TEST CREATE : persist + find
    // =========================================================================

    public function testPersistEtTrouverUnEvenement(): void
    {
        $ev = $this->createEvenement('Forum Innovation 2025', 'Sfax', '2025-10-01', '2025-10-05');
        $ev->setDescription('Forum annuel sur l\'innovation.');
        $ev->setImageUrl('/uploads/forum.jpg');

        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        $found = $this->repository->find($ev->getId());

        $this->assertNotNull($found);
        $this->assertEquals('Forum Innovation 2025', $found->getTitre());
        $this->assertEquals('Sfax', $found->getLieu());
        $this->assertEquals('2025-10-01', $found->getDateDebut());
        $this->assertEquals('2025-10-05', $found->getDateFin());
        $this->assertEquals('Forum annuel sur l\'innovation.', $found->getDescription());

        // Clean up
        $this->entityManager->remove($found);
        $this->entityManager->flush();
    }

    public function testPersistEvenementSansDescriptionNiImage(): void
    {
        $ev = $this->createEvenement('Séminaire RH', 'Tunis', '2025-11-01', '2025-11-02');

        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        $found = $this->repository->find($ev->getId());

        $this->assertNotNull($found);
        $this->assertNull($found->getDescription());
        $this->assertNull($found->getImageUrl());

        // Clean up
        $this->entityManager->remove($found);
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST READ : findBy
    // =========================================================================

    public function testFindByLieuRetourneLesEvenementsDuLieu(): void
    {
        $ev = $this->createEvenement('Conférence Tunis', 'Tunis', '2025-12-01', '2025-12-03');

        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        $results = $this->repository->findBy(['lieu' => 'Tunis']);

        $this->assertGreaterThanOrEqual(1, count($results));

        $titres = array_map(fn($e) => $e->getTitre(), $results);
        $this->assertContains('Conférence Tunis', $titres);

        // Clean up
        $this->entityManager->remove($ev);
        $this->entityManager->flush();
    }

    public function testFindOneByTitreRetourneLeBonEvenement(): void
    {
        $titre = 'Événement Unique_' . uniqid();
        $ev    = $this->createEvenement($titre, 'Sousse', '2025-08-01', '2025-08-02');

        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        $found = $this->repository->findOneBy(['titre' => $titre]);

        $this->assertNotNull($found);
        $this->assertEquals($titre, $found->getTitre());

        // Clean up
        $this->entityManager->remove($found);
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST UPDATE
    // =========================================================================

    public function testMettreAJourLetitreDUnEvenement(): void
    {
        $ev = $this->createEvenement('Ancien Titre', 'Tunis', '2025-07-01', '2025-07-05');

        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        $id = $ev->getId();

        // Modification
        $ev->setTitre('Nouveau Titre Modifié');
        $this->entityManager->flush();

        // Vérification après clear
        $this->entityManager->clear();
        $updated = $this->repository->find($id);

        $this->assertEquals('Nouveau Titre Modifié', $updated->getTitre());

        // Clean up
        $this->entityManager->remove($updated);
        $this->entityManager->flush();
    }

    public function testMettreAJourLesCoordonnees(): void
    {
        $ev = $this->createEvenement('Événement Géolocalisé', 'Tunis', '2025-06-01', '2025-06-03');

        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        $id = $ev->getId();

        $ev->setLatitude('36.8065');
        $ev->setLongitude('10.1815');
        $this->entityManager->flush();

        $this->entityManager->clear();
        $updated = $this->repository->find($id);

        $this->assertEquals('36.8065', $updated->getLatitude());
        $this->assertEquals('10.1815', $updated->getLongitude());

        // Clean up
        $this->entityManager->remove($updated);
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST DELETE
    // =========================================================================

    public function testSupprimerUnEvenement(): void
    {
        $ev = $this->createEvenement('Événement à Supprimer', 'Monastir', '2025-05-01', '2025-05-02');

        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        $id = $ev->getId();

        $this->entityManager->remove($ev);
        $this->entityManager->flush();

        $deleted = $this->repository->find($id);
        $this->assertNull($deleted);
    }

    // =========================================================================
    // TEST searchByKeyword() (méthode personnalisée)
    // =========================================================================

    public function testSearchByKeywordTrouveParTitre(): void
    {
        $titre = 'ConférencePHP_' . uniqid();
        $ev    = $this->createEvenement($titre, 'Tunis', '2025-10-01', '2025-10-03');

        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        $results = $this->repository->searchByKeyword('ConférencePHP');

        $this->assertGreaterThanOrEqual(1, count($results));

        // Clean up
        $this->entityManager->remove($ev);
        $this->entityManager->flush();
    }

    public function testSearchByKeywordTrouveParLieu(): void
    {
        $lieu = 'VilleFictive_' . uniqid();
        $ev   = $this->createEvenement('Événement Quelconque', $lieu, '2025-10-01', '2025-10-03');

        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        $results = $this->repository->searchByKeyword($lieu);

        $this->assertGreaterThanOrEqual(1, count($results));

        // Clean up
        $this->entityManager->remove($ev);
        $this->entityManager->flush();
    }

    public function testSearchByKeywordTrouveParDescription(): void
    {
        $motCle = 'DescriptionUnique_' . uniqid();
        $ev     = $this->createEvenement('Forum RH', 'Tunis', '2025-10-01', '2025-10-03');
        $ev->setDescription($motCle . ' - Description complète.');

        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        $results = $this->repository->searchByKeyword($motCle);

        $this->assertGreaterThanOrEqual(1, count($results));

        // Clean up
        $this->entityManager->remove($ev);
        $this->entityManager->flush();
    }

    public function testSearchByKeywordRetourneVideSiAucuneCorrespondance(): void
    {
        $results = $this->repository->searchByKeyword('MotCleSansResultat_XYZABC_999');

        $this->assertIsArray($results);
        $this->assertCount(0, $results);
    }

    public function testSearchByKeywordInsensibleALaCasse(): void
    {
        $ev = $this->createEvenement('FORUM INNOVATION MAJUSCULES', 'Tunis', '2025-10-01', '2025-10-03');

        $this->entityManager->persist($ev);
        $this->entityManager->flush();

        // Recherche en minuscules
        $results = $this->repository->searchByKeyword('forum innovation majuscules');

        $this->assertGreaterThanOrEqual(1, count($results));

        // Clean up
        $this->entityManager->remove($ev);
        $this->entityManager->flush();
    }

    // =========================================================================
    // TEST : Plusieurs événements
    // =========================================================================

    public function testTrouverPlusieursEvenements(): void
    {
        $ev1 = $this->createEvenement('Événement Multi 1', 'Tunis',   '2025-11-01', '2025-11-02');
        $ev2 = $this->createEvenement('Événement Multi 2', 'Sfax',    '2025-11-05', '2025-11-07');
        $ev3 = $this->createEvenement('Événement Multi 3', 'Sousse',  '2025-11-10', '2025-11-12');

        $this->entityManager->persist($ev1);
        $this->entityManager->persist($ev2);
        $this->entityManager->persist($ev3);
        $this->entityManager->flush();

        $all = $this->repository->findAll();
        $this->assertGreaterThanOrEqual(3, count($all));

        // Clean up
        $this->entityManager->remove($ev1);
        $this->entityManager->remove($ev2);
        $this->entityManager->remove($ev3);
        $this->entityManager->flush();
    }
}
