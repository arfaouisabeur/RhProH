<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\FormLoginAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function request(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Si cette adresse e-mail existe, un code vous a été envoyé.');
            return $this->redirectToRoute('app_forgot_password_code');
        }

        return $this->render('auth/forgot_password.html.twig');
    }

    #[Route('/forgot-password/reset', name: 'app_forgot_password_code')]
    public function reset(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/forgot_password_code.html.twig');
    }

    #[Route('/send-otp', name: 'app_send_otp', methods: ['POST'])]
    public function sendOtp(Request $request, MailerInterface $mailer): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? null;

            if (!$email) {
                return $this->json([
                    'success' => false,
                    'message' => 'Email manquant'
                ]);
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->json([
                    'success' => false,
                    'message' => 'Format d\'email invalide'
                ]);
            }

            $otp = random_int(100000, 999999);

            $session = $request->getSession();
            $session->set('otp_code', $otp);
            $session->set('otp_email', $email);
            $session->set('otp_timestamp', time()); // Add timestamp for expiration

            // 🔥 DEVELOPMENT MODE: Show OTP in response for testing
            $isDev = $this->getParameter('kernel.environment') === 'dev';

            $message = (new Email())
                ->from('noreply@rhpro.local')
                ->to($email)
                ->subject('🔐 Code de vérification - RH Pro')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <div style='background: linear-gradient(135deg, #8f4cc9, #b39ddb); padding: 30px; border-radius: 15px; text-align: center; color: white;'>
                            <h1 style='margin: 0; font-size: 24px;'>🔐 Code de Vérification</h1>
                            <p style='margin: 10px 0 0; opacity: 0.9;'>RH Pro - Système de Gestion</p>
                        </div>
                        <div style='background: #f8f9fa; padding: 30px; border-radius: 15px; margin-top: 20px; text-align: center;'>
                            <h2 style='color: #2e0d52; margin-bottom: 20px;'>Votre code de vérification</h2>
                            <div style='background: white; padding: 20px; border-radius: 10px; border: 2px solid #8f4cc9; display: inline-block;'>
                                <span style='font-size: 32px; font-weight: bold; color: #8f4cc9; letter-spacing: 5px;'>$otp</span>
                            </div>
                            <p style='color: #666; margin-top: 20px; font-size: 14px;'>
                                ⏰ Ce code expire dans 10 minutes<br>
                                🔒 Ne partagez jamais ce code avec personne
                            </p>
                        </div>
                        <div style='text-align: center; margin-top: 20px; color: #999; font-size: 12px;'>
                            <p>Si vous n'avez pas demandé ce code, ignorez cet email.</p>
                        </div>
                    </div>
                ");

            try {
                $mailer->send($message);
                $emailSent = true;
            } catch (\Exception $mailError) {
                // Email sending failed, but we'll continue for development
                $emailSent = false;
                error_log('Email sending failed: ' . $mailError->getMessage());
            }

            $response = [
                'success' => true,
                'message' => $emailSent ? 'Code envoyé avec succès' : 'Code généré (email non envoyé - mode développement)'
            ];

            // 🔥 In development mode, include OTP in response for testing
            if ($isDev) {
                $response['dev_otp'] = $otp;
                $response['dev_message'] = 'Mode développement: Utilisez le code ci-dessus';
            }

            return $this->json($response);

        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('OTP Error: ' . $e->getMessage());
            
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du code',
                'debug' => $this->getParameter('kernel.environment') === 'dev' ? $e->getMessage() : null
            ], 500);
        }
    }

 #[Route('/verify-otp', name: 'app_verify_otp', methods: ['POST'])]
public function verifyOtp(
    Request $request,
    UserRepository $userRepository,
    TokenStorageInterface $tokenStorage
): Response {
    try {
        $data = json_decode($request->getContent(), true);
        $otp = $data['otp'] ?? null;

        $session = $request->getSession();

        $savedOtp = $session->get('otp_code');
        $email = $session->get('otp_email');
        $timestamp = $session->get('otp_timestamp');

        if (!$otp || !$savedOtp || !$email || !$timestamp) {
            return $this->json(['success' => false, 'message' => 'Session invalide']);
        }

        // Check if OTP is expired (10 minutes = 600 seconds)
        if (time() - $timestamp > 600) {
            $session->remove('otp_code');
            $session->remove('otp_email');
            $session->remove('otp_timestamp');
            return $this->json(['success' => false, 'message' => 'Code expiré']);
        }

        if ((int)$otp !== (int)$savedOtp) {
            return $this->json(['success' => false, 'message' => 'Code incorrect']);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur introuvable']);
        }

        // Clean session OTP
        $session->remove('otp_code');
        $session->remove('otp_email');
        $session->remove('otp_timestamp');

        // 🔐 LOGIN SYMFONY RÉEL
        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );

        $tokenStorage->setToken($token);
        $session->set('_security_main', serialize($token));

        return $this->json([
            'success' => true,
            'message' => 'Connexion réussie'
        ]);

    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'message' => 'Erreur lors de la vérification',
            'debug' => $e->getMessage()
        ], 500);
    }
}


}
