<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

enum EmploymentType: string
{
    case EMPLOYMENT_CONTRACT = 'employment_contract';
    case CIVIL_CONTRACT = 'civil_contract';
    case CONTRACTOR = 'contractor';

    public function getMaxDailyHours(): int
    {
        return match($this) {
            self::EMPLOYMENT_CONTRACT => 8,
            self::CIVIL_CONTRACT => 8,
            self::CONTRACTOR => 8
        };
    }

    public function getMaxWeeklyHours(): int
    {
        return match($this) {
            self::EMPLOYMENT_CONTRACT => 40,
            self::CIVIL_CONTRACT => 40,
            self::CONTRACTOR => 40
        };
    }

    public function requiresRecurringSchedule(): bool
    {
        return match($this) {
            self::EMPLOYMENT_CONTRACT => true,
            self::CIVIL_CONTRACT => true,
            self::CONTRACTOR => true
        };
    }

    public function getMinimumNoticePeriodHours(): int
    {
        return match($this) {
            self::EMPLOYMENT_CONTRACT => 72, // 3 days
            self::CIVIL_CONTRACT => 48, // 2 days
            self::CONTRACTOR => 48 // 2 days
        };
    }
} 