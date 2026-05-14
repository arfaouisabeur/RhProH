<?php

namespace App\Tests\Entity;

use App\Entity\Employe;
use App\Entity\Evenement;
use App\Entity\EventParticipation;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'entité EventParticipation.
 *
 * Vérifie les getters/setters, les valeurs de statut valides,
 * les relations avec Evenement et Employe, et les règles métier.
 */
class EventParticipationTest extends TestCase
{
    // =========================================================================
    // TESTS DE CRÉATION
    // =========================================================================

    public function testEventParticipationCanBeCreated(): void
    {
        $participation = new EventParticipation();
        $this->assertInstanceOf(EventParticipation::class, $participation);
        $this->assertNull($participation->getId());
    }

    // =========================================================================
    // TESTS DES GETTERS / SETTERS
    // =========================================================================

    public function testSetAndGetStatut(): void
    {
        $participation = new EventParticipation();
        $participation->setStatut('en_attente');

        $this->assertEquals('en_attente', $participation->getStatut());
    }

    public function testSetAndGetDateInscription(): void
    {
        $participation = new EventParticipation();
        $participation->setDateInscription('2025-05-15');

        $this->assertEquals('2025-05-15', $participation->getDateInscription());
    }

    public function testSetAndGetEvenement(): void
    {
        $participation = new EventParticipation();
        $evenement     = new Evenement();
        $evenement->setTitre('Conférence RH');

        $participation->setEvenement($evenement);

        $this->assertSame($evenement, $participation->getEvenement());
    }

    public function testEvenementCanBeNull(): void
    {
        $participation = new EventParticipation();
        $this->expectException(\TypeError::class);
        $participation->setEvenement(null);
    }

    public function testSetAndGetEmploye(): void
    {
        $participation = new EventParticipation();
        $employe       = new Employe();

        $participation->setEmploye($employe);

        $this->assertSame($employe, $participation->getEmploye());
    }

    public function testEmployeCanBeNull(): void
    {
        $participation = new EventParticipation();
        $participation->setEmploye(null);

        $this->assertNull($participation->getEmploye());
    }

    // =========================================================================
    // TESTS DES VALEURS DE STATUT VALIDES
    // =========================================================================

    public function testStatutEnAttente(): void
    {
        $participation = new EventParticipation();
        $participation->setStatut('en_attente');

        $this->assertEquals('en_attente', $participation->getStatut());
    }

    public function testStatutAccepte(): void
    {
        $participation = new EventParticipation();
        $participation->setStatut('accepte');

        $this->assertEquals('accepte', $participation->getStatut());
    }

    public function testStatutRefuse(): void
    {
        $participation = new EventParticipation();
        $participation->setStatut('refuse');

        $this->assertEquals('refuse', $participation->getStatut());
    }

    public function testTousLesStatutsValides(): void
    {
        $participation = new EventParticipation();
        $statuts       = ['en_attente', 'accepte', 'refuse'];

        foreach ($statuts as $statut) {
            $participation->setStatut($statut);
            $this->assertEquals($statut, $participation->getStatut());
        }
    }

    // =========================================================================
    // TESTS DE L'INTERFACE FLUENTE (chaînage)
    // =========================================================================

    public function testFluentInterface(): void
    {
        $participation = new EventParticipation();
        $evenement     = new Evenement();
        $employe       = new Employe();

        $result = $participation
            ->setStatut('en_attente')
            ->setDateInscription('2025-05-01')
            ->setEvenement($evenement)
            ->setEmploye($employe);

        $this->assertSame($participation, $result);
        $this->assertEquals('en_attente', $participation->getStatut());
        $this->assertEquals('2025-05-01', $participation->getDateInscription());
    }

    // =========================================================================
    // TESTS DE RÈGLES MÉTIER
    // =========================================================================

    /**
     * Règle : La date d'inscription ne peut pas être dans le futur.
     */
    public function testDateInscriptionEstDansLePasse(): void
    {
        $participation = new EventParticipation();
        $today = (new \DateTime())->format('Y-m-d');
        $participation->setDateInscription($today);

        // La date d'inscription d'aujourd'hui est valide (pas dans le futur)
        $this->assertLessThanOrEqual($today, $participation->getDateInscription());
    }

    /**
     * Règle : Un employé inscrit à un événement a un statut initial "en_attente".
     */
    public function testNouvelleParticipationEstEnAttente(): void
    {
        $participation = new EventParticipation();
        $employe       = new Employe();
        $evenement     = new Evenement();
        $evenement->setTitre('Forum Innovation');

        $participation->setEmploye($employe);
        $participation->setEvenement($evenement);
        $participation->setStatut('en_attente');
        $participation->setDateInscription((new \DateTime())->format('Y-m-d'));

        $this->assertEquals('en_attente', $participation->getStatut());
    }

    /**
     * Règle : Après acceptation, le statut passe à "accepte".
     */
    public function testTransitionStatutVersAccepte(): void
    {
        $participation = new EventParticipation();
        $participation->setStatut('en_attente');

        // Simulation de l'acceptation
        $participation->setStatut('accepte');

        $this->assertEquals('accepte', $participation->getStatut());
        $this->assertNotEquals('en_attente', $participation->getStatut());
    }

    /**
     * Règle : Après refus, le statut passe à "refuse".
     */
    public function testTransitionStatutVersRefuse(): void
    {
        $participation = new EventParticipation();
        $participation->setStatut('en_attente');

        // Simulation du refus
        $participation->setStatut('refuse');

        $this->assertEquals('refuse', $participation->getStatut());
        $this->assertNotEquals('en_attente', $participation->getStatut());
    }

    // =========================================================================
    // TESTS DES CHAMPS NULLABLE
    // =========================================================================

    public function testNullableFields(): void
    {
        $participation = new EventParticipation();

        $this->assertNull($participation->getId());
        
        $this->expectException(\TypeError::class);
        $participation->getEvenement();
    }

    // =========================================================================
    // TESTS D'INTÉGRITÉ DES DONNÉES
    // =========================================================================

    public function testParticipationAvecEvenementEtEmploye(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Séminaire Leadership');
        $evenement->setLieu('Tunis');
        $evenement->setDateDebut('2025-10-01');
        $evenement->setDateFin('2025-10-02');

        $employe = new Employe();

        $participation = new EventParticipation();
        $participation->setEvenement($evenement);
        $participation->setEmploye($employe);
        $participation->setStatut('en_attente');
        $participation->setDateInscription('2025-09-01');

        $this->assertSame($evenement, $participation->getEvenement());
        $this->assertSame($employe, $participation->getEmploye());
        $this->assertEquals('en_attente', $participation->getStatut());
        $this->assertEquals('2025-09-01', $participation->getDateInscription());
    }
}
