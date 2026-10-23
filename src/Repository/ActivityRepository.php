<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\User;
use App\Enum\ActivityKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function searchByOwner(User $owner, ?string $search, ?ActivityKind $kind): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.contact', 'c')->addSelect('c')
            ->leftJoin('a.opportunity', 'o')->addSelect('o')
            ->andWhere('a.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('a.occurredAt', 'DESC');

        if ($search !== null && $search !== '') {
            $qb->andWhere($qb->expr()->orX(
                'a.title LIKE :search',
                'a.description LIKE :search',
                'c.lastName LIKE :search',
                'c.firstName LIKE :search',
                'c.company LIKE :search'
            ))->setParameter('search', '%' . $search . '%');
        }

        if ($kind !== null) {
            $qb->andWhere('a.type = :kind')->setParameter('kind', $kind->value);
        }

        return $qb;
    }
}