<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Logic Hashing password
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );
            }

            // Gestion roles
            $roleSelection = $form->get('roles')->getData();
            $user->setRoles([$roleSelection]);

            // Auto-verify admin-created users
            $user->setIsVerified(true);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Member added successfully! They can now access the platform.');
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        // On passe 'is_new' => false hit password machi obligatory f edit
        $form = $this->createForm(UserType::class, $user, ['is_new' => false]);
        
        // On remplit form role b dakchi li 3end l user deja (on prend le premier role)
        $currentRoles = $user->getRoles();
        if (!empty($currentRoles)) {
            // Normalement Symfony met tjours ROLE_USER, on cherche le plus haut ou le premier non-ROLE_USER
            $displayRole = in_array('ROLE_ADMIN', $currentRoles) ? 'ROLE_ADMIN' : 'ROLE_USER';
            $form->get('roles')->setData($displayRole);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Re-hash password ila tbdal
            $plainPassword = $form->get('password')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );
            }
            
            // Update roles
            $roleSelection = $form->get('roles')->getData();
            $user->setRoles([$roleSelection]);

            $entityManager->flush();

            $this->addFlash('success', 'Member profile updated successfully.');
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            // Prevent self-deletion if current user is the one being deleted
            if ($user === $this->getUser()) {
                $this->addFlash('danger', 'You cannot delete your own account.');
                return $this->redirectToRoute('app_user_index');
            }

            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Member has been removed from the team.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
