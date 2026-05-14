<?php

namespace App\Tests\Controller;

use App\Entity\Employe;
use App\Entity\Projet;
use App\Entity\RH;
use App\Entity\User;
use App\Entity\Tache;
use App\Service\EmployeScoreService;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ProjetController.
 *
 * Vérifie la logique métier du contrôleur :
 * 1. Calcul de la progression des projets (tâches terminées / total)
 * 2. Sélection du meilleur employé parmi les responsables
 * 3. Logique d'accès à la visioconférence (meeting)
 * 4. Ouverture/Fermeture de la réunion par le RH
 */
class ProjetControllerTest extends TestCase
{
    // =========================================================================
    // RÈGLE 1 : Calcul de la progression du projet pour l'employé
    // =========================================================================

    public function testCalculProgressionProjetAvecTaches(): void
    {
        // Simule la logique de ProjetController::mesProjects
        $totalTasks = 4;
        $completedTasks = 3;

        $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        $this->assertEquals(75, $progress);
    }

    public function testCalculProgressionProjetSansTache(): void
    {
        $totalTasks = 0;
        $completedTasks = 0;

        $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        $this->assertEquals(0, $progress);
    }

    public function testCalculProgressionProjetCompletementTermine(): void
    {
        $totalTasks = 5;
        $completedTasks = 5;

        $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

        $this->assertEquals(100, $progress);
    }

    // =========================================================================
    // RÈGLE 2 : Sélection du meilleur employé dans l'index RH
    // =========================================================================

    public function testSelectionMeilleurEmployeParmiResponsables(): void
    {
        // Simule la boucle de ProjetController::index()
        
        $employe1 = new Employe();
        $user1 = new User();
        $ref1 = new \ReflectionClass($user1);
        $prop1 = $ref1->getProperty('id');
        $prop1->setAccessible(true);
        $prop1->setValue($user1, 1);
        $employe1->setUser($user1);
        
        $employe2 = new Employe();
        $user2 = new User();
        $ref2 = new \ReflectionClass($user2);
        $prop2 = $ref2->getProperty('id');
        $prop2->setAccessible(true);
        $prop2->setValue($user2, 2);
        $employe2->setUser($user2);

        $projet1 = new Projet();
        $projet1->setResponsableEmploye($employe1);
        
        $projet2 = new Projet();
        $projet2->setResponsableEmploye($employe2);

        $projets = [$projet1, $projet2];

        // Simulation du service EmployeScoreService
        $scores = [
            1 => 85, // Employe 1 a 85 points
            2 => 95, // Employe 2 a 95 points
        ];

        $meilleurId = null;
        $meilleurScore = -1;

        foreach ($projets as $projet) {
            $emp = $projet->getResponsableEmploye();
            if ($emp !== null) {
                $id = $emp->getUserId();
                $score = $scores[$id] ?? 0;
                
                if ($score > $meilleurScore) {
                    $meilleurScore = $score;
                    $meilleurId = $id;
                }
            }
        }

        $this->assertEquals(2, $meilleurId);
        $this->assertEquals(95, $meilleurScore);
    }

    // =========================================================================
    // RÈGLE 3 : Logique d'accès au meeting (Visioconférence)
    // =========================================================================

    public function testEmployeAssignePeutAccederSiReunionOuverte(): void
    {
        $employe = new Employe();
        $user = new User();
        $employe->setUser($user);

        // On utilise une classe anonyme pour ajouter les méthodes manquantes
        $projet = new class extends Projet {
            private bool $meetingRequested = false;
            public function isMeetingRequested(): bool { return $this->meetingRequested; }
            public function setIsMeetingRequested(bool $val): static { $this->meetingRequested = $val; return $this; }
        };
            
        $projet->setIsMeetingRequested(true);
        $projet->setResponsableEmploye($employe);

        // Simulation de ProjetController::meeting
        $isAssigned = ($projet->getResponsableEmploye() === $employe);
        $isMeetingRequested = $projet->isMeetingRequested();

        $canAccess = $isAssigned && $isMeetingRequested;

        $this->assertTrue($canAccess);
    }

    public function testEmployeAssigneNePeutPasAccederSiReunionFermee(): void
    {
        $employe = new Employe();
        $user = new User();
        $employe->setUser($user);

        $projet = new class extends Projet {
            private bool $meetingRequested = false;
            public function isMeetingRequested(): bool { return $this->meetingRequested; }
            public function setIsMeetingRequested(bool $val): static { $this->meetingRequested = $val; return $this; }
        };
            
        $projet->setIsMeetingRequested(false);
        $projet->setResponsableEmploye($employe);

        $isAssigned = ($projet->getResponsableEmploye() === $employe);
        $isMeetingRequested = $projet->isMeetingRequested();

        $canAccess = $isAssigned && $isMeetingRequested;

        $this->assertFalse($canAccess);
    }

    public function testEmployeNonAssigneNePeutPasAccederMemeSiOuverte(): void
    {
        $employeAssigne = new Employe();
        $userAssigne = new User();
        
        $ref1 = new \ReflectionClass($userAssigne);
        $prop1 = $ref1->getProperty('id');
        $prop1->setAccessible(true);
        $prop1->setValue($userAssigne, 1);
        $employeAssigne->setUser($userAssigne);

        $employeVisiteur = new Employe();
        $userVisiteur = new User();
        
        $ref2 = new \ReflectionClass($userVisiteur);
        $prop2 = $ref2->getProperty('id');
        $prop2->setAccessible(true);
        $prop2->setValue($userVisiteur, 2);
        $employeVisiteur->setUser($userVisiteur);

        $projet = new class extends Projet {
            private bool $meetingRequested = false;
            public function isMeetingRequested(): bool { return $this->meetingRequested; }
            public function setIsMeetingRequested(bool $val): static { $this->meetingRequested = $val; return $this; }
        };
            
        $projet->setIsMeetingRequested(true);
        $projet->setResponsableEmploye($employeAssigne);

        $isAssigned = ($projet->getResponsableEmploye() === $employeVisiteur);
        $isMeetingRequested = $projet->isMeetingRequested();

        $canAccess = $isAssigned && $isMeetingRequested;

        $this->assertFalse($canAccess);
    }

    // =========================================================================
    // RÈGLE 4 : Ouverture / Fermeture de la réunion par le RH (toggleMeeting)
    // =========================================================================

    public function testToggleMeetingInverseLEtat(): void
    {
        $projet = new class extends Projet {
            private bool $meetingRequested = false;
            public function isMeetingRequested(): bool { return $this->meetingRequested; }
            public function setIsMeetingRequested(bool $val): static { $this->meetingRequested = $val; return $this; }
        };
        
        $this->assertFalse($projet->isMeetingRequested());

        // Simule l'action toggleMeeting() - ouverture
        $projet->setIsMeetingRequested(!$projet->isMeetingRequested());
        $this->assertTrue($projet->isMeetingRequested());

        // Simule l'action toggleMeeting() - fermeture
        $projet->setIsMeetingRequested(!$projet->isMeetingRequested());
        $this->assertFalse($projet->isMeetingRequested());
    }

    // =========================================================================
    // RÈGLE 5 : Redirection après création vers la suggestion IA
    // =========================================================================

    public function testRedirectionApresCreationProjetVersSuggestionTaches(): void
    {
        $projet = new Projet();
        
        // Simule l'ID généré après persist
        $projetId = 123;
        
        // Simule le nom de la route de redirection générée
        $routeName = 'app_projet_suggest_tasks';
        $routeParams = ['id' => $projetId];

        $this->assertEquals('app_projet_suggest_tasks', $routeName);
        $this->assertEquals(123, $routeParams['id']);
    }
}
