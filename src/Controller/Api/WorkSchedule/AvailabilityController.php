<?php

declare(strict_types=1);

namespace App\Controller\Api\WorkSchedule;

use App\Domain\User\Entity\User;
use App\Domain\WorkSchedule\Entity\Availability;
use App\Domain\WorkSchedule\Exception\InvalidAvailabilityException;
use App\Domain\WorkSchedule\Repository\AvailabilityRepositoryInterface;
use App\Domain\WorkSchedule\ValueObject\TimeRange;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\UserId;
use App\Domain\WorkSchedule\Repository\LeaveRequestRepositoryInterface;
use App\Domain\WorkSchedule\Service\LeaveType\LeaveTypeStrategyFactory;
use Symfony\Component\Validator\ConstraintViolationListInterface;

#[Route('/api/work-schedule/availabilities')]
#[OA\Tag(name: 'Work Schedule')]
final class AvailabilityController extends AbstractController
{
    private AvailabilityRepositoryInterface $availabilityRepository;
    private ValidatorInterface $validator;
    private iterable $availabilityStrategies;
    private UserRepositoryInterface $userRepository;
    private LeaveRequestRepositoryInterface $leaveRequestRepository;
    private LeaveTypeStrategyFactory $leaveTypeStrategyFactory;

    public function __construct(
        AvailabilityRepositoryInterface $availabilityRepository,
        ValidatorInterface $validator,
        iterable $availabilityStrategies,
        UserRepositoryInterface $userRepository,
        LeaveRequestRepositoryInterface $leaveRequestRepository,
        LeaveTypeStrategyFactory $leaveTypeStrategyFactory
    ) {
        $this->availabilityRepository = $availabilityRepository;
        $this->validator = $validator;
        $this->availabilityStrategies = $availabilityStrategies;
        $this->userRepository = $userRepository;
        $this->leaveRequestRepository = $leaveRequestRepository;
        $this->leaveTypeStrategyFactory = $leaveTypeStrategyFactory;
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    #[OA\Post(
        path: '/api/work-schedule/availabilities',
        summary: 'Create a new availability',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['startTime', 'endTime', 'userId', 'date'],
                properties: [
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2025-04-20'),
                    new OA\Property(property: 'startTime', type: 'string', format: 'time'),
                    new OA\Property(property: 'endTime', type: 'string', format: 'time'),
                    new OA\Property(property: 'userId', type: 'string', format: 'uuid'),
                    new OA\Property(
                        property: 'recurrencePattern',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'frequency', type: 'string', enum: ['DAILY', 'WEEKLY', 'MONTHLY']),
                            new OA\Property(property: 'interval', type: 'integer', minimum: 1),
                            new OA\Property(property: 'daysOfWeek', type: 'array', items: new OA\Items(type: 'integer', minimum: 0, maximum: 6)),
                            new OA\Property(property: 'daysOfMonth', type: 'array', items: new OA\Items(type: 'integer', minimum: 1, maximum: 31)),
                            new OA\Property(property: 'excludeDates', type: 'array', items: new OA\Items(type: 'string', format: 'date')),
                            new OA\Property(property: 'until', type: 'string', format: 'date')
                        ]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Availability created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'The ID of the created availability'),
                        new OA\Property(property: 'userId', type: 'string', format: 'uuid', description: 'The ID of the user'),
                        new OA\Property(property: 'date', type: 'string', format: 'date'),
                        new OA\Property(property: 'startTime', type: 'string', format: 'time'),
                        new OA\Property(property: 'endTime', type: 'string', format: 'time'),
                        new OA\Property(
                            property: 'recurrencePattern',
                            type: 'object',
                            nullable: true,
                            properties: [
                                new OA\Property(property: 'frequency', type: 'string', enum: ['DAILY', 'WEEKLY', 'MONTHLY']),
                                new OA\Property(property: 'interval', type: 'integer', minimum: 1),
                                new OA\Property(property: 'daysOfWeek', type: 'array', items: new OA\Items(type: 'integer', minimum: 0, maximum: 6)),
                                new OA\Property(property: 'daysOfMonth', type: 'array', items: new OA\Items(type: 'integer', minimum: 1, maximum: 31)),
                                new OA\Property(property: 'excludeDates', type: 'array', items: new OA\Items(type: 'string', format: 'date')),
                                new OA\Property(property: 'until', type: 'string', format: 'date', nullable: true)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid input'),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $constraints = new Assert\Collection([
            'userId' => [
                new Assert\NotBlank(),
                new Assert\Uuid(),
            ],
            'date' => [
                new Assert\NotBlank(),
                new Assert\Date()
            ],
            'startTime' => [
                new Assert\NotBlank(),
                new Assert\Regex([
                    'pattern' => '/^([01][0-9]|2[0-3]):[0-5][0-9]$/',
                    'message' => 'Start time must be in HH:mm format'
                ])
            ],
            'endTime' => [
                new Assert\NotBlank(),
                new Assert\Regex([
                    'pattern' => '/^([01][0-9]|2[0-3]):[0-5][0-9]$/',
                    'message' => 'End time must be in HH:mm format'
                ])
            ],
            'recurrencePattern' => new Assert\Optional([
                new Assert\Collection([
                    'frequency' => [
                        new Assert\NotBlank(),
                        new Assert\Choice(['daily', 'weekly', 'monthly'])
                    ],
                    'interval' => [
                        new Assert\NotBlank(),
                        new Assert\Type('integer'),
                        new Assert\Positive()
                    ],
                    'daysOfWeek' => new Assert\Optional([
                        new Assert\Type('array'),
                        new Assert\All([
                            new Assert\Choice([1, 2, 3, 4, 5, 6, 7])
                        ])
                    ]),
                    'daysOfMonth' => new Assert\Optional([
                        new Assert\Type('array'),
                        new Assert\All([
                            new Assert\Range(['min' => 1, 'max' => 31])
                        ])
                    ]),
                    'excludeDates' => new Assert\Optional([
                        new Assert\Type('array'),
                        new Assert\All([
                            new Assert\Date()
                        ])
                    ]),
                    'until' => new Assert\Optional([
                        new Assert\Date()
                    ])
                ])
            ])
        ]);

        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            return new JsonResponse(['errors' => $this->getErrorMessages($violations)], Response::HTTP_BAD_REQUEST);
        }

        try {
            /** @var User|null $user */
            $user = $this->userRepository->findById(UserId::fromString($data['userId']));

            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $date = new DateTimeImmutable($data['date']);
            $startTime = DateTimeImmutable::createFromFormat('H:i', $data['startTime']);
            $endTime = DateTimeImmutable::createFromFormat('H:i', $data['endTime']);
            
            if ($startTime === false || $endTime === false) {
                return new JsonResponse(['error' => 'Invalid time format'], Response::HTTP_BAD_REQUEST);
            }

            $timeRange = new TimeRange($startTime, $endTime);
            
            $recurrencePatternData = null;
            if (isset($data['recurrencePattern'])) {
                $recurrencePatternData = [
                    'frequency' => $data['recurrencePattern']['frequency'],
                    'interval' => $data['recurrencePattern']['interval'],
                    'daysOfWeek' => $data['recurrencePattern']['daysOfWeek'] ?? [],
                    'daysOfMonth' => $data['recurrencePattern']['daysOfMonth'] ?? [],
                    'excludeDates' => $data['recurrencePattern']['excludeDates'] ?? [],
                    'until' => isset($data['recurrencePattern']['until']) ? $data['recurrencePattern']['until'] : null
                ];
            }

            $availability = new Availability(
                Uuid::uuid4(),
                $user,
                $user->getEmploymentType(),
                $timeRange,
                $date,
                $recurrencePatternData
            );

            $this->validateAvailability($availability);
            $this->availabilityRepository->save($availability);

            return new JsonResponse(['id' => $availability->getId()], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_AGENT')]
    #[OA\Put(
        path: '/api/work-schedule/availabilities/{id}',
        summary: 'Update existing availability',
        description: 'Updates an existing availability entry. Only the owner can update their availability.',
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
                required: ['date', 'startTime', 'endTime'],
                properties: [
                    new OA\Property(property: 'date', type: 'string', format: 'date', example: '2024-04-20'),
                    new OA\Property(property: 'startTime', type: 'string', format: 'time', example: '09:00'),
                    new OA\Property(property: 'endTime', type: 'string', format: 'time', example: '17:00'),
                    new OA\Property(
                        property: 'recurrencePattern',
                        type: 'object',
                        nullable: true,
                        properties: [
                            new OA\Property(property: 'frequency', type: 'string', enum: ['daily', 'weekly', 'monthly']),
                            new OA\Property(property: 'interval', type: 'integer', minimum: 1),
                            new OA\Property(property: 'until', type: 'string', format: 'date')
                        ]
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Availability updated successfully'),
            new OA\Response(response: 400, description: 'Invalid request data'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - user is not the owner'),
            new OA\Response(response: 404, description: 'Availability not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function update(string $id, Request $request): JsonResponse
    {
        $availability = $this->availabilityRepository->findById($id);
        if (!$availability) {
            return $this->json(['error' => 'Availability not found'], Response::HTTP_NOT_FOUND);
        }

        if ($availability->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        $constraints = new Assert\Collection([
            'date' => [new Assert\NotBlank(), new Assert\Date()],
            'startTime' => [new Assert\NotBlank(), new Assert\Regex('/^([01][0-9]|2[0-3]):[0-5][0-9]$/')],
            'endTime' => [new Assert\NotBlank(), new Assert\Regex('/^([01][0-9]|2[0-3]):[0-5][0-9]$/')],
            'recurrencePattern' => new Assert\Optional([new Assert\Type('array')])
        ]);

        $violations = $this->validator->validate($data, $constraints);
        if (count($violations) > 0) {
            return $this->json(['errors' => (string) $violations], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $date = new DateTimeImmutable($data['date']);
        $startTime = DateTimeImmutable::createFromFormat('H:i', $data['startTime']);
        $endTime = DateTimeImmutable::createFromFormat('H:i', $data['endTime']);

        $startDateTime = $date->setTime((int) $startTime->format('H'), (int) $startTime->format('i'));
        $endDateTime = $date->setTime((int) $endTime->format('H'), (int) $endTime->format('i'));

        $updatedAvailability = new Availability(
            $availability->getId(),
            $availability->getUser(),
            $availability->getEmploymentType(),
            new TimeRange($startDateTime, $endDateTime),
            $date,
            $data['recurrencePattern'] ?? null
        );

        try {
            $this->validateAvailability($updatedAvailability);
            $this->availabilityRepository->save($updatedAvailability);

            return $this->json(null, Response::HTTP_OK);
        } catch (InvalidAvailabilityException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_AGENT')]
    #[OA\Delete(
        path: '/api/work-schedule/availabilities/{id}',
        summary: 'Delete availability',
        description: 'Deletes an existing availability entry. Only the owner can delete their availability.',
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
            new OA\Response(response: 204, description: 'Availability deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - user is not the owner'),
            new OA\Response(response: 404, description: 'Availability not found')
        ]
    )]
    public function delete(string $id): JsonResponse
    {
        $availability = $this->availabilityRepository->findById($id);
        if (!$availability) {
            return $this->json(['error' => 'Availability not found'], Response::HTTP_NOT_FOUND);
        }

        if ($availability->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $this->availabilityRepository->remove($availability);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    #[OA\Get(
        path: '/api/work-schedule/availabilities',
        summary: 'Get user daily schedule entries',
        description: 'Retrieves a flattened list of daily schedule entries (availability, leave, etc.) for the specified user within the date range. Recurring availabilities and multi-day leaves are expanded into individual daily records.',
        tags: ['Work Schedule'],
        parameters: [
            new OA\Parameter(
                name: 'startDate',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date'),
                example: '2024-04-01'
            ),
            new OA\Parameter(
                name: 'endDate',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date'),
                example: '2024-04-30'
            ),
            new OA\Parameter(
                name: 'userId',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                description: 'ID of the user whose schedule to retrieve. If not provided, returns current user\'s schedule. Managers can view their team members\' schedules.'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of daily schedule entries',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'date', type: 'string', format: 'date'),
                            new OA\Property(property: 'type', type: 'string', enum: ['available', 'leave'], description: 'Type of the schedule entry'),
                            new OA\Property(
                                property: 'meta',
                                type: 'object',
                                description: 'Details specific to the entry type',
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', description: 'ID of the original Availability or LeaveRequest record'),
                                    // --- Meta for 'available' type ---
                                    new OA\Property(property: 'startTime', type: 'string', format: 'time', nullable: true, description: 'Start time (for type=available)'),
                                    new OA\Property(property: 'endTime', type: 'string', format: 'time', nullable: true, description: 'End time (for type=available)'),
                                    // --- Meta for 'leave' type ---
                                    new OA\Property(property: 'leaveType', type: 'string', nullable: true, description: 'Leave type identifier (e.g., sick_leave, holiday)'),
                                    new OA\Property(property: 'leaveTypeLabel', type: 'string', nullable: true, description: 'Human-readable leave type label'),
                                    new OA\Property(property: 'status', type: 'string', nullable: true, description: 'Status of the leave request (e.g., approved, pending)'),
                                    new OA\Property(property: 'reason', type: 'string', nullable: true, description: 'Reason for leave (if provided)')
                                ]
                            )
                        ]
                    )
                )
            ),
            new OA\Response(response: 400, description: 'Invalid request parameters'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - user is not authorized to view this user\'s schedule'),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $constraints = new Assert\Collection([
            'startDate' => [new Assert\NotBlank(), new Assert\Date()],
            'endDate' => [new Assert\NotBlank(), new Assert\Date()],
            'userId' => new Assert\Optional([new Assert\Uuid()])
        ]);

        $violations = $this->validator->validate($request->query->all(), $constraints);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->getErrorMessages($violations)], Response::HTTP_BAD_REQUEST);
        }

        $startDate = new DateTimeImmutable($request->query->get('startDate'));
        $endDate = new DateTimeImmutable($request->query->get('endDate'));
        
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $targetUser = $currentUser;
        $userIdParam = $request->query->get('userId');

        if ($userIdParam !== null) {
            $targetUser = $this->userRepository->findById(UserId::fromString($userIdParam));
            
            if (!$targetUser) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }
            
            $canView = $this->isGranted('ROLE_ADMIN') ||
                       $targetUser === $currentUser ||
                       ($this->isGranted('ROLE_MANAGER') && $targetUser->getManager() === $currentUser);

            if (!$canView) {
                return $this->json(['error' => 'Access denied to view this user\'s schedule'], Response::HTTP_FORBIDDEN);
            }
        }

        $allAvailabilities = $this->availabilityRepository->findByUser($targetUser);
        $activeLeaveRequests = $this->leaveRequestRepository->findActiveByUserIntersectingDateRange(
            $targetUser,
            $startDate,
            $endDate
        );

        $dailyEntries = [];

        foreach ($allAvailabilities as $availability) {
            $occurrences = [];
            $pattern = $availability->getRecurrencePattern();

            if ($pattern) {
                $occurrences = $pattern->getOccurrences(
                    $availability->getDate(),
                    $startDate,
                    $endDate
                );
            } elseif ($availability->getDate() >= $startDate && $availability->getDate() <= $endDate) {
                $occurrences = [$availability->getDate()];
            }

            foreach ($occurrences as $occurrenceDate) {
                 $dailyEntries[] = [
                    'date' => $occurrenceDate->format('Y-m-d'),
                    'type' => 'available',
                    'meta' => [
                        'id' => (string)$availability->getId(),
                        'startTime' => $availability->getTimeRange()->getStartTime()->format('H:i'),
                        'endTime' => $availability->getTimeRange()->getEndTime()->format('H:i'),
                    ]
                 ];
            }
        }

        foreach ($activeLeaveRequests as $leave) {
            $currentLeaveDate = max($startDate, $leave->getStartDate());
            $leaveEndDateInRange = min($endDate, $leave->getEndDate());

            while ($currentLeaveDate <= $leaveEndDateInRange) {
                 $strategy = $this->leaveTypeStrategyFactory->getStrategy($leave->getType()->value);

                 $dailyEntries[] = [
                     'date' => $currentLeaveDate->format('Y-m-d'),
                     'type' => 'leave',
                     'meta' => [
                         'id' => (string)$leave->getId(),
                         'leaveType' => $leave->getType()->value,
                         'leaveTypeLabel' => $strategy->getLabel(),
                         'status' => $leave->getStatus()->value,
                         'reason' => $leave->getReason(),
                         'color' => $strategy->getColor()
                     ]
                 ];
                 $currentLeaveDate = $currentLeaveDate->modify('+1 day');
            }
        }

        usort($dailyEntries, function ($a, $b) {
            $dateComparison = strcmp($a['date'], $b['date']);
            if ($dateComparison !== 0) {
                return $dateComparison;
            }
            $typeOrder = ['available' => 1, 'leave' => 2, 'holiday' => 3];
            $typeComparison = ($typeOrder[$a['type']] ?? 99) <=> ($typeOrder[$b['type']] ?? 99);
             if ($typeComparison !== 0) {
                 return $typeComparison;
             }
            if ($a['type'] === 'available') {
                return strcmp($a['meta']['startTime'] ?? '00:00', $b['meta']['startTime'] ?? '00:00');
            }
            return 0;
        });

        return $this->json($dailyEntries);
    }

    private function validateAvailability(Availability $availability): void
    {
        $overlapping = $this->availabilityRepository->findOverlapping($availability);
        if (count($overlapping) > 0) {
            throw new InvalidAvailabilityException(
                'This time range overlaps with existing availability for this user on the same date.'
            );
        }

        foreach ($this->availabilityStrategies as $strategy) {
            if ($strategy->supports($availability->getEmploymentType())) {
                $strategy->validate($availability);
                return;
            }
        }

        throw new InvalidAvailabilityException('No validation strategy found for employment type: ' . $availability->getEmploymentType()->value);
    }

    private function getErrorMessages(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }
        return $errors;
    }
} 