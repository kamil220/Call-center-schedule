<?php

declare(strict_types=1);

namespace App\Domain\Employee\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'employee_skill_paths')]
class SkillPath
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private string $name;

    #[ORM\OneToMany(mappedBy: 'skillPath', targetEntity: Skill::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read'])]
    private Collection $skills;

    #[ORM\OneToMany(mappedBy: 'skillPath', targetEntity: EmployeeSkillPath::class, orphanRemoval: true)]
    private Collection $employeeSkillPaths;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->skills = new ArrayCollection();
        $this->employeeSkillPaths = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): self
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
            $skill->setSkillPath($this);
        }
        return $this;
    }

    public function removeSkill(Skill $skill): self
    {
        if ($this->skills->removeElement($skill)) {
            if ($skill->getSkillPath() === $this) {
                $skill->setSkillPath(null);
            }
        }
        return $this;
    }

    public function getEmployeeSkillPaths(): Collection
    {
        return $this->employeeSkillPaths;
    }
} 