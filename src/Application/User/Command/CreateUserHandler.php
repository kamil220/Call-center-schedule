<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\User\Service\UserService;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\UserId;

final class CreateUserHandler
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function handle(CreateUserCommand $command): User
    {
        $hireDate = null;
        if ($command->getHireDate()) {
            $hireDate = \DateTimeImmutable::createFromFormat('Y-m-d', $command->getHireDate());
        }
        
        $manager = null;
        if ($command->getManagerId()) {
            $manager = $this->userService->getUserById($command->getManagerId());
            
            if (!$manager) {
                throw new \InvalidArgumentException(sprintf('Manager with ID "%s" not found', $command->getManagerId()));
            }
            
            if (!$manager->canBeManager()) {
                throw new \InvalidArgumentException('The specified user cannot be assigned as a manager');
            }
        }
        
        return $this->userService->createUser(
            $command->getEmail(),
            $command->getPassword(),
            $command->getFirstName(),
            $command->getLastName(),
            $command->getRoles(),
            $hireDate,
            $manager
        );
    }
} 