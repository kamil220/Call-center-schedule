<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Entity;

use App\Domain\Calendar\ValueObject\DayType;
use DateTimeImmutable;

class CalendarDate
{
    private DateTimeImmutable $date;
    private DayType $type;
    private string $description;

    public function __construct(
        DateTimeImmutable $date,
        DayType $type,
        string $description
    ) {
        $this->date = $date;
        $this->type = $type;
        $this->description = $description;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getType(): DayType
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
} 