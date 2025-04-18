<?php

declare(strict_types=1);

namespace App\UI\Controller\Calendar;

use App\Domain\Calendar\Service\HolidayProvider\HolidayProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/calendar')]
#[OA\Tag(name: 'Calendar', description: 'Calendar operations including holidays management')]
final class CalendarController extends AbstractController
{
    public function __construct(
        private readonly HolidayProviderInterface $holidayProvider
    ) {
    }

    #[Route('/holidays/{year}', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Tag(name: 'Calendar')]
    #[OA\Get(
        path: '/api/v1/calendar/holidays/{year}',
        summary: 'Get holidays for a specific year',
        description: 'Retrieves a list of holidays (both fixed and movable) for the specified year. Supports different countries through country parameter.'
    )]
    #[OA\Parameter(
        name: 'year',
        description: 'Year to get holidays for',
        in: 'path',
        required: true,
        schema: new OA\Schema(
            type: 'integer',
            minimum: 1900,
            maximum: 2100,
            example: 2024
        )
    )]
    #[OA\Parameter(
        name: 'country',
        description: 'Country code (e.g. PL for Poland)',
        in: 'query',
        required: false,
        schema: new OA\Schema(
            type: 'string',
            default: 'PL',
            example: 'PL',
            minLength: 2,
            maxLength: 2
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns list of holidays for given year',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(
                        property: 'date',
                        type: 'string',
                        format: 'date',
                        example: '2024-01-01',
                        description: 'Holiday date in Y-m-d format'
                    ),
                    new OA\Property(
                        property: 'type',
                        type: 'string',
                        enum: ['fixed', 'movable'],
                        example: 'fixed',
                        description: 'Type of holiday - fixed (same date every year) or movable (date changes each year)'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        example: 'New Year',
                        description: 'Name or description of the holiday'
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad Request - Invalid year format or unsupported country',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'error',
                    type: 'string',
                    example: 'Year must be between 1900 and 2100'
                ),
                new OA\Property(
                    property: 'supported_countries',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['PL'],
                    description: 'List of supported country codes'
                )
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized - JWT token is missing or invalid',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'error',
                    type: 'string',
                    example: 'JWT Token not found'
                )
            ]
        )
    )]
    public function getHolidays(string $year, Request $request): JsonResponse
    {
        if (!is_numeric($year)) {
            return new JsonResponse([
                'error' => 'Invalid year format'
            ], 400);
        }

        $yearInt = (int) $year;
        if ($yearInt < 1900 || $yearInt > 2100) {
            return new JsonResponse([
                'error' => 'Year must be between 1900 and 2100'
            ], 400);
        }

        $country = $request->query->get('country', 'PL');

        if (!$this->holidayProvider->supports($country)) {
            return new JsonResponse([
                'error' => 'Country not supported',
                'supported_countries' => ['PL']
            ], 400);
        }

        $holidays = $this->holidayProvider->getHolidaysForYear($yearInt);

        return new JsonResponse($holidays);
    }
} 