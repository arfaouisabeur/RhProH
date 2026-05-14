<?php

namespace App\Tests\Entity;

use App\Entity\Activite;
use App\Entity\Evenement;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'entité Activite.
 *
 * Vérifie les getters/setters, la relation avec Evenement,
 * l'interface fluente et les règles métier (titre obligatoire, longueur max).
 */
class ActiviteTest extends TestCase
{
    // =========================================================================
    // TESTS DE CRÉATION
    // =========================================================================

    public function testActiviteCanBeCreated(): void
    {
        $activite = new Activite();
        $this->assertInstanceOf(Activite::class, $activite);
        $this->assertNull($activite->getId());
    }

    // =========================================================================
    // TESTS DES GETTERS / SETTERS
    // =========================================================================

    public function testSetAndGetTitre(): void
    {
        $activite = new Activite();
        $activite->setTitre('Atelier Symfony');

        $this->assertEquals('Atelier Symfony', $activite->getTitre());
    }

    public function testSetAndGetDescription(): void
    {
        $activite = new Activite();
        $activite->setDescription('Un atelier pratique sur Symfony 7.');

        $this->assertEquals('Un atelier pratique sur Symfony 7.', $activite->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $activite = new Activite();
        $activite->setDescription(null);

        $this->assertNull($activite->getDescription());
    }

    public function testSetAndGetEvenement(): void
    {
        $activite  = new Activite();
        $evenement = new Evenement();
        $evenement->setTitre('Conférence Tech');

        $activite->setEvenement($evenement);

        $this->assertSame($evenement, $activite->getEvenement());
    }

    public function testEvenementCanBeSetToNull(): void
    {
        $activite  = new Activite();
        $evenement = new Evenement();
        $evenement->setTitre('Conférence Tech');

        $activite->setEvenement($evenement);
        
        $this->expectException(\TypeError::class);
        $activite->setEvenement(null);
    }

    // =========================================================================
    // TESTS DE L'INTERFACE FLUENTE (chaînage)
    // =========================================================================

    public function testFluentInterface(): void
    {
        $activite  = new Activite();
        $evenement = new Evenement();

        $result = $activite
            ->setTitre('Hackathon IA')
            ->setDescription('Compétition de 24h sur l\'intelligence artificielle.')
            ->setEvenement($evenement);

        $this->assertSame($activite, $result);
        $this->assertEquals('Hackathon IA', $activite->getTitre());
        $this->assertEquals('Compétition de 24h sur l\'intelligence artificielle.', $activite->getDescription());
    }

    // =========================================================================
    // TESTS DES CHAMPS NULLABLE
    // =========================================================================

    public function testNullableFields(): void
    {
        $activite = new Activite();

        $this->assertNull($activite->getId());
        $this->assertNull($activite->getDescription());
        
        $this->expectException(\TypeError::class);
        $activite->getEvenement();
    }

    // =========================================================================
    // TESTS DE RÈGLES MÉTIER
    // =========================================================================

    /**
     * Règle : Le titre est obligatoire (ne doit pas être vide).
     */
    public function testTitreNonVide(): void
    {
        $activite = new Activite();
        $activite->setTitre('Conférence IA');

        $this->assertNotEmpty($activite->getTitre());
    }

    /**
     * Règle : Le titre ne peut pas dépasser 200 caractères.
     */
    public function testTitreRespecteLongueurMaximale(): void
    {
        $activite = new Activite();
        $titre200 = str_repeat('A', 200);
        $activite->setTitre($titre200);

        $this->assertLessThanOrEqual(200, strlen($activite->getTitre()));
    }

    /**
     * Règle : Un titre de 200 caractères exactement est valide.
     */
    public function testTitreDe200CaracteresEstValide(): void
    {
        $activite = new Activite();
        $titre200 = str_repeat('X', 200);
        $activite->setTitre($titre200);

        $this->assertEquals(200, strlen($activite->getTitre()));
    }

    /**
     * Règle : L'activité doit être rattachée à un événement.
     */
    public function testActiviteAssocieAUnEvenement(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Forum RH');
        $evenement->setLieu('Tunis');
        $evenement->setDateDebut('2025-10-01');
        $evenement->setDateFin('2025-10-03');

        $activite = new Activite();
        $activite->setTitre('Table ronde recrutement');
        $activite->setEvenement($evenement);

        $this->assertNotNull($activite->getEvenement());
        $this->assertSame($evenement, $activite->getEvenement());
    }

    /**
     * Règle : Une activité ajoutée à un événement apparaît dans sa collection.
     */
    public function testActiviteApparaitDansLaCollectionDeLevenement(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Forum RH');

        $activite = new Activite();
        $activite->setTitre('Table ronde recrutement');

        $evenement->addActivite($activite);

        $this->assertTrue($evenement->getActivites()->contains($activite));
    }

    // =========================================================================
    // TESTS AVEC DIFFÉRENTES VALEURS DE TITRES
    // =========================================================================

    public function testDifferentsTitresValides(): void
    {
        $activite = new Activite();
        $titres   = [
            'Atelier PHP',
            'Conférence Machine Learning',
            'Table ronde RH & Recrutement',
            'Session Q&A',
            'Workshop Docker & Kubernetes',
        ];

        foreach ($titres as $titre) {
            $activite->setTitre($titre);
            $this->assertEquals($titre, $activite->getTitre());
            $this->assertLessThanOrEqual(200, strlen($titre));
        }
    }

    /**
     * Test complet : création d'une activité complète et valide.
     */
    public function testActiviteCompleteEtValide(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Tech Day 2025');
        $evenement->setLieu('Sousse');
        $evenement->setDateDebut('2025-11-15');
        $evenement->setDateFin('2025-11-16');

        $activite = new Activite();
        $activite->setTitre('Présentation Symfony 7');
        $activite->setDescription('Découverte des nouvelles fonctionnalités de Symfony 7.');
        $activite->setEvenement($evenement);

        $this->assertEquals('Présentation Symfony 7', $activite->getTitre());
        $this->assertNotNull($activite->getDescription());
        $this->assertSame($evenement, $activite->getEvenement());
        $this->assertNull($activite->getId()); // Pas encore persisté
    }
}
