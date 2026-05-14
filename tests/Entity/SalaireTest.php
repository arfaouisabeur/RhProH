<?php

namespace App\Tests\Entity;

use App\Entity\Salaire;
use App\Entity\Contract;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SalaireTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testSalaireCanBeCreated(): void
    {
        $salaire = new Salaire();
        $this->assertInstanceOf(Salaire::class, $salaire);
        $this->assertNull($salaire->getId());
    }

    public function testSetAndGetMois(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Janvier');
        
        $this->assertEquals('Janvier', $salaire->getMois());
    }

    public function testSetAndGetAnnee(): void
    {
        $salaire = new Salaire();
        $salaire->setAnnee('2024');
        
        $this->assertEquals('2024', $salaire->getAnnee());
    }

    public function testSetAndGetMontant(): void
    {
        $salaire = new Salaire();
        $salaire->setMontant('5000.50');
        
        $this->assertEquals('5000.50', $salaire->getMontant());
    }

    public function testSetAndGetDatePaiement(): void
    {
        $salaire = new Salaire();
        $salaire->setDatePaiement('2024-01-15');
        
        $this->assertEquals('2024-01-15', $salaire->getDatePaiement());
    }

    public function testDatePaiementCanBeNull(): void
    {
        $salaire = new Salaire();
        $salaire->setDatePaiement(null);
        
        $this->assertNull($salaire->getDatePaiement());
    }

    public function testSetAndGetStatut(): void
    {
        $salaire = new Salaire();
        $salaire->setStatut('Payé');
        
        $this->assertEquals('Payé', $salaire->getStatut());
    }

    public function testSetAndGetContract(): void
    {
        $salaire = new Salaire();
        $contract = $this->createMock(Contract::class);
        
        $salaire->setContract($contract);
        
        $this->assertSame($contract, $salaire->getContract());
    }

    public function testContractCanBeNull(): void
    {
        $salaire = new Salaire();
        $salaire->setContract(null);
        
        $this->assertNull($salaire->getContract());
    }

    public function testFluentInterface(): void
    {
        $salaire = new Salaire();
        $contract = $this->createMock(Contract::class);
        
        $result = $salaire
            ->setMois('Février')
            ->setAnnee('2024')
            ->setMontant('6000')
            ->setDatePaiement('2024-02-15')
            ->setStatut('En attente')
            ->setContract($contract);
        
        $this->assertSame($salaire, $result);
    }

    public function testValidationFailsWhenMoisIsBlank(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('');
        $salaire->setAnnee('2024');
        $salaire->setMontant('5000');
        $salaire->setStatut('Payé');
        
        $violations = $this->validator->validate($salaire);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('Mois est obligatoire', $violations[0]->getMessage());
    }

    public function testValidationFailsWhenAnneeIsBlank(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Janvier');
        $salaire->setAnnee('');
        $salaire->setMontant('5000');
        $salaire->setStatut('Payé');
        
        $violations = $this->validator->validate($salaire);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenAnneeIsInvalid(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Janvier');
        $salaire->setAnnee('24'); // Invalid year format
        $salaire->setMontant('5000');
        $salaire->setStatut('Payé');
        
        $violations = $this->validator->validate($salaire);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('Année invalide (YYYY)', $violations[0]->getMessage());
    }

    public function testValidationFailsWhenMontantIsBlank(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Janvier');
        $salaire->setAnnee('2024');
        $salaire->setMontant('');
        $salaire->setStatut('Payé');
        
        $violations = $this->validator->validate($salaire);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenMontantIsNegative(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Janvier');
        $salaire->setAnnee('2024');
        $salaire->setMontant('-1000');
        $salaire->setStatut('Payé');
        
        $violations = $this->validator->validate($salaire);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('Le montant doit être positif', $violations[0]->getMessage());
    }

    public function testValidationFailsWhenStatutIsBlank(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Janvier');
        $salaire->setAnnee('2024');
        $salaire->setMontant('5000');
        $salaire->setStatut('');
        
        $violations = $this->validator->validate($salaire);
        
        $this->assertGreaterThan(0, count($violations));
        $this->assertEquals('Statut obligatoire', $violations[0]->getMessage());
    }

    public function testValidationPassesWithValidData(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Janvier');
        $salaire->setAnnee('2024');
        $salaire->setMontant('5000.50');
        $salaire->setStatut('Payé');
        $salaire->setDatePaiement('2024-01-15');
        
        $violations = $this->validator->validate($salaire);
        
        $this->assertCount(0, $violations);
    }

    public function testValidationPassesWithoutDatePaiement(): void
    {
        $salaire = new Salaire();
        $salaire->setMois('Janvier');
        $salaire->setAnnee('2024');
        $salaire->setMontant('5000');
        $salaire->setStatut('En attente');
        
        $violations = $this->validator->validate($salaire);
        
        $this->assertCount(0, $violations);
    }

    public function testDifferentStatutValues(): void
    {
        $salaire = new Salaire();
        
        $statuts = ['Payé', 'En attente', 'Annulé', 'En cours'];
        
        foreach ($statuts as $statut) {
            $salaire->setStatut($statut);
            $this->assertEquals($statut, $salaire->getStatut());
        }
    }

    public function testDifferentMoisValues(): void
    {
        $salaire = new Salaire();
        
        $mois = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        
        foreach ($mois as $m) {
            $salaire->setMois($m);
            $this->assertEquals($m, $salaire->getMois());
        }
    }

    public function testMontantWithDecimalValues(): void
    {
        $salaire = new Salaire();
        
        $montants = ['1000.00', '2500.50', '3999.99', '10000.25'];
        
        foreach ($montants as $montant) {
            $salaire->setMontant($montant);
            $this->assertEquals($montant, $salaire->getMontant());
        }
    }
}
