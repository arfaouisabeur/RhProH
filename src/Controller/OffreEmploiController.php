<?php

namespace App\Controller;

use App\Entity\OffreEmploi;
use App\Form\OffreEmploiType;
use App\Repository\CandidatRepository;
use App\Repository\OffreEmploiRepository;
use App\Repository\RHRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OffreEmploiController extends AbstractController
{
    public function __construct(
        private readonly CandidatRepository $candidatRepo
    ) {}

    private function getGeoapifyKey(): string
    {
        $key = $_ENV['GEOAPIFY_API_KEY'] ?? $_SERVER['GEOAPIFY_API_KEY'] ?? \getenv('GEOAPIFY_API_KEY');
        return is_string($key) ? $key : '';
    }

    // ── Admin routes ─────────────────────────────────────────────────────────

    #[Route('/admin/offres', name: 'app_offre_emploi_index', methods: ['GET'])]
    public function adminIndex(Request $request, OffreEmploiRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        return $this->render('offre_emploi/index.html.twig', [
            'offre_emplois' => $this->buildSearchQuery($repo, $q),
            'search'        => $q,
        ]);
    }

    #[Route('/admin/offres/ajax-search', name: 'app_offre_emploi_ajax_search', methods: ['GET'])]
    public function ajaxSearch(Request $request, OffreEmploiRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        return $this->render('offre_emploi/_table.html.twig', [
            'offre_emplois' => $this->buildSearchQuery($repo, $q),
        ]);
    }

    #[Route('/admin/offres/new', name: 'app_offre_emploi_new', methods: ['GET', 'POST'])]
    public function adminNew(Request $request, EntityManagerInterface $em, RHRepository $rhRepository): Response
    {
        $offre       = new OffreEmploi();
        $currentUser = $this->getUser();
        $currentRh   = $currentUser ? $rhRepository->findOneBy(['user' => $currentUser]) : null;

        // Initialiser avec des valeurs par défaut pour éviter les erreurs null
        $offre->setTitre('');
        $offre->setLocalisation('');
        $offre->setTypeContrat('');
        $offre->setStatut('');
        $offre->setDescription('');

        if ($currentRh) {
            $offre->setRh($currentRh);
        }

        $form = $this->createForm(OffreEmploiType::class, $offre, [
            'show_rh_field' => ($currentRh === null),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($currentRh !== null) {
                $offre->setRh($currentRh);
            }
            $em->persist($offre);
            $em->flush();
            $this->addFlash('success', 'Offre créée avec succès.');
            return $this->redirectToRoute('app_offre_emploi_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('offre_emploi/new.html.twig', [
            'form'         => $form,
            'offre_emploi' => $offre,
            'geoapify_key' => $this->getGeoapifyKey(),
        ]);
    }

    #[Route('/admin/offres/{id}', name: 'app_offre_emploi_show', methods: ['GET'])]
    public function adminShow(int $id, OffreEmploiRepository $repository): Response
    {
        $offre = $repository->find($id);
        if (!$offre) {
            $this->addFlash('error', 'Offre d\'emploi non trouvée.');
            return $this->redirectToRoute('app_offre_emploi_index');
        }
        
        return $this->render('offre_emploi/show.html.twig', ['offre_emploi' => $offre]);
    }

    #[Route('/admin/offres/{id}/edit', name: 'app_offre_emploi_edit', methods: ['GET', 'POST'])]
    public function adminEdit(int $id, Request $request, EntityManagerInterface $em, RHRepository $rhRepository, OffreEmploiRepository $repository): Response
    {
        $offre = $repository->find($id);
        if (!$offre) {
            $this->addFlash('error', 'Offre d\'emploi non trouvée.');
            return $this->redirectToRoute('app_offre_emploi_index');
        }
        
        $currentUser = $this->getUser();
        $currentRh   = $currentUser ? $rhRepository->findOneBy(['user' => $currentUser]) : null;

        $form = $this->createForm(OffreEmploiType::class, $offre, [
            'show_rh_field' => ($currentRh === null),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($currentRh !== null) {
                $offre->setRh($currentRh);
            }
            $em->flush();
            $this->addFlash('success', 'Offre modifiée avec succès.');
            return $this->redirectToRoute('app_offre_emploi_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('offre_emploi/edit.html.twig', [
            'form'         => $form,
            'offre_emploi' => $offre,
            'geoapify_key' => $this->getGeoapifyKey(),
        ]);
    }

    #[Route('/admin/offres/{id}', name: 'app_offre_emploi_delete', methods: ['POST'])]
    public function adminDelete(int $id, Request $request, EntityManagerInterface $em, OffreEmploiRepository $repository): Response
    {
        $offreEmploi = $repository->find($id);
        if (!$offreEmploi) {
            $this->addFlash('error', 'Offre d\'emploi non trouvée.');
            return $this->redirectToRoute('app_offre_emploi_index');
        }
        
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $offreEmploi->getId(), is_string($token) ? $token : null)) {
            $em->remove($offreEmploi);
            $em->flush();
            $this->addFlash('success', 'Offre supprimée.');
        }
        return $this->redirectToRoute('app_offre_emploi_index', [], Response::HTTP_SEE_OTHER);
    }

    // ── Candidat public routes ────────────────────────────────────────────────

    #[Route('/offres', name: 'candidat_offres', methods: ['GET'])]
    public function candidatIndex(Request $request, OffreEmploiRepository $repo): Response
    {
        $q  = trim((string) $request->query->get('q', ''));
        $qb = $repo->createQueryBuilder('o')->orderBy('o.datePublication', 'DESC');
        if ($q !== '') {
            $qb->andWhere('o.titre LIKE :q OR o.localisation LIKE :q OR o.typeContrat LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        return $this->render('candidat/offre/index.html.twig', [
            'offres'       => $qb->getQuery()->getResult(),
            'search'       => $q,
            'favoriteIds'  => $this->resolveFavoriteIds(),
            'geoapify_key' => $this->getGeoapifyKey(),
        ]);
    }

    #[Route('/offres/{id}', name: 'candidat_offre_show', methods: ['GET'])]
    public function candidatShow(int $id, OffreEmploiRepository $repository): Response
    {
        $offre = $repository->find($id);
        if (!$offre) {
            $this->addFlash('error', 'Offre d\'emploi non trouvée.');
            return $this->redirectToRoute('candidat_offres');
        }
        
        $favoriteIds = $this->resolveFavoriteIds();

        return $this->render('candidat/offre/show.html.twig', [
            'offre'        => $offre,
            'isFavorite'   => in_array($offre->getId(), $favoriteIds, true),
            'geoapify_key' => $this->getGeoapifyKey(),
        ]);
    }

    // ── Favorites ─────────────────────────────────────────────────────────────

    #[Route('/offres/{id}/favori', name: 'candidat_toggle_favori', methods: ['POST'])]
    public function toggleFavori(int $id, EntityManagerInterface $em, OffreEmploiRepository $repository): JsonResponse
    {
        $offre = $repository->find($id);
        if (!$offre) {
            return $this->json(['success' => false, 'message' => 'Offre d\'emploi non trouvée.'], 404);
        }
        
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Vous devez être connecté.'], 401);
        }

        $candidat = $this->candidatRepo->findOneBy(['user' => $user]);

        if (!$candidat) {
            $candidat = new \App\Entity\Candidat();
            $candidat->setUser($user);
            $em->persist($candidat);
            $em->flush();
        }

        if ($candidat->hasOffreFavorite($offre)) {
            $candidat->removeOffreFavorite($offre);
            $isFavorite = false;
            $message    = 'Offre retirée de vos favoris.';
        } else {
            $candidat->addOffreFavorite($offre);
            $isFavorite = true;
            $message    = 'Offre ajoutée à vos favoris !';
        }

        $em->flush();

        return $this->json([
            'success'    => true,
            'isFavorite' => $isFavorite,
            'message'    => $message,
        ]);
    }

    #[Route('/mes-favoris', name: 'candidat_mes_favoris', methods: ['GET'])]
    public function mesFavoris(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $candidat = $this->candidatRepo->findOneBy(['user' => $user]);
        if (!$candidat) {
            $candidat = new \App\Entity\Candidat();
            $candidat->setUser($user);
            $em = $this->container->get('doctrine.orm.entity_manager');
            $em->persist($candidat);
            $em->flush();
        }
        $offres   = $candidat ? $candidat->getOffresFavorites()->toArray() : [];

        return $this->render('candidat/offre/favoris.html.twig', [
            'offres'       => $offres,
            'favoriteIds'  => array_map(fn($o) => $o->getId(), $offres),
            'geoapify_key' => $this->getGeoapifyKey(),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * @return array<int>
     */
    private function resolveFavoriteIds(): array
    {
        $user = $this->getUser();
        if (!$user) {
            return [];
        }

        $candidat = $this->candidatRepo->findOneBy(['user' => $user]);
        if (!$candidat) {
            return [];
        }

        return array_map(
            fn($o) => $o->getId(),
            $candidat->getOffresFavorites()->toArray()
        );
    }

    /**
     * @return array<int, OffreEmploi>
     */
    private function buildSearchQuery(OffreEmploiRepository $repo, string $q): array
    {
        $qb = $repo->createQueryBuilder('o')->orderBy('o.datePublication', 'DESC');
        if ($q !== '') {
            $qb->andWhere('o.titre LIKE :q OR o.localisation LIKE :q OR o.typeContrat LIKE :q OR o.statut LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }
        return $qb->getQuery()->getResult();
    }
}
