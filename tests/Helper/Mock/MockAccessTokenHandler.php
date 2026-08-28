<?php
declare(strict_types=1);

namespace App\Tests\Helper\Mock;

use sgoranov\IdentityLinkShared\Security\User;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class MockAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        return new UserBadge(
            'test',
            fn() => new User('test', [
                'clients.read',
                'clients.write',
                'clients.delete',
                'clients.auth',
                'clients.query',
                'clients.groups.read',
                'clients.groups.write',
                'clients.groups.delete',
                'clients.secrets.read',
                'clients.secrets.write',
                'clients.secrets.delete',
            ])
        );
    }
}
