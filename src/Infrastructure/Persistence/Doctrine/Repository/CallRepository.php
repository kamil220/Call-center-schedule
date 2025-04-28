<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Call\Entity\Call;
use App\Domain\Call\Repository\CallRepositoryInterface;
use App\Domain\Employee\Entity\Skill;
use App\Domain\User\Entity\User;
use App\Domain\Employee\Entity\SkillPath;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CallRepository extends ServiceEntityRepository implements CallRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Call::class);
    }

    public function findByFilters(
        ?User $operator = null,
        ?Skill $line = null,
        ?SkillPath $skillPath = null,
        ?string $phoneNumber = null,
        int $page = 0,
        int $limit = 10,
        string $sortBy = 'dateTime',
        string $sortDirection = 'DESC'
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.line', 'l')
            ->leftJoin('l.skillPath', 'sp')
            ->leftJoin('c.operator', 'o');

        if ($operator !== null) {
            $qb->andWhere('c.operator = :operator')
                ->setParameter('operator', $operator);
        }

        if ($line !== null) {
            $qb->andWhere('c.line = :line')
                ->setParameter('line', $line);
        }

        if ($skillPath !== null) {
            $qb->andWhere('l.skillPath = :skillPath')
                ->setParameter('skillPath', $skillPath);
        }

        if ($phoneNumber !== null) {
            $qb->andWhere('c.phoneNumber LIKE :phoneNumber')
                ->setParameter('phoneNumber', '%' . $phoneNumber . '%');
        }

        // Add sorting
        if ($sortBy === 'dateTime') {
            $qb->orderBy('c.dateTime', $sortDirection);
        } elseif ($sortBy === 'duration') {
            $qb->orderBy('c.duration', $sortDirection);
        } elseif ($sortBy === 'phoneNumber') {
            $qb->orderBy('c.phoneNumber', $sortDirection);
        }

        // Get total count before pagination
        $countQb = clone $qb;
        $totalCount = $countQb->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Add pagination
        $qb->setFirstResult($page * $limit)
            ->setMaxResults($limit);

        return [
            $qb->getQuery()->getResult(),
            $totalCount
        ];
    }

    public function save(Call $call): void
    {
        $this->_em->persist($call);
        $this->_em->flush();
    }
} 