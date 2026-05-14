<?php

namespace App\Tests\Repository;

use App\Entity\Prime;
use App\Entity\Contract;
use App\Repository\PrimeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PrimeRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?PrimeRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(Prime::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }

    public function testRepositoryIsInstanceOfPrimeRepository(): void
    {
        $this->assertInstanceOf(PrimeRepository::class, $this->repository);
    }

    public function testCanPersistAndFindPrime(): void
    {
        $prime = new Prime();
        $prime->setMontant('1500');
        $prime->setDateAttribution('2024-03-15');
        $prime->setDescription('Prime de performance');

        $this->entityManager->persist($prime);
        $this->entityManager->flush();

        $foundPrime = $this->repository->find($prime->getId());

        $this->assertNotNull($foundPrime);
        $this->assertEquals('1500', $foundPrime->getMontant());
        $this->assertEquals('2024-03-15', $foundPrime->getDateAttribution());
        $this->assertEquals('Prime de performance', $foundPrime->getDescription());

        // Clean up
        $this->entityManager->remove($foundPrime);
        $this->entityManager->flush();
    }

    public function testCanFindAllPrimes(): void
    {
        $prime1 = new Prime();
        $prime1->setMontant('1000');
        $prime1->setDateAttribution('2024-01-15');

        $prime2 = new Prime();
        $prime2->setMontant('2000');
        $prime2->setDateAttribution('2024-02-15');

        $this->entityManager->persist($prime1);
        $this->entityManager->persist($prime2);
        $this->entityManager->flush();

        $primes = $this->repository->findAll();

        $this->assertGreaterThanOrEqual(2, count($primes));

        // Clean up
        $this->entityManager->remove($prime1);
        $this->entityManager->remove($prime2);
        $this->entityManager->flush();
    }

    public function testCanFindPrimeByMontant(): void
    {
        $prime = new Prime();
        $prime->setMontant('2500.50');
        $prime->setDateAttribution('2024-04-10');

        $this->entityManager->persist($prime);
        $this->entityManager->flush();

        $foundPrimes = $this->repository->findBy(['montant' => '2500.50']);

        $this->assertGreaterThanOrEqual(1, count($foundPrimes));
        $this->assertEquals('2500.50', $foundPrimes[0]->getMontant());

        // Clean up
        $this->entityManager->remove($prime);
        $this->entityManager->flush();
    }

    public function testCanFindPrimeByDateAttribution(): void
    {
        $prime = new Prime();
        $prime->setMontant('1800');
        $prime->setDateAttribution('2024-05-20');

        $this->entityManager->persist($prime);
        $this->entityManager->flush();

        $foundPrimes = $this->repository->findBy(['date_attribution' => '2024-05-20']);

        $this->assertGreaterThanOrEqual(1, count($foundPrimes));
        $this->assertEquals('2024-05-20', $foundPrimes[0]->getDateAttribution());

        // Clean up
        $this->entityManager->remove($prime);
        $this->entityManager->flush();
    }

    public function testCanFindOnePrimeByMultipleCriteria(): void
    {
        $prime = new Prime();
        $prime->setMontant('3000');
        $prime->setDateAttribution('2024-06-15');
        $prime->setDescription('Prime exceptionnelle');

        $this->entityManager->persist($prime);
        $this->entityManager->flush();

        $foundPrime = $this->repository->findOneBy([
            'montant' => '3000',
            'date_attribution' => '2024-06-15'
        ]);

        $this->assertNotNull($foundPrime);
        $this->assertEquals('3000', $foundPrime->getMontant());
        $this->assertEquals('2024-06-15', $foundPrime->getDateAttribution());
        $this->assertEquals('Prime exceptionnelle', $foundPrime->getDescription());

        // Clean up
        $this->entityManager->remove($foundPrime);
        $this->entityManager->flush();
    }

    public function testCanUpdatePrime(): void
    {
        $prime = new Prime();
        $prime->setMontant('1200');
        $prime->setDateAttribution('2024-07-01');
        $prime->setDescription('Prime initiale');

        $this->entityManager->persist($prime);
        $this->entityManager->flush();

        $id = $prime->getId();

        // Update
        $prime->setMontant('1500');
        $prime->setDescription('Prime mise à jour');
        $this->entityManager->flush();

        // Verify update
        $this->entityManager->clear();
        $updatedPrime = $this->repository->find($id);

        $this->assertEquals('1500', $updatedPrime->getMontant());
        $this->assertEquals('Prime mise à jour', $updatedPrime->getDescription());

        // Clean up
        $this->entityManager->remove($updatedPrime);
        $this->entityManager->flush();
    }

    public function testCanDeletePrime(): void
    {
        $prime = new Prime();
        $prime->setMontant('1000');
        $prime->setDateAttribution('2024-08-01');

        $this->entityManager->persist($prime);
        $this->entityManager->flush();

        $id = $prime->getId();

        // Delete
        $this->entityManager->remove($prime);
        $this->entityManager->flush();

        // Verify deletion
        $deletedPrime = $this->repository->find($id);
        $this->assertNull($deletedPrime);
    }

    public function testCanPersistPrimeWithoutDescription(): void
    {
        $prime = new Prime();
        $prime->setMontant('1700');
        $prime->setDateAttribution('2024-09-01');

        $this->entityManager->persist($prime);
        $this->entityManager->flush();

        $foundPrime = $this->repository->find($prime->getId());

        $this->assertNotNull($foundPrime);
        $this->assertNull($foundPrime->getDescription());

        // Clean up
        $this->entityManager->remove($foundPrime);
        $this->entityManager->flush();
    }

    public function testFindReturnsNullForNonExistentId(): void
    {
        $nonExistentId = 999999;
        $prime = $this->repository->find($nonExistentId);

        $this->assertNull($prime);
    }

    public function testFindOneByReturnsNullWhenNoMatch(): void
    {
        $prime = $this->repository->findOneBy([
            'montant' => '999999.99',
            'date_attribution' => '9999-12-31'
        ]);

        $this->assertNull($prime);
    }

    public function testCanFindPrimesOrderedByDateAttribution(): void
    {
        $prime1 = new Prime();
        $prime1->setMontant('1000');
        $prime1->setDateAttribution('2024-03-01');

        $prime2 = new Prime();
        $prime2->setMontant('1500');
        $prime2->setDateAttribution('2024-01-01');

        $prime3 = new Prime();
        $prime3->setMontant('2000');
        $prime3->setDateAttribution('2024-02-01');

        $this->entityManager->persist($prime1);
        $this->entityManager->persist($prime2);
        $this->entityManager->persist($prime3);
        $this->entityManager->flush();

        $primes = $this->repository->findBy([], ['date_attribution' => 'ASC']);

        $this->assertGreaterThanOrEqual(3, count($primes));

        // Clean up
        $this->entityManager->remove($prime1);
        $this->entityManager->remove($prime2);
        $this->entityManager->remove($prime3);
        $this->entityManager->flush();
    }

    public function testCanFindPrimesOrderedByMontant(): void
    {
        $prime1 = new Prime();
        $prime1->setMontant('3000');
        $prime1->setDateAttribution('2024-01-01');

        $prime2 = new Prime();
        $prime2->setMontant('1000');
        $prime2->setDateAttribution('2024-01-02');

        $prime3 = new Prime();
        $prime3->setMontant('2000');
        $prime3->setDateAttribution('2024-01-03');

        $this->entityManager->persist($prime1);
        $this->entityManager->persist($prime2);
        $this->entityManager->persist($prime3);
        $this->entityManager->flush();

        $primes = $this->repository->findBy([], ['montant' => 'DESC']);

        $this->assertGreaterThanOrEqual(3, count($primes));

        // Clean up
        $this->entityManager->remove($prime1);
        $this->entityManager->remove($prime2);
        $this->entityManager->remove($prime3);
        $this->entityManager->flush();
    }
}
