<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

#[Route('/api')]
#[OA\Tag(name: 'Example API')]
class ExampleController extends AbstractController
{
    #[Route('/example', name: 'api_example_get', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns example data',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1),
                    new OA\Property(property: 'name', type: 'string', example: 'Example Entity'),
                    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2023-06-15T08:30:00Z')
                ])
            ]
        )
    )]
    #[OA\Parameter(
        name: 'filter',
        in: 'query',
        description: 'Filter parameter',
        schema: new OA\Schema(type: 'string'),
        required: false
    )]
    public function getExample(Request $request): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'id' => 1,
                'name' => 'Example Entity',
                'createdAt' => '2023-06-15T08:30:00Z'
            ]
        ]);
    }

    #[Route('/example', name: 'api_example_post', methods: ['POST'])]
    #[OA\Response(
        response: 201,
        description: 'Creates a new resource',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Resource created successfully'),
                new OA\Property(property: 'id', type: 'integer', example: 2)
            ]
        )
    )]
    #[OA\RequestBody(
        description: 'Data needed to create a new resource',
        content: new OA\JsonContent(
            type: 'object',
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'New Entity'),
                new OA\Property(property: 'description', type: 'string', example: 'Description of the entity', nullable: true)
            ]
        )
    )]
    public function createExample(Request $request): JsonResponse
    {
        // In a real application, you would parse and validate the request body here
        return new JsonResponse([
            'status' => 'success',
            'message' => 'Resource created successfully',
            'id' => 2
        ], Response::HTTP_CREATED);
    }
} 