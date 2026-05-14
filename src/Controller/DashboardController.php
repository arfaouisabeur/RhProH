<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileCandidatType;
use App\Form\ProfileEmployeType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_home')]
    public function dashboard(): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->isRH()) {
            return $this->redirectToRoute('app_rh_dashboard');
        }

        if ($user->isCandidat()) {
            return $this->redirectToRoute('app_candidat_dashboard');
        }

        if ($user->isEmploye()) {
            return $this->redirectToRoute('app_employe_dashboard');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/candidat/dashboard', name: 'app_candidat_dashboard')]
    #[IsGranted('ROLE_CANDIDAT')]
    public function candidatDashboard(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $candidat = $user->getCandidat();

        return $this->render('dashboard/candidat_dashboard.html.twig', [
            'user' => $user,
            'candidat' => $candidat,
        ]);
    }

    #[Route('/candidat/profile', name: 'app_candidat_profile')]
    #[IsGranted('ROLE_CANDIDAT')]
    public function candidatProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $candidat = $user->getCandidat();

        $form = $this->createForm(ProfileCandidatType::class, $user);

        // Set candidat data in form BEFORE handleRequest
        if ($candidat && !$request->isMethod('POST')) {
            $form->get('niveauEtude')->setData($candidat->getNiveauEtude());
            $form->get('experience')->setData($candidat->getExperience());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Check if email already exists (except current user)
                $newEmail = $form->get('email')->getData();
                if ($newEmail !== $user->getEmail()) {
                    $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $newEmail]);
                    if ($existingUser) {
                        $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
                        return $this->redirectToRoute('app_candidat_profile');
                    }
                }

                // Check if telephone already exists (except current user)
                $newTelephone = $form->get('telephone')->getData();
                if ($newTelephone !== $user->getTelephone()) {
                    $existingPhone = $entityManager->getRepository(User::class)->findOneBy(['telephone' => $newTelephone]);
                    if ($existingPhone && $existingPhone->getId() !== $user->getId()) {
                        $this->addFlash('error', 'Ce numéro de téléphone est déjà utilisé.');
                        return $this->redirectToRoute('app_candidat_profile');
                    }
                }

                // Update candidat info
                if ($candidat) {
                    $candidat->setNiveauEtude($form->get('niveauEtude')->getData());
                    $candidat->setExperience($form->get('experience')->getData());
                }

                // Handle avatar upload
                $avatarFile = $form->get('avatar')->getData();
                if ($avatarFile) {
                    $newFilename = 'avatar_user_' . $user->getId() . '.' . $avatarFile->guessExtension();
                    $avatarFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                        $newFilename
                    );
                    $user->setAvatarPath('uploads/avatars/' . $newFilename);
                }

                $entityManager->flush();
                $this->addFlash('success', 'Votre profil a été mis à jour avec succès. (100% Valid)');

                return $this->redirectToRoute('app_candidat_profile');
            } else {
                $this->addFlash('error', 'LE FORMULAIRE A ETE SOUMIS MAIS EST INVALIDE ! Erreurs: ' . (string) $form->getErrors(true));
            }
        }


        return $this->render('dashboard/candidat_profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/employe/dashboard', name: 'app_employe_dashboard')]
    #[IsGranted('ROLE_EMPLOYE')]
    public function employeDashboard(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $employe = $user->getEmploye();

        return $this->render('dashboard/employe_dashboard.html.twig', [
            'user' => $user,
            'employe' => $employe,
        ]);
    }

    #[Route('/employe/profile', name: 'app_employe_profile')]
    #[IsGranted('ROLE_EMPLOYE')]
    public function employeProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $employe = $user->getEmploye();

        $form = $this->createForm(ProfileEmployeType::class, $user);

        // Set employe data in form BEFORE handleRequest
        if ($employe && !$request->isMethod('POST')) {
            $form->get('position')->setData($employe->getPosition());
            $form->get('dateEmbauche')->setData($employe->getDateEmbauche());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if email already exists (except current user)
            $newEmail = $form->get('email')->getData();
            if ($newEmail !== $user->getEmail()) {
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $newEmail]);
                if ($existingUser) {
                    $this->addFlash('error', 'Cet email est déjà utilisé par un autre utilisateur.');
                    return $this->redirectToRoute('app_employe_profile');
                }
            }

            // Check if telephone already exists (except current user)
            $newTelephone = $form->get('telephone')->getData();
            if ($newTelephone !== $user->getTelephone()) {
                $existingPhone = $entityManager->getRepository(User::class)->findOneBy(['telephone' => $newTelephone]);
                if ($existingPhone && $existingPhone->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Ce numéro de téléphone est déjà utilisé.');
                    return $this->redirectToRoute('app_employe_profile');
                }
            }

            // Update employe info
            if ($employe) {
                $employe->setPosition($form->get('position')->getData());
                $employe->setDateEmbauche($form->get('dateEmbauche')->getData());
            }

            // Handle avatar upload
            $avatarFile = $form->get('avatar')->getData();
            if ($avatarFile) {
                $newFilename = 'avatar_user_' . $user->getId() . '.' . $avatarFile->guessExtension();
                $avatarFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/avatars',
                    $newFilename
                );
                $user->setAvatarPath('uploads/avatars/' . $newFilename);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_employe_profile');
        }


        return $this->render('dashboard/employe_profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/delete-account', name: 'app_delete_account', methods: ['POST'])]
    public function deleteAccount(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Delete avatar file if exists
        if ($user->getAvatarPath()) {
            $avatarPath = $this->getParameter('kernel.project_dir') . '/public/' . $user->getAvatarPath();
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre compte a été supprimé avec succès.');
        return $this->redirectToRoute('app_home');
    }
    // src/Controller/ProfileController.php  (ajoute cette méthode)
#[Route('/save-avatar', name: 'app_save_avatar', methods: ['POST'])]
public function saveAvatar(Request $request, EntityManagerInterface $em): JsonResponse
{
    try {
        $data = json_decode($request->getContent(), true);

    // Vérification JSON
    if (!$data) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Données invalides',
            'debug' => [
                'json_error' => json_last_error_msg(),
                'raw_content' => $request->getContent()
            ]
        ], 400);
    }

    // Vérification CSRF
    if (!$this->isCsrfTokenValid('save_avatar', $data['_token'] ?? '')) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Token CSRF invalide'
        ], 403);
    }

    // Vérification utilisateur connecté
    $user = $this->getUser();
    if (!$user instanceof User) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Utilisateur non connecté'
        ], 401);
    }

    // ✅ Récupérer le style choisi par l'utilisateur
    $style = $data['style'] ?? 'cartoon';
    $allowedStyles = ['cartoon', 'pixel', 'anime', 'watercolor', '3d', 'sketch'];
    if (!in_array($style, $allowedStyles)) {
        $style = 'cartoon';
    }

    $projectDir = $this->getParameter('kernel.project_dir');

    // ✅ Photo source de l'utilisateur
    $sourcePhoto = $user->getAvatarPath();
    if (!$sourcePhoto) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Aucune photo de profil trouvée'
        ], 400);
    }

    $sourcePhotoPath = $projectDir . '/public/' . $sourcePhoto;
    if (!file_exists($sourcePhotoPath)) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Photo source introuvable',
            'path' => $sourcePhotoPath
        ], 404);
    }

    // Script Python - utiliser un chemin relatif au projet
    $scriptPath = $projectDir . '/face_ai_project/avatar_ai/generate_avatar.py';
    if (!file_exists($scriptPath)) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Script Python introuvable',
            'path' => $scriptPath
        ], 500);
    }

    // Dossier avatars
    $avatarDir = $projectDir . '/public/uploads/avatars/';
    if (!is_dir($avatarDir)) {
        mkdir($avatarDir, 0777, true);
    }

    // ✅ Nom du fichier avec style inclus
    $fileName = 'avatar_user_' . $user->getId() . '_' . $style . '.png';
    $filePath = $avatarDir . $fileName;

    // ✅ Commande avec photo source ET style
    // Test if Python is available first
    $pythonTest = 'python --version 2>&1';
    $testOutput = [];
    $testResult = 0;
    exec($pythonTest, $testOutput, $testResult);
    
    if ($testResult !== 0) {
        // Try python3 as fallback
        $pythonTest = 'python3 --version 2>&1';
        $testOutput = [];
        $testResult = 0;
        exec($pythonTest, $testOutput, $testResult);
        
        if ($testResult !== 0) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Python non disponible sur le serveur',
                'debug' => [
                    'python_test_output' => implode("\n", $testOutput),
                    'python_test_result' => $testResult,
                    'suggestion' => 'Installez Python ou vérifiez le PATH système'
                ]
            ], 500);
        } else {
            $pythonExe = 'python3';
        }
    } else {
        $pythonExe = 'python';
    }
    
    $command = $pythonExe . ' "' . $scriptPath . '" '
             . '"' . $sourcePhotoPath . '" '   // argument 1 : photo source
             . '"' . $filePath . '" '           // argument 2 : chemin output
             . '"' . $style . '" '              // argument 3 : style
             . '2>&1';

    $output     = [];
    $resultCode = 0;
    
    // 🔥 ADD DEBUGGING INFO BEFORE EXECUTION
    if (!file_exists($sourcePhotoPath)) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Photo source n\'existe pas',
            'source_path' => $sourcePhotoPath,
            'debug' => 'Le fichier source spécifié n\'existe pas sur le serveur'
        ], 404);
    }
    
    if (!is_readable($sourcePhotoPath)) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Photo source non lisible',
            'source_path' => $sourcePhotoPath,
            'debug' => 'Le fichier source existe mais n\'est pas lisible'
        ], 403);
    }

    exec($command, $output, $resultCode);

    // Erreur Python
    if ($resultCode !== 0) {
        return new JsonResponse([
            'success'      => false,
            'message'      => 'Erreur génération avatar Python',
            'debug'        => [
                'output' => implode("\n", $output),
                'command' => $command,
                'result_code' => $resultCode,
                'source_exists' => file_exists($sourcePhotoPath),
                'source_path' => $sourcePhotoPath,
                'output_path' => $filePath,
                'style' => $style,
                'script_exists' => file_exists($scriptPath),
                'script_path' => $scriptPath,
                'avatar_dir_exists' => is_dir($avatarDir),
                'avatar_dir_writable' => is_writable($avatarDir)
            ]
        ], 500);
    }
    // Vérifier image créée
    if (!file_exists($filePath)) {
        // 🔥 FALLBACK: Create a simple colored avatar if Python fails
        $fallbackSuccess = $this->createFallbackAvatar($sourcePhotoPath, $filePath, $style);
        
        if (!$fallbackSuccess) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Image avatar non générée et fallback échoué',
                'debug' => [
                    'python_output' => implode("\n", $output),
                    'python_command' => $command,
                    'fallback_attempted' => true
                ]
            ], 500);
        }
    }

    // Sauvegarde base de données
    $relativePath = 'uploads/avatars/' . $fileName;
    $user->setAvatarPath($relativePath);
    $em->flush();

    return new JsonResponse([
        'success'    => true,
        'avatarPath' => $relativePath,
        'avatarUrl'  => '/uploads/avatars/' . $fileName  // ✅ URL pour mise à jour immédiate dans le DOM
    ]);
    
    } catch (\Exception $e) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Erreur interne du serveur',
            'debug' => [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString()
            ]
        ], 500);
    }
}

/**
 * Create a simple fallback avatar if Python processing fails
 */
private function createFallbackAvatar(string $sourcePhotoPath, string $outputPath, string $style): bool
{
    try {
        // Simple PHP GD fallback - just copy and resize the original image
        if (!extension_loaded('gd')) {
            return false;
        }
        
        $sourceInfo = getimagesize($sourcePhotoPath);
        if (!$sourceInfo) {
            return false;
        }
        
        // Create image from source
        switch ($sourceInfo[2]) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourcePhotoPath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourcePhotoPath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourcePhotoPath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Create 600x600 avatar
        $avatar = imagecreatetruecolor(600, 600);
        
        // Apply simple style-based color filter
        switch ($style) {
            case 'cartoon':
                // Bright and colorful
                imagefilter($avatar, IMG_FILTER_BRIGHTNESS, 20);
                imagefilter($avatar, IMG_FILTER_CONTRAST, -10);
                break;
            case 'anime':
                // High contrast and saturation
                imagefilter($avatar, IMG_FILTER_CONTRAST, 15);
                break;
            case 'sketch':
                // Convert to grayscale
                imagefilter($avatar, IMG_FILTER_GRAYSCALE);
                break;
            default:
                // Default processing
                break;
        }
        
        // Resize and copy
        imagecopyresampled($avatar, $sourceImage, 0, 0, 0, 0, 600, 600, imagesx($sourceImage), imagesy($sourceImage));
        
        // Save as PNG
        $success = imagepng($avatar, $outputPath);
        
        // Cleanup
        imagedestroy($sourceImage);
        imagedestroy($avatar);
        
        return $success;
        
    } catch (\Exception $e) {
        return false;
    }
}

}
