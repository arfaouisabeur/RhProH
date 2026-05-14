<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Candidat;
use App\Entity\Employe;
use App\Entity\RH;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/rh')]
#[IsGranted('ROLE_RH')]
class RHController extends AbstractController
{
    #[Route('/dashboard', name: 'app_rh_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $candidats = $entityManager->getRepository(User::class)->findAllCandidats();
        $employes = $entityManager->getRepository(User::class)->findAllEmployes();

        return $this->render('rh/dashboard.html.twig', [
            'candidats' => $candidats,
            'employes' => $employes,
        ]);
    }

    #[Route('/candidats', name: 'app_rh_candidats')]
    public function listCandidats(EntityManagerInterface $entityManager, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'DESC');
        
        $queryBuilder = $entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', 'CANDIDAT');
        
        if ($search) {
            $queryBuilder->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        $queryBuilder->orderBy('u.' . $sort, $order);
        $candidats = $queryBuilder->getQuery()->getResult();

        // Si c'est une requête AJAX, retourner seulement le tableau
        if ($request->isXmlHttpRequest()) {
            return $this->render('rh/_candidats_table.html.twig', [
                'candidats' => $candidats,
            ]);
        }

        return $this->render('rh/candidats.html.twig', [
            'candidats' => $candidats,
            'search' => $search,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    #[Route('/employes', name: 'app_rh_employes')]
    public function listEmployes(EntityManagerInterface $entityManager, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'id');
        $order = $request->query->get('order', 'DESC');
        
        $queryBuilder = $entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->innerJoin('u.employe', 'e')
            ->where('u.role = :role')
            ->setParameter('role', 'EMPLOYE');
        
        if ($search) {
            $queryBuilder->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search OR e.matricule LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        $queryBuilder->orderBy('u.' . $sort, $order);
        $employes = $queryBuilder->getQuery()->getResult();

        // Si c'est une requête AJAX, retourner seulement le tableau
        if ($request->isXmlHttpRequest()) {
            return $this->render('rh/_employes_table.html.twig', [
                'employes' => $employes,
            ]);
        }

        return $this->render('rh/employes.html.twig', [
            'employes' => $employes,
            'search' => $search,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    #[Route('/export/pdf/{type}', name: 'app_rh_export_pdf', requirements: ['type' => 'candidats|employes|all'])]
    public function exportPdf(string $type, EntityManagerInterface $entityManager): Response
    {
        $users = [];
        $title = '';
        
        if ($type === 'candidats' || $type === 'all') {
            $candidats = $entityManager->getRepository(User::class)->findAllCandidats();
            $users = array_merge($users, $candidats);
            $title = 'Liste des Candidats';
        }
        
        if ($type === 'employes' || $type === 'all') {
            $employes = $entityManager->getRepository(User::class)->findAllEmployes();
            $users = array_merge($users, $employes);
            $title = $type === 'all' ? 'Liste des Utilisateurs' : 'Liste des Employés';
        }

        $html = $this->renderView('rh/export_pdf.html.twig', [
            'users' => $users,
            'title' => $title,
            'date' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="export_' . $type . '_' . date('Y-m-d') . '.pdf"',
        ]);
    }

    #[Route('/export/csv/{type}', name: 'app_rh_export_csv', requirements: ['type' => 'candidats|employes|all'])]
    public function exportCsv(string $type, EntityManagerInterface $entityManager): StreamedResponse
    {
        $response = new StreamedResponse(function() use ($type, $entityManager) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            if ($type === 'candidats' || $type === 'all') {
                fputcsv($handle, ['CANDIDATS']);
                fputcsv($handle, ['ID', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Adresse', 'Niveau d\'études', 'Expérience (années)']);
                
                $candidats = $entityManager->getRepository(User::class)->findAllCandidats();
                foreach ($candidats as $user) {
                    fputcsv($handle, [
                        $user->getId(),
                        $user->getPrenom(),
                        $user->getNom(),
                        $user->getEmail(),
                        $user->getTelephone(),
                        $user->getAdresse(),
                        $user->getCandidat() ? $user->getCandidat()->getNiveauEtude() : '',
                        $user->getCandidat() ? $user->getCandidat()->getExperience() : 0,
                    ]);
                }
                fputcsv($handle, []);
            }
            
            if ($type === 'employes' || $type === 'all') {
                fputcsv($handle, ['EMPLOYÉS']);
                fputcsv($handle, ['ID', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Adresse', 'Matricule', 'Poste', 'Date d\'embauche']);
                
                $employes = $entityManager->getRepository(User::class)->findAllEmployes();
                foreach ($employes as $user) {
                    $employe = $user->getEmploye();
                    fputcsv($handle, [
                        $user->getId(),
                        $user->getPrenom(),
                        $user->getNom(),
                        $user->getEmail(),
                        $user->getTelephone(),
                        $user->getAdresse(),
                        $employe ? $employe->getMatricule() : '',
                        $employe ? $employe->getPosition() : '',
                        $employe && $employe->getDateEmbauche() ? $employe->getDateEmbauche()->format('d/m/Y') : '',
                    ]);
                }
            }
            
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export_' . $type . '_' . date('Y-m-d') . '.csv"');

        return $response;
    }
    /*

    #[Route('/user/new/{role}', name: 'app_rh_user_new', requirements: ['role' => 'candidat|employe|rh'])]
    public function newUser(
        string $role,
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['role' => strtoupper($role)]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if email already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->render('rh/user_form.html.twig', [
                    'form' => $form->createView(),
                    'title' => 'Nouvel Utilisateur',
                ]);
            }

            // Generate a temporary password
            $tempPassword = bin2hex(random_bytes(4));
            $hashedPassword = $passwordHasher->hashPassword($user, $tempPassword);
            $user->setMotDePasse($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush(); // Flush pour obtenir l'ID du user

            // Create specific entity based on role
            switch ($user->getRole()) {
                case User::ROLE_CANDIDAT:
                    $candidat = new Candidat();
                    $candidat->setUser($user);
                    $candidat->setNiveauEtude($form->get('niveauEtude')->getData() ?? '');
                    $candidat->setExperience($form->get('experience')->getData() ?? 0);
                    $entityManager->persist($candidat);
                    break;

                case User::ROLE_EMPLOYE:
                    // Check if matricule already exists
                    $existingMatricule = $entityManager->getRepository(Employe::class)->findOneBy([
                        'matricule' => $form->get('matricule')->getData()
                    ]);
                    if ($existingMatricule) {
                        $this->addFlash('error', 'Ce matricule est déjà utilisé.');
                        return $this->render('rh/user_form.html.twig', [
                            'form' => $form->createView(),
                            'title' => 'Nouvel Utilisateur',
                        ]);
                    }

                    $employe = new Employe();
                    $employe->setUser($user);
                    $employe->setMatricule($form->get('matricule')->getData());
                    $employe->setPosition($form->get('position')->getData());
                    $employe->setDateEmbauche(new \DateTime());
                    $entityManager->persist($employe);
                    break;

                case User::ROLE_RH:
                    $rh = new RH();
                    $rh->setUser($user);
                    $entityManager->persist($rh);
                    break;
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès. Mot de passe temporaire: ' . $tempPassword);
            return $this->redirectToRoute('app_rh_dashboard');
        }
            

        return $this->render('rh/user_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nouvel Utilisateur',
        ]);
    }
        */

    #[Route('/user/edit/{id}', name: 'app_rh_user_edit')]
    public function editUser(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(UserType::class, $user, ['role' => $user->getRole()]);

        // Pre-fill specific fields
        if ($user->getRole() === User::ROLE_CANDIDAT && $user->getCandidat()) {
            $form->get('niveauEtude')->setData($user->getCandidat()->getNiveauEtude());
            $form->get('experience')->setData($user->getCandidat()->getExperience());
        }

        if ($user->getRole() === User::ROLE_EMPLOYE && $user->getEmploye()) {
            $form->get('matricule')->setData($user->getEmploye()->getMatricule());
            $form->get('position')->setData($user->getEmploye()->getPosition());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update specific entity based on role
            switch ($user->getRole()) {
                case User::ROLE_CANDIDAT:
                    if ($user->getCandidat()) {
                        $user->getCandidat()->setNiveauEtude($form->get('niveauEtude')->getData());
                        $user->getCandidat()->setExperience((int)($form->get('experience')->getData() ?? 0));
                    }
                    break;

                case User::ROLE_EMPLOYE:
                    if ($user->getEmploye()) {
                        $user->getEmploye()->setPosition($form->get('position')->getData());
                    }
                    break;
            }

            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
            return $this->redirectToRoute('app_rh_dashboard');
        }

        return $this->render('rh/user_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier Utilisateur',
        ]);
    }


#[Route('/user/{id}/toggle-statut', name: 'app_rh_user_toggle_statut', methods: ['POST'])]
public function toggleStatut(User $user, EntityManagerInterface $em, Request $request): Response
{
    if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
        if ($user->getStatut() === 'actif') {
            $user->setStatut('inactif');
            $this->addFlash('warning', 'L\'utilisateur a été désactivé.');
        } else {
            $user->setStatut('actif');
            $this->addFlash('success', 'L\'utilisateur a été activé.');
        }
        $em->flush();
    }
    $redirect = $request->request->get('redirect', 'candidats');
    if ($redirect === 'employes') {
        return $this->redirectToRoute('app_rh_employes');
    }

    return $this->redirectToRoute('app_rh_candidats'); // adapte le nom de ta route
}
/*
    #[Route('/user/delete/{id}', name: 'app_rh_user_delete', methods: ['POST'])]
    public function deleteUser(
        User $user,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $currentUser = $this->getUser();
        $isSelfDelete = $currentUser && $currentUser->getId() === $user->getId();

        // Delete avatar file if exists
        if ($user->getAvatarPath()) {
            $avatarPath = $this->getParameter('kernel.project_dir') . '/public/' . $user->getAvatarPath();
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }
        }

        $entityManager->remove($user);
        $entityManager->flush();

        // Si l'utilisateur supprime son propre compte, invalider la session
        if ($isSelfDelete) {
            $request->getSession()->invalidate();
            $this->container->get('security.token_storage')->setToken(null);
            $this->addFlash('success', 'Votre compte a été supprimé.');
            return $this->redirectToRoute('app_home');
        }

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('app_rh_dashboard');
    }
        */

    #[Route('/user/view/{id}', name: 'app_rh_user_view')]
    public function viewUser(User $user): Response
    {
        return $this->render('rh/user_view.html.twig', [
            'user' => $user,
        ]);
    }
}
