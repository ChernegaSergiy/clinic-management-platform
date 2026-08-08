<?php

namespace App\Tests\Core\Auth;

use App\Core\Auth\AuthStep;
use PHPUnit\Framework\TestCase;

class AuthStepTest extends TestCase
{
    protected function setUp() : void
    {
        unset($_SESSION['user'], $_SESSION['mfa_required'], $_SESSION['mfa_pending_user_id']);
    }

    protected function tearDown() : void
    {
        unset($_SESSION['user'], $_SESSION['mfa_required'], $_SESSION['mfa_pending_user_id']);
    }

    public function testCurrentReturnsAuthenticatedWhenUserIsSet() : void
    {
        $_SESSION['user'] = ['id' => 1];

        $this->assertSame(AuthStep::AUTHENTICATED, AuthStep::current());
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
