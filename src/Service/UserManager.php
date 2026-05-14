<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    public function validate(User $user): bool
    {
        if (empty($user->getNom())) {
            throw new \InvalidArgumentException('Le nom est obligatoire');
        }

        if (empty($user->getPrenom())) {
            throw new \InvalidArgumentException('Le prénom est obligatoire');
        }

        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide');
        }

        if (empty($user->getRole())) {
            throw new \InvalidArgumentException('Le rôle est obligatoire');
        }

        $validRoles = [User::ROLE_CANDIDAT, User::ROLE_EMPLOYE, User::ROLE_RH];
        if (!in_array($user->getRole(), $validRoles)) {
            throw new \InvalidArgumentException('Rôle invalide');
        }

        return true;
    }

    public function canAccessRHFeatures(User $user): bool
    {
        return $user->getRole() === User::ROLE_RH;
    }

    public function isActiveUser(User $user): bool
    {
        return $user->getStatut() === 'actif';
    }

    public function getFullUserInfo(User $user): array
    {
        if (!$this->validate($user)) {
            throw new \InvalidArgumentException('Utilisateur invalide');
        }

        return [
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'statut' => $user->getStatut(),
            'isActive' => $this->isActiveUser($user),
            'canAccessRH' => $this->canAccessRHFeatures($user)
        ];
    }
}