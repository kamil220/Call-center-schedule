<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use Ramsey\Uuid\Uuid;
use Stringable;

final class UserId implements Stringable
{
    private string $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $id): self
    {
        return new self($id);
    }

    public function toString(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function equals(UserId $other): bool
    {
        return $this->id === $other->id;
    }
} 