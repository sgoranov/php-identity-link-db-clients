<?php
declare(strict_types=1);

namespace App\Api\DTO\Client;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: "AuthRequest",
    title: "AuthRequest",
    description: "DTO for client authentication request",
    required: ["id", "secret"],
    properties: [
        new OA\Property(
            property: "id",
            description: "Unique identifier of the client",
            type: "string",
            format: "uuid"
        ),
        new OA\Property(
            property: "secret",
            description: "Client secret",
            type: "string",
            maxLength: 200,
            minLength: 1
        ),
        new OA\Property(
            property: "grantType",
            description: "OAuth2 grant type",
            type: "string",
            enum: ["client_credentials", "password", "authorization_code", "refresh_token", "implicit"],
            nullable: true
        )
    ]
)]
class AuthRequest
{
    #[Assert\NotBlank]
    #[Assert\Uuid(message: 'The ID must be a valid UUID.')]
    public string $id;

    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 200)]
    public string $secret;

    #[Assert\Choice(['client_credentials', 'password', 'authorization_code', 'refresh_token', 'implicit'])]
    public ?string $grantType = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function getGrantType(): ?string
    {
        return $this->grantType;
    }

    public function setGrantType(?string $grantType): void
    {
        $this->grantType = $grantType;
    }
}
