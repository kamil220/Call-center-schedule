<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\ValueObject;

use App\Domain\WorkSchedule\Exception\InvalidRecurrencePatternException;
use DateTimeImmutable;
use JsonSerializable;

final class RecurrencePattern implements JsonSerializable
{
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';

    public const VALID_FREQUENCIES = [
        self::FREQUENCY_DAILY,
        self::FREQUENCY_WEEKLY,
        self::FREQUENCY_MONTHLY,
    ];

    private string $frequency;
    private int $interval;
    private ?array $daysOfWeek;
    private ?array $daysOfMonth;
    private array $excludeDates;
    private DateTimeImmutable $until;

    /**
     * @param string $frequency
     * @param int $interval
     * @param int[]|null $daysOfWeek Numbers from 1 (Monday) to 7 (Sunday)
     * @param int[]|null $daysOfMonth Numbers from 1 to 31
     * @param string[] $excludeDates Dates in Y-m-d format
     * @param string $until Date in Y-m-d format
     */
    public function __construct(
        string $frequency,
        int $interval,
        ?array $daysOfWeek = null,
        ?array $daysOfMonth = null,
        array $excludeDates = [],
        string $until
    ) {
        $this->validateFrequency($frequency);
        $this->validateInterval($interval);
        $this->validateDaysOfWeek($daysOfWeek);
        $this->validateDaysOfMonth($daysOfMonth);
        $this->validateExcludeDates($excludeDates);
        $this->validateUntil($until);

        $this->frequency = $frequency;
        $this->interval = $interval;
        $this->daysOfWeek = $daysOfWeek;
        $this->daysOfMonth = $daysOfMonth;
        $this->excludeDates = array_map(
            fn(string $date) => new DateTimeImmutable($date),
            $excludeDates
        );
        $this->until = new DateTimeImmutable($until);
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['frequency'], $data['interval'], $data['until'])) {
            throw new InvalidRecurrencePatternException('Required fields are missing');
        }

        return new self(
            $data['frequency'],
            (int) $data['interval'],
            $data['daysOfWeek'] ?? null,
            $data['daysOfMonth'] ?? null,
            $data['excludeDates'] ?? [],
            $data['until']
        );
    }

    private function validateFrequency(string $frequency): void
    {
        if (!in_array($frequency, self::VALID_FREQUENCIES, true)) {
            throw new InvalidRecurrencePatternException(
                sprintf(
                    'Invalid frequency. Must be one of: %s',
                    implode(', ', self::VALID_FREQUENCIES)
                )
            );
        }
    }

    private function validateInterval(int $interval): void
    {
        if ($interval < 1) {
            throw new InvalidRecurrencePatternException('Interval must be greater than 0');
        }
    }

    private function validateDaysOfWeek(?array $daysOfWeek): void
    {
        if ($daysOfWeek === null) {
            return;
        }

        foreach ($daysOfWeek as $day) {
            if (!is_int($day) || $day < 1 || $day > 7) {
                throw new InvalidRecurrencePatternException(
                    'Days of week must be integers between 1 (Monday) and 7 (Sunday)'
                );
            }
        }
    }

    private function validateDaysOfMonth(?array $daysOfMonth): void
    {
        if ($daysOfMonth === null) {
            return;
        }

        foreach ($daysOfMonth as $day) {
            if (!is_int($day) || $day < 1 || $day > 31) {
                throw new InvalidRecurrencePatternException(
                    'Days of month must be integers between 1 and 31'
                );
            }
        }
    }

    private function validateExcludeDates(array $excludeDates): void
    {
        foreach ($excludeDates as $date) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                throw new InvalidRecurrencePatternException(
                    'Exclude dates must be in Y-m-d format'
                );
            }
        }
    }

    private function validateUntil(string $until): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $until)) {
            throw new InvalidRecurrencePatternException(
                'Until date must be in Y-m-d format'
            );
        }
    }

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getDaysOfWeek(): ?array
    {
        return $this->daysOfWeek;
    }

    public function getDaysOfMonth(): ?array
    {
        return $this->daysOfMonth;
    }

    /**
     * @return DateTimeImmutable[]
     */
    public function getExcludeDates(): array
    {
        return $this->excludeDates;
    }

    public function getUntil(): DateTimeImmutable
    {
        return $this->until;
    }

    public function isDateExcluded(DateTimeImmutable $date): bool
    {
        foreach ($this->excludeDates as $excludeDate) {
            if ($date->format('Y-m-d') === $excludeDate->format('Y-m-d')) {
                return true;
            }
        }

        return false;
    }

    public function isDateValid(DateTimeImmutable $date): bool
    {
        if ($this->isDateExcluded($date)) {
            return false;
        }

        if ($date > $this->until) {
            return false;
        }

        $dayOfWeek = (int) $date->format('N'); // 1 (Monday) to 7 (Sunday)
        $dayOfMonth = (int) $date->format('j'); // 1 to 31

        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                return true;

            case self::FREQUENCY_WEEKLY:
                if ($this->daysOfWeek === null) {
                    return true;
                }
                return in_array($dayOfWeek, $this->daysOfWeek, true);

            case self::FREQUENCY_MONTHLY:
                if ($this->daysOfMonth === null) {
                    return true;
                }
                return in_array($dayOfMonth, $this->daysOfMonth, true);

            default:
                return false;
        }
    }

    /**
     * Generates occurrence dates for this pattern within a given range.
     *
     * @param DateTimeImmutable $patternStartDate The date the availability pattern starts from.
     * @param DateTimeImmutable $rangeStart Start of the date range to find occurrences in.
     * @param DateTimeImmutable $rangeEnd End of the date range to find occurrences in.
     * @return array<DateTimeImmutable>
     */
    public function getOccurrences(DateTimeImmutable $patternStartDate, DateTimeImmutable $rangeStart, DateTimeImmutable $rangeEnd): array
    {
        $occurrences = [];
        $effectiveEndDate = $this->until ? min($rangeEnd, $this->until) : $rangeEnd;

        // Ensure we don't generate dates before the pattern actually starts
        $currentDate = max($patternStartDate, $rangeStart);

        while ($currentDate <= $effectiveEndDate) {
            $isValid = false;
            switch ($this->frequency) {
                case self::FREQUENCY_DAILY:
                    // Daily check: occurs every 'interval' days starting from patternStartDate
                    $diffInDays = $patternStartDate->diff($currentDate)->days;
                    if ($diffInDays % $this->interval === 0) {
                        $isValid = true;
                    }
                    break;

                case self::FREQUENCY_WEEKLY:
                    // Weekly check: occurs every 'interval' weeks on specified daysOfWeek
                     // Check if the week itself is valid based on interval
                    $weekDiff = (int)floor($patternStartDate->diff($currentDate)->days / 7);
                    if ($weekDiff % $this->interval === 0) {
                         // Check if the day of the week matches
                         $currentDayOfWeek = (int)$currentDate->format('w'); // 0 (Sun) to 6 (Sat)
                         if ($this->daysOfWeek !== null && in_array($currentDayOfWeek, $this->daysOfWeek, true)) {
                            $isValid = true;
                         }
                    }
                    break;

                case self::FREQUENCY_MONTHLY:
                    // Monthly check: occurs every 'interval' months on specified daysOfMonth
                    // Calculate month difference carefully
                     $monthDiff = (($currentDate->format('Y') - $patternStartDate->format('Y')) * 12)
                                 + ($currentDate->format('n') - $patternStartDate->format('n'));
                     if ($monthDiff >= 0 && $monthDiff % $this->interval === 0) {
                          // Check if the day of the month matches
                         $currentDayOfMonth = (int)$currentDate->format('j');
                         if ($this->daysOfMonth !== null && in_array($currentDayOfMonth, $this->daysOfMonth, true)) {
                             $isValid = true;
                         }
                     }
                     break;
            }

            // Final checks: must be within range, after pattern start, and not excluded
            if ($isValid &&
                $currentDate >= $rangeStart && // Must be within requested range
                $currentDate <= $effectiveEndDate &&
                !isset($this->excludeDates[$currentDate->format('Y-m-d')]) // Check exclusion list
            ) {
                $occurrences[] = $currentDate;
            }

            // Move to the next day to check
            $currentDate = $currentDate->modify('+1 day');
        }

        return $occurrences;
    }

    public function jsonSerialize(): array
    {
        return [
            'frequency' => $this->frequency,
            'interval' => $this->interval,
            'daysOfWeek' => $this->daysOfWeek,
            'daysOfMonth' => $this->daysOfMonth,
            'excludeDates' => array_map(
                fn(DateTimeImmutable $date) => $date->format('Y-m-d'),
                $this->excludeDates
            ),
            'until' => $this->until->format('Y-m-d')
        ];
    }
}
 