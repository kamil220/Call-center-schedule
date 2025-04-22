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
use Symfony\Component\Validator\ConstraintViolationListInterface;

#[Route('/api/work-schedule/availabilities')]
#[OA\Tag(name: 'Work Schedule')]
final class AvailabilityController extends AbstractController
{
    private AvailabilityRepositoryInterface $availabilityRepository;
    private ValidatorInterface $validator;
    private iterable $availabilityStrategies;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        AvailabilityRepositoryInterface $availabilityRepository,
        ValidatorInterface $validator,
        iterable $availabilityStrategies,
        UserRepositoryInterface $userRepository
    ) {
        $this->availabilityRepository = $availabilityRepository;
        $this->validator = $validator;
        $this->availabilityStrategies = $availabilityStrategies;
        $this->userRepository = $userRepository;
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
                content: new OA\JsonContent(ref: '#/components/schemas/Availability')
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
        summary: 'Get user availabilities',
        description: 'Retrieves a list of availabilities for the specified user (or current user if userId not provided) within the specified date range. Managers can view availabilities of their team members.',
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
                description: 'ID of the user whose availabilities to retrieve. If not provided, returns current user\'s availabilities. Managers can view their team members\' availabilities.'
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of availabilities',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'date', type: 'string', format: 'date'),
                            new OA\Property(property: 'startTime', type: 'string', format: 'time'),
                            new OA\Property(property: 'endTime', type: 'string', format: 'time'),
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
                )
            ),
            new OA\Response(response: 400, description: 'Invalid request parameters'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - user is not authorized to view this user\'s availabilities'),
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
            return $this->json(['errors' => (string) $violations], Response::HTTP_BAD_REQUEST);
        }

        $startDate = new DateTimeImmutable($request->query->get('startDate'));
        $endDate = new DateTimeImmutable($request->query->get('endDate'));
        
        $targetUser = $this->getUser();
        $userId = $request->query->get('userId');
        
        if ($userId !== null) {
            $targetUser = $this->userRepository->findById(UserId::fromString($userId));
            
            if (!$targetUser) {
                return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }
            
            // Check if current user is authorized to view target user's availabilities
            if (!$this->isGranted('ROLE_ADMIN') && (!$this->isGranted('ROLE_MANAGER') || $targetUser->getManager() !== $this->getUser())) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
        }

        $availabilities = $this->availabilityRepository->findByUserAndDateRange(
            $targetUser,
            $startDate,
            $endDate
        );

        return $this->json(
            array_map(
                fn(Availability $availability) => [
                    'id' => (string)$availability->getId(),
                    'userId' => (string)$availability->getUser()->getId(),
                    'date' => $availability->getDate()->format('Y-m-d'),
                    'startTime' => $availability->getTimeRange()->getStartTime()->format('H:i'),
                    'endTime' => $availability->getTimeRange()->getEndTime()->format('H:i'),
                    'recurrencePattern' => $availability->getRecurrencePattern()?->jsonSerialize()
                ],
                $availabilities
            )
        );
    }

    private function validateAvailability(Availability $availability): void
    {
        foreach ($this->availabilityStrategies as $strategy) {
            if ($strategy->supports($availability->getEmploymentType())) {
                $strategy->validate($availability);
                return;
            }
        }

        throw new InvalidAvailabilityException('No strategy found for employment type: ' . $availability->getEmploymentType()->value);
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