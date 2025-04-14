<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\User\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\User\Repository\DoctrineUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DoctrineUserRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?DoctrineUserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineUserRepository($this->entityManager);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
        $this->repository = null;
    }

    public function testSave(): void
    {
        // Arrange
        $userId = UserId::generate();
        $user = new User($userId, 'test@example.com', 'Test', 'User');
        $user->setPassword('password123');

        // Act
        $this->repository->save($user);

        // Assert
        $savedUser = $this->repository->findById($userId);
        $this->assertNotNull($savedUser);
        $this->assertEquals('test@example.com', $savedUser->getEmail());
        $this->assertEquals('Test', $savedUser->getFirstName());
        $this->assertEquals('User', $savedUser->getLastName());
    }

    public function testFindByEmail(): void
    {
        // Arrange
        $email = 'find-by-email@example.com';
        $userId = UserId::generate();
        $user = new User($userId, $email, 'Find', 'ByEmail');
        $user->setPassword('password123');
        $this->repository->save($user);

        // Act
        $foundUser = $this->repository->findByEmail($email);

        // Assert
        $this->assertNotNull($foundUser);
        $this->assertEquals($userId->toString(), $foundUser->getId());
        $this->assertEquals($email, $foundUser->getEmail());
    }

    public function testFindAll(): void
    {
        // Act
        $users = $this->repository->findAll();

        // Assert
        $this->assertIsArray($users);
        // We can only assert that the array exists, not its contents,
        // since other tests might have added users to the database
    }

    public function testRemove(): void
    {
        // Arrange
        $userId = UserId::generate();
        $email = 'to-remove@example.com';
        $user = new User($userId, $email, 'To', 'Remove');
        $user->setPassword('password123');
        $this->repository->save($user);

        // Verify user was saved
        $savedUser = $this->repository->findById($userId);
        $this->assertNotNull($savedUser);

        // Act
        $this->repository->remove($savedUser);

        // Assert
        $deletedUser = $this->repository->findById($userId);
        $this->assertNull($deletedUser);
    }
} 