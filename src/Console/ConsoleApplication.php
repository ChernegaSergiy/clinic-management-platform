<?php

namespace App\Console;

use App\Console\Command\CalculateKpisCommand;
use App\Console\Command\MigrateCommand;
use App\Infrastructure\Database\DoctrineFactory;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\Migrations\Tools\Console\Command\LatestCommand;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand as DoctrineMigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\Migrations\Tools\Console\Command\VersionCommand;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Symfony\Component\Console\Application;

class ConsoleApplication extends Application
{
    public function __construct()
    {
        parent::__construct('Clinic Management Platform', '1.0.0');

        $this->add(new CalculateKpisCommand());
        $this->add(new MigrateCommand());

        $entityManager = DoctrineFactory::createEntityManager();

        // Add Doctrine ORM commands
        ConsoleRunner::addCommands($this, new SingleManagerProvider($entityManager));

        // Add Doctrine Migrations commands
        $config = new PhpFile(dirname(__DIR__, 2) . '/migrations.php');
        $dependencyFactory = DependencyFactory::fromEntityManager($config, new ExistingEntityManager($entityManager));

        $this->addCommands([
            new DiffCommand($dependencyFactory),
            new ExecuteCommand($dependencyFactory),
            new GenerateCommand($dependencyFactory),
            new LatestCommand($dependencyFactory),
            new DoctrineMigrateCommand($dependencyFactory),
            new StatusCommand($dependencyFactory),
            new UpToDateCommand($dependencyFactory),
            new VersionCommand($dependencyFactory),
        ]);
    }
}
