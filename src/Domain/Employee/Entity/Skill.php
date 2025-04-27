<?php

declare(strict_types=1);

namespace App\Domain\Employee\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'employee_skills')]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private string $name;

    #[ORM\ManyToOne(targetEntity: SkillPath::class, inversedBy: 'skills', fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['user:read'])]
    private SkillPath $skillPath;

    #[ORM\OneToMany(mappedBy: 'skill', targetEntity: EmployeeSkill::class, orphanRemoval: true)]
    private Collection $employeeSkills;

    public function __construct(string $name, SkillPath $skillPath)
    {
        $this->name = $name;
        $this->skillPath = $skillPath;
        $this->employeeSkills = new ArrayCollection();
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

    public function getSkillPath(): SkillPath
    {
        return $this->skillPath;
    }

    public function setSkillPath(?SkillPath $skillPath): self
    {
        $this->skillPath = $skillPath;
        return $this;
    }

    public function getEmployeeSkills(): Collection
    {
        return $this->employeeSkills;
    }
} 