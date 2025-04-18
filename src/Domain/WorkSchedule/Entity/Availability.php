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

#[ORM\Entity]
#[ORM\Table(name: 'work_schedule_availabilities')]
class Availability
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', enumType: EmploymentType::class)]
    private EmploymentType $employmentType;

    #[ORM\Embedded(class: TimeRange::class)]
    private TimeRange $timeRange;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $date;

    #[ORM\Column(type: 'json', nullable: true)]
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