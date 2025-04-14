<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Command;

use App\Application\User\Service\UserService;
use App\Domain\User\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates the initial admin user'
)]
class CreateAdminCommand extends Command
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();
        $this->userService = $userService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = 'admin@email.com';
        $existingUser = $this->userService->getUserByEmail($email);

        if ($existingUser) {
            $io->warning(sprintf('Admin user with email "%s" already exists.', $email));
            return Command::SUCCESS;
        }

        $this->userService->createUser(
            $email,
            'admin',
            'Admin',
            'User',
            [User::ROLE_ADMIN]
        );

        $io->success(sprintf('Admin user created with email "%s" and password "admin".', $email));

        return Command::SUCCESS;
    }
} 