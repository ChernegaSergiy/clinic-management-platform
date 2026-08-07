<?php

namespace App\Bundles\ScheduleBundle;

use App\Bundles\ScheduleBundle\DependencyInjection\Compiler\SchedulePermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ScheduleBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new SchedulePermissionsPass());
    }
}
