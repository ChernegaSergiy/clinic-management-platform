<?php

/*
 *
 *                      _
 *   _ __ ___   ___  __| | ___ ___  _ __ ___       _   _  __ _
 *  | '_ ` _ \ / _ \/ _` |/ __/ _ \| '__/ _ \_____| | | |/ _` |
 *  | | | | | |  __/ (_| | (_| (_) | | |  __/_____| |_| | (_| |
 *  |_| |_| |_|\___|\__,_|\___\___/|_|  \___|      \__,_|\__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author MedCore Ukraine
 * @link https://medcore.pp.ua/
 *
 *
 */

namespace App\Console\Command;

use App\Module\Dashboard\Service\KpiCalculatorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateKpisCommand extends Command
{
    protected static $defaultName = 'kpis:calculate';

    protected function configure() : void
    {
        $this
            ->setName('kpis:calculate')
            ->setDescription('Calculate and store KPIs for the clinic management platform')
            ->addArgument('date', InputArgument::OPTIONAL, 'Date for KPI calculation (YYYY-MM-DD), defaults to today');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
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
