<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepositoryInterface
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public function findById(UserId $id): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($id->toString());
    }
    
    public function findByEmail(string $email): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }
    
    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
    
    public function findAll(): array
    {
        return $this->entityManager->getRepository(User::class)->findAll();
    }
    
    public function findByFilters(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')
           ->from(User::class, 'u');
        
        // Apply filters for specific fields
        if (isset($criteria['name']) && $criteria['name']) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(u.firstName) LIKE LOWER(:name)',
                    'LOWER(u.lastName) LIKE LOWER(:name)',
                    "LOWER(CONCAT(u.firstName, ' ', u.lastName)) LIKE LOWER(:nameConcat)"
                )
            )
            ->setParameter('name', '%' . $criteria['name'] . '%')
            ->setParameter('nameConcat', '%' . $criteria['name'] . '%');
        }
        
        if (isset($criteria['firstName']) && $criteria['firstName']) {
            $qb->andWhere('LOWER(u.firstName) LIKE LOWER(:firstName)')
               ->setParameter('firstName', '%' . $criteria['firstName'] . '%');
        }
        
        if (isset($criteria['lastName']) && $criteria['lastName']) {
            $qb->andWhere('LOWER(u.lastName) LIKE LOWER(:lastName)')
               ->setParameter('lastName', '%' . $criteria['lastName'] . '%');
        }
        
        if (isset($criteria['email']) && $criteria['email']) {
            $qb->andWhere('LOWER(u.email) LIKE LOWER(:email)')
               ->setParameter('email', '%' . $criteria['email'] . '%');
        }
        
        // Handle single role (for backward compatibility)
        if (isset($criteria['role']) && $criteria['role']) {
            $qb->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
               ->setParameter('role', json_encode($criteria['role']));
        }
        
        // New role filtering block using JSON_CONTAINS
        if (isset($criteria['roles']) && !empty($criteria['roles']) && is_array($criteria['roles'])) {
            $roleConditions = [];
            
            foreach ($criteria['roles'] as $index => $role) {
                $roleConditions[] = 'JSON_CONTAINS(u.roles, :role_' . $index . ') = 1';
                $qb->setParameter('role_' . $index, json_encode($role));
            }
            
            if (!empty($roleConditions)) {
                $qb->andWhere(implode(' OR ', $roleConditions));
            }
        }
        
        if (isset($criteria['active']) && $criteria['active'] !== null) {
            $qb->andWhere('u.active = :active')
               ->setParameter('active', $criteria['active']);
        }
        
        // Apply sorting
        if ($orderBy && count($orderBy) > 0) {
            foreach ($orderBy as $field => $direction) {
                if (in_array($field, ['id', 'email', 'firstName', 'lastName', 'active'])) {
                    $qb->addOrderBy('u.' . $field, $direction);
                }
            }
        } else {
            $qb->orderBy('u.lastName', 'ASC')
               ->addOrderBy('u.firstName', 'ASC');
        }
        
        // Clone the query builder to count total results (without limit/offset)
        $countQb = clone $qb;
        $countQb->resetDQLPart('orderBy');
        $countQb->select('COUNT(DISTINCT u.id)');
        
        // Copy parameters from $qb to $countQb
        foreach ($qb->getParameters() as $parameter) {
             if (!$countQb->getParameter($parameter->getName())) {
                  $countQb->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());
             }
        }
        // Ensure all role parameters are in the counting query if they were added
        if (isset($roleParams)) {
            foreach ($roleParams as $name => $value) {
                if (!$countQb->getParameter($name)) {
                     $countQb->setParameter($name, $value);
                }
            }
        }
        if (isset($criteria['email']) && $criteria['email']) {
            if (!$countQb->getParameter('email')) { $countQb->setParameter('email', '%' . $criteria['email'] . '%'); }
        }
        
        $totalCount = (int) $countQb->getQuery()->getSingleScalarResult();
        
        // Apply pagination if needed
        if ($limit !== null) {
            $qb->setMaxResults($limit);
            
            if ($offset !== null) {
                $qb->setFirstResult($offset);
            }
        }
        
        $query = $qb->getQuery();
        $parameters = $query->getParameters();
        $paramStr = '';
        foreach ($parameters as $param) {
            $paramStr .= $param->getName() . '=' . $param->getValue() . ', ';
        }

        $results = $query->getResult();
        
        return [$results, $totalCount];
    }
    
    public function remove(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
} 