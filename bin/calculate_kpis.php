<?php

require dirname(__DIR__) . '/vendor/autoload.php';

// Load .env file
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Set timezone from env or default to UTC
$tz = $_ENV['APP_TIMEZONE'] ?? 'UTC';
date_default_timezone_set($tz);

use App\Module\Dashboard\Service\KpiCalculatorService;
use App\Module\Admin\KpiController;

// Minimal setup to allow KpiCalculatorService to run
// This assumes Database::getInstance() is self-initializing or already configured.
// For a full CLI app, a more robust bootstrap might be needed.

// Optional: pass date as first CLI argument (YYYY-MM-DD). Defaults to today.
$dateArg = $argv[1] ?? null;

$controller = new KpiController();
$controller->calculateResults();

echo "KPI calculations completed.\n";

exit(0);
