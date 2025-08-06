<?php
declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

// To generate OpenAPI YAML file run:
// ./vendor/bin/openapi --output docs/openapi.yaml src/
// This scans the `src/` directory for OpenAPI attributes and outputs the spec to docs/openapi.yaml
#[OA\OpenApi(
    info: new OA\Info(
        version: "1.0",
        description: "API documentation for Identity Link DB Clients",
        title: "Identity Link DB Client API"
    ),
    servers: [
        new OA\Server(
            url: "/clients",
            description: "Clients API base path"
        )
    ],
    security: [["bearerAuth" => []]],
    components: new OA\Components(
        securitySchemes: [
            new OA\SecurityScheme(
                securityScheme: "bearerAuth",
                type: "http",
                description: "JWT Bearer token authorization",
                bearerFormat: "JWT",
                scheme: "bearer"
            )
        ]
    )
)]
#[OA\PathItem(
    path: "/api/v1/ping",
    get: new OA\Get(
        description: "Returns 'pong' if the service is alive.",
        summary: "Health check endpoint",
        tags: ["Health"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Pong response",
                content: new OA\JsonContent(
                    type: "string",
                    example: "pong"
                )
            )
        ]
    )
)]
#[OA\PathItem(
    path: '/api/v1/query',
    post: new OA\Post(
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
    )
)]
final class OpenApiInfo
{
}