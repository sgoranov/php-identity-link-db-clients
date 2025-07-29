<?php
declare(strict_types=1);

namespace App\Controller;

use sgoranov\IdentityLinkShared\Api\DTO\AbstractQueryRequest;
use sgoranov\IdentityLinkShared\Controller\QueryController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

#[AsController]
#[Route('/api/v1/query', name: 'api_v1_query', methods: ['POST'])]
#[OA\Post(
    path: '/api/v1/query',
    summary: 'Query any entity (Client, Group, Secret, etc.)',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/QueryRequest')
    ),
    tags: ['Query'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Query result',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'response',
                        properties: [
                            new OA\Property(
                                property: 'result',
                                type: 'array',
                                items: new OA\Items(type: 'object')
                            ),
                            new OA\Property(
                                property: 'hasMore',
                                type: 'boolean'
                            )
                        ],
                        type: 'object'
                    )
                ],
                type: 'object'
            )
        )
    ]
)]
final class QueryProxyController
{
    public function __construct(private readonly QueryController $queryController) {}

    public function __invoke(AbstractQueryRequest $request): Response
    {
        return $this->queryController->query($request);
    }
}
