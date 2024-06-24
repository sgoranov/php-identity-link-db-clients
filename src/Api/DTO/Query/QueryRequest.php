<?php
declare(strict_types=1);

namespace App\Api\DTO\Query;

use App\Enum\EntityType;
use sgoranov\PHPIdentityLinkShared\Api\DTO\AbstractQueryRequest;
use Symfony\Component\Validator\Constraints as Assert;

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