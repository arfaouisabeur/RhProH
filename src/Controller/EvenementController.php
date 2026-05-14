<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Activite;
use App\Form\EvenementType;
use App\Form\ActiviteType;
use App\Repository\EvenementRepository;
use App\Repository\EventParticipationRepository;
use App\Service\GeocodingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\ParticipationPredictionService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/rh/evenement')]
#[IsGranted('ROLE_RH')]
class EvenementController extends AbstractController
{
    #[Route('', name: 'app_evenement_index')]
    public function index(
        EvenementRepository          $repo,
        EventParticipationRepository $participationRepo,
        \App\Repository\UserRepository $userRepository
    ): Response {
        return $this->render('evenement/index.html.twig', [
            'evenements'           => $repo->findBy([], ['date_debut' => 'DESC']),
            'event_participations' => $participationRepo->findAll(),
            'users_bloques'        => $userRepository->findBy(['statut' => 'bloque']),
        ]);
    }

    #[Route('/new', name: 'app_evenement_new')]
    public function new(Request $request, EntityManagerInterface $em, GeocodingService $geocoding, SluggerInterface $slugger): Response
    {
        $evenement = new Evenement();

        // Auto-assign the currently logged-in RH
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if ($user && $user->getRh()) {
            $evenement->setRh($user->getRh());
        }

        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $evenement->setDateDebut(substr((string) $evenement->getDateDebut(), 0, 10));
            $evenement->setDateFin(substr((string) $evenement->getDateFin(), 0, 10));

            // Ensure rh is still set after form binding
            if ($user && $user->getRh() && $evenement->getRh() === null) {
                $evenement->setRh($user->getRh());
            }

            if ($form->isValid()) {
                // 🌍 Géocodage automatique du lieu via Nominatim
                $coords = $geocoding->geocode((string) $evenement->getLieu());
                if ($coords) {
                    $evenement->setLatitude($coords['lat']);
                    $evenement->setLongitude($coords['lon']);
                }

                // 🖼️ Upload image
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/evenements',
                            $newFilename
                        );
                        $evenement->setImageUrl('/uploads/evenements/' . $newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('warning', 'Erreur lors de l\'upload de l\'image.');
                    }
                }

                $em->persist($evenement);
                $em->flush();
                $this->addFlash('success', 'Événement créé avec succès.');
                return $this->redirectToRoute('app_evenement_index');
            }
        }

        return $this->render('evenement/new.html.twig', ['form' => $form]);
    }

    #[Route('/predict', name: 'app_evenement_predict', methods: ['GET'])]
    public function predict(
        Request                         $request,
        ParticipationPredictionService  $predictionService
    ): JsonResponse {
        $titre     = $request->query->get('titre', '');
        $lieu      = $request->query->get('lieu', '');
        $dateDebut = $request->query->get('date_debut', '');

        if (empty($titre) || empty($dateDebut)) {
            return new JsonResponse([
                'error' => 'Paramètres manquants (titre et date requis)',
                'details' => [
                    'titre' => empty($titre) ? 'manquant' : 'ok',
                    'date_debut' => empty($dateDebut) ? 'manquant' : 'ok',
                ]
            ], 400);
        }

        // Si le lieu est vide, on met une valeur par défaut pour l'IA
        if (empty($lieu)) {
            $lieu = 'Inconnu';
        }

        try {
            $result = $predictionService->predict($titre, $lieu, $dateDebut);
            return new JsonResponse($result);
        } catch (\Exception $e) {
            error_log('Prediction Error: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Erreur lors de la prédiction : ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/{id}/analyze-reviews', name: 'app_evenement_analyze_reviews', methods: ['GET'])]
    public function analyzeReviews(
        Evenement               $evenement,
        EntityManagerInterface  $em,
        \App\Service\ReviewAnalysisService $analysisService
    ): JsonResponse {
        $ratings = $em->getRepository(\App\Entity\Rating::class)->findBy(['evenement' => $evenement]);
        $analysis = $analysisService->analyzeEventReviews($ratings);
        $response = new JsonResponse($analysis);
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        return $response;
    }

    #[Route('/{id}', name: 'app_evenement_show')]
    public function show(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $em,
        EventParticipationRepository $participationRepo
    ): Response {
        $activite = new Activite();
        $activite->setEvenement($evenement);
        $activiteForm = $this->createForm(ActiviteType::class, $activite);
        $activiteForm->handleRequest($request);

        if ($activiteForm->isSubmitted() && $activiteForm->isValid()) {
            $em->persist($activite);
            $em->flush();
            $this->addFlash('success', 'Activité ajoutée.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        $participations = $participationRepo->findBy(['evenement' => $evenement]);

        return $this->render('evenement/show.html.twig', [
            'evenement'      => $evenement,
            'activiteForm'   => $activiteForm,
            'participations' => $participations,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $em, GeocodingService $geocoding, SluggerInterface $slugger): Response
    {
        // Remember the original RH in case form clears it
        $originalRh = $evenement->getRh();
        $originalLieu = $evenement->getLieu();
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Fix: Ensure date fields have valid datetime format before form creation
        $dateDebut = $evenement->getDateDebut();
        $dateFin = $evenement->getDateFin();
        
        // Check if date_debut is empty, null, or invalid format
        if (empty($dateDebut) || !preg_match('/^\d{4}-\d{2}-\d{2}/', $dateDebut)) {
            $evenement->setDateDebut(date('Y-m-d H:i:s'));
        }
        
        // Check if date_fin is empty, null, or invalid format
        if (empty($dateFin) || !preg_match('/^\d{4}-\d{2}-\d{2}/', $dateFin)) {
            $evenement->setDateFin(date('Y-m-d H:i:s'));
        }

        $form = $this->createForm(EvenementType::class, $evenement, [
            'csrf_protection'  => false, // ✅ fix: custom date inputs bypass CSRF widget
            'validation_groups' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $evenement->setDateDebut(substr((string) $evenement->getDateDebut(), 0, 10));
            $evenement->setDateFin(substr((string) $evenement->getDateFin(), 0, 10));

            // Restore or assign RH if lost during form binding
            if ($evenement->getRh() === null) {
                if ($originalRh) {
                    $evenement->setRh($originalRh);
                } elseif ($user && $user->getRh()) {
                    $evenement->setRh($user->getRh());
                }
            }

            if ($form->isValid()) {
                // 🌍 Re-géocoder uniquement si le lieu a changé
                if ($evenement->getLieu() !== $originalLieu) {
                    $coords = $geocoding->geocode((string) $evenement->getLieu());
                    if ($coords) {
                        $evenement->setLatitude($coords['lat']);
                        $evenement->setLongitude($coords['lon']);
                    } else {
                        $evenement->setLatitude(null);
                        $evenement->setLongitude(null);
                    }
                }

                // 🖼️ Upload image
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/evenements',
                            $newFilename
                        );
                        $evenement->setImageUrl('/uploads/evenements/' . $newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('warning', 'Erreur lors de l\'upload de l\'image.');
                    }
                }

                $em->flush();
                $this->addFlash('success', 'Événement modifié avec succès.');
                return $this->redirectToRoute('app_evenement_index');
            }
        }

        return $this->render('evenement/edit.html.twig', [
            'form'      => $form,
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/annuler', name: 'app_evenement_annuler')]
    public function annuler(Evenement $evenement, EntityManagerInterface $em): Response
    {
        $prefix = '[ANNULÉ] ';
        if (!str_starts_with((string) $evenement->getTitre(), '[ANNULÉ] ')) {
            $evenement->setTitre($prefix . $evenement->getTitre());
            $em->flush();
            $this->addFlash('success', 'L\'événement a été marqué comme annulé.');
        } else {
            $this->addFlash('warning', 'Cet événement est déjà annulé.');
        }

        return $this->redirectToRoute('app_evenement_index');
    }

    #[Route('/{id}/delete', name: 'app_evenement_delete')]
    public function delete(Evenement $evenement, EntityManagerInterface $em): Response
    {
        $em->remove($evenement);
        $em->flush();
        $this->addFlash('success', 'Événement supprimé.');
        return $this->redirectToRoute('app_evenement_index');
    }
}