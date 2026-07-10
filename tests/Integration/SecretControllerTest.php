<?php

namespace App\Tests\Integration;

use App\DataFixtures\AppFixtures;
use App\Repository\ClientRepository;
use App\Repository\SecretRepository;
use Doctrine\ORM\EntityManagerInterface;
use sgoranov\IdentityLinkShared\Security\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SecretControllerTest extends WebTestCase
{
    public function testCreateSecret(): void
    {
        $client = $this->createAuthenticatedClient();
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
        $this->assertFalse(json_decode($response->getContent(), true)['response']['secret']['isSystem']);
    }

    public function testCreateSecretCannotSetSystemFlag(): void
    {
        $client = $this->createAuthenticatedClient();
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
            'isSystem' => true,
        ];

        $client->request('POST', $router->generate('api_v1_create_secret'), [], [], [], json_encode($content));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringStartsWith('Extra attributes are not allowed',
            json_decode($response->getContent(), true)['error']);
    }

    public function testUpdateSecret()
    {
        $client = $this->createAuthenticatedClient();
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

    public function testUpdateSystemSecretIsForbidden(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $repository = $client->getContainer()->get(SecretRepository::class);
        list($secret) = $repository->findBy(['passwordHint' => AppFixtures::CLIENT_SECRET_HINT]);
        $this->markSecretAsSystem($client->getContainer()->get(EntityManagerInterface::class), $secret->getId());

        $client->request('PUT', $router->generate('api_v1_update_secret', [
            'id' => $secret->getId()
        ]), [], [], [], json_encode(['passwordHint' => 'master password']));
        $response = $client->getResponse();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('System secrets cannot be updated.',
            json_decode($response->getContent(), true)['error']);
    }

    public function testDeleteSystemSecretIsForbidden(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $repository = $client->getContainer()->get(SecretRepository::class);
        list($secret) = $repository->findBy(['passwordHint' => AppFixtures::CLIENT_SECRET_HINT]);
        $this->markSecretAsSystem($client->getContainer()->get(EntityManagerInterface::class), $secret->getId());

        $client->request('DELETE', $router->generate('api_v1_delete_secret', [
            'id' => $secret->getId()
        ]));
        $response = $client->getResponse();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('System secrets cannot be deleted.',
            json_decode($response->getContent(), true)['error']);
    }

    public function testFetchSystemSecretExposesSystemFlag(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = $client->getContainer()->get(RouterInterface::class);

        $repository = $client->getContainer()->get(SecretRepository::class);
        list($secret) = $repository->findBy(['passwordHint' => AppFixtures::CLIENT_SECRET_HINT]);
        $this->markSecretAsSystem($client->getContainer()->get(EntityManagerInterface::class), $secret->getId());

        $client->request('GET', $router->generate('api_v1_fetch_secret', [
            'id' => $secret->getId()
        ]));
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue(json_decode($response->getContent(), true)['response']['secret']['isSystem']);
    }

    public function testUpdateSecretPassword()
    {
        $client = $this->createAuthenticatedClient();
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
        $client = $this->createAuthenticatedClient();
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

    public function testIssueWithoutExpirationPeriod(): void
    {
        $client = $this->createAuthenticatedClient();

        $router = $client->getContainer()->get(RouterInterface::class);

        $container = static::getContainer();
        $clientRepository = $container->get(ClientRepository::class);
        $clientEntity = $clientRepository->findOneBy(['name' => AppFixtures::CLIENT_NAME]);

        $payload = [
            'client' => $clientEntity->getId(),
            // no expirationPeriod
            // no passwordHint
        ];

        $client->request('POST', $router->generate('api_v1_issue_secret'), [], [], [], json_encode($payload));
        $response = $client->getResponse();

        // expirationPeriod is required by validation -> expect 400 with a validation error message
        $this->assertSame(400, $response->getStatusCode(), $response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('expirationPeriod', $data['error']);
        $this->assertStringContainsString('should not be blank', $data['error']);
    }

    public function testIssueWithInvalidExpirationPeriod(): void
    {
        $client = $this->createAuthenticatedClient();

        $router = $client->getContainer()->get(RouterInterface::class);

        $container = static::getContainer();
        $clientRepository = $container->get(ClientRepository::class);
        $clientEntity = $clientRepository->findOneBy(['name' => AppFixtures::CLIENT_NAME]);

        $payload = [
            'client' => $clientEntity->getId(),
            'expirationPeriod' => '99x', // invalid
        ];

        $client->request('POST', $router->generate('api_v1_issue_secret'), [], [], [], json_encode($payload));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertStringContainsString('Choose one of', $data['error']);
    }

    public function testIssueWithoutPasswordHintSetsDefault(): void
    {
        $client = $this->createAuthenticatedClient();

        $router = $client->getContainer()->get(RouterInterface::class);

        $container = static::getContainer();
        $clientRepository = $container->get(ClientRepository::class);
        $clientEntity = $clientRepository->findOneBy(['name' => AppFixtures::CLIENT_NAME]);

        $payload = [
            'client' => $clientEntity->getId(),
            'expirationPeriod' => '1d',
            // no passwordHint
        ];

        $client->request('POST', $router->generate('api_v1_issue_secret'), [], [], [], json_encode($payload));
        $response = $client->getResponse();

        $this->assertSame(201, $response->getStatusCode());

        $secret = json_decode($response->getContent(), true)['response']['secret'];
        $this->assertSame('auto generated', $secret['passwordHint']);
    }

    public function testIssueWithPasswordHint(): void
    {
        $client = $this->createAuthenticatedClient();

        $router = $client->getContainer()->get(RouterInterface::class);

        $container = static::getContainer();
        $clientRepository = $container->get(ClientRepository::class);
        $clientEntity = $clientRepository->findOneBy(['name' => AppFixtures::CLIENT_NAME]);

        $payload = [
            'client' => $clientEntity->getId(),
            'expirationPeriod' => '1d',
            'passwordHint' => 'custom hint',
        ];

        $client->request('POST', $router->generate('api_v1_issue_secret'), [], [], [], json_encode($payload));
        $response = $client->getResponse();

        $this->assertSame(201, $response->getStatusCode());

        $secret = json_decode($response->getContent(), true)['response']['secret'];
        $this->assertSame('custom hint', $secret['passwordHint']);
    }

    public function testIssueWithInvalidClientUuid(): void
    {
        $client = $this->createAuthenticatedClient();

        $router = $client->getContainer()->get(RouterInterface::class);

        $payload = [
            'client' => '00000000-0000-0000-0000-000000000000',
            'expirationPeriod' => '1d',
            'passwordHint' => 'hint',
        ];

        $client->request('POST', $router->generate('api_v1_issue_secret'), [], [], [], json_encode($payload));
        $response = $client->getResponse();

        $this->assertSame(400, $response->getStatusCode(), $response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testIssueSuccessfulIncludesGeneratedPassword(): void
    {
        $client = $this->createAuthenticatedClient();

        $router = $client->getContainer()->get(RouterInterface::class);

        $container = static::getContainer();
        $clientRepository = $container->get(ClientRepository::class);
        $clientEntity = $clientRepository->findOneBy(['name' => AppFixtures::CLIENT_NAME]);

        $payload = [
            'client' => $clientEntity->getId(),
            'expirationPeriod' => '1d',
            'passwordHint' => 'any',
        ];

        $client->request('POST', $router->generate('api_v1_issue_secret'), [], [], [], json_encode($payload));
        $response = $client->getResponse();

        $this->assertSame(201, $response->getStatusCode(), $response->getContent());

        $secret = json_decode($response->getContent(), true)['response']['secret'];

        // Expect password to be present and to be 64 hex characters (32 random bytes, hex-encoded)
        $this->assertArrayHasKey('password', $secret);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/i', $secret['password']);
    }

    public function testIssueWithExpirationPeriodComputesExpirationDateTime(): void
    {
        // Bootstrap once to fetch test data from the container
        $bootstrapClient = static::createClient();
        $container = $bootstrapClient->getContainer();

        $clientRepository = $container->get(ClientRepository::class);
        $clientEntity = $clientRepository->findOneBy(['name' => AppFixtures::CLIENT_NAME]);

        // Shut the kernel down before creating fresh clients in the loop
        static::ensureKernelShutdown();

        $periods = [
            '1d' => new \DateInterval('P1D'),
            '1w' => new \DateInterval('P1W'),
            '1m' => new \DateInterval('P1M'),
        ];

        foreach ($periods as $periodKey => $interval) {
            // Fresh client per iteration to ensure a clean security context
            $client = static::createClient();
            $testUser = new User('test', ['ROLE_ADMIN']);
            $client->loginUser($testUser);

            // Router must come from THIS client's container
            $router = $client->getContainer()->get(RouterInterface::class);

            $payload = [
                'client' => $clientEntity->getId(),
                'expirationPeriod' => $periodKey,
                'passwordHint' => 'ph',
            ];

            $before = new \DateTimeImmutable('now');
            $client->request(
                'POST',
                $router->generate('api_v1_issue_secret'),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($payload)
            );

            $response = $client->getResponse();
            $this->assertSame(201, $response->getStatusCode(), $response->getContent());

            $secret = json_decode($response->getContent(), true)['response']['secret'];
            $this->assertArrayHasKey('expirationDateTime', $secret);

            $actual = new \DateTimeImmutable($secret['expirationDateTime']);

            // Allow tolerance for processing time
            $expectedLowerBound = $before->add($interval)->sub(new \DateInterval('PT1M'));
            $expectedUpperBound = (new \DateTimeImmutable('now'))->add($interval)->add(new \DateInterval('PT1M'));

            $this->assertGreaterThanOrEqual(
                $expectedLowerBound->getTimestamp(),
                $actual->getTimestamp(),
                sprintf('expirationDateTime is earlier than expected for %s', $periodKey)
            );
            $this->assertLessThanOrEqual(
                $expectedUpperBound->getTimestamp(),
                $actual->getTimestamp(),
                sprintf('expirationDateTime is later than expected for %s', $periodKey)
            );

            // Optionally shutdown between iterations to avoid lingering state
            static::ensureKernelShutdown();
        }
    }

    private function createAuthenticatedClient(): KernelBrowser
    {
        $client = static::createClient();
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', 'test'));

        return $client;
    }

    private function markSecretAsSystem(EntityManagerInterface $entityManager, string $id): void
    {
        $entityManager->getConnection()->executeStatement(
            'UPDATE secret SET is_system = true WHERE id = :id',
            ['id' => $id]
        );
        $entityManager->clear();
    }
}
