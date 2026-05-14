<?php

namespace App\Controller;

use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ChatbotController extends AbstractController
{
    /**
     * Endpoint PHP proxy pour le chatbot.
     * Reçoit le message du JS, construit le contexte depuis la DB,
     * envoie à Python et retourne la réponse.
     */
    #[Route('/chatbot/ask', name: 'app_chatbot_ask', methods: ['POST'])]
    public function ask(Request $request, ChatbotService $chatbotService): JsonResponse
    {
        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (empty($message)) {
            return new JsonResponse(['erreur' => 'Message vide'], 400);
        }

        try {
            $result = $chatbotService->poserQuestion($message);
            return new JsonResponse($result);
        } catch (\Throwable $e) {
            error_log('[ChatbotController] Erreur: ' . $e->getMessage());
            return new JsonResponse([
                'reponse'   => '⚠️ Le chatbot est temporairement indisponible. Vérifiez que le serveur Python tourne sur le port 5001.',
                'intention' => 'erreur',
                'confiance' => 0,
                'langue'    => 'fr',
                'timestamp' => date('H:i'),
            ]);
        }
    }
}
