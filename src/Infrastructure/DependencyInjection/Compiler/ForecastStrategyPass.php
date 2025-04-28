<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Compiler;

use App\Domain\Forecast\Service\ForecastService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ForecastStrategyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ForecastService::class)) {
            return;
        }

        $definition = $container->findDefinition(ForecastService::class);
        $taggedServices = $container->findTaggedServiceIds('app.forecast_strategy');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addStrategy', [new Reference($id)]);
        }
    }
} 