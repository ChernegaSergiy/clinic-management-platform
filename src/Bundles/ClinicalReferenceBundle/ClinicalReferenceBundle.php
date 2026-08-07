<?php

namespace App\Bundles\ClinicalReferenceBundle;

use App\Bundles\ClinicalReferenceBundle\DependencyInjection\Compiler\ClinicalReferencePermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ClinicalReferenceBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new ClinicalReferencePermissionsPass());
    }
}
