<?php

declare(strict_types=1);

namespace App\UI\Controller\User;

use App\Application\User\Command\CreateUserCommand;
use App\Application\User\Command\CreateUserHandler;
use App\Application\User\DTO\CreateUserRequestDTO;
use App\Application\User\DTO\UserResponseDTO;
use App\Application\User\Service\UserService;
use App\Domain\User\Entity\User;
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
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        
        $users = $this->userService->getAllUsers();
        $responseDTOs = array_map(
            fn(User $user) => UserResponseDTO::fromEntity($user)->toArray(),
            $users
        );
        
        return $this->json(['users' => $responseDTOs]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
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