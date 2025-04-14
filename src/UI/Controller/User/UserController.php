<?php

declare(strict_types=1);

namespace App\UI\Controller\User;

use App\Application\User\Command\CreateUserCommand;
use App\Application\User\Command\CreateUserHandler;
use App\Application\User\DTO\CreateUserRequestDTO;
use App\Common\DTO\PaginatedResponseDTO;
use App\Application\User\DTO\UserFilterRequestDTO;
use App\Application\User\DTO\UserResponseDTO;
use App\Application\User\Service\UserService;
use App\Domain\User\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use App\Domain\User\Event\UserCreatedEvent;
use App\Domain\User\Event\UserActivatedEvent;
use App\Domain\User\Event\UserDeactivatedEvent;

#[Route('/api/users', name: 'api_users_')]
#[OA\Tag(name: 'Users')]
class UserController extends AbstractController
{
    private UserService $userService;
    private CreateUserHandler $createUserHandler;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        UserService $userService,
        CreateUserHandler $createUserHandler,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->userService = $userService;
        $this->createUserHandler = $createUserHandler;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get a list of users with filtering and pagination',
        description: 'Returns a paginated list of users that can be filtered by various criteria'
    )]
    #[OA\Parameter(
        name: 'name',
        description: 'Filter by name (searches in first name, last name, or full name - partial match)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'firstName',
        description: 'Filter by first name (partial match)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'lastName',
        description: 'Filter by last name (partial match)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'email',
        description: 'Filter by email (partial match)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'role',
        description: 'Filter by role (exact match)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: User::VALID_ROLES)
    )]
    #[OA\Parameter(
        name: 'active',
        description: 'Filter by active status',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'page',
        description: 'Page number (0-based)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 0)
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Number of items per page',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 10)
    )]
    #[OA\Parameter(
        name: 'sortBy',
        description: 'Field to sort by',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['id', 'email', 'firstName', 'lastName', 'active'])
    )]
    #[OA\Parameter(
        name: 'sortDirection',
        description: 'Sort direction',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC'], default: 'ASC')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns paginated list of users',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'string'),
                        new OA\Property(property: 'email', type: 'string'),
                        new OA\Property(property: 'firstName', type: 'string'),
                        new OA\Property(property: 'lastName', type: 'string'),
                        new OA\Property(property: 'fullName', type: 'string'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                        new OA\Property(property: 'active', type: 'boolean')
                    ],
                    type: 'object'
                )),
                new OA\Property(property: 'total', type: 'integer'),
                new OA\Property(property: 'page', type: 'integer'),
                new OA\Property(property: 'limit', type: 'integer'),
                new OA\Property(property: 'totalPages', type: 'integer')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid filter parameters'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized'
    )]
    public function list(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        
        // Get filter and pagination parameters from request
        $filterDTO = UserFilterRequestDTO::fromArray($request->query->all());
        $violations = $this->validator->validate($filterDTO);
        
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }
        
        // Get filtered users with pagination
        [$users, $totalCount] = $this->userService->getFilteredUsers($filterDTO);
        
        // Convert to response DTOs
        $responseDTOs = array_map(
            fn(User $user) => UserResponseDTO::fromEntity($user)->toArray(),
            $users
        );
        
        // Create paginated response
        $paginatedResponse = new PaginatedResponseDTO(
            $responseDTOs,
            $totalCount,
            $filterDTO->getPage(),
            $filterDTO->getLimit()
        );
        
        return $this->json($paginatedResponse->toArray());
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get a single user by ID',
        description: 'Returns detailed information about a specific user'
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'User ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'User details',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string'),
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
                new OA\Property(property: 'fullName', type: 'string'),
                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'active', type: 'boolean')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized'
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found'
    )]
    public function get(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        
        $user = $this->userService->getUserById($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        $responseDTO = UserResponseDTO::fromEntity($user);
        
        return $this->json($responseDTO->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        summary: 'Create a new user',
        description: 'Creates a new user with the provided details'
    )]
    #[OA\RequestBody(
        description: 'User data',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'password', type: 'string'),
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
                new OA\Property(
                    property: 'roles', 
                    type: 'array', 
                    items: new OA\Items(type: 'string', enum: User::VALID_ROLES)
                )
            ],
            required: ['email', 'password', 'firstName', 'lastName', 'roles']
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'User created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'id', type: 'string'),
                new OA\Property(property: 'email', type: 'string'),
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
                new OA\Property(property: 'fullName', type: 'string'),
                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'active', type: 'boolean')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid input data'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized'
    )]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        
        $data = json_decode($request->getContent(), true);
        
        // Create and validate DTO
        $requestDTO = CreateUserRequestDTO::fromArray($data);
        $violations = $this->validator->validate($requestDTO);
        
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $command = new CreateUserCommand(
                $requestDTO->getEmail(),
                $requestDTO->getPassword(),
                $requestDTO->getFirstName(),
                $requestDTO->getLastName(),
                $requestDTO->getRoles()
            );
            
            $user = $this->createUserHandler->handle($command);
            
            // Dispatch domain event
            $this->eventDispatcher->dispatch(new UserCreatedEvent($user));
            
            $responseDTO = UserResponseDTO::fromEntity($user);
            
            return $this->json($responseDTO->toArray(), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/activate', name: 'activate', methods: ['PATCH'])]
    #[OA\Patch(
        summary: 'Activate a user',
        description: 'Activates a deactivated user'
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'User ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'User activated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized'
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found'
    )]
    public function activate(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        
        $user = $this->userService->getUserById($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        $this->userService->activateUser($user);
        
        // Dispatch domain event
        $this->eventDispatcher->dispatch(new UserActivatedEvent($user));
        
        return $this->json(['message' => 'User activated successfully']);
    }

    #[Route('/{id}/deactivate', name: 'deactivate', methods: ['PATCH'])]
    #[OA\Patch(
        summary: 'Deactivate a user',
        description: 'Deactivates an active user'
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'User ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'User deactivated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized'
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found'
    )]
    public function deactivate(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        
        $user = $this->userService->getUserById($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        $this->userService->deactivateUser($user);
        
        // Dispatch domain event
        $this->eventDispatcher->dispatch(new UserDeactivatedEvent($user));
        
        return $this->json(['message' => 'User deactivated successfully']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        summary: 'Delete a user',
        description: 'Permanently deletes a user'
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'User ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'User deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string')
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized'
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found'
    )]
    public function delete(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        
        $user = $this->userService->getUserById($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        $this->userService->deleteUser($user);
        
        return $this->json(['message' => 'User deleted successfully']);
    }
} 