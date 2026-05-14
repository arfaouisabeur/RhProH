<?php

namespace App\Controller;

use App\Repository\RatingRepository;
use App\Service\SentimentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sentiment')]
class SentimentController extends AbstractController
{
    public function __construct(
        private SentimentService $sentimentService,
        private RatingRepository $ratingRepository,
    ) {}

    /**
     * Analyse le sentiment d'UN seul commentaire (appelé par AJAX depuis la liste employé).
     *
     * GET /sentiment/rating/{id}
     */
    #[Route('/rating/{id}', name: 'app_sentiment_rating', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function analyzeRating(int $id): JsonResponse
    {
        $rating = $this->ratingRepository->find($id);

        if (!$rating) {
            return $this->json(['error' => 'Rating introuvable'], 404);
        }

        $result = $this->sentimentService->analyzeComment(
            $rating->getCommentaire(),
            (int) $rating->getEtoiles()
        );

        return $this->json([
            'rating_id' => $id,
            'sentiment' => $result,
        ]);
    }

    /**
     * Analyse TOUS les ratings d'un événement en une seule requête (côté RH).
     *
     * GET /sentiment/evenement/{id}
     */
    #[Route('/evenement/{id}', name: 'app_sentiment_evenement', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function analyzeEvenement(int $id, RatingRepository $ratingRepository): JsonResponse
    {
        $ratings = $ratingRepository->findBy(['evenement' => $id]);

        if (empty($ratings)) {
            return $this->json([
                'total'   => 0,
                'message' => 'Aucun avis pour cet événement.',
            ]);
        }

        $summary = $this->sentimentService->analyzeEvent($ratings);

        return $this->json($summary);
    }
}