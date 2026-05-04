<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tasks')]
class TaskController extends AbstractController
{
    #[Route('', name: 'app_task_index', methods: ['GET'])]
    public function index(Request $request, TaskRepository $taskRepository, \App\Repository\ProjectRepository $projectRepository): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $projectId = $request->query->get('project');
        
        $tasks = $taskRepository->findByFilter($projectId ? (int)$projectId : null);
        $projects = $projectRepository->findBy(['isActive' => true], ['name' => 'ASC']);

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
            'projects' => $projects,
            'currentProjectId' => $projectId,
            'isAdmin' => $isAdmin,
        ]);
    }

    #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/new.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        // Vérifier permissions
        if (!$isAdmin && $task->getAssignedTo() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas l\'autorisation de modifier cette tâche.');
        }

        // Sauvegarder les valeurs obligatoires avant le formulaire
        $originalProject    = $task->getProject();
        $originalAssignedTo = $task->getAssignedTo();

        $form = $this->createForm(TaskType::class, $task);
        
        // Non-admin: seulement le statut est modifiable
        if (!$isAdmin) {
            $form->remove('title');
            $form->remove('description');
            $form->remove('project');
            $form->remove('assignedTo');
            $form->remove('deadline');
            $form->remove('estimatedHours');
            $form->remove('estimatedDurationUnit');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Restaurer les champs obligatoires si enlevés du formulaire
            if (!$isAdmin) {
                $task->setProject($originalProject);
                $task->setAssignedTo($originalAssignedTo);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Tâche mise à jour avec succès.');
            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('task/edit.html.twig', [
            'task'    => $task,
            'form'    => $form,
            'isAdmin' => $isAdmin
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_task_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, Task $task, string $status, EntityManagerInterface $entityManager): Response
    {
        // CSRF Check
        if (!$this->isCsrfTokenValid('status' . $task->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_task_index');
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');
        // Admin peut toujours changer, sinon seulement l'utilisateur assigné
        if (!$isAdmin && $task->getAssignedTo() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Seul l\'utilisateur assigné peut changer le statut.');
        }

        $allowedStatuses = ['pending', 'in_progress', 'completed'];
        if (in_array($status, $allowedStatuses)) {
            $task->setStatus($status);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_task_index');
    }

    #[Route('/{id}', name: 'app_task_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$task->getId(), $request->request->get('_token'))) {
            $entityManager->remove($task);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_task_index');
    }

    #[Route('/count', name: 'app_task_count', methods: ['GET'])]
    public function count(TaskRepository $taskRepository): Response
    {
        $user = $this->getUser();
        if ($this->isGranted('ROLE_ADMIN')) {
            $count = $taskRepository->count(['status' => ['pending', 'in_progress']]);
        } else {
            $count = $taskRepository->count(['assignedTo' => $user, 'status' => ['pending', 'in_progress']]);
        }

        if ($count === 0) {
            return new Response('');
        }

        return new Response('<turbo-frame id="sidebar-task-count"><span class="badge rounded-pill bg-warning text-dark" style="font-size: 0.7rem;">' . $count . '</span></turbo-frame>');
    }
}
