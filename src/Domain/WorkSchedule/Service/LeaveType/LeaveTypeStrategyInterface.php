<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service\LeaveType;

use App\Domain\WorkSchedule\Entity\LeaveRequest;
use App\Domain\WorkSchedule\Exception\InvalidLeaveRequestException;
use DateTimeImmutable;

interface LeaveTypeStrategyInterface
{
    /**
     * Get the leave type identifier
     */
    public function getType(): string;
    
    /**
     * Get the user-friendly label for this leave type
     */
    public function getLabel(): string;
    
    /**
     * Check if this leave type requires manager approval
     */
    public function requiresApproval(): bool;
    
    /**
     * Get the maximum duration allowed for this leave type (in days)
     */
    public function getMaxDuration(): int;
    
    /**
     * Validate a leave request for this leave type
     *
     * @throws InvalidLeaveRequestException if validation fails
     */
    public function validateRequest(
        LeaveRequest $request, 
        ?array $existingRequests = []
    ): void;
    
    /**
     * Calculate the number of working days between two dates
     * This can be customized per leave type (some might count weekends, some might not)
     */
    public function calculateDuration(
        DateTimeImmutable $startDate, 
        DateTimeImmutable $endDate
    ): int;
    
    /**
     * Check if this leave type applies/is available to the user based on criteria
     * For example, some types might be available only to employees with specific conditions
     */
    public function isApplicable(array $criteria = []): bool;
    
    /**
     * Get the CSS color used for displaying this leave type in UI
     */
    public function getColor(): string;
} 