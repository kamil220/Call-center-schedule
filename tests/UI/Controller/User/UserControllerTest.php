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
    
    public function testListUsersWithFiltersRequiresAuthentication(): void
    {
        // Act
        $this->client->request('GET', '/api/users?firstName=John&active=true&page=0&limit=10');
        
        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
    
    /**
     * @group functionalnot
     */
    public function testListUsersWithFiltersAndPagination(): void
    {
        // Ten test wymaga rzeczywistego środowiska uwierzytelniania
        $this->markTestSkipped('Ten test wymaga pełnej integracji z JWT, która nie jest dostępna w testach jednostkowych');
        
        // Arrange
        $this->authenticateAsAdmin();
        
        // Act - test paginacji i filtrowania
        $this->client->request('GET', '/api/users?firstName=John&active=true&page=0&limit=10&sortBy=email&sortDirection=ASC');
        
        // Assert
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Sprawdzenie struktury odpowiedzi paginowanej
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('page', $responseData);
        $this->assertArrayHasKey('limit', $responseData);
        $this->assertArrayHasKey('totalPages', $responseData);
        
        // Sprawdzenie wartości paginacji
        $this->assertSame(0, $responseData['page']);
        $this->assertSame(10, $responseData['limit']);
        
        // Sprawdzenie czy filtrowanie działa poprawnie
        if (count($responseData['items']) > 0) {
            foreach ($responseData['items'] as $user) {
                $this->assertStringContainsString('John', $user['firstName']);
                $this->assertTrue($user['active']);
            }
        }
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