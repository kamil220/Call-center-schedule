<?php

declare(strict_types=1);

namespace App\Application\Call\Service;

use App\Domain\Call\Repository\CallRepositoryInterface;
use App\Domain\Employee\Entity\Skill;
use App\Domain\Employee\Entity\SkillPath;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\Employee\Repository\SkillRepositoryInterface;
use App\Domain\Employee\Repository\SkillPathRepositoryInterface;
use App\Domain\User\ValueObject\UserId;

class CallService
{
    private CallRepositoryInterface $callRepository;
    private UserRepositoryInterface $userRepository;
    private SkillRepositoryInterface $skillRepository;
    private SkillPathRepositoryInterface $skillPathRepository;

    public function __construct(
        CallRepositoryInterface $callRepository,
        UserRepositoryInterface $userRepository,
        SkillRepositoryInterface $skillRepository,
        SkillPathRepositoryInterface $skillPathRepository
    ) {
        $this->callRepository = $callRepository;
        $this->userRepository = $userRepository;
        $this->skillRepository = $skillRepository;
        $this->skillPathRepository = $skillPathRepository;
    }

    public function getFilteredCalls(
        ?string $operatorId = null,
        ?int $lineId = null,
        ?int $skillPathId = null,
        ?string $phoneNumber = null,
        int $page = 0,
        int $limit = 10,
        string $sortBy = 'dateTime',
        string $sortDirection = 'DESC'
    ): array {
        $operator = null;
        if ($operatorId !== null) {
            $operator = $this->userRepository->findById(UserId::fromString($operatorId));
            if (!$operator) {
                throw new \InvalidArgumentException('Operator not found');
            }
        }

        $line = null;
        if ($lineId !== null) {
            $line = $this->skillRepository->find($lineId);
            if (!$line) {
                throw new \InvalidArgumentException('Line not found');
            }
        }

        $skillPath = null;
        if ($skillPathId !== null) {
            $skillPath = $this->skillPathRepository->find($skillPathId);
            if (!$skillPath) {
                throw new \InvalidArgumentException('Skill path not found');
            }
        }

        return $this->callRepository->findByFilters(
            $operator,
            $line,
            $skillPath,
            $phoneNumber,
            $page,
            $limit,
            $sortBy,
            $sortDirection
        );
    }
} 