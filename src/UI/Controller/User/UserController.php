<?php

declare(strict_types=1);

namespace App\UI\Controller\User;

use App\Application\User\Command\CreateUserCommand;
use App\Application\User\Command\CreateUserHandler;
use App\Application\User\Service\UserService;
use App\Domain\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/users', name: 'api_users_')]
class UserController extends AbstractController
{
    private UserService $userService;
    private CreateUserHandler $createUserHandler;
    private SerializerInterface $serializer;

    public function __construct(
        UserService $userService,
        CreateUserHandler $createUserHandler,
        SerializerInterface $serializer
    ) {
        $this->userService = $userService;
        $this->createUserHandler = $createUserHandler;
        $this->serializer = $serializer;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        
        $users = $this->userService->getAllUsers();
        
        return $this->json([
            'users' => array_map(function (User $user) {
                return [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'fullName' => $user->getFullName(),
                    'roles' => $user->getRoles(),
                    'active' => $user->isActive(),
                ];
            }, $users)
        ]);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        
        $user = $this->userService->getUserById($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'active' => $user->isActive(),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['email'], $data['password'], $data['firstName'], $data['lastName'], $data['roles'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }
        
        try {
            $command = new CreateUserCommand(
                $data['email'],
                $data['password'],
                $data['firstName'],
                $data['lastName'],
                $data['roles']
            );
            
            $user = $this->createUserHandler->handle($command);
            
            return $this->json([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'active' => $user->isActive(),
            ], Response::HTTP_CREATED);
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