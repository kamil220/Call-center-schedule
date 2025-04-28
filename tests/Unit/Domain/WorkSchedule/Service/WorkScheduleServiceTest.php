<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WorkSchedule\Service;

use App\Domain\Employee\Entity\EmployeeSkillPath;
use App\Domain\Employee\Entity\SkillPath;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\EmploymentType;
use App\Domain\User\ValueObject\UserId;
use App\Domain\WorkSchedule\Entity\WorkSchedule;
use App\Domain\WorkSchedule\Exception\InvalidWorkScheduleException;
use App\Domain\WorkSchedule\Repository\WorkScheduleRepositoryInterface;
use App\Domain\WorkSchedule\Service\WorkScheduleService;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class WorkScheduleServiceTest extends TestCase
{
    private WorkScheduleService $workScheduleService;
    /** @var WorkScheduleRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject */
    private mixed $workScheduleRepository;
    private User $user;
    private EmployeeSkillPath $skillPath;
    private DateTimeImmutable $date;
    private TimeRange $timeRange;

    protected function setUp(): void
    {
        $this->workScheduleRepository = $this->createMock(WorkScheduleRepositoryInterface::class);
        $this->workScheduleService = new WorkScheduleService($this->workScheduleRepository);

        // Create test data
        $this->user = new User(
            UserId::fromString(Uuid::uuid4()->toString()),
            'test@example.com',
            'John',
            'Doe',
            EmploymentType::EMPLOYMENT_CONTRACT
        );

        $skillPathEntity = new SkillPath('Test Skill Path');
        $this->skillPath = new EmployeeSkillPath($this->user, $skillPathEntity);
        $this->user->addEmployeeSkillPath($this->skillPath);

        $this->date = new DateTimeImmutable('2024-03-20');
        $this->timeRange = new TimeRange(
            new DateTimeImmutable('2024-03-20 09:00:00'),
            new DateTimeImmutable('2024-03-20 17:00:00')
        );
    }

    public function testCreateScheduleSuccessfully(): void
    {
        // Arrange
        $this->workScheduleRepository
            ->expects($this->once())
            ->method('findOverlappingSchedules')
            ->willReturn([]);

        $this->workScheduleRepository
            ->expects($this->once())
            ->method('findByUser')
            ->willReturn([]);

        $this->workScheduleRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(WorkSchedule::class));

        // Act
        $schedule = $this->workScheduleService->createSchedule(
            $this->user,
            $this->skillPath,
            $this->date,
            $this->timeRange
        );

        // Assert
        $this->assertInstanceOf(WorkSchedule::class, $schedule);
        $this->assertSame($this->user, $schedule->getUser());
        $this->assertSame($this->skillPath, $schedule->getSkillPath());
        $this->assertSame($this->date, $schedule->getDate());
        $this->assertSame($this->timeRange, $schedule->getTimeRange());
    }

    public function testCreateScheduleThrowsExceptionWhenUserDoesNotHaveSkillPath(): void
    {
        // Arrange
        $newSkillPath = new EmployeeSkillPath(
            $this->user,
            new SkillPath('Another Skill Path')
        );

        // Assert
        $this->expectException(InvalidWorkScheduleException::class);
        $this->expectExceptionMessage('Employee does not have the required skill path');

        // Act
        $this->workScheduleService->createSchedule(
            $this->user,
            $newSkillPath,
            $this->date,
            $this->timeRange
        );
    }

    public function testCreateScheduleThrowsExceptionWhenSchedulesOverlap(): void
    {
        // Arrange
        $existingSchedule = new WorkSchedule(
            $this->user,
            $this->skillPath,
            $this->date,
            $this->timeRange
        );

        $this->workScheduleRepository
            ->expects($this->once())
            ->method('findOverlappingSchedules')
            ->willReturn([$existingSchedule]);

        // Assert
        $this->expectException(InvalidWorkScheduleException::class);
        $this->expectExceptionMessage('Schedule overlaps with existing schedules');

        // Act
        $this->workScheduleService->createSchedule(
            $this->user,
            $this->skillPath,
            $this->date,
            $this->timeRange
        );
    }

    public function testCreateScheduleThrowsExceptionWhenExceedingDailyHours(): void
    {
        // Arrange
        $existingSchedule = new WorkSchedule(
            $this->user,
            $this->skillPath,
            $this->date,
            new TimeRange(
                new DateTimeImmutable('2024-03-20 08:00:00'),
                new DateTimeImmutable('2024-03-20 16:00:00')
            )
        );

        $this->workScheduleRepository
            ->expects($this->once())
            ->method('findOverlappingSchedules')
            ->willReturn([]);

        $this->workScheduleRepository
            ->expects($this->once())
            ->method('findByUser')
            ->willReturn([$existingSchedule]);

        $newTimeRange = new TimeRange(
            new DateTimeImmutable('2024-03-20 16:30:00'),
            new DateTimeImmutable('2024-03-20 23:00:00')
        );

        // Assert
        $this->expectException(InvalidWorkScheduleException::class);
        $this->expectExceptionMessage('Total working hours cannot exceed 12 hours per day');

        // Act
        $this->workScheduleService->createSchedule(
            $this->user,
            $this->skillPath,
            $this->date,
            $newTimeRange
        );
    }

    public function testCreateScheduleThrowsExceptionWhenBreakIsTooShort(): void
    {
        // Arrange
        $existingSchedule = new WorkSchedule(
            $this->user,
            $this->skillPath,
            $this->date,
            new TimeRange(
                new DateTimeImmutable('2024-03-20 08:00:00'),
                new DateTimeImmutable('2024-03-20 12:00:00')
            )
        );

        $this->workScheduleRepository
            ->expects($this->once())
            ->method('findOverlappingSchedules')
            ->willReturn([]);

        $this->workScheduleRepository
            ->expects($this->once())
            ->method('findByUser')
            ->willReturn([$existingSchedule]);

        $newTimeRange = new TimeRange(
            new DateTimeImmutable('2024-03-20 12:15:00'), // Only 15 minutes break
            new DateTimeImmutable('2024-03-20 16:00:00')
        );

        // Assert
        $this->expectException(InvalidWorkScheduleException::class);
        $this->expectExceptionMessage('Minimum break between shifts must be 30 minutes');

        // Act
        $this->workScheduleService->createSchedule(
            $this->user,
            $this->skillPath,
            $this->date,
            $newTimeRange
        );
    }

    public function testUpdateScheduleSuccessfully(): void
    {
        // Arrange
        $schedule = new WorkSchedule(
            $this->user,
            $this->skillPath,
            $this->date,
            $this->timeRange
        );

        $newTimeRange = new TimeRange(
            new DateTimeImmutable('2024-03-20 10:00:00'),
            new DateTimeImmutable('2024-03-20 18:00:00')
        );

        $this->workScheduleRepository
            ->expects($this->once())
            ->method('findOverlappingSchedules')
            ->willReturn([]);

        $this->workScheduleRepository
            ->expects($this->once())
            ->method('findByUser')
            ->willReturn([]);

        $this->workScheduleRepository
            ->expects($this->once())
            ->method('save')
            ->with($schedule);

        // Act
        $this->workScheduleService->updateSchedule(
            $schedule,
            $this->skillPath,
            $newTimeRange,
            'Updated notes'
        );

        // Assert
        $this->assertSame($newTimeRange, $schedule->getTimeRange());
        $this->assertSame('Updated notes', $schedule->getNotes());
    }

    public function testRemoveSchedule(): void
    {
        // Arrange
        $schedule = new WorkSchedule(
            $this->user,
            $this->skillPath,
            $this->date,
            $this->timeRange
        );

        $this->workScheduleRepository
            ->expects($this->once())
            ->method('remove')
            ->with($schedule);

        // Act
        $this->workScheduleService->removeSchedule($schedule);
    }
} 