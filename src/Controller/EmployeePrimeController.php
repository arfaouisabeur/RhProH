<?php

namespace App\Controller;

use App\Entity\Prime;
use App\Entity\Employe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CurrencyService; 

#[Route('/employe/primes')]
#[IsGranted('ROLE_EMPLOYE')]
class EmployeePrimeController extends AbstractController
{
    #[Route('/', name: 'app_employee_prime_index')]
public function index(EntityManagerInterface $em, CurrencyService $currencyService): Response
{
    $user = $this->getUser();

    $employe = $em->getRepository(Employe::class)
        ->findOneBy(['user' => $user]);

    $primes = $em->getRepository(Prime::class)
        ->createQueryBuilder('p')
        ->join('p.contract', 'c')
        ->where('c.employe = :emp')
        ->setParameter('emp', $employe)
        ->getQuery()
        ->getResult();

    // 🔥 ADD ONLY
    $currencyService->load();

    $convertedPrimes = [];

    foreach ($primes as $p) {
        $convertedPrimes[$p->getId()] = $currencyService->convert((float)$p->getMontant());
    }

    return $this->render('employee/primes/index.html.twig', [
        'primes' => $primes,

        // 🔥 ADD ONLY
        'convertedPrimes' => $convertedPrimes,
        'currency' => $currencyService->getCurrency()
    ]);
}
}