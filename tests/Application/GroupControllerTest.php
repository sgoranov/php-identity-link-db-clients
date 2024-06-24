<?php
declare(strict_types=1);

namespace App\Tests\Application;

use App\DataFixtures\AppFixtures;
use App\Repository\GroupRepository;
use sgoranov\PHPIdentityLinkShared\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class GroupControllerTest extends WebTestCase
{
    public function testCreateGroupWithMissingBody(): void
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $client->request('POST', $router->generate('api_v1_create_group'));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateGroupWithEmptyName(): void
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
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
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => 'administrator',
        ];

        $client->request('POST', $router->generate('api_v1_create_group'), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('administrator',
            json_decode($response->getContent(), true)['response']['group']['name']);
    }

    public function testUpdateGroupWithInvalidUuid()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
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
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
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
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
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

    public function testDeleteGroupWithMissingUuid()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $client->request('DELETE', $router->generate('api_v1_update_group', [
            'id' => 'c9160e4a-3642-46e6-8b2d-b8aa11bf781b'
        ]));
        $response = $client->getResponse();

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testDeleteGroupSuccessfully()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $repository = $client->getContainer()->get(GroupRepository::class);
        list($group) = $repository->findBy(['name' => AppFixtures::GROUP_NAME]);

        $client->request('DELETE', $router->generate('api_v1_delete_group', [
            'id' => $group->getId()
        ]));
        $response = $client->getResponse();

        $this->assertSame(204, $response->getStatusCode());
    }
}