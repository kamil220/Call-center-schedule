<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Repository;

use App\Domain\Calendar\Entity\CalendarDate;

interface CalendarDateRepositoryInterface
{
    /**
     * @return array<CalendarDate>
     */
    public function findByYear(int $year): array;

    /**
     * @return array<CalendarDate>
     */
    public function findByYearAndMonth(int $year, int $month): array;
} 