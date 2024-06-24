<?php
declare(strict_types=1);

namespace App\Tests\Application;

use App\DataFixtures\AppFixtures;
use sgoranov\PHPIdentityLinkShared\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class QueryControllerTest extends WebTestCase
{
    public function testQueryWithLimitAsString()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $client->request('POST', $router->generate('api_v1_query', []), [], [], [], json_encode([
            'type' => 'Client',
            'limit' => '10',
            'offset' => 0,
        ]));

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('The limit property must be of type int, but string was provided.',
            json_decode($response->getContent(), true)['error']);
    }

    public function testQueryWithNegativeLimit()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $client->request('POST', $router->generate('api_v1_query', []), [], [], [], json_encode([
            'type' => 'Client',
            'limit' => -5,
            'offset' => 0,
        ]));

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid limit. This value should be either positive or zero.',
            json_decode($response->getContent(), true)['error']);
    }

    public function testQueryWithNegativeOffset()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        $client->request('POST', $router->generate('api_v1_query', []), [], [], [], json_encode([
            'type' => 'Client',
            'limit' => 10,
            'offset' => -1,
        ]));

        $response = $client->getResponse();
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('Invalid offset. This value should be either positive or zero.',
            json_decode($response->getContent(), true)['error']);
    }

    public function testQuerySuccessfully()
    {
        $client = static::createClient();
        $testUser = new User('test', ['ROLE_ADMIN']);
        $client->loginUser($testUser);
        $router = $client->getContainer()->get(RouterInterface::class);

        // fetch clients with at least one active secret
        $client->request('POST', $router->generate('api_v1_query', []), [], [], [], json_encode([
            'type' => 'Client',
            'alias' => 't',
            'joins' => [
                's' => 't.secrets'
            ],
            'query' => 's.expirationDateTime >= CURRENT_TIMESTAMP() AND t.name = :name',
            'parameters' => [
                'name' => AppFixtures::CLIENT_NAME,
            ],
            'orderBy' => [
                't.name' => 'ASC'
            ]
        ]));

        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(false,
            json_decode($response->getContent(), true)['response']['hasMore']);
        $this->assertSame(1,
            count(json_decode($response->getContent(), true)['response']['result']));
    }
}