# Система управління клінікою (Clinic Management Platform)

![PHPStan](https://github.com/ChernegaSergiy/clinic-management-platform/actions/workflows/phpstan.yml/badge.svg)
![PHPCS](https://github.com/ChernegaSergiy/clinic-management-platform/actions/workflows/phpcs.yml/badge.svg)
![PHPUnit](https://github.com/ChernegaSergiy/clinic-management-platform/actions/workflows/phpunit.yml/badge.svg)
[![Test Coverage](https://img.shields.io/codecov/c/github/ChernegaSergiy/clinic-management-platform?label=Test%20Coverage&logo=codecov)](https://app.codecov.io/gh/ChernegaSergiy/clinic-management-platform)

Це навчальний курсовий проєкт, метою якого є розробка трирівневої веб-системи для автоматизації ключових процесів сучасної клініки. Система розробляється з урахуванням українських реалій та нормативних вимог.

## Огляд

Проєкт охоплює повний цикл обслуговування пацієнта: від запису на прийом до ведення електронної медичної картки, управління ресурсами та формування звітності. Архітектура системи є модульною, що дозволяє гнучко розширювати функціонал.

## Технологічний стек

- **Бекенд:** PHP 8.2+
- **Шаблонізатор:** Twig
- **Фронтенд:** Semantic UI, Vanilla JS
- **База даних:** MySQL/MariaDB, з підтримкою SQLite для розробки
- **Веб-сервер:** Nginx + PHP-FPM

## Швидкий старт

> Детальні інструкції з розгортання будуть додані в `docs/deployment.md`.

1.  **Клонуйте репозиторій:**
    ```bash
    git clone https://github.com/your-username/clinic-management-platform.git
    cd clinic-management-platform/www
    ```

2.  **Встановіть залежності:**
    ```bash
    composer install
    ```

3.  **Налаштуйте середовище:**
    - Скопіюйте `.env.example` у `.env`.
    - Вкажіть параметри підключення до вашої бази даних.

4.  **Запустіть міграції та наповнення даними:**
    ```bash
    composer db:migrate
    composer db:seed
    ```

5.  **Налаштуйте веб-сервер,** щоб він вказував на директорію `public/` як на кореневу.

6.  **Запустіть локальний сервер (для розробки):**
    ```bash
    php -S localhost:8000 -t public
    ```

## Як запустити перевірки якості коду

Для забезпечення якості коду проєкт використовує такі інструменти:

- **PHPStan** — статичний аналіз для виявлення помилок у PHP-коді
- **PHPCS** — перевірка стилю коду за стандартом PSR-12
- **PHPUnit** — запуск Unit та Integration тестів

### Встановлення залежностей

```bash
composer install
```

### Запуск перевірок

Всі команди виконуються з директорії `www/`:

```bash
# Статичний аналіз PHPStan
composer stan

# Перевірка стилю коду PHPCS
composer cs-check

# Автоматичне виправлення стилів PHPCS
composer cs

# Запуск PHPUnit тестів
composer test
```

### Запуск окремих інструментів

```bash
# PHPStan
vendor/bin/phpstan analyse

# PHPCS
vendor/bin/phpcs --standard=PSR12 --ignore=vendor/ public/ src/

# PHPCBF (автоматичне виправлення)
vendor/bin/phpcbf --standard=PSR12 --ignore=vendor/ public/ src/

# PHPUnit
vendor/bin/phpunit
```

### Перевірка в CI

GitHub Actions автоматично запускає всі перевірки при кожному push до гілок `main`, `epic/**`, `feature/**`, `fix/**` та при Pull Requests до `main`. Статус перевірок відображається у бейджах на початку цього файлу.
