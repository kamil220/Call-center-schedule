<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\Entity\User;

class UserRoleChangedEvent
{
    private User $user;
    private array $oldRoles;
    private array $newRoles;
    private \DateTimeImmutable $occurredOn;

    public function __construct(User $user, array $oldRoles, array $newRoles)
    {
        $this->user = $user;
        $this->oldRoles = $oldRoles;
        $this->newRoles = $newRoles;
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOldRoles(): array
    {
        return $this->oldRoles;
    }

    public function getNewRoles(): array
    {
        return $this->newRoles;
    }

    public function getOccurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function wasManager(): bool
    {
        foreach (User::MANAGER_ROLES as $role) {
            if (in_array($role, $this->oldRoles, true)) {
                return true;
            }
        }
        
        return false;
    }

    public function isStillManager(): bool
    {
        foreach (User::MANAGER_ROLES as $role) {
            if (in_array($role, $this->newRoles, true)) {
                return true;
            }
        }
        
        return false;
    }
} 