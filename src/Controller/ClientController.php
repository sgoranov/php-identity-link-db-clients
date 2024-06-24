<?php
declare(strict_types=1);

namespace App\Controller;

use App\Api\DTO\Client\AuthRequest;
use App\Entity\Client;
use App\Repository\ClientRepository;
use sgoranov\PHPIdentityLinkShared\Serializer\Deserializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1', name: 'api_v1_')]
final class ClientController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface    $serializer,
        private readonly EntityManagerInterface $entityManager,
        private readonly Deserializer           $deserializer,
        private readonly ClientRepository       $repository,
    )
    {
    }

    #[Route('/client/{id}', name: 'fetch_client', methods: 'GET')]
    public function fetch(#[MapEntity(id: 'id')] Client $client): Response
    {
        return new JsonResponse([
            'response' => ['client' => json_decode($this->serializer->serialize($client, 'json'))]
        ]);
    }

    #[Route('/client', name: 'create_client', methods: 'POST')]
    public function create(): Response
    {
        $client = new Client();
        if (!$this->deserializer->deserialize($client, ['create'])) {
            return $this->deserializer->respondWithError();
        }

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return new JsonResponse([
            'response' => ['client' => json_decode($this->serializer->serialize($client, 'json'))]
        ], Response::HTTP_CREATED);
    }

    #[Route('/client/{id}', name: 'update_client', methods: 'PUT')]
    public function update(#[MapEntity(id: 'id')] Client $client): Response
    {
        if (!$this->deserializer->deserialize($client, ['update'])) {
            return $this->deserializer->respondWithError();
        }

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return new JsonResponse([
            'response' => ['client' => json_decode($this->serializer->serialize($client, 'json'))]
        ]);
    }

    #[Route('/client/{id}', name: 'delete_client', methods: 'DELETE')]
    public function delete(#[MapEntity(id: 'id')] Client $client): Response
    {
        $this->entityManager->remove($client);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/auth', name: 'auth', methods: 'POST')]
    public function auth(): Response
    {
        $authRequest = new AuthRequest();
        if (!$this->deserializer->deserialize($authRequest)) {
            return $this->deserializer->respondWithError();
        }

        $client = $this->repository->getClientByNameAndSecret($authRequest->getName(),
            $authRequest->getSecret(), $authRequest->getGrantType());

        if ($client === null) {
            return new JsonResponse([
                'error' => 'Auth failed. Please ensure your name and secret are correct. If you continue to experience issues, contact support.'
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'response' => ['client' => json_decode($this->serializer->serialize($client, 'json'))]
        ]);
    }
}
