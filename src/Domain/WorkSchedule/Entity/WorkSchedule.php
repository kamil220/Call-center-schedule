<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Entity;

use App\Domain\Employee\Entity\EmployeeSkillPath;
use App\Domain\User\Entity\User;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;

#[ORM\Entity]
#[ORM\Table(name: 'work_schedule_entries')]
#[ORM\UniqueConstraint(
    name: 'unique_schedule_entry',
    columns: ['user_id', 'skill_path_id', 'date', 'time_range_start', 'time_range_end']
)]
class WorkSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[OA\Property(type: 'integer', format: 'int64')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: EmployeeSkillPath::class)]
    #[ORM\JoinColumn(nullable: false)]
    private EmployeeSkillPath $skillPath;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $date;

    #[ORM\Embedded(class: TimeRange::class)]
    private TimeRange $timeRange;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        User $user,
        EmployeeSkillPath $skillPath,
        DateTimeImmutable $date,
        TimeRange $timeRange,
        ?string $notes = null
    ) {
        $this->user = $user;
        $this->skillPath = $skillPath;
        $this->date = $date;
        $this->timeRange = $timeRange;
        $this->notes = $notes;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSkillPath(): EmployeeSkillPath
    {
        return $this->skillPath;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getTimeRange(): TimeRange
    {
        return $this->timeRange;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(
        EmployeeSkillPath $skillPath,
        TimeRange $timeRange,
        ?string $notes = null
    ): void {
        $this->skillPath = $skillPath;
        $this->timeRange = $timeRange;
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable();
    }
} 