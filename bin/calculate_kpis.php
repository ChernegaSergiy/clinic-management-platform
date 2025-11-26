<?php

require dirname(__DIR__) . '/vendor/autoload.php';

// Load .env file
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

use App\Module\Dashboard\Service\KpiCalculatorService;

// Minimal setup to allow KpiCalculatorService to run
// This assumes Database::getInstance() is self-initializing or already configured.
// For a full CLI app, a more robust bootstrap might be needed.

$kpiCalculator = new KpiCalculatorService();
$kpiCalculator->calculateAndStoreAll();

echo "KPI calculations completed.\n";

exit(0);

