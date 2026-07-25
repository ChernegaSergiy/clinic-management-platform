<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public static ?\Symfony\Component\DependencyInjection\ContainerInterface $staticContainer = null;

    public function boot(): void
    {
        parent::boot();
        self::$staticContainer = $this->getContainer();
    }
}
