<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rh/paiements')]
#[IsGranted('ROLE_RH')]
class RhPaiementController extends AbstractController
{
    #[Route('/', name: 'app_rh_paiement_index')]
    public function index(): Response
    {
        return $this->render('rh/paiements/index.html.twig');
    }
}