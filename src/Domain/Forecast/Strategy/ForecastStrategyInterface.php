<?php

declare(strict_types=1);

namespace App\Domain\Forecast\Strategy;

use App\Domain\Forecast\ValueObject\ForecastPeriod;
use App\Domain\Forecast\ValueObject\ForecastResult;

interface ForecastStrategyInterface
{
    public function forecast(ForecastPeriod $period): ForecastResult;
    
    public function getName(): string;
    
    public function getDescription(): string;
} 