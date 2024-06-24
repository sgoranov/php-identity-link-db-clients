<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Group;
use App\Entity\Secret;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: "test")]
#[When(env: "dev")]
class AppFixtures extends Fixture
{
    const GROUP_NAME = 'test_group';
    const CLIENT_NAME = 'test_client';
    const CLIENT_SECRET = 'f1080c74-ace7-44e8-8512-d2915d6dcde6';
    const CLIENT_SECRET_HINT = 'default secret';

    public function load(ObjectManager $manager): void
    {
        // Group
        $group = new Group();
        $group->setName(self::GROUP_NAME);
        $manager->persist($group);

        // User
        $client = new Client();
        $client->setIsPublic(false);
        $client->setDescription('private client');
        $client->setName(self::CLIENT_NAME);
        $client->setGrantTypes(['client_credentials', 'password', 'authorization_code', 'refresh_token', 'implicit']);
        $client->setRedirectUri('http://localhost/');
        $manager->persist($client);

        $currentDateTime = new \DateTime();

        $secret = new Secret();
        $secret->setClient($client);
        $secret->setPassword(self::CLIENT_SECRET);
        $secret->setPasswordHint(self::CLIENT_SECRET_HINT);
        $secret->setExpirationDateTime($currentDateTime->add(new \DateInterval('P1D')));
        $manager->persist($secret);

        $manager->flush();
    }
}
