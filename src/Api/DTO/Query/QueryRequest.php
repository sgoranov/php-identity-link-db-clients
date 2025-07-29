<?php
declare(strict_types=1);

namespace App\Api\DTO\Query;

use App\Enum\EntityType;
use sgoranov\IdentityLinkShared\Api\DTO\AbstractQueryRequest;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'QueryRequest',
    title: 'Query Request',
    description: 'Request body for generic entity querying',
    required: ['type'],
    properties: [
        new OA\Property(
            property: 'type',
            description: 'The entity type being queried (e.g., "Client", "Group", "Secret")',
            type: 'string',
            example: 'Group'
        ),
        new OA\Property(
            property: 'alias',
            description: 'Alias used in the query builder',
            type: 'string',
            default: 't',
            example: 't'
        ),
        new OA\Property(
            property: 'query',
            description: 'DQL-compatible where clause, e.g., "t.name = :name"',
            type: 'string',
            example: 'c.name = :name',
            nullable: true
        ),
        new OA\Property(
            property: 'joins',
            description: 'Joins to perform (DQL-style)',
            type: 'object',
            example: ['c' => 't.clients'],
            nullable: true,
            additionalProperties: true
        ),
        new OA\Property(
            property: 'orderBy',
            description: 'Ordering of results',
            type: 'object',
            example: ['t.name' => 'ASC'],
            nullable: true,
            additionalProperties: true
        ),
        new OA\Property(
            property: 'parameters',
            description: 'Parameters used in query, used with query string placeholders',
            type: 'object',
            example: ['name' => 'myclient'],
            nullable: true,
            additionalProperties: true
        ),
        new OA\Property(
            property: 'limit',
            description: 'Maximum number of results to return',
            type: 'integer',
            format: 'int32',
            example: 15
        ),
        new OA\Property(
            property: 'offset',
            description: 'Number of records to skip',
            type: 'integer',
            format: 'int32',
            example: 0
        )
    ],
    type: 'object'
)]
class QueryRequest extends AbstractQueryRequest
{
    #[Assert\NotBlank]
    private EntityType $type;

    public function getType(): string
    {
        return $this->type->entity();
    }

    public function setType(string $type): void
    {
        $this->type = EntityType::fromString($type);
    }
}