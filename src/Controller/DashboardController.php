<?php

namespace App\Controller;

use App\Repository\TimeEntryRepository;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_dashboard');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        TimeEntryRepository $timeEntryRepository,
        UserRepository $userRepository,
        ProjectRepository $projectRepository,
        \Doctrine\ORM\EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('dashboard/index.html.twig', $this->getDashboardData(
            $timeEntryRepository,
            $userRepository,
            $projectRepository,
            $entityManager
        ));
    }

    #[Route('/dashboard/content', name: 'app_dashboard_content')]
    public function content(
        TimeEntryRepository $timeEntryRepository,
        UserRepository $userRepository,
        ProjectRepository $projectRepository,
        \Doctrine\ORM\EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('dashboard/_content.html.twig', $this->getDashboardData(
            $timeEntryRepository,
            $userRepository,
            $projectRepository,
            $entityManager
        ));
    }

    private function getDashboardData($timeEntryRepository, $userRepository, $projectRepository, $entityManager): array
    {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Logique stats
        if ($isAdmin) {
            $timeEntries = $timeEntryRepository->findBy([], ['date' => 'DESC']);
            $totalUsersCount = $userRepository->count([]);
            $activeProjectsCount = $projectRepository->count(['isActive' => true]);
        } else {
            $timeEntries = $timeEntryRepository->findBy(['user' => $user], ['date' => 'DESC']);
            $totalUsersCount = $userRepository->count([]);
            $activeProjectsCount = $projectRepository->count(['isActive' => true]);
        }

        $totalHours = 0;
        foreach ($timeEntries as $entry) {
            $totalHours += $entry->getHours();
        }

        // === Données graphiques ===

        // 1) Heures par jour - 7 derniers jours (jours en français)
        $joursFr = ['Sun' => 'Dim', 'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mer', 'Thu' => 'Jeu', 'Fri' => 'Ven', 'Sat' => 'Sam'];
        $last7Days = [];
        $hoursPerDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = new \DateTimeImmutable("-{$i} days");
            $engKey = $day->format('D d/m');
            $frDay  = $joursFr[$day->format('D')] . ' ' . $day->format('d/m');
            $last7Days[$engKey] = $frDay;
            $hoursPerDay[$engKey] = 0;
        }
        foreach ($timeEntries as $entry) {
            $label = $entry->getDate()->format('D d/m');
            if (isset($hoursPerDay[$label])) {
                $hoursPerDay[$label] += $entry->getHours();
            }
        }

        // 2) Heures par projet
        $projectHours = [];
        $projectColors = [];
        foreach ($timeEntries as $entry) {
            $projectName = $entry->getProject()->getName();
            $projectColor = $entry->getProject()->getColor();
            if (!isset($projectHours[$projectName])) {
                $projectHours[$projectName] = 0;
                $projectColors[$projectName] = $projectColor;
            }
            $projectHours[$projectName] += $entry->getHours();
        }

        // 3) Statut des tâches
        $taskRepo = $entityManager->getRepository(\App\Entity\Task::class);
        $allTasks = $isAdmin
            ? $taskRepo->findAll()
            : $taskRepo->findBy(['assignedTo' => $user]);

        $taskStats = ['pending' => 0, 'in_progress' => 0, 'completed' => 0];
        foreach ($allTasks as $task) {
            $s = $task->getStatus() ?? 'pending';
            if (array_key_exists($s, $taskStats)) {
                $taskStats[$s]++;
            }
        }
        $totalTasksCount = count($allTasks);

        return [
            'totalHours'         => $totalHours,
            'activeProjectsCount'=> $activeProjectsCount,
            'totalUsersCount'    => $totalUsersCount,
            'recentEntries'      => array_slice($timeEntries, 0, 8),
            'userName'           => $user->getName() ?? $user->getEmail(),
            'isAdmin'            => $isAdmin,
            // Charts data
            'chartDaysLabels'    => json_encode(array_values($last7Days)),
            'chartDaysData'      => json_encode(array_values($hoursPerDay)),
            'chartProjectLabels' => json_encode(array_keys($projectHours)),
            'chartProjectData'   => json_encode(array_values($projectHours)),
            'chartProjectColors' => json_encode(array_values($projectColors)),
            'taskStatsPending'   => $taskStats['pending'],
            'taskStatsInProgress'=> $taskStats['in_progress'],
            'taskStatsCompleted' => $taskStats['completed'],
            'totalTasks'         => $totalTasksCount,
        ];
    }
}
