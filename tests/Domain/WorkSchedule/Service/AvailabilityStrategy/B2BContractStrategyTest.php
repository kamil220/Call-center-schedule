<?php

declare(strict_types=1);

namespace App\Tests\Domain\WorkSchedule\Service\AvailabilityStrategy;

use App\Domain\User\ValueObject\EmploymentType;
use App\Domain\WorkSchedule\Entity\Availability;
use App\Domain\WorkSchedule\Exception\InvalidAvailabilityException;
use App\Domain\WorkSchedule\Repository\AvailabilityRepositoryInterface;
use App\Domain\WorkSchedule\Service\AvailabilityStrategy\B2BContractStrategy;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use App\Domain\User\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class B2BContractStrategyTest extends TestCase
{
    private B2BContractStrategy $strategy;
    private MockObject&AvailabilityRepositoryInterface $repository;
    private User $user;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AvailabilityRepositoryInterface::class);
        $this->strategy = new B2BContractStrategy($this->repository);
        $this->user = $this->createMock(User::class);
    }

    public function testSupportsOnlyB2BContract(): void
    {
        $this->assertTrue($this->strategy->supports(EmploymentType::B2B_CONTRACT));
        $this->assertFalse($this->strategy->supports(EmploymentType::EMPLOYMENT_CONTRACT));
        $this->assertFalse($this->strategy->supports(EmploymentType::CIVIL_CONTRACT));
    }

    public function testGenerateAvailabilitiesReturnsEmptyArray(): void
    {
        $startDate = new DateTimeImmutable();
        $endDate = $startDate->modify('+1 week');

        $availabilities = $this->strategy->generateAvailabilities($startDate, $endDate, []);
        
        $this->assertEmpty($availabilities);
    }

    public function testValidateTimeRangeSuccess(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+25 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::B2B_CONTRACT,
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
        $startTime = (new DateTimeImmutable())->modify('+25 hours');
        $endTime = $startTime->modify('+25 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::B2B_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        $this->expectException(InvalidAvailabilityException::class);
        $this->expectExceptionMessage('Time range cannot exceed 24 hours for B2B contract');

        $this->strategy->validate($availability);
    }

    public function testValidateWeeklyHoursSuccess(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+25 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::B2B_CONTRACT,
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
        $startTime = (new DateTimeImmutable())->modify('+25 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::B2B_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        // Symulujemy, że użytkownik ma już zaplanowane 167 godzin w tym tygodniu
        $existingAvailability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::B2B_CONTRACT,
            new TimeRange(
                $startTime->modify('-1 day'),
                $startTime->modify('-1 day')->modify('+167 hours')
            ),
            $startTime->modify('-1 day')
        );

        $this->repository->expects($this->once())
            ->method('findByUserAndDateRange')
            ->willReturn([$existingAvailability]);

        $this->expectException(InvalidAvailabilityException::class);
        $this->expectExceptionMessage('Weekly working hours cannot exceed 168 hours for B2B contract');

        $this->strategy->validate($availability);
    }

    public function testValidateNoticePeriodSuccess(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+25 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::B2B_CONTRACT,
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
        $startTime = new DateTimeImmutable('+1 hour');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::B2B_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        $this->expectException(InvalidAvailabilityException::class);
        $this->expectExceptionMessage('Availability must be submitted at least 24 hours in advance for B2B contract');

        $this->strategy->validate($availability);
    }
} 