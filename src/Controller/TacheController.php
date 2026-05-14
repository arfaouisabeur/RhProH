<?php

namespace App\Controller;

use App\Entity\Tache;
use App\Entity\Projet;
use App\Form\TacheType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tache')]
final class TacheController extends AbstractController
{
    /**
     * RH → voit toutes les tâches
     */
    #[Route(name: 'app_tache_index', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $taches = $entityManager
            ->getRepository(Tache::class)
            ->findAll();

        return $this->render('tache/index.html.twig', [
            'taches' => $taches,
        ]);
    }

    /**
     * Employé → liste les tâches d'un projet spécifique
     * Accessible via le bouton "Gérer les tâches" depuis employe_index.html.twig
     */
    #[Route('/projet/{projetId}', name: 'app_tache_par_projet', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function tachesParProjet(
        int $projetId,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $employe = $user->getEmploye();

        $projet = $entityManager->getRepository(Projet::class)->find($projetId);

        if (!$projet) {
            throw $this->createNotFoundException('Projet introuvable.');
        }

        // Sécurité : l'employé doit être le responsable du projet (sauf si ROLE_RH)
        if (!$this->isGranted('ROLE_RH')) {
            if ($projet->getResponsableEmploye() !== $employe) {
                $this->addFlash('error', 'Vous n\'êtes pas autorisé à accéder à ce projet.');
                return $this->redirectToRoute('app_projet_employe_index');
            }
        }

        $taches = $entityManager
            ->getRepository(Tache::class)
            ->findBy(['projet' => $projet]);

        return $this->render('tache/employe_index.html.twig', [
            'taches'         => $taches,
            'projet'         => $projet,
            'calendarEvents' => $this->buildCalendarEvents($taches),
        ]);
    }

    /**
     * @param Tache[] $taches
     * @return array<int, array<string, mixed>>
     */
    private function buildCalendarEvents(array $taches): array
    {
        /** @var array<string, array{bg: string, border: string}> $couleurs */
        $couleurs = [
            'a_faire'  => ['bg' => '#6b2d8b', 'border' => '#5a2474'],
            'en_cours' => ['bg' => '#f59e0b', 'border' => '#d97706'],
            'terminee' => ['bg' => '#10b981', 'border' => '#059669'],
            'bloquee'  => ['bg' => '#ef4444', 'border' => '#dc2626'],
        ];

        /** @var array<int, array<string, mixed>> $events */
        $events = [];
        foreach ($taches as $t) {
            $statut  = $t->getStatut() ?? 'a_faire';
            $couleur = $couleurs[$statut] ?? $couleurs['a_faire'];
            
            $dateFin = $t->getDateFin();
            $end = null;
            if ($dateFin instanceof \DateTime) {
                $modified = (clone $dateFin)->modify('+1 day');
                $end = $modified->format('Y-m-d');
            } elseif ($dateFin instanceof \DateTimeImmutable) {
                $end = $dateFin->modify('+1 day')->format('Y-m-d');
            }

            $events[] = [
                'id'              => $t->getId(),
                'title'           => $t->getTitre(),
                'start'           => $t->getDateDebut()->format('Y-m-d'),
                'end'             => $end,
                'backgroundColor' => $couleur['bg'],
                'borderColor'     => $couleur['border'],
                'textColor'       => '#ffffff',
                'extendedProps'   => [
                    'statut'      => $statut,
                    'priorite'    => $t->getLevel(),
                    'description' => $t->getDescription(),
                ],
            ];
        }

        return $events;
    }

    /**
     * API JSON — tâches d'un projet pour FullCalendar
     */
    #[Route('/projet/{projetId}/calendar-api', name: 'app_tache_calendar_api', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function calendarApi(int $projetId, EntityManagerInterface $em): JsonResponse
    {
        $projet = $em->getRepository(Projet::class)->find($projetId);
        if (!$projet) {
            return new JsonResponse([], 404);
        }

        $taches = $em->getRepository(Tache::class)->findBy(['projet' => $projet]);

        $couleurs = [
            'a_faire'  => ['bg' => '#6b2d8b', 'border' => '#5a2474'],
            'en_cours' => ['bg' => '#f59e0b', 'border' => '#d97706'],
            'terminee' => ['bg' => '#10b981', 'border' => '#059669'],
            'bloquee'  => ['bg' => '#ef4444', 'border' => '#dc2626'],
        ];

        /** @var array<int, array<string, mixed>> $events */
        $events = [];
        foreach ($taches as $t) {
            $statut  = $t->getStatut() ?? 'a_faire';
            $couleur = $couleurs[$statut] ?? $couleurs['a_faire'];

            $dateFin = $t->getDateFin();
            $end = null;
            if ($dateFin instanceof \DateTime) {
                $modified = (clone $dateFin)->modify('+1 day');
                $end = $modified->format('Y-m-d');
            } elseif ($dateFin instanceof \DateTimeImmutable) {
                $end = $dateFin->modify('+1 day')->format('Y-m-d');
            }

            $events[] = [
                'id'              => $t->getId(),
                'title'           => $t->getTitre(),
                'start'           => $t->getDateDebut()->format('Y-m-d'),
                'end'             => $end,
                'backgroundColor' => $couleur['bg'],
                'borderColor'     => $couleur['border'],
                'textColor'       => '#ffffff',
                'extendedProps'   => [
                    'statut'      => $statut,
                    'priorite'    => $t->getLevel(),
                    'description' => $t->getDescription(),
                ],
            ];
        }

        return new JsonResponse($events);
    }

    /**
     * RH → créer une tâche (formulaire complet)
     */
    #[Route('/new', name: 'app_tache_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_RH')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tache = new Tache();
        $form = $this->createForm(TacheType::class, $tache, ['is_employe' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tache);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche créée avec succès.');
            return $this->redirectToRoute('app_tache_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tache/new.html.twig', [
            'tache'  => $tache,
            'form'   => $form,
            'projet' => null,
        ]);
    }

    /**
     * Employé → créer une tâche dans un projet spécifique
     * Le projet et l'employé sont pré-remplis automatiquement
     */
    #[Route('/new/projet/{projetId}', name: 'app_tache_new_pour_projet', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function newPourProjet(
        int $projetId,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $employe = $user->getEmploye();

        $projet = $entityManager->getRepository(Projet::class)->find($projetId);

        if (!$projet) {
            throw $this->createNotFoundException('Projet introuvable.');
        }

        // Sécurité : seul le responsable du projet peut créer des tâches
        if ($projet->getResponsableEmploye() !== $employe) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à créer des tâches pour ce projet.');
            return $this->redirectToRoute('app_projet_employe_index');
        }

        $tache = new Tache();
        $tache->setProjet($projet);
        if ($employe) {
            $tache->setEmploye($employe);
        }

        $form = $this->createForm(TacheType::class, $tache, ['is_employe' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tache);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche créée avec succès.');
            return $this->redirectToRoute('app_tache_par_projet', [
                'projetId' => $projet->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tache/new.html.twig', [
            'tache'  => $tache,
            'form'   => $form,
            'projet' => $projet,
        ]);
    }

    /**
     * RH + Employé → voir le détail d'une tâche
     */
    #[Route('/{id}', name: 'app_tache_show', methods: ['GET'])]
    public function show(Tache $tache): Response
    {
        return $this->render('tache/show.html.twig', [
            'tache' => $tache,
        ]);
    }

    /**
     * RH + Employé → modifier une tâche
     * L'employé ne peut modifier que les tâches de ses propres projets
     */
    #[Route('/{id}/edit', name: 'app_tache_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Tache $tache, EntityManagerInterface $entityManager, \App\Service\TaskMailerService $taskMailer): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user->isEmploye()) {
            $employe = $user->getEmploye();
            if ($tache->getProjet()->getResponsableEmploye() !== $employe) {
                $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier cette tâche.');
                return $this->redirectToRoute('app_projet_employe_index');
            }
        }

        $oldStatus = $tache->getStatut();

        $form = $this->createForm(TacheType::class, $tache, [
            'is_employe' => $user->isEmploye(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Si la tâche passe à terminée, on envoie l'email
            if (strtolower($oldStatus ?? '') !== 'terminee' && strtolower((string)$tache->getStatut()) === 'terminee') {
                $rh = $tache->getProjet()->getRh();
                if ($rh && $rh->getUser() && $rh->getUser()->getEmail() && filter_var($rh->getUser()->getEmail(), FILTER_VALIDATE_EMAIL)) {
                    try {
                        $taskMailer->sendTaskCompletedEmail($tache);
                        $this->addFlash('info', 'Un e-mail de notification a été envoyé au responsable RH (' . $rh->getUser()->getEmail() . ').');
                    } catch (\Exception $e) {
                        // Email failed but don't block the user - just log it
                        error_log('[TaskMailer] Failed to send email: ' . $e->getMessage());
                        $this->addFlash('warning', 'Tâche modifiée avec succès, mais l\'e-mail de notification n\'a pas pu être envoyé (problème de connexion SMTP).');
                    }
                } else {
                    $this->addFlash('warning', 'Alerte e-mail ignorée : le responsable RH du projet n\'a pas d\'adresse e-mail valide (' . ($rh?->getUser()?->getEmail() ?? 'aucun') . ').');
                }
            } else {
                $this->addFlash('success', 'Tâche modifiée avec succès.');
            }

            // Redirection selon le rôle
            if ($user->isEmploye()) {
                return $this->redirectToRoute('app_tache_par_projet', [
                    'projetId' => $tache->getProjet()->getId(),
                ], Response::HTTP_SEE_OTHER);
            }
            return $this->redirectToRoute('app_tache_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tache/edit.html.twig', [
            'tache' => $tache,
            'form'  => $form,
        ]);
    }

    /**
     * RH + Employé → supprimer une tâche
     * L'employé ne peut supprimer que les tâches de ses propres projets
     */
    #[Route('/{id}', name: 'app_tache_delete', methods: ['POST'])]
    public function delete(Request $request, Tache $tache, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $projetId = $tache->getProjet()->getId();

        if ($user->isEmploye()) {
            $employe = $user->getEmploye();
            if ($tache->getProjet()->getResponsableEmploye() !== $employe) {
                $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cette tâche.');
                return $this->redirectToRoute('app_projet_employe_index');
            }
        }

        if ($this->isCsrfTokenValid('delete'.$tache->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tache);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche supprimée avec succès.');
        }

        // Redirection selon le rôle
        if ($user->isEmploye() && $projetId) {
            return $this->redirectToRoute('app_tache_par_projet', [
                'projetId' => $projetId,
            ], Response::HTTP_SEE_OTHER);
        }
        return $this->redirectToRoute('app_tache_index', [], Response::HTTP_SEE_OTHER);
    }
}