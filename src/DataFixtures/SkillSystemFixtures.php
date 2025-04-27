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
    public function load(ObjectManager $manager): void
    {
        // Create Skill Paths
        $customerServicePath = new SkillPath('Customer Service');
        $salesPath = new SkillPath('Sales');
        $technicalPath = new SkillPath('Technical');
        $administrationPath = new SkillPath('Administration');

        $manager->persist($customerServicePath);
        $manager->persist($salesPath);
        $manager->persist($technicalPath);
        $manager->persist($administrationPath);

        // Create Skills for Customer Service
        $basicCustomerService = new Skill('Basic Customer Service', $customerServicePath);
        $complaintResolution = new Skill('Complaint Resolution', $customerServicePath);
        $phoneSupport = new Skill('Phone Support', $customerServicePath);

        $manager->persist($basicCustomerService);
        $manager->persist($complaintResolution);
        $manager->persist($phoneSupport);

        // Create Skills for Sales
        $medicalProductsSales = new Skill('Medical Products Sales', $salesPath);
        $leadGeneration = new Skill('Lead Generation', $salesPath);
        $contractNegotiation = new Skill('Contract Negotiation', $salesPath);

        $manager->persist($medicalProductsSales);
        $manager->persist($leadGeneration);
        $manager->persist($contractNegotiation);

        // Create Skills for Technical
        $crmSystems = new Skill('CRM Systems', $technicalPath);
        $technicalDocumentation = new Skill('Technical Documentation', $technicalPath);
        $dataAnalysis = new Skill('Data Analysis', $technicalPath);

        $manager->persist($crmSystems);
        $manager->persist($technicalDocumentation);
        $manager->persist($dataAnalysis);

        // Create Skills for Administration
        $projectManagement = new Skill('Project Management', $administrationPath);
        $teamCoordination = new Skill('Team Coordination', $administrationPath);

        $manager->persist($projectManagement);
        $manager->persist($teamCoordination);

        // Assign skills to existing agents
        for ($i = 0; $i < 10; $i++) {
            /** @var User $agent */
            $agent = $this->getReference(sprintf('%s-%d', UserFixtures::AGENT_USER_REFERENCE, $i), User::class);

            // Assign random skill paths to agent
            $skillPaths = [$customerServicePath];
            if ($i % 2 === 0) {
                $skillPaths[] = $salesPath;
            }
            if ($i % 3 === 0) {
                $skillPaths[] = $technicalPath;
            }
            if ($i % 4 === 0) {
                $skillPaths[] = $administrationPath;
            }

            foreach ($skillPaths as $skillPath) {
                $manager->persist(new EmployeeSkillPath($agent, $skillPath));
            }

            // Assign skills with levels based on skill paths
            if (in_array($customerServicePath, $skillPaths)) {
                $manager->persist(new EmployeeSkill($agent, $basicCustomerService, random_int(3, 5)));
                $manager->persist(new EmployeeSkill($agent, $complaintResolution, random_int(2, 5)));
                $manager->persist(new EmployeeSkill($agent, $phoneSupport, random_int(3, 5)));
            }

            if (in_array($salesPath, $skillPaths)) {
                $manager->persist(new EmployeeSkill($agent, $medicalProductsSales, random_int(2, 5)));
                $manager->persist(new EmployeeSkill($agent, $leadGeneration, random_int(2, 4)));
                $manager->persist(new EmployeeSkill($agent, $contractNegotiation, random_int(1, 4)));
            }

            if (in_array($technicalPath, $skillPaths)) {
                $manager->persist(new EmployeeSkill($agent, $crmSystems, random_int(2, 5)));
                $manager->persist(new EmployeeSkill($agent, $technicalDocumentation, random_int(2, 4)));
                $manager->persist(new EmployeeSkill($agent, $dataAnalysis, random_int(1, 4)));
            }

            if (in_array($administrationPath, $skillPaths)) {
                $manager->persist(new EmployeeSkill($agent, $projectManagement, random_int(2, 4)));
                $manager->persist(new EmployeeSkill($agent, $teamCoordination, random_int(2, 5)));
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