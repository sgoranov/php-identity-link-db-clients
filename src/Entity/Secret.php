<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\SecretRepository;
use sgoranov\PHPIdentityLinkShared\Security\PasswordHashGenerator;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SecretRepository::class)]
#[ORM\Table(name: 'secret')]
class Secret
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    private ?string $id = null;

    #[Ignore]
    #[ORM\Column(name: 'password', length: 100)]
    private string $hashedPassword;

    #[Groups(['create'])]
    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Length(min: 1, max: 50, groups: ['create'])]
    private string $password;

    #[Groups(['create', 'update', 'response_without_password'])]
    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Length(min: 1, max: 500, groups: ['create', 'update'])]
    #[ORM\Column(length: 500)]
    private string $passwordHint;

    #[Groups(['create', 'response_without_password'])]
    #[Assert\NotNull(groups: ['create'])]
    #[Assert\GreaterThanOrEqual("now", message: "The date must be greater than or equal to now.", groups: ['create'])]
    #[ORM\Column]
    private \DateTime $expirationDateTime;

    #[Groups(['create', 'response_without_password'])]
    #[Assert\NotBlank(groups: ['create'])]
    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'secrets')]
    private Client $client;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getHashedPassword(): string
    {
        return $this->hashedPassword;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
        $this->hashedPassword = PasswordHashGenerator::create($password);
    }

    public function getPasswordHint(): string
    {
        return $this->passwordHint;
    }

    public function setPasswordHint(string $passwordHint): void
    {
        $this->passwordHint = $passwordHint;
    }

    public function getExpirationDateTime(): \DateTime
    {
        return $this->expirationDateTime;
    }

    public function setExpirationDateTime(\DateTime $expirationDateTime): void
    {
        $this->expirationDateTime = $expirationDateTime;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }
}