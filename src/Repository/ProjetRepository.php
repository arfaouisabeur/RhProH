<?php

namespace App\Repository;

use App\Entity\Projet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Projet>
 */
class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    /**
     * Recherche des projets par titre, statut, ou nom du responsable.
     *
     * @param string|null $q      Texte libre (titre ou responsable)
     * @param string|null $statut Filtre exact sur le statut
     * @return Projet[]
     */
    public function search(?string $q, ?string $statut = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.responsable_employe', 'e')
            ->leftJoin('e.user', 'u');

        if ($q) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(p.titre)', ':q'),
                    $qb->expr()->like('LOWER(u.nom)', ':q'),
                    $qb->expr()->like('LOWER(u.prenom)', ':q')
                )
            )->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if ($statut) {
            $qb->andWhere('p.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return $qb->orderBy('p.id', 'DESC')->getQuery()->getResult();
    }

    /**
     * Retourne les statistiques de statut (comptage par statut).
     *
     * @return array<int, array{statut: string|null, count: int|string}>
     */
    public function getStatusStats(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.statut, COUNT(p.id) as count')
            ->groupBy('p.statut')
            ->getQuery()
            ->getArrayResult();
    }
}
