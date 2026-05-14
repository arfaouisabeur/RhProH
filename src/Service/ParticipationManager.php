<?php

namespace App\Service;

use App\Entity\EventParticipation;

/**
 * Service métier pour la gestion et la validation des participations aux événements.
 *
 * Règles métier validées :
 * 1. Le statut est obligatoire et doit être valide (en_attente, accepte, refuse)
 * 2. La date d'inscription est obligatoire et ne peut pas être dans le futur
 * 3. Un employé ne peut avoir qu'une seule participation par événement (vérifiée en amont)
 */
class ParticipationManager
{
    /** Statuts valides pour une participation */
    public const STATUTS_VALIDES = ['en_attente', 'accepte', 'refuse'];

    /**
     * Valide une participation selon les règles métier.
     *
     * @throws \InvalidArgumentException si une règle est violée
     */
    public function validate(EventParticipation $participation): bool
    {
        // Règle 1 : Le statut est obligatoire
        if (empty($participation->getStatut())) {
            throw new \InvalidArgumentException('Le statut de la participation est obligatoire');
        }

        // Règle 2 : Le statut doit être une valeur valide
        if (!in_array($participation->getStatut(), self::STATUTS_VALIDES, true)) {
            throw new \InvalidArgumentException(
                'Le statut doit être l\'une des valeurs suivantes : ' . implode(', ', self::STATUTS_VALIDES)
            );
        }

        // Règle 3 : La date d'inscription est obligatoire
        if (empty($participation->getDateInscription())) {
            throw new \InvalidArgumentException('La date d\'inscription est obligatoire');
        }

        // Règle 4 : La date d'inscription ne peut pas être dans le futur
        $today = (new \DateTime())->format('Y-m-d');
        if ($participation->getDateInscription() > $today) {
            throw new \InvalidArgumentException('La date d\'inscription ne peut pas être dans le futur');
        }

        return true;
    }

    /**
     * Accepte une participation.
     *
     * @throws \InvalidArgumentException si la participation est déjà traitée
     */
    public function accepter(EventParticipation $participation): EventParticipation
    {
        if ($participation->getStatut() === 'accepte') {
            throw new \InvalidArgumentException('Cette participation est déjà acceptée');
        }

        $participation->setStatut('accepte');
        return $participation;
    }

    /**
     * Refuse une participation.
     *
     * @throws \InvalidArgumentException si la participation est déjà refusée
     */
    public function refuser(EventParticipation $participation): EventParticipation
    {
        if ($participation->getStatut() === 'refuse') {
            throw new \InvalidArgumentException('Cette participation est déjà refusée');
        }

        $participation->setStatut('refuse');
        return $participation;
    }

    /**
     * Vérifie si une participation est en attente.
     */
    public function isEnAttente(EventParticipation $participation): bool
    {
        return $participation->getStatut() === 'en_attente';
    }

    /**
     * Vérifie si une participation est acceptée.
     */
    public function isAcceptee(EventParticipation $participation): bool
    {
        return $participation->getStatut() === 'accepte';
    }
}
