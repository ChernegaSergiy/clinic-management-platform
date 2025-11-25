<?php

namespace App\Module\Admin;

use App\Core\View;
use App\Module\User\Repository\UserRepository;
use App\Module\User\Repository\RoleRepository;
use App\Module\Admin\Repository\DictionaryRepository;
use App\Module\Admin\Repository\AuthConfigRepository;
use App\Module\Admin\Repository\BackupPolicyRepository;
use App\Module\Admin\Repository\KpiRepository;
use App\Core\AuthGuard;

class AdminController
{
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private DictionaryRepository $dictionaryRepository;
    private AuthConfigRepository $authConfigRepository;
    private BackupPolicyRepository $backupPolicyRepository;
    private KpiRepository $kpiRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->roleRepository = new RoleRepository();
        $this->dictionaryRepository = new DictionaryRepository();
        $this->authConfigRepository = new AuthConfigRepository();
        $this->backupPolicyRepository = new BackupPolicyRepository();
        $this->kpiRepository = new KpiRepository();
    }

    public function users(): void
    {
        AuthGuard::isAdmin();
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

        View::render('@modules/Admin/templates/users.html.twig', [
            'users' => $users,
            'searchTerm' => $searchTerm,
        ]);
    }

    public function createUser(): void
    {
        AuthGuard::isAdmin();

        $roles = $this->roleRepository->findAll();
        $roleOptions = [];
        foreach ($roles as $role) {
            $roleOptions[$role['id']] = $role['name'];
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('@modules/Admin/templates/new_user.html.twig', [
            'roles' => $roleOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    public function storeUser(): void
    {
        AuthGuard::isAdmin();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function showUser(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo "Користувача не знайдено";
            return;
        }

        $role = $this->roleRepository->findById($user['role_id']);
        $user['role_name'] = $role['name'] ?? 'Невідома';

        View::render('@modules/Admin/templates/show_user.html.twig', ['user' => $user]);
    }

    public function editUser(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo "Користувача не знайдено";
            return;
        }

        $roles = $this->roleRepository->findAll();
        $roleOptions = [];
        foreach ($roles as $role) {
            $roleOptions[$role['id']] = $role['name'];
        }

        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        View::render('@modules/Admin/templates/edit_user.html.twig', [
            'user' => $user,
            'roles' => $roleOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    public function updateUser(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo "Користувача не знайдено";
            return;
        }

        // TODO: Add validation
        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function deleteUser(): void
    {
        AuthGuard::isAdmin();

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

    // --- Role Management ---
    public function listRoles(): void
    {
        AuthGuard::isAdmin();
        $roles = $this->roleRepository->findAll();
        View::render('@modules/Admin/templates/roles.html.twig', ['roles' => $roles]);
    }

    public function createRole(): void
    {
        AuthGuard::isAdmin();
        View::render('@modules/Admin/templates/new_role.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeRole(): void
    {
        AuthGuard::isAdmin();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function editRole(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            http_response_code(404);
            echo "Роль не знайдено";
            return;
        }

        View::render('@modules/Admin/templates/edit_role.html.twig', [
            'role' => $role,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function updateRole(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            http_response_code(404);
            echo "Роль не знайдено";
            return;
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function deleteRole(): void
    {
        AuthGuard::isAdmin();

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
    public function listDictionaries(): void
    {
        AuthGuard::isAdmin();
        $dictionaries = $this->dictionaryRepository->findAll();
        View::render('@modules/Admin/templates/dictionaries/index.html.twig', ['dictionaries' => $dictionaries]);
    }

    public function showDictionary(): void
    {
        AuthGuard::isAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $dictionary = $this->dictionaryRepository->findById($id);

        if (!$dictionary) {
            http_response_code(404);
            echo "Словник не знайдено";
            return;
        }

        $values = $this->dictionaryRepository->findValuesByDictionaryId($id);
        View::render('@modules/Admin/templates/dictionaries/show.html.twig', [
            'dictionary' => $dictionary,
            'values' => $values,
        ]);
    }

    public function createDictionary(): void
    {
        AuthGuard::isAdmin();
        View::render('@modules/Admin/templates/dictionaries/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeDictionary(): void
    {
        AuthGuard::isAdmin();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function editDictionary(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $dictionary = $this->dictionaryRepository->findById($id);

        if (!$dictionary) {
            http_response_code(404);
            echo "Словник не знайдено";
            return;
        }

        View::render('@modules/Admin/templates/dictionaries/edit.html.twig', [
            'dictionary' => $dictionary,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function updateDictionary(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $dictionary = $this->dictionaryRepository->findById($id);

        if (!$dictionary) {
            http_response_code(404);
            echo "Словник не знайдено";
            return;
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function deleteDictionary(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $this->dictionaryRepository->delete($id);
        $_SESSION['success_message'] = "Словник успішно видалено.";
        header('Location: /admin/dictionaries');
        exit();
    }

    // --- Dictionary Value Management ---
    public function createDictionaryValue(): void
    {
        AuthGuard::isAdmin();
        $dictionaryId = (int)($_GET['dictionary_id'] ?? 0);
        View::render('@modules/Admin/templates/dictionaries/values/new.html.twig', [
            'dictionary_id' => $dictionaryId,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeDictionaryValue(): void
    {
        AuthGuard::isAdmin();

        $dictionaryId = (int)($_POST['dictionary_id'] ?? 0);
        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function editDictionaryValue(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $value = $this->dictionaryRepository->findValueById($id);

        if (!$value) {
            http_response_code(404);
            echo "Значення словника не знайдено";
            return;
        }

        View::render('@modules/Admin/templates/dictionaries/values/edit.html.twig', [
            'value' => $value,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function updateDictionaryValue(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $value = $this->dictionaryRepository->findValueById($id);
        $dictionaryId = $value['dictionary_id'];

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function deleteDictionaryValue(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $value = $this->dictionaryRepository->findValueById($id);
        $dictionaryId = $value['dictionary_id'];

        $this->dictionaryRepository->deleteValue($id);
        $_SESSION['success_message'] = "Значення словника успішно видалено.";
        header('Location: /admin/dictionaries/show?id=' . $dictionaryId);
        exit();
    }

    // --- Auth Configuration Management ---
    public function listAuthConfigs(): void
    {
        AuthGuard::isAdmin();
        $configs = $this->authConfigRepository->findAll();
        View::render('@modules/Admin/templates/auth_configs/index.html.twig', ['configs' => $configs]);
    }

    public function createAuthConfig(): void
    {
        AuthGuard::isAdmin();
        View::render('@modules/Admin/templates/auth_configs/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeAuthConfig(): void
    {
        AuthGuard::isAdmin();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function editAuthConfig(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $config = $this->authConfigRepository->findById($id);

        if (!$config) {
            http_response_code(404);
            echo "Конфігурацію аутентифікації не знайдено";
            return;
        }

        View::render('@modules/Admin/templates/auth_configs/edit.html.twig', [
            'config' => $config,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function updateAuthConfig(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $config = $this->authConfigRepository->findById($id);

        if (!$config) {
            http_response_code(404);
            echo "Конфігурацію аутентифікації не знайдено";
            return;
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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

    public function deleteAuthConfig(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $this->authConfigRepository->delete($id);
        $_SESSION['success_message'] = "Конфігурацію аутентифікації успішно видалено.";
        header('Location: /admin/auth_configs');
        exit();
    }

    // --- Backup Policy Management ---
    public function listBackupPolicies(): void
    {
        AuthGuard::isAdmin();
        $policies = $this->backupPolicyRepository->findAll();
        View::render('@modules/Admin/templates/backup_policies/index.html.twig', ['policies' => $policies]);
    }

    public function createBackupPolicy(): void
    {
        AuthGuard::isAdmin();
        View::render('@modules/Admin/templates/backup_policies/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeBackupPolicy(): void
    {
        AuthGuard::isAdmin();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $policy = $this->backupPolicyRepository->findById($id);

        if (!$policy) {
            http_response_code(404);
            echo "Політику резервного копіювання не знайдено";
            return;
        }

        View::render('@modules/Admin/templates/backup_policies/edit.html.twig', [
            'policy' => $policy,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function updateBackupPolicy(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $policy = $this->backupPolicyRepository->findById($id);

        if (!$policy) {
            http_response_code(404);
            echo "Політику резервного копіювання не знайдено";
            return;
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
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
        AuthGuard::isAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $this->backupPolicyRepository->delete($id);
        $_SESSION['success_message'] = "Політику резервного копіювання успішно видалено.";
        header('Location: /admin/backup_policies');
        exit();
    }

    // --- KPI Definition Management ---
    public function listKpiDefinitions(): void
    {
        AuthGuard::isAdmin();
        $definitions = $this->kpiRepository->findAllKpiDefinitions();
        View::render('@modules/Admin/templates/kpi/definitions/index.html.twig', ['definitions' => $definitions]);
    }

    public function createKpiDefinition(): void
    {
        AuthGuard::isAdmin();
        View::render('@modules/Admin/templates/kpi/definitions/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeKpiDefinition(): void
    {
        AuthGuard::isAdmin();

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'name' => ['required', 'unique:kpi_definitions,name'],
            'kpi_type' => ['required'],
        ]);

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
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $definition = $this->kpiRepository->findKpiDefinitionById($id);

        if (!$definition) {
            http_response_code(404);
            echo "Визначення KPI не знайдено";
            return;
        }

        View::render('@modules/Admin/templates/kpi/definitions/edit.html.twig', [
            'definition' => $definition,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function updateKpiDefinition(): void
    {
        AuthGuard::isAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $definition = $this->kpiRepository->findKpiDefinitionById($id);

        if (!$definition) {
            http_response_code(404);
            echo "Визначення KPI не знайдено";
            return;
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'name' => ['required', 'unique:kpi_definitions,name,' . $id],
            'kpi_type' => ['required'],
        ]);

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
        AuthGuard::isAdmin();

        $id = (int)($_POST['id'] ?? 0);
        $this->kpiRepository->deleteKpiDefinition($id);
        $_SESSION['success_message'] = "Визначення KPI успішно видалено.";
        header('Location: /admin/kpi_definitions');
        exit();
    }
}
