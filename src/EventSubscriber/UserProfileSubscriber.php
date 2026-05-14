<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\CandidatRepository;
use App\Repository\EmployeRepository;
use App\Repository\RHRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * Populates User::$candidat, $employe and $rh after Doctrine loads a User.
 *
 * These properties are NOT mapped by Doctrine (no @OneToOne mappedBy) to avoid
 * the silent N+1 queries caused by OneToOne inverse-side lazy-loading.
 * Instead, we batch the hydration here with a single targeted query per type.
 *
 * Important: this runs ONE extra query per User object loaded individually.
 * For lists, prefer UserRepository::findAllWithCandidat/Employe/RH which use
 * LEFT JOINs and avoid any extra queries entirely.
 */
#[AsDoctrineListener(event: Events::postLoad)]
class UserProfileSubscriber
{
    public function __construct(
        private readonly CandidatRepository $candidatRepository,
        private readonly EmployeRepository  $employeRepository,
        private readonly RHRepository       $rhRepository,
    ) {}

    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // Use findOneBy so Doctrine's identity map returns the cached object
        // (no extra SQL if the profile was already loaded by a join query)
        $candidat = $this->candidatRepository->findOneBy(['user' => $entity]);
        $entity->setCandidat($candidat);

        $employe = $this->employeRepository->findOneBy(['user' => $entity]);
        $entity->setEmploye($employe);

        $rh = $this->rhRepository->findOneBy(['user' => $entity]);
        $entity->setRh($rh);
    }
}
