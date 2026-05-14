<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employe/calendrier')]
#[IsGranted('ROLE_EMPLOYE')]
class CalendarController extends AbstractController
{
    /**
     * Page du calendrier qui utilise le bundle.
     */
    #[Route('', name: 'app_calendar_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('calendar/index.html.twig');
    }
}
