<?php

declare(strict_types=1);

namespace App\Tests\Domain\User\Exception;

use App\Domain\User\Entity\User;
use App\Domain\User\Exception\InvalidRoleException;
use PHPUnit\Framework\TestCase;

class InvalidRoleExceptionTest extends TestCase
{
    public function testExceptionMessageContainsInvalidRoleAndValidRoles(): void
    {
        // Arrange
        $invalidRole = 'INVALID_ROLE';
        
        // Act
        $exception = new InvalidRoleException($invalidRole);
        
        // Assert
        $this->assertStringContainsString($invalidRole, $exception->getMessage());
        
        foreach (User::VALID_ROLES as $role) {
            $this->assertStringContainsString($role, $exception->getMessage());
        }
    }
} 