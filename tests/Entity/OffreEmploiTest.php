<?php

namespace App\Tests\Entity;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Entity\RH;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OffreEmploiTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testOffreEmploiCanBeCreated(): void
    {
        $offre = new OffreEmploi();
        $this->assertInstanceOf(OffreEmploi::class, $offre);
        $this->assertNull($offre->getId());
    }

    public function testSetAndGetTitre(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Développeur PHP Symfony');
        
        $this->assertEquals('Développeur PHP Symfony', $offre->getTitre());
    }

    public function testSetAndGetLocalisation(): void
    {
        $offre = new OffreEmploi();
        $offre->setLocalisation('Paris, France');
        
        $this->assertEquals('Paris, France', $offre->getLocalisation());
    }

    public function testSetAndGetTypeContrat(): void
    {
        $offre = new OffreEmploi();
        $offre->setTypeContrat('CDI');
        
        $this->assertEquals('CDI', $offre->getTypeContrat());
    }

    public function testSetAndGetStatut(): void
    {
        $offre = new OffreEmploi();
        $offre->setStatut('Ouverte');
        
        $this->assertEquals('Ouverte', $offre->getStatut());
    }

    public function testSetAndGetDatePublication(): void
    {
        $offre = new OffreEmploi();
        $date = new \DateTimeImmutable('2024-01-01');
        $offre->setDatePublication($date);
        
        $this->assertEquals($date, $offre->getDatePublication());
    }

    public function testSetAndGetDateExpiration(): void
    {
        $offre = new OffreEmploi();
        $date = new \DateTimeImmutable('2024-12-31');
        $offre->setDateExpiration($date);
        
        $this->assertEquals($date, $offre->getDateExpiration());
    }

    public function testSetAndGetDescription(): void
    {
        $offre = new OffreEmploi();
        $description = 'Nous recherchons un développeur PHP expérimenté...';
        $offre->setDescription($description);
        
        $this->assertEquals($description, $offre->getDescription());
    }

    public function testSetAndGetRh(): void
    {
        $offre = new OffreEmploi();
        $rh = $this->createMock(RH::class);
        
        $offre->setRh($rh);
        
        $this->assertSame($rh, $offre->getRh());
    }

    public function testSetAndGetLatitude(): void
    {
        $offre = new OffreEmploi();
        $offre->setLatitude(48.8566);
        
        $this->assertEquals(48.8566, $offre->getLatitude());
    }

    public function testSetAndGetLongitude(): void
    {
        $offre = new OffreEmploi();
        $offre->setLongitude(2.3522);
        
        $this->assertEquals(2.3522, $offre->getLongitude());
    }

    public function testCandidaturesCollection(): void
    {
        $offre = new OffreEmploi();
        
        $this->assertCount(0, $offre->getCandidatures());
    }

    public function testAddCandidature(): void
    {
        $offre = new OffreEmploi();
        $candidature = $this->createMock(Candidature::class);
        
        $candidature->expects($this->once())
            ->method('setOffreEmploi')
            ->with($offre);
        
        $offre->addCandidature($candidature);
        
        $this->assertCount(1, $offre->getCandidatures());
        $this->assertTrue($offre->getCandidatures()->contains($candidature));
    }

    public function testAddCandidatureDoesNotDuplicates(): void
    {
        $offre = new OffreEmploi();
        $candidature = $this->createMock(Candidature::class);
        
        $candidature->expects($this->once())
            ->method('setOffreEmploi')
            ->with($offre);
        
        $offre->addCandidature($candidature);
        $offre->addCandidature($candidature); // Ajout en double
        
        $this->assertCount(1, $offre->getCandidatures());
    }

    public function testRemoveCandidature(): void
    {
        $offre = new OffreEmploi();
        $candidature = $this->createMock(Candidature::class);
        
        $candidature->expects($this->atLeastOnce())
            ->method('setOffreEmploi');
        
        $candidature->expects($this->once())
            ->method('getOffreEmploi')
            ->willReturn($offre);
        
        $offre->addCandidature($candidature);
        $this->assertCount(1, $offre->getCandidatures());
        
        $offre->removeCandidature($candidature);
        $this->assertCount(0, $offre->getCandidatures());
    }

    public function testFluentInterface(): void
    {
        $offre = new OffreEmploi();
        $rh = $this->createMock(RH::class);
        
        $result = $offre
            ->setTitre('Développeur Full Stack')
            ->setLocalisation('Lyon')
            ->setTypeContrat('CDI')
            ->setStatut('Ouverte')
            ->setDatePublication(new \DateTimeImmutable('2024-01-01'))
            ->setDateExpiration(new \DateTimeImmutable('2024-12-31'))
            ->setDescription('Description du poste')
            ->setRh($rh)
            ->setLatitude(45.7640)
            ->setLongitude(4.8357);
        
        $this->assertSame($offre, $result);
    }

    public function testValidationFailsWhenTitreIsBlank(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('');
        $offre->setLocalisation('Paris');
        $offre->setTypeContrat('CDI');
        $offre->setStatut('Ouverte');
        $offre->setDatePublication(new \DateTimeImmutable('2024-01-01'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-12-31'));
        $offre->setDescription('Description valide');
        
        $violations = $this->validator->validate($offre);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenTitreIsTooShort(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('AB'); // Moins de 3 caractères
        $offre->setLocalisation('Paris');
        $offre->setTypeContrat('CDI');
        $offre->setStatut('Ouverte');
        $offre->setDatePublication(new \DateTimeImmutable('2024-01-01'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-12-31'));
        $offre->setDescription('Description valide');
        
        $violations = $this->validator->validate($offre);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenTitreIsTooLong(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre(str_repeat('a', 256)); // Plus de 255 caractères
        $offre->setLocalisation('Paris');
        $offre->setTypeContrat('CDI');
        $offre->setStatut('Ouverte');
        $offre->setDatePublication(new \DateTimeImmutable('2024-01-01'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-12-31'));
        $offre->setDescription('Description valide');
        
        $violations = $this->validator->validate($offre);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenLocalisationIsBlank(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Développeur PHP');
        $offre->setLocalisation('');
        $offre->setTypeContrat('CDI');
        $offre->setStatut('Ouverte');
        $offre->setDatePublication(new \DateTimeImmutable('2024-01-01'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-12-31'));
        $offre->setDescription('Description valide');
        
        $violations = $this->validator->validate($offre);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenTypeContratIsBlank(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Développeur PHP');
        $offre->setLocalisation('Paris');
        $offre->setTypeContrat('');
        $offre->setStatut('Ouverte');
        $offre->setDatePublication(new \DateTimeImmutable('2024-01-01'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-12-31'));
        $offre->setDescription('Description valide');
        
        $violations = $this->validator->validate($offre);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenStatutIsBlank(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Développeur PHP');
        $offre->setLocalisation('Paris');
        $offre->setTypeContrat('CDI');
        $offre->setStatut('');
        $offre->setDatePublication(new \DateTimeImmutable('2024-01-01'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-12-31'));
        $offre->setDescription('Description valide');
        
        $violations = $this->validator->validate($offre);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenDescriptionIsBlank(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Développeur PHP');
        $offre->setLocalisation('Paris');
        $offre->setTypeContrat('CDI');
        $offre->setStatut('Ouverte');
        $offre->setDatePublication(new \DateTimeImmutable('2024-01-01'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-12-31'));
        $offre->setDescription('');
        
        $violations = $this->validator->validate($offre);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenDescriptionIsTooShort(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Développeur PHP');
        $offre->setLocalisation('Paris');
        $offre->setTypeContrat('CDI');
        $offre->setStatut('Ouverte');
        $offre->setDatePublication(new \DateTimeImmutable('2024-01-01'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-12-31'));
        $offre->setDescription('Court'); // Moins de 10 caractères
        
        $violations = $this->validator->validate($offre);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenDateExpirationIsBeforeDatePublication(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Développeur PHP');
        $offre->setLocalisation('Paris');
        $offre->setTypeContrat('CDI');
        $offre->setStatut('Ouverte');
        $offre->setDatePublication(new \DateTimeImmutable('2024-12-31'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-01-01')); // Avant la date de publication
        $offre->setDescription('Description valide du poste');
        
        $violations = $this->validator->validate($offre);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationPassesWithValidData(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Développeur PHP Symfony');
        $offre->setLocalisation('Paris, France');
        $offre->setTypeContrat('CDI');
        $offre->setStatut('Ouverte');
        $offre->setDatePublication(new \DateTimeImmutable('2024-01-01'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-12-31'));
        $offre->setDescription('Nous recherchons un développeur PHP expérimenté avec Symfony.');
        
        $violations = $this->validator->validate($offre);
        
        $this->assertCount(0, $violations);
    }

    public function testDifferentTypeContratValues(): void
    {
        $offre = new OffreEmploi();
        
        $types = ['CDI', 'CDD', 'Stage', 'Alternance', 'Freelance'];
        
        foreach ($types as $type) {
            $offre->setTypeContrat($type);
            $this->assertEquals($type, $offre->getTypeContrat());
        }
    }

    public function testDifferentStatutValues(): void
    {
        $offre = new OffreEmploi();
        
        $statuts = ['Ouverte', 'Fermée', 'En attente', 'Pourvue'];
        
        foreach ($statuts as $statut) {
            $offre->setStatut($statut);
            $this->assertEquals($statut, $offre->getStatut());
        }
    }

    public function testTitreMinimumLength(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Dev'); // Exactement 3 caractères
        $offre->setLocalisation('Paris');
        $offre->setTypeContrat('CDI');
        $offre->setStatut('Ouverte');
        $offre->setDatePublication(new \DateTimeImmutable('2024-01-01'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-12-31'));
        $offre->setDescription('Description valide du poste');
        
        $violations = $this->validator->validate($offre);
        
        $this->assertCount(0, $violations);
    }

    public function testDescriptionMinimumLength(): void
    {
        $offre = new OffreEmploi();
        $offre->setTitre('Développeur');
        $offre->setLocalisation('Paris');
        $offre->setTypeContrat('CDI');
        $offre->setStatut('Ouverte');
        $offre->setDatePublication(new \DateTimeImmutable('2024-01-01'));
        $offre->setDateExpiration(new \DateTimeImmutable('2024-12-31'));
        $offre->setDescription('1234567890'); // Exactement 10 caractères
        
        $violations = $this->validator->validate($offre);
        
        $this->assertCount(0, $violations);
    }

    public function testNullableCoordinates(): void
    {
        $offre = new OffreEmploi();
        
        $this->assertNull($offre->getLatitude());
        $this->assertNull($offre->getLongitude());
    }

    public function testValidCoordinates(): void
    {
        $offre = new OffreEmploi();
        
        // Paris
        $offre->setLatitude(48.8566);
        $offre->setLongitude(2.3522);
        
        $this->assertEquals(48.8566, $offre->getLatitude());
        $this->assertEquals(2.3522, $offre->getLongitude());
    }
}
