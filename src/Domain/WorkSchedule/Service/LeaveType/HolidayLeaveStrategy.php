<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service\LeaveType;

use App\Domain\WorkSchedule\Entity\LeaveRequest;
use App\Domain\WorkSchedule\Exception\InvalidLeaveRequestException;
use DateTimeImmutable;

class HolidayLeaveStrategy extends AbstractLeaveTypeStrategy
{
    // Minimum days in advance for holiday request
    private const MIN_DAYS_IN_ADVANCE = 7;
    
    public function getType(): string
    {
        return 'holiday';
    }
    
    public function getLabel(): string
    {
        return 'Holiday';
    }
    
    public function requiresApproval(): bool
    {
        return true;
    }
    
    public function getMaxDuration(): int
    {
        return 26; // 26 days per year
    }
    
    public function getColor(): string
    {
        return '#AAD8FF'; // Light blue
    }
    
    /**
     * Additional validation specific to holidays
     */
    public function validateRequest(
        LeaveRequest $request, 
        ?array $existingRequests = []
    ): void {
        // First run the common validation
        parent::validateRequest($request, $existingRequests);
        
        // Holiday-specific validation - must be requested at least 7 days in advance
        $now = new DateTimeImmutable();
        $daysUntilStart = $now->diff($request->getStartDate())->days;
        
        if ($daysUntilStart < self::MIN_DAYS_IN_ADVANCE) {
            throw InvalidLeaveRequestException::tooEarlyRequest(self::MIN_DAYS_IN_ADVANCE);
        }
        
        // Check remaining holiday balance
        // This would typically check against a user's balance from another service
    }
    
    /**
     * Additional methods specific to holidays
     */
    public function calculateRemainingBalance(string $userId, int $year): int
    {
        // In a real implementation, this would retrieve the user's
        // remaining holiday allocation from a holiday balance service
        // For now, just return a placeholder value
        return 20;
    }
} 