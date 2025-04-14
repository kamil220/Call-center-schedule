<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Authentication;

use App\Domain\User\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CustomAuthenticationSuccessHandler extends AuthenticationSuccessHandler
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $response = parent::onAuthenticationSuccess($request, $token);
        
        $data = json_decode($response->getContent(), true);
        $user = $token->getUser();
        
        if ($user instanceof User) {
            $data['user'] = [
                'id' => $user->getId(),
                'email' => $user->getUserIdentifier(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'roles' => $user->getRoles(),
                'active' => $user->isActive(),
            ];
            
            // Create a new response with updated data
            return new JsonResponse($data);
        }
        
        return $response;
    }
} 