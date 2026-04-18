<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/debug/seed', name: 'app_debug_seed')]
    public function seed(
        \Doctrine\ORM\EntityManagerInterface $entityManager, 
        \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $userPasswordHasher,
        \App\Repository\UserRepository $userRepository
    ): Response {
        $conn = $entityManager->getConnection();
        
        // Fix the typo 'dete' to 'date' in the database if it exists
        try {
            $conn->executeStatement('ALTER TABLE time_entry CHANGE dete date DATE NOT NULL');
        } catch (\Exception $e) {
            // Probably already fixed or column doesn't exist yet
        }

        $usersToCreate = [
            ['email' => 'admin@divatime.com', 'name' => 'Admin Diva', 'roles' => ['ROLE_ADMIN'], 'password' => 'password'],
            ['email' => 'user@divatime.com', 'name' => 'John Doe', 'roles' => ['ROLE_USER'], 'password' => 'password'],
        ];

        $created = [];
        foreach ($usersToCreate as $data) {
            $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
            if (!$existingUser) {
                $user = new \App\Entity\User();
                $user->setEmail($data['email']);
                $user->setName($data['name']);
                $user->setRoles($data['roles']);
                $user->setPassword($userPasswordHasher->hashPassword($user, $data['password']));
                $user->setIsVerified(true);
                $entityManager->persist($user);
                $created[] = $data['email'];
            }
        }

        $entityManager->flush();

        return new Response('Database fixed and seeded! <br> Created: ' . implode(', ', $created) . '<br> Login at /login with password "password"');
    }

    #[Route(path: '/debug/users', name: 'app_debug_users')]
    public function debugUsers(\App\Repository\UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $html = '<table border="1" style="width:100%; border-collapse:collapse; text-align:left;">';
        $html .= '<tr><th>Email</th><th>Name</th><th>Roles</th><th>Is Verified</th><th>Password Starts With</th><th>Is Hashed?</th></tr>';

        foreach ($users as $user) {
            $pass = $user->getPassword();
            $isHashed = (str_starts_with($pass, '$2y$') || str_starts_with($pass, '$argon2id$')) ? "✅ YES" : "❌ NO (Plain Text?)";
            $html .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><code>%s</code></td><td>%s</td></tr>',
                $user->getEmail(),
                $user->getName() ?? 'N/A',
                implode(', ', $user->getRoles()),
                $user->isVerified() ? "✅ Yes" : "❌ No",
                substr($pass, 0, 10),
                $isHashed
            );
        }
        $html .= '</table>';
        
        return new Response('<h3>DivaTime User Status Debug</h3>' . $html);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
