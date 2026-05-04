<?php

namespace App\Controller;

use App\Entity\TimeEntry;           
use App\Form\TimeEntryType;         
use App\Repository\TimeEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;  
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/time-entry')]
class TimeEntryController extends AbstractController
{

// Lecture données

    #[Route('/', name: 'app_time_entry_index', methods: ['GET'])]
    public function index(TimeEntryRepository $timeEntryRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        $entries = $timeEntryRepository->findBy(['user' => $this->getUser()]);

        return $this->render('time_entry/index.html.twig', [
            'time_entries' => $entries,
        ]);
    }

// Création donnée

    #[Route('/new', name: 'app_time_entry_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY'); 

        $timeEntry = new TimeEntry();

        $form = $this->createForm(TimeEntryType::class, $timeEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $timeEntry->setUser($this->getUser());
            $timeEntry->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($timeEntry);
            $entityManager->flush();

            $this->addFlash('success', 'Entrée de temps créée avec succès.');
            return $this->redirectToRoute('app_time_entry_index');
        }

        return $this->render('time_entry/new.html.twig', [
            'time_entry' => $timeEntry,
            'form' => $form->createView(), 
        ]);
    }

// Édition donnée

    #[Route('/{id}/edit', name: 'app_time_entry_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TimeEntry $timeEntry, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY'); 

        // Vérifier permissions
        if ($timeEntry->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas la permission de modifier cette entrée.'); 
        }

        $form = $this->createForm(TimeEntryType::class, $timeEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {     
            $entityManager->flush();

            $this->addFlash('success', 'Entrée de temps modifiée avec succès.');
            return $this->redirectToRoute('app_time_entry_index');
        }

        return $this->render('time_entry/edit.html.twig', [
            'time_entry' => $timeEntry,
            'form' => $form->createView(),
        ]);
    }


// Suppression donnée
    
    #[Route('/{id}', name: 'app_time_entry_delete', methods: ['POST'])]
    public function delete(Request $request, TimeEntry $timeEntry, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY'); 

        // verifier que l'utilisateur est le propriétaire ou un Admin
        if ($timeEntry->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas la permission de supprimer cette entrée.'); 
        }

        if ($this->isCsrfTokenValid('delete'.$timeEntry->getId(), $request->request->get('_token'))) {
            $entityManager->remove($timeEntry);
            $entityManager->flush();
            
            $this->addFlash('success', 'La suppression a été effectuée avec succès.'); 
        }

        return $this->redirectToRoute('app_time_entry_index');
    }
}
