# Система прав доступу та полісів

## Огляд

Нова модульна система прав доступу дозволяє модулям самостійно реєструвати свої права та політики перевірки доступу.

## Архітектура

### PermissionRegistry

Клас для реєстрації та управління правами доступу.

```php
$registry = new PermissionRegistry();
$registry->add('hrm.read', 'Перегляд співробітників');
$registry->addRoleMapping('hr_manager', ['hrm.read', 'hrm.write']);
```

### PolicyRegistry

Клас для реєстрації полісів перевірки доступу до ресурсів.

```php
$registry = new PolicyRegistry();
$registry->register('hrm', HrmPolicy::class);
```

### Policy

Абстрактний базовий клас для всіх полісів.

```php
class HrmPolicy extends Policy
{
    public function view(mixed $resource): bool
    {
        // Логіка перевірки доступу
        return $this->isAdmin();
    }

    public function create(): bool { }
    public function update(mixed $resource): bool { }
    public function delete(mixed $resource): bool { }
}
```

## Використання в модулях

### Реєстрація прав

Кожен модуль може реєструвати свої права через метод `registerPermissions`:

```php
class HrmModule extends BaseModule
{
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('hrm.read', 'Перегляд співробітників');
        $registry->add('hrm.write', 'Редагування співробітників');
        $registry->add('hrm.manage', 'Керування співробітниками');

        $registry->addRoleMapping('admin', ['hrm.read', 'hrm.write', 'hrm.manage']);
        $registry->addRoleMapping('hr_manager', ['hrm.read', 'hrm.write', 'hrm.manage']);
        $registry->addRoleMapping('medical_manager', ['hrm.read']);
    }
}
```

### Реєстрація полісів

Можна також зареєструвати політики для перевірки доступу:

```php
public function registerPolicies(PolicyRegistry $registry): void
{
    $registry->register('hrm', HrmPolicy::class);
}
```

## Перевірка прав у контролерах

### Сучасний спосіб (через Gate)

```php
public function index(): void
{
    Gate::authorize('hrm.read');
    // ... код методу
}
```

### Перевірка без викидання помилки

```php
if (Gate::allows('hrm.write')) {
    // Користувач має доступ
}
```

### З контекстом

```php
Gate::authorize('patients.read', ['patient_id' => $id]);
```

## Ініціалізація в index.php

```php
$permissionRegistry = new PermissionRegistry();
$policyRegistry = new PolicyRegistry();

$moduleManager = new ModuleManager();
$moduleLoader = new ModuleLoader($moduleManager);
$moduleLoader->loadAll();

$moduleManager->bootstrapAll();
$moduleManager->registerPermissions($permissionRegistry);
$moduleManager->registerPolicies($policyRegistry);
$moduleManager->registerRoutes($router);

Gate::setPermissionRegistry($permissionRegistry);
Gate::setPolicyRegistry($policyRegistry);
```

## Переваги нової системи

1. **Модульність** - кожен модуль самостійно керує своїми правами
2. **Гнучкість** - легко додавати нові права та політики
3. **Type safety** - поліси типізовані та перевіряються
4. **No hardcode** - права не захардкоджені в ядрі
5. **Легкість тестування** - поліси легко мокати для тестів
6. **Читабельність** - логіка доступу зрозуміла та структурована

## Міграція з старої системи

Стара система Gate все ще підтримується через `GateNew.php` з fallback на legacy права. Можна повільно мігрувати модулі на нову систему.