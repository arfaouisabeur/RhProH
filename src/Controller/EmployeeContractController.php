<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\Employe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CurrencyService; 
use App\Service\TaxService; 


// 🔥 PDF
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/employe/contracts')]
#[IsGranted('ROLE_EMPLOYE')]
class EmployeeContractController extends AbstractController
{
   #[Route('/', name: 'app_employee_contract_index')]
public function index(
    EntityManagerInterface $em,
    CurrencyService $currencyService,
    TaxService $taxService
): Response
{
    $user = $this->getUser();

    $employe = $em->getRepository(Employe::class)
        ->findOneBy(['user' => $user]);

    if (!$employe) {
        throw $this->createNotFoundException('Employe not found');
    }

    $contracts = $em->getRepository(Contract::class)
        ->findBy(['employe' => $employe]);

    // 🔥 LOAD LOCATION + CURRENCY
    $currencyService->load();

    $convertedSalaires = [];
    $taxData = [];

    foreach ($contracts as $c) {

    $monthlyTND = (float)$c->getSalaireBase(); // Monthly in TND from DB
    $yearlyTND = $monthlyTND * 12; // Yearly in TND

    // Convert to display currency
    $monthlyDisplay = $currencyService->convert($monthlyTND);
    $yearlyDisplay = $currencyService->convert($yearlyTND);
    
    $convertedSalaires[$c->getId()] = $monthlyDisplay;

    // Get country for tax calculation
    $country = $this->mapCurrencyToCountry($currencyService->getCurrency());

    // Calculate tax on yearly amount
    $yearlyResult = $taxService->calculateNet($yearlyDisplay, $country);
    
    if ($yearlyResult) {
        // Convert to MONTHLY for display
        $taxData[$c->getId()] = [
            'gross' => round($yearlyResult['gross'] / 12, 2),
            'tax' => round($yearlyResult['tax'] / 12, 2),
            'net' => round($yearlyResult['net'] / 12, 2),
            // Debug info
            'debug' => [
                'monthlyTND' => $monthlyTND,
                'yearlyTND' => $yearlyTND,
                'yearlyDisplay' => $yearlyDisplay,
                'country' => $country,
                'yearlyTax' => $yearlyResult['tax']
            ]
        ];
    }
}

    return $this->render('employee/contracts/index.html.twig', [
        'contracts' => $contracts,
        'currency' => $currencyService->getCurrency(),
        'convertedSalaires' => $convertedSalaires,

        // 🔥 NEW
        'taxData' => $taxData
    ]);
}


   #[Route('/{id}/pdf', name: 'app_employee_contract_pdf')]
public function pdf(Contract $contract): Response
{
    $user = $this->getUser();

    $employe = $contract->getEmploye();
    if (!$employe || $employe->getUser() !== $user) {
        throw $this->createAccessDeniedException();
    }

    // Get the cachet image path from assets
    $projectDir = $this->getParameter('kernel.project_dir');
    if (!is_string($projectDir)) {
        throw new \RuntimeException('Project directory parameter must be a string');
    }
    $cachetPath = $projectDir . '/assets/images/cachet.png';
    
    // Convert to base64 if file exists
    $cachetData = null;
    if (file_exists($cachetPath)) {
        $imageData = file_get_contents($cachetPath);
        if ($imageData === false) {
            throw new \RuntimeException('Failed to read cachet image');
        }
        $cachetData = 'data:image/png;base64,' . base64_encode($imageData);
    }

    $html = $this->renderView('employee/contracts/pdf.html.twig', [
        'contract' => $contract,
        'cachet_path' => $cachetData
    ]);

    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $dompdf->stream(
        'contract_'.$contract->getId().'.pdf',
        ["Attachment" => true]
    );

    exit();
}
private function mapCurrencyToCountry(string $currency): string
{
    return match ($currency) {
        'TND' => 'TN',
        'EUR' => 'FR',
        'USD' => 'US',
        'GBP' => 'GB',
        'CAD' => 'CA',
        default => 'US',
    };
}
}