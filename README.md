# Clinic Management Platform

[![PHPStan](https://github.com/medcore-ua/medcore/actions/workflows/phpstan.yml/badge.svg)](https://github.com/medcore-ua/medcore/actions/workflows/phpstan.yml)
[![PHPCS](https://github.com/medcore-ua/medcore/actions/workflows/phpcs.yml/badge.svg)](https://github.com/medcore-ua/medcore/actions/workflows/phpcs.yml)
[![PHPUnit](https://github.com/medcore-ua/medcore/actions/workflows/phpunit.yml/badge.svg)](https://github.com/medcore-ua/medcore/actions/workflows/phpunit.yml)
[![Test Coverage](https://img.shields.io/codecov/c/github/medcore-ua/medcore?label=Test%20Coverage&logo=codecov)](https://app.codecov.io/gh/medcore-ua/medcore)

A robust, three-tier healthcare information system designed to automate key workflows in modern medical centers. This platform is built with a modular architecture, prioritizing scalability, security, and compliance with national regulatory requirements.

## Features

- **Electronic Medical Records**: Comprehensive tracking of patient history, diagnoses, and treatment plans.
- **Appointment Scheduling**: Interactive calendars for doctors and staff to manage patient visits efficiently.
- **Advanced Access Control**: Modular Role-Based Access Control (RBAC) with granular, policy-based permissions for each bundle.
- **Billing and Insurance**: Integrated tracking of invoices, payments, and insurance claims.
- **Laboratory Management**: Seamless processing of lab orders and test results.
- **Inventory Tracking**: Monitoring of medical supplies, pharmaceuticals, and equipment.
- **Extensible Architecture**: Built on a modern, decoupled bundle system ready for future scaling.

## Technology Stack

- **Backend:** PHP 8.2+
- **Templating Engine:** Twig
- **Frontend:** Semantic UI, Vanilla JS
- **Database:** MySQL/MariaDB, with SQLite support for development
- **Web Server:** Nginx + PHP-FPM

## Quick Start

> [!NOTE]
> Detailed deployment instructions will be added to [docs/deployment.md](docs/deployment.md).

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

All commands are executed from the root of the project:

```bash
# Static analysis with PHPStan
composer stan

# Code style check with PHPCS
composer cs

# Automatic style fixes with PHPCS
composer cs-fix

# Running PHPUnit tests
composer test
```

### Running Individual Tools

```bash
# PHPStan
vendor/bin/phpstan analyse

# PHPCS
vendor/bin/phpcs .

# PHPCBF (automatic fixes)
vendor/bin/phpcbf .

# PHPUnit
vendor/bin/phpunit
```

### CI Checks

GitHub Actions automatically runs all checks on every push to branches `main`, `epic/**`, `feature/**`, `fix/**` and on Pull Requests to `main`. The status of checks is displayed in the badges at the beginning of this file.

## Contributing

Contributions are welcome and appreciated! Here's how you can contribute:

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Please make sure to update tests as appropriate and adhere to the existing coding style.

## License

This project is licensed under the CSSM Unlimited License v2.0 (CSSM-ULv2). See the [LICENSE](LICENSE) file for details.
