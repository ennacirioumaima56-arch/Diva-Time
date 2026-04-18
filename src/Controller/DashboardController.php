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

        // Auto-fix for database schema issues (typos and missing NULL constraints)
        $conn = $entityManager->getConnection();
        try {
            // Fix 'dete' typo if it exists
            $conn->executeStatement('ALTER TABLE time_entry CHANGE dete date DATE NOT NULL');
        } catch (\Exception $e) {}

        try {
            // Fix 'note' constraint if it's currently NOT NULL
            $conn->executeStatement('ALTER TABLE time_entry MODIFY note TEXT NULL');
        } catch (\Exception $e) {}

        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        // Logic for statistics
        if ($isAdmin) {
            // Admin sees global stats
            $timeEntries = $timeEntryRepository->findBy([], ['date' => 'DESC']);
            $totalUsersCount = $userRepository->count([]);
            $activeProjectsCount = $projectRepository->count(['isActive' => true]);
        } else {
            // Regular user sees their own stats
            $timeEntries = $timeEntryRepository->findBy(['user' => $user], ['date' => 'DESC']);
            $totalUsersCount = $userRepository->count([]); // Or maybe hide this for users?
            $activeProjectsCount = $projectRepository->count(['isActive' => true]);
        }

        $totalHours = 0;
        foreach ($timeEntries as $entry) {
            $totalHours += $entry->getHours();
        }

        return $this->render('dashboard/index.html.twig', [
            'totalHours' => $totalHours,
            'activeProjectsCount' => $activeProjectsCount,
            'totalUsersCount' => $totalUsersCount,
            'recentEntries' => array_slice($timeEntries, 0, 8), // Show more entries on dashboard
            'userName' => $user->getName() ?? $user->getEmail(),
            'isAdmin' => $isAdmin
        ]);
    }
}
