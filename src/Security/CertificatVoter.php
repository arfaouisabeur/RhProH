<?php

namespace App\Security;

use App\Entity\Candidature;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class CertificatVoter extends Voter
{
    const DOWNLOAD = 'certificat_download';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::DOWNLOAD
            && $subject instanceof Candidature;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Candidature $subject */
        return $subject->getCandidat()->getUser()->getId() === $user->getId();
    }
}
