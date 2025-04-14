<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Authentication;

use App\Domain\User\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler as BaseSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationSuccessHandler extends BaseSuccessHandler
{
    private $logger;

    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        LoggerInterface $logger,
        array $options = []
    ) {
        parent::__construct($jwtManager, $options);
        $this->logger = $logger;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        try {
            // Get the original response which contains the JWT token
            $originalResponse = parent::onAuthenticationSuccess($request, $token);
            
            // Get the user for additional data
            $user = $token->getUser();
            
            if (!$user instanceof User) {
                $this->logger->warning('User is not instance of our User entity', [
                    'class' => get_class($user)
                ]);
                return $originalResponse;
            }
            
            // Get response data (should have token)
            $data = json_decode($originalResponse->getContent(), true);
            
            // Add user data to response
            $data['user'] = [
                'id' => $user->getId(),
                'email' => $user->getUserIdentifier(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'active' => $user->isActive(),
            ];
            
            // Create new response with combined data
            $response = new JsonResponse($data, Response::HTTP_OK);
            
            // Log successful response
            $this->logger->info('Authentication success with user data', [
                'user_id' => $user->getId()
            ]);
            
            return $response;
        } catch (\Throwable $e) {
            // Log error
            $this->logger->error('Error in authentication success handler', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fall back to parent implementation
            return parent::onAuthenticationSuccess($request, $token);
        }
    }
} 