<?php

namespace App\Controller;

use App\Entity\Salaire;
use App\Form\SalaireType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CurrencyService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;


#[Route('/rh/salaires')]
#[IsGranted('ROLE_RH')]
class RhSalaireController extends AbstractController
{
     #[Route('/', name: 'app_rh_salaire_index')]
public function index(Request $request, EntityManagerInterface $em, CurrencyService $currencyService): Response
{
    $currencyService->load();

    $qb = $em->getRepository(Salaire::class)
        ->createQueryBuilder('s')
        ->join('s.contract', 'c')
        ->join('c.employe', 'e')
        ->join('e.user', 'u');

    $salaires = $qb->getQuery()->getResult();

    $salairesData = [];

    $total = count($salaires);
    $paye = 0;
    $attente = 0;
    $totalDisplay = 0;

    foreach ($salaires as $s) {

        $converted = $currencyService->convert((float)$s->getMontant());

        $salairesData[] = [
            'id' => $s->getId(),
            'montant' => $converted,
            'mois' => $s->getMois(),
            'annee' => $s->getAnnee(),
            'statut' => $s->getStatut(),
            'contract' => $s->getContract()
        ];

        $totalDisplay += $converted;

        if ($s->getStatut() === 'PAYE') $paye++;
        else $attente++;
    }

    return $this->render('rh/salaires/index.html.twig', [
        'salaires' => $salairesData,
        'total_count' => $total,
        'paye_count' => $paye,
        'attente_count' => $attente,
        'total_montant' => $totalDisplay,
        'currency' => $currencyService->getCurrency()
    ]);
}
#[Route('/new', name: 'app_rh_salaire_new')]
public function new(Request $request, EntityManagerInterface $em, CurrencyService $currencyService): Response
{
    $currencyService->load();

    $salaire = new Salaire();
    $form = $this->createForm(SalaireType::class, $salaire);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {

        if (!$salaire->getContract()) {
            $form->addError(new \Symfony\Component\Form\FormError(
                '⚠ Choisissez un employé depuis la recherche'
            ));
        }

        $existing = null;
        if ($salaire->getContract()) {
            $existing = $em->getRepository(Salaire::class)->findOneBy([
                'contract' => $salaire->getContract(),
                'mois' => $salaire->getMois(),
                'annee' => $salaire->getAnnee(),
            ]);
        }

        if ($existing) {
            $form->addError(new \Symfony\Component\Form\FormError(
                '⚠ Salaire déjà existant pour cet employé ce mois !'
            ));
        }

        if ($form->isValid() && !$existing && $salaire->getContract()) {

            // 🔥 DISPLAY → TND
            $display = (float)$salaire->getMontant();
            $salaire->setMontant((string)$currencyService->convertToTnd($display));

            $em->persist($salaire);
            $em->flush();

            return $this->redirectToRoute('app_rh_salaire_index');
        }
    }

    return $this->render('rh/salaires/new.html.twig', [
        'form' => $form->createView(),
        'currency' => $currencyService->getCurrency(),
        'rate' => $currencyService->getRate()
    ]);
}

     #[Route('/{id}/edit', name: 'app_rh_salaire_edit')]
public function edit(Request $request, Salaire $salaire, EntityManagerInterface $em, CurrencyService $currencyService): Response
{
    $currencyService->load();

    // 🔥 TND → DISPLAY before form
    $display = $currencyService->convert((float)$salaire->getMontant());
    $salaire->setMontant((string)$display);

    $form = $this->createForm(SalaireType::class, $salaire, [
        'is_edit' => true
    ]);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // 🔥 DISPLAY → TND
        $display = (float)$salaire->getMontant();
        $salaire->setMontant((string)$currencyService->convertToTnd($display));

        $em->flush();

        return $this->redirectToRoute('app_rh_salaire_index');
    }

    return $this->render('rh/salaires/edit.html.twig', [
        'form' => $form->createView(),
        'salaire' => $salaire,
        'currency' => $currencyService->getCurrency(),
        'rate' => $currencyService->getRate()
    ]);
}
    #[Route('/{id}', name: 'app_rh_salaire_delete', methods: ['POST'])]
    public function delete(Request $request, Salaire $salaire, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$salaire->getId(), is_string($token) ? $token : '')) {
            $em->remove($salaire);
            $em->flush();
        }

        return $this->redirectToRoute('app_rh_salaire_index');
    }


#[Route('/export', name: 'app_rh_salaire_export')]
public function export(EntityManagerInterface $em): Response
{
    $salaires = $em->getRepository(Salaire::class)
        ->createQueryBuilder('s')
        ->join('s.contract', 'c')
        ->join('c.employe', 'e')
        ->join('e.user', 'u')
        ->getQuery()
        ->getResult();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // 🔥 HEADERS
    $headers = ['Employé', 'Mois', 'Année', 'Montant (TND)', 'Statut'];
    $col = 'A';

    foreach ($headers as $header) {
        $sheet->setCellValue($col.'1', $header);
        $col++;
    }

    // 🔥 STYLE HEADER
    $sheet->getStyle('A1:E1')->getFont()->setBold(true);
    $sheet->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('E9D5FF');

    // 🔥 DATA
    $row = 2;

    foreach ($salaires as $s) {
        $sheet->setCellValue('A'.$row, $s->getContract()->getEmploye()->getUser()->getFullName());
        $sheet->setCellValue('B'.$row, $s->getMois());
        $sheet->setCellValue('C'.$row, $s->getAnnee());
        $sheet->setCellValue('D'.$row, $s->getMontant()); // 🔥 ALWAYS TND
        $sheet->setCellValue('E'.$row, $s->getStatut());
        $row++;
    }

    // 🔥 AUTO SIZE
    foreach (range('A', 'E') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // 🔥 EXPORT
    $writer = new Xlsx($spreadsheet);

    $response = new Response();
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', 'attachment;filename="salaires.xlsx"');

    ob_start();
    $writer->save('php://output');
    $content = ob_get_clean();

    if ($content === false) {
        throw new \RuntimeException('Failed to generate Excel file');
    }

    $response->setContent($content);

    return $response;
}
}