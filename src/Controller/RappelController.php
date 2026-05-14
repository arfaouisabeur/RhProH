<?php

namespace App\Controller;

use App\Service\RappelEvenementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employe/rappels')]
#[IsGranted('ROLE_EMPLOYE')]
class RappelController extends AbstractController
{
    public function __construct(
        private RappelEvenementService $rappelService,
    ) {}

    /**
     * Page complète des rappels.
     * GET /employe/rappels
     */
    #[Route('', name: 'app_rappel_index', methods: ['GET'])]
    public function index(): Response
    {
        $employe = $this->getUser()?->getEmploye();

        if (!$employe) {
            return $this->redirectToRoute('app_home');
        }

        $rappels = $this->rappelService->getRappels($employe);

        return $this->render('rappel/index.html.twig', [
            'rappels' => $rappels,
        ]);
    }

    /**
     * Endpoint JSON appelé en AJAX par la navbar pour mettre à jour le badge.
     * GET /employe/rappels/count
     */
    #[Route('/count', name: 'app_rappel_count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        $employe = $this->getUser()?->getEmploye();

        if (!$employe) {
            return $this->json(['count' => 0, 'rappels' => []]);
        }

        $rappels = $this->rappelService->getRappels($employe);

        // Formater les données pour le JSON
        $data = array_map(fn($p) => [
            'id'             => $p->getId(),
            'titre'          => $p->getEvenement()->getTitre(),
            'lieu'           => $p->getEvenement()->getLieu(),
            'date_debut'     => $p->getEvenement()->getDateDebut(),
            'is_cancelled'   => str_starts_with($p->getEvenement()->getTitre(), '[ANNULÉ]'),
            'url'            => $this->generateUrl('app_employe_evenement_show', [
                'id' => $p->getEvenement()->getId(),
            ]),
        ], $rappels);

        return $this->json([
            'count'   => count($rappels),
            'rappels' => $data,
        ]);
    }
}