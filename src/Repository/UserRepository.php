<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', $role)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllCandidats(): array
    {
        return $this->findByRole(User::ROLE_CANDIDAT);
    }

    public function findAllEmployes(): array
    {
        return $this->findByRole(User::ROLE_EMPLOYE);
    }

    public function findAllRH(): array
    {
        return $this->findByRole(User::ROLE_RH);
    }

    /**
     * Load users together with their Candidat profile in a single JOIN query.
     * Use this instead of relying on global EAGER fetch to avoid N+1 queries.
     *
     * @return User[]
     */
    public function findAllWithCandidat(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.candidat', 'c')
            ->addSelect('c')
            ->andWhere('u.role = :role')
            ->setParameter('role', User::ROLE_CANDIDAT)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Load users together with their Employe profile in a single JOIN query.
     * Use this instead of relying on global EAGER fetch to avoid N+1 queries.
     *
     * @return User[]
     */
    public function findAllWithEmploye(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.employe', 'e')
            ->addSelect('e')
            ->andWhere('u.role = :role')
            ->setParameter('role', User::ROLE_EMPLOYE)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Load users together with their RH profile in a single JOIN query.
     * Use this instead of relying on global EAGER fetch to avoid N+1 queries.
     *
     * @return User[]
     */
    public function findAllWithRH(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.rh', 'r')
            ->addSelect('r')
            ->andWhere('u.role = :role')
            ->setParameter('role', User::ROLE_RH)
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a single user with all profiles eagerly loaded (candidat, employe, rh).
     * Useful for authentication/security context where you need the full profile.
     */
    public function findOneWithProfile(int $id): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.candidat', 'c')
            ->addSelect('c')
            ->leftJoin('u.employe', 'e')
            ->addSelect('e')
            ->leftJoin('u.rh', 'r')
            ->addSelect('r')
            ->andWhere('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
