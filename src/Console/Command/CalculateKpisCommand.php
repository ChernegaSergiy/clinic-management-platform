<?php

namespace App\Console\Command;

use App\Module\Dashboard\Service\KpiCalculatorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateKpisCommand extends Command
{
    protected static $defaultName = 'kpis:calculate';

    protected function configure(): void
    {
        $this
            ->setName('kpis:calculate')
            ->setDescription('Calculate and store KPIs for the clinic management platform')
            ->addArgument('date', InputArgument::OPTIONAL, 'Date for KPI calculation (YYYY-MM-DD), defaults to today');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateArg = $input->getArgument('date');

        $service = new KpiCalculatorService();
        $output->writeln("Running KPI calculation for date: " . ($dateArg ?: 'today'));
        $service->calculateAndStoreAll($dateArg);
        $output->writeln("Done.");
        $output->writeln("KPI calculations completed.");

        return Command::SUCCESS;
    }
}