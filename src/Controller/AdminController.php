<?php

namespace App\Controller;

use App\Core\View;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Repository\DictionaryRepository;
use App\Repository\AuthConfigRepository;

class AdminController
{
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;
    private DictionaryRepository $dictionaryRepository;
    private AuthConfigRepository $authConfigRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->roleRepository = new RoleRepository();
        $this->dictionaryRepository = new DictionaryRepository();
        $this->authConfigRepository = new AuthConfigRepository();
    }

    public function users(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) { // Assuming role_id 1 is admin
            header('Location: /login');
            exit();
        }
        $users = $this->userRepository->findAll();
        $roles = $this->roleRepository->findAll();
        $roleMap = [];
        foreach ($roles as $role) {
            $roleMap[$role['id']] = $role['name'];
        }

        foreach ($users as &$user) {
            $user['role_name'] = $roleMap[$user['role_id']] ?? 'Невідома';
        }
        unset($user); // Break the reference with the last element

        View::render('admin/users.html.twig', ['users' => $users]);
    }

    public function createUser(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) { // Assuming role_id 1 is admin
            header('Location: /login');
            exit();
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

        View::render('admin/new_user.html.twig', [
            'roles' => $roleOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    public function storeUser(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) { // Assuming role_id 1 is admin
            header('Location: /login');
            exit();
        }

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
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) { // Assuming role_id 1 is admin
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userRepository->findById($id);

        if (!$user) {
            http_response_code(404);
            echo "Користувача не знайдено";
            return;
        }

        $role = $this->roleRepository->findById($user['role_id']);
        $user['role_name'] = $role['name'] ?? 'Невідома';

        View::render('admin/show_user.html.twig', ['user' => $user]);
    }

    public function editUser(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) { // Assuming role_id 1 is admin
            header('Location: /login');
            exit();
        }

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

        View::render('admin/edit_user.html.twig', [
            'user' => $user,
            'roles' => $roleOptions,
            'old' => $old,
            'errors' => $errors,
        ]);
    }

    public function updateUser(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) { // Assuming role_id 1 is admin
            header('Location: /login');
            exit();
        }

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
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) { // Assuming role_id 1 is admin
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
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
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }
        $roles = $this->roleRepository->findAll();
        View::render('admin/roles.html.twig', ['roles' => $roles]);
    }

    public function createRole(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }
        View::render('admin/new_role.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeRole(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

        $validator = new \App\Core\Validator();
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
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            http_response_code(404);
            echo "Роль не знайдено";
            return;
        }

        View::render('admin/edit_role.html.twig', [
            'role' => $role,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function updateRole(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $role = $this->roleRepository->findById($id);

        if (!$role) {
            http_response_code(404);
            echo "Роль не знайдено";
            return;
        }

        $validator = new \App\Core\Validator();
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
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
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
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }
        $dictionaries = $this->dictionaryRepository->findAll();
        View::render('admin/dictionaries/index.html.twig', ['dictionaries' => $dictionaries]);
    }

    public function showDictionary(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }
        $id = (int)($_GET['id'] ?? 0);
        $dictionary = $this->dictionaryRepository->findById($id);

        if (!$dictionary) {
            http_response_code(404);
            echo "Словник не знайдено";
            return;
        }

        $values = $this->dictionaryRepository->findValuesByDictionaryId($id);
        View::render('admin/dictionaries/show.html.twig', [
            'dictionary' => $dictionary,
            'values' => $values,
        ]);
    }

    public function createDictionary(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }
        View::render('admin/dictionaries/new.html.twig', [
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeDictionary(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'name' => ['required', 'unique:dictionaries,name'],
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
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $dictionary = $this->dictionaryRepository->findById($id);

        if (!$dictionary) {
            http_response_code(404);
            echo "Словник не знайдено";
            return;
        }

        View::render('admin/dictionaries/edit.html.twig', [
            'dictionary' => $dictionary,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function updateDictionary(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $dictionary = $this->dictionaryRepository->findById($id);

        if (!$dictionary) {
            http_response_code(404);
            echo "Словник не знайдено";
            return;
        }

        $validator = new \App\Core\Validator(\App\Database::getInstance());
        $validator->validate($_POST, [
            'name' => ['required', 'unique:dictionaries,name,' . $id],
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
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $this->dictionaryRepository->delete($id);
        $_SESSION['success_message'] = "Словник успішно видалено.";
        header('Location: /admin/dictionaries');
        exit();
    }

    // --- Dictionary Value Management ---
    public function createDictionaryValue(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }
        $dictionaryId = (int)($_GET['dictionary_id'] ?? 0);
        View::render('admin/dictionaries/values/new.html.twig', [
            'dictionary_id' => $dictionaryId,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function storeDictionaryValue(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

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
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $value = $this->dictionaryRepository->findValueById($id);

        if (!$value) {
            http_response_code(404);
            echo "Значення словника не знайдено";
            return;
        }

        View::render('admin/dictionaries/values/edit.html.twig', [
            'value' => $value,
            'old' => $_SESSION['old'] ?? [],
            'errors' => $_SESSION['errors'] ?? [],
        ]);
        unset($_SESSION['old'], $_SESSION['errors']);
    }

    public function updateDictionaryValue(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

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
        if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] !== 1) {
            header('Location: /login');
            exit();
        }

        $id = (int)($_GET['id'] ?? 0);
        $value = $this->dictionaryRepository->findValueById($id);
        $dictionaryId = $value['dictionary_id'];

        $this->dictionaryRepository->deleteValue($id);
        $_SESSION['success_message'] = "Значення словника успішно видалено.";
        header('Location: /admin/dictionaries/show?id=' . $dictionaryId);
        exit();
    }
}
