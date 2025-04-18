<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Repository;

use App\Domain\User\Entity\User;
use App\Domain\WorkSchedule\Entity\Availability;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\Uuid;

class AvailabilityRepository extends ServiceEntityRepository implements AvailabilityRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Availability::class);
    }

    public function save(Availability $availability): void
    {
        $this->_em->persist($availability);
        $this->_em->flush();
    }

    public function remove(Availability $availability): void
    {
        $this->_em->remove($availability);
        $this->_em->flush();
    }

    public function findById(string $id): ?Availability
    {
        return $this->findOneBy(['id' => Uuid::fromString($id)]);
    }

    public function findByUserAndDateRange(User $user, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.date >= :startDate')
            ->andWhere('a.date <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();
    }

    public function findOverlapping(Availability $availability): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.id != :id')
            ->andWhere('a.date = :date')
            ->andWhere('
                (a.timeRange.startTime <= :endTime AND a.timeRange.endTime >= :startTime) OR
                (a.timeRange.startTime <= :startTime AND a.timeRange.endTime >= :startTime) OR
                (a.timeRange.startTime <= :endTime AND a.timeRange.endTime >= :endTime)
            ')
            ->setParameter('user', $availability->getUser())
            ->setParameter('id', $availability->getId())
            ->setParameter('date', $availability->getDate())
            ->setParameter('startTime', $availability->getTimeRange()->getStartTime())
            ->setParameter('endTime', $availability->getTimeRange()->getEndTime())
            ->getQuery()
            ->getResult();
    }
} 