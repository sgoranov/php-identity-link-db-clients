<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Secret;
use sgoranov\PHPIdentityLinkShared\Serializer\Deserializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1', name: 'api_v1_')]
final class SecretController extends AbstractController
{

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $entityManager,
        private readonly Deserializer $deserializer,
    )
    {
    }

    #[Route('/secret/{id}', name: 'fetch_secret', methods: 'GET')]
    public function fetch(#[MapEntity(id: 'id')] Secret $secret): Response
    {
        return new JsonResponse([
            'response' => ['secret' => json_decode($this->serializer->serialize($secret, 'json'))]
        ]);
    }

    #[Route('/secret', name: 'create_secret', methods: 'POST')]
    public function create(): Response
    {
        $secret = new Secret();
        if (!$this->deserializer->deserialize($secret, ['create'])) {
            return $this->deserializer->respondWithError();
        }

        $this->entityManager->persist($secret);
        $this->entityManager->flush();

        $context = (new ObjectNormalizerContextBuilder())
            ->withGroups('response_without_password')
            ->toArray();

        return new JsonResponse([
            'response' => ['secret' => json_decode($this->serializer->serialize($secret, 'json', $context))]
        ], Response::HTTP_CREATED);
    }

    #[Route('/secret/{id}', name: 'update_secret', methods: 'PUT')]
    public function update(#[MapEntity(id: 'id')] Secret $secret): Response
    {
        if (!$this->deserializer->deserialize($secret, ['update'])) {
            return $this->deserializer->respondWithError();
        }

        $this->entityManager->persist($secret);
        $this->entityManager->flush();

        return new JsonResponse([
            'response' => ['secret' => json_decode($this->serializer->serialize($secret, 'json'))]
        ]);
    }

    #[Route('/secret/{id}', name: 'delete_secret', methods: 'DELETE')]
    public function delete(#[MapEntity(id: 'id')] Secret $secret): Response
    {
        $this->entityManager->remove($secret);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}