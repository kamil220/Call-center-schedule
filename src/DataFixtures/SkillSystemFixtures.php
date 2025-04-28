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
            ['name' => 'Phone Support', 'path' => $customerServicePath],
            ['name' => 'Email Support', 'path' => $customerServicePath],
            ['name' => 'Chat Support', 'path' => $customerServicePath],
            
            // Sales skills
            ['name' => 'Product Knowledge', 'path' => $salesPath],
            ['name' => 'Negotiation', 'path' => $salesPath],
            ['name' => 'Lead Generation', 'path' => $salesPath],
            
            // Technical skills
            ['name' => 'Hardware Support', 'path' => $technicalPath],
            ['name' => 'Software Support', 'path' => $technicalPath],
            ['name' => 'Network Support', 'path' => $technicalPath],
            
            // Administration skills
            ['name' => 'Documentation', 'path' => $administrationPath],
            ['name' => 'Quality Assurance', 'path' => $administrationPath],
            ['name' => 'Team Management', 'path' => $administrationPath],
        ];

        $skills = [];
        foreach ($skillsData as $skillData) {
            $skill = $skillRepository->findOneBy(['name' => $skillData['name']]) 
                ?? new Skill($skillData['name'], $skillData['path']);
            $manager->persist($skill);
            $skills[] = $skill;
        }

        // Assign skills to agents
        for ($i = 0; $i < 10; $i++) {
            /** @var User $agent */
            $agent = $this->getReference(sprintf('%s-%d', UserFixtures::AGENT_USER_REFERENCE, $i), User::class);
            
            // Assign Customer Service skills to all agents
            foreach (range(0, 2) as $skillIndex) {
                $employeeSkill = new EmployeeSkill($agent, $skills[$skillIndex], random_int(1, 5));
                $manager->persist($employeeSkill);
            }
            
            // Randomly assign other skills
            foreach (range(3, count($skills) - 1) as $skillIndex) {
                if (random_int(0, 1)) {
                    $employeeSkill = new EmployeeSkill($agent, $skills[$skillIndex], random_int(1, 5));
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