<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\Contact;
use App\Entity\Opportunity;
use App\Entity\Task;
use App\Entity\User;
use App\Enum\OpportunityStatus;
use App\Enum\TaskStatus;
use Doctrine\ORM\EntityManagerInterface;

class DashboardStats
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function kpis(User $user): array
    {
        $closed = [OpportunityStatus::Won->value, OpportunityStatus::Lost->value];

        return [
            'contacts' => (int) $this->scalar(
                'SELECT COUNT(c.id) FROM ' . Contact::class . ' c WHERE c.owner = :u',
                ['u' => $user]
            ),
            'prospects' => (int) $this->scalar(
                'SELECT COUNT(c.id) FROM ' . Contact::class . " c WHERE c.owner = :u AND c.type = 'prospect'",
                ['u' => $user]
            ),
            'openOpportunities' => (int) $this->scalar(
                'SELECT COUNT(o.id) FROM ' . Opportunity::class . ' o WHERE o.owner = :u AND o.status NOT IN (:closed)',
                ['u' => $user, 'closed' => $closed]
            ),
            'wonOpportunities' => (int) $this->scalar(
                'SELECT COUNT(o.id) FROM ' . Opportunity::class . ' o WHERE o.owner = :u AND o.status = :won',
                ['u' => $user, 'won' => OpportunityStatus::Won->value]
            ),
            'openValue' => (string) ($this->scalar(
                'SELECT SUM(o.amount) FROM ' . Opportunity::class . ' o WHERE o.owner = :u AND o.status NOT IN (:closed)',
                ['u' => $user, 'closed' => $closed]
            ) ?? '0'),
            'overdueTasks' => (int) $this->scalar(
                'SELECT COUNT(t.id) FROM ' . Task::class . ' t WHERE t.owner = :u AND t.status != :done AND t.dueAt < :today',
                ['u' => $user, 'done' => TaskStatus::Done->value, 'today' => new \DateTimeImmutable('today')]
            ),
        ];
    }

    /**
     * Fil d'activité : on fusionne les derniers événements de quatre tables.
     *
     * @return list<array{at: \DateTimeInterface, title: string, text: string, route: string, params: array}>
     */
    public function recentEvents(User $user, int $limit = 8): array
    {
        $events = [];

        foreach ($this->em->getRepository(Contact::class)->findBy(['owner' => $user], ['createdAt' => 'DESC'], $limit) as $contact) {
            $events[] = [
                'at' => $contact->getCreatedAt(),
                'title' => 'Nouveau contact',
                'text' => trim($contact->getFirstName() . ' ' . $contact->getLastName()),
                'route' => 'app_contact_show',
                'params' => ['id' => $contact->getId()],
            ];
        }

        $closedOpportunities = $this->em->createQuery(
            'SELECT o FROM ' . Opportunity::class . ' o WHERE o.owner = :u AND o.closedAt IS NOT NULL ORDER BY o.closedAt DESC'
        )->setParameter('u', $user)->setMaxResults($limit)->getResult();

        foreach ($closedOpportunities as $opportunity) {
            $won = $opportunity->getStatus() === OpportunityStatus::Won;
            $events[] = [
                'at' => $opportunity->getClosedAt(),
                'title' => $won ? 'Opportunité gagnée' : 'Opportunité perdue',
                'text' => $opportunity->getTitle(),
                'route' => 'app_opportunity_show',
                'params' => ['id' => $opportunity->getId()],
            ];
        }

        $doneTasks = $this->em->getRepository(Task::class)
            ->findBy(['owner' => $user, 'status' => TaskStatus::Done->value], ['completedAt' => 'DESC'], $limit);

        foreach ($doneTasks as $task) {
            if (!$task->getCompletedAt()) {
                continue;
            }
            $events[] = [
                'at' => $task->getCompletedAt(),
                'title' => 'Tâche terminée',
                'text' => $task->getTitle(),
                'route' => 'app_task_index',
                'params' => [],
            ];
        }

        foreach ($this->em->getRepository(Activity::class)->findBy(['owner' => $user], ['occurredAt' => 'DESC'], $limit) as $activity) {
            $events[] = [
                'at' => $activity->getOccurredAt(),
                'title' => $activity->getType()->label(),
                'text' => $activity->getTitle(),
                'route' => 'app_contact_show',
                'params' => ['id' => $activity->getContact()->getId()],
            ];
        }

        usort($events, fn (array $a, array $b) => $b['at'] <=> $a['at']);

        return array_slice($events, 0, $limit);
    }

    private function scalar(string $dql, array $params): mixed
    {
        $query = $this->em->createQuery($dql);
        foreach ($params as $name => $value) {
            $query->setParameter($name, $value);
        }

        return $query->getSingleScalarResult();
    }

    /** Répartition des opportunités par statut (pour l'anneau). */
    public function statusChart(User $user): array
    {
        $rows = $this->em->createQuery(
            'SELECT o.status AS status, COUNT(o.id) AS total FROM ' . Opportunity::class . ' o WHERE o.owner = :u GROUP BY o.status'
        )->setParameter('u', $user)->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $status = $row['status'] instanceof OpportunityStatus ? $row['status'] : OpportunityStatus::from($row['status']);
            $counts[$status->value] = (int) $row['total'];
        }

        $colors = [
            'new' => '#565e74',
            'contacted' => '#d97706',
            'negotiation' => '#00855d',
            'won' => '#006948',
            'lost' => '#ba1a1a',
        ];

        $labels = $values = $palette = [];
        foreach (OpportunityStatus::cases() as $case) {
            $count = $counts[$case->value] ?? 0;
            $labels[] = $case->label() . ' (' . $count . ')';
            $values[] = $count;
            $palette[] = $colors[$case->value];
        }

        return ['labels' => $labels, 'values' => $values, 'colors' => $palette, 'total' => array_sum($values)];
    }

    /** Nombre d'opportunités gagnées par mois sur les $months derniers mois. */
    public function wonPerMonth(User $user, int $months = 12): array
    {
        $names = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
        $start = (new \DateTimeImmutable('first day of this month midnight'))->modify('-' . ($months - 1) . ' months');

        $rows = $this->em->createQuery(
            'SELECT o.closedAt AS closedAt FROM ' . Opportunity::class . ' o WHERE o.owner = :u AND o.status = :won AND o.closedAt >= :start'
        )
            ->setParameter('u', $user)
            ->setParameter('won', OpportunityStatus::Won->value)
            ->setParameter('start', $start)
            ->getArrayResult();

        $perMonth = [];
        foreach ($rows as $row) {
            $date = $row['closedAt'] instanceof \DateTimeInterface ? $row['closedAt'] : new \DateTimeImmutable($row['closedAt']);
            $key = $date->format('Y-m');
            $perMonth[$key] = ($perMonth[$key] ?? 0) + 1;
        }

        $labels = $values = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->modify('+' . $i . ' months');
            $labels[] = $names[(int) $month->format('n') - 1] . ' ' . $month->format('y');
            $values[] = $perMonth[$month->format('Y-m')] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }
}