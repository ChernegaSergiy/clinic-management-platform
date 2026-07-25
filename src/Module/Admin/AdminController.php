<?php

namespace App\Module\Admin;

use App\Database\Database;
use App\Core\Auth\Gate;
use App\Core\Repository\SettingsRepository;
use Symfony\Component\Routing\Attribute\Route;
use App\Module\Admin\Repository\AuthConfigRepository;
use App\Module\Admin\Repository\BackupPolicyRepository;
use App\Module\Admin\Repository\DictionaryRepository;
use App\Module\Billing\Repository\ServiceRepository;
use App\Module\Kpi\Repository\KpiRepository;
use App\Module\User\Repository\RoleRepositoryInterface;
use App\Module\User\Repository\UserRepositoryInterface;

class AdminController extends \App\Core\Controller\AbstractController
{
    private UserRepositoryInterface $userRepository;
    private RoleRepositoryInterface $roleRepository;
    private DictionaryRepository $dictionaryRepository;
    private AuthConfigRepository $authConfigRepository;
    private BackupPolicyRepository $backupPolicyRepository;
    private KpiRepository $kpiRepository;
    private ServiceRepository $serviceRepository;
    private SettingsRepository $settingsRepository;
    private \App\Module\User\MfaService $mfaService;
    private \App\Core\Validation\Validator $validator;

    public function __construct(
        UserRepositoryInterface $userRepository,
        RoleRepositoryInterface $roleRepository,
        DictionaryRepository $dictionaryRepository,
        AuthConfigRepository $authConfigRepository,
        BackupPolicyRepository $backupPolicyRepository,
        KpiRepository $kpiRepository,
        ServiceRepository $serviceRepository,
        SettingsRepository $settingsRepository,
        \App\Module\User\MfaService $mfaService,
        \App\Core\Validation\Validator $validator
    ) {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->dictionaryRepository = $dictionaryRepository;
        $this->authConfigRepository = $authConfigRepository;
        $this->backupPolicyRepository = $backupPolicyRepository;
        $this->kpiRepository = $kpiRepository;
        $this->serviceRepository = $serviceRepository;
        $this->settingsRepository = $settingsRepository;
        $this->mfaService = $mfaService;
        $this->validator = $validator;
    }

    #[Route('/admin/settings', name: 'admin_settings', methods: ['GET'])]
    public function showSettings(): void
    {
        $this->authorizeAdmin();

        $roles = $this->roleRepository->findAll();

        $settings = [
            'clinic_name' => $this->settingsRepository->get('clinic_name', ''),
            'mfa_policy' => $this->settingsRepository->getMfaPolicy(),
            'mfa_force_roles' => $this->settingsRepository->getMfaForceRoles(),
            'system_locale' => $this->settingsRepository->get('system_locale', 'uk'),
        ];

        $availableLocales = $this->view->getTranslationService()->getAvailableLocales();

        $this->render('@modules/Admin/templates/settings.html.twig', [
            'settings' => $settings,
            'roles' => $roles,
            'availableLocales' => $availableLocales,
        ]);
    }

    #[Route('/admin/settings', name: 'admin_settings_post', methods: ['POST'])]
    public function updateSettings(): void
    {
        $this->authorizeAdmin();

        $clinicName = $_POST['clinic_name'] ?? '';
        $mfaPolicy = $_POST['mfa_policy'] ?? 'optional';
        $mfaForceRolesRaw = $_POST['mfa_force_roles'] ?? '';
        $locale = $_POST['locale'] ?? 'uk';

        $mfaForceRoles = [];
        if (!empty($mfaForceRolesRaw)) {
            $mfaForceRoles = array_map('intval', explode(',', $mfaForceRolesRaw));
        }

        $this->settingsRepository->set('clinic_name', $clinicName);
        $this->settingsRepository->setMfaPolicy($mfaPolicy);
        $this->settingsRepository->setMfaForceRoles($mfaForceRoles);
        $this->settingsRepository->set('system_locale', $locale);

        // Clear View cache to force re-initialization with new locale
        $this->view->clearCache();

        $_SESSION['success_message'] = 'Налаштування збережено.';
        header('Location: /admin/settings');
        exit();
    }

    #[Route('/admin/users', name: 'admin_users', methods: ['GET'])]
    public function users(): void
    {
        $this->authorizeAdmin();
        $searchTerm = $_GET['search'] ?? '';
        $users = $this->userRepository->findAll($searchTerm);
        $roles = $this->roleRepository->findAll();
        $roleMap = [];
        foreach ($roles as $role) {
            $roleMap[$role['id']] = $role['name'];
        }

        foreach ($users as &$user) {
            $user['role_name'] = $roleMap[$user['role_id']] ?? 'Невідома';
        }
        unset($user); // Break the reference with the last element

        $this->render('@modules/Admin/templates/users.html.twig', [
            'users' => $users,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/admin/users/new', name: 'admin_users_new', methods: ['GET'])]
    public function createUser(): void
    {
        $this->authorizeAdmin();

        $roleOptions = $this->buildRoleOptionsByPriority();

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        $this->render('@modules/Admin/templates/new_user.html.twig', [
            'roles' => $roleOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    #[Route('/admin/users/new', name: 'admin_users_new_post', methods: ['POST'])]
    public function storeUser(): void
    {
        $this->authorizeAdmin();

        $validator = $this->validator;
        $validator->validate($_POST, [
            'first_name' => ['required'],
            'last_name' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
            'role_id' => ['required', 'numeric'],
        ]);

        if ($this->userRepository->findByEmail($_POST['email'])) {
            $validator->addError('email', 'Цей email вже використовується.');
        }

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/users/new');
            exit();
        }

        $this->userRepository->save($_POST);
        $_SESSION['success_message'] = "Користувача успішно створено.";
        header('Location: /admin/users');
        exit();
    }

    #[Route('/admin/users/show', name: 'admin_users_show', methods: ['GET'])]
    public function showUser(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo "Користувача не знайдено";
            return;
        }

        $role = $this->roleRepository->findById($user['role_id']);
        $user['role_name'] = $role['name'] ?? 'Невідома';

        $this->render('@modules/Admin/templates/show_user.html.twig', ['user' => $user]);
    }

    #[Route('/admin/users/edit', name: 'admin_users_edit', methods: ['GET'])]
    public function editUser(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo "Користувача не знайдено";
            return;
        }

        $roleOptions = $this->buildRoleOptionsByPriority();

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        $this->render('@modules/Admin/templates/edit_user.html.twig', [
            'user' => $user,
            'roles' => $roleOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    private function buildRoleOptionsByPriority(): array
    {
        $roles = $this->roleRepository->findAll();
        $priority = [
            'admin' => 1,
            'medical_manager' => 2,
            'doctor' => 3,
            'registrar' => 4,
            'nurse' => 5,
            'lab_technician' => 6,
            'billing' => 7,
            'inventory_manager' => 8,
        ];

        usort($roles, function ($a, $b) use ($priority) {
            $pa = $priority[$a['name']] ?? 999;
            $pb = $priority[$b['name']] ?? 999;
            if ($pa === $pb) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $pa <=> $pb;
        });

        $roleOptions = [];
        foreach ($roles as $role) {
            $roleOptions[$role['id']] = $role['name'];
        }

        return $roleOptions;
    }

    #[Route('/admin/users/edit', name: 'admin_users_edit_post', methods: ['POST'])]
    public function updateUser(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo "Користувача не знайдено";
            return;
        }

        // TODO: Add validation
        $validator = $this->validator;
        $rules = [
            'first_name' => ['required'],
            'last_name' => ['required'],
            'email' => ['required', 'email'],
            'role_id' => ['required', 'numeric'],
        ];

        if (!empty($_POST['password'])) {
            $rules['password'] = ['min:6'];
        }

        $validator->validate($_POST, $rules);

        if ($this->userRepository->findByEmailExcludingId($_POST['email'], $id)) {
            $validator->addError('email', 'Цей email вже використовується іншим користувачем.');
        }

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/users/edit?id=' . $id);
            exit();
        }

        $this->userRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Дані користувача успішно оновлено.";
        header('Location: /admin/users/show?id=' . $id);
        exit();
    }

    #[Route('/admin/users/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function deleteUser(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo "Користувача не знайдено";
            return;
        }

        // Prevent admin from deleting themselves
        if ($user['id'] === $_SESSION['user']['id']) {
            $_SESSION['error_message'] = "Ви не можете видалити свій власний обліковий запис.";
            header('Location: /admin/users');
            exit();
        }

        $this->userRepository->delete($id);
        $_SESSION['success_message'] = "Користувача успішно видалено.";
        header('Location: /admin/users');
        exit();
    }

    #[Route('/admin/users/disable-mfa', name: 'admin_users_disable_mfa', methods: ['POST'])]
    public function disableUserMfa(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo "Користувача не знайдено";
            return;
        }

        $this->mfaService->disableMfaForUser($id);

        $_SESSION['success_message'] = "2FA для користувача " . $user['email'] . " успішно вимкнено.";
        header('Location: /admin/users');
        exit();
    }

    // --- Role Management ---
    #[Route('/admin/roles', name: 'admin_roles', methods: ['GET'])]
    public function listRoles(): void
    {
        $this->authorizeAdmin();
        $roles = $this->roleRepository->findAll();
        $this->render('@modules/Admin/templates/roles.html.twig', ['roles' => $roles]);
    }

    #[Route('/admin/roles/new', name: 'admin_roles_new', methods: ['GET'])]
    public function createRole(): void
    {
        $this->authorizeAdmin();
        $this->render('@modules/Admin/templates/new_role.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/roles/new', name: 'admin_roles_new_post', methods: ['POST'])]
    public function storeRole(): void
    {
        $this->authorizeAdmin();

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:roles'], // Need to implement unique validation in Validator
            'description' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/roles/new');
            exit();
        }

        $this->roleRepository->save($_POST);
        $_SESSION['success_message'] = "Роль успішно створено.";
        header('Location: /admin/roles');
        exit();
    }

    #[Route('/admin/roles/edit', name: 'admin_roles_edit', methods: ['GET'])]
    public function editRole(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            http_response_code(404);
            echo "Роль не знайдено";
            return;
        }

        $this->render('@modules/Admin/templates/edit_role.html.twig', [
            'role' => $role,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/roles/edit', name: 'admin_roles_edit_post', methods: ['POST'])]
    public function updateRole(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            http_response_code(404);
            echo "Роль не знайдено";
            return;
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:roles,name,' . $id], // Need to implement unique validation in Validator
            'description' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/roles/edit?id=' . $id);
            exit();
        }

        $this->roleRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Роль успішно оновлено.";
        header('Location: /admin/roles');
        exit();
    }

    #[Route('/admin/roles/delete', name: 'admin_roles_delete', methods: ['POST'])]
    public function deleteRole(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            http_response_code(404);
            echo "Роль не знайдено";
            return;
        }

        $this->roleRepository->delete($id);
        $_SESSION['success_message'] = "Роль успішно видалено.";
        header('Location: /admin/roles');
        exit();
    }

    // --- Dictionary Management ---
    #[Route('/admin/dictionaries', name: 'admin_dictionaries', methods: ['GET'])]
    public function listDictionaries(): void
    {
        $this->authorizeAdmin();
        $dictionaries = $this->dictionaryRepository->findAll();
        $this->render('@modules/Admin/templates/dictionaries/index.html.twig', ['dictionaries' => $dictionaries]);
    }

    #[Route('/admin/dictionaries/show', name: 'admin_dictionaries_show', methods: ['GET'])]
    public function showDictionary(): void
    {
        $this->authorizeAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $dictionary = $this->dictionaryRepository->findById($id);

        if (!$dictionary) {
            http_response_code(404);
            echo "Словник не знайдено";
            return;
        }

        $values = $this->dictionaryRepository->findValuesByDictionaryId($id);
        $this->render('@modules/Admin/templates/dictionaries/show.html.twig', [
            'dictionary' => $dictionary,
            'values' => $values,
        ]);
    }

    #[Route('/admin/dictionaries/new', name: 'admin_dictionaries_new', methods: ['GET'])]
    public function createDictionary(): void
    {
        $this->authorizeAdmin();
        $this->render('@modules/Admin/templates/dictionaries/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/dictionaries/new', name: 'admin_dictionaries_new_post', methods: ['POST'])]
    public function storeDictionary(): void
    {
        $this->authorizeAdmin();

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:dictionaries,name'], // Corrected unique validation
            'description' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/dictionaries/new');
            exit();
        }

        $this->dictionaryRepository->save($_POST);
        $_SESSION['success_message'] = "Словник успішно створено.";
        header('Location: /admin/dictionaries');
        exit();
    }

    #[Route('/admin/dictionaries/edit', name: 'admin_dictionaries_edit', methods: ['GET'])]
    public function editDictionary(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $dictionary = $this->dictionaryRepository->findById($id);

        if (!$dictionary) {
            http_response_code(404);
            echo "Словник не знайдено";
            return;
        }

        $this->render('@modules/Admin/templates/dictionaries/edit.html.twig', [
            'dictionary' => $dictionary,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/dictionaries/edit', name: 'admin_dictionaries_edit_post', methods: ['POST'])]
    public function updateDictionary(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $dictionary = $this->dictionaryRepository->findById($id);

        if (!$dictionary) {
            http_response_code(404);
            echo "Словник не знайдено";
            return;
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:dictionaries,name,' . $id], // Corrected unique validation
            'description' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/dictionaries/edit?id=' . $id);
            exit();
        }

        $this->dictionaryRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Словник успішно оновлено.";
        header('Location: /admin/dictionaries');
        exit();
    }

    #[Route('/admin/dictionaries/delete', name: 'admin_dictionaries_delete', methods: ['POST'])]
    public function deleteDictionary(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $this->dictionaryRepository->delete($id);
        $_SESSION['success_message'] = "Словник успішно видалено.";
        header('Location: /admin/dictionaries');
        exit();
    }

    // --- Dictionary Value Management ---
    #[Route('/admin/dictionaries/values/new', name: 'admin_dictionaries_values_new', methods: ['GET'])]
    public function createDictionaryValue(): void
    {
        $this->authorizeAdmin();
        $dictionaryId = (int)($_GET['dictionary_id'] ?? 0);
        $this->render('@modules/Admin/templates/dictionaries/values/new.html.twig', [
            'dictionary_id' => $dictionaryId,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/dictionaries/values/new', name: 'admin_dictionaries_values_new_post', methods: ['POST'])]
    public function storeDictionaryValue(): void
    {
        $this->authorizeAdmin();

        $dictionaryId = (int)($_POST['dictionary_id'] ?? 0);
        $validator = $this->validator;
        $validator->validate($_POST, [
            'dictionary_id' => ['required'],
            'value' => ['required', 'unique:dictionary_values,value,dictionary_id,' . $dictionaryId],
            'label' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/dictionaries/values/new?dictionary_id=' . $dictionaryId);
            exit();
        }

        $this->dictionaryRepository->saveValue($_POST);
        $_SESSION['success_message'] = "Значення словника успішно створено.";
        header('Location: /admin/dictionaries/show?id=' . $dictionaryId);
        exit();
    }

    #[Route('/admin/dictionaries/values/edit', name: 'admin_dictionaries_values_edit', methods: ['GET'])]
    public function editDictionaryValue(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $value = $this->dictionaryRepository->findValueById($id);

        if (!$value) {
            http_response_code(404);
            echo "Значення словника не знайдено";
            return;
        }

        $this->render('@modules/Admin/templates/dictionaries/values/edit.html.twig', [
            'value' => $value,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/dictionaries/values/edit', name: 'admin_dictionaries_values_edit_post', methods: ['POST'])]
    public function updateDictionaryValue(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $value = $this->dictionaryRepository->findValueById($id);
        $dictionaryId = $value['dictionary_id'];

        $validator = $this->validator;
        $validator->validate($_POST, [
            'dictionary_id' => ['required'],
            'value' => ['required', 'unique:dictionary_values,value,dictionary_id,' . $dictionaryId . ',id,' . $id],
            'label' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/dictionaries/values/edit?id=' . $id);
            exit();
        }

        $this->dictionaryRepository->updateValue($id, $_POST);
        $_SESSION['success_message'] = "Значення словника успішно оновлено.";
        header('Location: /admin/dictionaries/show?id=' . $dictionaryId);
        exit();
    }

    #[Route('/admin/dictionaries/values/delete', name: 'admin_dictionaries_values_delete', methods: ['POST'])]
    public function deleteDictionaryValue(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $value = $this->dictionaryRepository->findValueById($id);
        $dictionaryId = $value['dictionary_id'];

        $this->dictionaryRepository->deleteValue($id);
        $_SESSION['success_message'] = "Значення словника успішно видалено.";
        header('Location: /admin/dictionaries/show?id=' . $dictionaryId);
        exit();
    }

    // --- Auth Configuration Management ---
    #[Route('/admin/auth_configs', name: 'admin_auth_configs', methods: ['GET'])]
    public function listAuthConfigs(): void
    {
        $this->authorizeAdmin();
        $configs = $this->authConfigRepository->findAll();
        $this->render('@modules/Admin/templates/auth_configs/index.html.twig', ['configs' => $configs]);
    }

    #[Route('/admin/auth_configs/new', name: 'admin_auth_configs_new', methods: ['GET'])]
    public function createAuthConfig(): void
    {
        $this->authorizeAdmin();
        $supportedProviders = \App\Module\User\OAuthController::getSupportedProviders();

        $this->render('@modules/Admin/templates/auth_configs/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'supportedProviders' => $supportedProviders,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/auth_configs/new', name: 'admin_auth_configs_new_post', methods: ['POST'])]
    public function storeAuthConfig(): void
    {
        $this->authorizeAdmin();

        $validator = $this->validator;
        $validator->validate($_POST, [
            'provider' => ['required', 'unique:auth_configs,provider'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/auth_configs/new');
            exit();
        }

        $data = $_POST;
        $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        $this->authConfigRepository->save($data);
        $_SESSION['success_message'] = "Конфігурацію аутентифікації успішно створено.";
        header('Location: /admin/auth_configs');
        exit();
    }

    #[Route('/admin/auth_configs/edit', name: 'admin_auth_configs_edit', methods: ['GET'])]
    public function editAuthConfig(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $config = $this->authConfigRepository->findById($id);

        if (!$config) {
            http_response_code(404);
            echo "Конфігурацію аутентифікації не знайдено";
            return;
        }
        $supportedProviders = \App\Module\User\OAuthController::getSupportedProviders();

        $this->render('@modules/Admin/templates/auth_configs/edit.html.twig', [
            'config' => $config,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'supportedProviders' => $supportedProviders,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/auth_configs/edit', name: 'admin_auth_configs_edit_post', methods: ['POST'])]
    public function updateAuthConfig(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $config = $this->authConfigRepository->findById($id);

        if (!$config) {
            http_response_code(404);
            echo "Конфігурацію аутентифікації не знайдено";
            return;
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'provider' => ['required', 'unique:auth_configs,provider,' . $id],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/auth_configs/edit?id=' . $id);
            exit();
        }

        $data = $_POST;
        $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        $this->authConfigRepository->update($id, $data);
        $_SESSION['success_message'] = "Конфігурацію аутентифікації успішно оновлено.";
        header('Location: /admin/auth_configs');
        exit();
    }

    #[Route('/admin/auth_configs/delete', name: 'admin_auth_configs_delete', methods: ['POST'])]
    public function deleteAuthConfig(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $this->authConfigRepository->delete($id);
        $_SESSION['success_message'] = "Конфігурацію аутентифікації успішно видалено.";
        header('Location: /admin/auth_configs');
        exit();
    }

    #[Route('/admin/auth_configs/show', name: 'admin_auth_configs_show', methods: ['GET'])]
    public function showAuthConfig(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $config = $this->authConfigRepository->findById($id);

        if (!$config) {
            http_response_code(404);
            echo "Конфігурацію аутентифікації не знайдено";
            return;
        }

        $redirectUri = $_ENV['APP_BASE_URL'] . '/oauth/callback/' . $config['provider'];

        $this->render('@modules/Admin/templates/auth_configs/show.html.twig', [
            'config' => $config,
            'redirectUri' => $redirectUri,
        ]);
    }

    // --- Backup Policy Management ---
    public function listBackupPolicies(): void
    {
        $this->authorizeAdmin();
        $policies = $this->backupPolicyRepository->findAll();
        $this->render('@modules/Admin/templates/backup_policies/index.html.twig', ['policies' => $policies]);
    }

    public function createBackupPolicy(): void
    {
        $this->authorizeAdmin();
        $this->render('@modules/Admin/templates/backup_policies/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeBackupPolicy(): void
    {
        $this->authorizeAdmin();

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:backup_policies,name'],
            'frequency' => ['required'],
            'retention_days' => ['required', 'numeric', 'min:1'],
            'status' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/backup_policies/new');
            exit();
        }

        $this->backupPolicyRepository->save($_POST);
        $_SESSION['success_message'] = "Політику резервного копіювання успішно створено.";
        header('Location: /admin/backup_policies');
        exit();
    }

    public function editBackupPolicy(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $policy = $this->backupPolicyRepository->findById($id);

        if (!$policy) {
            http_response_code(404);
            echo "Політику резервного копіювання не знайдено";
            return;
        }

        $this->render('@modules/Admin/templates/backup_policies/edit.html.twig', [
            'policy' => $policy,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function updateBackupPolicy(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $policy = $this->backupPolicyRepository->findById($id);

        if (!$policy) {
            http_response_code(404);
            echo "Політику резервного копіювання не знайдено";
            return;
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:backup_policies,name,' . $id],
            'frequency' => ['required'],
            'retention_days' => ['required', 'numeric', 'min:1'],
            'status' => ['required'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/backup_policies/edit?id=' . $id);
            exit();
        }

        $this->backupPolicyRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Політику резервного копіювання успішно оновлено.";
        header('Location: /admin/backup_policies');
        exit();
    }

    public function deleteBackupPolicy(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $this->backupPolicyRepository->delete($id);
        $_SESSION['success_message'] = "Політику резервного копіювання успішно видалено.";
        header('Location: /admin/backup_policies');
        exit();
    }

    // --- KPI Definition Management ---
    public function listKpiDefinitions(): void
    {
        $this->authorizeAdmin();
        $definitions = $this->kpiRepository->findAllKpiDefinitions();
        $this->render('@modules/Kpi/templates/definitions/index.html.twig', ['definitions' => $definitions]);
    }

    public function createKpiDefinition(): void
    {
        $this->authorizeAdmin();
        $this->render('@modules/Kpi/templates/definitions/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeKpiDefinition(): void
    {
        $this->authorizeAdmin();

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:kpi_definitions,name'],
            'kpi_type' => ['required'],
        ]);

        // Normalize optional fields
        if (isset($_POST['target_value']) && $_POST['target_value'] === '') {
            unset($_POST['target_value']);
        }
        $_POST['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/kpi_definitions/new');
            exit();
        }

        $this->kpiRepository->saveKpiDefinition($_POST);
        $_SESSION['success_message'] = "Визначення KPI успішно створено.";
        header('Location: /admin/kpi_definitions');
        exit();
    }

    public function editKpiDefinition(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $definition = $this->kpiRepository->findKpiDefinitionById($id);

        if (!$definition) {
            http_response_code(404);
            echo "Визначення KPI не знайдено";
            return;
        }

        $this->render('@modules/Kpi/templates/definitions/edit.html.twig', [
            'definition' => $definition,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function updateKpiDefinition(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $definition = $this->kpiRepository->findKpiDefinitionById($id);

        if (!$definition) {
            http_response_code(404);
            echo "Визначення KPI не знайдено";
            return;
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:kpi_definitions,name,' . $id],
            'kpi_type' => ['required'],
        ]);

        // Normalize optional fields
        if (isset($_POST['target_value']) && $_POST['target_value'] === '') {
            unset($_POST['target_value']);
        }
        $_POST['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/kpi_definitions/edit?id=' . $id);
            exit();
        }

        $this->kpiRepository->updateKpiDefinition($id, $_POST);
        $_SESSION['success_message'] = "Визначення KPI успішно оновлено.";
        header('Location: /admin/kpi_definitions');
        exit();
    }

    public function deleteKpiDefinition(): void
    {
        $this->authorizeAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $this->kpiRepository->deleteKpiDefinition($id);
        $_SESSION['success_message'] = "Визначення KPI успішно видалено.";
        header('Location: /admin/kpi_definitions');
        exit();
    }

    // --- Service Management ---
    #[Route('/admin/services', name: 'admin_services', methods: ['GET'])]
    public function listServices(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_services'); // Specific permission for service management
        $services = $this->serviceRepository->findAll();
        $this->render('@modules/Admin/templates/services/index.html.twig', ['services' => $services]);
    }

    #[Route('/admin/services/new', name: 'admin_services_new', methods: ['GET'])]
    public function createService(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_services');
        $categories = $this->serviceRepository->findCategories();
        $categoryOptions = [];
        foreach ($categories as $category) {
            $categoryOptions[$category['id']] = $category['name'];
        }

        $this->render('@modules/Admin/templates/services/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'categories' => $categoryOptions,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/services/new', name: 'admin_services_new_post', methods: ['POST'])]
    public function storeService(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_services');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:services,name'],
            'price' => ['required', 'numeric'],
            'duration_minutes' => ['required', 'numeric', 'min:1'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/services/new');
            exit();
        }

        // Normalize is_active checkbox value
        $_POST['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        $this->serviceRepository->save($_POST);
        $_SESSION['success_message'] = "Послугу успішно створено.";
        header('Location: /admin/services');
        exit();
    }

    #[Route('/admin/services/edit', name: 'admin_services_edit', methods: ['GET'])]
    public function editService(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_services');

        $id = (int)($_GET['id'] ?? 0);
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            http_response_code(404);
            echo "Послугу не знайдено";
            return;
        }

        $categories = $this->serviceRepository->findCategories();
        $categoryOptions = [];
        foreach ($categories as $category) {
            $categoryOptions[$category['id']] = $category['name'];
        }

        $this->render('@modules/Admin/templates/services/edit.html.twig', [
            'service' => $service,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
            'categories' => $categoryOptions,
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/services/edit', name: 'admin_services_edit_post', methods: ['POST'])]
    public function updateService(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_services');

        $id = (int)($_GET['id'] ?? 0);
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            http_response_code(404);
            echo "Послугу не знайдено";
            return;
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:services,name,' . $id],
            'price' => ['required', 'numeric'],
            'duration_minutes' => ['required', 'numeric', 'min:1'],
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/services/edit?id=' . $id);
            exit();
        }

        // Normalize is_active checkbox value
        $_POST['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        $this->serviceRepository->update($id, $_POST);
        $_SESSION['success_message'] = "Послугу успішно оновлено.";
        header('Location: /admin/services');
        exit();
    }

    #[Route('/admin/services/delete', name: 'admin_services_delete', methods: ['POST'])]
    public function deleteService(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_services');

        $id = (int)($_POST['id'] ?? 0);
        $this->serviceRepository->delete($id);
        $_SESSION['success_message'] = "Послугу успішно видалено.";
        header('Location: /admin/services');
        exit();
    }

    // --- Service Category Management ---
    #[Route('/admin/service-categories', name: 'admin_service_categories', methods: ['GET'])]
    public function listServiceCategories(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_service_categories');
        $categories = $this->serviceRepository->findCategories();
        $this->render('@modules/Admin/templates/service_categories/index.html.twig', ['categories' => $categories]);
    }

    #[Route('/admin/service-categories/new', name: 'admin_service_categories_new', methods: ['GET'])]
    public function createServiceCategory(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_service_categories');
        $this->render('@modules/Admin/templates/service_categories/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/service-categories/new', name: 'admin_service_categories_new_post', methods: ['POST'])]
    public function storeServiceCategory(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_service_categories');

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:service_categories,name'],
            'description' => [], // Optional
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/service-categories/new');
            exit();
        }

        $this->serviceRepository->saveCategory($_POST);
        $_SESSION['success_message'] = "Категорію послуг успішно створено.";
        header('Location: /admin/service-categories');
        exit();
    }

    #[Route('/admin/service-categories/edit', name: 'admin_service_categories_edit', methods: ['GET'])]
    public function editServiceCategory(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_service_categories');

        $id = (int)($_GET['id'] ?? 0);
        $category = $this->serviceRepository->findCategoryById($id);

        if (!$category) {
            http_response_code(404);
            echo "Категорію послуг не знайдено";
            return;
        }

        $this->render('@modules/Admin/templates/service_categories/edit.html.twig', [
            'category' => $category,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    #[Route('/admin/service-categories/edit', name: 'admin_service_categories_edit_post', methods: ['POST'])]
    public function updateServiceCategory(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_service_categories');

        $id = (int)($_GET['id'] ?? 0);
        $category = $this->serviceRepository->findCategoryById($id);

        if (!$category) {
            http_response_code(404);
            echo "Категорію послуг не знайдено";
            return;
        }

        $validator = $this->validator;
        $validator->validate($_POST, [
            'name' => ['required', 'unique:service_categories,name,' . $id],
            'description' => [], // Optional
        ]);

        if ($validator->hasErrors()) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['old'] = $_POST;
            header('Location: /admin/service-categories/edit?id=' . $id);
            exit();
        }

        $this->serviceRepository->updateCategory($id, $_POST);
        $_SESSION['success_message'] = "Категорію послуг успішно оновлено.";
        header('Location: /admin/service-categories');
        exit();
    }

    #[Route('/admin/service-categories/delete', name: 'admin_service_categories_delete', methods: ['POST'])]
    public function deleteServiceCategory(): void
    {
        $this->authorizeAdmin();
        Gate::authorize('admin.manage_service_categories');

        $id = (int)($_POST['id'] ?? 0);
        $this->serviceRepository->deleteCategory($id);
        $_SESSION['success_message'] = "Категорію послуг успішно видалено.";
        header('Location: /admin/service-categories');
        exit();
    }

    private function authorizeAdmin(): void
    {
        $this->checkAuth();
        Gate::authorize('system.manage');
    }
}
