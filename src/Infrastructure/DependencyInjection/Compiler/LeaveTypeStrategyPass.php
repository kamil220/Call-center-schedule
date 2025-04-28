<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Compiler;

use App\Domain\WorkSchedule\Service\LeaveType\LeaveTypeStrategyFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LeaveTypeStrategyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(LeaveTypeStrategyFactory::class)) {
            return;
        }

        $definition = $container->findDefinition(LeaveTypeStrategyFactory::class);
        $taggedServices = $container->findTaggedServiceIds('app.leave_type_strategy');

        $strategies = [];
        foreach ($taggedServices as $id => $tags) {
            $strategies[] = new Reference($id);
        }

        $definition->setArgument('$strategies', $strategies);
    }
} 