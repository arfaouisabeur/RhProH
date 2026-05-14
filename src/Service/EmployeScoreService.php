<?php

namespace App\Service;

use App\Entity\Employe;
use App\Entity\Tache;
use App\Entity\Projet;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Système de scoring métier avancé — 100% calcul interne, sans API externe.
 *
 * Score d'un employé :
 *   + 3 pts  par tâche terminée
 *   + 1 pt   par tâche en cours
 *   - 2 pts  par tâche bloquée
 *   - 1 pt   par tâche en retard
 *   + 5 pts  par projet terminé (responsable)
 *   + 2 pts  par projet en cours (responsable)
 *   + 10 pts bonus si taux de complétion ≥ 80 %
 *   + 5 pts  bonus si aucune tâche bloquée
 */
class EmployeScoreService
{
    // Seuil de score pour obtenir une étoile ⭐
    private const SEUIL_ETOILE = 5;

    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Calcule le score d'un employé.
     */
    public function calculerScore(Employe $employe): int
    {
        $now    = new \DateTime();
        $taches = $this->em->getRepository(Tache::class)->findBy(['employe' => $employe]);

        $terminees = 0;
        $enCours   = 0;
        $bloquees  = 0;
        $retard    = 0;

        foreach ($taches as $t) {
            $statut = strtolower(trim($t->getStatut() ?? ''));
            if (in_array($statut, ['terminee', 'terminée', 'done'])) $terminees++;
            elseif (in_array($statut, ['en_cours', 'encours']))       $enCours++;
            elseif (in_array($statut, ['bloquee', 'bloquée']))        $bloquees++;

            if ($t->getDateFin() !== null && $t->getDateFin() < $now
                && !in_array($statut, ['terminee', 'terminée', 'done'])) {
                $retard++;
            }
        }

        // Score tâches
        $score = ($terminees * 3) + ($enCours * 1) - ($bloquees * 2) - ($retard * 1);

        // Score projets (responsable)
        $projets = $this->em->getRepository(Projet::class)->findBy(['responsable_employe' => $employe]);
        foreach ($projets as $p) {
            $statut = strtolower(trim($p->getStatut() ?? ''));
            if ($statut === 'termine')  $score += 5;
            if ($statut === 'en_cours') $score += 2;
        }

        // Bonus taux de complétion
        $total = count($taches);
        if ($total > 0) {
            $taux = ($terminees / $total) * 100;
            if ($taux >= 80) $score += 10;
            elseif ($taux >= 50) $score += 3;
        }

        // Bonus zéro blocage
        if ($bloquees === 0 && $total > 0) $score += 5;

        return max(0, $score);
    }

    /**
     * Retourne true si l'employé mérite une étoile.
     */
    public function meritEtoile(Employe $employe): bool
    {
        return $this->calculerScore($employe) >= self::SEUIL_ETOILE;
    }

    /**
     * Retourne le niveau de l'employé selon son score.
     */
    public function getNiveau(Employe $employe): array
    {
        $score = $this->calculerScore($employe);

        if ($score >= 40) return ['label' => 'Expert',    'icon' => '🏆', 'color' => '#f59e0b', 'stars' => 3];
        if ($score >= 20) return ['label' => 'Confirmé',  'icon' => '⭐', 'color' => '#6b2d8b', 'stars' => 2];
        if ($score >= 5)  return ['label' => 'Actif',     'icon' => '✨', 'color' => '#10b981', 'stars' => 1];
        return                    ['label' => 'Débutant',  'icon' => '',   'color' => '#94a3b8', 'stars' => 0];
    }

    /**
     * Calcule les scores de tous les employés et retourne un tableau trié.
     * Format : [['employe' => Employe, 'score' => int, 'niveau' => array], ...]
     */
    public function classement(): array
    {
        $employes = $this->em->getRepository(Employe::class)->findAll();
        $result   = [];

        foreach ($employes as $e) {
            $score  = $this->calculerScore($e);
            $result[] = [
                'employe' => $e,
                'score'   => $score,
                'niveau'  => $this->getNiveau($e),
            ];
        }

        usort($result, fn($a, $b) => $b['score'] <=> $a['score']);

        return $result;
    }
}
