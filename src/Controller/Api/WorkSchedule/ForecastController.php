<?php

declare(strict_types=1);

namespace App\Controller\Api\WorkSchedule;

use App\Domain\Forecast\Service\ForecastService;
use App\Domain\Forecast\ValueObject\ForecastPeriod;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Work Schedule')]
#[Route('/api/work-schedule/forecasts', name: 'api_work_schedule_forecasts_')]
class ForecastController extends AbstractController
{
    public function __construct(
        private readonly ForecastService $forecastService
    ) {
    }

    #[Route('', name: 'get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/work-schedule/forecasts',
        summary: 'Get forecast for a given period',
        tags: ['Work Schedule']
    )]
    #[OA\Parameter(
        name: 'start_date',
        in: 'query',
        description: 'Start date for forecast (Y-m-d)',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'end_date',
        in: 'query',
        description: 'End date for forecast (Y-m-d)',
        required: true,
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'strategy',
        in: 'query',
        description: 'Forecast strategy to use',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns forecast data',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    additionalProperties: new OA\AdditionalProperties(
                        additionalProperties: new OA\AdditionalProperties(
                            properties: [
                                new OA\Property(
                                    property: 'hours',
                                    type: 'object',
                                    additionalProperties: new OA\AdditionalProperties(
                                        properties: [
                                            new OA\Property(property: 'required_employees', type: 'integer'),
                                            new OA\Property(property: 'metadata', type: 'object')
                                        ]
                                    )
                                )
                            ]
                        )
                    )
                ),
                new OA\Property(
                    property: 'meta',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'start_date', type: 'string', format: 'date'),
                        new OA\Property(property: 'end_date', type: 'string', format: 'date'),
                        new OA\Property(property: 'strategy', type: 'string'),
                        new OA\Property(
                            property: 'available_strategies',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(type: 'string')
                        )
                    ]
                )
            ]
        )
    )]
    public function getForecast(Request $request): JsonResponse
    {
        $startDate = new \DateTime($request->query->get('start_date'));
        $endDate = new \DateTime($request->query->get('end_date'));
        $strategy = $request->query->get('strategy');

        $period = new ForecastPeriod($startDate, $endDate);
        $result = $this->forecastService->forecast($period, $strategy);

        $formattedData = [];
        foreach ($result->getDemands() as $demand) {
            $date = $demand->getDate()->format('Y-m-d');
            $skillPath = $demand->getSkillPath()->getSkillPath()->getName();
            $hour = $demand->getHour();

            if (!isset($formattedData[$date])) {
                $formattedData[$date] = [];
            }
            if (!isset($formattedData[$date][$skillPath])) {
                $formattedData[$date][$skillPath] = [
                    'hours' => []
                ];
            }

            $formattedData[$date][$skillPath]['hours'][$hour] = [
                'required_employees' => $demand->getRequiredEmployees(),
                'metadata' => $demand->getMetadata()
            ];
        }

        return new JsonResponse([
            'data' => $formattedData,
            'meta' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'strategy' => $strategy ?? 'default',
                'available_strategies' => $this->forecastService->getAvailableStrategies(),
            ],
        ]);
    }

    #[Route('/strategies', name: 'get_strategies', methods: ['GET'])]
    #[OA\Get(
        path: '/api/work-schedule/forecasts/strategies',
        summary: 'Get available forecast strategies',
        tags: ['Work Schedule']
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns available forecast strategies',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'strategies',
                    type: 'array',
                    items: new OA\Items(type: 'string')
                )
            ]
        )
    )]
    public function getStrategies(): JsonResponse
    {
        return new JsonResponse([
            'strategies' => $this->forecastService->getAvailableStrategies(),
        ]);
    }
} 