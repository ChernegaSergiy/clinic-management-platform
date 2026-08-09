<?php

namespace App\Core\Auth;

use App\Bundles\UserBundle\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class SessionAuthenticator extends AbstractAuthenticator
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function supports(Request $request) : ?bool
    {
        // We only support requests if our custom session has a user_id
        if (PHP_SESSION_NONE === session_status()) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }

    public function authenticate(Request $request) : Passport
    {
        if (PHP_SESSION_NONE === session_status()) {
            session_start();
        }

        $userId = $_SESSION['user_id'];

        return new SelfValidatingPassport(new UserBadge((string)$userId, function ($userIdentifier) {
            $user = $this->userRepository->find((int)$userIdentifier);
            if (!$user) {
                throw new UserNotFoundException();
            }
            return $user;
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName) : ?Response
    {
        // On success, do nothing, just let the request continue to the controller
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception) : ?Response
    {
        // If authentication fails (e.g. user was deleted from DB but session exists)
        // the AuthGuard probably handles redirects, so we just return null here
        // to let the request fall through, or we can throw an exception.
        return null;
    }
}
