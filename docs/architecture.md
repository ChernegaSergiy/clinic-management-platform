# Специфікація архітектури MedCore

Цей документ фіксує основні архітектурні принципи, шари та обов'язки платформи MedCore. 

---

## 1. Три шари системи

### 1.1 Kernel (Ядро)

Ядро — це механізм, який **не знає** жодної предметної області (Appointment, Patient, MedicalRecord тощо). Воно вміє:
- підняти DI-контейнер;
- знайти й завантажити бандли;
- дати бандлу можливість зареєструвати routes, DI-сервіси, права, політики, слухачів подій;
- прийняти HTTP-запит, знайти маршрут, викликати контролер;
- обробити виняток і повернути відповідь.

Ядро не містить маршрутів конкретних сторінок, реєстрацій конкретних репозиторіїв та доменних логік.

Namespace: `App\Core\*` (контракти й абстракції) та `App\Infrastructure\*` (файлова система, збірка контейнера, з'єднання).

### 1.2 Bundle (Модуль)

Бандл — самодостатня одиниця домену: `Appointment`, `Patient`, `MedicalRecord`, `Billing` і т.д. Кожен бандл:
- сам реєструє свої маршрути (`registerRoutes`);
- сам реєструє свої DI-сервіси (`registerServices`);
- сам реєструє свої права та політики (`registerPermissions`, `registerPolicies`);
- сам реєструє своїх слухачів подій;
- сам володіє своїми шаблонами та перекладами.

Бандл спілкується з іншими бандлами виключно через **публічний інтерфейс репозиторію** (внесений у DI за інтерфейсом) або через **доменні події**.

### 1.3 Host Application

Host Application не є окремим привілейованим шаром. "Сайт" (публічні сторінки `/`, `/about`) та "інсталятор" (`/install`) — це такі самі бандли (`SiteModule`, `InstallModule`), просто без складних прав чи політик. Директорія `src/Controller/*` з хардкодженими маршрутами не існує.

---

## 2. Контракт бандла (Bundle Contract)

Кожен модуль повинен реалізувати `App\Core\Module\ModuleInterface` (або наслідувати `BaseModule`):

```php
interface ModuleInterface
{
    public function getName(): string;
    public function getVersion(): string;

    /**
     * Реєстрація сервісів бандла в контейнер. 
     * Викликається ДО compile(), на етапі складання контейнера.
     */
    public function registerServices(ContainerBuilder $container): void;
    public function bootstrap(): void;
    public function registerRoutes(Router $router): void;
    public function registerPermissions(PermissionRegistry $registry): void;
    public function registerPolicies(PolicyRegistry $registry): void;
    public function registerEventListeners(EventDispatcherInterface $dispatcher): void;
}
```

### Структура директорії бандла
```
src/Module/<Name>/
├── module.yaml                  # маніфест: name, version, config
├── <Name>Module.php             # реалізує ModuleInterface
├── <Name>Controller.php         # один або декілька контролерів
├── <Name>Policy.php             # якщо є доменні правила доступу
├── Repository/
│   ├── <Name>RepositoryInterface.php
│   └── <Name>Repository.php
├── Service/                     # доменні сервіси, якщо потрібні
├── templates/
└── translations/
```

### Реєстрація DI-сервісів

Сервіси модуля реєструються лише у його власному методі `registerServices()`. Реєстрація здійснюється **за інтерфейсом** (якщо він існує), щоб інші модулі могли залежати від абстракції.

```php
class PatientModule extends BaseModule
{
    public function registerServices(ContainerBuilder $container): void
    {
        $container->register(PatientRepositoryInterface::class, PatientRepository::class)
            ->setArguments([new Reference('pdo'), new Reference(\App\Core\Service\AuditLogger::class)])
            ->setPublic(true);

        $container->register(PatientController::class)
            ->setArguments([new Reference(PatientRepositoryInterface::class)])
            ->setPublic(true);
    }
}
```

---

## 3. Життєвий цикл запиту

1. `public/index.php` стартує сесію, вантажить `.env`.
2. `ContainerFactory::createContainer()`:
   - реєструє ядрові сервіси з `config/services.php`;
   - знаходить усі бандли через `ModuleDiscovery`;
   - для кожного бандла викликає `registerServices($container)`;
   - виконує `$container->compile()`.
3. З контейнера витягуються: `Router`, `PermissionRegistry`, `PolicyRegistry`, `ModuleManager`, `EventDispatcher`.
4. `ModuleManager::bootstrapAll()` — кожен бандл виконує свій `bootstrap()`.
5. `ModuleManager::registerPermissions() / registerPolicies() / registerEventListeners() / registerRoutes()` — у цьому фіксованому порядку.
6. Встановлюються реєстри у глобальний `Gate`.
7. `Router` обробляє запит `$router->dispatch($request)`.
8. Обробка винятків та відправка `Response`.

---

## 4. Заборонені патерни (NEVER)

- **NEVER** реєструвати маршрут напряму в `public/index.php` (тільки `$router->dispatch()`).
- **NEVER** дописувати `$container->register(...)` для сервісу конкретного домену в `config/services.php`. Тільки через `<Name>Module::registerServices()`.
- **NEVER** створювати клас контролера поза `src/Module/<Name>/`.
- **NEVER** тайпхінтити конкретний клас репозиторію іншого бандла (завжди використовувати Interface).
- **NEVER** одному бандлу читати чи писати файли в директорії іншого бандла.
- **NEVER** одному бандлу викликати метод контролера чи репозиторію іншого бандла напряму для сповіщення про зміну стану — тільки через `App\Event\*`.
- **NEVER** ігнорувати реєстрацію `Permissions` і `Policies` для модулів, які керують медичними/чутливими даними.
