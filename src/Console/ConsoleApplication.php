<?php

namespace App\Console;

use App\Console\Command\CalculateKpisCommand;
use App\Console\Command\MigrateCommand;
use Symfony\Component\Console\Application;

class ConsoleApplication extends Application
{
    public function __construct()
    {
        parent::__construct('Clinic Management Platform', '1.0.0');

        // Register commands here
        $this->add(new CalculateKpisCommand());
        $this->add(new MigrateCommand());
    }
}
