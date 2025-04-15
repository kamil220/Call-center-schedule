<?php

declare(strict_types=1);

namespace App\Tests\Domain\User\Event;

use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserRoleChangedEvent;
use App\Domain\User\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class UserRoleChangedEventTest extends TestCase
{
    private User $user;
    
    protected function setUp(): void
    {
        $userId = UserId::generate();
        $this->user = new User(
            $userId,
            'test@example.com',
            'Test',
            'User'
        );
    }
    
    public function testEventCreation(): void
    {
        $oldRoles = [User::ROLE_ADMIN, User::ROLE_TEAM_MANAGER];
        $newRoles = [User::ROLE_AGENT];
        
        $event = new UserRoleChangedEvent($this->user, $oldRoles, $newRoles);
        
        $this->assertSame($this->user, $event->getUser());
        $this->assertSame($oldRoles, $event->getOldRoles());
        $this->assertSame($newRoles, $event->getNewRoles());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredOn());
    }
    
    public function testWasManager(): void
    {
        // Test when user was a manager
        $event = new UserRoleChangedEvent(
            $this->user,
            [User::ROLE_ADMIN],
            [User::ROLE_AGENT]
        );
        $this->assertTrue($event->wasManager());
        
        // Test with team manager role
        $event = new UserRoleChangedEvent(
            $this->user,
            [User::ROLE_TEAM_MANAGER],
            [User::ROLE_AGENT]
        );
        $this->assertTrue($event->wasManager());
        
        // Test with planner role
        $event = new UserRoleChangedEvent(
            $this->user,
            [User::ROLE_PLANNER],
            [User::ROLE_AGENT]
        );
        $this->assertTrue($event->wasManager());
        
        // Test when user was not a manager
        $event = new UserRoleChangedEvent(
            $this->user,
            [User::ROLE_AGENT],
            [User::ROLE_AGENT]
        );
        $this->assertFalse($event->wasManager());
    }
    
    public function testIsStillManager(): void
    {
        // Test when user is still a manager
        $event = new UserRoleChangedEvent(
            $this->user,
            [User::ROLE_ADMIN],
            [User::ROLE_TEAM_MANAGER]
        );
        $this->assertTrue($event->isStillManager());
        
        // Test when user has multiple roles including manager role
        $event = new UserRoleChangedEvent(
            $this->user,
            [User::ROLE_ADMIN],
            [User::ROLE_AGENT, User::ROLE_ADMIN]
        );
        $this->assertTrue($event->isStillManager());
        
        // Test when user is no longer a manager
        $event = new UserRoleChangedEvent(
            $this->user,
            [User::ROLE_ADMIN],
            [User::ROLE_AGENT]
        );
        $this->assertFalse($event->isStillManager());
    }
} 