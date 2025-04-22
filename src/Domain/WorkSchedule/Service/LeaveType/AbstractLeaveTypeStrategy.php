<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service\LeaveType;

use App\Domain\WorkSchedule\Entity\LeaveRequest;
use App\Domain\WorkSchedule\Exception\InvalidLeaveRequestException;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

abstract class AbstractLeaveTypeStrategy implements LeaveTypeStrategyInterface
{
    /**
     * Common validation logic for all leave types
     */
    public function validateRequest(
        LeaveRequest $request, 
        ?array $existingRequests = []
    ): void {
        $startDate = $request->getStartDate();
        $endDate = $request->getEndDate();
        
        // Validate date range
        if ($startDate > $endDate) {
            throw InvalidLeaveRequestException::invalidDateRange();
        }
        
        // Validate end date is not in the past
        if ($endDate < new DateTimeImmutable('today')) {
            throw InvalidLeaveRequestException::endDateInPast();
        }
        
        // Validate duration does not exceed maximum
        $duration = $this->calculateDuration($startDate, $endDate);
        if ($duration > $this->getMaxDuration()) {
            throw InvalidLeaveRequestException::exceededMaxDuration($this->getMaxDuration());
        }
        
        // Check for overlapping leave requests
        if (!empty($existingRequests)) {
            foreach ($existingRequests as $existingRequest) {
                if ($this->datesOverlap(
                    $startDate, 
                    $endDate, 
                    $existingRequest->getStartDate(), 
                    $existingRequest->getEndDate()
                )) {
                    throw InvalidLeaveRequestException::overlappingRequest();
                }
            }
        }
        
        // Additional validation can be added in specific implementations
    }
    
    /**
     * Default implementation to calculate working days between two dates
     * Excludes weekends by default
     */
    public function calculateDuration(
        DateTimeImmutable $startDate, 
        DateTimeImmutable $endDate
    ): int {
        $workingDays = 0;
        $currentDate = clone $startDate;
        
        while ($currentDate <= $endDate) {
            $dayOfWeek = (int) $currentDate->format('w');
            
            // Skip weekends (0 = Sunday, 6 = Saturday)
            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                $workingDays++;
            }
            
            $currentDate = $currentDate->modify('+1 day');
        }
        
        return $workingDays;
    }
    
    /**
     * Default implementation for all leave types
     * Can be overridden by specific implementations
     */
    public function isApplicable(array $criteria = []): bool
    {
        // By default, all leave types are applicable
        return true;
    }
    
    /**
     * Helper method to check if two date ranges overlap
     */
    protected function datesOverlap(
        DateTimeInterface $start1, 
        DateTimeInterface $end1, 
        DateTimeInterface $start2, 
        DateTimeInterface $end2
    ): bool {
        return $start1 <= $end2 && $end1 >= $start2;
    }
} 