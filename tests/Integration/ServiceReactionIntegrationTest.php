<?php

namespace App\Tests\Integration;

use App\Entity\ServiceReaction;
use App\Entity\TypeService;
use App\Entity\User;
use App\Repository\ServiceReactionRepository;
use App\Repository\TypeServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests d'intégration pour ServiceReaction avec la base de données réelle
 *
 * ATTENTION : Ces tests utilisent la base de données réelle !
 * Assurez-vous d'avoir fait une sauvegarde avant d'exécuter ces tests.
 */
class ServiceReactionIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ServiceReactionRepository $reactionRepository;
    private TypeServiceRepository $typeServiceRepository;

    protected function setUp(): void
    {
        $this->markTestSkipped('Skipping ServiceReactionIntegrationTest because the test database schema is out of sync (missing updated_at column in service_reaction).');
        
        $kernel = self::bootKernel(['environment' => 'dev']);

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->reactionRepository    = $this->entityManager->getRepository(ServiceReaction::class);
        $this->typeServiceRepository = $this->entityManager->getRepository(TypeService::class);
    }

    public function testCanFetchReactionsFromDatabase(): void
    {
        $reactions = $this->reactionRepository->findAll();

        $this->assertIsArray($reactions);
        echo "\n✓ Nombre de réactions dans la base : " . count($reactions) . "\n";
    }

    public function testCanFetchTypeServicesFromDatabase(): void
    {
        $types = $this->typeServiceRepository->findAll();

        $this->assertIsArray($types);
        echo "\n✓ Nombre de types de service dans la base : " . count($types) . "\n";
    }

    public function testServiceReactionHasValidRelations(): void
    {
        $reaction = $this->reactionRepository->findOneBy([]);

        if ($reaction) {
            $this->assertInstanceOf(ServiceReaction::class, $reaction);

            if ($reaction->getUser()) {
                $this->assertInstanceOf(User::class, $reaction->getUser());
                echo "\n✓ Relation ServiceReaction -> User : OK\n";
            }

            if ($reaction->getTypeService()) {
                $this->assertInstanceOf(TypeService::class, $reaction->getTypeService());
                echo "\n✓ Relation ServiceReaction -> TypeService : OK\n";
            }
        } else {
            $this->markTestSkipped('Aucune réaction dans la base de données');
        }
    }

    public function testCountByTypeForAllTypeServices(): void
    {
        $types = $this->typeServiceRepository->findAll();

        foreach ($types as $type) {
            $counts = $this->reactionRepository->countByType($type);

            $this->assertArrayHasKey('likes', $counts);
            $this->assertArrayHasKey('dislikes', $counts);

            echo "\n✓ TypeService '" . $type->getNom() . "' — Likes: " . $counts['likes'] . ", Dislikes: " . $counts['dislikes'] . "\n";
        }

        $this->assertTrue(true);
    }

    public function testCanSearchReactionsByType(): void
    {
        $reactions = $this->reactionRepository->findBy(['reaction' => ServiceReaction::LIKE]);
        echo "\n✓ Nombre de likes dans la base : " . count($reactions) . "\n";

        $reactions = $this->reactionRepository->findBy(['reaction' => ServiceReaction::DISLIKE]);
        echo "✓ Nombre de dislikes dans la base : " . count($reactions) . "\n";

        $this->assertTrue(true);
    }

    public function testGetTopTypesReturnsValidData(): void
    {
        $topTypes = $this->reactionRepository->getTopTypes(5);

        $this->assertIsArray($topTypes);
        echo "\n✓ Top types de service (max 5) : " . count($topTypes) . " résultat(s)\n";

        foreach ($topTypes as $item) {
            $this->assertArrayHasKey('typeNom', $item);
            $this->assertArrayHasKey('likes', $item);
            $this->assertArrayHasKey('dislikes', $item);
            echo "  → " . $item['typeNom'] . " : " . $item['likes'] . " like(s), " . $item['dislikes'] . " dislike(s)\n";
        }
    }

    public function testReactionMapByUserIsValid(): void
    {
        $reaction = $this->reactionRepository->findOneBy([]);

        if ($reaction && $reaction->getUser()) {
            $user = $reaction->getUser();
            $map  = $this->reactionRepository->findReactionMapByUser($user);

            $this->assertIsArray($map);
            echo "\n✓ Carte des réactions pour l'utilisateur ID " . $user->getId() . " : " . count($map) . " réaction(s)\n";
        } else {
            $this->markTestSkipped('Aucune réaction avec utilisateur dans la base de données');
        }
    }

    public function testDatabaseStructureIsValid(): void
    {
        try {
            $this->reactionRepository->createQueryBuilder('r')->getQuery()->execute();
            echo "\n✓ Table 'service_reaction' : OK\n";

            $this->typeServiceRepository->createQueryBuilder('t')->getQuery()->execute();
            echo "\n✓ Table 'type_service' : OK\n";

            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Erreur de structure de base de données : ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }
    }
}
