<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Entity;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\EmploymentType;
use App\Domain\WorkSchedule\ValueObject\RecurrencePattern;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use OpenApi\Attributes as OA;

#[ORM\Entity]
#[ORM\Table(name: 'work_schedule_availabilities')]
#[OA\Schema(
    schema: 'Availability',
    description: 'Availability model'
)]
class Availability
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[OA\Property(type: 'string', format: 'uuid')]
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[OA\Property(type: 'string', format: 'uuid', description: 'ID of the user this availability belongs to')]
    private User $user;

    #[ORM\Column(type: 'string', enumType: EmploymentType::class)]
    #[OA\Property(type: 'string', enum: ['FULL_TIME', 'PART_TIME', 'CONTRACTOR'])]
    private EmploymentType $employmentType;

    #[ORM\Embedded(class: TimeRange::class)]
    #[OA\Property(
        type: 'object',
        properties: [
            new OA\Property(property: 'startTime', type: 'string', format: 'time'),
            new OA\Property(property: 'endTime', type: 'string', format: 'time')
        ]
    )]
    private TimeRange $timeRange;

    #[ORM\Column(type: 'date_immutable')]
    #[OA\Property(type: 'string', format: 'date')]
    private DateTimeImmutable $date;

    #[ORM\Column(type: 'json', nullable: true)]
    #[OA\Property(
        type: 'object',
        nullable: true,
        properties: [
            new OA\Property(property: 'frequency', type: 'string', enum: ['DAILY', 'WEEKLY', 'MONTHLY']),
            new OA\Property(property: 'interval', type: 'integer', minimum: 1),
            new OA\Property(property: 'daysOfWeek', type: 'array', items: new OA\Items(type: 'integer', minimum: 0, maximum: 6)),
            new OA\Property(property: 'daysOfMonth', type: 'array', items: new OA\Items(type: 'integer', minimum: 1, maximum: 31)),
            new OA\Property(property: 'excludeDates', type: 'array', items: new OA\Items(type: 'string', format: 'date')),
            new OA\Property(property: 'until', type: 'string', format: 'date', nullable: true)
        ]
    )]
    private ?array $recurrencePatternData = null;

    private ?RecurrencePattern $recurrencePattern = null;

    public function __construct(
        UuidInterface $id,
        User $user,
        EmploymentType $employmentType,
        TimeRange $timeRange,
        DateTimeImmutable $date,
        ?array $recurrencePatternData = null
    ) {
        $this->id = $id;
        $this->user = $user;
        $this->employmentType = $employmentType;
        $this->timeRange = $timeRange;
        $this->date = $date;
        $this->setRecurrencePattern($recurrencePatternData);
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEmploymentType(): EmploymentType
    {
        return $this->employmentType;
    }

    public function getTimeRange(): TimeRange
    {
        return $this->timeRange;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getRecurrencePattern(): ?RecurrencePattern
    {
        if ($this->recurrencePattern === null && $this->recurrencePatternData !== null) {
            $this->recurrencePattern = RecurrencePattern::fromArray($this->recurrencePatternData);
        }

        return $this->recurrencePattern;
    }

    public function update(TimeRange $timeRange, DateTimeImmutable $date, ?array $recurrencePatternData = null): void
    {
        $this->timeRange = $timeRange;
        $this->date = $date;
        $this->setRecurrencePattern($recurrencePatternData);
    }

    private function setRecurrencePattern(?array $recurrencePatternData): void
    {
        $this->recurrencePatternData = $recurrencePatternData;
        $this->recurrencePattern = $recurrencePatternData !== null
            ? RecurrencePattern::fromArray($recurrencePatternData)
            : null;
    }
} 