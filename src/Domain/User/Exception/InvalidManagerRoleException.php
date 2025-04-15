<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use App\Domain\User\Entity\User;
use DomainException;

final class InvalidManagerRoleException extends DomainException
{
    public function __construct()
    {
        $validRoles = implode(', ', User::MANAGER_ROLES);
        parent::__construct(sprintf('Manager must have one of the following roles: %s', $validRoles));
    }
} 