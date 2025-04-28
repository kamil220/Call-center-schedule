<?php

declare(strict_types=1);

namespace App\UI\Controller\Employee;

use App\Domain\Employee\Entity\SkillPath;
use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/skill-paths', name: 'api_skill_paths_')]
#[OA\Tag(name: 'Skill Paths')]
class SkillPathController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Get all available skill paths',
        description: 'Returns a list of all skill paths with their IDs and names'
    )]
    #[OA\Response(
        response: 200,
        description: 'List of skill paths',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                ]
            )
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorized'
    )]
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted(User::ROLE_AGENT);

        $skillPaths = $this->entityManager->getRepository(SkillPath::class)->findAll();

        $response = array_map(
            fn(SkillPath $skillPath) => [
                'id' => $skillPath->getId(),
                'name' => $skillPath->getName(),
            ],
            $skillPaths
        );

        return $this->json($response);
    }
} 