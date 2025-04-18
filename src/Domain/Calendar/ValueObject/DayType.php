<?php

declare(strict_types=1);

namespace App\Domain\Calendar\ValueObject;

enum DayType: string
{
    case WORKDAY = 'workday';
    case HOLIDAY = 'holiday';
} 