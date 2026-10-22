<?php

namespace App\Repository;

use App\Entity\Contact;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\TaskStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    private function openQuery(User $owner)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')
            ->andWhere('t.status != :done')
            ->setParameter('owner', $owner)
            ->setParameter('done', TaskStatus::Done)
            ->orderBy('t.dueAt', 'ASC');
    }

    /** @return Task[] */
    public function overdue(User $owner, \DateTimeImmutable $today): array
    {
        return $this->openQuery($owner)
            ->andWhere('t.dueAt < :today')->setParameter('today', $today)
            ->getQuery()->getResult();
    }

    /** @return Task[] */
    public function dueToday(User $owner, \DateTimeImmutable $today): array
    {
        return $this->openQuery($owner)
            ->andWhere('t.dueAt >= :today')->andWhere('t.dueAt < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $today->modify('+1 day'))
            ->getQuery()->getResult();
    }

    /** @return Task[] */
    public function upcoming(User $owner, \DateTimeImmutable $today): array
    {
        return $this->openQuery($owner)
            ->andWhere('t.dueAt >= :tomorrow')
            ->setParameter('tomorrow', $today->modify('+1 day'))
            ->getQuery()->getResult();
    }

    /** @return Task[] */
    public function recentlyDone(User $owner, int $limit = 15): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.owner = :owner')->andWhere('t.status = :done')
            ->setParameter('owner', $owner)->setParameter('done', TaskStatus::Done)
            ->orderBy('t.completedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /** @return Task[] */
    public function openForContact(Contact $contact): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.contact = :contact')->andWhere('t.status != :done')
            ->setParameter('contact', $contact)->setParameter('done', TaskStatus::Done)
            ->orderBy('t.dueAt', 'ASC')
            ->getQuery()->getResult();
    }
}