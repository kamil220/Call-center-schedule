<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service\AvailabilityStrategy;

use App\Domain\WorkSchedule\Entity\Availability;
use App\Domain\WorkSchedule\Exception\InvalidAvailabilityException;
use App\Domain\User\ValueObject\EmploymentType;
use App\Domain\WorkSchedule\Repository\AvailabilityRepositoryInterface;
use DateTimeImmutable;

final class CivilContractStrategy implements AvailabilityStrategyInterface
{
    public function __construct(
        private readonly AvailabilityRepositoryInterface $availabilityRepository
    ) {
    }

    public function supports(EmploymentType $employmentType): bool
    {
        return $employmentType === EmploymentType::CIVIL_CONTRACT;
    }

    public function generateAvailabilities(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $parameters
    ): array {
        // Civil contract does not automatically generate availabilities
        return [];
    }

    public function validate(Availability $availability): void
    {
        $this->validateTimeRange($availability);
        $this->validateWeeklyHours($availability);
        $this->validateNoticePeriod($availability);
        $this->validateWeekendWork($availability);
    }

    private function validateTimeRange(Availability $availability): void
    {
        $duration = $availability->getTimeRange()->getDurationInMinutes();
        $maxHours = $availability->getEmploymentType()->getMaxDailyHours();
        
        if ($duration > $maxHours * 60) {
            throw new InvalidAvailabilityException(
                sprintf('Daily working hours cannot exceed %d hours for civil contract', $maxHours)
            );
        }
    }

    private function validateWeeklyHours(Availability $availability): void
    {
        $weekStart = $availability->getDate()->modify('monday this week')->setTime(0, 0);
        $weekEnd = (clone $weekStart)->modify('+6 days')->setTime(23, 59, 59);

        $weeklyAvailabilities = $this->availabilityRepository->findByUserAndDateRange(
            $availability->getUser(),
            $weekStart,
            $weekEnd
        );

        $totalMinutes = array_reduce(
            $weeklyAvailabilities,
            fn(int $sum, Availability $item) => $sum + $item->getTimeRange()->getDurationInMinutes(),
            0
        );

        $totalMinutes += $availability->getTimeRange()->getDurationInMinutes();
        $maxWeeklyHours = $availability->getEmploymentType()->getMaxWeeklyHours();

        if ($totalMinutes > $maxWeeklyHours * 60) {
            throw new InvalidAvailabilityException(
                sprintf('Weekly working hours cannot exceed %d hours for civil contract', $maxWeeklyHours)
            );
        }
    }

    private function validateNoticePeriod(Availability $availability): void
    {
        $now = new DateTimeImmutable();
        $minimumNoticeHours = $availability->getEmploymentType()->getMinimumNoticePeriodHours();
        $minimumNoticeDate = $now->modify(sprintf('+%d hours', $minimumNoticeHours));

        if ($availability->getDate() < $minimumNoticeDate) {
            throw new InvalidAvailabilityException(
                sprintf(
                    'Availability must be submitted at least %d hours in advance for civil contract',
                    $minimumNoticeHours
                )
            );
        }
    }

    private function validateWeekendWork(Availability $availability): void
    {
        // Implementation needed
    }
} 