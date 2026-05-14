<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * Recherche des événements par mot-clé (titre, lieu, description).
     * Utilisée par la recherche AJAX côté employé.
     *
     * @return Evenement[]
     */
    public function searchByKeyword(string $keyword): array
    {
        $q = '%' . mb_strtolower($keyword) . '%';

        return $this->createQueryBuilder('e')
            ->where('LOWER(e.titre) LIKE :q OR LOWER(e.lieu) LIKE :q OR LOWER(e.description) LIKE :q')
            ->setParameter('q', $q)
            ->orderBy('e.date_debut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}