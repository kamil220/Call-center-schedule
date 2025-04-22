<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service\LeaveType;

use App\Domain\WorkSchedule\Entity\LeaveRequest;
use App\Domain\WorkSchedule\Exception\InvalidLeaveRequestException;
use DateTimeImmutable;

class PaternityLeaveStrategy extends AbstractLeaveTypeStrategy
{
    private const MIN_NOTICE_DAYS = 14;
    
    public function getType(): string
    {
        return 'paternity_leave';
    }
    
    public function getLabel(): string
    {
        return 'Paternity Leave';
    }
    
    public function requiresApproval(): bool
    {
        return true;
    }
    
    public function getMaxDuration(): int
    {
        return 14; // 2 weeks
    }
    
    public function getColor(): string
    {
        return '#B4C7E7'; // Light blue-gray
    }
    
    /**
     * Additional validation specific to paternity leave
     */
    public function validateRequest(
        LeaveRequest $request, 
        ?array $existingRequests = []
    ): void {
        // First run the common validation
        parent::validateRequest($request, $existingRequests);
        
        // Paternity leave should be requested at least 2 weeks in advance
        $now = new DateTimeImmutable();
        $daysUntilStart = $now->diff($request->getStartDate())->days;
        
        if ($daysUntilStart < self::MIN_NOTICE_DAYS) {
            throw InvalidLeaveRequestException::tooEarlyRequest(self::MIN_NOTICE_DAYS);
        }
    }
    
    /**
     * Paternity leave uses calendar days (including weekends)
     */
    public function calculateDuration(DateTimeImmutable $startDate, DateTimeImmutable $endDate): int
    {
        $interval = $startDate->diff($endDate);
        return $interval->days + 1; // +1 to include both start and end days
    }
    
    /**
     * Check if this user is eligible for paternity leave
     */
    public function isApplicable(array $criteria = []): bool
    {
        // In a real implementation, this might check:
        // - Employee status
        // - Child's birth date (must be taken within a specific time after birth)
        
        // For now, always return true (eligibility determined elsewhere)
        return true;
    }
    
    /**
     * Check if the leave is being taken within the eligible timeframe
     */
    public function isWithinEligibleTimeframe(DateTimeImmutable $birthDate, DateTimeImmutable $leaveStartDate): bool
    {
        // Paternity leave often must be taken within a specific period after birth
        // E.g., within the first 8 weeks after birth
        $maxWeeks = 8;
        $interval = $birthDate->diff($leaveStartDate);
        
        // Return true if leave starts within eligible period
        return $interval->days <= ($maxWeeks * 7);
    }
} 