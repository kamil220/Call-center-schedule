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
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Authentication")
 */
#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager
    ) {
    }

    /**
     * Login to get JWT token
     *
     * This method won't get called due to Symfony security configuration
     * handling the /api/auth/login endpoint before it reaches the controller.
     * The actual authentication success response is handled by
     * CustomAuthenticationSuccessHandler.
     *
     * @OA\Post(
     *     path="/api/auth/login",
     *     operationId="auth_login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", example="admin123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Returns JWT token and user data",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="firstName", type="string"),
     *                 @OA\Property(property="lastName", type="string"),
     *                 @OA\Property(property="fullName", type="string"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="active", type="boolean")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user || !$user instanceof User) {
            return $this->json([
                'message' => 'Incorrect credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }
        
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
} 