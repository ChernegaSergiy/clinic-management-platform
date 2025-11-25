<?php

namespace App\Module\User;

use App\Core\View;
use App\Module\User\Repository\UserRepository;
use App\Module\Admin\Repository\AuthConfigRepository;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class OAuthController
{
    private AuthConfigRepository $authConfigRepository;
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->authConfigRepository = new AuthConfigRepository();
        $this->userRepository = new UserRepository();
    }

    public function redirect(string $provider): void
    {
        $providerConfig = $this->authConfigRepository->findByProvider($provider);

        if (!$providerConfig || !$providerConfig['is_active']) {
            // Or handle this error more gracefully
            die("Provider not supported or inactive.");
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
            die("Provider not supported or inactive.");
        }

        $providerObj = $this->getProvider($provider, $providerConfig);

        if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            die('Invalid state.');
        }

        try {
            $token = $providerObj->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);

            $ownerDetails = $providerObj->getResourceOwner($token);
            
            // Check if user is already logged in to link account
            if (isset($_SESSION['user']['id'])) {
                $userId = $_SESSION['user']['id'];
                // TODO: Add a method to UserRepository to update provider details
                // For now, we'll do it here directly for simplicity
                $stmt = \App\Database::getInstance()->prepare(
                    "UPDATE users SET provider = :provider, provider_id = :provider_id WHERE id = :id"
                );
                $stmt->execute([
                    ':provider' => $provider,
                    ':provider_id' => $ownerDetails->getId(),
                    ':id' => $userId,
                ]);

                // Redirect to a profile/settings page with a success message
                $_SESSION['success_message'] = 'Your ' . ucfirst($provider) . ' account has been successfully linked.';
                header('Location: /user/profile'); // Assuming a /user/profile route exists
                exit();
            }

            // User is not logged in, try to find or link by email
            $user = $this->userRepository->findOrLinkFromOAuth($provider, $ownerDetails);

            if ($user) {
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
            } else {
                // No user found, redirect back to login with an error
                $_SESSION['errors'] = ['login' => 'No account is associated with this ' . ucfirst($provider) . ' account. Please register first.'];
                header('Location: /login');
                exit();
            }

        } catch (IdentityProviderException $e) {
            // Failed to get the access token or user details.
            // You should handle this more gracefully.
            die('Something went wrong: ' . $e->getMessage());
        }
    }

    private function getProvider(string $provider, array $config)
    {
        switch ($provider) {
            case 'google':
                return new Google([
                    'clientId'     => $config['client_id'],
                    'clientSecret' => $config['client_secret'],
                    'redirectUri'  => 'http://' . $_SERVER['HTTP_HOST'] . '/oauth/callback/google',
                ]);
            case 'facebook':
                return new Facebook([
                    'clientId'          => $config['client_id'],
                    'clientSecret'      => $config['client_secret'],
                    'redirectUri'       => 'http://' . $_SERVER['HTTP_HOST'] . '/oauth/callback/facebook',
                    'graphApiVersion'   => 'v2.10',
                ]);
            // Add other providers here
            default:
                throw new \Exception("Provider not supported: $provider");
        }
    }

    public static function getSupportedProviders(): array
    {
        return [
            'google',
            'facebook',
        ];
    }
}
