<?php

declare(strict_types=1);

namespace App\Application\User\Command;

final class CreateUserCommand
{
    private string $email;
    private string $password;
    private string $firstName;
    private string $lastName;
    private array $roles;
    private ?string $hireDate;
    private ?string $managerId;

    public function __construct(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        array $roles,
        ?string $hireDate = null,
        ?string $managerId = null
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->roles = $roles;
        $this->hireDate = $hireDate;
        $this->managerId = $managerId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }
    
    public function getHireDate(): ?string
    {
        return $this->hireDate;
    }
    
    public function getManagerId(): ?string
    {
        return $this->managerId;
    }
} 