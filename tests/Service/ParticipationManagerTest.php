<?php

namespace App\Tests\Service;

use App\Entity\Employe;
use App\Entity\Evenement;
use App\Entity\EventParticipation;
use App\Service\ParticipationManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le service ParticipationManager.
 *
 * Vérifie les règles métier :
 * 1. Le statut est obligatoire
 * 2. Le statut doit être une valeur valide (en_attente, accepte, refuse)
 * 3. La date d'inscription est obligatoire
 * 4. La date d'inscription ne peut pas être dans le futur
 * 5. Les méthodes accepter() et refuser() fonctionnent correctement
 * 6. Les méthodes isEnAttente() et isAcceptee() retournent les bons résultats
 */
class ParticipationManagerTest extends TestCase
{
    private ParticipationManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ParticipationManager();
    }

    // =========================================================================
    // HELPER : crée une participation valide
    // =========================================================================

    private function createParticipationValide(): EventParticipation
    {
        $evenement = new Evenement();
        $evenement->setTitre('Conférence RH');
        $employe   = new Employe();

        $participation = new EventParticipation();
        $participation->setStatut('en_attente');
        $participation->setDateInscription('2025-05-01');
        $participation->setEvenement($evenement);
        $participation->setEmploye($employe);

        return $participation;
    }

    // =========================================================================
    // TESTS DE VALIDATION - CAS VALIDES
    // =========================================================================

    public function testValidationParticipationValide(): void
    {
        $participation = $this->createParticipationValide();

        $this->assertTrue($this->manager->validate($participation));
    }

    public function testValidationParticipationAvecStatutAccepte(): void
    {
        $participation = $this->createParticipationValide();
        $participation->setStatut('accepte');

        $this->assertTrue($this->manager->validate($participation));
    }

    public function testValidationParticipationAvecStatutRefuse(): void
    {
        $participation = $this->createParticipationValide();
        $participation->setStatut('refuse');

        $this->assertTrue($this->manager->validate($participation));
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 1 : Statut obligatoire
    // =========================================================================

    public function testValidationSansStatutLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut de la participation est obligatoire');

        $participation = $this->createParticipationValide();
        $participation->setStatut('');

        $this->manager->validate($participation);
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 2 : Statut valide
    // =========================================================================

    public function testValidationStatutInvalideLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut doit être l\'une des valeurs suivantes');

        $participation = $this->createParticipationValide();
        $participation->setStatut('invalide');

        $this->manager->validate($participation);
    }

    public function testValidationStatutApprouveLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $participation = $this->createParticipationValide();
        $participation->setStatut('approuve'); // pas dans les statuts valides

        $this->manager->validate($participation);
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 3 : Date inscription obligatoire
    // =========================================================================

    public function testValidationSansDateInscriptionLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date d\'inscription est obligatoire');

        $participation = $this->createParticipationValide();
        $participation->setDateInscription('');

        $this->manager->validate($participation);
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 4 : Date pas dans le futur
    // =========================================================================

    public function testValidationDateInscriptionDansFuturLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date d\'inscription ne peut pas être dans le futur');

        $participation = $this->createParticipationValide();
        $participation->setDateInscription('2099-12-31'); // date dans le futur

        $this->manager->validate($participation);
    }

    public function testValidationDateAujourdhuiEstValide(): void
    {
        $participation = $this->createParticipationValide();
        $today = (new \DateTime())->format('Y-m-d');
        $participation->setDateInscription($today);

        $this->assertTrue($this->manager->validate($participation));
    }

    // =========================================================================
    // TESTS DE accepter()
    // =========================================================================

    public function testAccepterParticipationEnAttente(): void
    {
        $participation = $this->createParticipationValide();
        // statut initial : en_attente

        $result = $this->manager->accepter($participation);

        $this->assertEquals('accepte', $result->getStatut());
        $this->assertSame($participation, $result);
    }

    public function testAccepterParticipationDejaAccepteeLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cette participation est déjà acceptée');

        $participation = $this->createParticipationValide();
        $participation->setStatut('accepte');

        $this->manager->accepter($participation);
    }

    // =========================================================================
    // TESTS DE refuser()
    // =========================================================================

    public function testRefuserParticipationEnAttente(): void
    {
        $participation = $this->createParticipationValide();

        $result = $this->manager->refuser($participation);

        $this->assertEquals('refuse', $result->getStatut());
        $this->assertSame($participation, $result);
    }

    public function testRefuserParticipationDejaRefuseeLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cette participation est déjà refusée');

        $participation = $this->createParticipationValide();
        $participation->setStatut('refuse');

        $this->manager->refuser($participation);
    }

    // =========================================================================
    // TESTS DE isEnAttente()
    // =========================================================================

    public function testIsEnAttenteRetourneTruePourStatutEnAttente(): void
    {
        $participation = $this->createParticipationValide();
        // statut : en_attente

        $this->assertTrue($this->manager->isEnAttente($participation));
    }

    public function testIsEnAttenteRetourneFalsePourStatutAccepte(): void
    {
        $participation = $this->createParticipationValide();
        $participation->setStatut('accepte');

        $this->assertFalse($this->manager->isEnAttente($participation));
    }

    // =========================================================================
    // TESTS DE isAcceptee()
    // =========================================================================

    public function testIsAccepteeRetourneTruePourStatutAccepte(): void
    {
        $participation = $this->createParticipationValide();
        $participation->setStatut('accepte');

        $this->assertTrue($this->manager->isAcceptee($participation));
    }

    public function testIsAccepteeRetourneFalsePourStatutEnAttente(): void
    {
        $participation = $this->createParticipationValide();
        // statut : en_attente

        $this->assertFalse($this->manager->isAcceptee($participation));
    }

    public function testIsAccepteeRetourneFalsePourStatutRefuse(): void
    {
        $participation = $this->createParticipationValide();
        $participation->setStatut('refuse');

        $this->assertFalse($this->manager->isAcceptee($participation));
    }

    // =========================================================================
    // TESTS DE CONSTANTE
    // =========================================================================

    public function testStatutsValidesContientLesValeursCles(): void
    {
        $this->assertContains('en_attente', ParticipationManager::STATUTS_VALIDES);
        $this->assertContains('accepte',    ParticipationManager::STATUTS_VALIDES);
        $this->assertContains('refuse',     ParticipationManager::STATUTS_VALIDES);
        $this->assertCount(3,               ParticipationManager::STATUTS_VALIDES);
    }
}
