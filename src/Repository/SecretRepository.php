<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Secret;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SecretRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Secret::class);
    }

    public function getActiveSecrets(Client $client): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.expirationDateTime >= :now')
            ->setParameter('now', new \DateTime())
            ->andWhere('t.client = :client')
            ->setParameter('client', $client)
            ->getQuery()
            ->getResult()
        ;
    }
}
