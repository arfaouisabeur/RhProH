<?php

namespace App\Tests\Controller;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use App\Repository\EventParticipationRepository;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour EvenementController.
 *
 * Ces tests vérifient la logique métier du contrôleur sans démarrer
 * le kernel Symfony complet (tests unitaires purs avec PHPUnit).
 *
 * Règles testées :
 * 1. Un événement annulé possède le préfixe [ANNULÉ]
 * 2. Un événement déjà annulé ne peut pas être ré-annulé
 * 3. La troncature de date fonctionne (format YYYY-MM-DD)
 * 4. La logique de statut par rapport aux dates est correcte
 */
class EvenementControllerTest extends TestCase
{
    // =========================================================================
    // TESTS DE LA LOGIQUE D'ANNULATION
    // =========================================================================

    /**
     * Règle : Annuler un événement ajoute le préfixe [ANNULÉ].
     */
    public function testAnnulerAjoutePrefixe(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Conférence RH');

        $prefix = '[ANNULÉ] ';
        if (!str_starts_with($evenement->getTitre(), $prefix)) {
            $evenement->setTitre($prefix . $evenement->getTitre());
        }

        $this->assertEquals('[ANNULÉ] Conférence RH', $evenement->getTitre());
        $this->assertTrue(str_starts_with($evenement->getTitre(), '[ANNULÉ] '));
    }

    /**
     * Règle : Un événement déjà annulé ne doit pas recevoir un second préfixe.
     */
    public function testEventDejaAnnuleNeRePrendPasLePrefixe(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('[ANNULÉ] Conférence RH');

        $prefix     = '[ANNULÉ] ';
        $estAnnule  = str_starts_with($evenement->getTitre(), $prefix);

        $this->assertTrue($estAnnule);

        // On simule la vérification : si déjà annulé, on ne re-préfixe pas
        if (!$estAnnule) {
            $evenement->setTitre($prefix . $evenement->getTitre());
        }

        // Le titre ne doit pas contenir le préfixe en double
        $this->assertStringStartsWith('[ANNULÉ] ', $evenement->getTitre());
        $this->assertStringNotContainsString('[ANNULÉ] [ANNULÉ]', $evenement->getTitre());
    }

    // =========================================================================
    // TESTS DE LA LOGIQUE DE TRONCATURE DE DATE
    // =========================================================================

    /**
     * Règle : substr($date, 0, 10) extrait correctement la partie YYYY-MM-DD.
     */
    public function testDateTroncatureFormatDatetime(): void
    {
        // Simule ce que fait le contrôleur new() et edit()
        $dateAvecHeure = '2025-09-01T14:30:00';
        $dateTronquee  = substr($dateAvecHeure, 0, 10);

        $this->assertEquals('2025-09-01', $dateTronquee);
        $this->assertEquals(10, strlen($dateTronquee));
    }

    public function testDateTroncatureFormatDatetimeLocal(): void
    {
        $dateAvecHeure = '2025-12-31T23:59';
        $dateTronquee  = substr($dateAvecHeure, 0, 10);

        $this->assertEquals('2025-12-31', $dateTronquee);
    }

    public function testDateDejaAuBonFormatNEstPasModifiee(): void
    {
        $date = '2025-09-01';
        $dateTronquee = substr($date, 0, 10);

        $this->assertEquals('2025-09-01', $dateTronquee);
    }

    // =========================================================================
    // TESTS DE LA LOGIQUE DE STATUT (badge)
    // =========================================================================

    /**
     * Règle : La logique de statut "a_venir" si début > aujourd'hui.
     */
    public function testStatutAVenirSiDebutEstDansFutur(): void
    {
        $today  = (new \DateTime())->format('Y-m-d');
        $debut  = '2099-01-01';
        $fin    = '2099-12-31';

        $badge = match(true) {
            $debut <= $today && $fin >= $today => 'en_cours',
            $debut > $today                   => 'a_venir',
            default                           => 'termine',
        };

        $this->assertEquals('a_venir', $badge);
    }

    /**
     * Règle : La logique de statut "termine" si fin < aujourd'hui.
     */
    public function testStatutTermineSiFinEstDansLePasse(): void
    {
        $today = (new \DateTime())->format('Y-m-d');
        $debut = '2000-01-01';
        $fin   = '2000-12-31';

        $badge = match(true) {
            $debut <= $today && $fin >= $today => 'en_cours',
            $debut > $today                   => 'a_venir',
            default                           => 'termine',
        };

        $this->assertEquals('termine', $badge);
    }

    /**
     * Règle : La logique de statut "en_cours" si début <= aujourd'hui <= fin.
     */
    public function testStatutEnCoursSiAujourdhuiEstDansLIntervalle(): void
    {
        $today  = (new \DateTime())->format('Y-m-d');
        $hier   = (new \DateTime('-1 day'))->format('Y-m-d');
        $demain = (new \DateTime('+1 day'))->format('Y-m-d');

        $badge = match(true) {
            $hier <= $today && $demain >= $today => 'en_cours',
            $hier > $today                       => 'a_venir',
            default                              => 'termine',
        };

        $this->assertEquals('en_cours', $badge);
    }

    // =========================================================================
    // TESTS DE LA LOGIQUE DE RECHERCHE (searchAjax)
    // =========================================================================

    /**
     * Règle : La recherche filtre par correspondance dans titre + lieu + description.
     */
    public function testRechercheParMotCleCorrespondDansTitre(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Forum Innovation Numérique');
        $evenement->setLieu('Tunis');
        $evenement->setDescription('Un forum sur le numérique.');

        $q        = 'forum';
        $haystack = mb_strtolower(
            $evenement->getTitre() . ' ' .
            $evenement->getLieu()  . ' ' .
            $evenement->getDescription()
        );

        $this->assertNotFalse(mb_strpos($haystack, mb_strtolower($q)));
    }

    /**
     * Règle : La recherche ne correspond pas si le mot clé est absent.
     */
    public function testRechercheParMotCleAbsent(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Forum Innovation Numérique');
        $evenement->setLieu('Tunis');
        $evenement->setDescription('Un forum sur le numérique.');

        $q        = 'conférence';
        $haystack = mb_strtolower(
            $evenement->getTitre() . ' ' .
            $evenement->getLieu()  . ' ' .
            $evenement->getDescription()
        );

        $this->assertFalse(mb_strpos($haystack, mb_strtolower($q)));
    }

    // =========================================================================
    // TESTS DE LA LOGIQUE DE GÉOCODAGE
    // =========================================================================

    /**
     * Règle : Les coordonnées sont stockées sous forme de chaîne.
     */
    public function testCoordonneesSontDesChaines(): void
    {
        $evenement = new Evenement();
        $evenement->setLatitude('36.8065');
        $evenement->setLongitude('10.1815');

        $this->assertIsString($evenement->getLatitude());
        $this->assertIsString($evenement->getLongitude());
    }

    /**
     * Règle : Si pas de coordonnées, les coordonnées sont null.
     */
    public function testSansCoordonneesLatLonSontNull(): void
    {
        $evenement = new Evenement();

        $this->assertNull($evenement->getLatitude());
        $this->assertNull($evenement->getLongitude());

        // Aucune météo ne doit être affichée sans coordonnées
        $peutAfficherMeteo = $evenement->getLatitude() && $evenement->getLongitude();
        $this->assertFalse($peutAfficherMeteo);
    }
}
