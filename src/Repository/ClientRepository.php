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

    public function getClientByIdAndSecret(string $id, string $clientSecret, ?string $grantType = null): ?Client
    {
        $client = $this->findOneBy(['id' => $id]);
        if (is_null($client) || $client->isPublic()) {
            return null;
        }

        if ($grantType !== null && !in_array($grantType, $client->getGrantTypes(), true)) {
            return null;
        }

        foreach ($this->secretRepository->getActiveSecrets($client) as $secret) {
            if (password_verify($clientSecret, $secret->getHashedPassword())) {
                return $client;
            }
        }

        return null;
    }

    public function getClientByName(string $name): ?Client
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * Returns the distinct scopes granted to a client through their current groups.
     *
     * @return list<string>
     */
    public function getScopes(Client $client, string $audience): array
    {
        return $this->getEntityManager()->getConnection()->fetchFirstColumn(
            <<<'SQL'
                SELECT DISTINCT gs.scope
                FROM group_scope gs
                INNER JOIN client_group cg ON cg.group_id = gs.group_id
                WHERE cg.client_id = :clientId
                  AND gs.audience_hash = :audienceHash
                  AND gs.audience = :audience
                ORDER BY gs.scope
                SQL,
            [
                'clientId' => $client->getId(),
                'audienceHash' => hash('sha256', $audience),
                'audience' => $audience,
            ]
        );
    }
}
