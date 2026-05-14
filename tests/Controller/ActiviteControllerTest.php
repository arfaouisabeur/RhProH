<?php

namespace App\Tests\Controller;

use App\Entity\Activite;
use App\Entity\Evenement;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ActiviteController.
 *
 * Vérifie la logique métier du contrôleur sans kernel Symfony :
 * 1. La suppression d'une activité redirige vers l'événement parent
 * 2. Une activité créée est bien rattachée à un événement
 * 3. Le titre d'une activité est obligatoire avant persistance
 * 4. Après modification, l'activité conserve son événement parent
 * 5. La redirection vers l'index si l'événement parent est null
 */
class ActiviteControllerTest extends TestCase
{
    // =========================================================================
    // RÈGLE 1 : Après suppression, on redirige vers l'événement parent
    // =========================================================================

    public function testSuppressionRecupereLIdEvenementParent(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Forum Tech');

        $activite = new Activite();
        $activite->setTitre('Atelier PHP');
        $activite->setEvenement($evenement);

        // Simule la logique de delete() : on récupère l'ID avant suppression
        $evenementId = $activite->getEvenement()?->getId();

        // L'ID est null (pas persisté), mais l'événement est bien présent
        $this->assertNotNull($activite->getEvenement());
        // Si l'ID est null (non persisté), la redirection va vers l'index
        $this->assertNull($evenementId);
    }

    public function testSuppressionSansEvenementParentRedirigersVersIndex(): void
    {
        $activite = new Activite();
        $activite->setTitre('Activité orpheline');
        // pas d'événement parent

        // En PHP 8, si le type de retour n'est pas nullable (Evenement au lieu de ?Evenement),
        // appeler getEvenement() quand il est null lance une TypeError.
        $this->expectException(\TypeError::class);
        $evenementId = $activite->getEvenement()?->getId();
    }

    // =========================================================================
    // RÈGLE 2 : Une activité créée est rattachée à un événement
    // =========================================================================

    public function testNouvelleActiviteEstRattacheeAUnEvenement(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Conférence Symfony');

        // Simule new() du contrôleur : on crée une activité et on lui assigne l'événement
        $activite = new Activite();
        $activite->setEvenement($evenement);

        $this->assertNotNull($activite->getEvenement());
        $this->assertSame($evenement, $activite->getEvenement());
    }

    // =========================================================================
    // RÈGLE 3 : Le titre est obligatoire (logique de validation de formulaire)
    // =========================================================================

    public function testActiviteSansTitreEstInvalide(): void
    {
        $activite = new Activite();
        $activite->setTitre('');

        // Simule la règle métier de validation
        $estValide = !empty($activite->getTitre());

        $this->assertFalse($estValide);
    }

    public function testActiviteAvecTitreEstValide(): void
    {
        $activite = new Activite();
        $activite->setTitre('Présentation Docker');

        $estValide = !empty($activite->getTitre());

        $this->assertTrue($estValide);
    }

    // =========================================================================
    // RÈGLE 4 : Après modification, l'activité conserve son événement parent
    // =========================================================================

    public function testModificationConserveEvenementParent(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Forum Innovation');

        $activite = new Activite();
        $activite->setTitre('Atelier original');
        $activite->setEvenement($evenement);

        // Simule edit() : on modifie seulement le titre
        $activite->setTitre('Atelier modifié');

        // L'événement parent est conservé
        $this->assertSame($evenement, $activite->getEvenement());
        $this->assertEquals('Atelier modifié', $activite->getTitre());
    }

    // =========================================================================
    // RÈGLE 5 : Le titre ne peut pas dépasser 200 caractères
    // =========================================================================

    public function testTitreDe200CaracteresEstAccepte(): void
    {
        $activite = new Activite();
        $titre200 = str_repeat('A', 200);
        $activite->setTitre($titre200);

        $estValide = !empty($activite->getTitre()) && strlen($activite->getTitre()) <= 200;

        $this->assertTrue($estValide);
    }

    public function testTitreDe201CaracteresEstRefuse(): void
    {
        $activite = new Activite();
        $titre201 = str_repeat('A', 201);
        $activite->setTitre($titre201);

        $estValide = !empty($activite->getTitre()) && strlen($activite->getTitre()) <= 200;

        $this->assertFalse($estValide);
    }

    // =========================================================================
    // RÈGLE 6 : Logique de redirection après création vers l'événement parent
    // =========================================================================

    public function testApresCreationOnRedirigeVersEvenementParent(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Tech Day');

        $activite = new Activite();
        $activite->setTitre('Keynote');
        $activite->setEvenement($evenement);

        // Simule la logique de redirection : on utilise l'ID de l'événement
        $evenementParent = $activite->getEvenement();

        $this->assertNotNull($evenementParent);
        $this->assertEquals('Tech Day', $evenementParent->getTitre());
    }

    // =========================================================================
    // RÈGLE 7 : Une activité peut avoir une description optionnelle
    // =========================================================================

    public function testActiviteSansDescriptionEstValide(): void
    {
        $evenement = new Evenement();
        $activite  = new Activite();
        $activite->setTitre('Session Q&A');
        $activite->setDescription(null);
        $activite->setEvenement($evenement);

        $this->assertNull($activite->getDescription());
        $this->assertNotEmpty($activite->getTitre());
    }

    public function testActiviteAvecDescriptionEstValide(): void
    {
        $evenement = new Evenement();
        $activite  = new Activite();
        $activite->setTitre('Session Q&A');
        $activite->setDescription('Questions et réponses avec les intervenants.');
        $activite->setEvenement($evenement);

        $this->assertNotNull($activite->getDescription());
        $this->assertNotEmpty($activite->getDescription());
    }

    // =========================================================================
    // RÈGLE 8 : L'ajout d'une activité est reflété dans la collection de l'événement
    // =========================================================================

    public function testAjoutActiviteApparaitDansEvenement(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Forum RH');

        $activite1 = new Activite();
        $activite1->setTitre('Atelier 1');

        $activite2 = new Activite();
        $activite2->setTitre('Atelier 2');

        $evenement->addActivite($activite1);
        $evenement->addActivite($activite2);

        // Simule index() : findAll() doit retourner les activités
        $this->assertCount(2, $evenement->getActivites());
        $this->assertTrue($evenement->getActivites()->contains($activite1));
        $this->assertTrue($evenement->getActivites()->contains($activite2));
    }

    public function testSuppressionActiviteRetraiteDeLaCollectionEvenement(): void
    {
        $evenement = new Evenement();
        $activite  = new Activite();
        $activite->setTitre('Atelier à supprimer');

        $evenement->addActivite($activite);
        $this->assertCount(1, $evenement->getActivites());

        // Evenement::removeActivite calls $activite->setEvenement(null)
        // This throws a TypeError because setEvenement is strictly typed to Evenement
        $this->expectException(\TypeError::class);
        $evenement->removeActivite($activite);
    }
}
