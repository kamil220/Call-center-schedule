<?php

declare(strict_types=1);

namespace App\Tests\Domain\WorkSchedule\Service\AvailabilityStrategy;

use App\Domain\User\ValueObject\EmploymentType;
use App\Domain\WorkSchedule\Entity\Availability;
use App\Domain\WorkSchedule\Exception\InvalidAvailabilityException;
use App\Domain\WorkSchedule\Repository\AvailabilityRepositoryInterface;
use App\Domain\WorkSchedule\Service\AvailabilityStrategy\EmploymentContractStrategy;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use App\Domain\User\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class EmploymentContractStrategyTest extends TestCase
{
    private EmploymentContractStrategy $strategy;
    private MockObject&AvailabilityRepositoryInterface $repository;
    private User $user;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AvailabilityRepositoryInterface::class);
        $this->strategy = new EmploymentContractStrategy($this->repository);
        $this->user = $this->createMock(User::class);
    }

    public function testSupportsOnlyEmploymentContract(): void
    {
        $this->assertTrue($this->strategy->supports(EmploymentType::EMPLOYMENT_CONTRACT));
        $this->assertFalse($this->strategy->supports(EmploymentType::B2B_CONTRACT));
        $this->assertFalse($this->strategy->supports(EmploymentType::CIVIL_CONTRACT));
    }

    public function testGenerateAvailabilitiesForOneWeek(): void
    {
        $startDate = new DateTimeImmutable('2024-01-01'); // Monday
        $endDate = $startDate->modify('+1 week');

        $parameters = [
            'workingHours' => [
                'start' => ['hour' => 9, 'minute' => 0],
                'end' => ['hour' => 17, 'minute' => 0]
            ],
            'user' => $this->user
        ];

        $availabilities = $this->strategy->generateAvailabilities($startDate, $endDate, $parameters);
        
        $this->assertCount(5, $availabilities);
        
        foreach ($availabilities as $index => $availability) {
            $this->assertInstanceOf(Availability::class, $availability);
            $this->assertEquals('09:00', $availability->getTimeRange()->getStartTime()->format('H:i'));
            $this->assertEquals('17:00', $availability->getTimeRange()->getEndTime()->format('H:i'));
            $this->assertLessThan(6, $availability->getDate()->format('N')); // Not weekend
        }
    }

    public function testValidateTimeRangeSuccess(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+73 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::EMPLOYMENT_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        $this->repository->expects($this->once())
            ->method('findByUserAndDateRange')
            ->willReturn([]);

        $this->strategy->validate($availability);
        $this->addToAssertionCount(1); // No exception thrown
    }

    public function testValidateTimeRangeExceedingDailyLimit(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+73 hours');
        $endTime = $startTime->modify('+12 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::EMPLOYMENT_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        $this->expectException(InvalidAvailabilityException::class);
        $this->expectExceptionMessage('Daily working hours cannot exceed 8 hours for employment contract');

        $this->strategy->validate($availability);
    }

    public function testValidateWeeklyHoursSuccess(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+73 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::EMPLOYMENT_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        $this->repository->expects($this->once())
            ->method('findByUserAndDateRange')
            ->willReturn([]);

        $this->strategy->validate($availability);
        $this->addToAssertionCount(1); // No exception thrown
    }

    public function testValidateWeeklyHoursExceedingLimit(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+73 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::EMPLOYMENT_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        // Symulujemy, że użytkownik ma już zaplanowane 39 godzin w tym tygodniu
        $existingAvailability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::EMPLOYMENT_CONTRACT,
            new TimeRange(
                $startTime->modify('-1 day'),
                $startTime->modify('-1 day')->modify('+39 hours')
            ),
            $startTime->modify('-1 day')
        );

        $this->repository->expects($this->once())
            ->method('findByUserAndDateRange')
            ->willReturn([$existingAvailability]);

        $this->expectException(InvalidAvailabilityException::class);
        $this->expectExceptionMessage('Weekly working hours cannot exceed 40 hours for employment contract');

        $this->strategy->validate($availability);
    }

    public function testValidateNoticePeriodSuccess(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+73 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::EMPLOYMENT_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        $this->repository->expects($this->once())
            ->method('findByUserAndDateRange')
            ->willReturn([]);

        $this->strategy->validate($availability);
        $this->addToAssertionCount(1); // No exception thrown
    }

    public function testValidateNoticePeriodTooShort(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+1 hour');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::EMPLOYMENT_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        $this->expectException(InvalidAvailabilityException::class);
        $this->expectExceptionMessage('Availability must be submitted at least 72 hours in advance for employment contract');

        $this->strategy->validate($availability);
    }

    public function testValidateWeekendAvailabilityNotAllowed(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+73 hours');
        $endTime = $startTime->modify('+8 hours');
        
        // Upewniamy się, że data to weekend
        while ((int)$startTime->format('N') < 6) {
            $startTime = $startTime->modify('+1 day');
            $endTime = $endTime->modify('+1 day');
        }
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::EMPLOYMENT_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        $this->expectException(InvalidAvailabilityException::class);
        $this->expectExceptionMessage('Standard employment contract does not allow working on weekends');

        $this->strategy->validate($availability);
    }
} 