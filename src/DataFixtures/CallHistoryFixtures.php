<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Call\Entity\Call;
use App\Domain\Employee\Entity\Skill;
use App\Domain\Employee\Entity\SkillPath;
use App\Domain\User\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class CallHistoryFixtures extends Fixture implements DependentFixtureInterface
{
    private const START_DATE = '2025-02-01';
    private const END_DATE = '2025-04-27';
    private const WORK_START_HOUR = 6;
    private const WORK_END_HOUR = 21;
    private const MIN_DURATION = 40; // seconds
    private const MAX_DURATION = 1800; // 30 minutes in seconds
    private const WORK_HOURS_PER_DAY = 8;
    private const BATCH_SIZE = 5;

    // Define skill path weights (probability of calls for each path)
    private const SKILL_PATH_WEIGHTS = [
        SkillSystemFixtures::CUSTOMER_SERVICE_PATH_REFERENCE => 40,  // 40% calls
        SkillSystemFixtures::SALES_PATH_REFERENCE => 30,            // 30% calls
        SkillSystemFixtures::TECHNICAL_PATH_REFERENCE => 20,        // 20% calls
        SkillSystemFixtures::ADMINISTRATION_PATH_REFERENCE => 10    // 10% calls
    ];

    public function load(ObjectManager $manager): void
    {
        /** @var EntityManagerInterface $em */
        $em = $manager;
        
        $output = new ConsoleOutput();
        $output->writeln('Loading operators...');
        $operators = $this->getOperators();
        $output->writeln(sprintf('Found %d operators', count($operators)));
        
        $output->writeln('Loading lines for all skill paths...');
        $linesByPath = $this->getLinesByPath($em);
        if (empty($linesByPath)) {
            throw new \RuntimeException('No phone lines found');
        }
        
        foreach ($linesByPath as $pathRef => $lines) {
            $output->writeln(sprintf('Found %d lines for path %s', count($lines), $pathRef));
        }

        $startDate = new DateTime(self::START_DATE);
        $endDate = new DateTime(self::END_DATE);
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($startDate, $interval, $endDate);
        
        $totalDays = iterator_count($dateRange);
        $output->writeln(sprintf('Generating calls for %d days', $totalDays));
        
        $progressBar = new ProgressBar($output);
        $progressBar->start($totalDays);

        $batchCount = 0;
        $totalCalls = 0;
        
        // Track operator's calls for overlap prevention
        $operatorCallEndTimes = [];

        foreach ($dateRange as $date) {
            $output->writeln(sprintf("\nProcessing date: %s", $date->format('Y-m-d')));
            
            // Skip weekends
            if ($date->format('N') >= 6) {
                $output->writeln('Skipping weekend');
                $progressBar->advance();
                continue;
            }

            // Reset operator call end times for new day
            $operatorCallEndTimes = [];

            foreach ($operators as $operatorIndex => $operator) {
                $operatorReference = sprintf('%s-%d', UserFixtures::AGENT_USER_REFERENCE, $operatorIndex);
                $output->writeln(sprintf('Processing operator: %s (ref: %s)', $operator->getEmail(), $operatorReference));
                
                // Calculate target work hours for this operator today (random between 60-100%)
                $workloadPercentage = rand(60, 100) / 100;
                $targetWorkSeconds = (int)($workloadPercentage * self::WORK_HOURS_PER_DAY * 3600);
                $currentWorkSeconds = 0;

                // Initialize operator's first available time
                if (!isset($operatorCallEndTimes[$operatorReference])) {
                    $operatorCallEndTimes[$operatorReference] = clone $date;
                    $operatorCallEndTimes[$operatorReference]->setTime(self::WORK_START_HOUR, 0);
                }

                while ($currentWorkSeconds < $targetWorkSeconds) {
                    $lastEndTime = $operatorCallEndTimes[$operatorReference];
                    
                    // If we've passed the work end hour, break
                    if ($lastEndTime->format('H') >= self::WORK_END_HOUR) {
                        break;
                    }

                    // Generate random duration for next call
                    $duration = rand(self::MIN_DURATION, self::MAX_DURATION);
                    
                    // Ensure we don't exceed target work seconds by too much
                    if ($currentWorkSeconds + $duration > $targetWorkSeconds * 1.1) {
                        break;
                    }

                    // Select skill path based on weights
                    $selectedPath = $this->selectSkillPathByWeight();
                    $availableLines = $linesByPath[$selectedPath] ?? [];
                    
                    if (empty($availableLines)) {
                        continue;
                    }

                    $call = new Call(
                        clone $lastEndTime,
                        $availableLines[array_rand($availableLines)],
                        $this->generatePhoneNumber(),
                        $this->getReference($operatorReference, User::class),
                        $duration
                    );

                    $em->persist($call);
                    $batchCount++;
                    $totalCalls++;
                    
                    // Update operator's next available time
                    $operatorCallEndTimes[$operatorReference] = clone $lastEndTime;
                    $operatorCallEndTimes[$operatorReference]->modify(sprintf('+%d seconds', $duration));
                    
                    // Add small break between calls (15-60 seconds)
                    $breakDuration = rand(15, 60);
                    $operatorCallEndTimes[$operatorReference]->modify(sprintf('+%d seconds', $breakDuration));
                    
                    $currentWorkSeconds += $duration;

                    // Batch persist more frequently
                    if ($batchCount >= self::BATCH_SIZE) {
                        $em->flush();
                        $em->clear();
                        
                        // Reload references after clearing
                        $operators = $this->getOperators();
                        $linesByPath = $this->getLinesByPath($em);
                        $operator = $this->getReference(sprintf('%s-%d', UserFixtures::AGENT_USER_REFERENCE, $operatorIndex), User::class);
                        
                        $batchCount = 0;
                    }
                }
            }
            
            $progressBar->advance();
            $output->writeln(sprintf('Completed day %s', $date->format('Y-m-d')));
        }

        if ($batchCount > 0) {
            $output->writeln(sprintf('Flushing final batch of %d calls...', $batchCount));
            $em->flush();
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln(sprintf('Successfully generated %d calls', $totalCalls));
    }

    private function getOperators(): array
    {
        $operators = [];
        
        // Add agents
        for ($i = 0; $i < 10; $i++) {
            $operators[] = $this->getReference(
                sprintf('%s-%d', UserFixtures::AGENT_USER_REFERENCE, $i),
                User::class
            );
        }

        return $operators;
    }

    private function getLinesByPath(EntityManagerInterface $em): array
    {
        $linesByPath = [];
        
        foreach (self::SKILL_PATH_WEIGHTS as $pathReference => $weight) {
            $skillPath = $this->getReference($pathReference, SkillPath::class);
            $lines = $em->getRepository(Skill::class)->findBy(['skillPath' => $skillPath]);
            
            if (!empty($lines)) {
                $linesByPath[$pathReference] = $lines;
            }
        }
        
        if (empty($linesByPath)) {
            throw new \RuntimeException('No skills found for any skill path');
        }
        
        return $linesByPath;
    }

    private function selectSkillPathByWeight(): string
    {
        $total = array_sum(self::SKILL_PATH_WEIGHTS);
        $random = rand(1, $total);
        $current = 0;
        
        foreach (self::SKILL_PATH_WEIGHTS as $pathReference => $weight) {
            $current += $weight;
            if ($random <= $current) {
                return $pathReference;
            }
        }
        
        // Fallback to customer service if something goes wrong
        return SkillSystemFixtures::CUSTOMER_SERVICE_PATH_REFERENCE;
    }

    private function generatePhoneNumber(): string
    {
        $prefixes = ['48'];
        $prefix = $prefixes[array_rand($prefixes)];
        $number = '';
        for ($i = 0; $i < 9; $i++) {
            $number .= rand(0, 9);
        }
        return '+' . $prefix . $number;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            SkillSystemFixtures::class,
            WorkScheduleFixtures::class,
        ];
    }
} 