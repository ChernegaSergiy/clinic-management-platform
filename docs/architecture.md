# MedCore Architecture Specification

This document defines the core architectural principles, layers, and responsibilities of the MedCore platform.

---

## 1. Three System Layers

### 1.1 Kernel

The Kernel is an engine that **does not know** about any specific business domain (Appointment, Patient, MedicalRecord, etc.). It knows how to:
- boot the DI container via Symfony Kernel;
- discover and load bundles registered in `config/bundles.php`;
- allow a bundle to register routes via attributes, DI services, permissions, policies, and event listeners via `CompilerPass` and `Extension`;
- accept an HTTP request, find the route, and call the controller;
- handle exceptions and return a response.

The Kernel does not contain routes for specific pages, specific repository registrations, or domain logic.

Namespace: `App\Core\*` (contracts and abstractions) and `App\Infrastructure\*` (file system, connections).

### 1.2 Bundle

A Bundle is a self-contained domain unit: `Appointment`, `Patient`, `MedicalRecord`, `Billing`, etc. Each bundle:
- registers its own routes (via `#[Route]` attributes);
- registers its own DI services (via `Extension`);
- registers its own permissions and policies (via `CompilerPass`);
- registers its own event listeners;
- owns its own templates and translations.

A bundle communicates with other bundles exclusively through a **public repository interface** (injected into DI by interface) or via **domain events**.

### 1.3 Host Application

The Host Application is not a separate privileged layer. The "Site" (public pages `/`, `/about`) is a bundle just like the rest (`SiteBundle`), simply without complex permissions or policies. The `src/Controller/*` directory with hardcoded routes does not exist.

---

## 2. Bundle Contract

Each bundle is a standard Symfony Bundle and extends `Symfony\Component\HttpKernel\Bundle\Bundle`:

### Bundle Directory Structure
```
src/Bundles/<Name>Bundle/
├── <Name>Bundle.php             # Main bundle class
├── Controller/
│   └── <Name>Controller.php     # Controllers with #[Route] attributes
├── DependencyInjection/
│   ├── <Name>Extension.php      # DI service registration
│   └── Compiler/
│       └── <Name>PermissionsPass.php # Permissions and policies registration
├── Repository/
│   └── <Name>Repository.php
├── <Name>Policy.php             # Domain access rules
├── templates/                   # Twig templates
└── translations/                # Translations
```

### DI Services and Permissions Registration

Bundle services are registered via `DependencyInjection/<Name>Extension.php`. 
Access rights and policies are added to the registry via `CompilerPass`.

```php
class PatientPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['patient.read', 'View patients']);
        }
    }
}
```

---

## 3. Request Lifecycle

1. `public/index.php` starts the session and initializes `App\Kernel` (via Symfony Runtime).
2. `App\Kernel`:
   - loads global configurations from `config/services.php` and `config/routes.yaml`;
   - discovers all bundles defined in `config/bundles.php`;
   - loads the `Extension` for each bundle and executes registered `CompilerPass` instances (e.g., registering rights in `PermissionRegistry`);
   - compiles the DI container.
3. Symfony `HttpKernel` accepts the `Request`.
4. Registries are set into the global `Gate` (via event or injection).
5. The `Router` finds the appropriate controller for the route.
6. The controller processes the request and returns a `Response`.
7. `HttpKernel` sends the response to the user.

---

## 4. Forbidden Patterns (NEVER)

- **NEVER** register a route directly in `public/index.php`.
- **NEVER** add a domain-specific service registration to the global `config/services.php`. Only via `<Name>Extension::load()`.
- **NEVER** create a controller class outside of `src/Bundles/<Name>Bundle/`.
- **NEVER** type-hint a specific repository class from another bundle (always use the Interface).
- **NEVER** allow one bundle to read or write files in another bundle's directory.
- **NEVER** allow one bundle to directly call a controller or repository method of another bundle to notify it about a state change — only via `App\Event\*`.
- **NEVER** ignore the registration of `Permissions` and `Policies` for modules that manage medical/sensitive data.
