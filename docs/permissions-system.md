# Access Rights and Policies System

## Overview

The new modular access rights system allows bundles to independently register their rights and access verification policies.

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

## Usage in Bundles

### Permission and Policy Registration

Each bundle registers its permissions and policies through a Symfony `CompilerPass` (e.g. in `DependencyInjection/Compiler/HrmPermissionsPass.php`):

```php
class HrmPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['hrm.read', 'View employees']);
            $registry->addMethodCall('add', ['hrm.write', 'Edit employees']);
            $registry->addMethodCall('add', ['hrm.manage', 'Manage employees']);

            $registry->addMethodCall('addRoleMapping', ['admin', ['hrm.read', 'hrm.write', 'hrm.manage']]);
            $registry->addMethodCall('addRoleMapping', ['hr_manager', ['hrm.read', 'hrm.write', 'hrm.manage']]);
            $registry->addMethodCall('addRoleMapping', ['medical_manager', ['hrm.read']]);
        }

        if ($container->hasDefinition(PolicyRegistry::class)) {
            $registry = $container->getDefinition(PolicyRegistry::class);
            $registry->addMethodCall('register', ['hrm', HrmPolicy::class]);
        }
    }
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

## Initialization in Request Lifecycle

In the new architecture, initialization is handled through the Symfony DI container and `App\Kernel`:

```php
// 1. Container compilation
// Symfony Kernel compiles the container and runs all registered CompilerPass classes.
// The `HrmPermissionsPass` is executed, injecting permissions into the `PermissionRegistry` definition.

// 2. Resolve core services from DI
$permissionRegistry = $container->get(PermissionRegistry::class);
$policyRegistry = $container->get(PolicyRegistry::class);

// 3. Configure Gate
// This happens globally via a listener or direct injection.
Gate::setPermissionRegistry($permissionRegistry);
Gate::setPolicyRegistry($policyRegistry);
```

## Advantages of the New System

1. **Modularity** — each module manages its own permissions independently
2. **Flexibility** — easy to add new permissions and policies
3. **Type safety** — policies are typed and validated
4. **No hardcode** — permissions are not hardcoded in the core
5. **Easy testing** — policies are easy to mock for tests
6. **Readability** — access logic is clear and structured

## Migration from Old System

The old Gate system is still supported through `GateNew.php` with fallback to legacy permissions. Modules can be gradually migrated to the new system.