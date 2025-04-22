<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Exception;

use DomainException;

class InvalidLeaveRequestException extends DomainException
{
    public static function invalidDateRange(): self
    {
        return new self('Start date must be before or equal to end date');
    }

    public static function endDateInPast(): self
    {
        return new self('End date cannot be in the past');
    }

    public static function exceededMaxDuration(int $maxDays): self
    {
        return new self(sprintf('Leave request exceeds maximum allowed duration of %d days', $maxDays));
    }

    public static function overlappingRequest(): self
    {
        return new self('There is an overlapping leave request for this period');
    }

    public static function insufficientLeaveBalance(int $available): self
    {
        return new self(sprintf('Insufficient leave balance. Available: %d days', $available));
    }

    public static function tooEarlyRequest(int $minDaysInAdvance): self
    {
        return new self(sprintf('Leave requests must be submitted at least %d days in advance', $minDaysInAdvance));
    }
} 