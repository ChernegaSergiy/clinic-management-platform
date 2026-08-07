<?php

namespace App\Bundles\BillingBundle;

use App\Bundles\BillingBundle\DependencyInjection\Compiler\BillingPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BillingBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new BillingPermissionsPass());
    }
}
