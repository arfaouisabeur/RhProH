<?php

namespace App\Controller;

use App\Entity\Prime;
use App\Entity\Contract;
use App\Entity\Tache;
use App\Form\PrimeType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TacheRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CurrencyService;


#[Route('/rh/primes')]
#[IsGranted('ROLE_RH')]
class RhPrimeController extends AbstractController
{
    #[Route('/', name: 'app_rh_prime_index')]
public function index(EntityManagerInterface $em, CurrencyService $currencyService): Response
{
    $currencyService->load();

    $primes = $em->getRepository(Prime::class)->findAll();

    $primesData = [];
    $totalDisplay = 0;

    foreach ($primes as $p) {

        $converted = $currencyService->convert((float)$p->getMontant());

        $primesData[] = [
            'id' => $p->getId(),
            'montant' => $converted,

            // 🔥 KEEP SAME NAME AS TWIG
            'dateAttribution' => $p->getDateAttribution(),

            'description' => $p->getDescription(),
            'contract' => $p->getContract()
        ];

        $totalDisplay += $converted;
    }

    return $this->render('rh/primes/index.html.twig', [
        'primes' => $primesData,
        'total_montant' => $totalDisplay,
        'currency' => $currencyService->getCurrency()
    ]);
}

#[Route('/new', name: 'app_rh_prime_new')]
public function new(
    Request $request,
    EntityManagerInterface $em,
    TacheRepository $tacheRepository,
    CurrencyService $currencyService
): Response
{
    // 🔥 LOAD USER CURRENCY
    $currencyService->load();

    // 🔥 GET TACHES
    $taches = $tacheRepository->findAll();

    // 🔥 CONVERT TACHES FROM TND → USER CURRENCY
    $tachesData = [];

foreach ($taches as $t) {
    $employe = $t->getEmploye();
    if (!$employe) {
        continue;
    }
    
    $tachesData[] = [
        'id' => $t->getId(),
        'titre' => $t->getTitre(),
        'montant' => $currencyService->convert((float)$t->getLevel() * 10),
        'employe_id' => $employe->getUserId(),
        'statut' => $t->getStatut()
    ];
}

    $prime = new Prime();
    $form = $this->createForm(PrimeType::class, $prime);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 🔥 CONVERT BACK USER CURRENCY → TND BEFORE SAVE
        $montantDisplay = (float)$prime->getMontant();
        $prime->setMontant((string)$currencyService->convertToTnd($montantDisplay));

        $em->persist($prime);
        $em->flush(); // generate ID

        // 🔥 LINK TACHES
        $selectedTaches = $request->request->get('selected_taches');
        $tacheIds = is_string($selectedTaches) ? json_decode($selectedTaches, true) ?? [] : [];

        foreach ($tacheIds as $id) {
            $tache = $tacheRepository->find($id);
            if ($tache) {
                $tache->setPrime($prime);
            }
        }

        $em->flush();

        return $this->redirectToRoute('app_rh_prime_index');
    }

    return $this->render('rh/primes/new.html.twig', [
        'form' => $form->createView(),
        'taches' => $tachesData,
        'currency' => $currencyService->getCurrency(),
        'rate' => $currencyService->getRate()
    ]);
}
    
    #[Route('/{id}/edit', name: 'app_rh_prime_edit')]
public function edit(
    Prime $prime,
    Request $request,
    EntityManagerInterface $em,
    CurrencyService $currencyService
): Response
{
    // 🔥 LOAD CURRENCY
    $currencyService->load();

    // 🔥 CONVERT TND → DISPLAY before showing form
    $displayMontant = $currencyService->convert((float)$prime->getMontant());
    $prime->setMontant((string)$displayMontant);

    $form = $this->createForm(PrimeType::class, $prime);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 🔥 CONVERT BACK DISPLAY → TND
        $montantDisplay = (float)$prime->getMontant();
        $prime->setMontant((string)$currencyService->convertToTnd($montantDisplay));

        $em->flush();

        return $this->redirectToRoute('app_rh_prime_index');
    }

    return $this->render('rh/primes/edit.html.twig', [
        'form' => $form->createView(),
        'prime' => $prime,
        'currency' => $currencyService->getCurrency(),
        'rate' => $currencyService->getRate()
    ]);
}
    #[Route('/{id}/delete', name: 'app_rh_prime_delete', methods: ['POST'])]
    public function delete(Request $request, Prime $prime, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $prime->getId(), is_string($token) ? $token : '')) {

            $em->remove($prime);
            $em->flush();
        }

        return $this->redirectToRoute('app_rh_prime_index');
    }

    /**
     * 🔥 FETCH TACHES FOR CONTRACT'S EMPLOYEE WITH REALISTIC VALUES
     */
    #[Route('/contracts/{id}/taches', name: 'app_rh_prime_contract_taches', methods: ['GET'])]
    public function getTaches(Contract $contract, EntityManagerInterface $em): JsonResponse
    {
        $employe = $contract->getEmploye();
        
        if (!$employe) {
            return $this->json(['taches' => []]);
        }

        $taches = $em->getRepository(Tache::class)->findBy(['employe' => $employe]);

        $tachesData = [];
        foreach ($taches as $tache) {
            $valeur = $this->calculateTacheValue(
                $tache->getLevel(),
                $tache->getDateDebut(),
                $tache->getDateFin(),
                $tache->getStatut()
            );

            $tachesData[] = [
                'id' => $tache->getId(),
                'titre' => $tache->getTitre(),
                'statut' => $tache->getStatut(),
                'description' => $tache->getDescription(),
                'projet_id' => $tache->getProjet() ? $tache->getProjet()->getId() : null,
                'date_debut' => $tache->getDateDebut() ? $tache->getDateDebut()->format('Y-m-d') : null,
                'date_fin' => $tache->getDateFin() ? $tache->getDateFin()->format('Y-m-d') : null,
                'level' => $tache->getLevel(),
                'valeur' => $valeur
            ];
        }

        return $this->json(['taches' => $tachesData]);
    }

    /**
     * 🔥 CALCULATE REALISTIC TACHE VALUE FOR PRIME
     * Primes are bonuses, so values are higher than regular salary calculations
     */
    private function calculateTacheValue(?string $level, ?\DateTimeInterface $dateDebut, ?\DateTimeInterface $dateFin, ?string $statut): float
    {
        // Base bonus rates per completed task (in TND)
        $rates = [
            'Junior' => 50,
            'Intermediate' => 100,
            'Senior' => 200,
            'Expert' => 350
        ];

        $baseRate = $rates[$level ?? 'Junior'] ?? 75; // Default to 75 if level not found

        // Bonus multiplier based on status
        $statusMultiplier = 1.0;
        if ($statut && (strtolower($statut) === 'terminé' || strtolower($statut) === 'done' || strtolower($statut) === 'complete')) {
            $statusMultiplier = 1.5; // 50% bonus for completed tasks
        } elseif ($statut && (strtolower($statut) === 'en cours' || strtolower($statut) === 'in progress')) {
            $statusMultiplier = 0.5; // 50% of value for in-progress tasks
        }

        if (!$dateDebut || !$dateFin) {
            return round($baseRate * $statusMultiplier, 2);
        }

        // Calculate duration factor (longer tasks = higher bonus)
        $interval = $dateDebut->diff($dateFin);
        $totalDays = $interval->days + 1;
        
        // Duration multiplier (capped at 2x for very long tasks)
        $durationMultiplier = min(1 + ($totalDays / 30), 2.0);

        $finalValue = $baseRate * $statusMultiplier * $durationMultiplier;

        return round($finalValue, 2);
    }
}
