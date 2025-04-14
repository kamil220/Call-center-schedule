<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\Entity\User;

final class UserCreatedEvent
{
    private User $user;
    private \DateTimeImmutable $occurredOn;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }
} 