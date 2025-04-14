<?php

declare(strict_types=1);

namespace App\Tests\UI\Controller\User;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\UserId;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    
    protected function setUp(): void
    {
        $this->client = self::createClient();
    }
    
    public function testListUsersRequiresAuthentication(): void
    {
        // Act
        $this->client->request('GET', '/api/users');
        
        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
    
    public function testCreateUserRequiresAuthentication(): void
    {
        // Act
        $this->client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'new@example.com',
            'password' => 'password123',
            'firstName' => 'New',
            'lastName' => 'User',
            'roles' => [User::ROLE_AGENT],
        ]));
        
        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
    
    /**
     * @group functionalnot
     */
    public function testCreateUserWithAdminRole(): void
    {
        // Ten test wymaga rzeczywistego środowiska uwierzytelniania
        $this->markTestSkipped('Ten test wymaga pełnej integracji z JWT, która nie jest dostępna w testach jednostkowych');
    }
    
    /**
     * @group functionalnot
     */
    public function testGetUserWithAdminRole(): void
    {
        // Ten test wymaga rzeczywistego środowiska uwierzytelniania
        $this->markTestSkipped('Ten test wymaga pełnej integracji z JWT, która nie jest dostępna w testach jednostkowych');
    }
    
    private function authenticateAsAdmin(): void
    {
        // Metoda mockowania autentykacji - ta część musiałaby zostać odpowiednio dostosowana
        // dla prawdziwego środowiska testowego z JWT
        $this->client->setServerParameter('HTTP_Authorization', 'Bearer MOCKED_TOKEN_FOR_TESTS');
    }
} 