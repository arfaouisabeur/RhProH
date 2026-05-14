<?php

namespace App\Service;

use App\Entity\CongeTt;
use App\Repository\CongeTtRepository;

/**
 * Service de détection d'abus de congés.
 * Utilise CongeRegleService pour les jours fériés (API Aladhan + fallback).
 *
 * Détecte automatiquement :
 * - Congé collé à un weekend (vendredi ou lundi) — tous types
 * - Congé collé à un jour férié tunisien — tous types
 * - Plus de 3 congés maladie par trimestre — maladie uniquement
 */
class CongeAbuseDetectorService
{
    public function __construct(
        private readonly CongeTtRepository  $congeTtRepository,
        private readonly CongeRegleService  $congeRegleService
    ) {}

    /**
     * Analyse un congé et retourne la liste des alertes détectées.
     *
     * @return array<array{niveau: string, message: string, icone: string}>
     */
    public function analyser(CongeTt $conge): array
    {
        // Ignorer les congés non persistés ou sans type
        if ($conge->getId() === null || $conge->getTypeConge() === '') {
            return [];
        }

        $type      = strtolower($conge->getTypeConge());
        $isMaladie = str_contains($type, 'maladie')
                  || str_contains($type, 'medical')
                  || str_contains($type, 'médical');

        // Weekends et jours fériés → tous les types de congé
        $alertes = array_merge(
            $this->detecterColleWeekend($conge),
            $this->detecterColleFerie($conge)
        );

        // Fréquence élevée → uniquement pour les congés maladie
        if ($isMaladie) {
            $alertes = array_merge($alertes, $this->detecterFrequenceElevee($conge));
        }

        return $alertes;
    }

    /**
     * Détecte si le congé est collé à un weekend (vendredi ou lundi).
     *
     * @return array<array{niveau: string, message: string, icone: string}>
     */
    private function detecterColleWeekend(CongeTt $conge): array
    {
        $alertes   = [];
        $dateDebut = $conge->getDateDebut();
        $dateFin   = $conge->getDateFin();

        // Début un lundi (collé au weekend précédent)
        if ((int) $dateDebut->format('N') === 1) {
            $alertes[] = [
                'niveau'  => 'danger',
                'message' => 'Congé commençant un lundi (collé au weekend)',
                'icone'   => 'fa-calendar-xmark',
            ];
        }

        // Fin un vendredi (collée au weekend suivant)
        if ((int) $dateFin->format('N') === 5) {
            $alertes[] = [
                'niveau'  => 'danger',
                'message' => 'Congé se terminant un vendredi (collé au weekend)',
                'icone'   => 'fa-calendar-xmark',
            ];
        }

        return $alertes;
    }

    /**
     * Détecte si le congé est collé à un jour férié tunisien.
     * Utilise l'API Aladhan via CongeRegleService.
     *
     * @return array<array{niveau: string, message: string, icone: string}>
     */
    private function detecterColleFerie(CongeTt $conge): array
    {
        $alertes   = [];
        $dateDebut = $conge->getDateDebut();
        $dateFin   = $conge->getDateFin();
        $annee     = (int) $dateDebut->format('Y');

        $feriesAnnee = $this->congeRegleService->getJoursFeries($annee);

        // Vérifier si le congé commence le lendemain d'un férié
        $jourAvant = (clone $dateDebut)->modify('-1 day')->format('Y-m-d');
        if (isset($feriesAnnee[$jourAvant])) {
            $ferie = $feriesAnnee[$jourAvant];
            $alertes[] = [
                'niveau'  => 'danger',
                'message' => 'Congé débutant le lendemain du jour férié : '
                           . $ferie['emoji'] . ' ' . $ferie['nom'],
                'icone'   => 'fa-star-and-crescent',
            ];
        }

        // Vérifier si le congé se termine la veille d'un férié
        $lendemain = (clone $dateFin)->modify('+1 day')->format('Y-m-d');
        if (isset($feriesAnnee[$lendemain])) {
            $ferie = $feriesAnnee[$lendemain];
            $alertes[] = [
                'niveau'  => 'danger',
                'message' => 'Congé se terminant la veille du jour férié : '
                           . $ferie['emoji'] . ' ' . $ferie['nom'],
                'icone'   => 'fa-star-and-crescent',
            ];
        }

        // Vérifier si des jours fériés sont inclus dans la période
        $feriesDansPeriode = $this->congeRegleService->getJoursFeriesDansPeriode($dateDebut, $dateFin);
        if (!empty($feriesDansPeriode)) {
            $noms = array_map(
                fn($d, $f) => $f['emoji'] . ' ' . $f['nom'],
                array_keys($feriesDansPeriode),
                array_values($feriesDansPeriode)
            );
            $alertes[] = [
                'niveau'  => 'warning',
                'message' => 'Jours fériés inclus dans la période : ' . implode(', ', $noms),
                'icone'   => 'fa-calendar-days',
            ];
        }

        return $alertes;
    }

    /**
     * Détecte si l'employé a trop de congés maladie sur le trimestre en cours.
     *
     * @return array<array{niveau: string, message: string, icone: string}>
     */
    private function detecterFrequenceElevee(CongeTt $conge): array
    {
        $alertes = [];
        $employe = $conge->getEmploye();

        if ($employe === null) {
            return [];
        }

        $dateDebut = $conge->getDateDebut();
        $mois      = (int) $dateDebut->format('n');
        $annee     = (int) $dateDebut->format('Y');
        $trimestre = (int) ceil($mois / 3);

        $debutTrimestre = new \DateTime(sprintf('%d-%02d-01', $annee, ($trimestre - 1) * 3 + 1));
        $finTrimestre   = (clone $debutTrimestre)->modify('+3 months -1 day');

        $tousConges = $this->congeTtRepository->findBy(['employe' => $employe]);
        $compteur   = 0;

        foreach ($tousConges as $c) {
            if ($c->getId() === $conge->getId()) continue;

            $typeCourant = strtolower($c->getTypeConge());
            $estMaladie  = str_contains($typeCourant, 'maladie')
                        || str_contains($typeCourant, 'medical')
                        || str_contains($typeCourant, 'médical');

            if (!$estMaladie) continue;

            $debut = $c->getDateDebut();
            if ($debut >= $debutTrimestre && $debut <= $finTrimestre) {
                $compteur++;
            }
        }

        if ($compteur >= 3) {
            $alertes[] = [
                'niveau'  => 'warning',
                'message' => sprintf(
                    '%d congé(s) maladie ce trimestre (T%d %d)',
                    $compteur + 1,
                    $trimestre,
                    $annee
                ),
                'icone'   => 'fa-triangle-exclamation',
            ];
        }

        return $alertes;
    }

    /**
     * Analyse tous les congés et retourne un tableau indexé par ID de congé.
     *
     * @param CongeTt[] $conges
     * @return array<int, array<array{niveau: string, message: string, icone: string}>>
     */
    public function analyserTous(array $conges): array
    {
        $resultats = [];
        foreach ($conges as $conge) {
            $alertes = $this->analyser($conge);
            if ($alertes !== []) {
                $resultats[$conge->getId()] = $alertes;
            }
        }
        return $resultats;
    }
}
