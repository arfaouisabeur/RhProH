<?php
// src/Controller/FaceLoginController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Repository\UserRepository;

class FaceLoginController extends AbstractController
{
    #[Route('/face-login', name: 'face_login', methods: ['POST'])]
    public function faceLogin(
        Request $request,
        UserRepository $userRepository,
        UserAuthenticatorInterface $userAuthenticator
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);
        $image = $data['image'] ?? null;

        if (!$image) {
            return new JsonResponse(['success' => false, 'message' => 'Image manquante'], 400);
        }

        // Envoyer l'image à l'API Python Flask
        $ch = curl_init('http://localhost:5002/recognize');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['image' => $image]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (!$result || !$result['success']) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Visage non reconnu'
            ]);
        }

        // Trouver l'utilisateur par ID
        $user = $userRepository->find($result['user_id']);

        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur introuvable']);
        }

        // Connecter l'utilisateur manuellement via la session
        $request->getSession()->set('_security_main', serialize(
            new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
                $user, 'main', $user->getRoles()
            )
        ));

        return new JsonResponse([
            'success' => true,
            'redirect' => $this->generateUrl('app_dashboard') // 👈 change vers ta route
        ]);
    }
}