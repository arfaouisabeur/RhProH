<?php

namespace App\Tests\Entity;

use App\Entity\Reponse;
use App\Entity\CongeTt;
use App\Entity\DemandeService;
use App\Entity\Employe;
use App\Entity\RH;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReponseTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testReponseCanBeCreated(): void
    {
        $reponse = new Reponse();
        $this->assertInstanceOf(Reponse::class, $reponse);
        $this->assertNull($reponse->getId());
    }

    public function testSetAndGetDecision(): void
    {
        $reponse = new Reponse();
        $reponse->setDecision('approuvé');

        $this->assertEquals('approuvé', $reponse->getDecision());
    }

    public function testSetAndGetCommentaire(): void
    {
        $reponse = new Reponse();
        $reponse->setCommentaire('Demande acceptée sous réserve de justificatif.');

        $this->assertEquals('Demande acceptée sous réserve de justificatif.', $reponse->getCommentaire());
    }

    public function testCommentaireCanBeNull(): void
    {
        $reponse = new Reponse();
        $reponse->setCommentaire(null);

        $this->assertNull($reponse->getCommentaire());
    }

    public function testSetAndGetRh(): void
    {
        $reponse = new Reponse();
        $rh = new RH();

        $reponse->setRh($rh);

        $this->assertSame($rh, $reponse->getRh());
    }

    public function testRhCanBeNull(): void
    {
        $reponse = new Reponse();
        $reponse->setRh(null);

        $this->assertNull($reponse->getRh());
    }

    public function testSetAndGetEmploye(): void
    {
        $reponse = new Reponse();
        $employe = new Employe();

        $reponse->setEmploye($employe);

        $this->assertSame($employe, $reponse->getEmploye());
    }

    public function testEmployeCanBeNull(): void
    {
        $reponse = new Reponse();
        $reponse->setEmploye(null);

        $this->assertNull($reponse->getEmploye());
    }

    public function testSetAndGetCongeTt(): void
    {
        $reponse = new Reponse();
        $congeTt = new CongeTt();

        $reponse->setCongeTt($congeTt);

        $this->assertSame($congeTt, $reponse->getCongeTt());
    }

    public function testCongeTtCanBeNull(): void
    {
        $reponse = new Reponse();
        $reponse->setCongeTt(null);

        $this->assertNull($reponse->getCongeTt());
    }

    public function testSetAndGetDemandeService(): void
    {
        $reponse = new Reponse();
        $demande = new DemandeService();

        $reponse->setDemandeService($demande);

        $this->assertSame($demande, $reponse->getDemandeService());
    }

    public function testDemandeServiceCanBeNull(): void
    {
        $reponse = new Reponse();
        $reponse->setDemandeService(null);

        $this->assertNull($reponse->getDemandeService());
    }

    public function testFluentInterface(): void
    {
        $reponse = new Reponse();
        $rh      = new RH();
        $employe = new Employe();
        $conge   = new CongeTt();

        $result = $reponse
            ->setDecision('approuvé')
            ->setCommentaire('Accord donné.')
            ->setRh($rh)
            ->setEmploye($employe)
            ->setCongeTt($conge)
            ->setDemandeService(null);

        $this->assertSame($reponse, $result);
    }

    public function testDifferentDecisionValues(): void
    {
        $reponse = new Reponse();

        $decisions = ['approuvé', 'refusé', 'Accepté', 'Refusé'];

        foreach ($decisions as $decision) {
            $reponse->setDecision($decision);
            $this->assertEquals($decision, $reponse->getDecision());
        }
    }

    public function testNullableFields(): void
    {
        $reponse = new Reponse();

        $this->assertNull($reponse->getId());
        $this->assertNull($reponse->getCommentaire());
        $this->assertNull($reponse->getRh());
        $this->assertNull($reponse->getEmploye());
        $this->assertNull($reponse->getCongeTt());
        $this->assertNull($reponse->getDemandeService());
    }

    public function testReponseCanBeLinkedToCongeTtOnly(): void
    {
        $reponse = new Reponse();
        $congeTt = new CongeTt();

        $reponse->setDecision('refusé');
        $reponse->setCongeTt($congeTt);
        $reponse->setDemandeService(null);

        $this->assertSame($congeTt, $reponse->getCongeTt());
        $this->assertNull($reponse->getDemandeService());
    }

    public function testReponseCanBeLinkedToDemandeServiceOnly(): void
    {
        $reponse = new Reponse();
        $demande = new DemandeService();

        $reponse->setDecision('approuvé');
        $reponse->setCongeTt(null);
        $reponse->setDemandeService($demande);

        $this->assertNull($reponse->getCongeTt());
        $this->assertSame($demande, $reponse->getDemandeService());
    }
}
