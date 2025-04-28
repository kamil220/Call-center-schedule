<?php

declare(strict_types=1);

namespace App\Domain\Employee\Entity;

use App\Domain\User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'employee_skills_assignments')]
class EmployeeSkill
{
    public const MIN_LEVEL = 1;
    public const MAX_LEVEL = 5;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'employeeSkills', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private Skill $skill;

    #[ORM\Column(type: 'integer')]
    #[Groups(['user:read'])]
    private int $level;

    public function __construct(User $user, Skill $skill, int $level)
    {
        $this->user = $user;
        $this->setLevel($level);
        $this->setSkill($skill);
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

    public function getSkill(): Skill
    {
        return $this->skill;
    }

    public function setSkill(Skill $skill): self
    {
        $this->skill = $skill;
        
        // Automatically create EmployeeSkillPath if it doesn't exist
        $skillPath = $skill->getSkillPath();
        $userSkillPaths = $this->user->getEmployeeSkillPaths();
        
        $hasSkillPath = $userSkillPaths->exists(function($key, $employeeSkillPath) use ($skillPath) {
            return $employeeSkillPath->getSkillPath()->getId() === $skillPath->getId();
        });
        
        if (!$hasSkillPath) {
            $employeeSkillPath = new EmployeeSkillPath($this->user, $skillPath);
            $this->user->addEmployeeSkillPath($employeeSkillPath);
        }
        
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        if ($level < self::MIN_LEVEL || $level > self::MAX_LEVEL) {
            throw new \InvalidArgumentException(
                sprintf('Skill level must be between %d and %d', self::MIN_LEVEL, self::MAX_LEVEL)
            );
        }
        $this->level = $level;
        return $this;
    }
} 