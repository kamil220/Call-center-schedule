<?php

declare(strict_types=1);

namespace App\Tests\UI\Controller\User;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
{
    public function testLoginEndpointExists(): void
    {
        $client = self::createClient();
        
        // Act - sprawdzamy, czy endpoint istnieje, nawet jeśli zwraca błąd
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]));
        
        // Assert - nie oczekujemy 404, ale raczej 401 (nieuprawniony)
        // To potwierdza, że endpoint istnieje
        $this->assertNotEquals(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }
    
    public function testMeEndpointRequiresAuthentication(): void
    {
        $client = self::createClient();
        
        // Act - próba dostępu do /me bez uwierzytelnienia
        $client->request('GET', '/api/auth/me');
        
        // Assert - oczekujemy odpowiedzi 401 (nieuprawniony)
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
} 