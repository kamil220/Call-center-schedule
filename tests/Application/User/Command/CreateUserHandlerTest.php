<?php

declare(strict_types=1);

namespace App\Tests\Application\User\Command;

use App\Application\User\Command\CreateUserCommand;
use App\Application\User\Command\CreateUserHandler;
use App\Application\User\Service\UserService;
use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserHandlerTest extends TestCase
{
    private CreateUserHandler $handler;
    
    /**
     * @var UserRepositoryInterface&MockObject
     */
    private $userRepositoryMock;
    
    /**
     * @var UserPasswordHasherInterface&MockObject
     */
    private $passwordHasherMock;
    
    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasherMock = $this->createMock(UserPasswordHasherInterface::class);
        
        // Używamy rzeczywistej instancji UserService z mockami zależności
        $userService = new UserService(
            $this->userRepositoryMock,
            $this->passwordHasherMock
        );
        
        $this->handler = new CreateUserHandler($userService);
    }
    
    public function testHandleCreatesUser(): void
    {
        // Arrange
        $email = 'test@example.com';
        $password = 'password123';
        $firstName = 'John';
        $lastName = 'Doe';
        $roles = [User::ROLE_ADMIN];
        
        $command = new CreateUserCommand(
            $email,
            $password,
            $firstName,
            $lastName,
            $roles
        );
        
        // Konfiguracja mocków
        $this->passwordHasherMock
            ->method('hashPassword')
            ->willReturn('hashed_password');
        
        $this->userRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) use ($email, $firstName, $lastName) {
                return $user->getEmail() === $email
                    && $user->getFirstName() === $firstName
                    && $user->getLastName() === $lastName
                    && $user->hasRole(User::ROLE_ADMIN);
            }));
        
        // Act
        $user = $this->handler->handle($command);
        
        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($firstName, $user->getFirstName());
        $this->assertEquals($lastName, $user->getLastName());
        $this->assertTrue($user->hasRole(User::ROLE_ADMIN));
    }
} 