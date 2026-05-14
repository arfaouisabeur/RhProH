<?php

namespace App\Controller;

use App\Entity\Salaire;
use App\Form\SalaireType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/salaire')]
final class SalaireController extends AbstractController
{
    #[Route(name: 'app_salaire_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $salaires = $entityManager
            ->getRepository(Salaire::class)
            ->findAll();

        return $this->render('salaire/index.html.twig', [
            'salaires' => $salaires,
        ]);
    }

    #[Route('/new', name: 'app_salaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $salaire = new Salaire();
        $form = $this->createForm(SalaireType::class, $salaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($salaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_salaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('salaire/new.html.twig', [
            'salaire' => $salaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_salaire_show', methods: ['GET'])]
    public function show(Salaire $salaire): Response
    {
        return $this->render('salaire/show.html.twig', [
            'salaire' => $salaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_salaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Salaire $salaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SalaireType::class, $salaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_salaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('salaire/edit.html.twig', [
            'salaire' => $salaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_salaire_delete', methods: ['POST'])]
    public function delete(Request $request, Salaire $salaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$salaire->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($salaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_salaire_index', [], Response::HTTP_SEE_OTHER);
    }
}
