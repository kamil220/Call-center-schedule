<?php

declare(strict_types=1);

namespace App\Tests\UI\Controller\Calendar;

use App\Tests\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CalendarControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    public function testGetHolidaysUnauthorized(): void
    {
        $this->client->request('GET', '/api/v1/calendar/holidays/2024');

        $response = $this->client->getResponse();

        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testGetHolidays(): void
    {
        $user = $this->createUser(['ROLE_AGENT']);
        $this->loginUser($user);
        
        $this->client->request('GET', '/api/v1/calendar/holidays/2024', [], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ], null);

        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);

        self::assertEquals(Response::HTTP_OK, $response->getStatusCode());
        self::assertIsArray($content);
        self::assertNotEmpty($content);
        self::assertArrayHasKey('date', $content[0]);
        self::assertArrayHasKey('type', $content[0]);
        self::assertArrayHasKey('description', $content[0]);
    }

    public function testGetHolidaysForUnsupportedCountry(): void
    {
        $user = $this->createUser(['ROLE_AGENT']);
        $this->loginUser($user);
        
        $this->client->request('GET', '/api/v1/calendar/holidays/2024?country=XX');

        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);

        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $content);
        self::assertArrayHasKey('supported_countries', $content);
    }

    public function testGetHolidaysForInvalidYear(): void
    {
        $user = $this->createUser(['ROLE_AGENT']);
        $this->loginUser($user);
        
        $this->client->request('GET', '/api/v1/calendar/holidays/invalid');

        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);

        self::assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $content);
    }
} 