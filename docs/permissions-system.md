# Access Rights and Policies System

## Overview

The new modular access rights system allows modules to independently register their rights and access verification policies.

## Architecture

### PermissionRegistry

Class for registering and managing access rights.

```php
$registry = new PermissionRegistry();
$registry->add('hrm.read', 'View employees');
$registry->addRoleMapping('hr_manager', ['hrm.read', 'hrm.write']);
```

### PolicyRegistry

Class for registering access verification policies for resources.

```php
$registry = new PolicyRegistry();
$registry->register('hrm', HrmPolicy::class);
```

### Policy

Abstract base class for all policies.

```php
class HrmPolicy extends Policy
{
    public function view(mixed $resource): bool
    {
        // Access verification logic
        return $this->isAdmin();
    }

    public function create(): bool { }
    public function update(mixed $resource): bool { }
    public function delete(mixed $resource): bool { }
}
```

## Usage in Modules

### Permission Registration

Each module can register its permissions through the `registerPermissions` method:

```php
class HrmModule extends BaseModule
{
    public function registerPermissions(PermissionRegistry $registry): void
    {
        $registry->add('hrm.read', 'View employees');
        $registry->add('hrm.write', 'Edit employees');
        $registry->add('hrm.manage', 'Manage employees');

        $registry->addRoleMapping('admin', ['hrm.read', 'hrm.write', 'hrm.manage']);
        $registry->addRoleMapping('hr_manager', ['hrm.read', 'hrm.write', 'hrm.manage']);
        $registry->addRoleMapping('medical_manager', ['hrm.read']);
    }
}
```

### Policy Registration

You can also register policies for access verification:

```php
public function registerPolicies(PolicyRegistry $registry): void
{
    $registry->register('hrm', HrmPolicy::class);
}
```

## Permission Checking in Controllers

### Modern Way (via Gate)

```php
public function index(): void
{
    Gate::authorize('hrm.read');
    // ... method code
}
```

### Checking Without Throwing Error

```php
if (Gate::allows('hrm.write')) {
    // User has access
}
```

### With Context

```php
Gate::authorize('patients.read', ['patient_id' => $id]);
```

## Initialization in index.php

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

## Advantages of the New System

1. **Modularity** - each module manages its own permissions independently
2. **Flexibility** - easy to add new permissions and policies
3. **Type safety** - policies are typed and validated
4. **No hardcode** - permissions are not hardcoded in the core
5. **Easy testing** - policies are easy to mock for tests
6. **Readability** - access logic is clear and structured

## Migration from Old System

The old Gate system is still supported through `GateNew.php` with fallback to legacy permissions. Modules can be gradually migrated to the new system.