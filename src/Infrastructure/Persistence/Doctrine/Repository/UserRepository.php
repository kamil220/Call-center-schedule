<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?User
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findById(UserId $id): ?User
    {
        return $this->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    /**
     * @return array{0: User[], 1: int}
     */
    public function findByFilters(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->createQueryBuilder('u');

        foreach ($criteria as $field => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $qb->andWhere($qb->expr()->in('u.' . $field, ':' . $field))
                    ->setParameter($field, $value);
            } else {
                $qb->andWhere('u.' . $field . ' = :' . $field)
                    ->setParameter($field, $value);
            }
        }

        // Get total count before pagination
        $countQb = clone $qb;
        $totalCount = $countQb->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('u.' . $field, $direction);
            }
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return [$qb->getQuery()->getResult(), $totalCount];
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
} 