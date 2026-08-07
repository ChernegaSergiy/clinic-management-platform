# Специфікація архітектури MedCore

Цей документ фіксує основні архітектурні принципи, шари та обов'язки платформи MedCore. 

---

## 1. Три шари системи

### 1.1 Kernel (Ядро)

Ядро — це механізм, який **не знає** жодної предметної області (Appointment, Patient, MedicalRecord тощо). Воно вміє:
- підняти DI-контейнер через Symfony Kernel;
- знайти й завантажити бандли, зареєстровані в `config/bundles.php`;
- дати бандлу можливість зареєструвати маршрути через атрибути, DI-сервіси, права, політики, слухачів подій через `CompilerPass` та `Extension`;
- прийняти HTTP-запит, знайти маршрут, викликати контролер;
- обробити виняток і повернути відповідь.

Ядро не містить маршрутів конкретних сторінок, реєстрацій конкретних репозиторіїв та доменних логік.

Namespace: `App\Core\*` (контракти й абстракції) та `App\Infrastructure\*` (файлова система, з'єднання).

### 1.2 Bundle (Модуль)

Бандл — самодостатня одиниця домену: `Appointment`, `Patient`, `MedicalRecord`, `Billing` і т.д. Кожен бандл:
- сам реєструє свої маршрути (через `#[Route]` атрибути);
- сам реєструє свої DI-сервіси (через `Extension`);
- сам реєструє свої права та політики (через `CompilerPass`);
- сам реєструє своїх слухачів подій;
- сам володіє своїми шаблонами та перекладами.

Бандл спілкується з іншими бандлами виключно через **публічний інтерфейс репозиторію** (внесений у DI за інтерфейсом) або через **доменні події**.

### 1.3 Host Application

Host Application не є окремим привілейованим шаром. "Сайт" (публічні сторінки `/`, `/about`) — це такий самий бандл (`SiteBundle`), просто без складних прав чи політик. Директорія `src/Controller/*` з хардкодженими маршрутами не існує.

---

## 2. Контракт бандла (Bundle Contract)

Кожен бандл є стандартним Symfony Bundle та наслідує `Symfony\Component\HttpKernel\Bundle\Bundle`:

### Структура директорії бандла
```
src/Bundles/<Name>Bundle/
├── <Name>Bundle.php             # Головний клас бандла
├── Controller/
│   └── <Name>Controller.php     # Контролери з атрибутами #[Route]
├── DependencyInjection/
│   ├── <Name>Extension.php      # Реєстрація DI сервісів
│   └── Compiler/
│       └── <Name>PermissionsPass.php # Реєстрація прав та політик
├── Repository/
│   └── <Name>Repository.php
├── <Name>Policy.php             # Доменні правила доступу
├── templates/                   # Шаблони Twig
└── translations/                # Переклади
```

### Реєстрація DI-сервісів та Прав

Сервіси модуля реєструються через `DependencyInjection/<Name>Extension.php`. 
Права доступу та політики додаються до реєстру через `CompilerPass`.

```php
class PatientPermissionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(PermissionRegistry::class)) {
            $registry = $container->getDefinition(PermissionRegistry::class);
            $registry->addMethodCall('add', ['patient.read', 'Перегляд пацієнтів']);
        }
    }
}
```

---

## 3. Життєвий цикл запиту

1. `public/index.php` стартує сесію та ініціалізує `App\Kernel` (через Symfony Runtime).
2. `App\Kernel`:
   - завантажує глобальні конфігурації з `config/services.php` та `config/routes.yaml`;
   - знаходить усі бандли, прописані в `config/bundles.php`;
   - для кожного бандла завантажує `Extension` та виконує зареєстровані `CompilerPass` (наприклад, реєстрацію прав у `PermissionRegistry`);
   - компілює DI контейнер.
3. Symfony `HttpKernel` приймає `Request`.
4. Встановлюються реєстри у глобальний `Gate` (через подію або ін’єкцію).
5. `Router` знаходить потрібний контролер за маршрутом.
6. Контролер обробляє запит і повертає `Response`.
7. `HttpKernel` відправляє відповідь користувачу.

---

## 4. Заборонені патерни (NEVER)

- **NEVER** реєструвати маршрут напряму в `public/index.php`.
- **NEVER** дописувати реєстрацію сервісу конкретного домену в глобальний `config/services.php`. Тільки через `<Name>Extension::load()`.
- **NEVER** створювати клас контролера поза `src/Bundles/<Name>Bundle/`.
- **NEVER** тайпхінтити конкретний клас репозиторію іншого бандла (завжди використовувати Interface).
- **NEVER** одному бандлу читати чи писати файли в директорії іншого бандла.
- **NEVER** одному бандлу викликати метод контролера чи репозиторію іншого бандла напряму для сповіщення про зміну стану — тільки через `App\Event\*`.
- **NEVER** ігнорувати реєстрацію `Permissions` і `Policies` для модулів, які керують медичними/чутливими даними.
