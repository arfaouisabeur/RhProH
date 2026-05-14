<?php
 
namespace App\Controller;
 
use App\Entity\Projet;
use App\Form\ProjetType;
use App\Repository\ProjetRepository;
use App\Service\EmployeScoreService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Dompdf\Dompdf;
use Dompdf\Options;
 
class ProjetController extends AbstractController
{
    /**
     * RH → voit tous les projets avec recherche (titre, responsable, statut)
     */
    #[Route('/rh/projet', name: 'app_projet_index', methods: ['GET'])]
    public function index(Request $request, ProjetRepository $projetRepository, EmployeScoreService $scoreService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user->isEmploye()) {
            return $this->redirectToRoute('app_projet_employe_index');
        }

        $q      = $request->query->get('q');
        $statut = $request->query->get('statut');

        $projets = $projetRepository->search($q, $statut ?: null);
        $stats   = $projetRepository->getStatusStats();

        /** @var array<string, int> $statsData */
        $statsData = [];
        foreach ($stats as $s) {
            $statsData[$s['statut'] ?: 'non_defini'] = (int)$s['count'];
        }

        // Trouver le meilleur employé parmi les responsables des projets affichés
        /** @var array<int, array{score: float|int, niveau: string}> $scoresEmployes */
        $scoresEmployes = [];
        $meilleurId     = null;
        $meilleurScore  = -1;

        foreach ($projets as $projet) {
            $emp = $projet->getResponsableEmploye();
            if ($emp !== null) {
                $id = $emp->getUserId();
                if (!isset($scoresEmployes[$id])) {
                    $score = $scoreService->calculerScore($emp);
                    $scoresEmployes[$id] = [
                        'score'  => $score,
                        'niveau' => $scoreService->getNiveau($emp),
                    ];
                    if ($score > $meilleurScore) {
                        $meilleurScore = $score;
                        $meilleurId    = $id;
                    }
                }
            }
        }

        // On ne garde que l'ID du meilleur pour le template
        $meilleurEmployeId = $meilleurId;

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->render('projet/_projet_table.html.twig', [
                'projets'           => $projets,
                'meilleurEmployeId' => $meilleurEmployeId,
            ]);
        }

        return $this->render('projet/index.html.twig', [
            'projets'           => $projets,
            'q'                 => $q,
            'statut'            => $statut,
            'statsData'         => $statsData,
            'meilleurEmployeId' => $meilleurEmployeId,
        ]);
    }

    /**
     * RH → Export des projets en PDF
     */
    #[Route('/rh/projet/export/pdf', name: 'app_projet_export_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function exportPdf(Request $request, ProjetRepository $projetRepository): Response
    {
        $q = $request->query->get('q');
        $statut = $request->query->get('statut');
        $projets = $projetRepository->search($q, $statut ?: null);

        $html = $this->renderView('rh/projet_export_pdf.html.twig', [
            'projets' => $projets,
            'date' => new \DateTime(),
        ]);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="export_projets_' . date('Y-m-d') . '.pdf"',
        ]);
    }

    /**
     * RH → Export des statistiques en PDF (Graphique SVG natif)
     */
    #[Route('/rh/projet/export/stats', name: 'app_projet_export_stats', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_RH')]
    public function exportStatsPdf(ProjetRepository $projetRepository): Response
    {
        $stats = $projetRepository->getStatusStats();
        
        $total = 0;
        foreach ($stats as $s) { $total += $s['count']; }

        $colors = [
            'termine' => '#10b981',
            'en_cours' => '#f59e0b',
            'en_attente' => '#8b5cf6',
            'annule' => '#ef4444'
        ];

        /** @var array<int, array<string, mixed>> $slices */
        $slices = [];
        $currentOffset = 0;
        $circumference = 753.9822; // Circonférence pour un rayon de 120
        
        foreach ($stats as $s) {
            $percentage = $total > 0 ? ($s['count'] / $total) * 100 : 0;
            
            // Dash = portion de la circonférence
            $dash = ($percentage / 100) * $circumference;
            
            // Offset = rotation pour commencer à la fin du segment précédent
            // On commence à -90 deg de base (SVG stroke-dashoffset logic)
            $offset = $circumference - (($currentOffset / 100) * $circumference);

            $slices[] = [
                'statut' => $s['statut'],
                'count' => $s['count'],
                'percentage' => round($percentage, 0),
                'offset' => $offset,
                'dash' => $dash,
                'circumference' => $circumference,
                'color' => $colors[$s['statut']] ?? '#cbd5e1'
            ];
            $currentOffset += $percentage;
        }

        $html = $this->renderView('rh/projet_stats_pdf.html.twig', [
            'stats'  => $stats,
            'slices' => $slices,
            'total'  => $total,
            'date'   => new \DateTime(),
        ]);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="stats_projets_' . date('Y-m-d') . '.pdf"',
        ]);
    }
 
    /**
     * Employé → voit uniquement ses projets assignés avec statistiques de tâches
     */
    #[Route('/projet', name: 'app_projet_employe_index', methods: ['GET'])]
    #[IsGranted('ROLE_EMPLOYE')]
    public function mesProjects(EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $employe = $user->getEmploye();
 
        $projets = $entityManager
            ->getRepository(Projet::class)
            ->findBy(['responsable_employe' => $employe]);
 
        /** @var array<int, array<string, mixed>> $projetsData */
        $projetsData = [];
        $totalProjectsCount = count($projets);
        $totalTasksCount = 0;
        $completedProjectsCount = 0;
 
        foreach ($projets as $projet) {
            $totalTasks = $entityManager->getRepository(\App\Entity\Tache::class)->count(['projet' => $projet]);
            $completedTasks = $entityManager->getRepository(\App\Entity\Tache::class)->count([
                'projet' => $projet,
                'statut' => 'terminee'
            ]);
            
            $progress = $totalTasks > 0 ? (int)round(($completedTasks / $totalTasks) * 100) : 0; // @phpstan-ignore-line
            if ($progress === 100) {
                $completedProjectsCount++;
            }
            
            $totalTasksCount += $totalTasks;
            
            $projetsData[] = [
                'entity'         => $projet,
                'progress'       => $progress,
                'totalTasks'     => $totalTasks,
                'completedTasks' => $completedTasks
            ];
        }
 
        return $this->render('projet/employe_index.html.twig', [
            'projetsData'           => $projetsData,
            'totalProjects'         => $totalProjectsCount,
            'totalTasks'            => $totalTasksCount,
            'completedProjects'      => $completedProjectsCount,
        ]);
    }
 
    /**
     * RH uniquement → créer un nouveau projet
     */
    #[Route('/rh/projet/new', name: 'app_projet_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_RH')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $projet = new Projet();
        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($projet);
            $entityManager->flush();
 
            $this->addFlash('success', 'Étape 1/2 : Projet créé. Sélectionnez maintenant les tâches suggérées par l\'IA.');
            return $this->redirectToRoute('app_projet_suggest_tasks', ['id' => $projet->getId()], Response::HTTP_SEE_OTHER);
        }
 
        return $this->render('projet/new.html.twig', [
            'projet' => $projet,
            'form'   => $form,
        ]);
    }
 
    /**
     * RH uniquement → voir le détail d'un projet
     */
    #[Route('/rh/projet/{id}', name: 'app_projet_show', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function show(Projet $projet): Response
    {
        return $this->render('projet/show.html.twig', [
            'projet' => $projet,
        ]);
    }
 
    /**
     * RH uniquement → modifier un projet
     */
    #[Route('/rh/projet/{id}/edit', name: 'app_projet_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_RH')]
    public function edit(Request $request, Projet $projet, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
 
            $this->addFlash('success', 'Projet modifié avec succès.');
            return $this->redirectToRoute('app_projet_index', [], Response::HTTP_SEE_OTHER);
        }
 
        return $this->render('projet/edit.html.twig', [
            'projet' => $projet,
            'form'   => $form,
        ]);
    }
 
    /**
     * RH uniquement → supprimer un projet
     */
    #[Route('/rh/projet/{id}', name: 'app_projet_delete', methods: ['POST'])]
    #[IsGranted('ROLE_RH')]
    public function delete(Request $request, Projet $projet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $projet->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($projet);
            $entityManager->flush();
 
            $this->addFlash('success', 'Projet supprimé avec succès.');
        }
 
        return $this->redirectToRoute('app_projet_index', [], Response::HTTP_SEE_OTHER);
    }
 
    /**
     * RH → voir les tâches d'un projet spécifique dans une vue RH
     */
    #[Route('/rh/projet/{id}/taches', name: 'app_projet_taches_view', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function projetTaches(Projet $projet, EntityManagerInterface $entityManager): Response
    {
        $taches = $entityManager->getRepository(\App\Entity\Tache::class)->findBy(['projet' => $projet]);
 
        return $this->render('rh/projet_taches.html.twig', [
            'projet' => $projet,
            'taches' => $taches,
        ]);
    }

    /**
     * Visioconférence Jitsi Meet pour un projet
     */
    #[Route('/projet/{id}/meeting', name: 'app_projet_meeting', methods: ['GET'])]
    public function meeting(Projet $projet): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        // Vérification des accès
        if (!$this->isGranted('ROLE_RH')) {
            if ($projet->getResponsableEmploye() !== $user->getEmploye()) {
                throw $this->createAccessDeniedException("Vous n'êtes pas assigné à ce projet.");
            }
            if ($projet->isMeetingRequested() === false) {
                throw $this->createAccessDeniedException("Cette réunion n'est pas ouverte. Veuillez attendre que le RH la demande.");
            }
        }

        return $this->render('projet/meeting.html.twig', [
            'projet' => $projet,
        ]);
    }

    /**
     * RH uniquement → Ouvrir/Fermer l'accès à la visioconférence (demander un meet)
     */
    #[Route('/rh/projet/{id}/toggle-meeting', name: 'app_projet_toggle_meeting', methods: ['POST'])]
    #[IsGranted('ROLE_RH')]
    public function toggleMeeting(Request $request, Projet $projet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle_meeting' . $projet->getId(), (string)$request->request->get('_token'))) {
            $projet->setIsMeetingRequested(!$projet->isMeetingRequested());
            $entityManager->flush();

            if ($projet->isMeetingRequested()) {
                $this->addFlash('success', 'La salle de visioconférence a été ouverte. Le responsable peut maintenant la rejoindre.');
            } else {
                $this->addFlash('info', 'La salle de visioconférence a été fermée.');
            }
        }

        // Rediriger vers la page dont l'utilisateur vient
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_projet_show', ['id' => $projet->getId()]);
    }
    /**
     * Étape 2 : Suggestion de tâches par l'IA
     */
    #[Route('/rh/projet/{id}/suggest-tasks', name: 'app_projet_suggest_tasks', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function suggestTasks(Projet $projet, \App\Service\TaskSuggestionService $suggestionService): Response
    {
        $suggestedTasks = $suggestionService->suggestTasks($projet);

        return $this->render('projet/suggest_tasks.html.twig', [
            'projet' => $projet,
            'suggestedTasks' => $suggestedTasks,
        ]);
    }

    /**
     * Sauvegarde des tâches sélectionnées
     */
    #[Route('/rh/projet/{id}/save-suggested-tasks', name: 'app_projet_save_suggested_tasks', methods: ['POST'])]
    #[IsGranted('ROLE_RH')]
    public function saveSuggestedTasks(Request $request, Projet $projet, EntityManagerInterface $entityManager): Response
    {
        $selectedIndices = $request->request->all('tasks');
        $titles = $request->request->all('titles');
        $descriptions = $request->request->all('descriptions');
        $starts = $request->request->all('starts');
        $ends = $request->request->all('ends');

        foreach ($selectedIndices as $index) {
            $tache = new \App\Entity\Tache();
            $tache->setTitre($titles[$index]);
            $tache->setDescription($descriptions[$index]);
            $tache->setStatut('a_faire');
            $tache->setLevel('moyenne');
            $tache->setProjet($projet);
            $resp = $projet->getResponsableEmploye();
            if ($resp) {
                $tache->setEmploye($resp);
            }
            
            $tache->setDateDebut(\DateTimeImmutable::createFromFormat('Y-m-d', $starts[$index]) ?: new \DateTimeImmutable($starts[$index]));
            $tache->setDateFin(\DateTimeImmutable::createFromFormat('Y-m-d', $ends[$index]) ?: new \DateTimeImmutable($ends[$index]));

            $entityManager->persist($tache);
        }

        $entityManager->flush();

        $this->addFlash('success', count($selectedIndices) . ' tâches suggérées ont été ajoutées au projet.');
        return $this->redirectToRoute('app_projet_index');
    }
}