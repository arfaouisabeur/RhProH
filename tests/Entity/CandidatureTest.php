<?php

namespace App\Tests\Entity;

use App\Entity\Candidat;
use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CandidatureTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testCandidatureCanBeCreated(): void
    {
        $candidature = new Candidature();
        $this->assertInstanceOf(Candidature::class, $candidature);
        $this->assertNull($candidature->getId());
    }

    public function testSetAndGetDateCandidature(): void
    {
        $candidature = new Candidature();
        $date = new \DateTimeImmutable('2024-01-15');
        $candidature->setDateCandidature($date);
        
        $this->assertEquals($date, $candidature->getDateCandidature());
    }

    public function testSetAndGetStatut(): void
    {
        $candidature = new Candidature();
        $candidature->setStatut('en_attente');
        
        $this->assertEquals('en_attente', $candidature->getStatut());
    }

    public function testSetAndGetCvPath(): void
    {
        $candidature = new Candidature();
        $candidature->setCvPath('/uploads/cv_123.pdf');
        
        $this->assertEquals('/uploads/cv_123.pdf', $candidature->getCvPath());
    }

    public function testSetAndGetCvOriginalName(): void
    {
        $candidature = new Candidature();
        $candidature->setCvOriginalName('mon_cv.pdf');
        
        $this->assertEquals('mon_cv.pdf', $candidature->getCvOriginalName());
    }

    public function testSetAndGetCvSize(): void
    {
        $candidature = new Candidature();
        $candidature->setCvSize(1024000);
        
        $this->assertEquals(1024000, $candidature->getCvSize());
    }

    public function testGetCvUploadedAt(): void
    {
        $candidature = new Candidature();
        $date = new \DateTimeImmutable('2024-01-15 10:30:00');
        
        $reflection = new \ReflectionClass($candidature);
        $property = $reflection->getProperty('cvUploadedAt');
        $property->setAccessible(true);
        $property->setValue($candidature, $date);
        
        $this->assertEquals($date, $candidature->getCvUploadedAt());
    }

    public function testSetAndGetMatchScore(): void
    {
        $candidature = new Candidature();
        $candidature->setMatchScore(85);
        
        $this->assertEquals(85, $candidature->getMatchScore());
    }

    public function testGetMatchUpdatedAt(): void
    {
        $candidature = new Candidature();
        $date = new \DateTimeImmutable('2024-01-20');
        
        $reflection = new \ReflectionClass($candidature);
        $property = $reflection->getProperty('matchUpdatedAt');
        $property->setAccessible(true);
        $property->setValue($candidature, $date);
        
        $this->assertEquals($date, $candidature->getMatchUpdatedAt());
    }

    public function testSetAndGetCvSkills(): void
    {
        $candidature = new Candidature();
        $skills = 'PHP, Symfony, JavaScript, MySQL';
        $candidature->setCvSkills($skills);
        
        $this->assertEquals($skills, $candidature->getCvSkills());
    }

    public function testSetAndGetAiAnalysis(): void
    {
        $candidature = new Candidature();
        $analysis = 'Candidat avec un profil intéressant...';
        $candidature->setAiAnalysis($analysis);
        
        $this->assertEquals($analysis, $candidature->getAiAnalysis());
    }

    public function testSetAndGetSignatureRequestId(): void
    {
        $candidature = new Candidature();
        $candidature->setSignatureRequestId('abc123def456');
        
        $this->assertEquals('abc123def456', $candidature->getSignatureRequestId());
    }

    public function testSetAndGetContractStatus(): void
    {
        $candidature = new Candidature();
        $candidature->setContractStatus('certified');
        
        $this->assertEquals('certified', $candidature->getContractStatus());
    }

    public function testSetAndGetLettreMotivation(): void
    {
        $candidature = new Candidature();
        $lettre = str_repeat('Lettre de motivation. ', 10);
        $candidature->setLettreMotivation($lettre);
        
        $this->assertEquals($lettre, $candidature->getLettreMotivation());
    }

    public function testSetAndGetDisponibilite(): void
    {
        $candidature = new Candidature();
        $candidature->setDisponibilite('Immédiate');
        
        $this->assertEquals('Immédiate', $candidature->getDisponibilite());
    }

    public function testSetAndGetPretentionSalariale(): void
    {
        $candidature = new Candidature();
        $candidature->setPretentionSalariale(3500);
        
        $this->assertEquals(3500, $candidature->getPretentionSalariale());
    }

    public function testSetAndGetCandidat(): void
    {
        $candidature = new Candidature();
        $candidat = $this->createMock(Candidat::class);
        
        $candidature->setCandidat($candidat);
        
        $this->assertSame($candidat, $candidature->getCandidat());
    }

    public function testSetAndGetOffreEmploi(): void
    {
        $candidature = new Candidature();
        $offre = $this->createMock(OffreEmploi::class);
        
        $candidature->setOffreEmploi($offre);
        
        $this->assertSame($offre, $candidature->getOffreEmploi());
    }

    public function testFluentInterface(): void
    {
        $candidature = new Candidature();
        $candidat = $this->createMock(Candidat::class);
        $offre = $this->createMock(OffreEmploi::class);
        
        $result = $candidature
            ->setDateCandidature(new \DateTimeImmutable())
            ->setStatut('en_attente')
            ->setCvPath('/uploads/cv.pdf')
            ->setCandidat($candidat)
            ->setOffreEmploi($offre)
            ->setLettreMotivation(str_repeat('Test lettre. ', 10))
            ->setDisponibilite('Immédiate')
            ->setPretentionSalariale(3000);
        
        $this->assertSame($candidature, $result);
    }

    public function testValidationFailsWhenStatutIsBlank(): void
    {
        $candidature = new Candidature();
        $candidature->setStatut('');
        $candidature->setDateCandidature(new \DateTimeImmutable());
        $candidature->setCandidat($this->createMock(Candidat::class));
        $candidature->setOffreEmploi($this->createMock(OffreEmploi::class));
        
        $violations = $this->validator->validate($candidature);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenLettreMotivationIsTooShort(): void
    {
        $candidature = new Candidature();
        $candidature->setStatut('en_attente');
        $candidature->setDateCandidature(new \DateTimeImmutable());
        $candidature->setCandidat($this->createMock(Candidat::class));
        $candidature->setOffreEmploi($this->createMock(OffreEmploi::class));
        $candidature->setLettreMotivation('Court'); // Moins de 50 caractères
        
        $violations = $this->validator->validate($candidature);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenLettreMotivationIsTooLong(): void
    {
        $candidature = new Candidature();
        $candidature->setStatut('en_attente');
        $candidature->setDateCandidature(new \DateTimeImmutable());
        $candidature->setCandidat($this->createMock(Candidat::class));
        $candidature->setOffreEmploi($this->createMock(OffreEmploi::class));
        $candidature->setLettreMotivation(str_repeat('a', 1501)); // Plus de 1500 caractères
        
        $violations = $this->validator->validate($candidature);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationFailsWhenPretentionSalarialeIsNegative(): void
    {
        $candidature = new Candidature();
        $candidature->setStatut('en_attente');
        $candidature->setDateCandidature(new \DateTimeImmutable());
        $candidature->setCandidat($this->createMock(Candidat::class));
        $candidature->setOffreEmploi($this->createMock(OffreEmploi::class));
        $candidature->setLettreMotivation(str_repeat('Lettre valide. ', 10));
        $candidature->setPretentionSalariale(-1000);
        
        $violations = $this->validator->validate($candidature);
        
        $this->assertGreaterThan(0, count($violations));
    }

    public function testValidationPassesWithValidData(): void
    {
        $candidature = new Candidature();
        $candidature->setStatut('en_attente');
        $candidature->setDateCandidature(new \DateTimeImmutable());
        $candidature->setCandidat($this->createMock(Candidat::class));
        $candidature->setOffreEmploi($this->createMock(OffreEmploi::class));
        $candidature->setLettreMotivation(str_repeat('Lettre de motivation valide. ', 10));
        $candidature->setDisponibilite('Immédiate');
        $candidature->setPretentionSalariale(3500);
        
        $violations = $this->validator->validate($candidature);
        
        $this->assertCount(0, $violations);
    }

    public function testDifferentStatutValues(): void
    {
        $candidature = new Candidature();
        
        $statuts = ['en_attente', 'entretien', 'acceptee', 'refusee'];
        
        foreach ($statuts as $statut) {
            $candidature->setStatut($statut);
            $this->assertEquals($statut, $candidature->getStatut());
        }
    }

    public function testNullableFields(): void
    {
        $candidature = new Candidature();
        
        $this->assertNull($candidature->getCvPath());
        $this->assertNull($candidature->getCvOriginalName());
        $this->assertNull($candidature->getCvSize());
        $this->assertNull($candidature->getCvUploadedAt());
        $this->assertNull($candidature->getMatchScore());
        $this->assertNull($candidature->getMatchUpdatedAt());
        $this->assertNull($candidature->getCvSkills());
        $this->assertNull($candidature->getAiAnalysis());
        $this->assertNull($candidature->getSignatureRequestId());
        $this->assertNull($candidature->getContractStatus());
        $this->assertNull($candidature->getLettreMotivation());
        $this->assertNull($candidature->getDisponibilite());
        $this->assertNull($candidature->getPretentionSalariale());
    }

    public function testMatchScoreRange(): void
    {
        $candidature = new Candidature();
        
        $scores = [0, 25, 50, 75, 100];
        
        foreach ($scores as $score) {
            $candidature->setMatchScore($score);
            $this->assertEquals($score, $candidature->getMatchScore());
        }
    }

    public function testLettreMotivationMinimumLength(): void
    {
        $candidature = new Candidature();
        $candidature->setStatut('en_attente');
        $candidature->setDateCandidature(new \DateTimeImmutable());
        $candidature->setCandidat($this->createMock(Candidat::class));
        $candidature->setOffreEmploi($this->createMock(OffreEmploi::class));
        $candidature->setLettreMotivation(str_repeat('a', 50)); // Exactement 50 caractères
        
        $violations = $this->validator->validate($candidature);
        
        $this->assertCount(0, $violations);
    }

    public function testLettreMotivationMaximumLength(): void
    {
        $candidature = new Candidature();
        $candidature->setStatut('en_attente');
        $candidature->setDateCandidature(new \DateTimeImmutable());
        $candidature->setCandidat($this->createMock(Candidat::class));
        $candidature->setOffreEmploi($this->createMock(OffreEmploi::class));
        $candidature->setLettreMotivation(str_repeat('a', 1500)); // Exactement 1500 caractères
        
        $violations = $this->validator->validate($candidature);
        
        $this->assertCount(0, $violations);
    }
}
