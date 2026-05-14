<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testValidUser(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setRole(User::ROLE_CANDIDAT);

        $manager = new UserManager();
        $this->assertTrue($manager->validate($user));
    }

    public function testUserWithoutNom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire');

        $user = new User();
        $user->setPrenom('Jean');
        $user->setEmail('jean@example.com');
        $user->setRole(User::ROLE_CANDIDAT);

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithoutPrenom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom est obligatoire');

        $user = new User();
        $user->setNom('Dupont');
        $user->setEmail('dupont@example.com');
        $user->setRole(User::ROLE_CANDIDAT);

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email invalide');

        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('email_invalide');
        $user->setRole(User::ROLE_CANDIDAT);

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithoutRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le rôle est obligatoire');

        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testUserWithInvalidRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rôle invalide');

        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setRole('ROLE_INVALID');

        $manager = new UserManager();
        $manager->validate($user);
    }

    public function testCanAccessRHFeaturesWithRHUser(): void
    {
        $user = new User();
        $user->setRole(User::ROLE_RH);

        $manager = new UserManager();
        $this->assertTrue($manager->canAccessRHFeatures($user));
    }

    public function testCanAccessRHFeaturesWithNonRHUser(): void
    {
        $user = new User();
        $user->setRole(User::ROLE_CANDIDAT);

        $manager = new UserManager();
        $this->assertFalse($manager->canAccessRHFeatures($user));
    }

    public function testIsActiveUserWithActiveStatus(): void
    {
        $user = new User();
        $user->setStatut('actif');

        $manager = new UserManager();
        $this->assertTrue($manager->isActiveUser($user));
    }

    public function testIsActiveUserWithInactiveStatus(): void
    {
        $user = new User();
        $user->setStatut('inactif');

        $manager = new UserManager();
        $this->assertFalse($manager->isActiveUser($user));
    }

    public function testGetFullUserInfoWithValidUser(): void
    {
        $user = new User();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setRole(User::ROLE_RH);
        $user->setStatut('actif');

        $manager = new UserManager();
        $result = $manager->getFullUserInfo($user);

        $this->assertIsArray($result);
        $this->assertEquals('Jean Dupont', $result['fullName']);
        $this->assertEquals('jean.dupont@example.com', $result['email']);
        $this->assertEquals(User::ROLE_RH, $result['role']);
        $this->assertEquals('actif', $result['statut']);
        $this->assertTrue($result['isActive']);
        $this->assertTrue($result['canAccessRH']);
    }

    public function testGetFullUserInfoWithInvalidUser(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $user = new User();
        // User invalide (pas de nom)
        $user->setPrenom('Jean');
        $user->setEmail('jean@example.com');
        $user->setRole(User::ROLE_CANDIDAT);

        $manager = new UserManager();
        $manager->getFullUserInfo($user);
    }
}