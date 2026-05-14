<?php

namespace App\Controller;

use App\Entity\CongeTt;
use App\Entity\Reponse;
use App\Form\CongeTtType;
use App\Repository\CongeTtRepository;
use App\Repository\EmployeRepository;
use App\Repository\RHRepository;
use App\Service\AiService;
use App\Service\CongeAbuseDetectorService;
use App\Service\CongeRegleService;
use App\Service\ElevenLabsService;
use App\Service\OcrService;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/conge/tt')]
final class CongeTtController extends AbstractController
{
    #[Route(name: 'app_conge_tt_index', methods: ['GET'])]
    public function index(
        Request $request,
        CongeTtRepository $congeTtRepository,
        EmployeRepository $employeRepository,
        CongeAbuseDetectorService $abuseDetector
    ): Response {
        $search   = $request->query->get('search', '');
        $searchBy = $request->query->get('searchBy', 'all');
        $sortBy   = $request->query->get('sortBy', 'id');
        $sortDir  = $request->query->get('sortDir', 'asc');

        $allowedSort = ['id', 'typeConge', 'dateDebut', 'dateFin', 'statut'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'id';
        }
        $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        $qb = $congeTtRepository->createQueryBuilder('c')
            ->leftJoin('c.employe', 'e')
            ->leftJoin('e.user', 'u')
            ->addSelect('e', 'u');

        // Employé : ne voit que SES propres demandes
        if ($this->isGranted('ROLE_EMPLOYE') && !$this->isGranted('ROLE_RH')) {
            $user    = $this->getUser();
            $employe = $employeRepository->findOneBy(['user' => $user]);
            if ($employe) {
                $qb->andWhere('c.employe = :employe')
                   ->setParameter('employe', $employe);
            }
        }

        // Recherche ciblée par critère
        if ($search !== '') {
            $s = '%' . $search . '%';
            if ($searchBy === 'type_conge') {
                $qb->andWhere('c.type_conge LIKE :s')->setParameter('s', $s);
            } elseif ($searchBy === 'statut') {
                $qb->andWhere('c.statut LIKE :s')->setParameter('s', $s);
            } elseif ($searchBy === 'employe') {
                $qb->andWhere('u.prenom LIKE :s OR u.nom LIKE :s')->setParameter('s', $s);
            } else { // all
                $qb->andWhere('c.type_conge LIKE :s OR c.statut LIKE :s OR c.description LIKE :s OR u.prenom LIKE :s OR u.nom LIKE :s')
                   ->setParameter('s', $s);
            }
        }

        $columnMap = [
            'typeConge' => 'type_conge',
            'dateDebut' => 'date_debut',
            'dateFin'   => 'date_fin',
            'statut'    => 'statut',
            'id'        => 'id',
        ];
        $doctrineCol = $columnMap[$sortBy] !== '' ? $columnMap[$sortBy] : 'id';
        $qb->orderBy('c.' . $doctrineCol, $sortDir);

        $conge_tts = $qb->getQuery()->getResult();

        // ── Détection d'abus ─────────────────────────────────────────────────
        /** @var CongeTt[] $conge_tts */
        $alertesAbus = $abuseDetector->analyserTous($conge_tts);

        return $this->render('conge_tt/index.html.twig', [
            'conge_tts'   => $conge_tts,
            'alertesAbus' => $alertesAbus,
            'search'      => $search,
            'searchBy'    => $searchBy,
            'sortBy'      => $sortBy,
            'sortDir'     => $sortDir,
        ]);
    }

    // ─── AJAX : recherche / tri côté employé (retourne JSON) ────────────────
    #[Route('/search', name: 'app_conge_tt_search', methods: ['GET'])]
    public function search(
        Request $request,
        CongeTtRepository $congeTtRepository,
        EmployeRepository $employeRepository
    ): JsonResponse {
        $search   = $request->query->get('search', '');
        $searchBy = $request->query->get('searchBy', 'all');
        $sortBy   = $request->query->get('sortBy', 'id');
        $sortDir  = $request->query->get('sortDir', 'asc');

        $allowedSort = ['id', 'typeConge', 'dateDebut', 'dateFin', 'statut'];
        if (!in_array($sortBy, $allowedSort)) { $sortBy = 'id'; }
        $sortDir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';

        $qb = $congeTtRepository->createQueryBuilder('c')
            ->leftJoin('c.employe', 'e')
            ->leftJoin('e.user', 'u')
            ->addSelect('e', 'u');

        // L'employé ne voit que ses propres congés
        if ($this->isGranted('ROLE_EMPLOYE') && !$this->isGranted('ROLE_RH')) {
            $user    = $this->getUser();
            $employe = $employeRepository->findOneBy(['user' => $user]);
            if ($employe) {
                $qb->andWhere('c.employe = :employe')->setParameter('employe', $employe);
            }
        }

        if ($search !== '') {
            $s = '%' . $search . '%';
            // Accepter à la fois 'typeConge' et 'type_conge'
            if ($searchBy === 'typeConge' || $searchBy === 'type_conge') {
                $qb->andWhere('c.type_conge LIKE :s')->setParameter('s', $s);
            } elseif ($searchBy === 'statut') {
                $qb->andWhere('c.statut LIKE :s')->setParameter('s', $s);
            } elseif ($searchBy === 'employe') {
                $qb->andWhere('u.prenom LIKE :s OR u.nom LIKE :s')->setParameter('s', $s);
            } else {
                // Recherche globale sur tous les champs
                $qb->andWhere('c.type_conge LIKE :s OR c.statut LIKE :s OR c.description LIKE :s OR u.prenom LIKE :s OR u.nom LIKE :s')
                   ->setParameter('s', $s);
            }
        }

        $columnMap = [
            'typeConge' => 'type_conge',
            'dateDebut' => 'date_debut',
            'dateFin'   => 'date_fin',
            'statut'    => 'statut',
            'id'        => 'id',
        ];
        $doctrineCol = $columnMap[$sortBy] !== '' ? $columnMap[$sortBy] : 'id';
        $qb->orderBy('c.' . $doctrineCol, $sortDir);

        $conges = $qb->getQuery()->getResult();

        // Sérialisation manuelle (pas besoin du serializer Symfony)
        $rows = [];
        foreach ($conges as $c) {
            $d1 = $c->getDateDebut() ? $c->getDateDebut()->getTimestamp() : null;
            $d2 = $c->getDateFin()   ? $c->getDateFin()->getTimestamp()   : null;
            $nbJours = ($d1 && $d2 && $d2 >= $d1)
                ? (int) floor(($d2 - $d1) / 86400) + 1
                : null;

            $st    = $c->getStatut();
            $editable = !in_array($st, ['Accepté','Refusé','approuvé','refusé']);

            $rows[] = [
                'id'         => $c->getId(),
                'typeConge'  => $c->getTypeConge(),
                'dateDebut'  => $c->getDateDebut()  ? $c->getDateDebut()->format('d/m/Y')  : '',
                'dateFin'    => $c->getDateFin()    ? $c->getDateFin()->format('d/m/Y')    : '',
                'nbJours'    => $nbJours,
                'statut'     => $st,
                'editable'   => $editable,
                'showUrl'    => $this->generateUrl('app_conge_tt_show', ['id' => $c->getId()]),
                'editUrl'    => $this->generateUrl('app_conge_tt_edit', ['id' => $c->getId()]),
            ];
        }

        return new JsonResponse(['rows' => $rows, 'total' => count($rows)]);
    }

    // ─── IA AJAX : Génération de description via Groq ──────────────────────────────────────────
    #[Route('/ai-description', name: 'app_conge_tt_ai_description', methods: ['GET'])]
    public function aiDescription(Request $request, AiService $aiService): JsonResponse
    {
        $type = trim((string) $request->query->get('type', ''));
        if ($type === '') {
            return new JsonResponse(['error' => 'Type manquant'], 400);
        }
        try {
            $result = $aiService->genererDescriptionConge($type);
            return new JsonResponse($result);
        } catch (\Throwable $e) {
            error_log('[CongeTtController] aiDescription error: ' . $e->getMessage());
            return new JsonResponse(['error' => 'IA indisponible'], 500);
        }
    }

    // ─── OCR AJAX : Analyse de certificat médical ────────────────────────────────────────────
    #[Route('/ocr-analyse', name: 'app_conge_tt_ocr_analyse', methods: ['POST'])]
    public function ocrAnalyse(Request $request, OcrService $ocrService): JsonResponse
    {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['succes' => false, 'error' => 'Aucun fichier reçu'], 400);
        }

        // Sauvegarder temporairement le fichier
        $tmpDir  = sys_get_temp_dir();
        $tmpName = uniqid('ocr_') . '.' . ($file->guessExtension() ?? 'tmp');
        $file->move($tmpDir, $tmpName);
        $tmpPath = $tmpDir . DIRECTORY_SEPARATOR . $tmpName;

        try {
            $result = $ocrService->analyserDocumentPath($tmpPath);
        } catch (\Throwable $e) {
            error_log('[OcrController] ' . $e->getMessage());
            $result = ['succes' => false, 'texte' => '', 'medecin' => null, 'periode' => null];
        } finally {
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }

        return new JsonResponse($result);
    }

    // ─── AJAX : Validation + jours fériés en temps réel ─────────────────────
    #[Route('/valider-periode', name: 'app_conge_tt_valider_periode', methods: ['GET'])]
    public function validerPeriode(Request $request, CongeRegleService $congeRegleService): JsonResponse
    {
        $typeConge  = (string) $request->query->get('type', '');
        $dateDebut  = (string) $request->query->get('debut', '');
        $dateFin    = (string) $request->query->get('fin', '');
        $aDocument  = $request->query->getBoolean('document', false);

        if ($dateDebut === '' || $dateFin === '') {
            return new JsonResponse(['ok' => false]);
        }

        try {
            $debut = new \DateTime($dateDebut);
            $fin   = new \DateTime($dateFin);
        } catch (\Throwable) {
            return new JsonResponse(['ok' => false, 'erreurs' => ['Dates invalides']]);
        }

        if ($fin < $debut) {
            return new JsonResponse(['ok' => false, 'erreurs' => ['La date de fin doit être après la date de début']]);
        }

        $resultat = $congeRegleService->valider($typeConge, $debut, $fin, $aDocument);

        // Formater les jours fériés pour le JSON
        $feries = [];
        foreach ($resultat['feriesDansPeriode'] as $date => $info) {
            $feries[] = [
                'date'  => $date,
                'nom'   => $info['nom'],
                'type'  => $info['type'],
                'emoji' => $info['emoji'],
            ];
        }

        return new JsonResponse([
            'ok'              => $resultat['valide'],
            'erreurs'         => $resultat['erreurs'],
            'avertissements'  => $resultat['avertissements'],
            'infos'           => $resultat['infos'],
            'joursOuvrables'  => $resultat['joursOuvrables'],
            'joursCalendaires'=> $resultat['joursCalendaires'],
            'feries'          => $feries,
        ]);
    }

    #[Route('/new', name: 'app_conge_tt_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EmployeRepository $employeRepository, OcrService $ocrService): Response
    {
        $congeTt = new CongeTt();

        $user = $this->getUser();
        if ($user) {
            $employe = $employeRepository->findOneBy(['user' => $user]);
            if ($employe) {
                $congeTt->setEmploye($employe);
            }
        }

        $congeTt->setStatut('En attente');

        $form = $this->createForm(CongeTtType::class, $congeTt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            error_log('[CongeTt::new] Formulaire soumis et valide — type: ' . $congeTt->getTypeConge());

            $certificatFile = $form->get('certificatMedical')->getData();
            $isMaladie      = str_contains(strtolower($congeTt->getTypeConge()), 'maladie');

            if ($certificatFile && $isMaladie) {
                set_time_limit(120); // OCR peut être lent
                $uploadsDir = (string) $this->getParameter('kernel.project_dir') . '/public/uploads/certificats';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                }

                $fileName = uniqid() . '.' . $certificatFile->guessExtension();
                $certificatFile->move($uploadsDir, $fileName);

                $filePath = $uploadsDir . DIRECTORY_SEPARATOR . $fileName;
                $congeTt->setDocumentPath('uploads/certificats/' . $fileName);

                // Analyse OCR du certificat — ne bloque pas si l'API est lente
                try {
                    $ocrResult = $ocrService->analyserDocumentPath($filePath);
                    $congeTt->setOcrVerified($ocrResult['succes']);
                } catch (\Throwable $e) {
                    error_log('[CongeTtController] OCR échoué : ' . $e->getMessage());
                    $congeTt->setOcrVerified(false);
                }
            }

            $entityManager->persist($congeTt);
            $entityManager->flush();
            error_log('[CongeTt::new] Congé persisté avec succès — ID: ' . $congeTt->getId());

            $this->addFlash('success', 'Votre demande de congé a été soumise avec succès !');

            return $this->redirectToRoute('app_conge_tt_index', [], Response::HTTP_SEE_OTHER);
        }

        // Log validation errors for debugging
        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                error_log('[CongeTt::new] Erreur validation: [' . $error->getOrigin()?->getName() . '] ' . $error->getMessage());
            }
        }

        return $this->render('conge_tt/new.html.twig', [
            'conge_tt' => $congeTt,
            'form'     => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}', name: 'app_conge_tt_show', methods: ['GET'])]
    public function show(CongeTt $congeTt): Response
    {
        return $this->render('conge_tt/show.html.twig', [
            'conge_tt' => $congeTt,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_conge_tt_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CongeTt $congeTt, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CongeTtType::class, $congeTt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_conge_tt_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('conge_tt/edit.html.twig', [
            'conge_tt' => $congeTt,
            'form'     => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}', name: 'app_conge_tt_delete', methods: ['POST'])]
    public function delete(Request $request, CongeTt $congeTt, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $congeTt->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($congeTt);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_conge_tt_index', [], Response::HTTP_SEE_OTHER);
    }

    // ─── RH : Accepter ou Refuser une demande ────────────────────────────────
    #[Route('/{id}/repondre', name: 'app_conge_tt_repondre', methods: ['POST'])]
    public function repondre(
        Request $request,
        CongeTt $congeTt,
        EntityManagerInterface $entityManager,
        RHRepository $rhRepository,
        SmsService $smsService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $decision    = (string) $request->request->get('decision', '');
        $commentaire = (string) $request->request->get('commentaire', '');

        if (!in_array($decision, ['approuvé', 'refusé'], true)) {
            $this->addFlash('error', 'Décision invalide.');
            return $this->redirectToRoute('app_conge_tt_index');
        }

        $reponseRepo = $entityManager->getRepository(Reponse::class);
        $reponse     = $reponseRepo->findOneBy(['conge_tt' => $congeTt]) ?? new Reponse();

        $user = $this->getUser();
        $rh   = $rhRepository->findOneBy(['user' => $user]);

        // ── Si REFUSÉ : mettre à jour le statut et créer la réponse ─────
        if ($decision === 'refusé') {
            $congeTt->setStatut('Refusé');
            
            $reponse->setDecision('refusé');
            $reponse->setCommentaire($commentaire ?: null);
            $reponse->setRh($rh);
            $reponse->setEmploye($congeTt->getEmploye());
            $reponse->setCongeTt($congeTt);

            $entityManager->persist($reponse);
            $entityManager->flush();

            // 🔥 GÉNÉRER ET AFFICHER LE PDF AUTOMATIQUEMENT
            $pdfResponse = $this->generateAndDisplayCongePdf($congeTt, $reponse, $decision, $commentaire);

            // Envoyer l'alerte SMS à l'employé (refusé)
            $smsService->envoyerAlerteConge($congeTt, 'refusé');

            return $pdfResponse;
        }

        // ── Si ACCEPTÉ ────────────────────────────────────────────────────
        $congeTt->setStatut('Accepté');

        $reponse->setDecision('approuvé');
        $reponse->setCommentaire($commentaire ?: null);
        $reponse->setRh($rh);
        $reponse->setEmploye($congeTt->getEmploye());
        $reponse->setCongeTt($congeTt);

        $entityManager->persist($reponse);
        $entityManager->flush();

        // 🔥 GÉNÉRER ET AFFICHER LE PDF AUTOMATIQUEMENT
        $pdfResponse = $this->generateAndDisplayCongePdf($congeTt, $reponse, $decision, $commentaire);

        // Envoyer l'alerte SMS à l'employé (accepté)
        $smsService->envoyerAlerteConge($congeTt, 'approuvé');

        return $pdfResponse;
    }

    // ─── RH : Supprimer manuellement une demande ─────────────────────────────
    #[Route('/{id}/rh-delete', name: 'app_conge_tt_rh_delete', methods: ['POST'])]
    public function rhDelete(
        Request $request,
        CongeTt $congeTt,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_RH');

        if ($this->isCsrfTokenValid('rh-delete' . $congeTt->getId(), $request->request->getString('_token'))) {
            // Supprimer la réponse liée si elle existe
            $reponseRepo = $entityManager->getRepository(Reponse::class);
            $reponse     = $reponseRepo->findOneBy(['conge_tt' => $congeTt]);
            if ($reponse) {
                $entityManager->remove($reponse);
            }
            $entityManager->remove($congeTt);
            $entityManager->flush();
            $this->addFlash('success', '🗑️ Demande supprimée par le RH.');
        }

        return $this->redirectToRoute('app_conge_tt_index');
    }

    // ─── Rapport vocal : génération audio via ElevenLabs ─────────────────────
    #[Route('/rapport-vocal', name: 'app_conge_tt_rapport_vocal', methods: ['GET'])]
    public function rapportVocal(
        CongeTtRepository $congeTtRepository,
        ElevenLabsService $elevenLabsService
    ): Response {
        // Récupérer toutes les demandes de congé
        $conges = $congeTtRepository->findAll();
        $total  = count($conges);

        // Compter par statut
        $statuts = [
            'Accepté'    => 0,
            'Refusé'     => 0,
            'En attente' => 0,
        ];

        $slaDepasses = 0;
        $nbUrgentes  = 0;
        $now         = new \DateTime();

        foreach ($conges as $conge) {
            if (!$conge instanceof CongeTt) {
                continue;
            }
            $statut = $conge->getStatut();
            if (isset($statuts[$statut])) {
                $statuts[$statut]++;
            }

            // Vérifier SLA (7 jours)
            $dateDebut = $conge->getDateDebut();
            if ($dateDebut !== null && $statut === 'En attente') {
                $diff = $now->diff($dateDebut)->days;
                if ($diff !== false && $diff > 7) {
                    $slaDepasses++;
                }
            }

            $type = strtolower($conge->getTypeConge());
            if ($statut === 'En attente' && (str_contains($type, 'urgent') || str_contains($type, 'maladie'))) {
                $nbUrgentes++;
            }
        }

        // Calculer le taux de résolution
        $resolved = $statuts['Accepté'] + $statuts['Refusé'];
        $tauxRes  = $total > 0 ? ($resolved / $total) * 100 : 0;

        // Compter les types de congé les plus demandés
        $typesCount = [];
        foreach ($conges as $conge) {
            if (!$conge instanceof CongeTt) {
                continue;
            }
            $type = $conge->getTypeConge();
            if (!isset($typesCount[$type])) {
                $typesCount[$type] = 0;
            }
            $typesCount[$type]++;
        }

        // Trier et prendre le top 3
        arsort($typesCount);
        $topTypes = [];
        $i = 0;
        foreach ($typesCount as $nom => $count) {
            if ($i >= 3) break;
            $topTypes[] = ['nom' => $nom, 'count' => $count];
            $i++;
        }

        // Construire le texte du rapport
        $texte = $elevenLabsService->construireResume(
            total: $total,
            tauxRes: $tauxRes,
            slaDepasses: $slaDepasses,
            nbUrgentes: $nbUrgentes,
            statuts: $statuts,
            topTypes: $topTypes,
            totalLikes: 0,      // Pas de système de likes/dislikes pour les congés
            totalDislikes: 0
        );

        try {
            // Générer l'audio via ElevenLabs
            $audioBytes = $elevenLabsService->genererAudio($texte);

            // Retourner le fichier audio
            $response = new Response($audioBytes);
            $response->headers->set('Content-Type', 'audio/mpeg');
            $response->headers->set('Content-Disposition', 'inline; filename="rapport_conges.mp3"');
            return $response;
        } catch (\Throwable $e) {
            error_log('[CongeTtController] rapportVocal error: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Impossible de générer le rapport vocal'], 500);
        }
    }

    /**
     * 🔥 TÉLÉCHARGER LE PDF D'UNE DÉCISION DE CONGÉ
     */
    #[Route('/{id}/download-pdf', name: 'app_conge_tt_download_pdf', methods: ['GET'])]
    public function downloadPdf(CongeTt $congeTt, EntityManagerInterface $entityManager): Response
    {
        // Récupérer la réponse RH pour ce congé
        $reponseRepo = $entityManager->getRepository(Reponse::class);
        $reponse = $reponseRepo->findOneBy(['conge_tt' => $congeTt]);
        
        if (!$reponse) {
            $this->addFlash('error', 'Aucune décision RH trouvée pour cette demande.');
            return $this->redirectToRoute('app_conge_tt_index');
        }

        // Générer le PDF à la demande avec le nouveau template
        return $this->generateAndDisplayCongePdf(
            $congeTt, 
            $reponse, 
            $reponse->getDecision(), 
            $reponse->getCommentaire() ?? ''
        );
    }

    /**
     * 🔥 GÉNÉRATION ET AFFICHAGE AUTOMATIQUE DE PDF POUR DÉCISION CONGÉ
     */
    private function generateAndDisplayCongePdf(CongeTt $congeTt, Reponse $reponse, string $decision, string $commentaire): Response
    {
        try {
            // Configuration Dompdf
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new Dompdf($options);

            // Charger le cachet en base64
            $cachetPath = (string) $this->getParameter('kernel.project_dir') . '/assets/images/cachet.png';
            $cachetBase64 = '';
            if (file_exists($cachetPath)) {
                $imageData = file_get_contents($cachetPath);
                if ($imageData !== false) {
                    $cachetBase64 = 'data:image/png;base64,' . base64_encode($imageData);
                }
            }

            // Rendre le template
            $html = $this->renderView('conge_tt/pdf.html.twig', [
                'conge' => $congeTt,
                'reponse' => $reponse,
                'decision' => $decision,
                'commentaire' => $commentaire,
                'cachet_base64' => $cachetBase64
            ]);

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Créer le dossier si nécessaire
            $pdfDir = (string) $this->getParameter('kernel.project_dir') . '/public/conges_pdf';
            if (!is_dir($pdfDir)) {
                mkdir($pdfDir, 0777, true);
            }

            // Nom du fichier
            $filename = sprintf(
                'conge_%d_%s_%s.pdf',
                $congeTt->getId(),
                $decision,
                date('Ymd_His')
            );

            // Sauvegarder le PDF
            $filepath = $pdfDir . '/' . $filename;
            file_put_contents($filepath, $dompdf->output());

            error_log('[CongeTtController] PDF généré et affiché: ' . $filename);

            // Retourner le PDF pour affichage dans le navigateur
            $response = new Response($dompdf->output());
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'inline; filename="' . $filename . '"');
            
            return $response;

        } catch (\Throwable $e) {
            error_log('[CongeTtController] Erreur génération PDF: ' . $e->getMessage());
            // En cas d'erreur, rediriger vers l'index avec un message
            $this->addFlash('warning', 'Décision enregistrée mais erreur lors de la génération du PDF.');
            return $this->redirectToRoute('app_conge_tt_index');
        }
    }

}
