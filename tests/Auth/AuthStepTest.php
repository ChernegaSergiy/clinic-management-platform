<?php

namespace App\Tests\Auth;

use App\Auth\AuthStep;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthStepTest extends TestCase
{
    protected function setUp() : void
    {
        unset($_SESSION['mfa_required'], $_SESSION['mfa_pending_user_id']);
    }

    protected function tearDown() : void
    {
        unset($_SESSION['mfa_required'], $_SESSION['mfa_pending_user_id']);
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
        $_SESSION['mfa_required'] = true;

        $this->assertSame(AuthStep::MFA_SETUP, AuthStep::current());
    }

    public function testCurrentReturnsMfaVerifyWhenMfaPendingUserIdIsSet() : void
    {
        $_SESSION['mfa_pending_user_id'] = 5;

        $this->assertSame(AuthStep::MFA_VERIFY, AuthStep::current());
    }

    public function testCurrentReturnsCredentialsByDefault() : void
    {
        $this->assertSame(AuthStep::CREDENTIALS, AuthStep::current());
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
