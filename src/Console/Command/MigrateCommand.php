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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MigrateCommand extends Command
{
    protected static $defaultName = 'db:migrate';

    protected function configure() : void
    {
        $this
            ->setName('db:migrate')
            ->setDescription('Run database migrations using Phinx');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $output->writeln('Running database migrations...');

        $process = Process::fromShellCommandline('php vendor/bin/phinx migrate -c phinx.php');
        $process->setTimeout(300); // 5 minutes

        $process->run(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $output->writeln('<error>Migration failed</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Migrations completed successfully</info>');
        return Command::SUCCESS;
    }
}
