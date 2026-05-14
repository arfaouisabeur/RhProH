<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositorySimpleTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?UserRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(User::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager) {
            $this->entityManager->close();
        }
        $this->entityManager = null;
        $this->repository = null;
    }

    public function testRepositoryIsInstanceOfUserRepository(): void
    {
        $this->assertInstanceOf(UserRepository::class, $this->repository);
    }

    public function testCanFindAllUsers(): void
    {
        $result = $this->repository->findAll();
        $this->assertIsArray($result);
    }

    public function testFindByRoleMethod(): void
    {
        // Test that the method exists and returns an array
        $result = $this->repository->findByRole(User::ROLE_CANDIDAT);
        $this->assertIsArray($result);
    }

    public function testFindAllCandidatsMethod(): void
    {
        // Test that the method exists and returns an array
        $result = $this->repository->findAllCandidats();
        $this->assertIsArray($result);
    }

    public function testFindAllEmployesMethod(): void
    {
        // Test that the method exists and returns an array
        $result = $this->repository->findAllEmployes();
        $this->assertIsArray($result);
    }

    public function testFindAllRHMethod(): void
    {
        // Test that the method exists and returns an array
        $result = $this->repository->findAllRH();
        $this->assertIsArray($result);
    }
}