<?php

namespace App\Repository;

use App\Entity\ServiceReaction;
use App\Entity\TypeService;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceReaction>
 */
class ServiceReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceReaction::class);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  MÉTHODES MÉTIER
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Trouver la réaction d'un user sur un type de service (ou null si aucune).
     */
    public function findOneByUserAndType(User $user, TypeService $typeService): ?ServiceReaction
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.typeService = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $typeService)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compter les likes/dislikes pour un type de service donné.
     *
     * @return array{likes: int, dislikes: int}
     */
    public function countByType(TypeService $typeService): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.reaction, COUNT(r.id) AS cnt')
            ->where('r.typeService = :type')
            ->setParameter('type', $typeService)
            ->groupBy('r.reaction')
            ->getQuery()
            ->getResult();

        $result = ['likes' => 0, 'dislikes' => 0];
        foreach ($rows as $row) {
            if ($row['reaction'] === ServiceReaction::LIKE) {
                $result['likes'] = (int) $row['cnt'];
            } elseif ($row['reaction'] === ServiceReaction::DISLIKE) {
                $result['dislikes'] = (int) $row['cnt'];
            }
        }

        return $result;
    }

    /**
     * Toutes les réactions LIKE d'un utilisateur (pour la page "Mes réactions").
     *
     * @return ServiceReaction[]
     */
    public function findLikesByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.typeService', 'ts')
            ->addSelect('ts')
            ->where('r.user = :user')
            ->andWhere('r.reaction = :like')
            ->setParameter('user', $user)
            ->setParameter('like', ServiceReaction::LIKE)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Toutes les réactions DISLIKE d'un utilisateur.
     *
     * @return ServiceReaction[]
     */
    public function findDislikesByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.typeService', 'ts')
            ->addSelect('ts')
            ->where('r.user = :user')
            ->andWhere('r.reaction = :dislike')
            ->setParameter('user', $user)
            ->setParameter('dislike', ServiceReaction::DISLIKE)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Toutes les réactions d'un user (likes + dislikes), indexées par typeServiceId.
     *
     * @return array<int, string>  [ typeServiceId => 'like'|'dislike' ]
     */
    public function findReactionMapByUser(User $user): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.typeService) AS tsId, r.reaction')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['tsId']] = $row['reaction'];
        }

        return $map;
    }

    /**
     * Statistiques globales : top 5 des types les plus likés (pour le RH).
     *
     * @return array<array{typeNom: string, likes: int, dislikes: int}>
     */
    public function getTopTypes(int $limit = 5): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('ts.nom AS typeNom, ts.id AS typeId, r.reaction, COUNT(r.id) AS cnt')
            ->join('r.typeService', 'ts')
            ->groupBy('ts.id, r.reaction')
            ->getQuery()
            ->getResult();

        // Regrouper par type
        $map = [];
        foreach ($rows as $row) {
            $id = $row['typeId'];
            if (!isset($map[$id])) {
                $map[$id] = ['typeNom' => $row['typeNom'], 'likes' => 0, 'dislikes' => 0];
            }
            if ($row['reaction'] === ServiceReaction::LIKE) {
                $map[$id]['likes'] = (int) $row['cnt'];
            } else {
                $map[$id]['dislikes'] = (int) $row['cnt'];
            }
        }

        // Trier par likes décroissants
        usort($map, fn($a, $b) => $b['likes'] - $a['likes']);

        return array_slice($map, 0, $limit);
    }
}
