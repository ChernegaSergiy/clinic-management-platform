<?php

namespace App\Tests\Auth;

use App\Auth\AuthStep;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthStepTest extends TestCase
{
    private function createSession(array $values = [], array $hasKeys = []) : SessionInterface
    {
        $session = $this->createMock(SessionInterface::class);

        foreach ($values as $key => $value) {
            $session->method('get')->with($key)->willReturn($value);
        }

        foreach ($hasKeys as $key) {
            $session->method('has')->with($key)->willReturn(true);
        }

        $session->method('get')->willReturnCallback(function ($key, $default = null) use ($values) {
            return $values[$key] ?? $default;
        });

        $session->method('has')->willReturnCallback(function ($key) use ($hasKeys) {
            return in_array($key, $hasKeys, true);
        });

        return $session;
    }

    public function testCurrentReturnsAuthenticatedWhenUserIsSet() : void
    {
        $user = $this->createMock(UserInterface::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $this->assertSame(AuthStep::AUTHENTICATED, AuthStep::current($tokenStorage));
    }

    public function testCurrentReturnsMfaSetupWhenMfaRequiredIsTrue() : void
    {
        $session = $this->createSession(['mfa_required' => true]);

        $this->assertSame(AuthStep::MFA_SETUP, AuthStep::current(null, $session));
    }

    public function testCurrentReturnsMfaVerifyWhenMfaPendingUserIdIsSet() : void
    {
        $session = $this->createSession([], ['mfa_pending_user_id']);

        $this->assertSame(AuthStep::MFA_VERIFY, AuthStep::current(null, $session));
    }

    public function testCurrentReturnsCredentialsByDefault() : void
    {
        $session = $this->createSession();

        $this->assertSame(AuthStep::CREDENTIALS, AuthStep::current(null, $session));
    }

    public function testIsAuthorizedOnlyTrueForAuthenticated() : void
    {
        $this->assertTrue(AuthStep::AUTHENTICATED->isAuthorized());
        $this->assertFalse(AuthStep::CREDENTIALS->isAuthorized());
        $this->assertFalse(AuthStep::MFA_SETUP->isAuthorized());
        $this->assertFalse(AuthStep::MFA_VERIFY->isAuthorized());
    }

    public function testRequiresMfaSetupOnlyTrueForMfaSetup() : void
    {
        $this->assertTrue(AuthStep::MFA_SETUP->requiresMfaSetup());
        $this->assertFalse(AuthStep::CREDENTIALS->requiresMfaSetup());
        $this->assertFalse(AuthStep::MFA_VERIFY->requiresMfaSetup());
        $this->assertFalse(AuthStep::AUTHENTICATED->requiresMfaSetup());
    }

    public function testRequiresMfaVerifyOnlyTrueForMfaVerify() : void
    {
        $this->assertTrue(AuthStep::MFA_VERIFY->requiresMfaVerify());
        $this->assertFalse(AuthStep::CREDENTIALS->requiresMfaVerify());
        $this->assertFalse(AuthStep::MFA_SETUP->requiresMfaVerify());
        $this->assertFalse(AuthStep::AUTHENTICATED->requiresMfaVerify());
    }
}
