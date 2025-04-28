<?php

declare(strict_types=1);

namespace App\Application\Call\DTO;

use App\Domain\Call\Entity\Call;

class CallResponseDTO
{
    private int $id;
    private string $dateTime;
    private array $skillPath;
    private array $line;
    private string $phoneNumber;
    private array $operator;
    private int $duration;

    public static function fromEntity(Call $call): self
    {
        $dto = new self();
        $dto->id = $call->getId();
        $dto->dateTime = $call->getDateTime()->format('Y-m-d H:i:s');
        $dto->skillPath = [
            'id' => $call->getLine()->getSkillPath()->getId(),
            'name' => $call->getLine()->getSkillPath()->getName()
        ];
        $dto->line = [
            'id' => $call->getLine()->getId(),
            'name' => $call->getLine()->getName()
        ];
        $dto->phoneNumber = $call->getPhoneNumber();
        $dto->operator = [
            'id' => $call->getOperator()->getId()->toString(),
            'fullName' => $call->getOperator()->getFullName(),
            'email' => $call->getOperator()->getEmail()
        ];
        $dto->duration = $call->getDuration();

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'dateTime' => $this->dateTime,
            'skillPath' => $this->skillPath,
            'line' => $this->line,
            'phoneNumber' => $this->phoneNumber,
            'operator' => $this->operator,
            'duration' => $this->duration
        ];
    }
} 