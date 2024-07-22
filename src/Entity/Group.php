<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\Collection;
use sgoranov\PHPIdentityLinkShared\Validator\UniqueEntry;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: '`group`')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\CustomIdGenerator(class: "doctrine.uuid_generator")]
    private ?string $id = null;

    #[Groups(['create', 'update'])]
    #[UniqueEntry(groups: ['create', 'update'])]
    #[Assert\NotBlank(groups: ['create', 'update'])]
    #[Assert\Length(min: 1, max: 100, groups: ['create', 'update'])]
    #[Assert\Regex(pattern: '/^([\.\w0-9_ :-])+$/u', groups: ['create', 'update'])]
    #[ORM\Column(length: 100)]
    private string $name;

    #[Ignore]
    #[ORM\ManyToMany(targetEntity: Client::class, inversedBy: "groups")]
    #[ORM\JoinTable(name: "client_group")]
    private Collection $clients;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
