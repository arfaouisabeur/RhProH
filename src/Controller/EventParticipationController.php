<?php

namespace App\Controller;

use App\Entity\EventParticipation;
use App\Entity\Evenement;
use App\Entity\Rating;
use App\Service\BadWordService;
use App\Service\GeocodingService;
use App\Service\RecommendationService;
use App\Service\SentimentService;
use App\Service\WeatherService;
use App\Repository\EventParticipationRepository;
use App\Repository\EvenementRepository;
use App\Repository\RatingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/')]
final class EventParticipationController extends AbstractController
{
    // ==========================================================================
    // 🔵 CÔTÉ RH
    // ==========================================================================

    #[Route('/rh/participations', name: 'app_event_participation_index', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function index(
        EventParticipationRepository $repo,
        UserRepository               $userRepository   // ← pour récupérer les bloqués
    ): Response {
        return $this->render('event_participation/index.html.twig', [
            'event_participations' => $repo->findAll(),
            'users_bloques'        => $userRepository->findBy(['statut' => 'bloque']),
        ]);
    }

    #[Route('/rh/participations/evenement/{id}', name: 'app_event_participation_by_event', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function byEvent(
        Evenement                    $evenement,
        EventParticipationRepository $repo,
        UserRepository               $userRepository
    ): Response {
        return $this->render('event_participation/index.html.twig', [
            'event_participations' => $repo->findBy(['evenement' => $evenement]),
            'evenement'            => $evenement,
            'users_bloques'        => $userRepository->findBy(['statut' => 'bloque']),
        ]);
    }

    #[Route('/rh/participations/{id}/accept', name: 'app_event_participation_accept', methods: ['POST'])]
    #[IsGranted('ROLE_RH')]
    public function accept(
        Request                $request,
        EventParticipation     $participation,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('accept' . $participation->getId(), (string) $request->getPayload()->getString('_token'))) {
            $participation->setStatut('accepte');
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_event_participation_index');
    }

    #[Route('/rh/participations/{id}/refuse', name: 'app_event_participation_refuse', methods: ['POST'])]
    #[IsGranted('ROLE_RH')]
    public function refuse(
        Request                $request,
        EventParticipation     $participation,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('refuse' . $participation->getId(), (string) $request->getPayload()->getString('_token'))) {
            $participation->setStatut('refuse');
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_event_participation_index');
    }

    #[Route('/rh/participations/{id}/delete', name: 'app_event_participation_delete', methods: ['POST'])]
    #[IsGranted('ROLE_RH')]
    public function delete(
        Request                $request,
        EventParticipation     $participation,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $participation->getId(), (string) $request->getPayload()->getString('_token'))) {
            $entityManager->remove($participation);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_event_participation_index');
    }

    // ==========================================================================
    // 🔓 DÉBLOCAGE UTILISATEUR — depuis la page des participations
    // ==========================================================================

    #[Route('/rh/participations/debloquer/{id}', name: 'app_participation_debloquer_user', methods: ['POST'])]
    #[IsGranted('ROLE_RH')]
    public function debloquerUser(
        int              $id,
        UserRepository   $userRepository,
        BadWordService   $badWordService,
        Request          $request
    ): Response {
        if (!$this->isCsrfTokenValid('debloquer' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token invalide.');
            return $this->redirectToRoute('app_event_participation_index');
        }

        $user = $userRepository->find($id);

        if (!$user) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_event_participation_index');
        }

        $badWordService->unblock($user);

        $this->addFlash('success', sprintf(
            'Le compte de %s a été débloqué avec succès.',
            $user->getFullName()
        ));

        return $this->redirectToRoute('app_event_participation_index');
    }

    // ==========================================================================
    // 🟢 CÔTÉ EMPLOYÉ
    // ==========================================================================

    #[Route('/employe/evenements', name: 'app_employe_evenements', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function employeIndex(
        EvenementRepository          $evenementRepo,
        EventParticipationRepository $participationRepo
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $employe = $user?->getEmploye();
        $evenements = $evenementRepo->findAll();
        $mesParticipations = [];

        if ($employe) {
            foreach ($participationRepo->findBy(['employe' => $employe]) as $p) {
                $ev = $p->getEvenement();
                if ($ev) {
                    $mesParticipations[$ev->getId()] = $p;
                }
            }
        }

        return $this->render('evenement/employe/evenements.html.twig', [
            'evenements'        => $evenements,
            'mesParticipations' => $mesParticipations,
        ]);
    }

    #[Route('/employe/evenements/recommandations', name: 'app_employe_recommandations', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function recommandations(RecommendationService $recommendationService): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $employe = $user?->getEmploye();

        if (!$employe) {
            return new JsonResponse(['recommandations' => [], 'source' => 'no_user']);
        }

        $recommendations = $recommendationService->getRecommendations($employe);

        if (empty($recommendations)) {
            return new JsonResponse(['recommandations' => [], 'source' => 'no_history']);
        }

        $today  = (new \DateTime())->format('Y-m-d');
        $source = $recommendations[0]['source'] ?? 'embeddings';
        $result = [];

        foreach ($recommendations as $rec) {
            $ev    = $rec['evenement'];
            $debut = $ev->getDateDebut();
            $fin   = $ev->getDateFin();

            $badge = match(true) {
                $debut <= $today && $fin >= $today => 'en_cours',
                $debut > $today                    => 'a_venir',
                default                            => 'termine',
            };

            $result[] = [
                'id'          => $ev->getId(),
                'titre'       => $ev->getTitre(),
                'lieu'        => $ev->getLieu(),
                'date_debut'  => $debut,
                'date_fin'    => $fin,
                'description' => $ev->getDescription(),
                'image_url'   => $ev->getImageUrl(),
                'badge'       => $badge,
                'score'       => $rec['score'],
                'raison'      => $rec['raison'],
                'url_detail'  => $this->generateUrl('app_employe_evenement_show', ['id' => $ev->getId()]),
            ];
        }

        return new JsonResponse(['recommandations' => $result, 'source' => $source]);
    }

    #[Route('/employe/evenements/search', name: 'app_employe_evenements_search', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function searchAjax(
        Request                      $request,
        EvenementRepository          $evenementRepo,
        EventParticipationRepository $participationRepo,
        CsrfTokenManagerInterface    $csrfTokenManager
    ): JsonResponse {
        $q      = trim((string) $request->query->get('q', ''));
        $filtre = $request->query->get('statut', 'tous');
        $today  = (new \DateTime())->format('Y-m-d');
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $employe = $user?->getEmploye();

        $mesParticipationsRaw = [];
        if ($employe) {
            foreach ($participationRepo->findBy(['employe' => $employe]) as $p) {
                $ev = $p->getEvenement();
                if ($ev) {
                    $mesParticipationsRaw[$ev->getId()] = [
                        'statut' => $p->getStatut(),
                        'id'     => $p->getId(),
                    ];
                }
            }
        }

        $all     = $evenementRepo->findAll();
        $results = [];

        foreach ($all as $ev) {
            if ($q !== '') {
                $haystack = mb_strtolower($ev->getTitre() . ' ' . $ev->getLieu() . ' ' . $ev->getDescription());
                if (mb_strpos($haystack, mb_strtolower($q)) === false) continue;
            }

            $debut = $ev->getDateDebut();
            $fin   = $ev->getDateFin();

            if ($filtre === 'a_venir'  && !($debut > $today)) continue;
            if ($filtre === 'en_cours' && !($debut <= $today && $fin >= $today)) continue;
            if ($filtre === 'termine'  && !($fin < $today)) continue;

            $badge = match(true) {
                $debut <= $today && $fin >= $today => 'en_cours',
                $debut > $today                    => 'a_venir',
                default                            => 'termine',
            };

            $results[] = [
                'id'             => $ev->getId(),
                'titre'          => $ev->getTitre(),
                'lieu'           => $ev->getLieu(),
                'date_debut'     => $ev->getDateDebut(),
                'date_fin'       => $ev->getDateFin(),
                'description'    => $ev->getDescription(),
                'image_url'      => $ev->getImageUrl(),
                'activites'      => count($ev->getActivites()),
                'badge'          => $badge,
                'participation'  => $mesParticipationsRaw[$ev->getId()] ?? null,
                'url_detail'     => $this->generateUrl('app_employe_evenement_show', ['id' => $ev->getId()]),
                'url_participer' => $this->generateUrl('app_employe_participer', ['id' => $ev->getId()]),
                'csrf_token'      => $csrfTokenManager->getToken('participer' . $ev->getId())->getValue(),
            ];
        }

        return new JsonResponse(['evenements' => $results, 'total' => count($results)]);
    }

    #[Route('/employe/evenement/{id}', name: 'app_employe_evenement_show', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function employeShow(
        Evenement                    $evenement,
        EventParticipationRepository $participationRepo,
        RatingRepository             $ratingRepo,
        WeatherService               $weatherService,
        GeocodingService             $geocoding,
        EntityManagerInterface       $entityManager
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $employe = $user?->getEmploye();
        $maParticipation = null;
        $monRating       = null;

        if ($employe) {
            $maParticipation = $participationRepo->findOneBy(['evenement' => $evenement, 'employe' => $employe]);
            $monRating       = $ratingRepo->findOneBy(['evenement' => $evenement, 'employe' => $employe]);
        }

        $peutNoter = $maParticipation
            && $maParticipation->getStatut() === 'accepte'
            && $monRating === null;

        // 📍 Auto-géocodage si les coordonnées manquent
        if (!$evenement->getLatitude() || !$evenement->getLongitude()) {
            $coords = $geocoding->geocode((string) $evenement->getLieu());
            if ($coords) {
                $evenement->setLatitude($coords['lat']);
                $evenement->setLongitude($coords['lon']);
                $entityManager->flush(); // On sauvegarde pour les prochaines fois
            }
        }

        $weather = null;
        if ($evenement->getLatitude() && $evenement->getLongitude()) {
            $weather = $weatherService->getWeatherData($evenement->getLatitude(), $evenement->getLongitude());
        }

        return $this->render('evenement/employe/evenement_show.html.twig', [
            'evenement'       => $evenement,
            'maParticipation' => $maParticipation,
            'monRating'       => $monRating,
            'peutNoter'       => $peutNoter,
            'weather'         => $weather,
        ]);
    }

    #[Route('/employe/evenement/{id}/noter', name: 'app_employe_noter', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function submitRating(
        Request                      $request,
        Evenement                    $evenement,
        EventParticipationRepository $participationRepo,
        RatingRepository             $ratingRepo,
        SentimentService             $sentimentService,
        BadWordService               $badWordService,
        EntityManagerInterface       $entityManager
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $employe = $user?->getEmploye();

        if (!$employe) {
            return $this->redirectToRoute('app_employe_evenements');
        }

        $participation = $participationRepo->findOneBy(['evenement' => $evenement, 'employe' => $employe]);

        if (!$participation || $participation->getStatut() !== 'accepte') {
            $this->addFlash('danger', 'Vous devez avoir une participation acceptée pour donner un avis.');
            return $this->redirectToRoute('app_employe_evenement_show', ['id' => $evenement->getId()]);
        }

        $existingRating = $ratingRepo->findOneBy(['evenement' => $evenement, 'employe' => $employe]);

        if ($existingRating) {
            $this->addFlash('warning', 'Vous avez déjà donné un avis pour cet événement.');
            return $this->redirectToRoute('app_employe_evenement_show', ['id' => $evenement->getId()]);
        }

        if (!$this->isCsrfTokenValid('noter' . $evenement->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_employe_evenement_show', ['id' => $evenement->getId()]);
        }

        $commentaire = trim((string) $request->request->get('commentaire', ''));

        if (empty($commentaire) || mb_strlen($commentaire) < 10) {
            $this->addFlash('danger', 'Votre commentaire doit contenir au moins 10 caractères.');
            return $this->redirectToRoute('app_employe_evenement_show', ['id' => $evenement->getId()]);
        }

        // ── 🚨 VÉRIFICATION BAD WORDS ─────────────────────────────────────────
        /** @var \App\Entity\User $userToPass */
        $userToPass = $user;
        $badResult = $badWordService->check($commentaire, $userToPass);

        if ($badResult['blocked']) {
            $this->addFlash('danger', $badResult['message']);
            return $this->redirectToRoute('app_employe_evenement_show', ['id' => $evenement->getId()]);
        }
        // ── FIN VÉRIFICATION ──────────────────────────────────────────────────

        $analysis = $sentimentService->detectStars($commentaire);
        $etoiles  = $analysis['stars'];

        $rating = new Rating();
        $rating->setEvenement($evenement);
        $rating->setEmploye($employe);
        $rating->setCommentaire($commentaire);
        $rating->setEtoiles((string) $etoiles);
        $rating->setDateCreation((new \DateTime())->format('Y-m-d H:i:s'));

        $entityManager->persist($rating);
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'Merci pour votre avis ! L\'IA a attribué %d étoile%s %s à votre commentaire.',
            $etoiles,
            $etoiles > 1 ? 's' : '',
            $analysis['emoji']
        ));

        return $this->redirectToRoute('app_employe_evenement_show', ['id' => $evenement->getId()]);
    }

    #[Route('/employe/evenement/{id}/participer', name: 'app_employe_participer', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function participer(
        Request                      $request,
        Evenement                    $evenement,
        EventParticipationRepository $participationRepo,
        EntityManagerInterface       $entityManager
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $employe = $user?->getEmploye();

        if (!$employe) {
            $this->addFlash('danger', 'Votre profil employé est incomplet. Veuillez contacter l\'administration.');
            return $this->redirectToRoute('app_employe_evenements');
        }

        // Vérification CSRF
        if (!$this->isCsrfTokenValid('participer' . $evenement->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_employe_evenements');
        }

        $existing = $participationRepo->findOneBy(['evenement' => $evenement, 'employe' => $employe]);

        if ($existing) {
            $this->addFlash('info', 'Vous êtes déjà inscrit à cet événement.');
            return $this->redirectToRoute('app_employe_evenements');
        }

        $participation = new EventParticipation();
        $participation->setEvenement($evenement);
        $participation->setEmploye($employe);
        $participation->setStatut('en_attente');
        $participation->setDateInscription((new \DateTime())->format('Y-m-d'));

        $entityManager->persist($participation);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande de participation a été enregistrée avec succès !');

        return $this->redirectToRoute('app_employe_evenements');
    }

    #[Route('/employe/evenement/{id}/annuler', name: 'app_employe_annuler_participation', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function annuler(
        Request                $request,
        EventParticipation     $participation,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $employe = $user?->getEmploye();

        if ($participation->getEmploye() !== $employe) {
            return $this->redirectToRoute('app_employe_evenements');
        }

        if ($this->isCsrfTokenValid('annuler' . $participation->getId(), (string) $request->getPayload()->getString('_token'))) {
            $entityManager->remove($participation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_employe_evenements');
    }

    #[Route('/employe/mes-participations', name: 'app_employe_mes_participations', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function mesParticipations(EventParticipationRepository $repo): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $employe        = $user?->getEmploye();
        $participations = [];

        if ($employe) {
            $participations = $repo->findBy(['employe' => $employe], ['id' => 'DESC']);
        }

        return $this->render('evenement/employe/mes_participations.html.twig', [
            'participations' => $participations,
        ]);
    }
}