<?php

declare(strict_types=1);

namespace App\Tests;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;

class WebTestCase extends SymfonyWebTestCase
{
    protected ?AbstractBrowser $client = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createUser(array $roles): User
    {
        $user = new User(
            UserId::generate(),
            'test@example.com',
            'Test',
            'User'
        );
        $user->setPassword('password');
        
        foreach ($roles as $role) {
            $user->addRole($role);
        }

        return $user;
    }

    protected function loginUser(User $user): void
    {
        if (!$this->client) {
            throw new \RuntimeException('Client must be created before logging in user');
        }

        // Get the JWT token manager service
        $jwtManager = static::getContainer()->get('lexik_jwt_authentication.jwt_manager');
        $token = $jwtManager->create($user);

        // Set the Authorization header with the JWT token
        $this->client->setServerParameter('HTTP_AUTHORIZATION', sprintf('Bearer %s', $token));
    }
} 