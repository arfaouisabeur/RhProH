<?php

namespace App\Tests\Entity;

use App\Entity\Projet;
use App\Entity\RH;
use App\Entity\Employe;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProjetTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testProjetCanBeCreated(): void
    {
        $projet = new Projet();
        $this->assertInstanceOf(Projet::class, $projet);
        $this->assertNull($projet->getId());
    }

    public function testSetAndGetTitre(): void
    {
        $projet = new Projet();
        $projet->setTitre('Refonte du système RH');

        $this->assertEquals('Refonte du système RH', $projet->getTitre());
    }

    public function testSetAndGetStatut(): void
    {
        $projet = new Projet();
        $projet->setStatut('en_cours');

        $this->assertEquals('en_cours', $projet->getStatut());
    }

    public function testSetAndGetDescription(): void
    {
        $projet = new Projet();
        $projet->setDescription('Description complète du projet de refonte.');

        $this->assertEquals('Description complète du projet de refonte.', $projet->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $projet = new Projet();
        $projet->setDescription(null);

        $this->assertNull($projet->getDescription());
    }

    public function testSetAndGetDateDebut(): void
    {
        $projet = new Projet();
        $date = new \DateTimeImmutable('2024-03-01');
        $projet->setDateDebut($date);

        $this->assertEquals($date, $projet->getDateDebut());
    }

    public function testSetAndGetDateFin(): void
    {
        $projet = new Projet();
        $date = new \DateTimeImmutable('2024-12-31');
        $projet->setDateFin($date);

        $this->assertEquals($date, $projet->getDateFin());
    }

    // Les dates ne sont plus nullables.
    // Les tests testDateDebutCanBeNull et testDateFinCanBeNull sont supprimés.

    public function testSetAndGetRh(): void
    {
        $projet = new Projet();
        $rh = $this->createMock(RH::class);

        $projet->setRh($rh);

        $this->assertSame($rh, $projet->getRh());
    }

    // RH n'est plus nullable.
    // Le test testRhCanBeNull est supprimé.

    public function testSetAndGetResponsableEmploye(): void
    {
        $projet = new Projet();
        $employe = $this->createMock(Employe::class);

        $projet->setResponsableEmploye($employe);

        $this->assertSame($employe, $projet->getResponsableEmploye());
    }

    public function testResponsableEmployeCanBeNull(): void
    {
        $projet = new Projet();
        $projet->setResponsableEmploye(null);

        $this->assertNull($projet->getResponsableEmploye());
    }

    public function testIsMeetingRequestedDefaultIsFalse(): void
    {
        $projet = new Projet();

        $this->assertFalse($projet->isMeetingRequested());
    }

    public function testSetAndGetIsMeetingRequested(): void
    {
        $projet = new Projet();
        $projet->setIsMeetingRequested(true);

        $this->assertTrue($projet->isMeetingRequested());
    }

    public function testSetIsMeetingRequestedToFalse(): void
    {
        $projet = new Projet();
        $projet->setIsMeetingRequested(true);
        $projet->setIsMeetingRequested(false);

        $this->assertFalse($projet->isMeetingRequested());
    }

    public function testFluentInterface(): void
    {
        $projet = new Projet();
        $rh = new RH();
        $employe = new Employe();

        $result = $projet
            ->setTitre('Projet de migration')
            ->setStatut('en_attente')
            ->setDescription('Description du projet de migration.')
            ->setDateDebut(new \DateTimeImmutable('tomorrow'))
            ->setDateFin(new \DateTimeImmutable('+6 months'))
            ->setRh($rh)
            ->setResponsableEmploye($employe)
            ->setIsMeetingRequested(false);

        $this->assertSame($projet, $result);
    }

    public function testValidationFailsWhenTitreIsBlank(): void
    {
        $projet = new Projet();
        $projet->setTitre('');
        $projet->setStatut('en_cours');
        $projet->setDateDebut(new \DateTimeImmutable('tomorrow'));
        $projet->setDateFin(new \DateTimeImmutable('+6 months'));

        $violations = $this->validator->validate($projet);

        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenTitreContainsDigits(): void
    {
        $projet = new Projet();
        $projet->setTitre('Projet2024');
        $projet->setStatut('en_cours');
        $projet->setDateDebut(new \DateTimeImmutable('tomorrow'));
        $projet->setDateFin(new \DateTimeImmutable('+6 months'));

        $violations = $this->validator->validate($projet);

        $this->assertGreaterThan(0, count($violations));
        $violationMessages = array_map(fn($v) => $v->getMessage(), iterator_to_array($violations));
        $this->assertContains('Le titre du projet ne doit pas contenir de chiffres.', $violationMessages);
    }

    // DateDebut n'est plus nullable.
    // Le test testValidationFailsWhenDateDebutIsBlank est supprimé.

    // DateFin n'est plus nullable.
    // Le test testValidationFailsWhenDateFinIsBlank est supprimé.

    public function testValidationFailsWhenDateFinIsBeforeDateDebut(): void
    {
        $projet = new Projet();
        $projet->setTitre('Refonte du système RH');
        $projet->setStatut('en_cours');
        $projet->setDateDebut(new \DateTimeImmutable('+6 months'));
        $projet->setDateFin(new \DateTimeImmutable('tomorrow')); // Fin avant début

        $violations = $this->validator->validate($projet);

        $this->assertGreaterThan(0, count($violations));
        $violationMessages = array_map(fn($v) => $v->getMessage(), iterator_to_array($violations));
        $this->assertContains('La date de fin doit être strictement après la date de début.', $violationMessages);
    }

    public function testDifferentStatutValues(): void
    {
        $projet = new Projet();

        $statuts = ['en_attente', 'en_cours', 'termine', 'annule'];

        foreach ($statuts as $statut) {
            $projet->setStatut($statut);
            $this->assertEquals($statut, $projet->getStatut());
        }
    }

    public function testDefaultValues(): void
    {
        $projet = new Projet();

        $this->assertNull($projet->getId());
        $this->assertEquals('', $projet->getTitre());
        $this->assertEquals('en_attente', $projet->getStatut());
        $this->assertInstanceOf(\DateTimeInterface::class, $projet->getDateDebut());
        $this->assertInstanceOf(\DateTimeInterface::class, $projet->getDateFin());
    }

    public function testValidationPassesWithValidData(): void
    {
        $projet = new Projet();
        $rh = new RH();

        $projet->setTitre('Refonte du système RH');
        $projet->setStatut('en_cours');
        $projet->setDescription('Description complète du projet.');
        $projet->setDateDebut(new \DateTimeImmutable('tomorrow'));
        $projet->setDateFin(new \DateTimeImmutable('+6 months'));
        $projet->setRh($rh);

        $violations = $this->validator->validate($projet);

        // On vérifie uniquement les champs scalaires (hors relations Doctrine)
        $scalarViolations = array_filter(
            iterator_to_array($violations),
            fn($v) => !in_array($v->getPropertyPath(), ['rh'])
        );

        $this->assertCount(0, $scalarViolations);
    }

    public function testValidationPassesWithoutDescription(): void
    {
        $projet = new Projet();

        $projet->setTitre('Refonte du système RH');
        $projet->setStatut('en_cours');
        $projet->setDateDebut(new \DateTimeImmutable('tomorrow'));
        $projet->setDateFin(new \DateTimeImmutable('+6 months'));

        $violations = $this->validator->validate($projet);

        $descriptionViolations = array_filter(
            iterator_to_array($violations),
            fn($v) => $v->getPropertyPath() === 'description'
        );

        $this->assertCount(0, $descriptionViolations);
    }

    public function testToggleMeetingRequested(): void
    {
        $projet = new Projet();

        $this->assertFalse($projet->isMeetingRequested());

        $projet->setIsMeetingRequested(true);
        $this->assertTrue($projet->isMeetingRequested());

        $projet->setIsMeetingRequested(false);
        $this->assertFalse($projet->isMeetingRequested());
    }
}
