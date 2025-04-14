<?php

declare(strict_types=1);

namespace App\Application\User\DTO;

use App\Domain\User\Entity\User;

final class UserResponseDTO
{
    private string $id;
    private string $email;
    private string $firstName;
    private string $lastName;
    private string $fullName;
    private array $roles;
    private bool $active;

    public function __construct(
        string $id,
        string $email,
        string $firstName,
        string $lastName,
        string $fullName,
        array $roles,
        bool $active
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->fullName = $fullName;
        $this->roles = $roles;
        $this->active = $active;
    }

    public static function fromEntity(User $user): self
    {
        return new self(
            $user->getId(),
            $user->getEmail(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getFullName(),
            $user->getRoles(),
            $user->isActive()
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'fullName' => $this->fullName,
            'roles' => $this->roles,
            'active' => $this->active,
        ];
    }
} 