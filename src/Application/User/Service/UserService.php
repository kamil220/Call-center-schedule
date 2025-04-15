<?php

declare(strict_types=1);

namespace App\Application\User\Service;

use App\Application\User\DTO\UserFilterRequestDTO;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\UserId;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Event\UserRoleChangedEvent;

final class UserService
{
    private UserRepositoryInterface $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private EventDispatcherInterface $eventDispatcher;
    
    public function __construct(
        UserRepositoryInterface $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    public function createUser(
        string $email,
        string $plainPassword,
        string $firstName,
        string $lastName,
        array $roles,
        ?\DateTimeInterface $hireDate = null,
        ?User $manager = null
    ): User {
        // Check if a user with this email already exists
        $existingUser = $this->getUserByEmail($email);
        if ($existingUser !== null) {
            throw new \InvalidArgumentException(sprintf('User with email "%s" already exists', $email));
        }
        
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
        
        if ($hireDate !== null) {
            $user->setHireDate($hireDate);
        }
        
        if ($manager !== null) {
            $user->setManager($manager);
        }
        
        $this->userRepository->save($user);
        
        // Dispatch domain event
        $this->eventDispatcher->dispatch(new UserCreatedEvent($user));
        
        return $user;
    }
    
    public function updateUser(
        User $user,
        string $email,
        string $firstName,
        string $lastName,
        array $roles,
        ?\DateTimeInterface $hireDate = null,
        ?User $manager = null
    ): User {
        // Check if the email is being changed and if a user with the new email already exists
        if ($user->getEmail() !== $email) {
            $existingUser = $this->getUserByEmail($email);
            if ($existingUser !== null && $existingUser->getId() !== $user->getId()) {
                throw new \InvalidArgumentException(sprintf('User with email "%s" already exists', $email));
            }
        }
        
        $user
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName);
        
        // Get current roles before updating
        $oldRoles = $user->getRoles();
        
        // Reset roles and add new ones
        foreach (User::VALID_ROLES as $role) {
            if ($user->hasRole($role)) {
                $user->removeRole($role);
            }
        }
        
        foreach ($roles as $role) {
            $user->addRole($role);
        }
        
        // Set hire date (may be null, which is allowed)
        $user->setHireDate($hireDate);
        
        // Only set manager if the user doesn't become a self-manager
        if ($manager !== null && $manager->getId() !== $user->getId()) {
            $user->setManager($manager);
        }
        
        $this->userRepository->save($user);
        
        // Check if roles changed and dispatch event if they did
        $newRoles = $user->getRoles();
        if (array_diff($oldRoles, $newRoles) || array_diff($newRoles, $oldRoles)) {
            $this->eventDispatcher->dispatch(new UserRoleChangedEvent($user, $oldRoles, $newRoles));
        }
        
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
    
    /**
     * Get filtered and paginated users
     * 
     * @param UserFilterRequestDTO $filterDTO
     * @return array{0: User[], 1: int} Array containing [results, totalCount]
     */
    public function getFilteredUsers(UserFilterRequestDTO $filterDTO): array
    {
        $criteria = [];
        
        if ($filterDTO->getName()) {
            $criteria['name'] = $filterDTO->getName();
        }
        
        if ($filterDTO->getFirstName()) {
            $criteria['firstName'] = $filterDTO->getFirstName();
        }
        
        if ($filterDTO->getLastName()) {
            $criteria['lastName'] = $filterDTO->getLastName();
        }
        
        if ($filterDTO->getEmail()) {
            $criteria['email'] = $filterDTO->getEmail();
        }
        
        if ($filterDTO->getRole()) {
            $criteria['role'] = $filterDTO->getRole();
        }
        
        if ($filterDTO->getActive() !== null) {
            $criteria['active'] = $filterDTO->getActive();
        }
        
        $orderBy = null;
        if ($filterDTO->getSortBy()) {
            $orderBy = [$filterDTO->getSortBy() => $filterDTO->getSortDirection()];
        }
        
        $limit = $filterDTO->getLimit();
        $offset = $filterDTO->getPage() * $filterDTO->getLimit();
        
        return $this->userRepository->findByFilters($criteria, $orderBy, $limit, $offset);
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