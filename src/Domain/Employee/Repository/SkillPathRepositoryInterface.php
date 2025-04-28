<?php

declare(strict_types=1);

namespace App\Domain\Employee\Repository;

use App\Domain\Employee\Entity\SkillPath;

interface SkillPathRepositoryInterface
{
    public function find(int $id): ?SkillPath;
} 