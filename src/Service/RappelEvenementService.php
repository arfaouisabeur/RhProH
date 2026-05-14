<?php

namespace App\Service;

use App\Entity\Employe;
use App\Repository\EventParticipationRepository;

/**
 * Service métier — Rappel d'événements dans les 24h à venir.
 *
 * Retourne les participations acceptées d'un employé
 * dont l'événement commence dans les prochaines 24 heures.
 */
class RappelEvenementService
{
    public function __construct(
        private EventParticipationRepository $participationRepo,
    ) {}

    /**
     * Retourne les participations de l'employé
     * dont l'événement commence dans les 24h à venir.
     *
     * @return array<\App\Entity\EventParticipation>
     */
    public function getRappels(Employe $employe): array
    {
        $maintenant = new \DateTime();
        $dans24h    = (new \DateTime())->modify('+24 hours');
        $ilYa7Jours = (new \DateTime())->modify('-7 days');

        return $this->participationRepo
            ->createQueryBuilder('p')
            ->join('p.evenement', 'e')
            ->where('p.employe = :employe')
            ->andWhere('
                (p.statut = :accepte AND e.date_debut BETWEEN :now AND :dans24h AND e.titre NOT LIKE :prefix)
                OR
                (e.titre LIKE :prefix AND p.statut IN (:statuts) AND e.date_debut >= :recent)
            ')
            ->setParameter('employe', $employe)
            ->setParameter('accepte', 'accepte')
            ->setParameter('statuts', ['accepte', 'en_attente'])
            ->setParameter('now', $maintenant->format('Y-m-d'))
            ->setParameter('dans24h', $dans24h->format('Y-m-d'))
            ->setParameter('recent', $ilYa7Jours->format('Y-m-d'))
            ->setParameter('prefix', '[ANNULÉ]%')
            ->orderBy('e.date_debut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne uniquement le nombre de rappels (pour le badge 🔔).
     */
    public function countRappels(Employe $employe): int
    {
        return count($this->getRappels($employe));
    }
}