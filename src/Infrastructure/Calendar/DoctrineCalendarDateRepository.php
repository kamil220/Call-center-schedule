<?php

declare(strict_types=1);

namespace App\Infrastructure\Calendar;

use App\Domain\Calendar\Entity\CalendarDate;
use App\Domain\Calendar\Repository\CalendarDateRepositoryInterface;
use App\Domain\Calendar\ValueObject\DayType;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CalendarDate>
 */
final class DoctrineCalendarDateRepository extends ServiceEntityRepository implements CalendarDateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalendarDate::class);
    }

    public function findByYear(int $year): array
    {
        $startDate = new DateTimeImmutable(sprintf('%d-01-01', $year));
        $endDate = new DateTimeImmutable(sprintf('%d-12-31', $year));

        return $this->createQueryBuilder('cd')
            ->where('cd.date >= :start')
            ->andWhere('cd.date <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function findByYearAndMonth(int $year, int $month): array
    {
        $startDate = new DateTimeImmutable(sprintf('%d-%02d-01', $year, $month));
        $endDate = new DateTimeImmutable(sprintf('%d-%02d-%d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year)));

        return $this->createQueryBuilder('cd')
            ->where('cd.date >= :start')
            ->andWhere('cd.date <= :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function save(CalendarDate $calendarDate): void
    {
        $this->_em->persist($calendarDate);
        $this->_em->flush();
    }

    public function remove(CalendarDate $calendarDate): void
    {
        $this->_em->remove($calendarDate);
        $this->_em->flush();
    }

    public function findByDate(DateTimeImmutable $date): ?CalendarDate
    {
        return $this->createQueryBuilder('cd')
            ->where('cd.date = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 