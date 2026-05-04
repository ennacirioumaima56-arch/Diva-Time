<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class MessengerController extends AbstractController
{
    #[Route('/messenger', name: 'app_messenger')]
    public function index(MessageRepository $messageRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $user->setLastSeenMessagesAt(new \DateTimeImmutable());
        $entityManager->flush();

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            if ($content) {
                $message = new Message();
                $message->setContent($content);
                $message->setSender($user);
                
                $entityManager->persist($message);
                $entityManager->flush();

                // Requête Turbo
                // Sondage marche
                if ($request->headers->get('Turbo-Frame')) {
                    return $this->redirectToRoute('app_messenger_feed');
                }

                return $this->redirectToRoute('app_messenger');
            }
        }

        return $this->render('messenger/index.html.twig', [
            'messages' => $messageRepository->findAllLatest(),
        ]);
    }

    #[Route('/messenger/feed', name: 'app_messenger_feed')]
    public function feed(MessageRepository $messageRepository): Response
    {
        return $this->render('messenger/_feed.html.twig', [
            'messages' => $messageRepository->findAllLatest(),
        ]);
    }

    #[Route('/messenger/count', name: 'app_messenger_count')]
    public function count(MessageRepository $messageRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $count = $messageRepository->countUnreadForUser($user);
        
        $html = '<turbo-frame id="sidebar-messenger-count">';
        if ($count > 0) {
            $html .= '<span class="badge rounded-pill bg-danger" style="font-size: 0.7rem;">' . $count . '</span>';
        }
        $html .= '</turbo-frame>';

        return new Response($html);
    }
}
