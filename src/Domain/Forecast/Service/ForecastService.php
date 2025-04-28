<?php

declare(strict_types=1);

namespace App\Domain\Forecast\Service;

use App\Domain\Forecast\Strategy\ForecastStrategyInterface;
use App\Domain\Forecast\ValueObject\ForecastPeriod;
use App\Domain\Forecast\ValueObject\ForecastResult;

class ForecastService
{
    /** @var ForecastStrategyInterface[] */
    private array $strategies = [];

    public function addStrategy(ForecastStrategyInterface $strategy): self
    {
        $this->strategies[$strategy->getName()] = $strategy;
        return $this;
    }

    public function forecast(
        ForecastPeriod $period,
        ?string $strategyName = null
    ): ForecastResult {
        if ($strategyName === null) {
            // Use the first available strategy if none specified
            if (empty($this->strategies)) {
                throw new \RuntimeException('No forecast strategies available');
            }
            $strategy = reset($this->strategies);
        } else {
            if (!isset($this->strategies[$strategyName])) {
                throw new \InvalidArgumentException(sprintf('Strategy "%s" not found', $strategyName));
            }
            $strategy = $this->strategies[$strategyName];
        }

        return $strategy->forecast($period);
    }

    /**
     * @return array<string, string> Array of strategy names and descriptions
     */
    public function getAvailableStrategies(): array
    {
        $strategies = [];
        foreach ($this->strategies as $strategy) {
            $strategies[$strategy->getName()] = $strategy->getDescription();
        }
        return $strategies;
    }
} 