<?php

namespace App\Tests\Repository;

use App\Entity\Contract;
use App\Entity\Employe;
use App\Entity\Rh;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ContractRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?ContractRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(Contract::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }

    public function testRepositoryIsInstanceOfContractRepository(): void
    {
        $this->assertInstanceOf(ContractRepository::class, $this->repository);
    }

    public function testCanPersistAndFindContract(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3500');
        $contract->setDescription('Contrat standard');

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        $foundContract = $this->repository->find($contract->getId());

        $this->assertNotNull($foundContract);
        $this->assertEquals('2024-01-01', $foundContract->getDateDebut());
        $this->assertEquals('2024-12-31', $foundContract->getDateFin());
        $this->assertEquals('CDI', $foundContract->getType());
        $this->assertEquals('Actif', $foundContract->getStatut());
        $this->assertEquals('3500', $foundContract->getSalaireBase());
        $this->assertEquals('Contrat standard', $foundContract->getDescription());

        // Clean up
        $this->entityManager->remove($foundContract);
        $this->entityManager->flush();
    }

    public function testCanFindAllContracts(): void
    {
        $contract1 = new Contract();
        $contract1->setDateDebut('2024-01-01');
        $contract1->setDateFin('2024-12-31');
        $contract1->setType('CDI');
        $contract1->setStatut('Actif');
        $contract1->setSalaireBase('3000');

        $contract2 = new Contract();
        $contract2->setDateDebut('2024-02-01');
        $contract2->setDateFin('2024-08-31');
        $contract2->setType('CDD');
        $contract2->setStatut('Actif');
        $contract2->setSalaireBase('2500');

        $this->entityManager->persist($contract1);
        $this->entityManager->persist($contract2);
        $this->entityManager->flush();

        $contracts = $this->repository->findAll();

        $this->assertGreaterThanOrEqual(2, count($contracts));

        // Clean up
        $this->entityManager->remove($contract1);
        $this->entityManager->remove($contract2);
        $this->entityManager->flush();
    }

    public function testCanFindContractByType(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-03-01');
        $contract->setDateFin('2024-09-30');
        $contract->setType('Stage');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('1000');

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        $foundContracts = $this->repository->findBy(['type' => 'Stage']);

        $this->assertGreaterThanOrEqual(1, count($foundContracts));
        $this->assertEquals('Stage', $foundContracts[0]->getType());

        // Clean up
        $this->entityManager->remove($contract);
        $this->entityManager->flush();
    }

    public function testCanFindContractByStatut(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-04-01');
        $contract->setDateFin('2024-10-31');
        $contract->setType('CDD');
        $contract->setStatut('Terminé');
        $contract->setSalaireBase('2800');

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        $foundContracts = $this->repository->findBy(['statut' => 'Terminé']);

        $this->assertGreaterThanOrEqual(1, count($foundContracts));
        $this->assertEquals('Terminé', $foundContracts[0]->getStatut());

        // Clean up
        $this->entityManager->remove($contract);
        $this->entityManager->flush();
    }

    public function testCanFindOneContractByMultipleCriteria(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-05-01');
        $contract->setDateFin('2024-11-30');
        $contract->setType('Alternance');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('1500');

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        $foundContract = $this->repository->findOneBy([
            'type' => 'Alternance',
            'statut' => 'Actif'
        ]);

        $this->assertNotNull($foundContract);
        $this->assertEquals('Alternance', $foundContract->getType());
        $this->assertEquals('Actif', $foundContract->getStatut());

        // Clean up
        $this->entityManager->remove($foundContract);
        $this->entityManager->flush();
    }

    public function testCanUpdateContract(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-06-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDD');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        $id = $contract->getId();

        // Update
        $contract->setStatut('Terminé');
        $contract->setSalaireBase('3200');
        $this->entityManager->flush();

        // Verify update
        $this->entityManager->clear();
        $updatedContract = $this->repository->find($id);

        $this->assertEquals('Terminé', $updatedContract->getStatut());
        $this->assertEquals('3200', $updatedContract->getSalaireBase());

        // Clean up
        $this->entityManager->remove($updatedContract);
        $this->entityManager->flush();
    }

    public function testCanDeleteContract(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-07-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('4000');

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        $id = $contract->getId();

        // Delete
        $this->entityManager->remove($contract);
        $this->entityManager->flush();

        // Verify deletion
        $deletedContract = $this->repository->find($id);
        $this->assertNull($deletedContract);
    }

    public function testCanPersistContractWithoutDescription(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-08-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3500');

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        $foundContract = $this->repository->find($contract->getId());

        $this->assertNotNull($foundContract);
        $this->assertNull($foundContract->getDescription());

        // Clean up
        $this->entityManager->remove($foundContract);
        $this->entityManager->flush();
    }

    public function testFindReturnsNullForNonExistentId(): void
    {
        $nonExistentId = 999999;
        $contract = $this->repository->find($nonExistentId);

        $this->assertNull($contract);
    }

    public function testFindOneByReturnsNullWhenNoMatch(): void
    {
        $contract = $this->repository->findOneBy([
            'type' => 'NonExistentType',
            'statut' => 'NonExistentStatut'
        ]);

        $this->assertNull($contract);
    }

    public function testCanFindContractsOrderedByDateDebut(): void
    {
        $contract1 = new Contract();
        $contract1->setDateDebut('2024-03-01');
        $contract1->setDateFin('2024-12-31');
        $contract1->setType('CDI');
        $contract1->setStatut('Actif');
        $contract1->setSalaireBase('3000');

        $contract2 = new Contract();
        $contract2->setDateDebut('2024-01-01');
        $contract2->setDateFin('2024-12-31');
        $contract2->setType('CDI');
        $contract2->setStatut('Actif');
        $contract2->setSalaireBase('3500');

        $contract3 = new Contract();
        $contract3->setDateDebut('2024-02-01');
        $contract3->setDateFin('2024-12-31');
        $contract3->setType('CDD');
        $contract3->setStatut('Actif');
        $contract3->setSalaireBase('2800');

        $this->entityManager->persist($contract1);
        $this->entityManager->persist($contract2);
        $this->entityManager->persist($contract3);
        $this->entityManager->flush();

        $contracts = $this->repository->findBy([], ['date_debut' => 'ASC']);

        $this->assertGreaterThanOrEqual(3, count($contracts));

        // Clean up
        $this->entityManager->remove($contract1);
        $this->entityManager->remove($contract2);
        $this->entityManager->remove($contract3);
        $this->entityManager->flush();
    }

    public function testCanFindContractsOrderedBySalaireBase(): void
    {
        $contract1 = new Contract();
        $contract1->setDateDebut('2024-01-01');
        $contract1->setDateFin('2024-12-31');
        $contract1->setType('CDI');
        $contract1->setStatut('Actif');
        $contract1->setSalaireBase('5000');

        $contract2 = new Contract();
        $contract2->setDateDebut('2024-01-01');
        $contract2->setDateFin('2024-12-31');
        $contract2->setType('CDD');
        $contract2->setStatut('Actif');
        $contract2->setSalaireBase('2000');

        $contract3 = new Contract();
        $contract3->setDateDebut('2024-01-01');
        $contract3->setDateFin('2024-12-31');
        $contract3->setType('Stage');
        $contract3->setStatut('Actif');
        $contract3->setSalaireBase('3500');

        $this->entityManager->persist($contract1);
        $this->entityManager->persist($contract2);
        $this->entityManager->persist($contract3);
        $this->entityManager->flush();

        $contracts = $this->repository->findBy([], ['salaire_base' => 'DESC']);

        $this->assertGreaterThanOrEqual(3, count($contracts));

        // Clean up
        $this->entityManager->remove($contract1);
        $this->entityManager->remove($contract2);
        $this->entityManager->remove($contract3);
        $this->entityManager->flush();
    }

    public function testCanFindActiveContracts(): void
    {
        $contract1 = new Contract();
        $contract1->setDateDebut('2024-01-01');
        $contract1->setDateFin('2024-12-31');
        $contract1->setType('CDI');
        $contract1->setStatut('Actif');
        $contract1->setSalaireBase('3000');

        $contract2 = new Contract();
        $contract2->setDateDebut('2024-01-01');
        $contract2->setDateFin('2024-06-30');
        $contract2->setType('CDD');
        $contract2->setStatut('Terminé');
        $contract2->setSalaireBase('2500');

        $this->entityManager->persist($contract1);
        $this->entityManager->persist($contract2);
        $this->entityManager->flush();

        $activeContracts = $this->repository->findBy(['statut' => 'Actif']);

        $this->assertGreaterThanOrEqual(1, count($activeContracts));
        
        foreach ($activeContracts as $contract) {
            $this->assertEquals('Actif', $contract->getStatut());
        }

        // Clean up
        $this->entityManager->remove($contract1);
        $this->entityManager->remove($contract2);
        $this->entityManager->flush();
    }
}
