<?php

namespace App\Service;

use App\Entity\Evenement;

/**
 * Service métier pour la gestion et la validation des événements.
 *
 * Règles métier validées :
 * 1. Le titre de l'événement est obligatoire
 * 2. Le lieu de l'événement est obligatoire
 * 3. La date de fin doit être postérieure à la date de début
 * 4. Le titre ne peut pas dépasser 255 caractères
 */
class EvenementManager
{
    /**
     * Valide un événement selon les règles métier.
     *
     * @throws \InvalidArgumentException si une règle est violée
     */
    public function validate(Evenement $evenement): bool
    {
        // Règle 1 : Le titre est obligatoire
        if (empty($evenement->getTitre())) {
            throw new \InvalidArgumentException('Le titre de l\'événement est obligatoire');
        }

        // Règle 2 : Le lieu est obligatoire
        if (empty($evenement->getLieu())) {
            throw new \InvalidArgumentException('Le lieu de l\'événement est obligatoire');
        }

        // Règle 3 : La date de début est obligatoire
        if (empty($evenement->getDateDebut())) {
            throw new \InvalidArgumentException('La date de début est obligatoire');
        }

        // Règle 4 : La date de fin est obligatoire
        if (empty($evenement->getDateFin())) {
            throw new \InvalidArgumentException('La date de fin est obligatoire');
        }

        // Règle 5 : La date de fin doit être postérieure à la date de début
        if ($evenement->getDateFin() <= $evenement->getDateDebut()) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début');
        }

        // Règle 6 : Le titre ne peut pas dépasser 255 caractères
        if (strlen($evenement->getTitre()) > 255) {
            throw new \InvalidArgumentException('Le titre ne peut pas dépasser 255 caractères');
        }

        return true;
    }

    /**
     * Vérifie si un événement est annulé (commence par "[ANNULÉ]").
     */
    public function isAnnule(Evenement $evenement): bool
    {
        return str_starts_with((string) $evenement->getTitre(), '[ANNULÉ] ');
    }

    /**
     * Marque un événement comme annulé.
     *
     * @throws \InvalidArgumentException si l'événement est déjà annulé
     */
    public function annuler(Evenement $evenement): Evenement
    {
        if ($this->isAnnule($evenement)) {
            throw new \InvalidArgumentException('Cet événement est déjà annulé');
        }

        $evenement->setTitre('[ANNULÉ] ' . $evenement->getTitre());
        return $evenement;
    }

    /**
     * Retourne le statut de l'événement par rapport à la date du jour.
     */
    public function getStatut(Evenement $evenement): string
    {
        $today = (new \DateTime())->format('Y-m-d');
        $debut = $evenement->getDateDebut();
        $fin   = $evenement->getDateFin();

        if ($debut <= $today && $fin >= $today) {
            return 'en_cours';
        }

        if ($debut > $today) {
            return 'a_venir';
        }

        return 'termine';
    }
}
