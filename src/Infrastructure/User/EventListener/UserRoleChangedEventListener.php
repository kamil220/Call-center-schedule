<?php

declare(strict_types=1);

namespace App\Infrastructure\User\EventListener;

use App\Domain\User\Entity\User;
use App\Domain\User\Event\UserRoleChangedEvent;
use App\Domain\User\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UserRoleChangedEventListener implements EventSubscriberInterface
{
    private UserRepositoryInterface $userRepository;
    private LoggerInterface $logger;

    public function __construct(
        UserRepositoryInterface $userRepository,
        LoggerInterface $logger
    ) {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserRoleChangedEvent::class => 'onUserRoleChanged',
        ];
    }

    public function onUserRoleChanged(UserRoleChangedEvent $event): void
    {
        $user = $event->getUser();
        
        // Log the role change
        $this->logger->info('User role changed', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'old_roles' => $event->getOldRoles(),
            'new_roles' => $event->getNewRoles(),
            'timestamp' => $event->getOccurredOn()->format('Y-m-d H:i:s'),
        ]);
        
        // If a manager was demoted, reassign their subordinates
        if ($event->wasManager() && !$event->isStillManager()) {
            $this->handleManagerDemotion($user);
        }
    }
    
    private function handleManagerDemotion(User $user): void
    {
        $this->logger->info('Manager demoted, reassigning subordinates', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
        ]);
        
        // Find the first admin to be the new manager
        $adminUsers = $this->userRepository->findByFilters(['role' => User::ROLE_ADMIN]);
        $firstAdmin = $adminUsers[0][0] ?? null;
        
        if (!$firstAdmin) {
            $this->logger->warning('No admin user found to reassign subordinates, subordinates will have no manager');
            return;
        }
        
        // Reassign all subordinates to the first admin
        foreach ($user->getSubordinates() as $subordinate) {
            $subordinate->setManager($firstAdmin);
            $this->userRepository->save($subordinate);
            
            $this->logger->info('Subordinate reassigned', [
                'subordinate_id' => $subordinate->getId(),
                'subordinate_email' => $subordinate->getEmail(),
                'new_manager_id' => $firstAdmin->getId(),
                'new_manager_email' => $firstAdmin->getEmail(),
            ]);
        }
    }
} 