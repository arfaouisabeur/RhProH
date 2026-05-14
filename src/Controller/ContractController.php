<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\RH;
use App\Form\ContractType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CurrencyService;
use App\Service\SalaryAverageService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/rh/contracts')]
#[IsGranted('ROLE_RH')]
final class ContractController extends AbstractController
{
   #[Route('/', name: 'app_rh_contract_index')]
public function index(Request $request, EntityManagerInterface $em, CurrencyService $currencyService): Response
{
    $search = $request->query->get('search');
    $status = $request->query->get('status');

    $qb = $em->getRepository(Contract::class)
        ->createQueryBuilder('c')
        ->join('c.employe', 'e')
        ->join('e.user', 'u');

    if ($search) {
        $qb->andWhere('LOWER(e.matricule) LIKE :search OR LOWER(u.nom) LIKE :search OR LOWER(u.prenom) LIKE :search')
           ->setParameter('search', '%'.strtolower($search).'%');
    }

    if ($status === 'active') {
        $qb->andWhere('c.date_fin IS NULL OR c.date_fin > :today')
           ->setParameter('today', date('Y-m-d'));
    }

    if ($status === 'expired') {
        $qb->andWhere('c.date_fin IS NOT NULL AND c.date_fin < :today')
           ->setParameter('today', date('Y-m-d'));
    }

    $contracts = $qb->getQuery()->getResult();

    // 🔥 ADD ONLY (currency logic)
    $currencyService->load();

    foreach ($contracts as $c) {
        $c->convertedSalaire = $currencyService->convert((float)$c->getSalaireBase());
    }

    return $this->render('rh/contracts/index.html.twig', [
        'contracts' => $contracts,
        'search' => $search,
        'status' => $status,

        'currency' => $currencyService->getCurrency()
    ]);
}

#[Route('/new', name: 'app_rh_contract_new')]
public function new(Request $request, EntityManagerInterface $em, CurrencyService $currencyService): Response
{
    $contract = new Contract();

    $user = $this->getUser();
    $rh = $em->getRepository(RH::class)->findOneBy(['user' => $user]);

    if (!$rh) {
        // Create RH record if it doesn't exist
        $rh = new RH();
        $rh->setUser($user);
        $em->persist($rh);
        $em->flush();
    }

    $contract->setRh($rh);

    // 🔥 ADD ONLY
    $currencyService->load();

    $form = $this->createForm(ContractType::class, $contract);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {

        if ($form->isValid()) {

            // 🔥 KEEP YOUR LOGIC
            $existing = $em->getRepository(Contract::class)
                ->createQueryBuilder('c')
                ->where('c.employe = :emp')
                ->andWhere('c.date_fin IS NULL OR c.date_fin > :today')
                ->setParameter('emp', $contract->getEmploye())
                ->setParameter('today', date('Y-m-d'))
                ->getQuery()
                ->getOneOrNullResult();

            if ($existing) {
                $form->addError(new FormError(
                    '⚠ Cet employé a déjà un contrat actif !'
                ));
            } else {

                // 🔥 ADD ONLY (convert before save)
                $display = (float)$contract->getSalaireBase();
                $contract->setSalaireBase((string)$currencyService->convertToTnd($display));

                $em->persist($contract);
                $em->flush();

                $this->addFlash('success', 'Contrat créé avec succès!');
                return $this->redirectToRoute('app_rh_contract_index');
            }
        } else {
            // Add flash message for validation errors
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }
    }

    return $this->render('rh/contracts/new.html.twig', [
        'form' => $form->createView(),

        // 🔥 ADD ONLY
        'currency' => $currencyService->getCurrency(),
        'rate' => $currencyService->getRate()
    ]);
}
   #[Route('/{id}/edit', name: 'app_rh_contract_edit')]
public function edit(Request $request, Contract $contract, EntityManagerInterface $em, CurrencyService $currencyService): Response
{
    // 🔥 KEEP ORIGINAL EMPLOYEE (VERY IMPORTANT)
    $originalEmploye = $contract->getEmploye();

    // 🔥 ADD ONLY
    $currencyService->load();

    // 🔥 convert TND → DISPLAY
    $display = $currencyService->convert((float)$contract->getSalaireBase());
    $contract->setSalaireBase((string)$display);

    $form = $this->createForm(ContractType::class, $contract, [
        'edit_mode' => true
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 🔥 KEEP ORIGINAL EMPLOYEE (NO CHANGE)
        $contract->setEmploye($originalEmploye);

        // 🔥 ADD ONLY (convert back before save)
        $display = (float)$contract->getSalaireBase();
        $contract->setSalaireBase((string)$currencyService->convertToTnd($display));

        $em->flush();

        return $this->redirectToRoute('app_rh_contract_index');
    }

    return $this->render('rh/contracts/edit.html.twig', [
        'form' => $form->createView(),
        'contract' => $contract,

        // 🔥 ADD ONLY
        'currency' => $currencyService->getCurrency(),
        'rate' => $currencyService->getRate()
    ]);
}


    #[Route('/{id}', name: 'app_rh_contract_delete', methods: ['POST'])]
    public function delete(Request $request, Contract $contract, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$contract->getId(), is_string($token) ? $token : '')) {
            $em->remove($contract);
            $em->flush();
        }

        return $this->redirectToRoute('app_rh_contract_index');
    }
    #[Route('/check-active/{id}', name: 'app_rh_contract_check')]
public function checkActive(int $id, EntityManagerInterface $em): Response
{
    // 🔥 FIX HERE
    $employe = $em->getRepository(\App\Entity\Employe::class)
        ->findOneBy(['userId' => $id]);

    if (!$employe) {
        return $this->json(['hasActive' => false]);
    }

    $existing = $em->getRepository(Contract::class)
        ->createQueryBuilder('c')
        ->where('c.employe = :emp')
        ->andWhere('c.date_fin IS NULL OR c.date_fin > :today')
        ->setParameter('emp', $employe)
        ->setParameter('today', date('Y-m-d'))
        ->getQuery()
        ->getOneOrNullResult();

    return $this->json([
        'hasActive' => $existing ? true : false
    ]);
}
#[Route('/api/average-salary', name: 'api_average_salary')]
public function averageSalary(
    Request $request,
    SalaryAverageService $salaryService,
    CurrencyService $currencyService
): JsonResponse {

    $country = strtoupper((string)$request->query->get('country', ''));

    // 🔥 GET USD SALARY
    $usdSalary = $salaryService->getAverageSalary($country);

    if (!$usdSalary) {
        return $this->json(['salary' => null]);
    }

    // 🔥 USD → TND
    $tndSalary = $currencyService->convertUsdToTnd($usdSalary);

    // 🔥 LOAD USER CURRENCY
    $currencyService->load();

    // 🔥 TND → USER CURRENCY
    $final = $currencyService->convert($tndSalary);

    return $this->json([
        'salary' => $final,
        'currency' => $currencyService->getCurrency()
    ]);
}
}
