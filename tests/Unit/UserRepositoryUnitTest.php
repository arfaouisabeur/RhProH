<?php

namespace App\Tests\Unit;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class UserRepositoryUnitTest extends TestCase
{
    private MockObject $entityManager;
    private MockObject $queryBuilder;
    private MockObject $query;
    /** @var UserRepository&MockObject */
    private $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
        
        // Create a partial mock of UserRepository to test specific methods
        $this->repository = $this->getMockBuilder(UserRepository::class)
            ->setConstructorArgs([
                $this->createMock(\Doctrine\Persistence\ManagerRegistry::class)
            ])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
    }

    public function testFindByRoleMethodExists(): void
    {
        // Test that the method exists and can be called
        $this->assertTrue(method_exists($this->repository, 'findByRole'));
    }

    public function testFindAllCandidatsMethodExists(): void
    {
        // Test that the method exists and can be called
        $this->assertTrue(method_exists($this->repository, 'findAllCandidats'));
    }

    public function testFindAllEmployesMethodExists(): void
    {
        // Test that the method exists and can be called
        $this->assertTrue(method_exists($this->repository, 'findAllEmployes'));
    }

    public function testFindAllRHMethodExists(): void
    {
        // Test that the method exists and can be called
        $this->assertTrue(method_exists($this->repository, 'findAllRH'));
    }

    public function testFindByRoleCallsCorrectMethods(): void
    {
        // Setup mock expectations
        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('u')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('u.role = :role')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('role', User::ROLE_CANDIDAT)
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('u.id', 'ASC')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        // Call the method
        $result = $this->repository->findByRole(User::ROLE_CANDIDAT);

        // Assert result
        $this->assertIsArray($result);
    }

    public function testUserRoleConstants(): void
    {
        $this->assertEquals('CANDIDAT', User::ROLE_CANDIDAT);
        $this->assertEquals('EMPLOYE', User::ROLE_EMPLOYE);
        $this->assertEquals('RH', User::ROLE_RH);
    }
}