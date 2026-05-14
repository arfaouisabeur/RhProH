<?php

namespace App\Controller;

use App\Entity\TypeService;
use App\Form\TypeServiceType;
use App\Repository\TypeServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/type/service')]
#[IsGranted('ROLE_RH')]
final class TypeServiceController extends AbstractController
{
    #[Route(name: 'app_type_service_index', methods: ['GET'])]
    public function index(Request $request, TypeServiceRepository $typeServiceRepository): Response
    {
        $search   = $request->query->get('search', '');
        $searchBy = $request->query->get('searchBy', 'all');
        $sortBy   = $request->query->get('sortBy', 'nom');
        $sortDir  = strtolower($request->query->get('sortDir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowed = ['nom', 'categorie'];
        if (!in_array($sortBy, $allowed)) $sortBy = 'nom';

        $qb = $typeServiceRepository->createQueryBuilder('t');

        if ($search !== '') {
            $s = '%' . $search . '%';
            if ($searchBy === 'nom') {
                $qb->andWhere('t.nom LIKE :s')->setParameter('s', $s);
            } elseif ($searchBy === 'categorie') {
                $qb->andWhere('t.categorie LIKE :s')->setParameter('s', $s);
            } else {
                $qb->andWhere('t.nom LIKE :s OR t.categorie LIKE :s OR t.description LIKE :s')
                   ->setParameter('s', $s);
            }
        }

        $qb->orderBy('t.' . $sortBy, $sortDir);

        return $this->render('type_service/index.html.twig', [
            'type_services' => $qb->getQuery()->getResult(),
            'search'        => $search,
            'searchBy'      => $searchBy,
            'sortBy'        => $sortBy,
            'sortDir'       => $sortDir,
        ]);
    }

    #[Route('/new', name: 'app_type_service_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $typeService = new TypeService();
        $form = $this->createForm(TypeServiceType::class, $typeService);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($typeService);
            $entityManager->flush();

            $this->addFlash('success', '✅ Type de service créé avec succès.');
            return $this->redirectToRoute('app_type_service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_service/new.html.twig', [
            'type_service' => $typeService,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_type_service_show', methods: ['GET'])]
    public function show(TypeService $typeService): Response
    {
        return $this->render('type_service/show.html.twig', [
            'type_service' => $typeService,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_type_service_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TypeService $typeService, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TypeServiceType::class, $typeService);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', '✅ Type de service mis à jour.');
            return $this->redirectToRoute('app_type_service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_service/edit.html.twig', [
            'type_service' => $typeService,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_type_service_delete', methods: ['POST'])]
    public function delete(Request $request, TypeService $typeService, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$typeService->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($typeService);
            $entityManager->flush();
            $this->addFlash('success', '🗑️ Type de service supprimé (et toutes ses demandes associées).');
        }

        return $this->redirectToRoute('app_type_service_index', [], Response::HTTP_SEE_OTHER);
    }
}
