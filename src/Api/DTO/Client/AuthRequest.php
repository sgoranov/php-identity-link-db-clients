<?php
declare(strict_types=1);

namespace App\Api\DTO\Client;

use Symfony\Component\Validator\Constraints as Assert;

class AuthRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 200)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 200)]
    public string $secret;

    #[Assert\Choice(['client_credentials', 'password', 'authorization_code', 'refresh_token', 'implicit'])]
    public ?string $grantType = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
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
