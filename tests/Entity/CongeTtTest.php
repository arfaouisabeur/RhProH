<?php

namespace App\Tests\Entity;

use App\Entity\CongeTt;
use App\Entity\Employe;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CongeTtTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testCongeTtCanBeCreated(): void
    {
        $congeTt = new CongeTt();
        $this->assertInstanceOf(CongeTt::class, $congeTt);
        $this->assertNull($congeTt->getId());
    }

    public function testSetAndGetTypeConge(): void
    {
        $congeTt = new CongeTt();
        $congeTt->setTypeConge('Congé annuel');

        $this->assertEquals('Congé annuel', $congeTt->getTypeConge());
    }

    public function testSetAndGetDateDebut(): void
    {
        $congeTt = new CongeTt();
        $date = new \DateTimeImmutable('2024-06-01');
        $congeTt->setDateDebut($date);

        $this->assertEquals($date, $congeTt->getDateDebut());
    }

    public function testSetAndGetDateFin(): void
    {
        $congeTt = new CongeTt();
        $date = new \DateTimeImmutable('2024-06-15');
        $congeTt->setDateFin($date);

        $this->assertEquals($date, $congeTt->getDateFin());
    }

    public function testSetAndGetStatut(): void
    {
        $congeTt = new CongeTt();
        $congeTt->setStatut('En attente');

        $this->assertEquals('En attente', $congeTt->getStatut());
    }

    public function testSetAndGetDescription(): void
    {
        $congeTt = new CongeTt();
        $congeTt->setDescription('Congé pour raisons personnelles.');

        $this->assertEquals('Congé pour raisons personnelles.', $congeTt->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $congeTt = new CongeTt();
        $congeTt->setDescription(null);

        $this->assertNull($congeTt->getDescription());
    }

    public function testSetAndGetEmploye(): void
    {
        $congeTt = new CongeTt();
        $employe = new Employe();

        $congeTt->setEmploye($employe);

        $this->assertSame($employe, $congeTt->getEmploye());
    }

    public function testSetAndGetDocumentPath(): void
    {
        $congeTt = new CongeTt();
        $congeTt->setDocumentPath('uploads/certificats/cert_123.pdf');

        $this->assertEquals('uploads/certificats/cert_123.pdf', $congeTt->getDocumentPath());
    }

    public function testDocumentPathCanBeNull(): void
    {
        $congeTt = new CongeTt();
        $congeTt->setDocumentPath(null);

        $this->assertNull($congeTt->getDocumentPath());
    }

    public function testSetAndGetOcrVerified(): void
    {
        $congeTt = new CongeTt();
        $congeTt->setOcrVerified(true);

        $this->assertTrue($congeTt->getOcrVerified());
    }

    public function testOcrVerifiedCanBeFalse(): void
    {
        $congeTt = new CongeTt();
        $congeTt->setOcrVerified(false);

        $this->assertFalse($congeTt->getOcrVerified());
    }

    public function testOcrVerifiedCanBeNull(): void
    {
        $congeTt = new CongeTt();
        $congeTt->setOcrVerified(null);

        $this->assertNull($congeTt->getOcrVerified());
    }

    public function testFluentInterface(): void
    {
        $congeTt = new CongeTt();
        $employe = new Employe();

        $result = $congeTt
            ->setTypeConge('Congé maladie')
            ->setDateDebut(new \DateTimeImmutable('2024-01-10'))
            ->setDateFin(new \DateTimeImmutable('2024-01-20'))
            ->setStatut('En attente')
            ->setDescription('Certificat médical joint.')
            ->setEmploye($employe)
            ->setDocumentPath('uploads/certificats/cert_abc.pdf')
            ->setOcrVerified(true);

        $this->assertSame($congeTt, $result);
    }

    public function testDifferentStatutValues(): void
    {
        $congeTt = new CongeTt();

        $statuts = ['En attente', 'Accepté', 'Refusé', 'approuvé', 'refusé'];

        foreach ($statuts as $statut) {
            $congeTt->setStatut($statut);
            $this->assertEquals($statut, $congeTt->getStatut());
        }
    }

    public function testDifferentTypeCongeValues(): void
    {
        $congeTt = new CongeTt();

        $types = ['Congé annuel', 'Congé maladie', 'Congé sans solde', 'Télétravail'];

        foreach ($types as $type) {
            $congeTt->setTypeConge($type);
            $this->assertEquals($type, $congeTt->getTypeConge());
        }
    }

    public function testNullableFields(): void
    {
        $congeTt = new CongeTt();

        $this->assertNull($congeTt->getId());
        $this->assertNull($congeTt->getDescription());
        $this->assertNull($congeTt->getDocumentPath());
        $this->assertNull($congeTt->getOcrVerified());
    }
}
