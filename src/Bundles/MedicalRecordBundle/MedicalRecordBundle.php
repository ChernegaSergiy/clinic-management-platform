<?php

namespace App\Bundles\MedicalRecordBundle;

use App\Bundles\MedicalRecordBundle\DependencyInjection\Compiler\MedicalRecordPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MedicalRecordBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new MedicalRecordPermissionsPass());
    }
}
