<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Entity;

use App\Domain\User\Entity\User;
use App\Domain\WorkSchedule\ValueObject\LeaveType;
use App\Domain\WorkSchedule\ValueObject\LeaveStatus;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(name: 'work_schedule_leave_requests')]
class LeaveRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private UuidInterface $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', enumType: LeaveType::class)]
    private LeaveType $type;

    #[ORM\Column(type: 'string', enumType: LeaveStatus::class)]
    private LeaveStatus $status;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $startDate;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $endDate;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $reason;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $approver = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $approvalDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        UuidInterface $id,
        User $user,
        LeaveType $type,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?string $reason = null
    ) {
        $this->id = $id;
        $this->user = $user;
        $this->type = $type;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->reason = $reason;
        $this->status = $type === LeaveType::SICK_LEAVE ? LeaveStatus::APPROVED : LeaveStatus::PENDING;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): LeaveType
    {
        return $this->type;
    }

    public function getStatus(): LeaveStatus
    {
        return $this->status;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getApprover(): ?User
    {
        return $this->approver;
    }

    public function getApprovalDate(): ?DateTimeImmutable
    {
        return $this->approvalDate;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateDates(DateTimeImmutable $startDate, DateTimeImmutable $endDate): void
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateReason(string $reason): void
    {
        $this->reason = $reason;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function approve(User $approver, ?string $comments = null): void
    {
        if ($this->status !== LeaveStatus::PENDING) {
            throw new \DomainException('Only pending leave requests can be approved');
        }

        $this->status = LeaveStatus::APPROVED;
        $this->approver = $approver;
        $this->approvalDate = new DateTimeImmutable();
        $this->comments = $comments;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function reject(User $approver, string $comments): void
    {
        if ($this->status !== LeaveStatus::PENDING) {
            throw new \DomainException('Only pending leave requests can be rejected');
        }

        $this->status = LeaveStatus::REJECTED;
        $this->approver = $approver;
        $this->approvalDate = new DateTimeImmutable();
        $this->comments = $comments;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function cancel(): void
    {
        if ($this->status === LeaveStatus::APPROVED && $this->startDate <= new DateTimeImmutable()) {
            throw new \DomainException('Cannot cancel leave that has already started or completed');
        }

        if ($this->status === LeaveStatus::CANCELLED) {
            throw new \DomainException('Leave request already cancelled');
        }

        $this->status = LeaveStatus::CANCELLED;
        $this->updatedAt = new DateTimeImmutable();
    }
} 