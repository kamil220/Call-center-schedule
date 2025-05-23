<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\Employee\Entity\EmployeeSkill;
use App\Domain\Employee\Entity\EmployeeSkillPath;
use App\Domain\User\Exception\InvalidRoleException;
use App\Domain\User\Exception\InvalidManagerRoleException;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\EmploymentType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_PLANNER = 'ROLE_PLANNER';
    public const ROLE_TEAM_MANAGER = 'ROLE_TEAM_MANAGER';
    public const ROLE_AGENT = 'ROLE_AGENT';

    public const VALID_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_PLANNER,
        self::ROLE_TEAM_MANAGER,
        self::ROLE_AGENT,
    ];
    
    public const MANAGER_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_PLANNER,
        self::ROLE_TEAM_MANAGER,
    ];

    #[ORM\Id]
    #[ORM\Column(type: 'user_id')]
    #[Groups(['user:read'])]
    private UserId $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['user:read'])]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $password;

    /** @var string[] */
    #[ORM\Column(type: 'json')]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    #[Groups(['user:read'])]
    private string $firstName;

    #[ORM\Column(type: 'string')]
    #[Groups(['user:read'])]
    private string $lastName;

    #[ORM\Column(type: 'string', enumType: EmploymentType::class)]
    #[Groups(['user:read'])]
    private EmploymentType $employmentType;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:read'])]
    private bool $active = true;
    
    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $hireDate = null;
    
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'manager_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['user:read'])]
    private ?User $manager = null;
    
    #[ORM\OneToMany(mappedBy: 'manager', targetEntity: User::class)]
    #[Groups(['user:read'])]
    private iterable $subordinates;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: EmployeeSkillPath::class, orphanRemoval: true, fetch: 'EAGER', cascade: ['persist'])]
    #[Groups(['user:read'])]
    private Collection $employeeSkillPaths;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: EmployeeSkill::class, orphanRemoval: true, fetch: 'EAGER', cascade: ['persist'])]
    #[Groups(['user:read'])]
    private Collection $employeeSkills;

    public function __construct(
        UserId $id,
        string $email,
        string $firstName,
        string $lastName,
        EmploymentType $employmentType
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->employmentType = $employmentType;
        $this->subordinates = [];
        $this->roles[] = self::ROLE_AGENT;
        $this->employeeSkillPaths = new ArrayCollection();
        $this->employeeSkills = new ArrayCollection();
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        
        return array_unique($roles);
    }

    public function addRole(string $role): self
    {
        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new InvalidRoleException($role);
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        
        return $this;
    }

    public function removeRole(string $role): self
    {
        if (($key = array_search($role, $this->roles, true)) !== false) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }
        
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function activate(): self
    {
        $this->active = true;
        
        return $this;
    }

    public function deactivate(): self
    {
        $this->active = false;
        
        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }
    
    public function getHireDate(): ?\DateTimeInterface
    {
        return $this->hireDate;
    }
    
    public function setHireDate(?\DateTimeInterface $hireDate): self
    {
        $this->hireDate = $hireDate;
        
        return $this;
    }
    
    public function getManager(): ?self
    {
        return $this->manager;
    }
    
    public function setManager(?self $manager): self
    {
        if ($manager !== null && !$manager->canBeManager()) {
            throw new InvalidManagerRoleException($manager->getFullName());
        }
        
        $this->manager = $manager;
        
        return $this;
    }
    
    public function getSubordinates(): iterable
    {
        return $this->subordinates;
    }
    
    public function isValidManager(?self $manager): bool
    {
        if ($manager === null) {
            return true;
        }

        if ($manager === $this) {
            return false;
        }

        return $manager->canBeManager();
    }
    
    public function canBeManager(): bool
    {
        foreach ($this->roles as $role) {
            if (in_array($role, self::MANAGER_ROLES, true)) {
                return true;
            }
        }

        return false;
    }

    public function getEmploymentType(): EmploymentType
    {
        return $this->employmentType;
    }

    public function getEmployeeSkillPaths(): Collection
    {
        return $this->employeeSkillPaths;
    }

    public function addEmployeeSkillPath(EmployeeSkillPath $employeeSkillPath): self
    {
        if (!$this->employeeSkillPaths->contains($employeeSkillPath)) {
            $this->employeeSkillPaths->add($employeeSkillPath);
            $employeeSkillPath->setUser($this);
        }
        return $this;
    }

    public function removeEmployeeSkillPath(EmployeeSkillPath $employeeSkillPath): self
    {
        $this->employeeSkillPaths->removeElement($employeeSkillPath);
        return $this;
    }

    public function getEmployeeSkills(): Collection
    {
        return $this->employeeSkills;
    }

    public function addEmployeeSkill(EmployeeSkill $employeeSkill): self
    {
        if (!$this->employeeSkills->contains($employeeSkill)) {
            $this->employeeSkills->add($employeeSkill);
            $employeeSkill->setUser($this);
        }
        return $this;
    }

    public function removeEmployeeSkill(EmployeeSkill $employeeSkill): self
    {
        $this->employeeSkills->removeElement($employeeSkill);
        return $this;
    }

    public function hasSkillPath(EmployeeSkillPath $skillPath): bool
    {
        return $this->employeeSkillPaths->exists(
            fn(int $key, EmployeeSkillPath $employeeSkillPath) => $employeeSkillPath->getSkillPath() === $skillPath->getSkillPath()
        );
    }
} 