<?php

namespace App\Module\User;

use App\Core\BaseModule;
use App\Core\Router;
use App\Core\PermissionRegistry;
use App\Core\PolicyRegistry;
use App\Module\User\AuthController;
use App\Module\User\UserController;
use App\Module\User\OAuthController;

class UserModule extends BaseModule
{
    public function registerRoutes(Router $router): void
    {
        $router->add('GET', '/login', [AuthController::class, 'showLoginForm']);
        $router->add('POST', '/login', [AuthController::class, 'login']);
        $router->add('GET', '/logout', [AuthController::class, 'logout']);

        $router->add('GET', '/user/profile', [UserController::class, 'profile']);
        $router->add('POST', '/user/profile/unlink-provider/{provider}', [UserController::class, 'unlinkProvider']);

        if ($this->getConfig('features.profile_photo', true)) {
            $router->add('POST', '/user/profile/upload-photo', [UserController::class, 'uploadPhoto']);
        }

        if ($this->getConfig('features.oauth', true)) {
            $router->add('GET', '/oauth/redirect/{provider}', [AuthController::class, 'redirectToProvider']);
            $router->add('GET', '/oauth/callback/{provider}', [OAuthController::class, 'callback']);
        }
    }

    public function registerPermissions(PermissionRegistry $registry): void
    {
    }

    public function registerPolicies(PolicyRegistry $registry): void
    {
    }
}