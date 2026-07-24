<?php

namespace App\Module\User;

use App\Core\Auth\PermissionRegistry;
use App\Core\Auth\PolicyRegistry;
use App\Core\Http\Router;
use App\Core\Module\BaseModule;
use App\Module\User\AuthController;
use App\Module\User\OAuthController;
use App\Module\User\UserController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UserModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/login', [AuthController::class, 'showLoginForm']);
        $router->add('POST', '/login', [AuthController::class, 'login']);
        $router->add('GET', '/logout', [AuthController::class, 'logout']);

        $router->add('GET', '/user/profile', [UserController::class, 'profile']);
        $router->add('POST', '/user/profile/unlink-provider/{provider}', [UserController::class, 'unlinkProvider']);
        $router->add('POST', '/user/clear-messages', [UserController::class, 'clearMessages']);

        if ($this->getConfig('features.profile_photo', true)) {
            $router->add('POST', '/user/profile/upload-photo', [UserController::class, 'uploadPhoto']);
        }

        if ($this->getConfig('features.oauth', true)) {
            $router->add('GET', '/oauth/redirect/{provider}', [AuthController::class, 'redirectToProvider']);
            $router->add('GET', '/oauth/callback/{provider}', [OAuthController::class, 'callback']);
        }

        $router->add('GET', '/user/mfa/required', [MfaController::class, 'showMfaRequiredChoice']);
        $router->add('GET', '/user/mfa/setup/{type}', [MfaController::class, 'showMfaSetup']);
        $router->add('POST', '/user/mfa/setup/{type}', [MfaController::class, 'verifyMfaSetup']);
        $router->add('GET', '/user/mfa/required/{type}', [MfaController::class, 'showMfaRequired']);
        $router->add('POST', '/user/mfa/required/{type}', [MfaController::class, 'verifyMfaRequired']);
        $router->add('POST', '/user/mfa/disable', [MfaController::class, 'disableMfa']);
        $router->add('GET', '/user/mfa/verify', [MfaController::class, 'showMfaVerify']);
        $router->add('POST', '/user/mfa/verify', [MfaController::class, 'verifyMfa']);
        $router->add('GET', '/user/mfa/clear-backup-codes', [MfaController::class, 'clearNewBackupCodes']);
        $router->add('POST', '/user/mfa/regenerate-backup-codes', [MfaController::class, 'regenerateBackupCodes']);
    }

    public function registerServices(ContainerBuilder $container): void
    {
        $container->register(\App\Module\User\Repository\UserRepository::class)->setPublic(true);
        $container->register(\App\Module\User\Repository\RoleRepository::class)->setPublic(true);
        $container->register(\App\Module\User\Repository\UserOAuthIdentityRepository::class)->setPublic(true);

        $container->register(\App\Module\User\MfaService::class)
            ->setArguments([
                new Reference('pdo'),
                new Reference(\App\Core\Service\QrCodeGenerator::class),
            ])->setPublic(true);

        $container->register(\App\Module\User\AuthController::class)
            ->setArguments([
                new Reference(\App\Module\User\Repository\UserRepository::class),
                new Reference(\App\Module\Admin\Repository\AuthConfigRepository::class),
                new Reference(\App\Module\User\Repository\RoleRepository::class),
                new Reference(\App\Module\User\MfaService::class),
                new Reference(\App\Core\Repository\SettingsRepository::class),
                new Reference(\App\Module\User\OAuthController::class),
            ])->setPublic(true);

        $container->register(\App\Module\User\MfaController::class)
            ->setArguments([
                new Reference(\App\Module\User\MfaService::class),
                new Reference(\App\Module\User\Repository\UserRepository::class),
                new Reference(\App\Core\Repository\SettingsRepository::class),
                new Reference(\App\Module\User\Repository\RoleRepository::class),
            ])->setPublic(true);

        $container->register(\App\Module\User\OAuthController::class)
            ->setArguments([
                new Reference(\App\Module\Admin\Repository\AuthConfigRepository::class),
                new Reference(\App\Module\User\Repository\UserRepository::class),
                new Reference(\App\Module\User\Repository\UserOAuthIdentityRepository::class),
            ])->setPublic(true);
            
        $container->register(\App\Module\User\UserController::class)
            ->setArguments([
                new Reference(\App\Module\User\Repository\UserRepository::class),
                new Reference(\App\Module\Admin\Repository\AuthConfigRepository::class),
                new Reference(\App\Module\User\Repository\UserOAuthIdentityRepository::class),
                new Reference(\App\Module\Hrm\Repository\HrmRepository::class),
                new Reference(\App\Core\Repository\SettingsRepository::class),
            ])->setPublic(true);
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
    }
}
