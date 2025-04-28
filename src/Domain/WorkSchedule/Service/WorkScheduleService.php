<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service;

use App\Domain\Employee\Entity\EmployeeSkillPath;
use App\Domain\User\Entity\User;
use App\Domain\WorkSchedule\Entity\WorkSchedule;
use App\Domain\WorkSchedule\Exception\InvalidWorkScheduleException;
use App\Domain\WorkSchedule\Repository\WorkScheduleRepositoryInterface;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class WorkScheduleService
{
    public function __construct(
        private readonly WorkScheduleRepositoryInterface $workScheduleRepository
    ) {
    }

    public function createSchedule(
        User $user,
        EmployeeSkillPath $skillPath,
        DateTimeImmutable $date,
        TimeRange $timeRange,
        ?string $notes = null
    ): WorkSchedule {
        // Validate employee has the skill path
        if (!$user->hasSkillPath($skillPath)) {
            throw new InvalidWorkScheduleException('Employee does not have the required skill path');
        }

        // Check for overlapping schedules
        $overlappingSchedules = $this->workScheduleRepository->findOverlappingSchedules(
            $user,
            $date,
            $timeRange->getStartTime(),
            $timeRange->getEndTime()
        );

        if (!empty($overlappingSchedules)) {
            throw new InvalidWorkScheduleException('Schedule overlaps with existing schedules');
        }

        // Validate working hours
        $this->validateWorkingHours($user, $date, $timeRange);

        $schedule = new WorkSchedule(
            $user,
            $skillPath,
            $date,
            $timeRange,
            $notes
        );

        $this->workScheduleRepository->save($schedule);

        return $schedule;
    }

    public function updateSchedule(
        WorkSchedule $schedule,
        EmployeeSkillPath $skillPath,
        TimeRange $timeRange,
        ?string $notes = null
    ): void {
        // Validate employee has the skill path
        if (!$schedule->getUser()->hasSkillPath($skillPath)) {
            throw new InvalidWorkScheduleException('Employee does not have the required skill path');
        }

        // Check for overlapping schedules (excluding current schedule)
        $overlappingSchedules = $this->workScheduleRepository->findOverlappingSchedules(
            $schedule->getUser(),
            $schedule->getDate(),
            $timeRange->getStartTime(),
            $timeRange->getEndTime()
        );

        foreach ($overlappingSchedules as $overlappingSchedule) {
            if ($overlappingSchedule->getId() !== $schedule->getId()) {
                throw new InvalidWorkScheduleException('Schedule overlaps with existing schedules');
            }
        }

        // Validate working hours
        $this->validateWorkingHours($schedule->getUser(), $schedule->getDate(), $timeRange);

        $schedule->update($skillPath, $timeRange, $notes);
        $this->workScheduleRepository->save($schedule);
    }

    public function removeSchedule(WorkSchedule $schedule): void
    {
        $this->workScheduleRepository->remove($schedule);
    }

    /**
     * @return array<WorkSchedule>
     */
    public function getSchedulesByUser(User $user, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->workScheduleRepository->findByUser($user, $startDate, $endDate);
    }

    /**
     * @return array<WorkSchedule>
     */
    public function getSchedulesBySkillPath(EmployeeSkillPath $skillPath, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->workScheduleRepository->findBySkillPath($skillPath, $startDate, $endDate);
    }

    private function validateWorkingHours(User $user, DateTimeImmutable $date, TimeRange $timeRange): void
    {
        // Get all schedules for the same day
        $startOfDay = (clone $date)->setTime(0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);
        
        $dailySchedules = $this->workScheduleRepository->findByUser($user, $startOfDay, $endOfDay);

        // Calculate total working minutes for the day
        $totalMinutes = array_reduce(
            $dailySchedules,
            fn (int $sum, WorkSchedule $schedule) => $sum + $schedule->getTimeRange()->getDurationInMinutes(),
            0
        );

        $totalMinutes += $timeRange->getDurationInMinutes();

        // Maximum 12 hours (720 minutes) per day
        if ($totalMinutes > 720) {
            throw new InvalidWorkScheduleException('Total working hours cannot exceed 12 hours per day');
        }

        // Minimum 30 minutes break between shifts
        foreach ($dailySchedules as $existingSchedule) {
            $gap = $this->calculateGapBetweenTimeRanges($existingSchedule->getTimeRange(), $timeRange);
            if ($gap !== null && $gap < 30) {
                throw new InvalidWorkScheduleException('Minimum break between shifts must be 30 minutes');
            }
        }
    }

    private function calculateGapBetweenTimeRanges(TimeRange $range1, TimeRange $range2): ?int
    {
        // If ranges overlap, there is no gap
        if ($range1->overlaps($range2)) {
            return null;
        }

        // Calculate gap in minutes
        if ($range1->getEndTime() < $range2->getStartTime()) {
            return (int) $range1->getEndTime()->diff($range2->getStartTime())->i;
        }

        if ($range2->getEndTime() < $range1->getStartTime()) {
            return (int) $range2->getEndTime()->diff($range1->getStartTime())->i;
        }

        return null;
    }

    public function findById(string $id): ?WorkSchedule
    {
        return $this->workScheduleRepository->findById($id);
    }
} 