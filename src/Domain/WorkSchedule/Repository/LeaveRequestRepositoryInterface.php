<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Repository;

use App\Domain\User\Entity\User;
use App\Domain\WorkSchedule\Entity\LeaveRequest;
use App\Domain\WorkSchedule\ValueObject\LeaveStatus;
use App\Domain\WorkSchedule\ValueObject\LeaveType;
use DateTimeImmutable;

interface LeaveRequestRepositoryInterface
{
    public function save(LeaveRequest $leaveRequest): void;
    
    public function remove(LeaveRequest $leaveRequest): void;
    
    public function findById(string $id): ?LeaveRequest;
    
    /**
     * Find all leave requests for a specific user
     */
    public function findByUser(User $user): array;
    
    /**
     * Find leave requests for a user within a date range
     */
    public function findByUserAndDateRange(
        User $user,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): array;
    
    /**
     * Find leave requests by status 
     */
    public function findByStatus(LeaveStatus $status): array;
    
    /**
     * Find leave requests by type
     */
    public function findByType(LeaveType $type): array;
    
    /**
     * Find pending leave requests for approval
     */
    public function findPendingRequestsForManager(User $manager): array;
    
    /**
     * Find leave requests (e.g., Approved, Pending) for a user that intersect with a given date range.
     *
     * @return LeaveRequest[]
     */
    public function findActiveByUserIntersectingDateRange(User $user, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;
    
    /**
     * Find overlapping leave requests
     */
    public function findOverlappingRequests(
        User $user,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?string $excludeId = null
    ): array;
    
    /**
     * Find all leave requests with pagination
     */
    public function findAll(int $page = 1, int $limit = 20): array;
    
    /**
     * Count total number of leave requests
     */
    public function countAll(): int;
} 