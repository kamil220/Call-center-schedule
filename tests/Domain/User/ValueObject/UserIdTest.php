<?php

declare(strict_types=1);

namespace App\Tests\Domain\User\ValueObject;

use App\Domain\User\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class UserIdTest extends TestCase
{
    public function testGenerate(): void
    {
        $userId = UserId::generate();
        
        $this->assertNotEmpty($userId->toString());
        $this->assertIsString($userId->toString());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $userId->toString());
    }

    public function testFromString(): void
    {
        $uuidString = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $userId = UserId::fromString($uuidString);
        
        $this->assertSame($uuidString, $userId->toString());
        $this->assertSame($uuidString, (string) $userId);
    }

    public function testEquals(): void
    {
        $uuidString = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $userId1 = UserId::fromString($uuidString);
        $userId2 = UserId::fromString($uuidString);
        $userId3 = UserId::generate();
        
        $this->assertTrue($userId1->equals($userId2));
        $this->assertTrue($userId2->equals($userId1));
        $this->assertFalse($userId1->equals($userId3));
    }
} 