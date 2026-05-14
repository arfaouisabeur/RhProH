<?php

namespace App\Tests\Service;

use App\Entity\Evenement;
use App\Service\EvenementManager;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le service EvenementManager.
 *
 * Vérifie les règles métier :
 * 1. Le titre est obligatoire
 * 2. Le lieu est obligatoire
 * 3. Les dates de début et fin sont obligatoires
 * 4. La date de fin doit être postérieure à la date de début
 * 5. Le titre ne peut pas dépasser 255 caractères
 * 6. La méthode annuler() et isAnnule() fonctionnent correctement
 * 7. La méthode getStatut() retourne le bon statut
 */
class EvenementManagerTest extends TestCase
{
    private EvenementManager $manager;

    protected function setUp(): void
    {
        $this->manager = new EvenementManager();
    }

    // =========================================================================
    // HELPER : crée un événement valide
    // =========================================================================

    private function createEvenementValide(): Evenement
    {
        $evenement = new Evenement();
        $evenement->setTitre('Conférence RH 2025');
        $evenement->setLieu('Tunis, Tunisie');
        $evenement->setDateDebut('2025-09-01');
        $evenement->setDateFin('2025-09-03');
        return $evenement;
    }

    // =========================================================================
    // TESTS DE VALIDATION - CAS VALIDES
    // =========================================================================

    public function testValidationEvenementValide(): void
    {
        $evenement = $this->createEvenementValide();

        $this->assertTrue($this->manager->validate($evenement));
    }

    public function testValidationEvenementAvecDescriptionEtImage(): void
    {
        $evenement = $this->createEvenementValide();
        $evenement->setDescription('Une description complète de l\'événement.');
        $evenement->setImageUrl('/uploads/evenements/conf.jpg');
        $evenement->setLatitude('36.8065');
        $evenement->setLongitude('10.1815');

        $this->assertTrue($this->manager->validate($evenement));
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 1 : Titre obligatoire
    // =========================================================================

    public function testValidationSansTitreLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre de l\'événement est obligatoire');

        $evenement = $this->createEvenementValide();
        $evenement->setTitre('');

        $this->manager->validate($evenement);
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 2 : Lieu obligatoire
    // =========================================================================

    public function testValidationSansLieuLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le lieu de l\'événement est obligatoire');

        $evenement = $this->createEvenementValide();
        $evenement->setLieu('');

        $this->manager->validate($evenement);
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 3 : Date de début obligatoire
    // =========================================================================

    public function testValidationSansDateDebutLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de début est obligatoire');

        $evenement = $this->createEvenementValide();
        $evenement->setDateDebut('');

        $this->manager->validate($evenement);
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 4 : Date de fin obligatoire
    // =========================================================================

    public function testValidationSansDateFinLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin est obligatoire');

        $evenement = $this->createEvenementValide();
        $evenement->setDateFin('');

        $this->manager->validate($evenement);
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 5 : Date fin > Date début
    // =========================================================================

    public function testValidationDateFinEgaleADateDebutLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début');

        $evenement = $this->createEvenementValide();
        $evenement->setDateDebut('2025-09-01');
        $evenement->setDateFin('2025-09-01'); // même date

        $this->manager->validate($evenement);
    }

    public function testValidationDateFinAvantDateDebutLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début');

        $evenement = $this->createEvenementValide();
        $evenement->setDateDebut('2025-09-10');
        $evenement->setDateFin('2025-09-01'); // fin avant début

        $this->manager->validate($evenement);
    }

    // =========================================================================
    // TESTS DE VALIDATION - RÈGLE 6 : Titre max 255 caractères
    // =========================================================================

    public function testValidationTitreTropLongLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre ne peut pas dépasser 255 caractères');

        $evenement = $this->createEvenementValide();
        $evenement->setTitre(str_repeat('A', 256)); // 256 caractères

        $this->manager->validate($evenement);
    }

    public function testValidationTitreDe255CaracteresEstValide(): void
    {
        $evenement = $this->createEvenementValide();
        $evenement->setTitre(str_repeat('A', 255)); // exactement 255

        $this->assertTrue($this->manager->validate($evenement));
    }

    // =========================================================================
    // TESTS DE isAnnule()
    // =========================================================================

    public function testIsAnnuleRetourneFalsePourEvenementNormal(): void
    {
        $evenement = $this->createEvenementValide();

        $this->assertFalse($this->manager->isAnnule($evenement));
    }

    public function testIsAnnuleRetourneTruePourEvenementAnnule(): void
    {
        $evenement = $this->createEvenementValide();
        $evenement->setTitre('[ANNULÉ] Conférence RH 2025');

        $this->assertTrue($this->manager->isAnnule($evenement));
    }

    // =========================================================================
    // TESTS DE annuler()
    // =========================================================================

    public function testAnnulerEvenementNonAnnule(): void
    {
        $evenement = $this->createEvenementValide();

        $this->manager->annuler($evenement);

        $this->assertTrue($this->manager->isAnnule($evenement));
        $this->assertEquals('[ANNULÉ] Conférence RH 2025', $evenement->getTitre());
    }

    public function testAnnulerEvenementDejaAnnuleLanceException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cet événement est déjà annulé');

        $evenement = $this->createEvenementValide();
        $this->manager->annuler($evenement); // 1ère annulation
        $this->manager->annuler($evenement); // doit lever une exception
    }

    // =========================================================================
    // TESTS DE getStatut()
    // =========================================================================

    public function testGetStatutRetourneAVenir(): void
    {
        $evenement = new Evenement();
        $evenement->setDateDebut('2099-01-01');
        $evenement->setDateFin('2099-12-31');

        $this->assertEquals('a_venir', $this->manager->getStatut($evenement));
    }

    public function testGetStatutRetourneTermine(): void
    {
        $evenement = new Evenement();
        $evenement->setDateDebut('2000-01-01');
        $evenement->setDateFin('2000-12-31');

        $this->assertEquals('termine', $this->manager->getStatut($evenement));
    }

    public function testGetStatutRetourneEnCours(): void
    {
        $today    = (new \DateTime())->format('Y-m-d');
        $hier     = (new \DateTime('-1 day'))->format('Y-m-d');
        $demain   = (new \DateTime('+1 day'))->format('Y-m-d');

        $evenement = new Evenement();
        $evenement->setDateDebut($hier);
        $evenement->setDateFin($demain);

        $this->assertEquals('en_cours', $this->manager->getStatut($evenement));
    }
}
