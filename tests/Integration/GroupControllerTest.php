<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\DataFixtures\AppFixtures;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use sgoranov\IdentityLinkShared\Security\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class GroupControllerTest extends WebTestCase
{
    public function testCreateGroupWithMissingBody(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $client->request('POST', $router->generate('api_v1_create_group'));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateGroupWithEmptyName(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => '',
        ];

        $client->request('POST', $router->generate('api_v1_create_group'), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid name. This value should not be blank.',
            json_decode($response->getContent(), true)['error']);
    }

    public function testCreateGroup(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => 'administrator',
        ];

        $client->request('POST', $router->generate('api_v1_create_group'), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('administrator',
            json_decode($response->getContent(), true)['response']['group']['name']);
        $this->assertFalse(json_decode($response->getContent(), true)['response']['group']['isSystem']);
    }

    public function testCreateGroupCannotSetSystemFlag(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => 'administrator_system',
            'isSystem' => true,
        ];

        $client->request('POST', $router->generate('api_v1_create_group'), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringStartsWith('Extra attributes are not allowed',
            json_decode($response->getContent(), true)['error']);
    }

    public function testUpdateGroupWithInvalidUuid()
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => 'test_new',
        ];

        $client->request('PUT', $router->generate('api_v1_update_group', [
            'id' => 'uuid'
        ]), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateGroupWithInvalidName()
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => '%$@!',
        ];

        $repository = $client->getContainer()->get(GroupRepository::class);
        list($group) = $repository->findBy(['name' => AppFixtures::GROUP_NAME]);

        $client->request('PUT', $router->generate('api_v1_update_group', [
            'id' => $group->getId()
        ]), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testUpdateGroupSuccessfully()
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => 'test_new',
        ];

        $repository = $client->getContainer()->get(GroupRepository::class);
        list($group) = $repository->findBy(['name' => AppFixtures::GROUP_NAME]);

        $client->request('PUT', $router->generate('api_v1_update_group', [
            'id' => $group->getId()
        ]), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('test_new',
            json_decode($response->getContent(), true)['response']['group']['name']);
    }

    public function testUpdateSystemGroupIsForbidden(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $repository = $client->getContainer()->get(GroupRepository::class);
        list($group) = $repository->findBy(['name' => AppFixtures::GROUP_NAME]);
        $this->markGroupAsSystem($client->getContainer()->get(EntityManagerInterface::class), $group->getId());

        $client->request('PUT', $router->generate('api_v1_update_group', [
            'id' => $group->getId()
        ]), [], [], [], json_encode(['name' => 'test_new']));
        $response = $client->getResponse();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('System groups cannot be updated.',
            json_decode($response->getContent(), true)['error']);
    }

    public function testDeleteGroupWithMissingUuid()
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $client->request('DELETE', $router->generate('api_v1_update_group', [
            'id' => 'c9160e4a-3642-46e6-8b2d-b8aa11bf781b'
        ]));
        $response = $client->getResponse();

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDeleteGroupSuccessfully()
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $repository = $client->getContainer()->get(GroupRepository::class);
        list($group) = $repository->findBy(['name' => AppFixtures::GROUP_NAME]);

        $client->request('DELETE', $router->generate('api_v1_delete_group', [
            'id' => $group->getId()
        ]));
        $response = $client->getResponse();

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testDeleteSystemGroupIsForbidden(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $repository = $client->getContainer()->get(GroupRepository::class);
        list($group) = $repository->findBy(['name' => AppFixtures::GROUP_NAME]);
        $this->markGroupAsSystem($client->getContainer()->get(EntityManagerInterface::class), $group->getId());

        $client->request('DELETE', $router->generate('api_v1_delete_group', [
            'id' => $group->getId()
        ]));
        $response = $client->getResponse();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('System groups cannot be deleted.',
            json_decode($response->getContent(), true)['error']);
    }

    public function testFetchSystemGroupExposesSystemFlag(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $repository = $client->getContainer()->get(GroupRepository::class);
        list($group) = $repository->findBy(['name' => AppFixtures::GROUP_NAME]);
        $this->markGroupAsSystem($client->getContainer()->get(EntityManagerInterface::class), $group->getId());

        $client->request('GET', $router->generate('api_v1_fetch_group', [
            'id' => $group->getId()
        ]));
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getContent(), true)['response']['group']['isSystem']);
    }

    private function createAuthenticatedClient(): KernelBrowser
    {
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', 'test'));

        return $client;
    }

    private function markGroupAsSystem(EntityManagerInterface $entityManager, string $id): void
    {
        $entityManager->getConnection()->executeStatement(
            'UPDATE "group" SET is_system = true WHERE id = :id',
            ['id' => $id]
        );
        $entityManager->clear();
    }
}
