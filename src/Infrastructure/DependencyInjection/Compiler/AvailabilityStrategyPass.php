<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Compiler;

use App\Controller\Api\WorkSchedule\AvailabilityController;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AvailabilityStrategyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(AvailabilityController::class)) {
            return;
        }

        $definition = $container->findDefinition(AvailabilityController::class);
        $taggedServices = $container->findTaggedServiceIds('app.availability_strategy');

        $strategies = [];
        foreach ($taggedServices as $id => $tags) {
            $strategies[] = new Reference($id);
        }

        $definition->setArgument('$availabilityStrategies', $strategies);
    }
} 