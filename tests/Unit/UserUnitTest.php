<?php

namespace App\Tests\Unit;

use App\Entity\User;
use App\Entity\Candidat;
use App\Entity\Employe;
use App\Entity\RH;
use PHPUnit\Framework\TestCase;

class UserUnitTest extends TestCase
{
    public function testUserCanBeCreated(): void
    {
        $user = new User();
        $this->assertInstanceOf(User::class, $user);
        $this->assertNull($user->getId());
    }

    public function testSetAndGetNom(): void
    {
        $user = new User();
        $user->setNom('Dupont');

        $this->assertEquals('Dupont', $user->getNom());
    }

    public function testSetAndGetPrenom(): void
    {
        $user = new User();
        $user->setPrenom('Jean');

        $this->assertEquals('Jean', $user->getPrenom());
    }

    public function testSetAndGetEmail(): void
    {
        $user = new User();
        $user->setEmail('jean.dupont@example.com');

        $this->assertEquals('jean.dupont@example.com', $user->getEmail());
    }

    public function testSetAndGetMotDePasse(): void
    {
        $user = new User();
        $user->setMotDePasse('password123');

        $this->assertEquals('password123', $user->getPassword());
    }

    public function testSetAndGetRole(): void
    {
        $user = new User();
        $user->setRole(User::ROLE_CANDIDAT);

        $this->assertEquals(User::ROLE_CANDIDAT, $user->getRole());
    }

    public function testRoleConstants(): void
    {
        $this->assertEquals('CANDIDAT', User::ROLE_CANDIDAT);
        $this->assertEquals('EMPLOYE', User::ROLE_EMPLOYE);
        $this->assertEquals('RH', User::ROLE_RH);
    }

    public function testIsRH(): void
    {
        $user = new User();

        $this->assertFalse($user->isRH());

        $user->setRole(User::ROLE_RH);
        $this->assertTrue($user->isRH());
    }

    public function testIsEmploye(): void
    {
        $user = new User();

        $this->assertFalse($user->isEmploye());

        $user->setRole(User::ROLE_EMPLOYE);
        $this->assertTrue($user->isEmploye());
    }

    public function testIsCandidat(): void
    {
        $user = new User();

        $this->assertFalse($user->isCandidat());

        $user->setRole(User::ROLE_CANDIDAT);
        $this->assertTrue($user->isCandidat());
    }

    public function testGetFullName(): void
    {
        $user = new User();
        $user->setPrenom('Jean');
        $user->setNom('Dupont');

        $this->assertEquals('Jean Dupont', $user->getFullName());
    }

    public function testGetRoles(): void
    {
        $user = new User();

        // Default roles
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        // With role
        $user->setRole(User::ROLE_CANDIDAT);
        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_CANDIDAT', $roles);
    }

    public function testGetUserIdentifier(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }

    public function testDefaultStatutIsActif(): void
    {
        $user = new User();

        $this->assertEquals('actif', $user->getStatut());
    }

    public function testSetStatutInactif(): void
    {
        $user = new User();
        $user->setStatut('inactif');

        $this->assertEquals('inactif', $user->getStatut());
    }

    public function testFluentInterface(): void
    {
        $user = new User();
        $candidat = $this->createMock(Candidat::class);

        $result = $user
            ->setNom('Dupont')
            ->setPrenom('Jean')
            ->setEmail('jean.dupont@example.com')
            ->setMotDePasse('password123')
            ->setRole(User::ROLE_CANDIDAT)
            ->setStatut('actif')
            ->setCandidat($candidat);

        $this->assertSame($user, $result);
    }

    public function testEraseCredentials(): void
    {
        $user = new User();

        // Should not throw any exception
        $user->eraseCredentials();
        
        // Add assertion to make test not risky
        $this->assertTrue(true, 'eraseCredentials method executed without exception');
    }
}