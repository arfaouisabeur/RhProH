<?php
namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connectAction(ClientRegistry $clientRegistry, Request $request): Response
    {
        // On forwarde le "type" d'inscription dans la session si on vient de register_candidat ou register_employe
        $type = $request->query->get('type');
        if ($type) {
            $request->getSession()->set('oauth_registration_type', $type);
        }

        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile']); // scopes
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
        // Laissé vide, l'authenticator va intercepter cette route
    }
}