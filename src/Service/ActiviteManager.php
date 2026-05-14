<?php

namespace App\Service;

use App\Entity\Activite;

/**
 * Service métier pour la gestion et la validation des activités.
 *
 * Règles métier validées :
 * 1. Le titre de l'activité est obligatoire
 * 2. Le titre ne peut pas dépasser 200 caractères
 * 3. L'activité doit être associée à un événement
 */
class ActiviteManager
{
    /**
     * Valide une activité selon les règles métier.
     *
     * @throws \InvalidArgumentException si une règle est violée
     */
    public function validate(Activite $activite): bool
    {
        // Règle 1 : Le titre est obligatoire
        if (empty($activite->getTitre())) {
            throw new \InvalidArgumentException('Le titre de l\'activité est obligatoire');
        }

        // Règle 2 : Le titre ne peut pas dépasser 200 caractères
        if (strlen($activite->getTitre()) > 200) {
            throw new \InvalidArgumentException('Le titre ne peut pas dépasser 200 caractères');
        }

        // Règle 3 : L'activité doit être associée à un événement
        if ($activite->getEvenement() === null) {
            throw new \InvalidArgumentException('L\'activité doit être associée à un événement');
        }

        return true;
    }

    public function getResume(Activite $activite, int $longueur = 100): string
    {
        $titre = $activite->getTitre() ?? '';
        $description = $activite->getDescription() ?? '';

        if (empty($description)) {
            return $titre;
        }

        if (strlen($description) <= $longueur) {
            return $titre . ' : ' . $description;
        }

        return $titre . ' : ' . substr($description, 0, $longueur) . '...';
    }

    /**
     * Vérifie si une activité a une description.
     */
    public function hasDescription(Activite $activite): bool
    {
        return !empty($activite->getDescription());
    }
}
