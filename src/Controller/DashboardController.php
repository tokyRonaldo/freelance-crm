<?php

namespace App\Controller;

use App\Repository\TaskRepository;
use App\Service\DashboardStats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('home/index.html.twig');
    }
    
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(DashboardStats $stats, TaskRepository $tasks): Response
    {
        $user = $this->getUser();
        $today = new \DateTimeImmutable('today');

        $overdue = $tasks->overdue($user, $today);
        $dueToday = $tasks->dueToday($user, $today);

        return $this->render('dashboard/index.html.twig', [
            'kpis' => $stats->kpis($user),
            'statusChart' => $stats->statusChart($user),
            'wonChart' => $stats->wonPerMonth($user),
            'priorityTasks' => array_slice([...$overdue, ...$dueToday], 0, 5),
            'overdueCount' => count($overdue),
            'todayCount' => count($dueToday),
            'events' => $stats->recentEvents($user),
        ]);
    }
}