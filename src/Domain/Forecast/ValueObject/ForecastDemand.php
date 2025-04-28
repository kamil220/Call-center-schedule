<?php

declare(strict_types=1);

namespace App\Domain\Forecast\ValueObject;

use App\Domain\Employee\Entity\EmployeeSkillPath;

class ForecastDemand
{
    public function __construct(
        private readonly \DateTimeInterface $date,
        private readonly int $hour,
        private readonly EmployeeSkillPath $skillPath,
        private readonly int $requiredEmployees,
        private readonly array $metadata = []
    ) {
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getHour(): int
    {
        return $this->hour;
    }

    public function getSkillPath(): EmployeeSkillPath
    {
        return $this->skillPath;
    }

    public function getRequiredEmployees(): int
    {
        return $this->requiredEmployees;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
} 