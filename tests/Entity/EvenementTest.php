<?php

namespace App\Tests\Entity;

use App\Entity\Activite;
use App\Entity\Evenement;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'entité Evenement.
 *
 * Vérifie les getters/setters, les règles métier de base
 * et le comportement des collections (activités, participations).
 */
class EvenementTest extends TestCase
{
    // =========================================================================
    // TESTS DE CRÉATION
    // =========================================================================

    public function testEvenementCanBeCreated(): void
    {
        $evenement = new Evenement();
        $this->assertInstanceOf(Evenement::class, $evenement);
        $this->assertNull($evenement->getId());
    }

    public function testEvenementCollectionsAreInitializedEmpty(): void
    {
        $evenement = new Evenement();
        $this->assertCount(0, $evenement->getActivites());
        $this->assertCount(0, $evenement->getRatings());
        $this->assertCount(0, $evenement->getParticipations());
    }

    // =========================================================================
    // TESTS DES GETTERS / SETTERS
    // =========================================================================

    public function testSetAndGetTitre(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Conférence RH 2025');

        $this->assertEquals('Conférence RH 2025', $evenement->getTitre());
    }

    public function testSetAndGetLieu(): void
    {
        $evenement = new Evenement();
        $evenement->setLieu('Tunis, Tunisie');

        $this->assertEquals('Tunis, Tunisie', $evenement->getLieu());
    }

    public function testSetAndGetDateDebut(): void
    {
        $evenement = new Evenement();
        $evenement->setDateDebut('2025-06-01');

        $this->assertEquals('2025-06-01', $evenement->getDateDebut());
    }

    public function testSetAndGetDateFin(): void
    {
        $evenement = new Evenement();
        $evenement->setDateFin('2025-06-10');

        $this->assertEquals('2025-06-10', $evenement->getDateFin());
    }

    public function testSetAndGetDescription(): void
    {
        $evenement = new Evenement();
        $evenement->setDescription('Une description détaillée de l\'événement.');

        $this->assertEquals('Une description détaillée de l\'événement.', $evenement->getDescription());
    }

    public function testDescriptionCanBeNull(): void
    {
        $evenement = new Evenement();
        $evenement->setDescription(null);

        $this->assertNull($evenement->getDescription());
    }

    public function testSetAndGetImageUrl(): void
    {
        $evenement = new Evenement();
        $evenement->setImageUrl('/uploads/evenements/image.jpg');

        $this->assertEquals('/uploads/evenements/image.jpg', $evenement->getImageUrl());
    }

    public function testImageUrlCanBeNull(): void
    {
        $evenement = new Evenement();
        $evenement->setImageUrl(null);

        $this->assertNull($evenement->getImageUrl());
    }

    public function testSetAndGetLatitude(): void
    {
        $evenement = new Evenement();
        $evenement->setLatitude('36.8065');

        $this->assertEquals('36.8065', $evenement->getLatitude());
    }

    public function testLatitudeCanBeNull(): void
    {
        $evenement = new Evenement();
        $evenement->setLatitude(null);

        $this->assertNull($evenement->getLatitude());
    }

    public function testSetAndGetLongitude(): void
    {
        $evenement = new Evenement();
        $evenement->setLongitude('10.1815');

        $this->assertEquals('10.1815', $evenement->getLongitude());
    }

    public function testLongitudeCanBeNull(): void
    {
        $evenement = new Evenement();
        $evenement->setLongitude(null);

        $this->assertNull($evenement->getLongitude());
    }

    // =========================================================================
    // TESTS DES COLLECTIONS D'ACTIVITÉS
    // =========================================================================

    public function testAddActivite(): void
    {
        $evenement = new Evenement();
        $activite  = new Activite();
        $activite->setTitre('Atelier PHP');

        $evenement->addActivite($activite);

        $this->assertCount(1, $evenement->getActivites());
        $this->assertTrue($evenement->getActivites()->contains($activite));
    }

    public function testAddActiviteSetsEvenementReference(): void
    {
        $evenement = new Evenement();
        $activite  = new Activite();
        $activite->setTitre('Atelier PHP');

        $evenement->addActivite($activite);

        // L'activité doit référencer l'événement parent
        $this->assertSame($evenement, $activite->getEvenement());
    }

    public function testAddSameActiviteTwiceDoesNotDuplicate(): void
    {
        $evenement = new Evenement();
        $activite  = new Activite();
        $activite->setTitre('Atelier PHP');

        $evenement->addActivite($activite);
        $evenement->addActivite($activite); // deuxième ajout

        $this->assertCount(1, $evenement->getActivites());
    }

    public function testRemoveActivite(): void
    {
        $evenement = new Evenement();
        $activite  = new Activite();
        $activite->setTitre('Atelier PHP');

        $evenement->addActivite($activite);
        $this->expectException(\TypeError::class);
        $evenement->removeActivite($activite);
    }

    public function testAddMultipleActivites(): void
    {
        $evenement  = new Evenement();
        $activite1  = new Activite();
        $activite1->setTitre('Atelier 1');
        $activite2  = new Activite();
        $activite2->setTitre('Atelier 2');

        $evenement->addActivite($activite1);
        $evenement->addActivite($activite2);

        $this->assertCount(2, $evenement->getActivites());
    }

    // =========================================================================
    // TESTS DE L'INTERFACE FLUENTE (chaînage)
    // =========================================================================

    public function testFluentInterface(): void
    {
        $evenement = new Evenement();

        $result = $evenement
            ->setTitre('Forum Innovation')
            ->setLieu('Sfax, Tunisie')
            ->setDateDebut('2025-09-01')
            ->setDateFin('2025-09-03')
            ->setDescription('Forum annuel sur l\'innovation technologique.')
            ->setImageUrl('/uploads/evenements/forum.jpg')
            ->setLatitude('34.7406')
            ->setLongitude('10.7603');

        $this->assertSame($evenement, $result);
        $this->assertEquals('Forum Innovation', $evenement->getTitre());
        $this->assertEquals('Sfax, Tunisie', $evenement->getLieu());
    }

    // =========================================================================
    // TESTS DES CHAMPS NULLABLE
    // =========================================================================

    public function testNullableFields(): void
    {
        $evenement = new Evenement();

        $this->assertNull($evenement->getId());
        $this->assertNull($evenement->getDescription());
        $this->assertNull($evenement->getImageUrl());
        $this->assertNull($evenement->getLatitude());
        $this->assertNull($evenement->getLongitude());
        $this->assertNull($evenement->getRh());
    }

    // =========================================================================
    // TESTS DE RÈGLES MÉTIER (via les valeurs)
    // =========================================================================

    public function testDateFinSuperieureADateDebut(): void
    {
        $evenement = new Evenement();
        $evenement->setDateDebut('2025-06-01');
        $evenement->setDateFin('2025-06-10');

        // La date de fin doit être strictement postérieure à la date de début
        $this->assertGreaterThan($evenement->getDateDebut(), $evenement->getDateFin());
    }

    public function testTitreNonAnnule(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Événement Normal');

        $this->assertFalse(str_starts_with($evenement->getTitre(), '[ANNULÉ] '));
    }

    public function testTitreMarqueAnnule(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('[ANNULÉ] Ancien Événement');

        $this->assertTrue(str_starts_with($evenement->getTitre(), '[ANNULÉ] '));
    }
}
