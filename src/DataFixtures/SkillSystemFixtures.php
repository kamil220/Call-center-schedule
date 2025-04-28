<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Employee\Entity\EmployeeSkill;
use App\Domain\Employee\Entity\EmployeeSkillPath;
use App\Domain\Employee\Entity\Skill;
use App\Domain\Employee\Entity\SkillPath;
use App\Domain\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SkillSystemFixtures extends Fixture implements DependentFixtureInterface
{
    public const CUSTOMER_SERVICE_PATH_REFERENCE = 'skill-path-customer-service';
    public const SALES_PATH_REFERENCE = 'skill-path-sales';
    public const TECHNICAL_PATH_REFERENCE = 'skill-path-technical';
    public const ADMINISTRATION_PATH_REFERENCE = 'skill-path-administration';
    public const SKILL_REFERENCE_PREFIX = 'skill-';

    public function load(ObjectManager $manager): void
    {
        $skillPathRepository = $manager->getRepository(SkillPath::class);
        $skillRepository = $manager->getRepository(Skill::class);

        // Create skill paths if they don't exist
        $customerServicePath = $skillPathRepository->findOneBy(['name' => 'Customer Service']) 
            ?? new SkillPath('Customer Service');
        $salesPath = $skillPathRepository->findOneBy(['name' => 'Sales']) 
            ?? new SkillPath('Sales');
        $technicalPath = $skillPathRepository->findOneBy(['name' => 'Technical']) 
            ?? new SkillPath('Technical');
        $administrationPath = $skillPathRepository->findOneBy(['name' => 'Administration']) 
            ?? new SkillPath('Administration');

        $manager->persist($customerServicePath);
        $manager->persist($salesPath);
        $manager->persist($technicalPath);
        $manager->persist($administrationPath);

        $this->addReference(self::CUSTOMER_SERVICE_PATH_REFERENCE, $customerServicePath);
        $this->addReference(self::SALES_PATH_REFERENCE, $salesPath);
        $this->addReference(self::TECHNICAL_PATH_REFERENCE, $technicalPath);
        $this->addReference(self::ADMINISTRATION_PATH_REFERENCE, $administrationPath);

        // Create skills
        $skillsData = [
            // Customer Service skills
            ['name' => 'Phone Support', 'path' => $customerServicePath, 'ref' => self::CUSTOMER_SERVICE_PATH_REFERENCE],
            ['name' => 'Email Support', 'path' => $customerServicePath, 'ref' => self::CUSTOMER_SERVICE_PATH_REFERENCE],
            ['name' => 'Chat Support', 'path' => $customerServicePath, 'ref' => self::CUSTOMER_SERVICE_PATH_REFERENCE],
            
            // Sales skills
            ['name' => 'Product Knowledge', 'path' => $salesPath, 'ref' => self::SALES_PATH_REFERENCE],
            ['name' => 'Negotiation', 'path' => $salesPath, 'ref' => self::SALES_PATH_REFERENCE],
            ['name' => 'Lead Generation', 'path' => $salesPath, 'ref' => self::SALES_PATH_REFERENCE],
            
            // Technical skills
            ['name' => 'Hardware Support', 'path' => $technicalPath, 'ref' => self::TECHNICAL_PATH_REFERENCE],
            ['name' => 'Software Support', 'path' => $technicalPath, 'ref' => self::TECHNICAL_PATH_REFERENCE],
            ['name' => 'Network Support', 'path' => $technicalPath, 'ref' => self::TECHNICAL_PATH_REFERENCE],
            
            // Administration skills
            ['name' => 'Documentation', 'path' => $administrationPath, 'ref' => self::ADMINISTRATION_PATH_REFERENCE],
            ['name' => 'Quality Assurance', 'path' => $administrationPath, 'ref' => self::ADMINISTRATION_PATH_REFERENCE],
            ['name' => 'Team Management', 'path' => $administrationPath, 'ref' => self::ADMINISTRATION_PATH_REFERENCE],
        ];

        $skills = [];
        foreach ($skillsData as $skillData) {
            $skill = $skillRepository->findOneBy(['name' => $skillData['name']]) 
                ?? new Skill($skillData['name'], $skillData['path']);
            $manager->persist($skill);
            $skills[] = $skill;
            // Add reference for each skill using a consistent format
            $this->addReference(
                self::SKILL_REFERENCE_PREFIX . $skillData['ref'] . '-' . $skillData['name'],
                $skill
            );
        }

        // Assign skills to agents
        for ($i = 0; $i < 10; $i++) {
            /** @var User $agent */
            $agent = $this->getReference(sprintf('%s-%d', UserFixtures::AGENT_USER_REFERENCE, $i), User::class);
            
            // Ensure each agent has at least 3 random skills
            $shuffledSkills = $skills;
            shuffle($shuffledSkills);
            
            // Assign minimum 3 random skills
            for ($j = 0; $j < 3; $j++) {
                $employeeSkill = new EmployeeSkill($agent, $shuffledSkills[$j], random_int(1, 5));
                $manager->persist($employeeSkill);
            }
            
            // Randomly assign additional skills
            for ($j = 3; $j < count($shuffledSkills); $j++) {
                if (random_int(0, 1)) {
                    $employeeSkill = new EmployeeSkill($agent, $shuffledSkills[$j], random_int(1, 5));
                    $manager->persist($employeeSkill);
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
} 