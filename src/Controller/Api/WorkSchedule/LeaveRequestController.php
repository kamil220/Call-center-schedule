<?php

declare(strict_types=1);

namespace App\Controller\Api\WorkSchedule;

use App\Domain\User\Entity\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\ValueObject\UserId;
use App\Domain\WorkSchedule\Entity\LeaveRequest;
use App\Domain\WorkSchedule\Repository\LeaveRequestRepositoryInterface;
use App\Domain\WorkSchedule\Service\LeaveType\LeaveTypeStrategyFactory;
use App\Domain\WorkSchedule\ValueObject\LeaveStatus;
use App\Domain\WorkSchedule\ValueObject\LeaveType;
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
use Symfony\Component\Validator\ConstraintViolationListInterface;
use OpenApi\Attributes as OA;

#[Route('/api/work-schedule/leave-requests')]
#[OA\Tag(name: 'Work Schedule')]
final class LeaveRequestController extends AbstractController
{
    private LeaveRequestRepositoryInterface $leaveRequestRepository;
    private ValidatorInterface $validator;
    private UserRepositoryInterface $userRepository;
    private LeaveTypeStrategyFactory $leaveTypeStrategyFactory;

    public function __construct(
        LeaveRequestRepositoryInterface $leaveRequestRepository,
        ValidatorInterface $validator,
        UserRepositoryInterface $userRepository,
        LeaveTypeStrategyFactory $leaveTypeStrategyFactory
    ) {
        $this->leaveRequestRepository = $leaveRequestRepository;
        $this->validator = $validator;
        $this->userRepository = $userRepository;
        $this->leaveTypeStrategyFactory = $leaveTypeStrategyFactory;
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[OA\Post(
        path: '/api/work-schedule/leave-requests',
        summary: 'Create a new leave request',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'startDate', 'endDate'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', enum: ['sick_leave', 'holiday', 'personal_leave', 'paternity_leave', 'maternity_leave']),
                    new OA\Property(property: 'startDate', type: 'string', format: 'date'),
                    new OA\Property(property: 'endDate', type: 'string', format: 'date'),
                    new OA\Property(property: 'reason', type: 'string'),
                    new OA\Property(property: 'userId', type: 'string', format: 'uuid', description: 'Required for managers creating requests for their team members'),
                    new OA\Property(property: 'metadata', type: 'object', description: 'Additional data specific to leave type (e.g., medical certificate for sick leave)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Leave request created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid input'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        // Check if user has required role
        if (!$this->isGranted('ROLE_AGENT') && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Access Denied.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        $constraints = new Assert\Collection([
            'type' => [
                new Assert\NotBlank(),
                new Assert\Choice(array_keys($this->getAvailableLeaveTypes()))
            ],
            'startDate' => [
                new Assert\NotBlank(),
                new Assert\Date(),
            ],
            'endDate' => [
                new Assert\NotBlank(),
                new Assert\Date(),
            ],
            'reason' => new Assert\Optional([
                new Assert\Type('string')
            ]),
            'userId' => new Assert\Optional([
                new Assert\Uuid()
            ]),
            'metadata' => new Assert\Optional([
                new Assert\Type('array')
            ])
        ]);

        $violations = $this->validator->validate($data, $constraints);

        if (count($violations) > 0) {
            return new JsonResponse(['errors' => $this->getErrorMessages($violations)], Response::HTTP_BAD_REQUEST);
        }

        $targetUser = $this->getUser();
        
        // If userId is provided, check if it's valid and the current user is authorized
        if (isset($data['userId'])) {
            // Only managers can create leave requests for other users
            if (!$this->isGranted('ROLE_MANAGER') && !$this->isGranted('ROLE_ADMIN')) {
                return new JsonResponse(['error' => 'Not authorized to create leave requests for other users'], Response::HTTP_FORBIDDEN);
            }
            
            $targetUser = $this->userRepository->findById(UserId::fromString($data['userId']));
            
            if (!$targetUser) {
                return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }
            
            // Check if the current user is the manager of the target user
            if (!$this->isGranted('ROLE_ADMIN') && $targetUser->getManager() !== $this->getUser()) {
                return new JsonResponse(['error' => 'Not authorized to create leave requests for this user'], Response::HTTP_FORBIDDEN);
            }
        }

        try {
            $startDate = new DateTimeImmutable($data['startDate']);
            $endDate = new DateTimeImmutable($data['endDate']);
            $leaveType = LeaveType::from($data['type']);
            
            // Get the appropriate strategy for this leave type
            $strategy = $this->leaveTypeStrategyFactory->getStrategy($leaveType->value);
            
            // Create the leave request
            $leaveRequest = new LeaveRequest(
                Uuid::uuid4(),
                $targetUser,
                $leaveType,
                $startDate,
                $endDate,
                $data['reason'] ?? null
            );
            
            // Check if this leave type is applicable for the user
            if (!$strategy->isApplicable(['user' => $targetUser])) {
                return new JsonResponse(
                    ['error' => sprintf('This user is not eligible for %s', $strategy->getLabel())], 
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            
            // Get existing leave requests for validation
            $existingRequests = $this->leaveRequestRepository->findByUserAndDateRange(
                $targetUser,
                $startDate,
                $endDate
            );
            
            // Validate the request according to its type-specific rules
            $strategy->validateRequest($leaveRequest, $existingRequests);
            
            $this->leaveRequestRepository->save($leaveRequest);

            return new JsonResponse(['id' => (string) $leaveRequest->getId()], Response::HTTP_CREATED);

        } catch (\InvalidArgumentException|\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/approve', methods: ['PUT'])]
    #[IsGranted('ROLE_MANAGER')]
    #[OA\Put(
        path: '/api/work-schedule/leave-requests/{id}/approve',
        summary: 'Approve a leave request',
        description: 'Approves a pending leave request. Only managers and admins can approve requests.',
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
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'comments', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Leave request approved successfully'),
            new OA\Response(response: 400, description: 'Invalid request'),
            new OA\Response(response: 403, description: 'Not authorized'),
            new OA\Response(response: 404, description: 'Leave request not found'),
            new OA\Response(response: 422, description: 'Cannot approve the request')
        ]
    )]
    public function approve(string $id, Request $request): JsonResponse
    {
        $leaveRequest = $this->leaveRequestRepository->findById($id);
        
        if (!$leaveRequest) {
            return new JsonResponse(['error' => 'Leave request not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Check if the current user is authorized to approve this request
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $requestUser = $leaveRequest->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && $requestUser->getManager() !== $currentUser) {
            return new JsonResponse(['error' => 'Not authorized to approve this request'], Response::HTTP_FORBIDDEN);
        }
        
        try {
            $data = json_decode($request->getContent(), true) ?: [];
            $comments = $data['comments'] ?? null;
            
            // Get the strategy for this leave type
            $strategy = $this->leaveTypeStrategyFactory->getStrategy($leaveRequest->getType()->value);
            
            // Perform any additional checks specific to this leave type before approval
            if (!$strategy->requiresApproval() && $leaveRequest->getStatus() === LeaveStatus::PENDING) {
                // This request should have been auto-approved - log this anomaly
                // but still proceed with approval
            }
            
            $leaveRequest->approve($currentUser, $comments);
            $this->leaveRequestRepository->save($leaveRequest);
            
            return new JsonResponse(null, Response::HTTP_OK);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}/reject', methods: ['PUT'])]
    #[IsGranted('ROLE_MANAGER')]
    #[OA\Put(
        path: '/api/work-schedule/leave-requests/{id}/reject',
        summary: 'Reject a leave request',
        description: 'Rejects a pending leave request. Only managers and admins can reject requests.',
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
                required: ['comments'],
                properties: [
                    new OA\Property(property: 'comments', type: 'string')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Leave request rejected successfully'),
            new OA\Response(response: 400, description: 'Invalid request'),
            new OA\Response(response: 403, description: 'Not authorized'),
            new OA\Response(response: 404, description: 'Leave request not found'),
            new OA\Response(response: 422, description: 'Cannot reject the request')
        ]
    )]
    public function reject(string $id, Request $request): JsonResponse
    {
        $leaveRequest = $this->leaveRequestRepository->findById($id);
        
        if (!$leaveRequest) {
            return new JsonResponse(['error' => 'Leave request not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Check if the current user is authorized to reject this request
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $requestUser = $leaveRequest->getUser();
        
        if (!$this->isGranted('ROLE_ADMIN') && $requestUser->getManager() !== $currentUser) {
            return new JsonResponse(['error' => 'Not authorized to reject this request'], Response::HTTP_FORBIDDEN);
        }
        
        $data = json_decode($request->getContent(), true) ?: [];
        
        $constraints = new Assert\Collection([
            'comments' => [new Assert\NotBlank(), new Assert\Type('string')]
        ]);
        
        $violations = $this->validator->validate($data, $constraints);
        
        if (count($violations) > 0) {
            return new JsonResponse(['errors' => $this->getErrorMessages($violations)], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            // Get the strategy for this leave type
            $strategy = $this->leaveTypeStrategyFactory->getStrategy($leaveRequest->getType()->value);
            
            // If the leave type doesn't require approval, we still allow rejection
            // (e.g., sick leave might be auto-approved but we might still need to reject it 
            // if evidence is insufficient)
            
            $leaveRequest->reject($currentUser, $data['comments']);
            $this->leaveRequestRepository->save($leaveRequest);
            
            return new JsonResponse(null, Response::HTTP_OK);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id}/cancel', methods: ['PUT'])]
    #[IsGranted('ROLE_AGENT')]
    #[OA\Put(
        path: '/api/work-schedule/leave-requests/{id}/cancel',
        summary: 'Cancel a leave request',
        description: 'Cancels a leave request. Users can cancel their own requests, managers can cancel their team members\' requests.',
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
            new OA\Response(response: 200, description: 'Leave request cancelled successfully'),
            new OA\Response(response: 400, description: 'Invalid request'),
            new OA\Response(response: 403, description: 'Not authorized'),
            new OA\Response(response: 404, description: 'Leave request not found'),
            new OA\Response(response: 422, description: 'Cannot cancel the request')
        ]
    )]
    public function cancel(string $id): JsonResponse
    {
        $leaveRequest = $this->leaveRequestRepository->findById($id);
        
        if (!$leaveRequest) {
            return new JsonResponse(['error' => 'Leave request not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Check if the current user is authorized to cancel this request
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $requestUser = $leaveRequest->getUser();
        
        $isOwner = $requestUser === $currentUser;
        $isManager = $this->isGranted('ROLE_ADMIN') || 
                    ($this->isGranted('ROLE_MANAGER') && $requestUser->getManager() === $currentUser);
        
        if (!$isOwner && !$isManager) {
            return new JsonResponse(['error' => 'Not authorized to cancel this request'], Response::HTTP_FORBIDDEN);
        }
        
        try {
            $leaveRequest->cancel();
            $this->leaveRequestRepository->save($leaveRequest);
            
            return new JsonResponse(null, Response::HTTP_OK);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    #[OA\Get(
        path: '/api/work-schedule/leave-requests',
        summary: 'Get leave requests',
        description: 'Retrieves leave requests. Users see their own requests, managers see their team\'s requests, admins see all requests.',
        tags: ['Work Schedule'],
        parameters: [
            new OA\Parameter(
                name: 'userId',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'rejected', 'cancelled'])
            ),
            new OA\Parameter(
                name: 'type',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['sick_leave', 'holiday', 'personal_leave', 'paternity_leave', 'maternity_leave'])
            ),
            new OA\Parameter(
                name: 'startDate',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'endDate',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 20)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of leave requests',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'userId', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'userName', type: 'string'),
                                new OA\Property(property: 'type', type: 'string'),
                                new OA\Property(property: 'typeLabel', type: 'string'),
                                new OA\Property(property: 'typeColor', type: 'string'),
                                new OA\Property(property: 'status', type: 'string'),
                                new OA\Property(property: 'statusLabel', type: 'string'),
                                new OA\Property(property: 'statusColor', type: 'string'),
                                new OA\Property(property: 'startDate', type: 'string', format: 'date'),
                                new OA\Property(property: 'endDate', type: 'string', format: 'date'),
                                new OA\Property(property: 'duration', type: 'integer'),
                                new OA\Property(property: 'reason', type: 'string', nullable: true),
                                new OA\Property(property: 'approver', type: 'string', nullable: true),
                                new OA\Property(property: 'approvalDate', type: 'string', format: 'date-time', nullable: true),
                                new OA\Property(property: 'comments', type: 'string', nullable: true),
                                new OA\Property(property: 'createdAt', type: 'string', format: 'date-time')
                            ]
                        )),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                        new OA\Property(property: 'totalPages', type: 'integer')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid parameters'),
            new OA\Response(response: 403, description: 'Forbidden')
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $queryParams = $request->query->all();
        
        // Basic validation of query parameters
        $dateConstraints = new Assert\Collection([
            'userId' => new Assert\Optional([new Assert\Uuid()]),
            'status' => new Assert\Optional([new Assert\Choice(['pending', 'approved', 'rejected', 'cancelled'])]),
            'type' => new Assert\Optional([new Assert\Choice(array_keys($this->getAvailableLeaveTypes()))]),
            'startDate' => new Assert\Optional([new Assert\Date()]),
            'endDate' => new Assert\Optional([new Assert\Date()]),
            'page' => new Assert\Optional([new Assert\Type('numeric'), new Assert\GreaterThan(0)]),
            'limit' => new Assert\Optional([new Assert\Type('numeric'), new Assert\GreaterThan(0)]),
        ]);
        
        $violations = $this->validator->validate($queryParams, $dateConstraints);
        
        if (count($violations) > 0) {
            return new JsonResponse(['errors' => $this->getErrorMessages($violations)], Response::HTTP_BAD_REQUEST);
        }
        
        // Process the query
        $userId = isset($queryParams['userId']) ? $queryParams['userId'] : null;
        $statusStr = isset($queryParams['status']) ? $queryParams['status'] : null;
        $typeStr = isset($queryParams['type']) ? $queryParams['type'] : null;
        $startDate = isset($queryParams['startDate']) ? new DateTimeImmutable($queryParams['startDate']) : null;
        $endDate = isset($queryParams['endDate']) ? new DateTimeImmutable($queryParams['endDate']) : null;
        $page = isset($queryParams['page']) ? (int) $queryParams['page'] : 1;
        $limit = isset($queryParams['limit']) ? (int) $queryParams['limit'] : 20;
        
        // Special case for admins viewing all leave requests without filters
        if ($this->isGranted('ROLE_ADMIN') && !$userId && !$statusStr && !$typeStr && !$startDate && !$endDate) {
            $total = $this->leaveRequestRepository->countAll();
            $leaveRequests = $this->leaveRequestRepository->findAll($page, $limit);
            
            $items = array_map(
                [$this, 'mapLeaveRequestToArray'],
                $leaveRequests
            );
            
            $paginatedResponse = new \App\Common\DTO\PaginatedResponseDTO($items, $total, $page, $limit);
            return new JsonResponse($paginatedResponse->toArray());
        }
        
        // Regular case - get filtered leave requests
        $leaveRequests = $this->getLeaveRequests($userId, $statusStr, $typeStr, $startDate, $endDate);
        
        // Apply pagination
        $total = count($leaveRequests);
        $offset = ($page - 1) * $limit;
        $paginatedRequests = array_slice($leaveRequests, $offset, $limit);
        
        // Map leave requests to DTOs
        $items = array_map(
            [$this, 'mapLeaveRequestToArray'],
            $paginatedRequests
        );
        
        // Create and return a paginated response
        $paginatedResponse = new \App\Common\DTO\PaginatedResponseDTO($items, $total, $page, $limit);
        return new JsonResponse($paginatedResponse->toArray());
    }
    
    /**
     * Map a LeaveRequest entity to an array for API response
     */
    private function mapLeaveRequestToArray(LeaveRequest $request): array
    {
        $user = $request->getUser();
        $approver = $request->getApprover();
        $leaveType = $request->getType();
        $leaveStrategy = $this->leaveTypeStrategyFactory->getStrategy($leaveType->value);
        
        return [
            'id' => (string) $request->getId(),
            'userId' => (string) $user->getId(),
            'userName' => $user->getFirstName() . ' ' . $user->getLastName(),
            'type' => $leaveType->value,
            'typeLabel' => $leaveStrategy->getLabel(),
            'typeColor' => $leaveStrategy->getColor(),
            'status' => $request->getStatus()->value,
            'statusLabel' => $request->getStatus()->getLabel(),
            'statusColor' => $request->getStatus()->getColor(),
            'startDate' => $request->getStartDate()->format('Y-m-d'),
            'endDate' => $request->getEndDate()->format('Y-m-d'),
            'duration' => $leaveStrategy->calculateDuration(
                $request->getStartDate(), 
                $request->getEndDate()
            ),
            'reason' => $request->getReason(),
            'approver' => $approver ? $approver->getFirstName() . ' ' . $approver->getLastName() : null,
            'approvalDate' => $request->getApprovalDate() ? $request->getApprovalDate()->format('c') : null,
            'comments' => $request->getComments(),
            'createdAt' => $request->getCreatedAt()->format('c')
        ];
    }

    /**
     * Get a list of available leave types
     */
    #[Route('/types', methods: ['GET'])]
    #[IsGranted('ROLE_AGENT')]
    #[OA\Get(
        path: '/api/work-schedule/leave-requests/types',
        summary: 'Get available leave types',
        description: 'Returns a list of available leave types for the current user',
        tags: ['Work Schedule'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of leave types',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'type', type: 'string'),
                            new OA\Property(property: 'label', type: 'string'),
                            new OA\Property(property: 'maxDuration', type: 'integer'),
                            new OA\Property(property: 'requiresApproval', type: 'boolean'),
                            new OA\Property(property: 'color', type: 'string')
                        ]
                    )
                )
            )
        ]
    )]
    public function getLeaveTypes(): JsonResponse
    {
        $types = [];
        $strategies = $this->leaveTypeStrategyFactory->getAllStrategies();
        
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        foreach ($strategies as $strategy) {
            // Only include types that are applicable to this user
            if ($strategy->isApplicable(['user' => $currentUser])) {
                $types[] = [
                    'type' => $strategy->getType(),
                    'label' => $strategy->getLabel(),
                    'maxDuration' => $strategy->getMaxDuration(),
                    'requiresApproval' => $strategy->requiresApproval(),
                    'color' => $strategy->getColor()
                ];
            }
        }
        
        return new JsonResponse($types);
    }

    /**
     * Filter and fetch leave requests based on the user's role and provided parameters
     */
    private function getLeaveRequests(
        ?string $userId = null,
        ?string $statusStr = null, 
        ?string $typeStr = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null,
        int $page = 1,
        int $limit = 20
    ): array {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $leaveRequests = [];
        
        // Convert string parameters to their enum types if provided
        $status = $statusStr ? LeaveStatus::from($statusStr) : null;
        $type = $typeStr ? LeaveType::from($typeStr) : null;
        
        // If specific user is requested and that's allowed given current user's role
        if ($userId) {
            $targetUser = $this->userRepository->findById(UserId::fromString($userId));
            
            if (!$targetUser) {
                return [];
            }
            
            // Only allow viewing another user's requests if admin or their manager
            $canViewUser = $this->isGranted('ROLE_ADMIN') || 
                            ($this->isGranted('ROLE_MANAGER') && $targetUser->getManager() === $currentUser);
            
            if (!$canViewUser && $targetUser !== $currentUser) {
                return [];
            }
            
            // Fetch requests for target user with date range if specified
            if ($startDate && $endDate) {
                $leaveRequests = $this->leaveRequestRepository->findByUserAndDateRange($targetUser, $startDate, $endDate);
            } else {
                $leaveRequests = $this->leaveRequestRepository->findByUser($targetUser);
            }
        } 
        // No user specified, return results based on role
        else {
            if ($this->isGranted('ROLE_ADMIN')) {
                // Admins can see all requests, filtered by status/type if specified
                if ($status) {
                    $leaveRequests = $this->leaveRequestRepository->findByStatus($status);
                } elseif ($type) {
                    $leaveRequests = $this->leaveRequestRepository->findByType($type);
                } else {
                    // Use pagination for all requests to avoid performance issues
                    return $this->leaveRequestRepository->findAll($page, $limit);
                }
            } elseif ($this->isGranted('ROLE_MANAGER')) {
                // Managers see pending requests from their team
                $leaveRequests = $this->leaveRequestRepository->findPendingRequestsForManager($currentUser);
            } else {
                // Regular users see their own requests
                if ($startDate && $endDate) {
                    $leaveRequests = $this->leaveRequestRepository->findByUserAndDateRange($currentUser, $startDate, $endDate);
                } else {
                    $leaveRequests = $this->leaveRequestRepository->findByUser($currentUser);
                }
            }
        }
        
        // Apply additional filters if both status and type weren't already used for the main query
        if ($status && $type) {
            $leaveRequests = array_filter($leaveRequests, function (LeaveRequest $request) use ($status, $type) {
                return $request->getStatus() === $status && $request->getType() === $type;
            });
        } elseif ($status && !isset($byStatus)) {
            $leaveRequests = array_filter($leaveRequests, function (LeaveRequest $request) use ($status) {
                return $request->getStatus() === $status;
            });
        } elseif ($type && !isset($byType)) {
            $leaveRequests = array_filter($leaveRequests, function (LeaveRequest $request) use ($type) {
                return $request->getType() === $type;
            });
        }
        
        return $leaveRequests;
    }

    /**
     * Get a list of all available leave types
     * 
     * @return array<string, string>
     */
    private function getAvailableLeaveTypes(): array
    {
        $types = [];
        $strategies = $this->leaveTypeStrategyFactory->getAllStrategies();
        
        foreach ($strategies as $strategy) {
            $types[$strategy->getType()] = $strategy->getLabel();
        }
        
        return $types;
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