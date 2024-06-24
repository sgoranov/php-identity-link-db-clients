<?php
declare(strict_types=1);

namespace App\Tests\Application;

use App\DataFixtures\AppFixtures;
use App\Repository\ClientRepository;
use sgoranov\PHPIdentityLinkShared\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class ClientControllerTest extends WebTestCase
{
    public function testCreateClientWithMissingBody(): void
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $client->request('POST', $router->generate('api_v1_create_client'));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateClientWithEmptyName(): void
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => '',
            'description' => 'client description',
            'redirectUri' => 'http://localhost/',
            'grantTypes' => ['password', 'authorization_code', 'client_credentials'],
            'isPublic' => false,
        ];

        $client->request('POST', $router->generate('api_v1_create_client'), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid name. This value should not be blank.',
            json_decode($response->getContent(), true)['error']);
    }

    public function testCreateClientWithInvalidName(): void
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => '&&%$',
            'description' => 'client description',
            'redirectUri' => 'http://localhost/',
            'grantTypes' => ['password', 'authorization_code', 'client_credentials'],
            'isPublic' => false,
        ];

        $client->request('POST', $router->generate('api_v1_create_client'), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid name. This value is not valid.',
            json_decode($response->getContent(), true)['error']);
    }

    public function testCreateClientWithExistingName(): void
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => AppFixtures::CLIENT_NAME,
            'description' => 'client description',
            'redirectUri' => 'http://localhost/',
            'grantTypes' => ['password', 'authorization_code', 'client_credentials'],
            'isPublic' => false,
        ];

        $client->request('POST', $router->generate('api_v1_create_client'), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(sprintf('Invalid name. The value "%s" already exists.', AppFixtures::CLIENT_NAME),
            json_decode($response->getContent(), true)['error']);
    }

    public function testCreateClientSuccessfully(): void
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => 'test',
            'description' => 'client description',
            'redirectUri' => 'http://localhost/',
            'grantTypes' => ['password', 'authorization_code', 'client_credentials'],
            'isPublic' => false,
        ];

        $client->request('POST', $router->generate('api_v1_create_client'), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('test',
            json_decode($response->getContent(), true)['response']['client']['name']);
    }

    public function testUpdateClientWithInvalidUuid()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => 'new_name',
        ];

        $client->request('PUT', $router->generate('api_v1_update_client', [
            'id' => 'uuid'
        ]), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testUpdateClientSuccessfully()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'name' => 'name_new',
        ];

        $repository = $client->getContainer()->get(ClientRepository::class);
        list($entity) = $repository->findBy(['name' => AppFixtures::CLIENT_NAME]);

        $client->request('PUT', $router->generate('api_v1_update_client', [
            'id' => $entity->getId()
        ]), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('name_new',
            json_decode($response->getContent(), true)['response']['client']['name']);
    }

    public function testDeleteClientSuccessfully()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $repository = $client->getContainer()->get(ClientRepository::class);
        list($entity) = $repository->findBy(['name' => AppFixtures::CLIENT_NAME]);

        $client->request('DELETE', $router->generate('api_v1_delete_client', [
            'id' => $entity->getId()
        ]));
        $response = $client->getResponse();

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testFetchClientSuccessfully()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $repository = $client->getContainer()->get(ClientRepository::class);
        list($entity) = $repository->findBy(['name' => AppFixtures::CLIENT_NAME]);

        $client->request('GET', $router->generate('api_v1_fetch_client', [
            'id' => $entity->getId()
        ]));
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(AppFixtures::CLIENT_NAME,
            json_decode($response->getContent(), true)['response']['client']['name']);
    }
}