<?php

namespace App\Controller;

use App\Entity\Salaire;
use App\Entity\Employe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CurrencyService;

// 🔥 CHART
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/employe/salaires')]
#[IsGranted('ROLE_EMPLOYE')]
class EmployeeSalaireController extends AbstractController
{
    #[Route('/', name: 'app_employee_salaire_index')]
    public function index(
        EntityManagerInterface $em,
        CurrencyService $currencyService,
        ChartBuilderInterface $chartBuilder
    ): Response
    {
        $user = $this->getUser();

        $employe = $em->getRepository(Employe::class)
            ->findOneBy(['user' => $user]);

        $salaires = $em->getRepository(Salaire::class)
            ->createQueryBuilder('s')
            ->join('s.contract', 'c')
            ->where('c.employe = :emp')
            ->setParameter('emp', $employe)
            ->orderBy('s.annee', 'ASC')
            ->addOrderBy('s.mois', 'ASC')
            ->getQuery()
            ->getResult();

        // 🔥 LOAD CURRENCY
        $currencyService->load();

        // 🔥 CLEAN ARRAY (NO dynamic property)
        $convertedSalaires = [];

        // 🔥 CHART DATA
        $labels = [];
        $data = [];

        foreach ($salaires as $s) {
            $converted = $currencyService->convert((float)$s->getMontant());

            $convertedSalaires[$s->getId()] = $converted;

            // chart
            $labels[] = $s->getMois() . '/' . $s->getAnnee();
            $data[] = $converted;
        }

        // 🔥 CREATE CHART
        $chart = $chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Salary (' . $currencyService->getCurrency() . ')',
                    'data' => $data,
                    'borderColor' => 'rgb(123,47,247)',
                    'backgroundColor' => 'rgba(123,47,247,0.2)',
                    'tension' => 0.4,
                    'fill' => true
                ]
            ]
        ]);
        $chart->setOptions([
    'responsive' => true,
    'maintainAspectRatio' => false,
    'plugins' => [
        'legend' => [
            'display' => true
        ]
    ],
    'scales' => [
        'y' => [
            'beginAtZero' => true
        ]
    ]
]);

        return $this->render('employee/salaires/index.html.twig', [
            'salaires' => $salaires,
            'convertedSalaires' => $convertedSalaires,
            'currency' => $currencyService->getCurrency(),
            'chart' => $chart
        ]);
    }
}