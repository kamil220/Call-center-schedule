<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service\LeaveType;

use App\Domain\WorkSchedule\Entity\LeaveRequest;
use DateTimeImmutable;

class SickLeaveStrategy extends AbstractLeaveTypeStrategy
{
    public function getType(): string
    {
        return 'sick_leave';
    }
    
    public function getLabel(): string
    {
        return 'Sick Leave';
    }
    
    public function requiresApproval(): bool
    {
        return false; // Automatic approval for sick leave
    }
    
    public function getMaxDuration(): int
    {
        return 14; // 14 days
    }
    
    public function getColor(): string
    {
        return '#FF9F9F'; // Light red
    }
    
    /**
     * Sick leave includes weekends in duration calculation
     */
    public function calculateDuration(DateTimeImmutable $startDate, DateTimeImmutable $endDate): int
    {
        // For sick leave, we count all calendar days
        $interval = $startDate->diff($endDate);
        return $interval->days + 1; // +1 to include both start and end days
    }
    
    /**
     * Additional methods specific to sick leave
     */
    public function requiresMedicalCertificate(int $duration): bool
    {
        // Medical certificate required for sick leave longer than 3 days
        return $duration > 3;
    }
} 