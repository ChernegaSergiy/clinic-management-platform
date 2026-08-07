<?php

namespace App\Bundles\InsuranceBundle;

use App\Bundles\InsuranceBundle\DependencyInjection\Compiler\InsurancePermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class InsuranceBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new InsurancePermissionsPass());
    }
}
