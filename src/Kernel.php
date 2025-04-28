<?php

namespace App;

use App\Infrastructure\DependencyInjection\Compiler\AvailabilityStrategyPass;
use App\Infrastructure\DependencyInjection\Compiler\LeaveTypeStrategyPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new LeaveTypeStrategyPass());
        $container->addCompilerPass(new AvailabilityStrategyPass());
    }
}
