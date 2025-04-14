<?php

declare(strict_types=1);

namespace App\Tests\Application\User\Service;

use App\Application\User\Service\UserService;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends TestCase
{
    private UserRepositoryInterface|MockObject $userRepository;
    private UserPasswordHasherInterface|MockObject $passwordHasher;
    private UserService $userService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->userService = new UserService($this->userRepository, $this->passwordHasher);
    }

    public function testCreateUser(): void
    {
        // Arrange
        $email = 'test@example.com';
        $plainPassword = 'password123';
        $firstName = 'John';
        $lastName = 'Doe';
        $roles = [User::ROLE_ADMIN];
        $hashedPassword = 'hashed_password';

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn($hashedPassword);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) use ($email, $hashedPassword, $firstName, $lastName) {
                return $user->getEmail() === $email
                    && $user->getPassword() === $hashedPassword
                    && $user->getFirstName() === $firstName
                    && $user->getLastName() === $lastName
                    && $user->hasRole(User::ROLE_ADMIN);
            }));

        // Act
        $user = $this->userService->createUser($email, $plainPassword, $firstName, $lastName, $roles);

        // Assert
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($hashedPassword, $user->getPassword());
        $this->assertSame($firstName, $user->getFirstName());
        $this->assertSame($lastName, $user->getLastName());
        $this->assertTrue($user->hasRole(User::ROLE_ADMIN));
    }

    public function testGetUserById(): void
    {
        // Arrange
        $userId = UserId::generate();
        $user = $this->createTestUser($userId);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($this->callback(function (UserId $id) use ($userId) {
                return $id->equals($userId);
            }))
            ->willReturn($user);

        // Act
        $result = $this->userService->getUserById($userId->toString());

        // Assert
        $this->assertSame($user, $result);
    }

    public function testGetUserByEmail(): void
    {
        // Arrange
        $email = 'test@example.com';
        $user = $this->createTestUser(UserId::generate(), $email);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        // Act
        $result = $this->userService->getUserByEmail($email);

        // Assert
        $this->assertSame($user, $result);
    }

    public function testActivateUser(): void
    {
        // Arrange
        $user = $this->createTestUser(UserId::generate());
        $user->deactivate();
        
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        // Act
        $this->userService->activateUser($user);

        // Assert
        $this->assertTrue($user->isActive());
    }

    private function createTestUser(UserId $id, string $email = 'test@example.com'): User
    {
        $user = new User($id, $email, 'John', 'Doe');
        $user->setPassword('hashed_password');
        return $user;
    }
} 