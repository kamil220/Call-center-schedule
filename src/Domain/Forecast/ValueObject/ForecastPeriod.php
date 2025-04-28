<?php

declare(strict_types=1);

namespace App\Domain\Forecast\ValueObject;

class ForecastPeriod
{
    public function __construct(
        private readonly \DateTimeInterface $startDate,
        private readonly \DateTimeInterface $endDate
    ) {
        if ($startDate > $endDate) {
            throw new \InvalidArgumentException('Start date cannot be after end date');
        }
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function getDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days + 1;
    }
} 