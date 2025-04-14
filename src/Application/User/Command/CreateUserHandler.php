<?php

declare(strict_types=1);

namespace App\Application\User\Command;

use App\Application\User\Service\UserService;
use App\Domain\User\Entity\User;

final class CreateUserHandler
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function handle(CreateUserCommand $command): User
    {
        return $this->userService->createUser(
            $command->getEmail(),
            $command->getPassword(),
            $command->getFirstName(),
            $command->getLastName(),
            $command->getRoles()
        );
    }
} 