<?php

namespace App\Tests\Application;

use App\DataFixtures\AppFixtures;
use App\Repository\ClientRepository;
use App\Repository\SecretRepository;
use sgoranov\PHPIdentityLinkShared\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SecretControllerTest extends WebTestCase
{
    public function testCreateSecret(): void
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $container = static::getContainer();
        $clientRepository = $container->get(ClientRepository::class);
        $clientEntity = $clientRepository->findOneBy(['name' => AppFixtures::CLIENT_NAME]);

        $currentDateTime = new \DateTime();

        $content = [
            'password' => 'mypass',
            'passwordHint' => 'master password',
            'expirationDateTime' => $currentDateTime->add(new \DateInterval('P1D'))->format('Y-m-d H:i:s'),
            'client' => $clientEntity->getId(),
        ];

        $client->request('POST', $router->generate('api_v1_create_secret'), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('master password',
            json_decode($response->getContent(), true)['response']['secret']['passwordHint']);
        $this->assertArrayNotHasKey('password', json_decode($response->getContent(), true)['response']['secret']);
    }

    public function testUpdateSecret()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'passwordHint' => 'master password',
        ];

        $repository = $client->getContainer()->get(SecretRepository::class);
        list($secret) = $repository->findBy(['passwordHint' => AppFixtures::CLIENT_SECRET_HINT]);

        $client->request('PUT', $router->generate('api_v1_update_secret', [
            'id' => $secret->getId()
        ]), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('master password',
            json_decode($response->getContent(), true)['response']['secret']['passwordHint']);
        $this->assertArrayNotHasKey('password', json_decode($response->getContent(), true)['response']['secret']);
    }

    public function testUpdateSecretPassword()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $content = [
            'password' => 'new_pass',
        ];

        $repository = $client->getContainer()->get(SecretRepository::class);
        list($secret) = $repository->findBy(['passwordHint' => AppFixtures::CLIENT_SECRET_HINT]);

        $client->request('PUT', $router->generate('api_v1_update_secret', [
            'id' => $secret->getId()
        ]), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringStartsWith('Extra attributes are not allowed',
            json_decode($response->getContent(), true)['error']);
    }

    public function testUpdateSecretExpirationDateTime()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $currentDateTime = new \DateTime();

        $content = [
            'expirationDateTime' => $currentDateTime->add(new \DateInterval('P1D'))->format('Y-m-d H:i:s'),
        ];

        $repository = $client->getContainer()->get(SecretRepository::class);
        list($secret) = $repository->findBy(['passwordHint' => AppFixtures::CLIENT_SECRET_HINT]);

        $client->request('PUT', $router->generate('api_v1_update_secret', [
            'id' => $secret->getId()
        ]), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringStartsWith('Extra attributes are not allowed',
            json_decode($response->getContent(), true)['error']);
    }
}