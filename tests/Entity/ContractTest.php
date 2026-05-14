<?php

namespace App\Tests\Entity;

use App\Entity\Contract;
use App\Entity\Employe;
use App\Entity\Rh;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ContractTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testContractCanBeCreated(): void
    {
        $contract = new Contract();
        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertNull($contract->getId());
    }

    public function testSetAndGetDateDebut(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-01-01');
        
        $this->assertEquals('2024-01-01', $contract->getDateDebut());
    }

    public function testSetAndGetDateFin(): void
    {
        $contract = new Contract();
        $contract->setDateFin('2024-12-31');
        
        $this->assertEquals('2024-12-31', $contract->getDateFin());
    }

    public function testSetAndGetType(): void
    {
        $contract = new Contract();
        $contract->setType('CDI');
        
        $this->assertEquals('CDI', $contract->getType());
    }

    public function testSetAndGetStatut(): void
    {
        $contract = new Contract();
        $contract->setStatut('Actif');
        
        $this->assertEquals('Actif', $contract->getStatut());
    }

    public function testSetAndGetSalaireBase(): void
    {
        $contract = new Contract();
        $contract->setSalaireBase('3500.00');
        
        $this->assertEquals('3500.00', $contract->getSalaireBase());
    }

    public function testSetAndGetDescription(): void
    {
        $contract = new Contract();
        $contract->setDescription('Contrat à durée indéterminée');
        
        $this->assertEquals('Contrat à durée indéterminée', $contract->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $contract = new Contract();
        $contract->setDescription(null);
        
        $this->assertNull($contract->getDescription());
    }

    public function testSetAndGetEmploye(): void
    {
        $contract = new Contract();
        $employe = $this->createMock(Employe::class);
        
        $contract->setEmploye($employe);
        
        $this->assertSame($employe, $contract->getEmploye());
    }

    public function testEmployeCanBeNull(): void
    {
        $contract = new Contract();
        $contract->setEmploye(null);
        
        $this->assertNull($contract->getEmploye());
    }

    public function testSetAndGetRh(): void
    {
        $contract = new Contract();
        $rh = $this->createMock(Rh::class);
        
        $contract->setRh($rh);
        
        $this->assertSame($rh, $contract->getRh());
    }

    public function testRhCanBeNull(): void
    {
        $contract = new Contract();
        $contract->setRh(null);
        
        $this->assertNull($contract->getRh());
    }

    public function testFluentInterface(): void
    {
        $contract = new Contract();
        $employe = $this->createMock(Employe::class);
        $rh = $this->createMock(Rh::class);
        
        $result = $contract
            ->setDateDebut('2024-01-01')
            ->setDateFin('2024-12-31')
            ->setType('CDI')
            ->setStatut('Actif')
            ->setSalaireBase('4000')
            ->setDescription('Test contract')
            ->setEmploye($employe)
            ->setRh($rh);
        
        $this->assertSame($contract, $result);
    }

    public function testValidationFailsWhenDateDebutIsBlank(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenDateFinIsBlank(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('');
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenTypeIsBlank(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenTypeIsTooShort(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('AB'); // Only 2 characters
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('Type must be at least 3 characters', $violations[0]->getMessage());
    }

    public function testValidationFailsWhenStatutIsBlank(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDI');
        $contract->setStatut('');
        $contract->setSalaireBase('3000');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenSalaireBaseIsBlank(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenSalaireBaseIsNegative(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('-1000');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('Salary must be positive', $violations[0]->getMessage());
    }

    public function testValidationFailsWhenDescriptionIsTooLong(): void
    {
        $contract = new Contract();
        $longDescription = str_repeat('a', 256); // 256 characters
        
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');
        $contract->setDescription($longDescription);
        
        $violations = $this->validator->validate($contract);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('Description too long', $violations[0]->getMessage());
    }

    public function testValidationFailsWhenDateFinIsBeforeDateDebut(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-12-31');
        $contract->setDateFin('2024-01-01'); // End before start
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('End date must be greater than start date', $violations[0]->getMessage());
    }

    public function testValidationFailsWhenDateFinEqualsDateDebut(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-06-15');
        $contract->setDateFin('2024-06-15'); // Same date
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('End date must be greater than start date', $violations[0]->getMessage());
    }

    public function testValidationPassesWithValidData(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3500.50');
        $contract->setDescription('Contrat standard');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertCount(0, $violations);
    }

    public function testValidationPassesWithoutDescription(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertCount(0, $violations);
    }

    public function testValidationPassesWithMaxLengthDescription(): void
    {
        $contract = new Contract();
        $description = str_repeat('a', 255); // Exactly 255 characters
        
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');
        $contract->setDescription($description);
        
        $violations = $this->validator->validate($contract);
        
        $this->assertCount(0, $violations);
    }

    public function testDifferentContractTypes(): void
    {
        $contract = new Contract();
        
        $types = ['CDI', 'CDD', 'Stage', 'Alternance', 'Freelance'];
        
        foreach ($types as $type) {
            $contract->setType($type);
            $this->assertEquals($type, $contract->getType());
        }
    }

    public function testDifferentStatutValues(): void
    {
        $contract = new Contract();
        
        $statuts = ['Actif', 'Inactif', 'Terminé', 'En attente', 'Suspendu'];
        
        foreach ($statuts as $statut) {
            $contract->setStatut($statut);
            $this->assertEquals($statut, $contract->getStatut());
        }
    }

    public function testSalaireBaseWithDifferentFormats(): void
    {
        $contract = new Contract();
        
        $salaires = ['2000', '2500.50', '3000.00', '4500.99'];
        
        foreach ($salaires as $salaire) {
            $contract->setSalaireBase($salaire);
            $this->assertEquals($salaire, $contract->getSalaireBase());
        }
    }

    public function testValidDateRanges(): void
    {
        $contract = new Contract();
        $contract->setType('CDI');
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');
        
        // Test various valid date ranges
        $dateRanges = [
            ['2024-01-01', '2024-12-31'],
            ['2024-06-01', '2025-05-31'],
            ['2024-01-15', '2024-01-16'], // One day difference
        ];
        
        foreach ($dateRanges as [$debut, $fin]) {
            $contract->setDateDebut($debut);
            $contract->setDateFin($fin);
            
            $violations = $this->validator->validate($contract);
            $this->assertCount(0, $violations, "Failed for range: $debut to $fin");
        }
    }

    public function testTypeMinimumLength(): void
    {
        $contract = new Contract();
        $contract->setDateDebut('2024-01-01');
        $contract->setDateFin('2024-12-31');
        $contract->setType('ABC'); // Exactly 3 characters (minimum)
        $contract->setStatut('Actif');
        $contract->setSalaireBase('3000');
        
        $violations = $this->validator->validate($contract);
        
        $this->assertCount(0, $violations);
    }
}
