<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\ValueObject;

/**
 * Leave type enum - simple value object for database persistence.
 * For business logic, use the LeaveTypeStrategy implementations.
 */
enum LeaveType: string
{
    case SICK_LEAVE = 'sick_leave';
    case HOLIDAY = 'holiday';
    case PERSONAL_LEAVE = 'personal_leave';
    case PATERNITY_LEAVE = 'paternity_leave';
    case MATERNITY_LEAVE = 'maternity_leave';
} 