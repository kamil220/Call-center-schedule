<?php

declare(strict_types=1);

namespace App\Tests\DataFixtures;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\EmploymentType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'admin-user';
    public const PLANNER_USER_REFERENCE = 'planner-user';
    public const TEAM_MANAGER_USER_REFERENCE = 'team-manager-user';
    public const AGENT_USER_REFERENCE = 'agent-user';
    
    private UserPasswordHasherInterface $passwordHasher;
    
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    
    public function load(ObjectManager $manager): void
    {
        $this->createAdmin($manager);
        $this->createPlanner($manager);
        $this->createTeamManager($manager);
        $this->createAgent($manager);
        
        $manager->flush();
    }
    
    private function createAdmin(ObjectManager $manager): void
    {
        $user = new User(
            UserId::generate(),
            'admin@example.com',
            'Admin',
            'User',
            EmploymentType::EMPLOYMENT_CONTRACT
        );
        
        $user->setPassword($this->passwordHasher->hashPassword($user, 'admin123'));
        $user->addRole(User::ROLE_ADMIN);
        
        $manager->persist($user);
        $this->addReference(self::ADMIN_USER_REFERENCE, $user);
    }
    
    private function createPlanner(ObjectManager $manager): void
    {
        $user = new User(
            UserId::generate(),
            'planner@example.com',
            'Planner',
            'User',
            EmploymentType::EMPLOYMENT_CONTRACT
        );
        
        $user->setPassword($this->passwordHasher->hashPassword($user, 'planner123'));
        $user->addRole(User::ROLE_PLANNER);
        
        $manager->persist($user);
        $this->addReference(self::PLANNER_USER_REFERENCE, $user);
    }
    
    private function createTeamManager(ObjectManager $manager): void
    {
        $user = new User(
            UserId::generate(),
            'manager@example.com',
            'Team',
            'Manager',
            EmploymentType::EMPLOYMENT_CONTRACT
        );
        
        $user->setPassword($this->passwordHasher->hashPassword($user, 'manager123'));
        $user->addRole(User::ROLE_TEAM_MANAGER);
        
        $manager->persist($user);
        $this->addReference(self::TEAM_MANAGER_USER_REFERENCE, $user);
    }
    
    private function createAgent(ObjectManager $manager): void
    {
        $user = new User(
            UserId::generate(),
            'agent@example.com',
            'Call',
            'Agent',
            EmploymentType::EMPLOYMENT_CONTRACT
        );
        
        $user->setPassword($this->passwordHasher->hashPassword($user, 'agent123'));
        $user->addRole(User::ROLE_AGENT);
        
        $manager->persist($user);
        $this->addReference(self::AGENT_USER_REFERENCE, $user);
    }
} 