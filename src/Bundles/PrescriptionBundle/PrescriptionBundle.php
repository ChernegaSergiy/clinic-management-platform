<?php

namespace App\Bundles\PrescriptionBundle;

use App\Bundles\PrescriptionBundle\DependencyInjection\Compiler\PrescriptionPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PrescriptionBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new PrescriptionPermissionsPass());
    }
}
