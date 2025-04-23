<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\EmploymentType;
use App\Domain\WorkSchedule\Entity\Availability;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class WorkScheduleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Generate availability for each agent
        for ($i = 0; $i < 10; $i++) {
            /** @var User $agent */
            $agent = $this->getReference(sprintf('%s-%d', UserFixtures::AGENT_USER_REFERENCE, $i), User::class);

            // Define recurrence patterns for different shifts
            $morningShiftPattern = [
                'frequency' => 'weekly',
                'interval' => 1,
                'daysOfWeek' => [1, 3, 5], // Monday, Wednesday, Friday
                'excludeDates' => ['2025-05-01', '2025-05-03'], // Holidays in May
                'until' => '2025-06-30'
            ];

            $afternoonShiftPattern = [
                'frequency' => 'weekly',
                'interval' => 1,
                'daysOfWeek' => [2, 4], // Tuesday, Thursday
                'excludeDates' => ['2025-05-01', '2025-05-03'],
                'until' => '2025-06-30'
            ];

            // For full-time employees, generate both shifts on different days
            if ($agent->getEmploymentType() === EmploymentType::EMPLOYMENT_CONTRACT) {
                // Morning shift starts on Monday
                $this->createAvailability(
                    $manager,
                    $agent,
                    new DateTimeImmutable('2025-04-01'), // Monday
                    '08:00',
                    '16:00',
                    $morningShiftPattern
                );

                // Afternoon shift starts on Tuesday
                $this->createAvailability(
                    $manager,
                    $agent,
                    new DateTimeImmutable('2025-04-02'), // Tuesday
                    '14:00',
                    '22:00',
                    $afternoonShiftPattern
                );
            }
            // For part-time employees, only morning shift on specific days
            elseif ($agent->getEmploymentType() === EmploymentType::CIVIL_CONTRACT) {
                $this->createAvailability(
                    $manager,
                    $agent,
                    new DateTimeImmutable('2025-04-01'),
                    '08:00',
                    '16:00',
                    [
                        'frequency' => 'weekly',
                        'interval' => 1,
                        'daysOfWeek' => [1, 3], // Monday, Wednesday only
                        'excludeDates' => ['2025-05-01'],
                        'until' => '2025-06-30'
                    ]
                );
            }
            // For contractors (B2B), flexible hours on all weekdays
            else {
                $this->createAvailability(
                    $manager,
                    $agent,
                    new DateTimeImmutable('2025-04-01'),
                    '10:00',
                    '18:00',
                    [
                        'frequency' => 'weekly',
                        'interval' => 1,
                        'daysOfWeek' => [1, 2, 3, 4, 5],
                        'excludeDates' => ['2025-05-01'],
                        'until' => '2025-06-30'
                    ]
                );
            }

            // Add single weekend availability (without recurrence pattern)
            // Only for contractors and full-time employees, every second weekend
            if ($agent->getEmploymentType() !== EmploymentType::CIVIL_CONTRACT) {
                $weekendDates = [
                    '2025-04-06', // First weekend
                    '2025-04-20', // Third weekend
                ];

                foreach ($weekendDates as $date) {
                    $this->createAvailability(
                        $manager,
                        $agent,
                        new DateTimeImmutable($date),
                        '10:00',
                        '18:00'
                    );
                }
            }
        }

        $manager->flush();
    }

    private function createAvailability(
        ObjectManager $manager,
        User $user,
        DateTimeImmutable $date,
        string $startTime,
        string $endTime,
        ?array $recurrencePattern = null
    ): void {
        $startDateTime = DateTimeImmutable::createFromFormat('H:i', $startTime);
        $endDateTime = DateTimeImmutable::createFromFormat('H:i', $endTime);

        if (!$startDateTime || !$endDateTime) {
            throw new \RuntimeException('Invalid time format');
        }

        $availability = new Availability(
            Uuid::uuid4(),
            $user,
            $user->getEmploymentType(),
            new TimeRange($startDateTime, $endDateTime),
            $date,
            $recurrencePattern
        );

        $manager->persist($availability);
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
} 