<?php

declare(strict_types=1);

namespace App\UI\Controller\WorkSchedule;

use App\Domain\Employee\Entity\EmployeeSkillPath;
use App\Domain\WorkSchedule\Exception\InvalidWorkScheduleException;
use App\Domain\WorkSchedule\Service\WorkScheduleService;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Work Schedule')]
#[Route('/api/work-schedules', name: 'api_work_schedules_')]
class WorkScheduleController extends AbstractController
{
    public function __construct(
        private readonly WorkScheduleService $workScheduleService
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/work-schedules',
        summary: 'Create a new work schedule entry',
        tags: ['Work Schedule'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['skillPathId', 'date', 'startTime', 'endTime'],
                properties: [
                    new OA\Property(property: 'skillPathId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'date', type: 'string', format: 'date'),
                    new OA\Property(property: 'startTime', type: 'string', format: 'time'),
                    new OA\Property(property: 'endTime', type: 'string', format: 'time'),
                    new OA\Property(property: 'notes', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Work schedule created successfully'),
            new OA\Response(response: 400, description: 'Invalid input'),
            new OA\Response(response: 403, description: 'Access denied')
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['skillPathId'], $data['date'], $data['startTime'], $data['endTime'])) {
            return $this->json(['message' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $date = new DateTimeImmutable($data['date']);
            $startTime = new DateTimeImmutable($data['startTime']);
            $endTime = new DateTimeImmutable($data['endTime']);
            $timeRange = new TimeRange($startTime, $endTime);

            $schedule = $this->workScheduleService->createSchedule(
                $this->getUser(),
                $data['skillPathId'],
                $date,
                $timeRange,
                $data['notes'] ?? null
            );

            return $this->json([
                'id' => $schedule->getId(),
                'message' => 'Work schedule created successfully'
            ], Response::HTTP_CREATED);
        } catch (InvalidWorkScheduleException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['message' => 'An error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/work-schedules',
        summary: 'Get work schedules for a date range',
        tags: ['Work Schedule'],
        parameters: [
            new OA\Parameter(
                name: 'startDate',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'endDate',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Returns work schedules for the specified date range'
            )
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $startDate = new DateTimeImmutable($request->query->get('startDate'));
        $endDate = new DateTimeImmutable($request->query->get('endDate'));

        $schedules = $this->workScheduleService->getSchedulesByUser(
            $this->getUser(),
            $startDate,
            $endDate
        );

        return $this->json([
            'schedules' => array_map(
                fn($schedule) => [
                    'id' => $schedule->getId(),
                    'date' => $schedule->getDate()->format('Y-m-d'),
                    'startTime' => $schedule->getTimeRange()->getStartTime()->format('H:i'),
                    'endTime' => $schedule->getTimeRange()->getEndTime()->format('H:i'),
                    'skillPath' => [
                        'id' => $schedule->getSkillPath()->getId(),
                        'name' => $schedule->getSkillPath()->getSkillPath()->getName()
                    ],
                    'notes' => $schedule->getNotes()
                ],
                $schedules
            )
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/work-schedules/{id}',
        summary: 'Update a work schedule entry',
        tags: ['Work Schedule'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['skillPathId', 'startTime', 'endTime'],
                properties: [
                    new OA\Property(property: 'skillPathId', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'startTime', type: 'string', format: 'time'),
                    new OA\Property(property: 'endTime', type: 'string', format: 'time'),
                    new OA\Property(property: 'notes', type: 'string', nullable: true)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Work schedule updated successfully'),
            new OA\Response(response: 404, description: 'Work schedule not found')
        ]
    )]
    public function update(string $id, Request $request): JsonResponse
    {
        $schedule = $this->workScheduleService->findById($id);

        if (!$schedule) {
            return $this->json(['message' => 'Work schedule not found'], Response::HTTP_NOT_FOUND);
        }

        if ($schedule->getUser() !== $this->getUser()) {
            return $this->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['skillPathId'], $data['startTime'], $data['endTime'])) {
            return $this->json(['message' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startTime = new DateTimeImmutable($data['startTime']);
            $endTime = new DateTimeImmutable($data['endTime']);
            $timeRange = new TimeRange($startTime, $endTime);

            $this->workScheduleService->updateSchedule(
                $schedule,
                $data['skillPathId'],
                $timeRange,
                $data['notes'] ?? null
            );

            return $this->json(['message' => 'Work schedule updated successfully']);
        } catch (InvalidWorkScheduleException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['message' => 'An error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/work-schedules/{id}',
        summary: 'Delete a work schedule entry',
        tags: ['Work Schedule'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Work schedule deleted successfully'),
            new OA\Response(response: 404, description: 'Work schedule not found')
        ]
    )]
    public function delete(string $id): JsonResponse
    {
        $schedule = $this->workScheduleService->findById($id);

        if (!$schedule) {
            return $this->json(['message' => 'Work schedule not found'], Response::HTTP_NOT_FOUND);
        }

        if ($schedule->getUser() !== $this->getUser()) {
            return $this->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->workScheduleService->removeSchedule($schedule);
            return $this->json(['message' => 'Work schedule deleted successfully']);
        } catch (\Exception $e) {
            return $this->json(['message' => 'An error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 