<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Entity\Candidat;
use App\Entity\Employe;
use App\Entity\RH;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
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

        // Create database schema for tests — disable FK checks so dropSchema works cleanly
        $connection = $this->entityManager->getConnection();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $schemaTool->dropSchema($metadata);
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
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

    public function testCanPersistAndFindUser(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@test.com');
        $user->setMotDePasse('password123');
        $user->setRole(User::ROLE_CANDIDAT);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $foundUser = $this->repository->find($user->getId());

        $this->assertNotNull($foundUser);
        $this->assertEquals('Dupont', $foundUser->getNom());
        $this->assertEquals('Jean', $foundUser->getPrenom());
        $this->assertEquals('jean.dupont@test.com', $foundUser->getEmail());
        $this->assertEquals(User::ROLE_CANDIDAT, $foundUser->getRole());

        // Clean up
        $this->entityManager->remove($foundUser);
        $this->entityManager->flush();
    }

    public function testFindByRole(): void
    {
        // Create test users with different roles
        $candidat = new User();
        $candidat->setNom('Martin');
        $candidat->setPrenom('Alice');
        $candidat->setEmail('alice.martin@test.com');
        $candidat->setMotDePasse('password123');
        $candidat->setRole(User::ROLE_CANDIDAT);

        $employe = new User();
        $employe->setNom('Bernard');
        $employe->setPrenom('Bob');
        $employe->setEmail('bob.bernard@test.com');
        $employe->setMotDePasse('password123');
        $employe->setRole(User::ROLE_EMPLOYE);

        $this->entityManager->persist($candidat);
        $this->entityManager->persist($employe);
        $this->entityManager->flush();

        // Test findByRole for candidats
        $candidats = $this->repository->findByRole(User::ROLE_CANDIDAT);
        $this->assertGreaterThanOrEqual(1, count($candidats));
        
        $foundCandidat = false;
        foreach ($candidats as $user) {
            $this->assertEquals(User::ROLE_CANDIDAT, $user->getRole());
            if ($user->getEmail() === 'alice.martin@test.com') {
                $foundCandidat = true;
            }
        }
        $this->assertTrue($foundCandidat);

        // Test findByRole for employes
        $employes = $this->repository->findByRole(User::ROLE_EMPLOYE);
        $this->assertGreaterThanOrEqual(1, count($employes));
        
        $foundEmploye = false;
        foreach ($employes as $user) {
            $this->assertEquals(User::ROLE_EMPLOYE, $user->getRole());
            if ($user->getEmail() === 'bob.bernard@test.com') {
                $foundEmploye = true;
            }
        }
        $this->assertTrue($foundEmploye);

        // Clean up
        $this->entityManager->remove($candidat);
        $this->entityManager->remove($employe);
        $this->entityManager->flush();
    }

    public function testFindAllCandidats(): void
    {
        $candidat = new User();
        $candidat->setNom('Candidat');
        $candidat->setPrenom('Test');
        $candidat->setEmail('candidat.test@test.com');
        $candidat->setMotDePasse('password123');
        $candidat->setRole(User::ROLE_CANDIDAT);

        $this->entityManager->persist($candidat);
        $this->entityManager->flush();

        $candidats = $this->repository->findAllCandidats();
        
        $this->assertIsArray($candidats);
        $this->assertGreaterThanOrEqual(1, count($candidats));
        
        $foundCandidat = false;
        foreach ($candidats as $user) {
            $this->assertEquals(User::ROLE_CANDIDAT, $user->getRole());
            if ($user->getEmail() === 'candidat.test@test.com') {
                $foundCandidat = true;
            }
        }
        $this->assertTrue($foundCandidat);

        // Clean up
        $this->entityManager->remove($candidat);
        $this->entityManager->flush();
    }

    public function testFindAllEmployes(): void
    {
        $employe = new User();
        $employe->setNom('Employe');
        $employe->setPrenom('Test');
        $employe->setEmail('employe.test@test.com');
        $employe->setMotDePasse('password123');
        $employe->setRole(User::ROLE_EMPLOYE);

        $this->entityManager->persist($employe);
        $this->entityManager->flush();

        $employes = $this->repository->findAllEmployes();
        
        $this->assertIsArray($employes);
        $this->assertGreaterThanOrEqual(1, count($employes));
        
        $foundEmploye = false;
        foreach ($employes as $user) {
            $this->assertEquals(User::ROLE_EMPLOYE, $user->getRole());
            if ($user->getEmail() === 'employe.test@test.com') {
                $foundEmploye = true;
            }
        }
        $this->assertTrue($foundEmploye);

        // Clean up
        $this->entityManager->remove($employe);
        $this->entityManager->flush();
    }

    public function testFindAllRH(): void
    {
        $rh = new User();
        $rh->setNom('RH');
        $rh->setPrenom('Test');
        $rh->setEmail('rh.test@test.com');
        $rh->setMotDePasse('password123');
        $rh->setRole(User::ROLE_RH);

        $this->entityManager->persist($rh);
        $this->entityManager->flush();

        $rhUsers = $this->repository->findAllRH();
        
        $this->assertIsArray($rhUsers);
        $this->assertGreaterThanOrEqual(1, count($rhUsers));
        
        $foundRH = false;
        foreach ($rhUsers as $user) {
            $this->assertEquals(User::ROLE_RH, $user->getRole());
            if ($user->getEmail() === 'rh.test@test.com') {
                $foundRH = true;
            }
        }
        $this->assertTrue($foundRH);

        // Clean up
        $this->entityManager->remove($rh);
        $this->entityManager->flush();
    }

    public function testCanFindUserByEmail(): void
    {
        $user = new User();
        $user->setNom('Email');
        $user->setPrenom('Test');
        $user->setEmail('unique.email@test.com');
        $user->setMotDePasse('password123');
        $user->setRole(User::ROLE_CANDIDAT);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $foundUser = $this->repository->findOneBy(['email' => 'unique.email@test.com']);

        $this->assertNotNull($foundUser);
        $this->assertEquals('unique.email@test.com', $foundUser->getEmail());
        $this->assertEquals('Email', $foundUser->getNom());

        // Clean up
        $this->entityManager->remove($foundUser);
        $this->entityManager->flush();
    }

    public function testCanUpdateUser(): void
    {
        $user = new User();
        $user->setNom('Original');
        $user->setPrenom('Name');
        $user->setEmail('original@test.com');
        $user->setMotDePasse('password123');
        $user->setRole(User::ROLE_CANDIDAT);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $id = $user->getId();

        // Update
        $user->setNom('Updated');
        $user->setPrenom('NewName');
        $user->setStatut('inactif');
        $this->entityManager->flush();

        // Verify update
        $this->entityManager->clear();
        $updatedUser = $this->repository->find($id);

        $this->assertEquals('Updated', $updatedUser->getNom());
        $this->assertEquals('NewName', $updatedUser->getPrenom());
        $this->assertEquals('inactif', $updatedUser->getStatut());

        // Clean up
        $this->entityManager->remove($updatedUser);
        $this->entityManager->flush();
    }

    public function testCanDeleteUser(): void
    {
        $user = new User();
        $user->setNom('ToDelete');
        $user->setPrenom('User');
        $user->setEmail('delete@test.com');
        $user->setMotDePasse('password123');
        $user->setRole(User::ROLE_CANDIDAT);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $id = $user->getId();

        // Delete
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        // Verify deletion
        $deletedUser = $this->repository->find($id);
        $this->assertNull($deletedUser);
    }

    public function testFindReturnsNullForNonExistentId(): void
    {
        $nonExistentId = 999999;
        $user = $this->repository->find($nonExistentId);

        $this->assertNull($user);
    }

    public function testFindOneByReturnsNullWhenNoMatch(): void
    {
        $user = $this->repository->findOneBy([
            'email' => 'nonexistent@test.com',
            'nom' => 'NonExistent'
        ]);

        $this->assertNull($user);
    }

    public function testFindByRoleReturnsEmptyArrayForNonExistentRole(): void
    {
        $users = $this->repository->findByRole('NONEXISTENT_ROLE');

        $this->assertIsArray($users);
        $this->assertEmpty($users);
    }

    public function testCanFindUsersByMultipleCriteria(): void
    {
        $user = new User();
        $user->setNom('MultiCriteria');
        $user->setPrenom('Test');
        $user->setEmail('multicriteria@test.com');
        $user->setMotDePasse('password123');
        $user->setRole(User::ROLE_EMPLOYE);
        $user->setStatut('actif');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $foundUser = $this->repository->findOneBy([
            'nom' => 'MultiCriteria',
            'role' => User::ROLE_EMPLOYE,
            'statut' => 'actif'
        ]);

        $this->assertNotNull($foundUser);
        $this->assertEquals('MultiCriteria', $foundUser->getNom());
        $this->assertEquals(User::ROLE_EMPLOYE, $foundUser->getRole());
        $this->assertEquals('actif', $foundUser->getStatut());

        // Clean up
        $this->entityManager->remove($foundUser);
        $this->entityManager->flush();
    }

    public function testCanFindUsersOrderedById(): void
    {
        $user1 = new User();
        $user1->setNom('First');
        $user1->setPrenom('User');
        $user1->setEmail('first@test.com');
        $user1->setMotDePasse('password123');
        $user1->setRole(User::ROLE_CANDIDAT);

        $user2 = new User();
        $user2->setNom('Second');
        $user2->setPrenom('User');
        $user2->setEmail('second@test.com');
        $user2->setMotDePasse('password123');
        $user2->setRole(User::ROLE_CANDIDAT);

        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->flush();

        $users = $this->repository->findBy(['role' => User::ROLE_CANDIDAT], ['id' => 'ASC']);

        $this->assertGreaterThanOrEqual(2, count($users));

        // Clean up
        $this->entityManager->remove($user1);
        $this->entityManager->remove($user2);
        $this->entityManager->flush();
    }

    public function testUserWithRelatedEntities(): void
    {
        // Test with Candidat
        $user = new User();
        $user->setNom('WithCandidat');
        $user->setPrenom('Test');
        $user->setEmail('withcandidat@test.com');
        $user->setMotDePasse('password123');
        $user->setRole(User::ROLE_CANDIDAT);

        $candidat = new Candidat();
        $candidat->setNiveauEtude('Master');
        $candidat->setExperience(3);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $candidat->setUser($user);
        $this->entityManager->persist($candidat);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $foundUser = $this->repository->find($user->getId());
        $this->assertNotNull($foundUser);
        $this->assertNotNull($foundUser->getCandidat());
        $this->assertEquals('Master', $foundUser->getCandidat()->getNiveauEtude());

        // Clean up — use managed entities from the current identity map (not the detached $candidat)
        $managedCandidat = $foundUser->getCandidat();
        if ($managedCandidat) {
            $this->entityManager->remove($managedCandidat);
        }
        $this->entityManager->remove($foundUser);
        $this->entityManager->flush();
    }
}