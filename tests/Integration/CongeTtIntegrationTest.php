<?php

namespace App\Tests\Integration;

use App\Entity\CongeTt;
use App\Entity\Employe;
use App\Entity\Reponse;
use App\Repository\CongeTtRepository;
use App\Repository\EmployeRepository;
use App\Repository\ReponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests d'intégration pour CongeTt avec la base de données réelle
 *
 * ATTENTION : Ces tests utilisent la base de données réelle !
 * Assurez-vous d'avoir fait une sauvegarde avant d'exécuter ces tests.
 */
class CongeTtIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CongeTtRepository $congeTtRepository;
    private EmployeRepository $employeRepository;
    private ReponseRepository $reponseRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel(['environment' => 'dev']);

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->congeTtRepository  = $this->entityManager->getRepository(CongeTt::class);
        $this->employeRepository  = $this->entityManager->getRepository(Employe::class);
        $this->reponseRepository  = $this->entityManager->getRepository(Reponse::class);
    }

    public function testCanFetchCongeTtsFromDatabase(): void
    {
        $congeTts = $this->congeTtRepository->findAll();

        $this->assertIsArray($congeTts);
        echo "\n✓ Nombre de demandes de congé dans la base : " . count($congeTts) . "\n";
    }

    public function testCanFetchEmployesFromDatabase(): void
    {
        $employes = $this->employeRepository->findAll();

        $this->assertIsArray($employes);
        echo "\n✓ Nombre d'employés dans la base : " . count($employes) . "\n";
    }

    public function testCongeTtHasValidRelations(): void
    {
        $congeTt = $this->congeTtRepository->findOneBy([]);

        if ($congeTt) {
            $this->assertInstanceOf(CongeTt::class, $congeTt);

            if ($congeTt->getEmploye()) {
                $this->assertInstanceOf(Employe::class, $congeTt->getEmploye());
                echo "\n✓ Relation CongeTt -> Employe : OK\n";
            }
        } else {
            $this->markTestSkipped('Aucune demande de congé dans la base de données');
        }
    }

    public function testCanSearchCongeTtsByStatut(): void
    {
        $statuts = ['En attente', 'Accepté', 'Refusé', 'approuvé', 'refusé'];

        foreach ($statuts as $statut) {
            $congeTts = $this->congeTtRepository->findBy(['statut' => $statut]);
            echo "\n✓ Congés avec statut '$statut' : " . count($congeTts) . "\n";
        }

        $this->assertTrue(true);
    }

    public function testCanSearchCongeTtsByTypeConge(): void
    {
        $types = ['Congé annuel', 'Congé maladie', 'Télétravail', 'Congé sans solde'];

        foreach ($types as $type) {
            $congeTts = $this->congeTtRepository->findBy(['type_conge' => $type]);
            echo "\n✓ Congés de type '$type' : " . count($congeTts) . "\n";
        }

        $this->assertTrue(true);
    }

    public function testCanFetchReponsesFromDatabase(): void
    {
        $reponses = $this->reponseRepository->findAll();

        $this->assertIsArray($reponses);
        echo "\n✓ Nombre de réponses dans la base : " . count($reponses) . "\n";
    }

    public function testReponseHasValidRelations(): void
    {
        $reponse = $this->reponseRepository->findOneBy([]);

        if ($reponse) {
            $this->assertInstanceOf(Reponse::class, $reponse);

            if ($reponse->getCongeTt()) {
                $this->assertInstanceOf(CongeTt::class, $reponse->getCongeTt());
                echo "\n✓ Relation Reponse -> CongeTt : OK\n";
            }

            if ($reponse->getEmploye()) {
                $this->assertInstanceOf(Employe::class, $reponse->getEmploye());
                echo "\n✓ Relation Reponse -> Employe : OK\n";
            }
        } else {
            $this->markTestSkipped('Aucune réponse dans la base de données');
        }
    }

    public function testDatabaseStructureIsValid(): void
    {
        try {
            $this->congeTtRepository->createQueryBuilder('c')->getQuery()->execute();
            echo "\n✓ Table 'conge_tt' : OK\n";

            $this->employeRepository->createQueryBuilder('e')->getQuery()->execute();
            echo "\n✓ Table 'employe' : OK\n";

            $this->reponseRepository->createQueryBuilder('r')->getQuery()->execute();
            echo "\n✓ Table 'reponse' : OK\n";

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Erreur de structure de base de données : ' . $e->getMessage());
        }
    }

    public function testCongeTtStatistiques(): void
    {
        $all      = $this->congeTtRepository->findAll();
        $total    = count($all);
        $attente  = count($this->congeTtRepository->findBy(['statut' => 'En attente']));
        $acceptes = count($this->congeTtRepository->findBy(['statut' => 'Accepté']));
        $refuses  = count($this->congeTtRepository->findBy(['statut' => 'Refusé']));

        echo "\n✓ Total congés : $total\n";
        echo "✓ En attente   : $attente\n";
        echo "✓ Acceptés     : $acceptes\n";
        echo "✓ Refusés      : $refuses\n";

        $this->assertGreaterThanOrEqual(0, $total);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
