<?php

declare(strict_types=1);

namespace App\Application\Call\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CallFilterRequestDTO
{
    #[Assert\Uuid(message: 'Invalid operator ID format')]
    private ?string $operatorId = null;

    #[Assert\Positive(message: 'Invalid line ID')]
    private ?int $lineId = null;

    #[Assert\Positive(message: 'Invalid skill path ID')]
    private ?int $skillPathId = null;

    private ?string $phoneNumber = null;

    #[Assert\GreaterThanOrEqual(value: 0)]
    private int $page = 0;

    #[Assert\Range(min: 1, max: 100)]
    private int $limit = 10;

    #[Assert\Choice(choices: ['dateTime', 'duration', 'phoneNumber'])]
    private string $sortBy = 'dateTime';

    #[Assert\Choice(choices: ['ASC', 'DESC'])]
    private string $sortDirection = 'DESC';

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->operatorId = $data['operatorId'] ?? null;
        $dto->lineId = isset($data['lineId']) ? (int) $data['lineId'] : null;
        $dto->skillPathId = isset($data['skillPathId']) ? (int) $data['skillPathId'] : null;
        $dto->phoneNumber = $data['phoneNumber'] ?? null;
        $dto->page = isset($data['page']) ? (int) $data['page'] : 0;
        $dto->limit = isset($data['limit']) ? (int) $data['limit'] : 10;
        $dto->sortBy = $data['sortBy'] ?? 'dateTime';
        $dto->sortDirection = $data['sortDirection'] ?? 'DESC';

        return $dto;
    }

    public function getOperatorId(): ?string
    {
        return $this->operatorId;
    }

    public function getLineId(): ?int
    {
        return $this->lineId;
    }

    public function getSkillPathId(): ?int
    {
        return $this->skillPathId;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }
} 