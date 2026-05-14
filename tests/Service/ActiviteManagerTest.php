<?php

namespace App\Tests\Service;

use App\Entity\Activite;
use App\Entity\Evenement;
use App\Service\ActiviteManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le service ActiviteManager.
 *
 * Vérifie les règles métier :
 * 1. Le titre est obligatoire
 * 2. Le titre ne dépasse pas 200 caractères
 * 3. L'activité doit être associée à un événement
 * 4. La méthode getResume() tronque correctement
 * 5. La méthode hasDescription() fonctionne correctement
 */
class ActiviteManagerTest extends TestCase
{
    private ActiviteManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ActiviteManager();
    }

    // =========================================================================
    // HELPER : crée une activité valide
    // =========================================================================

    private function createActiviteValide(): Activite
    {
        $evenement = new Evenement();
        $evenement->setTitre('Forum Tech');

        $activite = new Activite();
        $activite->setTitre('Atelier Symfony');
        $activite->setDescription('Introduction à Symfony 7.');
        $activite->setEvenement($evenement);

        return $activite;
    }

    // =========================================================================
    // TESTS DE VALIDATION - CAS VALIDES
    // =========================================================================

    public function testValidationActiviteValide(): void
    {
        $activite = $this->createActiviteValide();

        $this->assertTrue($this->manager->validate($activite));
    }

    public function testValidationActiviteSansDescription(): void
    {
        $activite = $this->createActiviteValide();
        $activite->setDescription(null);

        $this->assertTrue($this->manager->validate($activite));
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 1 : Titre obligatoire
    // =========================================================================

    public function testValidationSansTitreLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de l\'activité est obligatoire');

        $activite = $this->createActiviteValide();
        $activite->setTitre('');

        $this->manager->validate($activite);
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 2 : Titre max 200 caractères
    // =========================================================================

    public function testValidationTitreTropLongLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre ne peut pas dépasser 200 caractères');

        $activite = $this->createActiviteValide();
        $activite->setTitre(str_repeat('A', 201)); // 201 caractères

        $this->manager->validate($activite);
    }

    public function testValidationTitreDe200CaracteresEstValide(): void
    {
        $activite = $this->createActiviteValide();
        $activite->setTitre(str_repeat('B', 200)); // exactement 200

        $this->assertTrue($this->manager->validate($activite));
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 3 : Événement obligatoire
    // =========================================================================

    public function testValidationSansEvenementLanceException(): void
    {
        $this->expectException(\TypeError::class);

        $activite = new Activite();
        $activite->setTitre('Atelier PHP');
        // pas d'événement assigné

        $this->manager->validate($activite);
    }

    // =========================================================================
    // TESTS DE getResume()
    // =========================================================================

    public function testGetResumeSansDescription(): void
    {
        $activite = new Activite();
        $activite->setTitre('Conférence IA');
        $activite->setDescription(null);

        $resume = $this->manager->getResume($activite);

        $this->assertEquals('Conférence IA', $resume);
    }

    public function testGetResumeCourteDescription(): void
    {
        $activite = new Activite();
        $activite->setTitre('Conférence IA');
        $activite->setDescription('Description courte.');

        $resume = $this->manager->getResume($activite);

        $this->assertEquals('Conférence IA : Description courte.', $resume);
    }

    public function testGetResumeTronqueLongueDescription(): void
    {
        $activite = new Activite();
        $activite->setTitre('Atelier');
        $longueDescription = str_repeat('X', 200); // 200 caractères
        $activite->setDescription($longueDescription);

        $resume = $this->manager->getResume($activite, 100);

        // Le résumé doit contenir "..." à la fin (tronqué)
        $this->assertStringEndsWith('...', $resume);
        // La description ne doit pas dépasser 100 + longueur du titre + " : " + "..."
        $this->assertStringContainsString('Atelier : ', $resume);
    }

    public function testGetResumeAvecLongueurPersonnalisee(): void
    {
        $activite = new Activite();
        $activite->setTitre('Test');
        $activite->setDescription('ABCDEFGHIJKLMNOPQRSTUVWXYZ'); // 26 chars

        // Avec longueur = 10, doit tronquer
        $resume = $this->manager->getResume($activite, 10);
        $this->assertStringEndsWith('...', $resume);
        $this->assertStringContainsString('ABCDEFGHIJ', $resume);
    }

    public function testGetResumeDescriptionExactementEgaleALaLongueur(): void
    {
        $activite = new Activite();
        $activite->setTitre('Test');
        $activite->setDescription('Exactement'); // 10 chars

        // Avec longueur = 10, ne doit PAS tronquer
        $resume = $this->manager->getResume($activite, 10);
        $this->assertStringNotContainsString('...', $resume);
        $this->assertEquals('Test : Exactement', $resume);
    }

    // =========================================================================
    // TESTS DE hasDescription()
    // =========================================================================

    public function testHasDescriptionRetourneTrueQuandDescriptionPresente(): void
    {
        $activite = $this->createActiviteValide();
        // description : 'Introduction à Symfony 7.'

        $this->assertTrue($this->manager->hasDescription($activite));
    }

    public function testHasDescriptionRetourneFalseQuandDescriptionNull(): void
    {
        $activite = new Activite();
        $activite->setTitre('Atelier sans description');
        $activite->setDescription(null);

        $this->assertFalse($this->manager->hasDescription($activite));
    }

    public function testHasDescriptionRetourneFalseQuandDescriptionVide(): void
    {
        $activite = new Activite();
        $activite->setTitre('Atelier sans description');
        $activite->setDescription('');

        $this->assertFalse($this->manager->hasDescription($activite));
    }

    // =========================================================================
    // TEST COMPLET BOUT EN BOUT
    // =========================================================================

    public function testValidationEtResumeComplet(): void
    {
        $evenement = new Evenement();
        $evenement->setTitre('Tech Day 2025');

        $activite = new Activite();
        $activite->setTitre('Keynote d\'ouverture');
        $activite->setDescription('Présentation des grandes tendances de l\'IA en 2025.');
        $activite->setEvenement($evenement);

        // Validation passe
        $this->assertTrue($this->manager->validate($activite));

        // La description est présente
        $this->assertTrue($this->manager->hasDescription($activite));

        // Le résumé est correct
        $resume = $this->manager->getResume($activite);
        $this->assertStringStartsWith('Keynote d\'ouverture : ', $resume);
    }
}
