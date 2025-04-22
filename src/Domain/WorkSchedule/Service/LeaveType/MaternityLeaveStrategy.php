<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service\LeaveType;

use App\Domain\WorkSchedule\Entity\LeaveRequest;
use App\Domain\WorkSchedule\Exception\InvalidLeaveRequestException;
use DateTimeImmutable;

class MaternityLeaveStrategy extends AbstractLeaveTypeStrategy
{
    // Minimum notice for maternity leave in days
    private const MIN_NOTICE_DAYS = 14;
    
    public function getType(): string
    {
        return 'maternity_leave';
    }
    
    public function getLabel(): string
    {
        return 'Maternity Leave';
    }
    
    public function requiresApproval(): bool
    {
        return true;
    }
    
    public function getMaxDuration(): int
    {
        return 26 * 7; // 26 weeks (182 days)
    }
    
    public function getColor(): string
    {
        return '#E6B8AF'; // Light pink
    }
    
    /**
     * Additional validation specific to maternity leave
     */
    public function validateRequest(
        LeaveRequest $request, 
        ?array $existingRequests = []
    ): void {
        // First run the common validation
        parent::validateRequest($request, $existingRequests);
        
        // Maternity leave should be requested at least 2 weeks in advance
        $now = new DateTimeImmutable();
        $daysUntilStart = $now->diff($request->getStartDate())->days;
        
        if ($daysUntilStart < self::MIN_NOTICE_DAYS) {
            throw InvalidLeaveRequestException::tooEarlyRequest(self::MIN_NOTICE_DAYS);
        }
        
        // Additional validation could check for documentation
    }
    
    /**
     * Maternity leave uses calendar days (including weekends)
     */
    public function calculateDuration(DateTimeImmutable $startDate, DateTimeImmutable $endDate): int
    {
        $interval = $startDate->diff($endDate);
        return $interval->days + 1; // +1 to include both start and end days
    }
    
    /**
     * Check if this user is eligible for maternity leave
     */
    public function isApplicable(array $criteria = []): bool
    {
        // In a real implementation, this would check user eligibility based on:
        // - Employee status (must be permanent employee typically)
        // - Employment duration (often minimum length of service)
        // - Medical documentation
        
        // For now, always return true (eligibility determined elsewhere)
        return true;
    }
    
    /**
     * Check if required documentation has been provided
     */
    public function hasRequiredDocumentation(array $documents): bool
    {
        // Check if medical certificate is provided
        return isset($documents['medical_certificate']);
    }
} 