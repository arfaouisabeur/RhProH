<?php

namespace App\Tests\Entity;

use App\Entity\DemandeService;
use App\Entity\Employe;
use App\Entity\TypeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DemandeServiceTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testDemandeServiceCanBeCreated(): void
    {
        $demande = new DemandeService();
        $this->assertInstanceOf(DemandeService::class, $demande);
        $this->assertNull($demande->getId());
    }

    public function testSetAndGetTitre(): void
    {
        $demande = new DemandeService();
        $demande->setTitre('Demande de matériel informatique');

        $this->assertEquals('Demande de matériel informatique', $demande->getTitre());
    }

    public function testSetAndGetDescription(): void
    {
        $demande = new DemandeService();
        $demande->setDescription('Besoin d\'un second écran pour le télétravail.');

        $this->assertEquals('Besoin d\'un second écran pour le télétravail.', $demande->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $demande = new DemandeService();
        $demande->setDescription(null);

        $this->assertNull($demande->getDescription());
    }

    public function testSetAndGetDateDemande(): void
    {
        $demande = new DemandeService();
        $demande->setDateDemande('2024-05-15');

        $this->assertEquals('2024-05-15', $demande->getDateDemande());
    }

    public function testSetAndGetStatut(): void
    {
        $demande = new DemandeService();
        $demande->setStatut('En attente');

        $this->assertEquals('En attente', $demande->getStatut());
    }

    public function testSetAndGetEmploye(): void
    {
        $demande = new DemandeService();
        $employe = new Employe();

        $demande->setEmploye($employe);

        $this->assertSame($employe, $demande->getEmploye());
    }

    public function testEmployeCanBeNull(): void
    {
        $demande = new DemandeService();
        $demande->setEmploye(null);

        $this->assertNull($demande->getEmploye());
    }

    public function testSetAndGetType(): void
    {
        $demande = new DemandeService();
        $type = new TypeService();

        $demande->setType($type);

        $this->assertSame($type, $demande->getType());
    }

    public function testTypeCanBeNull(): void
    {
        $demande = new DemandeService();
        $this->expectException(\TypeError::class);
        $demande->setType(null);
    }

    public function testSetAndGetEtapeWorkflow(): void
    {
        $demande = new DemandeService();
        $demande->setEtapeWorkflow('validation_rh');

        $this->assertEquals('validation_rh', $demande->getEtapeWorkflow());
    }

    public function testEtapeWorkflowCanBeNull(): void
    {
        $demande = new DemandeService();
        $demande->setEtapeWorkflow(null);

        $this->assertNull($demande->getEtapeWorkflow());
    }

    public function testSetAndGetDateDerniereEtape(): void
    {
        $demande = new DemandeService();
        $demande->setDateDerniereEtape('2024-05-20');

        $this->assertEquals('2024-05-20', $demande->getDateDerniereEtape());
    }

    public function testDateDerniereEtapeCanBeNull(): void
    {
        $demande = new DemandeService();
        $demande->setDateDerniereEtape(null);

        $this->assertNull($demande->getDateDerniereEtape());
    }

    public function testSetAndGetPriorite(): void
    {
        $demande = new DemandeService();
        $demande->setPriorite('haute');

        $this->assertEquals('haute', $demande->getPriorite());
    }

    public function testPrioriteCanBeNull(): void
    {
        $demande = new DemandeService();
        $demande->setPriorite(null);

        $this->assertNull($demande->getPriorite());
    }

    public function testSetAndGetDeadlineReponse(): void
    {
        $demande = new DemandeService();
        $demande->setDeadlineReponse('2024-06-01');

        $this->assertEquals('2024-06-01', $demande->getDeadlineReponse());
    }

    public function testDeadlineReponseCanBeNull(): void
    {
        $demande = new DemandeService();
        $demande->setDeadlineReponse(null);

        $this->assertNull($demande->getDeadlineReponse());
    }

    public function testSetAndGetSlaDepasse(): void
    {
        $demande = new DemandeService();
        $demande->setSlaDepasse('oui');

        $this->assertEquals('oui', $demande->getSlaDepasse());
    }

    public function testSlaDepasseCanBeNull(): void
    {
        $demande = new DemandeService();
        $demande->setSlaDepasse(null);

        $this->assertNull($demande->getSlaDepasse());
    }

    public function testSetAndGetPdfPath(): void
    {
        $demande = new DemandeService();
        $demande->setPdfPath('uploads/demandes/demande_123.pdf');

        $this->assertEquals('uploads/demandes/demande_123.pdf', $demande->getPdfPath());
    }

    public function testPdfPathCanBeNull(): void
    {
        $demande = new DemandeService();
        $demande->setPdfPath(null);

        $this->assertNull($demande->getPdfPath());
    }

    public function testFluentInterface(): void
    {
        $demande = new DemandeService();
        $employe = new Employe();
        $type    = new TypeService();

        $result = $demande
            ->setTitre('Formation professionnelle')
            ->setDescription('Demande de formation en développement web.')
            ->setDateDemande('2024-04-01')
            ->setStatut('En attente')
            ->setEmploye($employe)
            ->setType($type)
            ->setEtapeWorkflow('soumis')
            ->setPriorite('normale');

        $this->assertSame($demande, $result);
    }

    public function testDifferentStatutValues(): void
    {
        $demande = new DemandeService();

        $statuts = ['En attente', 'Accepté', 'Refusé', 'approuvé', 'refusé'];

        foreach ($statuts as $statut) {
            $demande->setStatut($statut);
            $this->assertEquals($statut, $demande->getStatut());
        }
    }

    public function testNullableFields(): void
    {
        $demande = new DemandeService();

        $this->assertNull($demande->getId());
        $this->assertNull($demande->getDescription());
        $this->assertNull($demande->getEmploye());
        $this->assertNull($demande->getEtapeWorkflow());
        $this->assertNull($demande->getDateDerniereEtape());
        $this->assertNull($demande->getPriorite());
        $this->assertNull($demande->getDeadlineReponse());
        $this->assertNull($demande->getSlaDepasse());
        $this->assertNull($demande->getPdfPath());
    }
}
