<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\Candidat;
use App\Entity\Employe;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private $clientRegistry;
    private $entityManager;
    private $router;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client, $request) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();
                $googleId = $googleUser->getId();

                // 1. Chercher si l'utilisateur existe déjà
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['googleId' => $googleId]);
                if ($existingUser) {
                    return $existingUser;
                }

                // 2. S'il existe par e-mail, on le lie
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
                
                if (!$user) {
                    // 3. Nouveau compte!
                    $user = new User();
                    $user->setEmail($email);
                    $user->setNom($googleUser->getLastName() ?? 'Inconnu');
                    $user->setPrenom($googleUser->getFirstName() ?? 'Inconnu');
                    $user->setAvatarPath($googleUser->getAvatar());
                    $user->setGoogleId($googleId);
                    
                    // Mot de passe par défaut très complexe (non utilisable)
                    $user->setMotDePasse(bin2hex(random_bytes(32))); 

                    // Déterminer le rôle désiré via la session
                    $type = $request->getSession()->get('oauth_registration_type', 'candidat_et_employe');

                    if ($type === 'employe') {
                        $user->setRole(User::ROLE_EMPLOYE);
                    } elseif ($type === 'candidat') {
                        $user->setRole(User::ROLE_CANDIDAT);
                    } else {
                        // "candidat et employé" default as requested
                        $user->setRole(User::ROLE_CANDIDAT); 
                    }

                    $this->entityManager->persist($user);
                    $this->entityManager->flush(); // Flush user first to get ID

                    if ($type !== 'employe' && $type !== 'candidat') {
                        $employe = new Employe();
                        $employe->setMatricule('EMP-GOOGLE-'.rand(1000, 9999));
                        $employe->setUser($user);
                        $this->entityManager->persist($employe);
                    }

                    // On crée toujours le profil Candidat de base
                    if (!$user->getCandidat()) {
                        $candidat = new Candidat();
                        $candidat->setUser($user);
                        $this->entityManager->persist($candidat);
                    }

                    $this->entityManager->flush();
                } else {
                    // Update the user's google ID if not set
                    $user->setGoogleId($googleId);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Nettoyer la session
        $request->getSession()->remove('oauth_registration_type');
        
        return new RedirectResponse($this->router->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        $request->getSession()->getFlashBag()->add('error', $message);
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
