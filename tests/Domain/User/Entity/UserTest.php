<?php

declare(strict_types=1);

namespace App\Tests\Domain\User\Entity;

use App\Domain\User\Entity\User;
use App\Domain\User\Exception\InvalidRoleException;
use App\Domain\User\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;
    private UserId $userId;

    protected function setUp(): void
    {
        $this->userId = UserId::generate();
        $this->user = new User(
            $this->userId,
            'test@example.com',
            'John',
            'Doe'
        );
    }

    public function testUserCreation(): void
    {
        $this->assertSame('test@example.com', $this->user->getEmail());
        $this->assertSame('John', $this->user->getFirstName());
        $this->assertSame('Doe', $this->user->getLastName());
        $this->assertSame('John Doe', $this->user->getFullName());
        $this->assertTrue($this->user->isActive());
        $this->assertEmpty($this->user->getRoles());
    }

    public function testAddRole(): void
    {
        $this->user->addRole(User::ROLE_ADMIN);
        
        $this->assertContains(User::ROLE_ADMIN, $this->user->getRoles());
        $this->assertTrue($this->user->hasRole(User::ROLE_ADMIN));
    }

    public function testRemoveRole(): void
    {
        $this->user->addRole(User::ROLE_ADMIN);
        $this->user->addRole(User::ROLE_AGENT);
        
        $this->user->removeRole(User::ROLE_ADMIN);
        
        $this->assertNotContains(User::ROLE_ADMIN, $this->user->getRoles());
        $this->assertContains(User::ROLE_AGENT, $this->user->getRoles());
    }

    public function testAddInvalidRole(): void
    {
        $this->expectException(InvalidRoleException::class);
        
        $this->user->addRole('INVALID_ROLE');
    }

    public function testAddDuplicateRole(): void
    {
        $this->user->addRole(User::ROLE_ADMIN);
        $this->user->addRole(User::ROLE_ADMIN);
        
        $roles = $this->user->getRoles();
        $this->assertCount(1, $roles);
        $this->assertContains(User::ROLE_ADMIN, $roles);
    }

    public function testDeactivateAndActivate(): void
    {
        $this->assertTrue($this->user->isActive());
        
        $this->user->deactivate();
        $this->assertFalse($this->user->isActive());
        
        $this->user->activate();
        $this->assertTrue($this->user->isActive());
    }

    public function testUpdateUserData(): void
    {
        $this->user->setEmail('new@example.com');
        $this->user->setFirstName('Jane');
        $this->user->setLastName('Smith');
        
        $this->assertSame('new@example.com', $this->user->getEmail());
        $this->assertSame('Jane', $this->user->getFirstName());
        $this->assertSame('Smith', $this->user->getLastName());
        $this->assertSame('Jane Smith', $this->user->getFullName());
    }
} 