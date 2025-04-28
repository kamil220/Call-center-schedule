<?php

declare(strict_types=1);

namespace App\UI\Controller\Call;

use App\Application\Call\DTO\CallFilterRequestDTO;
use App\Application\Call\DTO\CallResponseDTO;
use App\Application\Call\Service\CallService;
use App\Common\DTO\PaginatedResponseDTO;
use App\Domain\User\Entity\User;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/calls', name: 'api_calls_')]
#[OA\Tag(name: 'Calls')]
class CallController extends AbstractController
{
    private CallService $callService;
    private ValidatorInterface $validator;

    public function __construct(
        CallService $callService,
        ValidatorInterface $validator
    ) {
        $this->callService = $callService;
        $this->validator = $validator;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get a list of calls with filtering and pagination',
        description: 'Returns a paginated list of calls that can be filtered by operator, line, skill path, and phone number. Agents can only see their own calls.'
    )]
    #[OA\Parameter(
        name: 'operatorId',
        description: 'Filter by operator ID (Agents can only see their own calls)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Parameter(
        name: 'lineId',
        description: 'Filter by line ID',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'skillPathId',
        description: 'Filter by skill path ID',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'phoneNumber',
        description: 'Filter by phone number (partial match)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string')
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
        schema: new OA\Schema(type: 'string', enum: ['dateTime', 'duration', 'phoneNumber'])
    )]
    #[OA\Parameter(
        name: 'sortDirection',
        description: 'Sort direction',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC'], default: 'DESC')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns paginated list of calls',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'dateTime', type: 'string', format: 'date-time'),
                        new OA\Property(
                            property: 'line',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(
                                    property: 'skillPath',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer'),
                                        new OA\Property(property: 'name', type: 'string')
                                    ]
                                )
                            ]
                        ),
                        new OA\Property(property: 'phoneNumber', type: 'string'),
                        new OA\Property(
                            property: 'operator',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string'),
                                new OA\Property(property: 'fullName', type: 'string'),
                                new OA\Property(property: 'email', type: 'string')
                            ]
                        ),
                        new OA\Property(property: 'duration', type: 'integer')
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
        /** @var User $user */
        $user = $this->getUser();
        
        // Get filter and pagination parameters from request
        $filterDTO = CallFilterRequestDTO::fromArray($request->query->all());
        
        // If user is an agent, force operatorId to be their own ID
        if ($user->hasRole(User::ROLE_AGENT) && !$user->hasRole(User::ROLE_ADMIN)) {
            $filterDTO = CallFilterRequestDTO::fromArray(array_merge(
                $request->query->all(),
                ['operatorId' => $user->getId()->toString()]
            ));
        }
        
        $violations = $this->validator->validate($filterDTO);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            [$calls, $totalCount] = $this->callService->getFilteredCalls(
                $filterDTO->getOperatorId(),
                $filterDTO->getLineId(),
                $filterDTO->getSkillPathId(),
                $filterDTO->getPhoneNumber(),
                $filterDTO->getPage(),
                $filterDTO->getLimit(),
                $filterDTO->getSortBy(),
                $filterDTO->getSortDirection()
            );

            // Convert to response DTOs
            $responseDTOs = array_map(
                fn($call) => CallResponseDTO::fromEntity($call)->toArray(),
                $calls
            );

            // Create paginated response
            $paginatedResponse = new PaginatedResponseDTO(
                $responseDTOs,
                $totalCount,
                $filterDTO->getPage(),
                $filterDTO->getLimit()
            );

            return $this->json($paginatedResponse->toArray());
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
} 