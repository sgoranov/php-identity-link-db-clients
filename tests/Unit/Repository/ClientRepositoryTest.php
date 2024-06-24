<?php
declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\DataFixtures\AppFixtures;
use App\Repository\ClientRepository;
use App\Repository\SecretRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClientRepositoryTest extends KernelTestCase
{
    private static ClientRepository $clientRepository;
    private static SecretRepository $secretRepository;
    private static EntityManagerInterface $entityManager;

    public static function setUpBeforeClass(): void
    {
        $container = static::getContainer();
        self::$entityManager = $container->get(EntityManagerInterface::class);
        self::$secretRepository = $container->get(SecretRepository::class);
        self::$clientRepository = $container->get(ClientRepository::class);
    }

    public function testGetClientByNameAndSecret(): void
    {
        $result = self::$clientRepository->getClientByNameAndSecret(
            AppFixtures::CLIENT_NAME, AppFixtures::CLIENT_SECRET, 'client_credentials');
        $this->assertEquals(AppFixtures::CLIENT_NAME, $result->getName());
    }

    public function testGetClientByNameAndSecretWithInvalidSecret(): void
    {
        $result = self::$clientRepository->getClientByNameAndSecret(
            AppFixtures::CLIENT_NAME, 'pass', 'client_credentials');
        $this->assertNull($result);
    }

    public function testGetClientByNameAndSecretWithExpiredSecret(): void
    {
        $currentDateTime = new \DateTime();
        $secret = self::$secretRepository->findOneBy(['passwordHint' => AppFixtures::CLIENT_SECRET_HINT]);
        $secret->setExpirationDateTime($currentDateTime->sub(new \DateInterval('P1D')));
        self::$entityManager->persist($secret);
        self::$entityManager->flush();

        $result = self::$clientRepository->getClientByNameAndSecret(
            AppFixtures::CLIENT_NAME, AppFixtures::CLIENT_SECRET, 'client_credentials');
        $this->assertNull($result);
    }

    public function testGetClientByNameAndSecretWithInvalidGrantType()
    {
        $client = self::$clientRepository->findOneBy(['name' => AppFixtures::CLIENT_NAME]);
        $client->setGrantTypes(['password', 'authorization_code', 'refresh_token', 'implicit']);
        self::$entityManager->persist($client);
        self::$entityManager->flush();

        $result = self::$clientRepository->getClientByNameAndSecret(
            AppFixtures::CLIENT_NAME, AppFixtures::CLIENT_SECRET, 'client_credentials');
        $this->assertNull($result);
    }
}