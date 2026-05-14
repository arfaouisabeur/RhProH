<?php

namespace App\Tests\Repository;

use App\Entity\ServiceReaction;
use App\Entity\TypeService;
use App\Entity\User;
use App\Repository\ServiceReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServiceReactionRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?ServiceReactionRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(ServiceReaction::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }

    private function createUserAndTypeService(): array
    {
        $user = new User();
        $user->setEmail('user' . uniqid() . '@example.com');
        $user->setMotDePasse('password');
        $user->setNom('Leroy');
        $user->setPrenom('Alice');
        $user->setRole('EMPLOYE');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $typeService = new TypeService();
        $typeService->setNom('Service ' . uniqid());
        $typeService->setCategorie('RH');
        $this->entityManager->persist($typeService);
        $this->entityManager->flush();

        return [$user, $typeService];
    }

    public function testRepositoryIsInstanceOfServiceReactionRepository(): void
    {
        $this->assertInstanceOf(ServiceReactionRepository::class, $this->repository);
    }

    public function testCanFindAllServiceReactions(): void
    {
        $result = $this->repository->findAll();

        $this->assertIsArray($result);
    }

    public function testCanPersistAndFindServiceReaction(): void
    {
        [$user, $typeService] = $this->createUserAndTypeService();

        $reaction = $this->createReaction($user, $typeService, ServiceReaction::LIKE);

        $this->entityManager->persist($reaction);
        $this->entityManager->flush();

        $foundReaction = $this->repository->find($reaction->getId());

        $this->assertNotNull($foundReaction);
        $this->assertEquals(ServiceReaction::LIKE, $foundReaction->getReaction());

        // Clean up
        $this->entityManager->remove($foundReaction);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function createReaction(User $user, TypeService $typeService, string $reactionType = ServiceReaction::LIKE): ServiceReaction
    {
        $reaction = new ServiceReaction();
        $reaction->setUser($user);
        $reaction->setTypeService($typeService);
        $reaction->setReaction($reactionType);
        
        $refClass = new \ReflectionClass(ServiceReaction::class);
        $prop = $refClass->getProperty('createdBy');
        $prop->setAccessible(true);
        $prop->setValue($reaction, $user);

        return $reaction;
    }

    public function testFindOneByUserAndType(): void
    {
        [$user, $typeService] = $this->createUserAndTypeService();

        $reaction = $this->createReaction($user, $typeService, ServiceReaction::LIKE);

        $this->entityManager->persist($reaction);
        $this->entityManager->flush();

        $foundReaction = $this->repository->findOneByUserAndType($user, $typeService);

        $this->assertNotNull($foundReaction);
        $this->assertEquals(ServiceReaction::LIKE, $foundReaction->getReaction());

        // Clean up
        $this->entityManager->remove($reaction);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testFindOneByUserAndTypeReturnsNullWhenNoMatch(): void
    {
        [$user, $typeService] = $this->createUserAndTypeService();

        $foundReaction = $this->repository->findOneByUserAndType($user, $typeService);

        $this->assertNull($foundReaction);

        // Clean up
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCountByTypeReturnsZeroWhenNoReactions(): void
    {
        [$user, $typeService] = $this->createUserAndTypeService();

        $counts = $this->repository->countByType($typeService);

        $this->assertEquals(0, $counts['likes']);
        $this->assertEquals(0, $counts['dislikes']);

        // Clean up
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCountByTypeCountsLikesCorrectly(): void
    {
        [$user, $typeService] = $this->createUserAndTypeService();

        $reaction = $this->createReaction($user, $typeService, ServiceReaction::LIKE);

        $this->entityManager->persist($reaction);
        $this->entityManager->flush();

        $counts = $this->repository->countByType($typeService);

        $this->assertEquals(1, $counts['likes']);
        $this->assertEquals(0, $counts['dislikes']);

        // Clean up
        $this->entityManager->remove($reaction);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCountByTypeCountsDislikesCorrectly(): void
    {
        [$user, $typeService] = $this->createUserAndTypeService();

        $reaction = $this->createReaction($user, $typeService, ServiceReaction::DISLIKE);

        $this->entityManager->persist($reaction);
        $this->entityManager->flush();

        $counts = $this->repository->countByType($typeService);

        $this->assertEquals(0, $counts['likes']);
        $this->assertEquals(1, $counts['dislikes']);

        // Clean up
        $this->entityManager->remove($reaction);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testFindLikesByUser(): void
    {
        [$user, $typeService] = $this->createUserAndTypeService();

        $reaction = $this->createReaction($user, $typeService, ServiceReaction::LIKE);

        $this->entityManager->persist($reaction);
        $this->entityManager->flush();

        $likes = $this->repository->findLikesByUser($user);

        $this->assertGreaterThanOrEqual(1, count($likes));
        $this->assertEquals(ServiceReaction::LIKE, $likes[0]->getReaction());

        // Clean up
        $this->entityManager->remove($reaction);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testFindDislikesByUser(): void
    {
        [$user, $typeService] = $this->createUserAndTypeService();

        $reaction = $this->createReaction($user, $typeService, ServiceReaction::DISLIKE);

        $this->entityManager->persist($reaction);
        $this->entityManager->flush();

        $dislikes = $this->repository->findDislikesByUser($user);

        $this->assertGreaterThanOrEqual(1, count($dislikes));
        $this->assertEquals(ServiceReaction::DISLIKE, $dislikes[0]->getReaction());

        // Clean up
        $this->entityManager->remove($reaction);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testFindReactionMapByUser(): void
    {
        [$user, $typeService] = $this->createUserAndTypeService();

        $reaction = $this->createReaction($user, $typeService, ServiceReaction::LIKE);

        $this->entityManager->persist($reaction);
        $this->entityManager->flush();

        $map = $this->repository->findReactionMapByUser($user);

        $this->assertIsArray($map);
        $this->assertArrayHasKey($typeService->getId(), $map);
        $this->assertEquals(ServiceReaction::LIKE, $map[$typeService->getId()]);

        // Clean up
        $this->entityManager->remove($reaction);
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testFindReactionMapByUserReturnsEmptyWhenNoReactions(): void
    {
        [$user, $typeService] = $this->createUserAndTypeService();

        $map = $this->repository->findReactionMapByUser($user);

        $this->assertIsArray($map);
        $this->assertEmpty($map);

        // Clean up
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testCanDeleteServiceReaction(): void
    {
        [$user, $typeService] = $this->createUserAndTypeService();

        $reaction = $this->createReaction($user, $typeService, ServiceReaction::LIKE);

        $this->entityManager->persist($reaction);
        $this->entityManager->flush();

        $id = $reaction->getId();

        // Delete
        $this->entityManager->remove($reaction);
        $this->entityManager->flush();

        // Verify deletion
        $deletedReaction = $this->repository->find($id);
        $this->assertNull($deletedReaction);

        // Clean up
        $this->entityManager->remove($typeService);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function testFindReturnsNullForNonExistentId(): void
    {
        $nonExistentId = 999999;
        $reaction = $this->repository->find($nonExistentId);

        $this->assertNull($reaction);
    }

    public function testGetTopTypesReturnsArray(): void
    {
        $result = $this->repository->getTopTypes(5);

        $this->assertIsArray($result);
    }
}
