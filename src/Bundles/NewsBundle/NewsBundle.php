<?php

namespace App\Bundles\NewsBundle;

use App\Bundles\NewsBundle\DependencyInjection\Compiler\NewsPermissionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NewsBundle extends Bundle
{
    public function build(ContainerBuilder $container) : void
    {
        parent::build($container);
        $container->addCompilerPass(new NewsPermissionsPass());
    }
}
