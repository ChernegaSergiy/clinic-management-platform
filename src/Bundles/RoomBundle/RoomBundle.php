<?php

namespace App\Bundles\RoomBundle;

use App\Bundles\RoomBundle\DependencyInjection\Compiler\RoomPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RoomBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new RoomPermissionsPass());
    }
}
