<?php

declare(strict_types=1);

namespace App\Domain\Employee\Entity;

use App\Domain\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'employee_skill_paths_assignments')]
class EmployeeSkillPath
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private User $user;

    #[ORM\ManyToOne(targetEntity: SkillPath::class, inversedBy: 'employeeSkillPaths', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private SkillPath $skillPath;

    public function __construct(User $user, SkillPath $skillPath)
    {
        $this->user = $user;
        $this->skillPath = $skillPath;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getSkillPath(): SkillPath
    {
        return $this->skillPath;
    }

    public function setSkillPath(SkillPath $skillPath): self
    {
        $this->skillPath = $skillPath;
        return $this;
    }
} 