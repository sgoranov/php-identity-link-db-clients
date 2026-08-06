<?php
declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\DataFixtures\AppFixtures;
use App\Entity\GroupScope;
use App\Repository\ClientRepository;
use App\Repository\GroupRepository;
use App\Repository\SecretRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClientRepositoryTest extends KernelTestCase
{
    public function testGetScopesReturnsScopesForAudienceFromClientGroups(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $repository = $container->get(ClientRepository::class);
        $group = $container->get(GroupRepository::class)
            ->findOneBy(['name' => AppFixtures::GROUP_NAME]);
        $client = $repository->getClientByName(AppFixtures::CLIENT_NAME);

        $groupScope = new GroupScope();
        $groupScope->setGroup($group);
        $groupScope->setAudience('https://example.com/orders');
        $groupScope->setScope('orders:read');
        $entityManager->persist($groupScope);
        $entityManager->flush();

        $this->assertSame(
            ['orders:read'],
            $repository->getScopes($client, 'https://example.com/orders')
        );
    }

    public function testGetScopesDoesNotReturnScopesForAnotherAudience(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(ClientRepository::class);
        $client = $repository->getClientByName(AppFixtures::CLIENT_NAME);

        $this->assertSame(
            [],
            $repository->getScopes($client, 'https://example.com/unknown')
        );
    }

    public function testGetClientByIdAndSecret(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(ClientRepository::class);
        $client = $repository->getClientByName(AppFixtures::CLIENT_NAME);
        $result = $repository->getClientByIdAndSecret(
            $client->getId(), AppFixtures::CLIENT_SECRET, 'client_credentials');
        $this->assertEquals(AppFixtures::CLIENT_NAME, $result->getName());
    }

    public function testGetClientByIdAndSecretWithInvalidSecret(): void
    {
        self::bootKernel();
        $repository = static::getContainer()->get(ClientRepository::class);
        $client = $repository->getClientByName(AppFixtures::CLIENT_NAME);
        $result = $repository->getClientByIdAndSecret(
            $client->getId(), 'pass', 'client_credentials');
        $this->assertNull($result);
    }

    public function testGetClientByIdAndSecretWithExpiredSecret(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $repository = $container->get(ClientRepository::class);
        $currentDateTime = new \DateTime();
        $secret = $container->get(SecretRepository::class)
            ->findOneBy(['passwordHint' => AppFixtures::CLIENT_SECRET_HINT]);
        $secret->setExpirationDateTime($currentDateTime->sub(new \DateInterval('P1D')));
        $entityManager->persist($secret);
        $entityManager->flush();

        $client = $repository->getClientByName(AppFixtures::CLIENT_NAME);
        $result = $repository->getClientByIdAndSecret(
            $client->getId(), AppFixtures::CLIENT_SECRET, 'client_credentials');
        $this->assertNull($result);
    }

    public function testGetClientByIdAndSecretWithInvalidGrantType(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $repository = $container->get(ClientRepository::class);
        $client = $repository->findOneBy(['name' => AppFixtures::CLIENT_NAME]);
        $client->setGrantTypes(['password', 'authorization_code', 'refresh_token', 'implicit']);
        $entityManager->persist($client);
        $entityManager->flush();

        $client = $repository->getClientByName(AppFixtures::CLIENT_NAME);
        $result = $repository->getClientByIdAndSecret(
            $client->getId(), AppFixtures::CLIENT_SECRET, 'client_credentials');
        $this->assertNull($result);
    }
}
