<?php

declare(strict_types=1);

namespace App\Tests\Integration\UI\Controller\WorkSchedule;

use App\Domain\Employee\Entity\EmployeeSkillPath;
use App\Domain\Employee\Entity\SkillPath;
use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\EmploymentType;
use App\Domain\User\ValueObject\UserId;
use App\Domain\WorkSchedule\Entity\WorkSchedule;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class WorkScheduleControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private User $user;
    private EmployeeSkillPath $skillPath;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        // Create test user
        $this->user = new User(
            new UserId(Uuid::uuid4()),
            'test@example.com',
            'John',
            'Doe',
            EmploymentType::EMPLOYMENT_CONTRACT
        );
        $this->user->setPassword('password123');
        $this->entityManager->persist($this->user);

        // Create skill path
        $skillPathEntity = new SkillPath('Test Skill Path');
        $this->entityManager->persist($skillPathEntity);

        $this->skillPath = new EmployeeSkillPath($this->user, $skillPathEntity);
        $this->entityManager->persist($this->skillPath);
        $this->user->addEmployeeSkillPath($this->skillPath);

        $this->entityManager->flush();

        // Log in the user
        $this->client->loginUser($this->user);
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
        parent::tearDown();
    }

    public function testCreateSchedule(): void
    {
        // Arrange
        $data = [
            'skillPathId' => $this->skillPath->getId(),
            'date' => '2024-03-20',
            'startTime' => '09:00',
            'endTime' => '17:00',
            'notes' => 'Test schedule'
        ];

        // Act
        $this->client->request(
            'POST',
            '/api/work-schedules',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Work schedule created successfully', $response['message']);

        // Verify database
        $schedule = $this->entityManager->getRepository(WorkSchedule::class)->find($response['id']);
        $this->assertNotNull($schedule);
        $this->assertEquals($this->user->getId(), $schedule->getUser()->getId());
        $this->assertEquals($this->skillPath->getId(), $schedule->getSkillPath()->getId());
        $this->assertEquals('2024-03-20', $schedule->getDate()->format('Y-m-d'));
        $this->assertEquals('09:00', $schedule->getTimeRange()->getStartTime()->format('H:i'));
        $this->assertEquals('17:00', $schedule->getTimeRange()->getEndTime()->format('H:i'));
        $this->assertEquals('Test schedule', $schedule->getNotes());
    }

    public function testListSchedules(): void
    {
        // Arrange
        $schedule = new WorkSchedule(
            $this->user,
            $this->skillPath,
            new DateTimeImmutable('2024-03-20'),
            new TimeRange(
                new DateTimeImmutable('2024-03-20 09:00:00'),
                new DateTimeImmutable('2024-03-20 17:00:00')
            ),
            'Test schedule'
        );
        $this->entityManager->persist($schedule);
        $this->entityManager->flush();

        // Act
        $this->client->request(
            'GET',
            '/api/work-schedules',
            [
                'startDate' => '2024-03-01',
                'endDate' => '2024-03-31'
            ]
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('schedules', $response);
        $this->assertCount(1, $response['schedules']);

        $scheduleData = $response['schedules'][0];
        $this->assertEquals($schedule->getId(), $scheduleData['id']);
        $this->assertEquals('2024-03-20', $scheduleData['date']);
        $this->assertEquals('09:00', $scheduleData['startTime']);
        $this->assertEquals('17:00', $scheduleData['endTime']);
        $this->assertEquals('Test schedule', $scheduleData['notes']);
    }

    public function testUpdateSchedule(): void
    {
        // Arrange
        $schedule = new WorkSchedule(
            $this->user,
            $this->skillPath,
            new DateTimeImmutable('2024-03-20'),
            new TimeRange(
                new DateTimeImmutable('2024-03-20 09:00:00'),
                new DateTimeImmutable('2024-03-20 17:00:00')
            ),
            'Test schedule'
        );
        $this->entityManager->persist($schedule);
        $this->entityManager->flush();

        $data = [
            'skillPathId' => $this->skillPath->getId(),
            'startTime' => '10:00',
            'endTime' => '18:00',
            'notes' => 'Updated schedule'
        ];

        // Act
        $this->client->request(
            'PUT',
            '/api/work-schedules/' . $schedule->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Work schedule updated successfully', $response['message']);

        // Verify database
        $this->entityManager->refresh($schedule);
        $this->assertEquals('10:00', $schedule->getTimeRange()->getStartTime()->format('H:i'));
        $this->assertEquals('18:00', $schedule->getTimeRange()->getEndTime()->format('H:i'));
        $this->assertEquals('Updated schedule', $schedule->getNotes());
    }

    public function testDeleteSchedule(): void
    {
        // Arrange
        $schedule = new WorkSchedule(
            $this->user,
            $this->skillPath,
            new DateTimeImmutable('2024-03-20'),
            new TimeRange(
                new DateTimeImmutable('2024-03-20 09:00:00'),
                new DateTimeImmutable('2024-03-20 17:00:00')
            ),
            'Test schedule'
        );
        $this->entityManager->persist($schedule);
        $this->entityManager->flush();

        // Act
        $this->client->request('DELETE', '/api/work-schedules/' . $schedule->getId());

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Work schedule deleted successfully', $response['message']);

        // Verify database
        $deletedSchedule = $this->entityManager->getRepository(WorkSchedule::class)->find($schedule->getId());
        $this->assertNull($deletedSchedule);
    }

    public function testCreateScheduleWithInvalidData(): void
    {
        // Arrange
        $data = [
            'date' => '2024-03-20',
            'startTime' => '09:00'
            // Missing required fields
        ];

        // Act
        $this->client->request(
            'POST',
            '/api/work-schedules',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Missing required fields', $response['message']);
    }

    public function testUpdateNonExistentSchedule(): void
    {
        // Arrange
        $data = [
            'skillPathId' => $this->skillPath->getId(),
            'startTime' => '10:00',
            'endTime' => '18:00'
        ];

        // Act
        $this->client->request(
            'PUT',
            '/api/work-schedules/' . Uuid::uuid4(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Work schedule not found', $response['message']);
    }

    public function testUpdateScheduleWithInvalidData(): void
    {
        // Arrange
        $schedule = new WorkSchedule(
            $this->user,
            $this->skillPath,
            new DateTimeImmutable('2024-03-20'),
            new TimeRange(
                new DateTimeImmutable('2024-03-20 09:00:00'),
                new DateTimeImmutable('2024-03-20 17:00:00')
            )
        );
        $this->entityManager->persist($schedule);
        $this->entityManager->flush();

        $data = [
            'startTime' => '10:00'
            // Missing required fields
        ];

        // Act
        $this->client->request(
            'PUT',
            '/api/work-schedules/' . $schedule->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Missing required fields', $response['message']);
    }
} 