<?php

declare(strict_types=1);

namespace App\Application\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class UserFilterRequestDTO
{
    private ?string $firstName = null;
    private ?string $lastName = null;
    private ?string $email = null;
    private ?string $role = null;
    private ?bool $active = null;
    
    #[Assert\PositiveOrZero]
    private int $page = 0;
    
    #[Assert\Positive]
    private int $limit = 10;
    
    private ?string $sortBy = null;
    private ?string $sortDirection = 'ASC';
    
    public static function fromArray(array $data): self
    {
        $dto = new self();
        
        if (isset($data['firstName'])) {
            $dto->firstName = $data['firstName'];
        }
        
        if (isset($data['lastName'])) {
            $dto->lastName = $data['lastName'];
        }
        
        if (isset($data['email'])) {
            $dto->email = $data['email'];
        }
        
        if (isset($data['role'])) {
            $dto->role = $data['role'];
        }
        
        if (isset($data['active'])) {
            $dto->active = filter_var($data['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        
        if (isset($data['page'])) {
            $dto->page = (int) $data['page'];
        }
        
        if (isset($data['limit'])) {
            $dto->limit = (int) $data['limit'];
        }
        
        if (isset($data['sortBy'])) {
            $dto->sortBy = $data['sortBy'];
        }
        
        if (isset($data['sortDirection']) && in_array(strtoupper($data['sortDirection']), ['ASC', 'DESC'])) {
            $dto->sortDirection = strtoupper($data['sortDirection']);
        }
        
        return $dto;
    }
    
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }
    
    public function getLastName(): ?string
    {
        return $this->lastName;
    }
    
    public function getEmail(): ?string
    {
        return $this->email;
    }
    
    public function getRole(): ?string
    {
        return $this->role;
    }
    
    public function getActive(): ?bool
    {
        return $this->active;
    }
    
    public function getPage(): int
    {
        return $this->page;
    }
    
    public function getLimit(): int
    {
        return $this->limit;
    }
    
    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }
    
    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }
    
    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'role' => $this->role,
            'active' => $this->active,
            'page' => $this->page,
            'limit' => $this->limit,
            'sortBy' => $this->sortBy,
            'sortDirection' => $this->sortDirection,
        ];
    }
} 