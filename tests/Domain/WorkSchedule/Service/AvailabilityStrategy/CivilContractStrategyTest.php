<?php

declare(strict_types=1);

namespace App\Tests\Domain\WorkSchedule\Service\AvailabilityStrategy;

use App\Domain\User\ValueObject\EmploymentType;
use App\Domain\WorkSchedule\Entity\Availability;
use App\Domain\WorkSchedule\Exception\InvalidAvailabilityException;
use App\Domain\WorkSchedule\Repository\AvailabilityRepositoryInterface;
use App\Domain\WorkSchedule\Service\AvailabilityStrategy\CivilContractStrategy;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use App\Domain\User\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class CivilContractStrategyTest extends TestCase
{
    private CivilContractStrategy $strategy;
    private MockObject&AvailabilityRepositoryInterface $repository;
    private User $user;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AvailabilityRepositoryInterface::class);
        $this->strategy = new CivilContractStrategy($this->repository);
        $this->user = $this->createMock(User::class);
    }

    public function testSupportsOnlyCivilContract(): void
    {
        $this->assertTrue($this->strategy->supports(EmploymentType::CIVIL_CONTRACT));
        $this->assertFalse($this->strategy->supports(EmploymentType::EMPLOYMENT_CONTRACT));
        $this->assertFalse($this->strategy->supports(EmploymentType::B2B_CONTRACT));
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
        $startTime = (new DateTimeImmutable())->modify('+49 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::CIVIL_CONTRACT,
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
        $startTime = (new DateTimeImmutable())->modify('+49 hours');
        $endTime = $startTime->modify('+13 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::CIVIL_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        $this->expectException(InvalidAvailabilityException::class);
        $this->expectExceptionMessage('Daily working hours cannot exceed 12 hours for civil contract');

        $this->strategy->validate($availability);
    }

    public function testValidateWeeklyHoursSuccess(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+49 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::CIVIL_CONTRACT,
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
        $startTime = (new DateTimeImmutable())->modify('+49 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::CIVIL_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        // Symulujemy, że użytkownik ma już zaplanowane 59 godzin w tym tygodniu
        $existingAvailability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::CIVIL_CONTRACT,
            new TimeRange(
                $startTime->modify('-1 day'),
                $startTime->modify('-1 day')->modify('+59 hours')
            ),
            $startTime->modify('-1 day')
        );

        $this->repository->expects($this->once())
            ->method('findByUserAndDateRange')
            ->willReturn([$existingAvailability]);

        $this->expectException(InvalidAvailabilityException::class);
        $this->expectExceptionMessage('Weekly working hours cannot exceed 60 hours for civil contract');

        $this->strategy->validate($availability);
    }

    public function testValidateNoticePeriodSuccess(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+49 hours');
        $endTime = $startTime->modify('+8 hours');
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::CIVIL_CONTRACT,
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
            EmploymentType::CIVIL_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        $this->expectException(InvalidAvailabilityException::class);
        $this->expectExceptionMessage('Availability must be submitted at least 48 hours in advance for civil contract');

        $this->strategy->validate($availability);
    }

    public function testValidateWeekendAvailabilityAllowed(): void
    {
        $startTime = (new DateTimeImmutable())->modify('+49 hours');
        $endTime = $startTime->modify('+8 hours');
        
        // Upewniamy się, że data to weekend
        while ((int)$startTime->format('N') < 6) {
            $startTime = $startTime->modify('+1 day');
            $endTime = $endTime->modify('+1 day');
        }
        
        $availability = new Availability(
            Uuid::uuid4(),
            $this->user,
            EmploymentType::CIVIL_CONTRACT,
            new TimeRange($startTime, $endTime),
            $startTime
        );

        $this->repository->expects($this->once())
            ->method('findByUserAndDateRange')
            ->willReturn([]);

        $this->strategy->validate($availability);
        $this->addToAssertionCount(1); // No exception thrown - weekend work is allowed
    }
} 