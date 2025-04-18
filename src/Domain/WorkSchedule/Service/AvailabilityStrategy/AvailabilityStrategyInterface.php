<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service\AvailabilityStrategy;

use App\Domain\WorkSchedule\Entity\Availability;
use App\Domain\User\ValueObject\EmploymentType;
use DateTimeImmutable;

interface AvailabilityStrategyInterface
{
    public function supports(EmploymentType $employmentType): bool;
    
    /**
     * @return array<Availability>
     */
    public function generateAvailabilities(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $parameters
    ): array;
    
    public function validate(Availability $availability): void;
} 