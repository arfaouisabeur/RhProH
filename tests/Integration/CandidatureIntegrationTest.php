<?php

namespace App\Tests\Integration;

use App\Entity\Candidat;
use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Repository\CandidatRepository;
use App\Repository\CandidatureRepository;
use App\Repository\OffreEmploiRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests d'intégration pour Candidature avec la base de données réelle
 * 
 * ATTENTION : Ces tests utilisent la base de données réelle !
 * Assurez-vous d'avoir fait une sauvegarde avant d'exécuter ces tests.
 */
class CandidatureIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CandidatureRepository $candidatureRepository;
    private OffreEmploiRepository $offreRepository;
    private CandidatRepository $candidatRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel(['environment' => 'dev']); // Utilise l'environnement dev (base réelle)

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->candidatureRepository = $this->entityManager->getRepository(Candidature::class);
        $this->offreRepository = $this->entityManager->getRepository(OffreEmploi::class);
        $this->candidatRepository = $this->entityManager->getRepository(Candidat::class);
    }

    public function testCanFetchCandidaturesFromDatabase(): void
    {
        $candidatures = $this->candidatureRepository->findAll();
        
        $this->assertIsArray($candidatures);
        // Afficher le nombre de candidatures trouvées
        echo "\n✓ Nombre de candidatures dans la base : " . count($candidatures) . "\n";
    }

    public function testCanFetchOffresFromDatabase(): void
    {
        $offres = $this->offreRepository->findAll();
        
        $this->assertIsArray($offres);
        echo "\n✓ Nombre d'offres d'emploi dans la base : " . count($offres) . "\n";
    }

    public function testCanFetchCandidatsFromDatabase(): void
    {
        $candidats = $this->candidatRepository->findAll();
        
        $this->assertIsArray($candidats);
        echo "\n✓ Nombre de candidats dans la base : " . count($candidats) . "\n";
    }

    public function testCandidatureHasValidRelations(): void
    {
        $candidature = $this->candidatureRepository->findOneBy([]);
        
        if ($candidature) {
            $this->assertInstanceOf(Candidature::class, $candidature);
            
            // Vérifier les relations
            if ($candidature->getCandidat()) {
                $this->assertInstanceOf(Candidat::class, $candidature->getCandidat());
                echo "\n✓ Relation Candidature -> Candidat : OK\n";
            }
            
            if ($candidature->getOffreEmploi()) {
                $this->assertInstanceOf(OffreEmploi::class, $candidature->getOffreEmploi());
                echo "\n✓ Relation Candidature -> OffreEmploi : OK\n";
            }
        } else {
            $this->markTestSkipped('Aucune candidature dans la base de données');
        }
    }

    public function testOffreEmploiHasCandidatures(): void
    {
        $offre = $this->offreRepository->findOneBy([]);
        
        if ($offre) {
            $this->assertInstanceOf(OffreEmploi::class, $offre);
            
            $candidatures = $offre->getCandidatures();
            $this->assertNotNull($candidatures);
            
            echo "\n✓ Offre ID " . $offre->getId() . " a " . count($candidatures) . " candidature(s)\n";
        } else {
            $this->markTestSkipped('Aucune offre d\'emploi dans la base de données');
        }
    }

    public function testCanSearchCandidaturesByStatut(): void
    {
        $statuts = ['en_attente', 'entretien', 'acceptee', 'refusee'];
        
        foreach ($statuts as $statut) {
            $candidatures = $this->candidatureRepository->findBy(['statut' => $statut]);
            echo "\n✓ Candidatures avec statut '$statut' : " . count($candidatures) . "\n";
        }
        
        $this->assertTrue(true);
    }

    public function testCanSearchOffresByStatut(): void
    {
        $statuts = ['Ouverte', 'Fermée', 'En attente', 'Pourvue'];
        
        foreach ($statuts as $statut) {
            $offres = $this->offreRepository->findBy(['statut' => $statut]);
            echo "\n✓ Offres avec statut '$statut' : " . count($offres) . "\n";
        }
        
        $this->assertTrue(true);
    }

    public function testDatabaseStructureIsValid(): void
    {
        // Vérifier que les tables existent en essayant de faire des requêtes
        try {
            $this->candidatureRepository->createQueryBuilder('c')->getQuery()->execute();
            echo "\n✓ Table 'candidature' : OK\n";
            
            $this->offreRepository->createQueryBuilder('o')->getQuery()->execute();
            echo "\n✓ Table 'offre_emploi' : OK\n";
            
            $this->candidatRepository->createQueryBuilder('c')->getQuery()->execute();
            echo "\n✓ Table 'candidat' : OK\n";
            
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Erreur de structure de base de données : ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Fermer la connexion
        $this->entityManager->close();
    }
}
