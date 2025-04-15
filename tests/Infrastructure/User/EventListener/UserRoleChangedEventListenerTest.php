<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\User\EventListener;

use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserRoleChangedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\User\EventListener\UserRoleChangedEventListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Custom event class for testing where we can control wasManager and isStillManager methods
 */
class TestUserRoleChangedEvent extends UserRoleChangedEvent
{
    private bool $wasManagerValue = false;
    private bool $isStillManagerValue = false;
    
    public function setWasManager(bool $value): self
    {
        $this->wasManagerValue = $value;
        return $this;
    }
    
    public function setIsStillManager(bool $value): self
    {
        $this->isStillManagerValue = $value;
        return $this;
    }
    
    public function wasManager(): bool
    {
        return $this->wasManagerValue;
    }
    
    public function isStillManager(): bool
    {
        return $this->isStillManagerValue;
    }
}

class UserRoleChangedEventListenerTest extends TestCase
{
    private UserRoleChangedEventListener $listener;
    
    /** @var MockObject&UserRepositoryInterface */
    private $userRepository;
    
    /** @var MockObject&LoggerInterface */
    private $logger;
    
    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->listener = new UserRoleChangedEventListener(
            $this->userRepository,
            $this->logger
        );
    }
    
    public function testGetSubscribedEvents(): void
    {
        $events = UserRoleChangedEventListener::getSubscribedEvents();
        
        $this->assertArrayHasKey(UserRoleChangedEvent::class, $events);
        $this->assertEquals('onUserRoleChanged', $events[UserRoleChangedEvent::class]);
    }
    
    public function testOnUserRoleChangedLogsEvent(): void
    {
        // Arrange
        $userId = UserId::generate();
        $user = new User($userId, 'test@example.com', 'Test', 'User');
        
        $oldRoles = [User::ROLE_ADMIN];
        $newRoles = [User::ROLE_AGENT];
        
        $event = new TestUserRoleChangedEvent($user, $oldRoles, $newRoles);
        $event->setWasManager(false);
        
        // Expect logger to be called
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'User role changed',
                $this->callback(function ($context) use ($userId) {
                    return $context['user_id'] === $userId->toString()
                        && $context['email'] === 'test@example.com'
                        && $context['old_roles'] === [User::ROLE_ADMIN]
                        && $context['new_roles'] === [User::ROLE_AGENT]
                        && isset($context['timestamp']);
                })
            );
        
        // Act
        $this->listener->onUserRoleChanged($event);
    }
    
    public function testHandleManagerDemotionReassignsSubordinates(): void
    {
        // Arrange
        $managerId = UserId::generate();
        /** @var User&MockObject $manager */
        $manager = $this->createPartialMock(User::class, ['getSubordinates', 'getId', 'getEmail']);
        $manager->method('getId')->willReturn($managerId->toString());
        $manager->method('getEmail')->willReturn('manager@example.com');
        
        $adminId = UserId::generate();
        $admin = new User($adminId, 'admin@example.com', 'Admin', 'User');
        $admin->addRole(User::ROLE_ADMIN);
        
        /** @var User&MockObject $subordinate1 */
        $subordinate1 = $this->createPartialMock(User::class, ['setManager', 'getId', 'getEmail']);
        $subordinate1->method('getId')->willReturn('sub1');
        $subordinate1->method('getEmail')->willReturn('sub1@example.com');
        
        /** @var User&MockObject $subordinate2 */
        $subordinate2 = $this->createPartialMock(User::class, ['setManager', 'getId', 'getEmail']);
        $subordinate2->method('getId')->willReturn('sub2');
        $subordinate2->method('getEmail')->willReturn('sub2@example.com');
        
        $subordinates = [$subordinate1, $subordinate2];
        $manager->method('getSubordinates')->willReturn($subordinates);
        
        // Create event with wasManager=true and isStillManager=false
        $oldRoles = [User::ROLE_TEAM_MANAGER];
        $newRoles = [User::ROLE_AGENT];
        $event = new TestUserRoleChangedEvent($manager, $oldRoles, $newRoles);
        $event->setWasManager(true);
        $event->setIsStillManager(false);
        
        // Mock repository to return admin when searching for admins
        $this->userRepository->expects($this->once())
            ->method('findByFilters')
            ->with(['role' => User::ROLE_ADMIN])
            ->willReturn([[$admin], 1]);
        
        // Expect subordinates to be reassigned to admin
        $subordinate1->expects($this->once())->method('setManager')->with($admin);
        $subordinate2->expects($this->once())->method('setManager')->with($admin);
        
        // Expect repository save calls for each subordinate
        $this->userRepository->expects($this->exactly(2))
            ->method('save');
        
        // Expect logging
        $logCalls = 0;
        
        $this->logger->expects($this->atLeast(3))
            ->method('info')
            ->willReturnCallback(function ($message, $context) use (&$logCalls, $managerId) {
                $logCalls++;
                
                if ($logCalls === 1) {
                    $this->assertEquals('User role changed', $message);
                } elseif ($logCalls === 2) {
                    $this->assertEquals('Manager demoted, reassigning subordinates', $message);
                    $this->assertEquals($managerId->toString(), $context['user_id']);
                    $this->assertEquals('manager@example.com', $context['email']);
                } elseif ($logCalls >= 3) {
                    $this->assertEquals('Subordinate reassigned', $message);
                }
                
                return true;
            });
        
        // Act
        $this->listener->onUserRoleChanged($event);
    }
    
    public function testHandleManagerDemotionWhenNoAdminExists(): void
    {
        // Arrange
        $managerId = UserId::generate();
        /** @var User&MockObject $manager */
        $manager = $this->createPartialMock(User::class, ['getSubordinates', 'getId', 'getEmail']);
        $manager->method('getId')->willReturn($managerId->toString());
        $manager->method('getEmail')->willReturn('manager@example.com');
        $manager->method('getSubordinates')->willReturn([]);
        
        // Create event with wasManager=true and isStillManager=false
        $oldRoles = [User::ROLE_TEAM_MANAGER];
        $newRoles = [User::ROLE_AGENT];
        $event = new TestUserRoleChangedEvent($manager, $oldRoles, $newRoles);
        $event->setWasManager(true);
        $event->setIsStillManager(false);
        
        // Mock repository to return empty result when searching for admins
        $this->userRepository->expects($this->once())
            ->method('findByFilters')
            ->with(['role' => User::ROLE_ADMIN])
            ->willReturn([[], 0]);
        
        // Expect logging
        $logCalls = 0;
        
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function ($message, $context) use (&$logCalls, $managerId) {
                $logCalls++;
                
                if ($logCalls === 1) {
                    $this->assertEquals('User role changed', $message);
                } else {
                    $this->assertEquals('Manager demoted, reassigning subordinates', $message);
                    $this->assertEquals($managerId->toString(), $context['user_id']);
                    $this->assertEquals('manager@example.com', $context['email']);
                }
                
                return true;
            });
        
        // Expect warning log for no admin
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'No admin user found to reassign subordinates, subordinates will have no manager'
            );
        
        // Act
        $this->listener->onUserRoleChanged($event);
    }
} 