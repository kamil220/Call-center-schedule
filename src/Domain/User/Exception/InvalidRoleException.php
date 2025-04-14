<?php

declare(strict_types=1);

namespace App\Domain\User\Exception;

use App\Domain\User\Entity\User;
use DomainException;

final class InvalidRoleException extends DomainException
{
    public function __construct(string $role)
    {
        $validRoles = implode(', ', User::VALID_ROLES);
        parent::__construct(sprintf('Invalid role "%s". Valid roles are: %s', $role, $validRoles));
    }
} 