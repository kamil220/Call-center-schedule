<?php

declare(strict_types=1);

namespace App\Infrastructure\User\EventListener;

use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Event\UserActivatedEvent;
use App\Domain\User\Event\UserDeactivatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UserEventListener implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedEvent::class => 'onUserCreated',
            UserActivatedEvent::class => 'onUserActivated',
            UserDeactivatedEvent::class => 'onUserDeactivated',
        ];
    }

    public function onUserCreated(UserCreatedEvent $event): void
    {
        $user = $event->getUser();
        $this->logger->info('User created', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'timestamp' => $event->getOccurredOn()->format('Y-m-d H:i:s'),
        ]);
    }

    public function onUserActivated(UserActivatedEvent $event): void
    {
        $user = $event->getUser();
        $this->logger->info('User activated', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'timestamp' => $event->getOccurredOn()->format('Y-m-d H:i:s'),
        ]);
    }

    public function onUserDeactivated(UserDeactivatedEvent $event): void
    {
        $user = $event->getUser();
        $this->logger->info('User deactivated', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'timestamp' => $event->getOccurredOn()->format('Y-m-d H:i:s'),
        ]);
        
    }
} 