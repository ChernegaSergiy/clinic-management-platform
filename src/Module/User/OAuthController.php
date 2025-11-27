<?php

namespace App\Module\User;

use App\Module\User\Repository\UserRepository;
use App\Module\User\Repository\UserOAuthIdentityRepository;
use App\Module\Admin\Repository\AuthConfigRepository;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class OAuthController
{
    private AuthConfigRepository $authConfigRepository;
    private UserRepository $userRepository;
    private UserOAuthIdentityRepository $userOAuthIdentityRepository;

    public function __construct()
    {
        $this->authConfigRepository = new AuthConfigRepository();
        $this->userRepository = new UserRepository();
        $this->userOAuthIdentityRepository = new UserOAuthIdentityRepository();
    }

    public function redirect(string $provider): void
    {
        $providerConfig = $this->authConfigRepository->findByProvider($provider);

        if (!$providerConfig || !$providerConfig['is_active']) {
            // Or handle this error more gracefully
            die("Провайдер не підтримується або вимкнений.");
        }

        $providerObj = $this->getProvider($provider, $providerConfig);

        $authUrl = $providerObj->getAuthorizationUrl();
        $_SESSION['oauth2state'] = $providerObj->getState();

        header('Location: ' . $authUrl);
        exit();
    }

    public function callback(string $provider): void
    {
        $providerConfig = $this->authConfigRepository->findByProvider($provider);

        if (!$providerConfig || !$providerConfig['is_active']) {
            die("Провайдер не підтримується або вимкнений.");
        }

        $providerObj = $this->getProvider($provider, $providerConfig);
        if (empty($_GET['state']) || !isset($_SESSION['oauth2state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            die('Некоректний стан запиту.');
        }

        try {
            $token = $providerObj->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            $ownerDetails = $providerObj->getResourceOwner($token);
            $providerId = $ownerDetails->getId();
            $email = $ownerDetails->getEmail();

            // 1. Check if user is already logged in (linking an existing account)
            if (isset($_SESSION['user']['id'])) {
                $userId = $_SESSION['user']['id'];

                // Check if this provider is already linked to the current user
                $existingIdentity = $this->userOAuthIdentityRepository->findByUserIdAndProvider($userId, $provider);

                if ($existingIdentity) {
                    $_SESSION['info_message'] = sprintf('Ваш акаунт %s вже прив\'язано.', ucfirst($provider));
                } else {
                    // Check if this provider ID is already linked to ANOTHER user
                    $anotherUserIdentity = $this->userOAuthIdentityRepository->findByProviderAndProviderId($provider, $providerId);
                    if ($anotherUserIdentity && $anotherUserIdentity['user_id'] != $userId) {
                        $_SESSION['errors'] = ['oauth' => sprintf('Цей акаунт %s вже прив\'язано до іншого користувача.', ucfirst($provider))];
                        header('Location: /user/profile');
                        exit();
                    }

                    // Link the account
                    $this->userOAuthIdentityRepository->create($userId, $provider, $providerId);
                    $_SESSION['success_message'] = sprintf('Ваш акаунт %s успішно прив\'язано.', ucfirst($provider));
                }

                header('Location: /user/profile'); // Assuming a /user/profile route exists
                exit();
            }

            // 2. User is not logged in - try to find or create user based on OAuth identity

            // Try to find a user by the OAuth identity
            $oauthIdentity = $this->userOAuthIdentityRepository->findByProviderAndProviderId($provider, $providerId);

            if ($oauthIdentity) {
                $user = $this->userRepository->findById($oauthIdentity['user_id']);
                if ($user) {
                    // Log in the user
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'email' => $user['email'],
                        'role_id' => $user['role_id'],
                    ];
                    $redirect = $_SESSION['intended_url'] ?? '/dashboard';
                    unset($_SESSION['intended_url']);
                    header('Location: ' . $redirect);
                    exit();
                }
            }

            // If no user found by OAuth identity, try to find by email
            $userByEmail = $this->userRepository->findByEmail($email);
            if ($userByEmail) {
                // User exists with this email, link the OAuth identity
                $this->userOAuthIdentityRepository->create($userByEmail['id'], $provider, $providerId);
                // Log in the user
                $_SESSION['user'] = [
                    'id' => $userByEmail['id'],
                    'first_name' => $userByEmail['first_name'],
                    'last_name' => $userByEmail['last_name'],
                    'email' => $userByEmail['email'],
                    'role_id' => $userByEmail['role_id'],
                ];
                $redirect = $_SESSION['intended_url'] ?? '/dashboard';
                unset($_SESSION['intended_url']);
                header('Location: ' . $redirect);
                exit();
            }

            // No user found or linked, redirect to login with an error or to a registration page
            $_SESSION['errors'] = ['login' => sprintf('Жодного користувача, пов\'язаного з цим акаунтом %s, не знайдено. Спершу зареєструйтеся або увійдіть в існуючий акаунт і прив\'яжіть його.', ucfirst($provider))];
            header('Location: /login');
            exit();
        } catch (IdentityProviderException $e) {
            $_SESSION['errors'] = ['oauth' => 'Помилка автентифікації: ' . $e->getMessage()];
            header('Location: /login');
            exit();
        }
    }

    private function getProvider(string $provider, array $config)
    {
        switch ($provider) {
            case 'google':
                return new Google([
                    'clientId'     => $config['client_id'],
                    'clientSecret' => $config['client_secret'],
                    'redirectUri'  => $_ENV['APP_BASE_URL'] . '/oauth/callback/google',
                ]);
            case 'facebook':
                return new Facebook([
                    'clientId'          => $config['client_id'],
                    'clientSecret'      => $config['client_secret'],
                    'redirectUri'       => $_ENV['APP_BASE_URL'] . '/oauth/callback/facebook',
                    'graphApiVersion'   => 'v2.10',
                ]);
            case 'github':
                return new Github([
                    'clientId'     => $config['client_id'],
                    'clientSecret' => $config['client_secret'],
                    'redirectUri'  => $_ENV['APP_BASE_URL'] . '/oauth/callback/github',
                ]);
            default:
                throw new \Exception("Провайдер не підтримується: $provider");
        }
    }

    public static function getSupportedProviders(): array
    {
        return [
            'google',
            'facebook',
            'github',
        ];
    }
}
