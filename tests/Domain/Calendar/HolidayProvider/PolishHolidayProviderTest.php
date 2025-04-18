<?php

declare(strict_types=1);

namespace App\Tests\Domain\Calendar\HolidayProvider;

use App\Domain\Calendar\HolidayProvider\PolishHolidayProvider;
use App\Domain\Calendar\DayType;
use PHPUnit\Framework\TestCase;

final class PolishHolidayProviderTest extends TestCase
{
    private PolishHolidayProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new PolishHolidayProvider();
    }

    public function testSupportsReturnsCorrectValue(): void
    {
        self::assertTrue($this->provider->supports('PL'));
        self::assertTrue($this->provider->supports('pl'));
        self::assertFalse($this->provider->supports('DE'));
    }

    public function testGetHolidaysForYearReturnsCorrectNumberOfHolidays(): void
    {
        $holidays = $this->provider->getHolidaysForYear(2024);
        
        // 9 fixed holidays + 4 movable holidays (Easter, Easter Monday, Corpus Christi, Pentecost)
        self::assertCount(13, $holidays);
    }

    public function testGetHolidaysForYearReturnsCorrectFixedHolidays(): void
    {
        $holidays = $this->provider->getHolidaysForYear(2024);
        
        $expectedDates = [
            '2024-01-01' => 'New Year',
            '2024-01-06' => 'Epiphany',
            '2024-05-01' => 'Labor Day',
            '2024-05-03' => 'Constitution Day',
            '2024-08-15' => 'Assumption of the Blessed Virgin Mary',
            '2024-11-01' => 'All Saints Day',
            '2024-11-11' => 'Independence Day',
            '2024-12-25' => 'Christmas Day',
            '2024-12-26' => 'Second Day of Christmas',
        ];

        foreach ($expectedDates as $date => $description) {
            $found = false;
            foreach ($holidays as $holiday) {
                if ($holiday->getDate()->format('Y-m-d') === $date 
                    && $holiday->getDescription() === $description
                    && $holiday->getType() === DayType::PUBLIC_HOLIDAY
                ) {
                    $found = true;
                    break;
                }
            }
            self::assertTrue($found, "Holiday not found: $description on $date");
        }
    }

    public function testGetHolidaysForYearReturnsCorrectEasterDate2024(): void
    {
        $holidays = $this->provider->getHolidaysForYear(2024);
        
        $expectedDates = [
            '2024-03-31' => 'Easter Sunday',
            '2024-04-01' => 'Easter Monday',
            '2024-05-19' => 'Pentecost Sunday',
            '2024-05-30' => 'Corpus Christi',
        ];

        foreach ($expectedDates as $date => $description) {
            $found = false;
            foreach ($holidays as $holiday) {
                if ($holiday->getDate()->format('Y-m-d') === $date 
                    && $holiday->getDescription() === $description
                    && $holiday->getType() === DayType::PUBLIC_HOLIDAY
                ) {
                    $found = true;
                    break;
                }
            }
            self::assertTrue($found, "Movable holiday not found: $description on $date");
        }
    }

    public function testGetHolidaysForYearReturnsCorrectEasterDate2025(): void
    {
        $holidays = $this->provider->getHolidaysForYear(2025);
        
        $expectedDates = [
            '2025-04-20' => 'Easter Sunday',
            '2025-04-21' => 'Easter Monday',
            '2025-06-08' => 'Pentecost Sunday',
            '2025-06-19' => 'Corpus Christi',
        ];

        foreach ($expectedDates as $date => $description) {
            $found = false;
            foreach ($holidays as $holiday) {
                if ($holiday->getDate()->format('Y-m-d') === $date 
                    && $holiday->getDescription() === $description
                    && $holiday->getType() === DayType::PUBLIC_HOLIDAY
                ) {
                    $found = true;
                    break;
                }
            }
            self::assertTrue($found, "Movable holiday not found: $description on $date");
        }
    }
} 