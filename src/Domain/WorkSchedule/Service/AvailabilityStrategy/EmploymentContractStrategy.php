<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service\AvailabilityStrategy;

use App\Domain\WorkSchedule\Entity\Availability;
use App\Domain\WorkSchedule\Exception\InvalidAvailabilityException;
use App\Domain\User\ValueObject\EmploymentType;
use App\Domain\WorkSchedule\Repository\AvailabilityRepositoryInterface;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class EmploymentContractStrategy implements AvailabilityStrategyInterface
{
    public function __construct(
        private readonly AvailabilityRepositoryInterface $availabilityRepository
    ) {
    }

    public function supports(EmploymentType $employmentType): bool
    {
        return $employmentType === EmploymentType::EMPLOYMENT_CONTRACT;
    }

    public function generateAvailabilities(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $parameters
    ): array {
        if (!isset($parameters['workingHours'])) {
            throw new InvalidAvailabilityException('Working hours must be specified for employment contract');
        }

        $workingHours = $parameters['workingHours'];
        if (!isset($workingHours['start'], $workingHours['end'])) {
            throw new InvalidAvailabilityException('Start and end time must be specified for working hours');
        }

        $availabilities = [];
        $currentDate = $startDate;
        $endDate = $endDate->modify('-1 day'); // Nie włączamy ostatniego dnia

        while ($currentDate <= $endDate) {
            // Skip weekends
            if ($currentDate->format('N') >= 6) {
                $currentDate = $currentDate->modify('+1 day');
                continue;
            }

            $startTime = $currentDate->setTime(
                (int) $workingHours['start']['hour'],
                (int) $workingHours['start']['minute']
            );
            $endTime = $currentDate->setTime(
                (int) $workingHours['end']['hour'],
                (int) $workingHours['end']['minute']
            );

            $timeRange = new TimeRange($startTime, $endTime);
            $availability = new Availability(
                Uuid::uuid4(),
                $parameters['user'],
                EmploymentType::EMPLOYMENT_CONTRACT,
                $timeRange,
                $currentDate,
                [
                    'type' => 'weekly',
                    'days' => [1, 2, 3, 4, 5] // Poniedziałek-Piątek
                ]
            );

            $this->validateForGeneration($availability);
            $availabilities[] = $availability;

            $currentDate = $currentDate->modify('+1 day');
        }

        return $availabilities;
    }

    public function validate(Availability $availability): void
    {
        $this->validateTimeRange($availability);
        $this->validateWeeklyHours($availability);
        $this->validateNoticePeriod($availability);
        $this->validateWorkingDays($availability);
    }

    private function validateForGeneration(Availability $availability): void
    {
        $this->validateTimeRange($availability);
        $this->validateWeeklyHours($availability);
        $this->validateWorkingDays($availability);
    }

    private function validateTimeRange(Availability $availability): void
    {
        $duration = $availability->getTimeRange()->getDurationInMinutes();
        $maxHours = $availability->getEmploymentType()->getMaxDailyHours();
        
        if ($duration > $maxHours * 60) {
            throw new InvalidAvailabilityException(
                sprintf('Daily working hours cannot exceed %d hours for employment contract', $maxHours)
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
                sprintf('Weekly working hours cannot exceed %d hours for employment contract', $maxWeeklyHours)
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
                    'Availability must be submitted at least %d hours in advance for employment contract',
                    $minimumNoticeHours
                )
            );
        }
    }

    private function validateWorkingDays(Availability $availability): void
    {
        $dayOfWeek = (int) $availability->getDate()->format('N');
        if ($dayOfWeek >= 6) {
            throw new InvalidAvailabilityException(
                'Standard employment contract does not allow working on weekends'
            );
        }
    }
} 