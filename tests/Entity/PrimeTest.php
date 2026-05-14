<?php

namespace App\Tests\Entity;

use App\Entity\Prime;
use App\Entity\Contract;
use App\Entity\Tache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PrimeTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testPrimeCanBeCreated(): void
    {
        $prime = new Prime();
        $this->assertInstanceOf(Prime::class, $prime);
        $this->assertNull($prime->getId());
    }

    public function testConstructorInitializesTachesCollection(): void
    {
        $prime = new Prime();
        $this->assertCount(0, $prime->getTaches());
    }

    public function testSetAndGetMontant(): void
    {
        $prime = new Prime();
        $prime->setMontant('1500.75');
        
        $this->assertEquals('1500.75', $prime->getMontant());
    }

    public function testSetAndGetDateAttribution(): void
    {
        $prime = new Prime();
        $prime->setDateAttribution('2024-03-15');
        
        $this->assertEquals('2024-03-15', $prime->getDateAttribution());
    }

    public function testSetAndGetDescription(): void
    {
        $prime = new Prime();
        $prime->setDescription('Prime de performance exceptionnelle');
        
        $this->assertEquals('Prime de performance exceptionnelle', $prime->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $prime = new Prime();
        $prime->setDescription(null);
        
        $this->assertNull($prime->getDescription());
    }

    public function testSetAndGetContract(): void
    {
        $prime = new Prime();
        $contract = $this->createMock(Contract::class);
        
        $prime->setContract($contract);
        
        $this->assertSame($contract, $prime->getContract());
    }

    public function testFluentInterface(): void
    {
        $prime = new Prime();
        $contract = $this->createMock(Contract::class);
        
        $result = $prime
            ->setMontant('2000')
            ->setDateAttribution('2024-04-01')
            ->setDescription('Prime annuelle')
            ->setContract($contract);
        
        $this->assertSame($prime, $result);
    }

    public function testAddTache(): void
    {
        $prime = new Prime();
        $tache = $this->createMock(Tache::class);
        
        $tache->expects($this->once())
            ->method('setPrime')
            ->with($prime);
        
        $result = $prime->addTache($tache);
        
        $this->assertSame($prime, $result);
        $this->assertCount(1, $prime->getTaches());
        $this->assertTrue($prime->getTaches()->contains($tache));
    }

    public function testAddTacheDoesNotAddDuplicate(): void
    {
        $prime = new Prime();
        $tache = $this->createMock(Tache::class);
        
        $tache->expects($this->once())
            ->method('setPrime')
            ->with($prime);
        
        $prime->addTache($tache);
        $prime->addTache($tache); // Try to add again
        
        $this->assertCount(1, $prime->getTaches());
    }

    public function testRemoveTache(): void
    {
        $prime = new Prime();
        $tache = $this->createMock(Tache::class);
        
        // First call when adding, second call when removing
        $tache->expects($this->exactly(2))
            ->method('setPrime')
            ->willReturnCallback(function ($arg) use ($prime, $tache) {
                static $callCount = 0;
                $callCount++;
                if ($callCount === 1) {
                    $this->assertSame($prime, $arg);
                } else {
                    $this->assertNull($arg);
                }
                return $tache; // Return self for fluent interface
            });
        
        $tache->expects($this->once())
            ->method('getPrime')
            ->willReturn($prime);
        
        $prime->addTache($tache);
        $this->assertCount(1, $prime->getTaches());
        
        $result = $prime->removeTache($tache);
        
        $this->assertSame($prime, $result);
        $this->assertCount(0, $prime->getTaches());
    }

    public function testRemoveTacheWhenNotInCollection(): void
    {
        $prime = new Prime();
        $tache = $this->createMock(Tache::class);
        
        $result = $prime->removeTache($tache);
        
        $this->assertSame($prime, $result);
        $this->assertCount(0, $prime->getTaches());
    }

    public function testValidationFailsWhenMontantIsBlank(): void
    {
        $prime = new Prime();
        $contract = $this->createMock(Contract::class);
        
        $prime->setMontant('');
        $prime->setDateAttribution('2024-03-15');
        $prime->setContract($contract);
        
        $violations = $this->validator->validate($prime);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenMontantIsNegative(): void
    {
        $prime = new Prime();
        $contract = $this->createMock(Contract::class);
        
        $prime->setMontant('-500');
        $prime->setDateAttribution('2024-03-15');
        $prime->setContract($contract);
        
        $violations = $this->validator->validate($prime);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('Le montant doit être positif', $violations[0]->getMessage());
    }

    public function testValidationFailsWhenDateAttributionIsBlank(): void
    {
        $prime = new Prime();
        $contract = $this->createMock(Contract::class);
        
        $prime->setMontant('1000');
        $prime->setDateAttribution('');
        $prime->setContract($contract);
        
        $violations = $this->validator->validate($prime);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenContractIsNull(): void
    {
        $prime = new Prime();
        
        $prime->setMontant('1000');
        $prime->setDateAttribution('2024-03-15');
        $prime->setContract(null);
        
        $violations = $this->validator->validate($prime);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('Contrat obligatoire', $violations[0]->getMessage());
    }

    public function testValidationFailsWhenDescriptionIsTooLong(): void
    {
        $prime = new Prime();
        $contract = $this->createMock(Contract::class);
        
        $longDescription = str_repeat('a', 256); // 256 characters
        
        $prime->setMontant('1000');
        $prime->setDateAttribution('2024-03-15');
        $prime->setDescription($longDescription);
        $prime->setContract($contract);
        
        $violations = $this->validator->validate($prime);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('Description trop longue', $violations[0]->getMessage());
    }

    public function testValidationPassesWithValidData(): void
    {
        $prime = new Prime();
        $contract = $this->createMock(Contract::class);
        
        $prime->setMontant('1500.50');
        $prime->setDateAttribution('2024-03-15');
        $prime->setDescription('Prime de performance');
        $prime->setContract($contract);
        
        $violations = $this->validator->validate($prime);
        
        $this->assertCount(0, $violations);
    }

    public function testValidationPassesWithoutDescription(): void
    {
        $prime = new Prime();
        $contract = $this->createMock(Contract::class);
        
        $prime->setMontant('1000');
        $prime->setDateAttribution('2024-03-15');
        $prime->setContract($contract);
        
        $violations = $this->validator->validate($prime);
        
        $this->assertCount(0, $violations);
    }

    public function testValidationPassesWithMaxLengthDescription(): void
    {
        $prime = new Prime();
        $contract = $this->createMock(Contract::class);
        
        $description = str_repeat('a', 255); // Exactly 255 characters
        
        $prime->setMontant('1000');
        $prime->setDateAttribution('2024-03-15');
        $prime->setDescription($description);
        $prime->setContract($contract);
        
        $violations = $this->validator->validate($prime);
        
        $this->assertCount(0, $violations);
    }

    public function testMontantWithDifferentFormats(): void
    {
        $prime = new Prime();
        
        $montants = ['1000', '1500.50', '2000.99', '500.00'];
        
        foreach ($montants as $montant) {
            $prime->setMontant($montant);
            $this->assertEquals($montant, $prime->getMontant());
        }
    }

    public function testMultipleTachesCanBeAdded(): void
    {
        $prime = new Prime();
        
        $tache1 = $this->createMock(Tache::class);
        $tache2 = $this->createMock(Tache::class);
        $tache3 = $this->createMock(Tache::class);
        
        $tache1->method('setPrime')->with($prime);
        $tache2->method('setPrime')->with($prime);
        $tache3->method('setPrime')->with($prime);
        
        $prime->addTache($tache1);
        $prime->addTache($tache2);
        $prime->addTache($tache3);
        
        $this->assertCount(3, $prime->getTaches());
    }
}
