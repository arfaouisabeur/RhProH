<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Candidat;
use App\Entity\Employe;
use App\Form\RegistrationCandidatType;
use App\Form\RegistrationEmployeType;
use App\Service\CaptchaService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Form\FormError;
use League\OAuth2\Client\Provider\GoogleUser;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register/candidat', name: 'app_register_candidat')]
    public function registerCandidat(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        CaptchaService $captchaService
    ): Response {

        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationCandidatType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ✅ Vérification captcha
            $captchaInput = $request->request->get('captcha_input');
            if (!$captchaService->verify($captchaInput)) {
                $this->addFlash('error', 'Code captcha invalide. Veuillez réessayer.');
                return $this->render('auth/register_candidat.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $existingUser = $entityManager->getRepository(User::class)->findOneBy([
                'email' => $user->getEmail()
            ]);

            if ($existingUser) {
                $form->get('email')->addError(new FormError('Cet email est déjà utilisé.'));
                return $this->render('auth/register_candidat.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $user->setRole(User::ROLE_CANDIDAT);
            $user->setStatut('actif');

            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setMotDePasse($hashedPassword);

            $avatarFile = $form->get('avatar')->getData();
            if ($avatarFile) {
                $newFilename = 'avatar_user_temp_' . uniqid() . '.' . $avatarFile->guessExtension();
                $avatarFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                    $newFilename
                );
                $user->setAvatarPath('uploads/avatars/' . $newFilename);
            }

            $candidat = new Candidat();
            $candidat->setNiveauEtude($form->get('niveauEtude')->getData() ?? '');
            $candidat->setExperience($form->get('experience')->getData() ?? 0);

            $entityManager->persist($user);
            $entityManager->flush();

            if ($user->getAvatarPath() && str_contains($user->getAvatarPath(), 'temp')) {
                $oldPath = $this->getParameter('kernel.project_dir') . '/public/' . $user->getAvatarPath();
                $newFilename = 'avatar_user_' . $user->getId() . '.' . pathinfo($oldPath, PATHINFO_EXTENSION);
                $newPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $newFilename;
                if (file_exists($oldPath)) {
                    rename($oldPath, $newPath);
                    $user->setAvatarPath('uploads/avatars/' . $newFilename);
                    $entityManager->persist($user);
                }
            }

            $candidat->setUser($user);
            $entityManager->persist($candidat);
            $entityManager->flush();

            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register_candidat.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/register/employe', name: 'app_register_employe')]
    public function registerEmploye(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        CaptchaService $captchaService
    ): Response {

        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationEmployeType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ✅ Vérification captcha
            $captchaInput = $request->request->get('captcha_input');
            if (!$captchaService->verify($captchaInput)) {
                $this->addFlash('error', 'Code captcha invalide. Veuillez réessayer.');
                return $this->render('auth/register_employe.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $existingUser = $entityManager->getRepository(User::class)->findOneBy([
                'email' => $user->getEmail()
            ]);

            if ($existingUser) {
                $form->get('email')->addError(new FormError('Cet email est déjà utilisé.'));
                return $this->render('auth/register_employe.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $existingMatricule = $entityManager->getRepository(Employe::class)->findOneBy([
                'matricule' => $form->get('matricule')->getData()
            ]);

            if ($existingMatricule) {
                $form->get('matricule')->addError(new FormError('Ce matricule est déjà utilisé.'));
                return $this->render('auth/register_employe.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $user->setRole(User::ROLE_EMPLOYE);
            $user->setStatut('actif');

            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setMotDePasse($hashedPassword);

            $avatarFile = $form->get('avatar')->getData();
            if ($avatarFile) {
                $newFilename = 'avatar_user_temp_' . uniqid() . '.' . $avatarFile->guessExtension();
                $avatarFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                    $newFilename
                );
                $user->setAvatarPath('uploads/avatars/' . $newFilename);
            }

            $employe = new Employe();
            $employe->setMatricule($form->get('matricule')->getData());
            $employe->setPosition($form->get('position')->getData());
            $dateEmbauche = $form->get('dateEmbauche')->getData();
            if ($dateEmbauche instanceof \DateTime) {
                $dateEmbauche = \DateTimeImmutable::createFromMutable($dateEmbauche);
            }
            $employe->setDateEmbauche($dateEmbauche);

            $entityManager->persist($user);
            $entityManager->flush();

            if ($user->getAvatarPath() && str_contains($user->getAvatarPath(), 'temp')) {
                $oldPath = $this->getParameter('kernel.project_dir') . '/public/' . $user->getAvatarPath();
                $newFilename = 'avatar_user_' . $user->getId() . '.' . pathinfo($oldPath, PATHINFO_EXTENSION);
                $newPath = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $newFilename;
                if (file_exists($oldPath)) {
                    rename($oldPath, $newPath);
                    $user->setAvatarPath('uploads/avatars/' . $newFilename);
                    $entityManager->persist($user);
                }
            }

            $employe->setUser($user);
            $entityManager->persist($employe);
            $entityManager->flush();

            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register_employe.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ✅ Google OAuth corrigé — redirige vers Google
    #[Route('/connect/google', name: 'connect_google')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect(['email', 'profile']);
    }

    // ✅ Callback Google — traité par le Security Authenticator automatiquement
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): Response
    {
        // Géré automatiquement par GoogleAuthenticator
        return $this->redirectToRoute('app_home');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void {}
}