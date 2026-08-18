<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\DataFixtures\AppFixtures;
use App\Entity\Client;
use App\Entity\Group;
use App\Entity\GroupScope;
use App\Entity\Secret;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use sgoranov\IdentityLinkShared\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ScopeAuthorizationTest extends WebTestCase
{
    #[DataProvider('scopeProtectedEndpointProvider')]
    public function testEndpointAllowsTheRequiredScope(
        string $method,
        string $path,
        string $requiredScope,
        int $expectedStatus,
    ): void {
        $client = static::createClient();
        $client->loginUser(new User('test', [$requiredScope]));
        $path = $this->resolveFixturePath($client->getContainer()->get(EntityManagerInterface::class), $path);

        $client->request($method, $path);

        self::assertResponseStatusCodeSame($expectedStatus);
    }

    #[DataProvider('scopeProtectedEndpointProvider')]
    public function testEndpointRejectsAnAuthenticatedUserWithoutTheRequiredScope(
        string $method,
        string $path,
    ): void {
        $client = static::createClient();
        $client->loginUser(new User('test', ['clients.unrelated']));
        $path = $this->resolveFixturePath($client->getContainer()->get(EntityManagerInterface::class), $path);

        $client->request($method, $path);

        self::assertResponseStatusCodeSame(403);
    }

    public function testApiRejectsAnonymousRequests(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/client');

        self::assertResponseStatusCodeSame(401);
    }

    public function testLegacyAdminRoleDoesNotBypassScopeChecks(): void
    {
        $client = static::createClient();
        $client->loginUser(new User('test', ['ROLE_ADMIN']));

        $client->request('POST', '/api/v1/client');

        self::assertResponseStatusCodeSame(403);
    }

    public static function scopeProtectedEndpointProvider(): iterable
    {
        yield 'read client' => ['GET', '/api/v1/client/{clientId}', 'clients.read', 200];
        yield 'create client' => ['POST', '/api/v1/client', 'clients.write', 400];
        yield 'delete client' => ['DELETE', '/api/v1/client/{clientId}', 'clients.delete', 204];
        yield 'authenticate client' => ['POST', '/api/v1/auth', 'clients.auth', 400];
        yield 'query clients' => ['POST', '/api/v1/query', 'clients.query', 400];

        yield 'read group' => ['GET', '/api/v1/group/{groupId}', 'clients.groups.read', 200];
        yield 'create group' => ['POST', '/api/v1/group', 'clients.groups.write', 400];
        yield 'delete group' => ['DELETE', '/api/v1/group/{groupId}', 'clients.groups.delete', 204];
        yield 'read group scopes' => ['GET', '/api/v1/group/{groupId}/scope', 'clients.groups.read', 200];
        yield 'add group scope' => ['POST', '/api/v1/group/{groupId}/scope', 'clients.groups.write', 400];
        yield 'delete group scope' => [
            'DELETE',
            '/api/v1/group/{groupId}/scope/{groupScopeId}',
            'clients.groups.delete',
            204,
        ];

        yield 'read secret metadata' => ['GET', '/api/v1/secret/{secretId}', 'clients.secrets.read', 200];
        yield 'create secret' => ['POST', '/api/v1/secret', 'clients.secrets.write', 400];
        yield 'issue secret' => ['POST', '/api/v1/secret/issue', 'clients.secrets.write', 400];
        yield 'delete secret' => ['DELETE', '/api/v1/secret/{secretId}', 'clients.secrets.delete', 204];
    }

    private function resolveFixturePath(EntityManagerInterface $entityManager, string $path): string
    {
        $client = $entityManager->getRepository(Client::class)->findOneBy(['name' => AppFixtures::CLIENT_NAME]);
        $group = $entityManager->getRepository(Group::class)->findOneBy(['name' => AppFixtures::GROUP_NAME]);
        $secret = $entityManager->getRepository(Secret::class)->findOneBy(['client' => $client]);

        if (str_contains($path, '{groupScopeId}')) {
            $groupScope = new GroupScope();
            $groupScope->setGroup($group);
            $groupScope->setAudience('https://example.com/api');
            $groupScope->setScope('clients.test');
            $entityManager->persist($groupScope);
            $entityManager->flush();

            $path = str_replace('{groupScopeId}', $groupScope->getId(), $path);
        }

        return str_replace(
            ['{clientId}', '{groupId}', '{secretId}'],
            [$client->getId(), $group->getId(), $secret->getId()],
            $path,
        );
    }
}
