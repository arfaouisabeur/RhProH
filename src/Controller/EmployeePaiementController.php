<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employe/paiements')]
#[IsGranted('ROLE_EMPLOYE')]
class EmployeePaiementController extends AbstractController
{
    #[Route('/', name: 'app_employee_paiement_index')]
    public function index(): Response
    {
        return $this->render('employee/paiements/index.html.twig');
    }
}