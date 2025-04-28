<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Employee\Entity\SkillPath;
use App\Domain\Employee\Repository\SkillPathRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SkillPathRepository extends ServiceEntityRepository implements SkillPathRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SkillPath::class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?SkillPath
    {
        return parent::find($id, $lockMode, $lockVersion);
    }
} 