# Clinic Management Platform

[![PHPStan](https://github.com/medcore-ua/medcore/actions/workflows/phpstan.yml/badge.svg)](https://github.com/medcore-ua/medcore/actions/workflows/phpstan.yml)
[![PHPCS](https://github.com/medcore-ua/medcore/actions/workflows/phpcs.yml/badge.svg)](https://github.com/medcore-ua/medcore/actions/workflows/phpcs.yml)
[![PHPUnit](https://github.com/medcore-ua/medcore/actions/workflows/phpunit.yml/badge.svg)](https://github.com/medcore-ua/medcore/actions/workflows/phpunit.yml)
[![Test Coverage](https://img.shields.io/codecov/c/github/ChernegaSergiy/clinic-management-platform?label=Test%20Coverage&logo=codecov)](https://app.codecov.io/gh/ChernegaSergiy/clinic-management-platform)

A robust, three-tier healthcare information system designed to automate key workflows in modern medical centers. This platform is built with a modular architecture, prioritizing scalability, security, and compliance with national regulatory requirements.

## Overview

The platform provides a comprehensive suite of tools for healthcare providers, covering the full patient service cycle from initial appointment booking to the maintenance of detailed electronic medical records (EMR). Built with a modular three-tier architecture, the system integrates resource management, staff scheduling, and automated reporting into a single workspace, ensuring scalability and compliance with national regulatory standards. Currently, the project is undergoing a strategic transition to a Symfony-based component architecture to further enhance its flexibility and long-term maintainability.

## Technology Stack

- **Backend:** PHP 8.2+
- **Templating Engine:** Twig
- **Frontend:** Semantic UI, Vanilla JS
- **Database:** MySQL/MariaDB, with SQLite support for development
- **Web Server:** Nginx + PHP-FPM

## Quick Start

> Detailed deployment instructions will be added to `docs/deployment.md`.

1. **Clone the repository:**
   ```bash
   git clone https://github.com/medcore-ua/medcore.git
   cd medcore
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Configure the environment:**
   - Copy `.env.example` to `.env`.
   - Specify the connection parameters for your database.

4. **Run migrations and data seeding:**
   ```bash
   composer db:migrate
   composer db:seed
   ```

5. **Configure the web server** to point to the `public/` directory as the root.

6. **Start the local server (for development):**
   ```bash
   php -S localhost:8000 -t public
   ```

## How to Run Code Quality Checks

To ensure code quality, the project uses the following tools:

- **PHPStan** — static analysis to detect errors in PHP code
- **PHPCS** — code style checking according to PSR-12 standard
- **PHPUnit** — running Unit and Integration tests

### Installing Dependencies

```bash
composer install
```

### Running Checks

All commands are executed from the `www/` directory:

```bash
# Static analysis with PHPStan
composer stan

# Code style check with PHPCS
composer cs-check

# Automatic style fixes with PHPCS
composer cs

# Running PHPUnit tests
composer test
```

### Running Individual Tools

```bash
# PHPStan
vendor/bin/phpstan analyse

# PHPCS
vendor/bin/phpcs --standard=PSR12 --ignore=vendor/ public/ src/

# PHPCBF (automatic fixes)
vendor/bin/phpcbf --standard=PSR12 --ignore=vendor/ public/ src/

# PHPUnit
vendor/bin/phpunit
```

### CI Checks

GitHub Actions automatically runs all checks on every push to branches `main`, `epic/**`, `feature/**`, `fix/**` and on Pull Requests to `main`. The status of checks is displayed in the badges at the beginning of this file.

## License

This project is licensed under the [CSSM Unlimited License v2.0](LICENSE).
