<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Repository;

use App\Domain\Employee\Entity\EmployeeSkillPath;
use App\Domain\User\Entity\User;
use App\Domain\WorkSchedule\Entity\WorkSchedule;
use DateTimeImmutable;

interface WorkScheduleRepositoryInterface
{
    public function save(WorkSchedule $workSchedule): void;

    public function remove(WorkSchedule $workSchedule): void;

    public function findById(string $id): ?WorkSchedule;

    /**
     * @return array<WorkSchedule>
     */
    public function findByUser(User $user, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;

    /**
     * @return array<WorkSchedule>
     */
    public function findBySkillPath(EmployeeSkillPath $skillPath, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;

    /**
     * @return array<WorkSchedule>
     */
    public function findOverlappingSchedules(
        User $user,
        DateTimeImmutable $date,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime
    ): array;

    /**
     * @return array<WorkSchedule>
     */
    public function findByDateRange(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;
} 