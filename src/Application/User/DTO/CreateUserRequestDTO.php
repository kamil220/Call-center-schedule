<?php

declare(strict_types=1);

namespace App\Application\User\DTO;

use App\Domain\User\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserRequestDTO
{
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    private string $password;

    #[Assert\NotBlank]
    private string $firstName;

    #[Assert\NotBlank]
    private string $lastName;

    #[Assert\NotBlank]
    #[Assert\All([
        new Assert\Choice(choices: User::VALID_ROLES)
    ])]
    private array $roles;

    public function __construct(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        array $roles
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->roles = $roles;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['firstName'] ?? '',
            $data['lastName'] ?? '',
            $data['roles'] ?? []
        );
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
} 