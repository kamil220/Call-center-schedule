<?php

declare(strict_types=1);

namespace App\Domain\Calendar\Service\HolidayProvider;

use DateTimeImmutable;

final class PolishHolidayProvider implements HolidayProviderInterface
{
    private const COUNTRY = 'PL';

    private const FIXED_HOLIDAYS = [
        '01-01' => 'New Year',
        '01-06' => 'Epiphany',
        '05-01' => 'Labor Day',
        '05-03' => 'Constitution Day',
        '08-15' => 'Assumption of the Blessed Virgin Mary',
        '11-01' => 'All Saints Day',
        '11-11' => 'Independence Day',
        '12-25' => 'Christmas Day',
        '12-26' => 'Second Day of Christmas'
    ];

    public function supports(string $country): bool
    {
        return $country === self::COUNTRY;
    }

    public function getHolidaysForYear(int $year): array
    {
        $holidays = [];

        // Add fixed holidays
        foreach (self::FIXED_HOLIDAYS as $date => $description) {
            $holidays[] = [
                'date' => sprintf('%d-%s', $year, $date),
                'type' => 'fixed',
                'description' => $description
            ];
        }

        // Calculate Easter and related holidays
        $easterDate = $this->calculateEasterDate($year);
        
        // Easter Sunday
        $holidays[] = [
            'date' => $easterDate->format('Y-m-d'),
            'type' => 'movable',
            'description' => 'Easter Sunday'
        ];

        // Easter Monday (Easter Sunday + 1 day)
        $easterMonday = $easterDate->modify('+1 day');
        $holidays[] = [
            'date' => $easterMonday->format('Y-m-d'),
            'type' => 'movable',
            'description' => 'Easter Monday'
        ];

        // Pentecost (Easter Sunday + 49 days)
        $pentecost = $easterDate->modify('+49 days');
        $holidays[] = [
            'date' => $pentecost->format('Y-m-d'),
            'type' => 'movable',
            'description' => 'Pentecost'
        ];

        // Corpus Christi (Easter Sunday + 60 days)
        $corpusChristi = $easterDate->modify('+60 days');
        $holidays[] = [
            'date' => $corpusChristi->format('Y-m-d'),
            'type' => 'movable',
            'description' => 'Corpus Christi'
        ];

        // Sort holidays by date
        usort($holidays, fn($a, $b) => $a['date'] <=> $b['date']);

        return $holidays;
    }

    private function calculateEasterDate(int $year): DateTimeImmutable
    {
        $a = $year % 19;
        $b = (int)($year / 100);
        $c = $year % 100;
        $d = (int)($b / 4);
        $e = $b % 4;
        $f = (int)(($b + 8) / 25);
        $g = (int)(($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = (int)($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = (int)(($a + 11 * $h + 22 * $l) / 451);
        $month = (int)(($h + $l - 7 * $m + 114) / 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new DateTimeImmutable(sprintf('%d-%02d-%02d', $year, $month, $day));
    }
} 