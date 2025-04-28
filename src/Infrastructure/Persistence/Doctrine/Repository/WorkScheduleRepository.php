<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Employee\Entity\EmployeeSkillPath;
use App\Domain\User\Entity\User;
use App\Domain\WorkSchedule\Entity\WorkSchedule;
use App\Domain\WorkSchedule\Repository\WorkScheduleRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WorkScheduleRepository extends ServiceEntityRepository implements WorkScheduleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkSchedule::class);
    }

    public function save(WorkSchedule $workSchedule): void
    {
        $this->_em->persist($workSchedule);
        $this->_em->flush();
    }

    public function remove(WorkSchedule $workSchedule): void
    {
        $this->_em->remove($workSchedule);
        $this->_em->flush();
    }

    public function findById(string $id): ?WorkSchedule
    {
        return $this->find($id);
    }

    public function findByUser(User $user, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('ws')
            ->where('ws.user = :user')
            ->andWhere('ws.date BETWEEN :startDate AND :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('ws.date', 'ASC')
            ->addOrderBy('ws.timeRange.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySkillPath(EmployeeSkillPath $skillPath, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('ws')
            ->where('ws.skillPath = :skillPath')
            ->andWhere('ws.date BETWEEN :startDate AND :endDate')
            ->setParameter('skillPath', $skillPath)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('ws.date', 'ASC')
            ->addOrderBy('ws.timeRange.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverlappingSchedules(
        User $user,
        DateTimeImmutable $date,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime
    ): array {
        return $this->createQueryBuilder('ws')
            ->where('ws.user = :user')
            ->andWhere('ws.date = :date')
            ->andWhere(
                '(ws.timeRange.startTime < :endTime AND ws.timeRange.endTime > :startTime)'
            )
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('ws')
            ->where('ws.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('ws.date', 'ASC')
            ->addOrderBy('ws.timeRange.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 