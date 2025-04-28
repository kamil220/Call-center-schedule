<?php

declare(strict_types=1);

namespace App\Domain\Call\Repository;

use App\Domain\Call\Entity\Call;
use App\Domain\Employee\Entity\Skill;
use App\Domain\Employee\Entity\SkillPath;
use App\Domain\User\Entity\User;

interface CallRepositoryInterface
{
    /**
     * @return array{0: Call[], 1: int}
     */
    public function findByFilters(
        ?User $operator = null,
        ?Skill $line = null,
        ?SkillPath $skillPath = null,
        ?string $phoneNumber = null,
        int $page = 0,
        int $limit = 10,
        string $sortBy = 'dateTime',
        string $sortDirection = 'DESC'
    ): array;

    public function save(Call $call): void;
} 