<?php

declare(strict_types=1);

namespace App\Domain\Employee\Repository;

use App\Domain\Employee\Entity\Skill;

interface SkillRepositoryInterface
{
    public function find(int $id): ?Skill;
} 