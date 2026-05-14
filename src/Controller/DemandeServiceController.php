<?php

namespace App\Controller;

use App\Entity\DemandeService;
use App\Entity\Reponse;
use App\Entity\ServiceReaction;
use App\Entity\TypeService;
use App\Form\DemandeServiceType;
use App\Repository\DemandeServiceRepository;
use App\Repository\EmployeRepository;
use App\Repository\RHRepository;
use App\Repository\ServiceReactionRepository;
use App\Repository\TypeServiceRepository;
use App\Service\AiService;
use App\Service\ElevenLabsService;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/demande/service')]
final class DemandeServiceController extends AbstractController
{
    #[Route(name: 'app_demande_service_index', methods: ['GET'])]
    public function index(
        Request $request,
        DemandeServiceRepository $demandeServiceRepository,
        EmployeRepository $employeRepository,
        TypeServiceRepository $typeServiceRepo,
        ServiceReactionRepository $reactionRepo,
        ElevenLabsService $elevenLabs
    ): Response {
        $search    = $request->query->get('search', '');
        $searchBy  = $request->query->get('searchBy', 'all');
        $sortBy    = $request->query->get('sortBy', 'dateDemande');
        $sortDir   = strtolower($request->query->get('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowed = ['dateDemande', 'statut', 'type'];
        if (!in_array($sortBy, $allowed)) $sortBy = 'dateDemande';

        $qb = $demandeServiceRepository->createQueryBuilder('d')
            ->leftJoin('d.type', 't')
            ->leftJoin('d.employe', 'e')
            ->leftJoin('e.user', 'u')
            ->addSelect('t', 'e', 'u');

        // Employé : ne voit que SES propres demandes
        if ($this->isGranted('ROLE_EMPLOYE') && !$this->isGranted('ROLE_RH')) {
            $user    = $this->getUser();
            $employe = $user ? $employeRepository->findOneBy(['user' => $user]) : null;
            if ($employe) {
                $qb->andWhere('d.employe = :employe')->setParameter('employe', $employe);
            }
        }

        // Recherche
        if ($search !== '') {
            $s = '%' . $search . '%';
            if ($searchBy === 'statut') {
                $qb->andWhere('d.statut LIKE :s')->setParameter('s', $s);
            } elseif ($searchBy === 'type') {
                $qb->andWhere('t.nom LIKE :s')->setParameter('s', $s);
            } elseif ($searchBy === 'employe') {
                $qb->andWhere('u.prenom LIKE :s OR u.nom LIKE :s')->setParameter('s', $s);
            } else { // all
                $qb->andWhere('d.statut LIKE :s OR t.nom LIKE :s OR u.prenom LIKE :s OR u.nom LIKE :s')
                   ->setParameter('s', $s);
            }
        }

        // Tri — mapping camelCase → nom réel de la propriété PHP de l'entité
        $columnMap = [
            'dateDemande' => 'date_demande',
            'statut'      => 'statut',
        ];

        if ($sortBy === 'type') {
            $qb->orderBy('t.nom', $sortDir);
        } else {
            $doctrineCol = $columnMap[$sortBy] !== '' ? $columnMap[$sortBy] : 'date_demande';
            $qb->orderBy('d.' . $doctrineCol, $sortDir);
        }

        // ── Types de service + réactions pour la vue employé ───────────────
        $currentUser  = $this->getUser();
        $allTypes     = $typeServiceRepo->findAll();
        $reactionMap  = ($currentUser instanceof \App\Entity\User) ? $reactionRepo->findReactionMapByUser($currentUser) : [];
        $countsMap    = [];
        foreach ($allTypes as $ts) {
            $countsMap[$ts->getId()] = $reactionRepo->countByType($ts);
        }

        // ── Texte du rapport vocal (pour fallback speechSynthesis) ──────────
        $rapportTexte = '';
        if ($this->isGranted('ROLE_RH')) {
            $all      = $demandeServiceRepository->findAll();
            $total    = count($all);
            $accepted = 0; $rejected = 0; $pending = 0;
            $statuts  = ['En attente' => 0, 'Accepté' => 0, 'Refusé' => 0];
            foreach ($all as $d) {
                $st = $d->getStatut();
                if ($st === 'Accepté' || $st === 'approuvé') { $accepted++; $statuts['Accepté']++; }
                elseif ($st === 'Refusé' || $st === 'refusé') { $rejected++; $statuts['Refusé']++; }
                else { $pending++; $statuts['En attente']++; }
            }
            $resolved  = $accepted + $rejected;
            $tauxRes   = $total > 0 ? ($resolved / $total * 100) : 0.0;
            $slaDepasses = 0;
            $now = new \DateTimeImmutable();
            foreach ($all as $d) {
                if ($d->getStatut() === 'En attente' && $d->getDateDemande()) {
                    try {
                        $dateObj = new \DateTimeImmutable($d->getDateDemande());
                        if ($dateObj->diff($now)->days > 7) $slaDepasses++;
                    } catch (\Exception $e) {
                        // Skip invalid dates
                    }
                }
            }
            $typeCountMap = [];
            foreach ($all as $d) {
                if ($d->getType()) {
                    $nom = $d->getType()->getNom();
                    $typeCountMap[$nom] = ($typeCountMap[$nom] ?? 0) + 1;
                }
            }
            arsort($typeCountMap);
            $topTypes = [];
            foreach (array_slice($typeCountMap, 0, 3, true) as $nom => $count) {
                $topTypes[] = ['nom' => $nom, 'count' => $count];
            }
            $totalLikes = 0; $totalDislikes = 0;
            foreach ($typeServiceRepo->findAll() as $ts) {
                $c = $reactionRepo->countByType($ts);
                $totalLikes += $c['likes']; $totalDislikes += $c['dislikes'];
            }
            $rapportTexte = $elevenLabs->construireResume($total, $tauxRes, $slaDepasses, $pending, $statuts, $topTypes, $totalLikes, $totalDislikes);
        }

        return $this->render('demande_service/index.html.twig', [
            'demande_services' => $qb->getQuery()->getResult(),
            'search'           => $search,
            'searchBy'         => $searchBy,
            'sortBy'           => $sortBy,
            'sortDir'          => $sortDir,
            'allTypes'         => $allTypes,
            'reactionMap'      => $reactionMap,
            'countsMap'        => $countsMap,
            'rapportTexte'     => $rapportTexte,
        ]);
    }

    // ─── AJAX : recherche / tri employé (retourne JSON) ─────────────────────
    #[Route('/search', name: 'app_demande_service_search', methods: ['GET'])]
    public function search(
        Request $request,
        DemandeServiceRepository $demandeServiceRepository,
        EmployeRepository $employeRepository
    ): JsonResponse {
        $search   = $request->query->get('search', '');
        $searchBy = $request->query->get('searchBy', 'all');
        $sortBy   = $request->query->get('sortBy', 'dateDemande');
        $sortDir  = strtolower($request->query->get('sortDir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowed = ['dateDemande', 'statut', 'type'];
        if (!in_array($sortBy, $allowed)) $sortBy = 'dateDemande';

        $qb = $demandeServiceRepository->createQueryBuilder('d')
            ->leftJoin('d.type', 't')
            ->leftJoin('d.employe', 'e')
            ->leftJoin('e.user', 'u')
            ->addSelect('t', 'e', 'u');

        // L'employé ne voit que ses propres demandes
        if ($this->isGranted('ROLE_EMPLOYE') && !$this->isGranted('ROLE_RH')) {
            $user    = $this->getUser();
            $employe = $user ? $employeRepository->findOneBy(['user' => $user]) : null;
            if ($employe) {
                $qb->andWhere('d.employe = :employe')->setParameter('employe', $employe);
            }
        }

        if ($search !== '') {
            $s = '%' . $search . '%';
            if ($searchBy === 'statut') {
                $qb->andWhere('d.statut LIKE :s')->setParameter('s', $s);
            } elseif ($searchBy === 'type') {
                $qb->andWhere('t.nom LIKE :s')->setParameter('s', $s);
            } elseif ($searchBy === 'employe') {
                $qb->andWhere('u.prenom LIKE :s OR u.nom LIKE :s')->setParameter('s', $s);
            } else {
                $qb->andWhere('d.statut LIKE :s OR t.nom LIKE :s OR u.prenom LIKE :s OR u.nom LIKE :s')
                   ->setParameter('s', $s);
            }
        }

        if ($sortBy === 'type') {
            $qb->orderBy('t.nom', $sortDir);
        } else {
            $col = $sortBy === 'dateDemande' ? 'date_demande' : $sortBy;
            $qb->orderBy('d.' . $col, $sortDir);
        }

        $rows = [];
        foreach ($qb->getQuery()->getResult() as $d) {
            $st = $d->getStatut();
            $type = $d->getType();
            $employe = $d->getEmploye();
            $user = $employe ? $employe->getUser() : null;

            $date = $d->getDateDemande();
            $dateStr = '';
            if ($date) {
                if ($date instanceof \DateTimeInterface) {
                    $dateStr = $date->format('d/m/Y');
                } else {
                    $dateStr = (string) $date !== '0000-00-00' ? date('d/m/Y', (int) strtotime((string) $date)) : '';
                }
            }

            $rows[] = [
                'id'         => $d->getId(),
                'typeNom'    => $type ? $type->getNom() : null,
                'date'       => $dateStr,
                'statut'     => $st,
                'prenom'     => $user ? $user->getPrenom() : null,
                'nom'        => $user ? $user->getNom()    : null,
                'showUrl'    => $this->generateUrl('app_demande_service_show', ['id' => $d->getId()]),
                'editUrl'    => $this->generateUrl('app_demande_service_edit', ['id' => $d->getId()]),
            ];
        }

        return new JsonResponse(['rows' => $rows, 'total' => count($rows)]);
    }

    // ─── AJAX IA : Génération de description via Groq ─────────────────────────
    #[Route('/ai-description', name: 'app_demande_service_ai_description', methods: ['GET'])]
    public function aiDescription(Request $request, AiService $aiService): JsonResponse
    {
        $type = trim((string) $request->query->get('type', ''));
        if ($type === '') {
            return new JsonResponse(['error' => 'Type manquant'], 400);
        }
        try {
            $result = $aiService->genererDescriptionService($type);
            return new JsonResponse($result);
        } catch (\Throwable $e) {
            error_log('[DemandeServiceController] aiDescription error: ' . $e->getMessage());
            return new JsonResponse(['error' => 'IA indisponible'], 500);
        }
    }

    // ─── IA OPTION A : Recommandation de type de service ─────────────────────
    /**
     * L'employé saisit son besoin en texte libre → l'IA recommande le meilleur type.
     * GET /demande/service/ai-recommander?besoin=...
     */
    #[Route('/ai-recommander', name: 'app_demande_service_ai_recommander', methods: ['GET'])]
    public function aiRecommander(
        Request $request,
        AiService $aiService,
        TypeServiceRepository $typeServiceRepo
    ): JsonResponse {
        $besoin = trim((string) $request->query->get('besoin', ''));
        if (strlen($besoin) < 5) {
            return new JsonResponse(['error' => 'Décrivez votre besoin en au moins 5 caractères'], 400);
        }

        // Récupérer tous les types disponibles en base
        $types       = $typeServiceRepo->findAll();
        $nomsTypes   = array_map(fn($t) => (string) $t->getNom(), $types);

        // Construire une map nom => id pour retrouver le type recommandé
        $typeMap = [];
        foreach ($types as $t) {
            $typeMap[$t->getNom()] = ['id' => $t->getId(), 'categorie' => $t->getCategorie()];
        }

        try {
            $result = $aiService->recommanderTypeService($besoin, $nomsTypes);

            // Enrichir avec l'id du type recommandé si trouvé
            $nomRec = $result['typeRecommande'];
            if ($nomRec !== '' && isset($typeMap[$nomRec])) {
                $result['typeId']    = $typeMap[$nomRec]['id'];
                $result['categorie'] = $typeMap[$nomRec]['categorie'];
            }

            return new JsonResponse($result);
        } catch (\Throwable $e) {
            error_log('[aiRecommander] ' . $e->getMessage());
            return new JsonResponse(['error' => 'IA indisponible'], 500);
        }
    }

    // ─── IA OPTION B : Analyse des réactions — rapport RH ────────────────────
    /**
     * Le RH demande une analyse des likes/dislikes → l'IA génère un rapport.
     * GET /demande/service/ai-analyse-reactions
     */
    #[Route('/ai-analyse-reactions', name: 'app_demande_service_ai_analyse_reactions', methods: ['GET'])]
    public function aiAnalyseReactions(
        AiService $aiService,
        TypeServiceRepository $typeServiceRepo,
        ServiceReactionRepository $reactionRepo
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_RH');

        // Construire les stats par type
        $statsParType     = [];
        $totalReactions   = 0;

        foreach ($typeServiceRepo->findAll() as $ts) {
            $c = $reactionRepo->countByType($ts);
            $total = $c['likes'] + $c['dislikes'];
            if ($total > 0) {
                $statsParType[] = [
                    'typeNom'  => $ts->getNom(),
                    'likes'    => $c['likes'],
                    'dislikes' => $c['dislikes'],
                ];
                $totalReactions += $total;
            }
        }

        try {
            $result = $aiService->analyserReactions($statsParType, $totalReactions);
            return new JsonResponse($result);
        } catch (\Throwable $e) {
            error_log('[aiAnalyseReactions] ' . $e->getMessage());
            return new JsonResponse(['error' => 'IA indisponible'], 500);
        }
    }

    // ─── ElevenLabs TTS : Rapport vocal RH ─────────────────────────────────
    /**
     * Génère un rapport audio MP3 du tableau de bord RH via ElevenLabs.
     * GET /demande/service/rapport-vocal
     */
    #[Route('/rapport-vocal', name: 'app_demande_service_rapport_vocal', methods: ['GET'])]
    public function rapportVocal(
        DemandeServiceRepository    $demandeServiceRepository,
        TypeServiceRepository        $typeServiceRepo,
        ServiceReactionRepository    $reactionRepo,
        ElevenLabsService            $elevenLabs
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');

        // ── Calcul des stats ─────────────────────────────────────────────────
        $all      = $demandeServiceRepository->findAll();
        $total    = count($all);
        $accepted = 0;
        $rejected = 0;
        $pending  = 0;
        $statuts  = ['En attente' => 0, 'Accepté' => 0, 'Refusé' => 0];

        foreach ($all as $d) {
            $st = $d->getStatut();
            if ($st === 'Accepté' || $st === 'approuvé') {
                $accepted++;
                $statuts['Accepté']++;
            } elseif ($st === 'Refusé' || $st === 'refusé') {
                $rejected++;
                $statuts['Refusé']++;
            } else {
                $pending++;
                $statuts['En attente']++;
            }
        }

        $resolved  = $accepted + $rejected;
        $tauxRes   = $total > 0 ? ($resolved / $total * 100) : 0.0;

        // Vérifier les SLA dépassés (> 7 jours en attente)
        $slaDepasses = 0;
        $now = new \DateTimeImmutable();
        foreach ($all as $d) {
            if ($d->getStatut() === 'En attente') {
                $date = $d->getDateDemande();
                if ($date) {
                    try {
                        $dateObj = new \DateTimeImmutable($date);
                        if ($dateObj->diff($now)->days > 7) {
                            $slaDepasses++;
                        }
                    } catch (\Exception $e) {
                        // Skip invalid dates
                    }
                }
            }
        }

        // Top 3 types de service
        $typeCountMap = [];
        foreach ($all as $d) {
            if ($d->getType()) {
                $nom = $d->getType()->getNom();
                $typeCountMap[$nom] = ($typeCountMap[$nom] ?? 0) + 1;
            }
        }
        arsort($typeCountMap);
        $topTypes = [];
        foreach (array_slice($typeCountMap, 0, 3, true) as $nom => $count) {
            $topTypes[] = ['nom' => $nom, 'count' => $count];
        }

        // Totaux likes / dislikes
        $totalLikes    = 0;
        $totalDislikes = 0;
        foreach ($typeServiceRepo->findAll() as $ts) {
            $c = $reactionRepo->countByType($ts);
            $totalLikes    += $c['likes'];
            $totalDislikes += $c['dislikes'];
        }

        // ── Construire le texte et générer l'audio ───────────────────────────
        try {
            $texte = $elevenLabs->construireResume(
                $total,
                $tauxRes,
                $slaDepasses,
                $pending,
                $statuts,
                $topTypes,
                $totalLikes,
                $totalDislikes
            );

            $mp3Bytes = $elevenLabs->genererAudio($texte);

            return new Response($mp3Bytes, 200, [
                'Content-Type'        => 'audio/mpeg',
                'Content-Disposition' => 'inline; filename="rapport-rh.mp3"',
                'Cache-Control'       => 'no-cache',
                'Content-Length'      => strlen($mp3Bytes),
            ]);
        } catch (\Throwable $e) {
            error_log('[ElevenLabs] ' . $e->getMessage());
            return new Response(
                (string) json_encode(['error' => 'Service vocal indisponible : ' . $e->getMessage()]),
                500,
                ['Content-Type' => 'application/json']
            );
        }
    }

    #[Route('/new', name: 'app_demande_service_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        EmployeRepository $employeRepository,
        TypeServiceRepository $typeServiceRepository
    ): Response
    {
        $demandeService = new DemandeService();

        // Auto-fill employé + statut/date
        $user = $this->getUser();
        $employe = $user ? $employeRepository->findOneBy(['user' => $user]) : null;
        if ($employe) {
            $demandeService->setEmploye($employe);
        }
        $demandeService->setStatut('En attente');
        $demandeService->setDateDemande((new \DateTimeImmutable())->format('Y-m-d'));
        // Éviter INSERT avec titre NULL (colonne NOT NULL) avant soumission du type
        $demandeService->setTitre('Demande de service');

        $form = $this->createForm(DemandeServiceType::class, $demandeService);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Titre = libellé du type choisi (obligatoire via le formulaire)
            $type = $demandeService->getType();
            $demandeService->setTitre($type ? (string) $type->getNom() : 'Demande de service');
            $entityManager->persist($demandeService);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande de service a été soumise avec succès !');
            return $this->redirectToRoute('app_demande_service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demande_service/new.html.twig', [
            'demande_service' => $demandeService,
            'form' => $form,
            'type_services_payload' => array_map(
                static fn($t) => ['id' => $t->getId(), 'nom' => $t->getNom(), 'categorie' => $t->getCategorie()],
                $typeServiceRepository->findAll()
            ),
        ]);
    }

    // ════════════════════════════════════════════════════════════════════
    //  LIKE / DISLIKE — Système de réactions (AVANT /{id} pour éviter les conflits)
    // ════════════════════════════════════════════════════════════════════

    #[Route('/react/{typeId}/{reaction}', name: 'app_demande_service_react', methods: ['POST'])]
    public function react(
        int $typeId,
        string $reaction,
        TypeServiceRepository $typeServiceRepo,
        ServiceReactionRepository $reactionRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => 'Non connecté'], 401);
        }
        if (!in_array($reaction, [ServiceReaction::LIKE, ServiceReaction::DISLIKE], true)) {
            return new JsonResponse(['error' => 'Réaction invalide'], 400);
        }
        $typeService = $typeServiceRepo->find($typeId);
        if (!$typeService) {
            return new JsonResponse(['error' => 'Type introuvable'], 404);
        }

        $existing    = $reactionRepo->findOneByUserAndType($user, $typeService);
        $newReaction = null;

        if ($existing === null) {
            $sr = (new ServiceReaction())
                ->setUser($user)
                ->setTypeService($typeService)
                ->setReaction($reaction)
                ->setCreatedBy($user);
            $em->persist($sr);
            $newReaction = $reaction;
        } elseif ($existing->getReaction() === $reaction) {
            $em->remove($existing);
        } else {
            $existing->setReaction($reaction);
            $existing->setUpdatedBy($user);
            $existing->setUpdatedAt(new \DateTimeImmutable());
            $newReaction = $reaction;
        }
        $em->flush();

        $counts = $reactionRepo->countByType($typeService);
        return new JsonResponse(['reaction' => $newReaction, 'likes' => $counts['likes'], 'dislikes' => $counts['dislikes']]);
    }

    #[Route('/mes-reactions', name: 'app_demande_service_mes_reactions', methods: ['GET'])]
    public function mesReactions(ServiceReactionRepository $reactionRepo, TypeServiceRepository $typeServiceRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user      = $this->getUser();
        $likes     = ($user instanceof \App\Entity\User) ? $reactionRepo->findLikesByUser($user) : [];
        $dislikes  = ($user instanceof \App\Entity\User) ? $reactionRepo->findDislikesByUser($user) : [];
        $countsMap = [];
        foreach ($typeServiceRepo->findAll() as $ts) {
            $countsMap[$ts->getId()] = $reactionRepo->countByType($ts);
        }
        return $this->render('demande_service/mes_reactions.html.twig', [
            'likes'     => $likes,
            'dislikes'  => $dislikes,
            'countsMap' => $countsMap,
        ]);
    }

    #[Route('/reactions-counts', name: 'app_demande_service_reactions_counts', methods: ['GET'])]
    public function reactionsCounts(TypeServiceRepository $typeServiceRepo, ServiceReactionRepository $reactionRepo): JsonResponse
    {
        $user      = $this->getUser();
        $userReact = ($user instanceof \App\Entity\User) ? $reactionRepo->findReactionMapByUser($user) : [];
        $counts    = [];
        foreach ($typeServiceRepo->findAll() as $ts) {
            $c = $reactionRepo->countByType($ts);
            $counts[$ts->getId()] = ['likes' => $c['likes'], 'dislikes' => $c['dislikes'], 'mine' => $userReact[$ts->getId()] ?? null];
        }
        return new JsonResponse($counts);
    }

    // ════════════════════════════════════════════════════════════════════
    //  CRUD standard — /{id} en dernier (routes génériques)
    // ════════════════════════════════════════════════════════════════════

    #[Route('/{id}', name: 'app_demande_service_show', methods: ['GET'])]
    public function show(DemandeService $demandeService): Response
    {
        return $this->render('demande_service/show.html.twig', [
            'demande_service' => $demandeService,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_demande_service_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        DemandeService $demandeService,
        EntityManagerInterface $entityManager,
        TypeServiceRepository $typeServiceRepository
    ): Response
    {
        $form = $this->createForm(DemandeServiceType::class, $demandeService);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $type = $demandeService->getType();
            if ($type) {
                $demandeService->setTitre((string) $type->getNom());
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_demande_service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demande_service/edit.html.twig', [
            'demande_service' => $demandeService,
            'form' => $form,
            'type_services_payload' => array_map(
                static fn($t) => ['id' => $t->getId(), 'nom' => $t->getNom(), 'categorie' => $t->getCategorie()],
                $typeServiceRepository->findAll()
            ),
        ]);
    }

    #[Route('/{id}', name: 'app_demande_service_delete', methods: ['POST'])]
    public function delete(Request $request, DemandeService $demandeService, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$demandeService->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($demandeService);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_demande_service_index', [], Response::HTTP_SEE_OTHER);
    }

    // ─── RH : Accepter ou Refuser une demande de service ─────────────────────
    #[Route('/{id}/repondre', name: 'app_demande_service_repondre', methods: ['POST'])]
    public function repondre(
        Request $request,
        DemandeService $demandeService,
        EntityManagerInterface $entityManager,
        RHRepository $rhRepository,
        SmsService $smsService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $decision    = (string) $request->request->get('decision', '');
        $commentaire = (string) $request->request->get('commentaire', '');

        if (!in_array($decision, ['approuvé', 'refusé'], true)) {
            $this->addFlash('error', 'Décision invalide.');
            return $this->redirectToRoute('app_demande_service_index');
        }

        // 1) Statut lisible côté employé
        $demandeService->setStatut($decision === 'approuvé' ? 'Accepté' : 'Refusé');

        // 2) Créer ou mettre à jour la Reponse associée
        $reponseRepo = $entityManager->getRepository(Reponse::class);
        $reponse     = $reponseRepo->findOneBy(['demande_service' => $demandeService]) ?? new Reponse();

        $user = $this->getUser();
        $rh   = $user ? $rhRepository->findOneBy(['user' => $user]) : null;

        $reponse->setDecision($decision);
        $reponse->setCommentaire($commentaire ?: null);
        $reponse->setRh($rh);
        $reponse->setEmploye($demandeService->getEmploye());
        $reponse->setDemandeService($demandeService);

        $entityManager->persist($reponse);
        $entityManager->flush();

        // Envoyer un SMS si approuvé
        if ($decision === 'approuvé') {
            $employe = $demandeService->getEmploye();
            if ($employe && $employe->getUser() && $employe->getUser()->getTelephone()) {
                $telephone = (string) preg_replace('/\s+/', '', (string) $employe->getUser()->getTelephone());
                if (!str_starts_with($telephone, '+')) {
                    $telephone = '+216' . ltrim($telephone, '0');
                }
                $message = sprintf(
                    'ALERTE RH 🚨' . "\n" .
                    'Votre demande de service a été %s.',
                    $decision
                );
                $smsService->sendSms($telephone, $message);
            }
        }

        $label = $decision === 'approuvé' ? '✅ approuvée' : '❌ refusée';
        $this->addFlash('success', 'Demande de service ' . $label . ' avec succès.');

        return $this->redirectToRoute('app_demande_service_index');
    }
}
