<?php

namespace App\Bundles\InventoryBundle;

use App\Bundles\InventoryBundle\DependencyInjection\Compiler\InventoryPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class InventoryBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new InventoryPermissionsPass());
    }
}
