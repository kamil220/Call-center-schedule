<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\ValueObject;

enum LeaveStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    
    public function isActive(): bool
    {
        return $this === self::APPROVED;
    }
    
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
    
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'orange',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::CANCELLED => 'gray',
        };
    }
} 