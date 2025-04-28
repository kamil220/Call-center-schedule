<?php

declare(strict_types=1);

namespace App\Domain\Call\Entity;

use App\Domain\Employee\Entity\Skill;
use App\Domain\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'calls')]
class Call
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['call:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['call:read'])]
    private \DateTimeInterface $dateTime;

    #[ORM\ManyToOne(targetEntity: Skill::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['call:read'])]
    private Skill $line;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['call:read'])]
    private string $phoneNumber;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['call:read'])]
    private User $operator;

    #[ORM\Column(type: 'integer')]
    #[Groups(['call:read'])]
    private int $duration;

    public function __construct(
        \DateTimeInterface $dateTime,
        Skill $line,
        string $phoneNumber,
        User $operator,
        int $duration
    ) {
        $this->dateTime = $dateTime;
        $this->line = $line;
        $this->phoneNumber = $phoneNumber;
        $this->operator = $operator;
        $this->duration = $duration;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateTime(): \DateTimeInterface
    {
        return $this->dateTime;
    }

    public function getLine(): Skill
    {
        return $this->line;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getOperator(): User
    {
        return $this->operator;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }
} 