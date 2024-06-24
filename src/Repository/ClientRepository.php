<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClientRepository extends ServiceEntityRepository
{
    private readonly SecretRepository $secretRepository;

    public function __construct(ManagerRegistry $registry, SecretRepository $secretRepository)
    {
        parent::__construct($registry, Client::class);
        $this->secretRepository = $secretRepository;
    }

    public function getClientByNameAndSecret(string $name, string $clientSecret, string $grantType): ?Client
    {
        $client = $this->findOneBy(['name' => $name]);
        if (is_null($client) || !in_array($grantType, $client->getGrantTypes(), true)
            || $client->isPublic() === true) {

            return null;
        }

        foreach ($this->secretRepository->getActiveSecrets($client) as $secret) {
            if (password_verify($clientSecret, $secret->getHashedPassword())) {
                return $client;
            }
        }

        return null;
    }
}
