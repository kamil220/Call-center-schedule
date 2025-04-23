<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\User\Entity\User;
use App\Domain\WorkSchedule\Entity\LeaveRequest;
use App\Domain\WorkSchedule\Repository\LeaveRequestRepositoryInterface;
use App\Domain\WorkSchedule\ValueObject\LeaveStatus;
use App\Domain\WorkSchedule\ValueObject\LeaveType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class LeaveRequestRepository implements LeaveRequestRepositoryInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function save(LeaveRequest $leaveRequest): void
    {
        $this->entityManager->persist($leaveRequest);
        $this->entityManager->flush();
    }

    public function remove(LeaveRequest $leaveRequest): void
    {
        $this->entityManager->remove($leaveRequest);
        $this->entityManager->flush();
    }

    public function findById(string $id): ?LeaveRequest
    {
        return $this->entityManager->find(LeaveRequest::class, $id);
    }

    public function findByUser(User $user): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('lr')
            ->from(LeaveRequest::class, 'lr')
            ->where('lr.user = :user')
            ->setParameter('user', $user)
            ->orderBy('lr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndDateRange(
        User $user,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): array {
        return $this->entityManager->createQueryBuilder()
            ->select('lr')
            ->from(LeaveRequest::class, 'lr')
            ->where('lr.user = :user')
            ->andWhere('(lr.startDate BETWEEN :startDate AND :endDate OR lr.endDate BETWEEN :startDate AND :endDate OR (lr.startDate <= :startDate AND lr.endDate >= :endDate))')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('lr.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(LeaveStatus $status): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('lr')
            ->from(LeaveRequest::class, 'lr')
            ->where('lr.status = :status')
            ->setParameter('status', $status)
            ->orderBy('lr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(LeaveType $type): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('lr')
            ->from(LeaveRequest::class, 'lr')
            ->where('lr.type = :type')
            ->setParameter('type', $type)
            ->orderBy('lr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingRequestsForManager(User $manager): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('lr')
            ->from(LeaveRequest::class, 'lr')
            ->join('lr.user', 'u')
            ->where('u.manager = :manager')
            ->andWhere('lr.status = :status')
            ->setParameter('manager', $manager)
            ->setParameter('status', LeaveStatus::PENDING)
            ->orderBy('lr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverlappingRequests(
        User $user,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?string $excludeId = null
    ): array {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('lr')
            ->from(LeaveRequest::class, 'lr')
            ->where('lr.user = :user')
            ->andWhere('lr.status = :status')
            ->andWhere('(
                (lr.startDate <= :endDate AND lr.endDate >= :startDate) OR
                (lr.startDate BETWEEN :startDate AND :endDate) OR
                (lr.endDate BETWEEN :startDate AND :endDate)
            )')
            ->setParameter('user', $user)
            ->setParameter('status', LeaveStatus::APPROVED)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($excludeId) {
            $qb->andWhere('lr.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->entityManager->createQueryBuilder()
            ->select('lr')
            ->from(LeaveRequest::class, 'lr')
            ->orderBy('lr.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(lr.id)')
            ->from(LeaveRequest::class, 'lr')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findActiveByUserIntersectingDateRange(User $user, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('lr')
            ->from(LeaveRequest::class, 'lr')
            ->where('lr.user = :user')
            ->andWhere('lr.status IN (:statuses)')
            // Check for intersection: (LeaveStart <= RangeEnd) AND (LeaveEnd >= RangeStart)
            ->andWhere('lr.startDate <= :endDate')
            ->andWhere('lr.endDate >= :startDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('statuses', [LeaveStatus::APPROVED, LeaveStatus::PENDING])
            ->orderBy('lr.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
} 