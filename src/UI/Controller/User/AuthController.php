<?php

declare(strict_types=1);

namespace App\UI\Controller\User;

use App\Application\User\Service\UserService;
use App\Domain\User\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    private UserService $userService;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(
        UserService $userService,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->userService = $userService;
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
    }

    /**
     * This method won't get called due to Symfony security configuration
     * handling the /api/auth/login endpoint before it reaches the controller.
     * The actual authentication success response is handled by
     * CustomAuthenticationSuccessHandler.
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        // This route is handled by the Symfony Security system
        // when properly configured in security.yaml
        
        // We shouldn't reach this code as the token should be generated
        // by the JWT authenticator, but just in case:
        $user = $this->getUser();
        
        if (!$user || !$user instanceof User) {
            return $this->json([
                'message' => 'Incorrect credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        // Generate token manually if needed
        $token = $this->jwtManager->create($user);
        
        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getUserIdentifier(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'active' => $user->isActive(),
            ],
        ]);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || !$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getUserIdentifier(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'active' => $user->isActive(),
        ]);
    }

    #[Route('/password-change', name: 'password_change', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user || !$user instanceof User) {
            return $this->json(['message' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['currentPassword'], $data['newPassword'])) {
            return $this->json(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }
        
        $currentPassword = $data['currentPassword'];
        $newPassword = $data['newPassword'];
        
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json(['error' => 'Current password is incorrect'], Response::HTTP_BAD_REQUEST);
        }
        
        $this->userService->changePassword($user, $newPassword);
        
        return $this->json(['message' => 'Password changed successfully']);
    }
} 