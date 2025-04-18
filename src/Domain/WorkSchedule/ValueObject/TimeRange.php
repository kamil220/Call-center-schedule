<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\ValueObject;

use App\Domain\WorkSchedule\Exception\InvalidTimeRangeException;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final class TimeRange
{
    #[ORM\Column(type: 'time_immutable')]
    private DateTimeImmutable $startTime;

    #[ORM\Column(type: 'time_immutable')]
    private DateTimeImmutable $endTime;

    public function __construct(DateTimeImmutable $startTime, DateTimeImmutable $endTime)
    {
        $this->validate($startTime, $endTime);
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    private function validate(DateTimeImmutable $startTime, DateTimeImmutable $endTime): void
    {
        if ($startTime >= $endTime) {
            throw new InvalidTimeRangeException('Start time must be before end time');
        }
    }

    public function getStartTime(): DateTimeImmutable
    {
        return $this->startTime;
    }

    public function getEndTime(): DateTimeImmutable
    {
        return $this->endTime;
    }

    public function overlaps(self $other): bool
    {
        return $this->startTime < $other->endTime && $this->endTime > $other->startTime;
    }

    public function getDuration(): \DateInterval
    {
        return $this->startTime->diff($this->endTime);
    }

    public function getDurationInMinutes(): int
    {
        return (int) $this->startTime->diff($this->endTime)->format('%i') + 
               ((int) $this->startTime->diff($this->endTime)->format('%h') * 60);
    }

    public function format(string $format = 'H:i'): array
    {
        return [
            'startTime' => $this->startTime->format($format),
            'endTime' => $this->endTime->format($format)
        ];
    }
} 