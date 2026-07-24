<?php

namespace App\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class MigrateCommand extends Command
{
    protected static $defaultName = 'db:migrate';

    protected function configure(): void
    {
        $this
            ->setName('db:migrate')
            ->setDescription('Run database migrations using Phinx');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
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
