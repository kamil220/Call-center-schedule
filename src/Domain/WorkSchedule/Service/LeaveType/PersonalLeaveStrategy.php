<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service\LeaveType;

use App\Domain\WorkSchedule\Entity\LeaveRequest;
use DateTimeImmutable;

class PersonalLeaveStrategy extends AbstractLeaveTypeStrategy
{
    public function getType(): string
    {
        return 'personal_leave';
    }
    
    public function getLabel(): string
    {
        return 'Personal Leave';
    }
    
    public function requiresApproval(): bool
    {
        return true;
    }
    
    public function getMaxDuration(): int
    {
        return 4; // 4 days per year
    }
    
    public function getColor(): string
    {
        return '#FFD966'; // Light gold
    }
    
    /**
     * For personal leave, we need to check how many days have been taken this year
     */
    public function validateRequest(
        LeaveRequest $request, 
        ?array $existingRequests = []
    ): void {
        // First run the common validation
        parent::validateRequest($request, $existingRequests);
        
        // Calculate how many personal leave days have been used this year
        $usedDays = $this->calculateUsedDays($existingRequests);
        $requestedDays = $this->calculateDuration($request->getStartDate(), $request->getEndDate());
        
        // Check if they have enough days remaining
        $remainingDays = $this->getMaxDuration() - $usedDays;
        if ($requestedDays > $remainingDays) {
            throw \App\Domain\WorkSchedule\Exception\InvalidLeaveRequestException::insufficientLeaveBalance($remainingDays);
        }
    }
    
    /**
     * Calculate how many personal leave days have been used this year
     */
    private function calculateUsedDays(array $existingRequests): int
    {
        $usedDays = 0;
        $currentYear = (int) (new DateTimeImmutable())->format('Y');
        
        foreach ($existingRequests as $request) {
            // Only count approved requests from the current year
            if ($request->getStatus()->isActive() && 
                (int) $request->getStartDate()->format('Y') === $currentYear) {
                $usedDays += $this->calculateDuration($request->getStartDate(), $request->getEndDate());
            }
        }
        
        return $usedDays;
    }
} 