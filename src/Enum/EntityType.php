<?php
declare(strict_types=1);

namespace App\Enum;

use App\Entity\Group;
use App\Entity\Client;
use App\Entity\Secret;

enum EntityType: string
{
    case CLIENT = 'Client';
    case SECRET = 'Secret';
    case GROUP = 'Group';

    public function entity(): string {
        return EntityType::getEntity($this);
    }

    public static function fromString(string $value): self
    {
        return self::from($value);
    }
    public static function getEntity(self $value): string {
        return match ($value) {
            EntityType::CLIENT => Client::class,
            EntityType::SECRET => Secret::class,
            EntityType::GROUP => Group::class,
        };
    }
}
