<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Candidat;
use App\Entity\Employe;
use App\Entity\RH;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

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

    public function testSetAndGetTelephone(): void
    {
        $user = new User();
        $user->setTelephone('0123456789');

        $this->assertEquals('0123456789', $user->getTelephone());
    }

    public function testTelephoneCanBeNull(): void
    {
        $user = new User();
        $user->setTelephone(null);

        $this->assertNull($user->getTelephone());
    }

    public function testSetAndGetAdresse(): void
    {
        $user = new User();
        $user->setAdresse('123 Rue de la Paix');

        $this->assertEquals('123 Rue de la Paix', $user->getAdresse());
    }

    public function testAdresseCanBeNull(): void
    {
        $user = new User();
        $user->setAdresse(null);

        $this->assertNull($user->getAdresse());
    }

    public function testSetAndGetRole(): void
    {
        $user = new User();
        $user->setRole(User::ROLE_CANDIDAT);

        $this->assertEquals(User::ROLE_CANDIDAT, $user->getRole());
    }

    public function testRoleCanBeNull(): void
    {
        $user = new User();
        $user->setRole(null);

        $this->assertNull($user->getRole());
    }

    public function testSetAndGetAvatarPath(): void
    {
        $user = new User();
        $user->setAvatarPath('/uploads/avatar.jpg');

        $this->assertEquals('/uploads/avatar.jpg', $user->getAvatarPath());
    }

    public function testAvatarPathCanBeNull(): void
    {
        $user = new User();
        $user->setAvatarPath(null);

        $this->assertNull($user->getAvatarPath());
    }

    public function testSetAndGetStatut(): void
    {
        $user = new User();

        $statuts = ['actif', 'inactif', 'suspendu', 'en_attente'];

        foreach ($statuts as $statut) {
            $user->setStatut($statut);
            $this->assertEquals($statut, $user->getStatut());
        }
    }

    public function testDifferentRoleValues(): void
    {
        $user = new User();

        $roles = [User::ROLE_CANDIDAT, User::ROLE_EMPLOYE, User::ROLE_RH];

        foreach ($roles as $role) {
            $user->setRole($role);
            $this->assertEquals($role, $user->getRole());
        }
    }

    public function testEraseCredentials(): void
    {
        $user = new User();

        // Should not throw any exception
        $user->eraseCredentials();
        
        // Add assertion to make test not risky
        $this->assertTrue(true, 'eraseCredentials method executed without exception');
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

    public function testFluentInterface(): void
    {
        $user = new User();
        $candidat = $this->createMock(Candidat::class);

        $result = $user
            ->setNom('Dupont')
            ->setPrenom('Jean')
            ->setEmail('jean.dupont@example.com')
            ->setMotDePasse('password123')
            ->setAdresse('123 Rue de la Paix')
            ->setTelephone('0123456789')
            ->setRole(User::ROLE_CANDIDAT)
            ->setStatut('actif')
            ->setAvatarPath('/uploads/avatar.jpg')
            ->setGoogleId('googleId123')
            ->setCandidat($candidat);

        $this->assertSame($user, $result);
    }

    public function testRhCanBeNull(): void
    {
        $user = new User();
        $user->setRh(null);

        $this->assertNull($user->getRh());
    }

    public function testSetAndGetRh(): void
    {
        $user = new User();
        $rh = $this->createMock(RH::class);

        $user->setRh($rh);

        $this->assertSame($rh, $user->getRh());
    }

    public function testEmployeCanBeNull(): void
    {
        $user = new User();
        $user->setEmploye(null);

        $this->assertNull($user->getEmploye());
    }

    public function testSetAndGetEmploye(): void
    {
        $user = new User();
        $employe = $this->createMock(Employe::class);

        $user->setEmploye($employe);

        $this->assertSame($employe, $user->getEmploye());
    }

    public function testCandidatCanBeNull(): void
    {
        $user = new User();
        $user->setCandidat(null);

        $this->assertNull($user->getCandidat());
    }

    public function testSetAndGetCandidat(): void
    {
        $user = new User();
        $candidat = $this->createMock(Candidat::class);

        $user->setCandidat($candidat);

        $this->assertSame($candidat, $user->getCandidat());
    }

    public function testGoogleIdCanBeNull(): void
    {
        $user = new User();
        $user->setGoogleId(null);

        $this->assertNull($user->getGoogleId());
    }

    public function testSetAndGetGoogleId(): void
    {
        $user = new User();
        $user->setGoogleId('googleId123456');

        $this->assertEquals('googleId123456', $user->getGoogleId());
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
}