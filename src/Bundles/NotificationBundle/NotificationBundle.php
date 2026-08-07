<?php

namespace App\Bundles\NotificationBundle;

use App\Bundles\NotificationBundle\DependencyInjection\Compiler\NotificationPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NotificationBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new NotificationPermissionsPass());
    }
}
