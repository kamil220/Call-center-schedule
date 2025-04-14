<?php

declare(strict_types=1);

namespace App\Application\User\Service;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\UserId;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserService
{
    private UserRepositoryInterface $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    
    public function __construct(
        UserRepositoryInterface $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }
    
    public function createUser(
        string $email,
        string $plainPassword,
        string $firstName,
        string $lastName,
        array $roles
    ): User {
        $user = new User(
            UserId::generate(),
            $email,
            $firstName,
            $lastName
        );
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        
        foreach ($roles as $role) {
            $user->addRole($role);
        }
        
        $this->userRepository->save($user);
        
        return $user;
    }
    
    public function updateUser(
        User $user,
        string $email,
        string $firstName,
        string $lastName,
        array $roles
    ): User {
        $user
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName);
        
        // Reset roles and add new ones
        foreach (User::VALID_ROLES as $role) {
            if ($user->hasRole($role)) {
                $user->removeRole($role);
            }
        }
        
        foreach ($roles as $role) {
            $user->addRole($role);
        }
        
        $this->userRepository->save($user);
        
        return $user;
    }
    
    public function changePassword(User $user, string $newPlainPassword): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPlainPassword);
        $user->setPassword($hashedPassword);
        
        $this->userRepository->save($user);
    }
    
    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }
    
    public function getUserById(string $id): ?User
    {
        return $this->userRepository->findById(UserId::fromString($id));
    }
    
    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }
    
    public function activateUser(User $user): void
    {
        $user->activate();
        $this->userRepository->save($user);
    }
    
    public function deactivateUser(User $user): void
    {
        $user->deactivate();
        $this->userRepository->save($user);
    }
    
    public function deleteUser(User $user): void
    {
        $this->userRepository->remove($user);
    }
} 