<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;
    
    public function findByEmail(string $email): ?User;
    
    public function save(User $user): void;
    
    public function findAll(): array;
    
    /**
     * Find users with filtering and pagination
     *
     * @param array $criteria Filtering criteria
     * @param array|null $orderBy Sorting criteria
     * @param int|null $limit Max number of results
     * @param int|null $offset Pagination offset
     * @return array{0: User[], 1: int} Array containing [results, totalCount]
     */
    public function findByFilters(
        array $criteria = [],
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array;
    
    public function remove(User $user): void;
} 