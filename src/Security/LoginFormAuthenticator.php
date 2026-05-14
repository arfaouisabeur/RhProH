<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_email', '');
        $password = $request->request->get('_password', '');
        /*
        $csrfToken = $request->request->get('_csrf_token', '');

*/

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, function ($userIdentifier) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy([
                    'email' => $userIdentifier
                ]);

                if (!$user) {
                    throw new CustomUserMessageAuthenticationException(
                        'Aucun compte trouvé avec cet email.'
                    );
                }

                if ($user->getStatut() === 'inactif') {
                    throw new CustomUserMessageAuthenticationException(
                        'Votre compte est inactif. Contactez l\'administrateur.'
                    );
                }

                return $user;
            }),
            new CustomCredentials(
                function ($credentials, UserInterface $user) {
                    $dbPassword = $user->getPassword();
                    
                    // 1. Compatibilité avec la base Java (mot de passe en clair)
                    if ($dbPassword === $credentials) {
                        return true;
                    }
                    
                    // 2. Vérification standard via le Hasher de Symfony (pour les futurs mots de passe hachés)
                    return $this->passwordHasher->isPasswordValid($user, $credentials);
                },
                $password
            )
            /*,
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]*/
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}