<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Service\HolidayProvider;

interface HolidayProviderInterface
{
    /**
     * @return array<array{date: string, type: string, description: string}>
     */
    public function getHolidaysForYear(int $year): array;
    
    public function supports(string $country): bool;
} 