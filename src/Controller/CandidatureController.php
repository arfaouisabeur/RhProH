<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Form\CandidatureType;
use App\Service\BlockchainService;
use App\Service\CertificatService;
use App\Service\CvAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CandidatureController extends AbstractController
{
    // ─── INDEX ───────────────────────────────────────────────────────────────────
    #[Route('/candidature', name: 'app_candidature_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $q      = trim((string) $request->query->get('q', ''));
        $statut = trim((string) $request->query->get('statut', 'all'));

        $candidatures = $this->buildCandidatureQuery($entityManager, $q, $statut);

        return $this->render('candidature/index.html.twig', [
            'candidatures'   => $candidatures,
            'search'         => $q,
            'current_statut' => $statut,
            'total'          => count($candidatures),
        ]);
    }

    // ─── AJAX SEARCH ─────────────────────────────────────────────────────────────
    #[Route('/candidature/ajax-search', name: 'app_candidature_ajax_search', methods: ['GET'])]
    public function ajaxSearch(Request $request, EntityManagerInterface $entityManager): Response
    {
        $q      = trim((string) $request->query->get('q', ''));
        $statut = trim((string) $request->query->get('statut', 'all'));

        $candidatures = $this->buildCandidatureQuery($entityManager, $q, $statut);

        return $this->render('candidature/_rows.html.twig', [
            'candidatures' => $candidatures,
        ]);
    }

    // ─── SHARED QUERY BUILDER ────────────────────────────────────────────────────
    /**
     * @return array<int, Candidature>
     */
    private function buildCandidatureQuery(
        EntityManagerInterface $em,
        string $q,
        string $statut
    ): array {
        $qb = $em->getRepository(Candidature::class)
            ->createQueryBuilder('c')
            ->leftJoin('c.candidat', 'cand')
            ->leftJoin('cand.user', 'u')
            ->leftJoin('c.offreEmploi', 'o')
            ->orderBy('c.dateCandidature', 'DESC');

        if ($q !== '') {
            $qb->andWhere('u.nom LIKE :q OR u.prenom LIKE :q OR u.email LIKE :q OR o.titre LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if ($statut !== '' && $statut !== 'all') {
            $map = [
                'en_attente' => ['en_attente', 'En attente', 'pending'],
                'acceptee'   => ['acceptee', 'Acceptée', 'accepted'],
                'entretien'  => ['entretien', 'Entretien', 'interview'],
                'refusee'    => ['refusee', 'Refusée', 'rejected'],
            ];
            if (isset($map[$statut])) {
                $qb->andWhere('c.statut IN (:statuts)')
                   ->setParameter('statuts', $map[$statut]);
            }
        }

        return $qb->getQuery()->getResult();
    }

    // ─── GENERATE LETTRE (IA) — DOIT ÊTRE AVANT {id} ────────────────────────────
    #[Route('/candidature/generate-lettre', name: 'app_generate_lettre', methods: ['POST'])]
    public function generateLettre(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if ($user === null) {
            return $this->json(['error' => 'Non autorisé.'], 401);
        }

        $candidat = $entityManager->getRepository(Candidat::class)->findOneBy(['user' => $user]);
        if (!$candidat) {
            $candidat = new Candidat();
            $candidat->setUser($user);
            $entityManager->persist($candidat);
            $entityManager->flush();
        }

        $offreId  = $request->request->get('offre_id');
        $offre    = $entityManager->getRepository(OffreEmploi::class)->find($offreId);

        if ($offre === null) {
            return $this->json(['error' => 'Données introuvables.'], 404);
        }

        $apiKey = $_ENV['GROQ_API_KEY'] ?? null;
        if (!$apiKey) {
            return $this->json(['error' => 'Clé API Groq manquante dans .env.local'], 500);
        }

        $nom         = $user->getNom() . ' ' . $user->getPrenom();
        $niveauEtude = $candidat->getNiveauEtude() ?? 'non précisé';
        $experience  = $candidat->getExperience() . ' an(s)';
        $titre       = $offre->getTitre();
        $desc        = $offre->getDescription();
        $contrat     = $offre->getTypeContrat();
        $lieu        = $offre->getLocalisation();

        $prompt = <<<PROMPT
Tu es un expert en recrutement. Génère une lettre de motivation professionnelle en français pour :

Candidat : $nom
Niveau d'études : $niveauEtude
Expérience : $experience

Poste visé : $titre ($contrat) à $lieu
Description du poste : $desc

La lettre doit :
- Être naturelle, professionnelle et personnalisée
- Mettre en valeur le niveau d'études et l'expérience du candidat
- Faire entre 200 et 300 mots
- Avoir 3 paragraphes : accroche, adéquation au poste, conclusion avec call-to-action
- Ne pas inclure les coordonnées ni la date
- Commencer directement par "Madame, Monsieur,"
PROMPT;

        $models = [
            'llama-3.1-8b-instant',
            'llama3-70b-8192',
            'gemma2-9b-it',
            'llama-3.3-70b-versatile',
        ];

        $client    = \Symfony\Component\HttpClient\HttpClient::create();
        $lastError = '';

        foreach ($models as $model) {
            try {
                $response = $client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'       => $model,
                        'messages'    => [['role' => 'user', 'content' => $prompt]],
                        'max_tokens'  => 1024,
                        'temperature' => 0.7,
                    ],
                    'timeout' => 30,
                ]);

                $statusCode = $response->getStatusCode();

                if ($statusCode === 200) {
                    $data   = $response->toArray();
                    $lettre = $data['choices'][0]['message']['content'] ?? '';
                    if (!empty($lettre)) {
                        return $this->json(['lettre' => $lettre]);
                    }
                }

                $lastError = 'Modèle ' . $model . ' — HTTP ' . $statusCode . ' : ' . $response->getContent(false);

            } catch (\Exception $e) {
                $lastError = 'Modèle ' . $model . ' — ' . $e->getMessage();
                continue;
            }
        }

        return $this->json([
            'error' => 'Tous les modèles ont échoué. Dernière erreur : ' . $lastError
        ], 500);
    }

    // ─── VÉRIFICATION BLOCKCHAIN — DOIT ÊTRE AVANT {id} ─────────────────────────
    #[Route('/candidature/verify/{hash}', name: 'candidature_verify', methods: ['GET'])]
    public function verifyCandidature(
        string $hash,
        EntityManagerInterface $entityManager,
        BlockchainService $blockchainService
    ): Response {
        $isValid     = $blockchainService->verifyHash($hash);
        $candidature = $entityManager->getRepository(Candidature::class)
            ->findOneBy(['signatureRequestId' => $hash]);

        return $this->render('candidature/verify.html.twig', [
            'hash'        => $hash,
            'isValid'     => $isValid,
            'candidature' => $candidature,
        ]);
    }

    // ─── NEW ─────────────────────────────────────────────────────────────────────
    #[Route('/candidature/new', name: 'app_candidature_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $candidature = new Candidature();

        $form = $this->createForm(CandidatureType::class, $candidature, [
            'is_admin' => true,
        ]);
        $form->handleRequest($request);

        $cvFile = $form->has('cvFile') ? $form->get('cvFile')->getData() : null;

        if ($form->isSubmitted() && $form->isValid()) {
            if ($cvFile) {
                $originalName = $cvFile->getClientOriginalName();
                $size         = $cvFile->getSize();
                $extension    = $cvFile->guessExtension() ?: 'pdf';
                $newFilename  = uniqid('', true) . '.' . $extension;

                $cvFile->move($this->getParameter('cv_directory'), $newFilename);

                $candidature->setCvPath($newFilename);
                $candidature->setCvOriginalName($originalName);
                $candidature->setCvSize($size);
                $candidature->setCvUploadedAt(new \DateTimeImmutable());
            }

            $entityManager->persist($candidature);
            $entityManager->flush();

            $this->addFlash('success', 'Candidature ajoutée avec succès.');

            return $this->redirectToRoute('app_candidature_index');
        }

        return $this->render('candidature/new.html.twig', [
            'form' => $form,
        ]);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────────
    #[Route('/candidature/{id}', name: 'app_candidature_show', methods: ['GET'])]
    public function show(Candidature $candidature): Response
    {
        return $this->render('candidature/show.html.twig', [
            'candidature' => $candidature,
        ]);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────────
    #[Route('/candidature/{id}/edit', name: 'app_candidature_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CandidatureType::class, $candidature, [
            'is_admin' => true,
        ]);
        $form->handleRequest($request);

        $cvFile = $form->get('cvFile')->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            if ($cvFile) {
                $originalName = $cvFile->getClientOriginalName();
                $size         = $cvFile->getSize();
                $extension    = $cvFile->guessExtension() ?: 'pdf';
                $newFilename  = uniqid('', true) . '.' . $extension;

                $cvFile->move($this->getParameter('cv_directory'), $newFilename);

                $candidature->setCvPath($newFilename);
                $candidature->setCvOriginalName($originalName);
                $candidature->setCvSize($size);
                $candidature->setCvUploadedAt(new \DateTimeImmutable());
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_candidature_index');
        }

        return $this->render('candidature/edit.html.twig', [
            'form'        => $form,
            'candidature' => $candidature,
        ]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────────
    #[Route('/candidature/{id}', name: 'app_candidature_delete', methods: ['POST'])]
    public function delete(Request $request, Candidature $candidature, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $candidature->getId(), is_string($token) ? $token : null)) {
            $entityManager->remove($candidature);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_candidature_index');
    }

    // ─── CHANGE STATUT ────────────────────────────────────────────────────────────
    #[Route('/candidature/{id}/statut', name: 'app_candidature_statut', methods: ['POST'])]
    public function changeStatut(
        Request $request,
        Candidature $candidature,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('statut' . $candidature->getId(), is_string($token) ? $token : null)) {
            $statut = $request->request->get('statut');

            if (is_string($statut) && in_array($statut, ['en_attente', 'entretien', 'acceptee', 'refusee'], true)) {
                $candidature->setStatut($statut);
                $entityManager->flush();
            }
        }

        return $this->redirectToRoute('app_candidature_show', [
            'id' => $candidature->getId(),
        ]);
    }

    // ─── MES CANDIDATURES ─────────────────────────────────────────────────────────
    #[Route('/mes-candidatures', name: 'candidat_candidatures', methods: ['GET'])]
    public function candidatCandidatures(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $candidat = $entityManager->getRepository(Candidat::class)->findOneBy(['user' => $user]);

        if (!$candidat) {
            $candidat = new Candidat();
            $candidat->setUser($user);
            $entityManager->persist($candidat);
            $entityManager->flush();
        }

        $candidatures = $entityManager->getRepository(Candidature::class)
            ->findBy(['candidat' => $candidat], ['dateCandidature' => 'DESC']);

        return $this->render('candidat/candidature/index.html.twig', [
            'candidatures' => $candidatures,
        ]);
    }

    // ─── POSTULER ─────────────────────────────────────────────────────────────────
    #[Route('/mes-candidatures/new/{offreId}', name: 'candidat_postuler', methods: ['GET', 'POST'])]
    public function candidatPostuler(
        int $offreId,
        Request $request,
        EntityManagerInterface $entityManager,
        BlockchainService $blockchainService,
        CertificatService $certificatService
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $candidat = $entityManager->getRepository(Candidat::class)->findOneBy(['user' => $user]);

        if (!$candidat) {
            $candidat = new Candidat();
            $candidat->setUser($user);
            $entityManager->persist($candidat);
            $entityManager->flush();
        }

        $offre = $entityManager->getRepository(OffreEmploi::class)->find($offreId);

        if (!$offre) {
            throw $this->createNotFoundException('Offre introuvable.');
        }

        $existing = $entityManager->getRepository(Candidature::class)->findOneBy([
            'candidat'    => $candidat,
            'offreEmploi' => $offre,
        ]);

        if ($existing) {
            $this->addFlash('error', 'Vous avez déjà postulé à cette offre.');
            return $this->redirectToRoute('candidat_candidatures');
        }

        $candidature = new Candidature();
        $candidature->setCandidat($candidat);
        $candidature->setOffreEmploi($offre);
        $candidature->setDateCandidature(new \DateTimeImmutable());
        $candidature->setStatut('en_attente');

        // ── Gestion du CV temporaire en session ────────────────────────────
        $session     = $request->getSession();
        $sessionKey  = 'tmp_cv_' . $offre->getId() . '_' . $candidat->getUserId();
        $hasTmpCv    = $session->has($sessionKey);

        $form = $this->createForm(CandidatureType::class, $candidature, [
            'is_admin' => false,
            'has_tmp_cv' => $hasTmpCv, // Passer l'info au formulaire
        ]);
        $form->handleRequest($request);

        $cvFile = $form->get('cvFile')->getData();
        $tmpCvPath = null;

        if ($cvFile) {
            // Nouveau fichier uploadé → sauvegarder en temp
            $tmpOriginalName = $cvFile->getClientOriginalName();
            $tmpSize         = $cvFile->getSize();
            $tmpName         = 'tmp_' . uniqid('', true) . '.' . ($cvFile->guessExtension() ?: 'pdf');

            $cvFile->move($this->getParameter('cv_directory'), $tmpName);

            $session->set($sessionKey, [
                'path'         => $tmpName,
                'originalName' => $tmpOriginalName,
                'size'         => $tmpSize,
            ]);
            $tmpCvPath = $tmpName;
        } elseif ($session->has($sessionKey)) {
            // Pas de nouveau fichier mais CV en session → réutiliser
            $tmpCvPath = $session->get($sessionKey)['path'] ?? null;
        }

        if ($form->isSubmitted() && $form->isValid()) {

            // Vérifier qu'un CV est disponible (nouveau ou en session)
            if (!$tmpCvPath || !file_exists($this->getParameter('cv_directory') . '/' . $tmpCvPath)) {
                $this->addFlash('error', 'Veuillez joindre votre CV.');
                return $this->render('candidat/candidature/new.html.twig', [
                    'form'         => $form,
                    'offre'        => $offre,
                    'has_tmp_cv'   => false,
                    'tmp_cv_name'  => '',
                ]);
            }

            // Utiliser le CV de session ou nouveau
            $cvInfo = $session->get($sessionKey, []);
            $candidature->setCvPath($tmpCvPath);
            $candidature->setCvOriginalName($cvInfo['originalName'] ?? $tmpCvPath);
            $candidature->setCvSize($cvInfo['size'] ?? 0);
            $candidature->setCvUploadedAt(new \DateTimeImmutable());

            // Supprimer le CV temporaire de la session
            $session->remove($sessionKey);

            $entityManager->persist($candidature);
            $entityManager->flush();

            // ── Certification Blockchain ──────────────────────────────────────
            try {
                $candidatId = $candidat->getUserId();
                $offreId = $offre->getId();
                
                if ($candidatId === null || $offreId === null) {
                    throw new \RuntimeException('ID candidat ou offre manquant');
                }
                
                $hash = $blockchainService->generateCandidatureHash(
                    $candidatId,
                    $offreId,
                    date('Y-m-d H:i:s'),
                    $candidature->getCvPath() ?? 'no-cv'
                );

                $blockchainData = $blockchainService->recordOnBlockchain($hash);

                $candidature->setSignatureRequestId($hash);
                $candidature->setContractStatus('certified');
                $entityManager->flush();

                $certificatService->generateCertificat($candidature, $blockchainData);

            } catch (\Exception $e) {
                // Candidature soumise même si la certification échoue
            }
            // ─────────────────────────────────────────────────────────────────

            $this->addFlash('success', 'Votre candidature a été envoyée et certifiée avec succès.');
            return $this->redirectToRoute('candidat_candidatures');
        }

        // Indiquer au template si un CV est déjà en session
        $hasTmpCv = $tmpCvPath && file_exists($this->getParameter('cv_directory') . '/' . $tmpCvPath);

        return $this->render('candidat/candidature/new.html.twig', [
            'form'         => $form->createView(),
            'offre'        => $offre,
            'has_tmp_cv'   => $hasTmpCv,
            'tmp_cv_name'  => $hasTmpCv ? ($session->get($sessionKey)['originalName'] ?? '') : '',
        ]);
    }

    // ─── TÉLÉCHARGER CERTIFICAT ───────────────────────────────────────────────────
    #[Route('/mes-candidatures/{id}/certificat', name: 'candidat_certificat', methods: ['GET'])]
    public function downloadCertificat(
        Candidature $candidature
    ): Response {
        $hash = $candidature->getSignatureRequestId();

        if (!$hash) {
            $this->addFlash('error', 'Aucun certificat disponible pour cette candidature.');
            return $this->redirectToRoute('candidat_candidatures');
        }

        $filepath = $this->getParameter('kernel.project_dir')
            . '/public/certificats/certificat_' . $hash . '.pdf';

        if (!file_exists($filepath)) {
            $this->addFlash('error', 'Certificat introuvable.');
            return $this->redirectToRoute('candidat_candidatures');
        }

        return $this->file($filepath, 'certificat_candidature.pdf');
    }
    #[Route('/mes-candidatures/{id}/regenerer-certificat', name: 'candidat_regenerer_certificat', methods: ['GET'])]
    public function regenererCertificat(
    Candidature $candidature,
    EntityManagerInterface $entityManager,
    BlockchainService $blockchainService,
    CertificatService $certificatService
): Response {
    $hash = $candidature->getSignatureRequestId();

    if (!$hash) {
        $this->addFlash('error', 'Cette candidature n\'a pas de hash blockchain.');
        return $this->redirectToRoute('candidat_candidatures');
    }

    $blockchainData = [
        'success'      => true,
        'hash'         => $hash,
        'block_number' => rand(1000000, 9999999),
        'timestamp'    => date('Y-m-d H:i:s'),
        'network'      => 'ETH Sepolia Testnet',
        'explorer_url' => '',
    ];

    $certificatService->generateCertificat($candidature, $blockchainData);

    $this->addFlash('success', 'Certificat régénéré avec succès.');
    return $this->redirectToRoute('candidat_candidatures');
}
    // ─── ANALYSE CV (IA) ──────────────────────────────────────────────────────────
    #[Route('/candidature/{id}/analyse-cv', name: 'candidature_analyse_cv', methods: ['POST'])]
    public function analyseCv(
        Candidature            $candidature,
        EntityManagerInterface $entityManager,
        CvAnalysisService      $cvAnalysisService
    ): Response {
        if (!$candidature->getCvPath()) {
            return $this->json(['success' => false, 'error' => 'Aucun CV associé à cette candidature.'], 400);
        }

        try {
            $result = $cvAnalysisService->analyse($candidature);

            $candidature->setMatchScore($result['score']);
            $candidature->setCvSkills($result['skills']);
            $candidature->setAiAnalysis($result['analysis']);
            $candidature->setMatchUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            return $this->json([
                'success'  => true,
                'score'    => $result['score'],
                'skills'   => $result['skills'],
                'analysis' => $result['analysis'],
            ]);

        } catch (\RuntimeException $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

}
