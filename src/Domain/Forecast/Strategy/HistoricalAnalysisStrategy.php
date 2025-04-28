<?php

declare(strict_types=1);

namespace App\Domain\Forecast\Strategy;

use App\Domain\Call\Entity\Call;
use App\Domain\Employee\Entity\EmployeeSkillPath;
use App\Domain\Forecast\ValueObject\ForecastDemand;
use App\Domain\Forecast\ValueObject\ForecastPeriod;
use App\Domain\Forecast\ValueObject\ForecastResult;
use Doctrine\ORM\EntityManagerInterface;

class HistoricalAnalysisStrategy implements ForecastStrategyInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly int $historicalDays = 90, // Default to analyzing last 90 days
        private readonly float $peakLoadFactor = 1.2 // 20% buffer for peak times
    ) {
    }

    public function forecast(ForecastPeriod $period): ForecastResult
    {
        $result = new ForecastResult();
        $skillPaths = $this->entityManager->getRepository(EmployeeSkillPath::class)->findAll();
        
        foreach ($skillPaths as $skillPath) {
            $this->analyzePath($skillPath, $period, $result);
        }

        return $result;
    }

    private function analyzePath(EmployeeSkillPath $skillPath, ForecastPeriod $period, ForecastResult $result): void
    {
        $historicalStartDate = (new \DateTimeImmutable())->modify("-{$this->historicalDays} days");
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
            ->from(Call::class, 'c')
            ->join('c.line', 'l')
            ->where('l.skillPath = :skillPath')
            ->andWhere('c.dateTime >= :startDate')
            ->setParameter('skillPath', $skillPath)
            ->setParameter('startDate', $historicalStartDate);

        $calls = $qb->getQuery()->getResult();
        
        // Group calls by hour
        $hourlyDistribution = $this->calculateHourlyDistribution($calls);
        
        // Generate forecast for each day in the period
        $currentDate = \DateTimeImmutable::createFromInterface($period->getStartDate());
        $endDate = \DateTimeImmutable::createFromInterface($period->getEndDate());
        
        while ($currentDate <= $endDate) {
            $this->generateDailyForecast($currentDate, $skillPath, $hourlyDistribution, $result);
            $currentDate = $currentDate->modify('+1 day');
        }
    }

    private function calculateHourlyDistribution(array $calls): array
    {
        // Initialize distributions for each day of week (0 = Sunday, 6 = Saturday)
        $distributions = array_fill(0, 7, array_fill(0, 24, 0));
        $dayCountsByHour = array_fill(0, 7, array_fill(0, 24, []));

        /** @var Call $call */
        foreach ($calls as $call) {
            $dateTime = $call->getDateTime();
            $dayOfWeek = (int) $dateTime->format('w'); // 0 (Sunday) through 6 (Saturday)
            $hour = (int) $dateTime->format('G');
            $date = $dateTime->format('Y-m-d');
            
            $distributions[$dayOfWeek][$hour]++;
            $dayCountsByHour[$dayOfWeek][$hour][$date] = true;
        }

        // Calculate average calls per hour for each day of week
        $finalDistribution = [];
        for ($dayOfWeek = 0; $dayOfWeek < 7; $dayOfWeek++) {
            $finalDistribution[$dayOfWeek] = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $uniqueDaysCount = count($dayCountsByHour[$dayOfWeek][$hour]);
                if ($uniqueDaysCount > 0) {
                    // Calculate average calls per hour with peak load factor
                    $averageCalls = ($distributions[$dayOfWeek][$hour] / $uniqueDaysCount) * $this->peakLoadFactor;
                    
                    // Convert to required employees (assuming each employee can handle 1 call at a time)
                    // Round up to ensure we have enough coverage
                    $finalDistribution[$dayOfWeek][$hour] = (int) ceil($averageCalls);
                } else {
                    $finalDistribution[$dayOfWeek][$hour] = 0;
                }
            }
        }

        return $finalDistribution;
    }

    private function generateDailyForecast(
        \DateTimeInterface $date,
        EmployeeSkillPath $skillPath,
        array $hourlyDistribution,
        ForecastResult $result
    ): void {
        $dayOfWeek = (int) $date->format('w');
        $dayDistribution = $hourlyDistribution[$dayOfWeek] ?? array_fill(0, 24, 0);

        for ($hour = 6; $hour <= 14; $hour++) { // Ograniczamy do godzin 6-14
            if ($dayDistribution[$hour] > 0) {
                $demandDate = \DateTimeImmutable::createFromInterface($date);
                $demand = new ForecastDemand(
                    $demandDate,
                    $hour,
                    $skillPath,
                    $dayDistribution[$hour]
                );
                
                $result->addDemand($demand);
            }
        }
    }

    public function getName(): string
    {
        return 'historical_analysis';
    }

    public function getDescription(): string
    {
        return 'Forecasts demand based on historical call data analysis';
    }
} 