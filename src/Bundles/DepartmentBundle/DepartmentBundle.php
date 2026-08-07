<?php

namespace App\Bundles\DepartmentBundle;

use App\Bundles\DepartmentBundle\DependencyInjection\Compiler\DepartmentPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DepartmentBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new DepartmentPermissionsPass());
    }
}
