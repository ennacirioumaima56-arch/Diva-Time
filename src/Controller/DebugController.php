<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Ce contrôleur peut être supprimé en production.
 */
class DebugController extends AbstractController
{
    #[Route('/debug', name: 'app_debug')]
    public function index(): Response
    {
        return new Response('Debug mode active');
    }
}
