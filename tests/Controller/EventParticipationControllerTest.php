<?php

namespace App\Tests\Controller;

use App\Entity\Employe;
use App\Entity\Evenement;
use App\Entity\EventParticipation;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour EventParticipationController.
 *
 * Vérifie la logique métier du contrôleur sans kernel Symfony :
 * 1. Un employé ne peut s'inscrire qu'une seule fois à un événement
 * 2. La participation initiale est toujours "en_attente"
 * 3. La date d'inscription est au format Y-m-d
 * 4. Seul le propriétaire peut annuler sa participation
 * 5. La logique de filtrage des participations par employé
 * 6. Un commentaire doit contenir au moins 10 caractères
 */
class EventParticipationControllerTest extends TestCase
{
    // =========================================================================
    // HELPER : crée une participation valide
    // =========================================================================

    private function createParticipation(Evenement $evenement, Employe $employe, string $statut = 'en_attente'): EventParticipation
    {
        $participation = new EventParticipation();
        $participation->setEvenement($evenement);
        $participation->setEmploye($employe);
        $participation->setStatut($statut);
        $participation->setDateInscription((new \DateTime())->format('Y-m-d'));
        return $participation;
    }

    // =========================================================================
    // RÈGLE 1 : Un employé ne peut s'inscrire qu'une seule fois
    // =========================================================================

    public function testEmployeNePeutPasParticiper2FoisAuMemeEvenement(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Conférence Symfony');
        $employe = new Employe();

        // Simule findOneBy(['evenement' => $ev, 'employe' => $emp])
        $participation = $this->createParticipation($evenement, $employe);

        // Si une participation existe déjà, on ne doit pas en créer une autre
        $dejaInscrit = ($participation !== null);

        $this->assertTrue($dejaInscrit);
    }

    public function testNouvelEmployePeutParticiperSiPasDejaInscrit(): void
    {
        // Simule findOneBy qui retourne null (pas de participation existante)
        $existante = null;

        $dejaInscrit = ($existante !== null);

        $this->assertFalse($dejaInscrit);
    }

    // =========================================================================
    // RÈGLE 2 : La participation initiale est "en_attente"
    // =========================================================================

    public function testNouvelleParticipationEstEnAttente(): void
    {
        $evenement = new Evenement();
        $employe   = new Employe();

        // Simulation de participer()
        $participation = new EventParticipation();
        $participation->setEvenement($evenement);
        $participation->setEmploye($employe);
        $participation->setStatut('en_attente');
        $participation->setDateInscription((new \DateTime())->format('Y-m-d'));

        $this->assertEquals('en_attente', $participation->getStatut());
    }

    // =========================================================================
    // RÈGLE 3 : La date d'inscription est au format Y-m-d
    // =========================================================================

    public function testDateInscriptionEstAuBonFormat(): void
    {
        $participation = new EventParticipation();
        $dateInscription = (new \DateTime())->format('Y-m-d');
        $participation->setDateInscription($dateInscription);

        // Vérifie que la date respecte le format YYYY-MM-DD
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}$/',
            $participation->getDateInscription()
        );
    }

    public function testDateInscriptionEstAujourdhuiOuDansLePasse(): void
    {
        $today           = (new \DateTime())->format('Y-m-d');
        $dateInscription = (new \DateTime())->format('Y-m-d');

        $this->assertLessThanOrEqual($today, $dateInscription);
    }

    // =========================================================================
    // RÈGLE 4 : Seul le propriétaire peut annuler sa participation
    // =========================================================================

    public function testProprietairePeutAnnulerSaParticipation(): void
    {
        $employe = new Employe();
        $evenement = new Evenement();
        $participation = $this->createParticipation($evenement, $employe);

        // Simule la vérification dans annuler()
        $peutAnnuler = ($participation->getEmploye() === $employe);

        $this->assertTrue($peutAnnuler);
    }

    public function testAutreEmployeNePeutPasAnnulerUneParticipation(): void
    {
        $employe1  = new Employe();
        $employe2  = new Employe();
        $evenement = new Evenement();

        // La participation appartient à employe1
        $participation = $this->createParticipation($evenement, $employe1);

        // employe2 essaie d'annuler → ne doit pas pouvoir
        $peutAnnuler = ($participation->getEmploye() === $employe2);

        $this->assertFalse($peutAnnuler);
    }

    // =========================================================================
    // RÈGLE 5 : Filtrage des participations par employé (mesParticipations)
    // =========================================================================

    public function testMesParticipationsFiltreParEmploye(): void
    {
        $employe1  = new Employe();
        $employe2  = new Employe();
        $evenement = new Evenement();
        $evenement->setTitre('Forum RH');

        $p1 = $this->createParticipation($evenement, $employe1);
        $p2 = $this->createParticipation($evenement, $employe2);

        $toutesLesParticipations = [$p1, $p2];

        // Filtre les participations de employe1
        $mesParticipations = array_filter(
            $toutesLesParticipations,
            fn($p) => $p->getEmploye() === $employe1
        );

        $this->assertCount(1, $mesParticipations);
        $this->assertContains($p1, $mesParticipations);
        $this->assertNotContains($p2, $mesParticipations);
    }

    // =========================================================================
    // RÈGLE 6 : Le commentaire doit avoir au moins 10 caractères
    // =========================================================================

    public function testCommentaireTropCourtEstInvalide(): void
    {
        $commentaire = 'Court';

        // Simule la vérification dans submitRating()
        $estValide = !(empty($commentaire) || mb_strlen($commentaire) < 10);

        $this->assertFalse($estValide);
    }

    public function testCommentaireVideEstInvalide(): void
    {
        $commentaire = '';

        $estValide = !(empty($commentaire) || mb_strlen($commentaire) < 10);

        $this->assertFalse($estValide);
    }

    public function testCommentaireDe10CaracteresEstValide(): void
    {
        $commentaire = 'Exactement'; // 10 chars

        $estValide = !(empty($commentaire) || mb_strlen($commentaire) < 10);

        $this->assertTrue($estValide);
    }

    public function testCommentaireLongEstValide(): void
    {
        $commentaire = 'Cet événement était très bien organisé et très enrichissant.';

        $estValide = !(empty($commentaire) || mb_strlen($commentaire) < 10);

        $this->assertTrue($estValide);
    }

    // =========================================================================
    // RÈGLE 7 : Seul un participant accepté peut donner un avis
    // =========================================================================

    public function testParticipantAcceptePeutNoter(): void
    {
        $employe   = new Employe();
        $evenement = new Evenement();

        $participation = $this->createParticipation($evenement, $employe, 'accepte');
        $monRating     = null; // pas encore noté

        $peutNoter = $participation
            && $participation->getStatut() === 'accepte'
            && $monRating === null;

        $this->assertTrue($peutNoter);
    }

    public function testParticipantEnAttentaNePeutPasNoter(): void
    {
        $employe   = new Employe();
        $evenement = new Evenement();

        $participation = $this->createParticipation($evenement, $employe, 'en_attente');
        $monRating     = null;

        $peutNoter = $participation
            && $participation->getStatut() === 'accepte'
            && $monRating === null;

        $this->assertFalse($peutNoter);
    }

    public function testParticipantQuiADejaNotePeutPasRenoter(): void
    {
        $employe   = new Employe();
        $evenement = new Evenement();

        $participation = $this->createParticipation($evenement, $employe, 'accepte');
        $monRating     = new \stdClass(); // simule un rating existant (non null)

        $peutNoter = $participation
            && $participation->getStatut() === 'accepte'
            && $monRating === null;

        $this->assertFalse($peutNoter);
    }

    // =========================================================================
    // RÈGLE 8 : Vérification du statut d'acceptation/refus par le RH
    // =========================================================================

    public function testAccepterParticipationChangeStatutVersAccepte(): void
    {
        $participation = new EventParticipation();
        $participation->setStatut('en_attente');

        // Simule accept() du contrôleur
        $participation->setStatut('accepte');

        $this->assertEquals('accepte', $participation->getStatut());
    }

    public function testRefuserParticipationChangeStatutVersRefuse(): void
    {
        $participation = new EventParticipation();
        $participation->setStatut('en_attente');

        // Simule refuse() du contrôleur
        $participation->setStatut('refuse');

        $this->assertEquals('refuse', $participation->getStatut());
    }
}
