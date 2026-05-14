<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Candidat;
use App\Entity\Employe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        // createClient() boots the kernel - do NOT call bootKernel() separately
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();

        // Create database schema for tests
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        try {
            $schemaTool->dropSchema($metadata);
        } catch (\Exception $e) {
            // Ignore if schema doesn't exist
        }
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->entityManager) {
            $this->entityManager->close();
        }
        $this->entityManager = null;
    }

    public function testLoginPageIsAccessible(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
    }

    public function testRegisterCandidatPageIsAccessible(): void
    {
        $this->client->request('GET', '/register/candidat');

        $this->assertResponseIsSuccessful();
    }

    public function testRegisterEmployePageIsAccessible(): void
    {
        $this->client->request('GET', '/register/employe');

        $this->assertResponseIsSuccessful();
    }

    public function testGoogleConnectRedirect(): void
    {
        $this->client->request('GET', '/connect/google');

        // Should redirect to Google OAuth
        $this->assertResponseRedirects();
    }

    public function testLogoutRoute(): void
    {
        $this->client->request('GET', '/logout');

        // Logout should redirect
        $this->assertResponseRedirects();
    }

    private function createTestUser(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setEmail($email);
        $user->setMotDePasse('hashedpassword');
        $user->setRole(User::ROLE_CANDIDAT);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestEmploye(string $matricule): User
    {
        $user = new User();
        $user->setNom('Test');
        $user->setPrenom('Employe');
        $user->setEmail('test.employe.' . $matricule . '@example.com');
        $user->setMotDePasse('hashedpassword');
        $user->setRole(User::ROLE_EMPLOYE);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $employe = new Employe();
        $employe->setMatricule($matricule);
        $employe->setPosition('Test Position');
        $employe->setDateEmbauche(new \DateTime());
        $employe->setUser($user);

        $this->entityManager->persist($employe);
        $this->entityManager->flush();

        return $user;
    }

    private function cleanupUser(User $user): void
    {
        if ($user->getCandidat()) {
            $this->entityManager->remove($user->getCandidat());
        }
        if ($user->getEmploye()) {
            $this->entityManager->remove($user->getEmploye());
        }
        if ($user->getRh()) {
            $this->entityManager->remove($user->getRh());
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}