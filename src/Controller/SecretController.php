<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Secret;
use sgoranov\IdentityLinkShared\Serializer\Deserializer;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\SerializerInterface;

#[OA\Tag(name: 'Secret')]
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
    #[OA\Get(
        path: '/api/v1/secret/{id}',
        summary: 'Fetch a secret by ID',
        tags: ['Secret'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Secret data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'response',
                            properties: [
                                new OA\Property(property: 'secret', ref: '#/components/schemas/Secret')
                            ],
                            type: 'object'
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 404, description: 'Secret not found')
        ]
    )]
    public function fetch(#[MapEntity(id: 'id')] Secret $secret): Response
    {
        return new JsonResponse([
            'response' => ['secret' => json_decode($this->serializer->serialize($secret, 'json'))]
        ]);
    }

    #[Route('/secret', name: 'create_secret', methods: 'POST')]
    #[OA\Post(
        path: '/api/v1/secret',
        summary: 'Create a new secret',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/Secret')
        ),
        tags: ['Secret'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Secret created (password omitted)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'response',
                            properties: [
                                new OA\Property(property: 'secret', ref: '#/components/schemas/Secret')
                            ],
                            type: 'object'
                        )
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Validation error')
        ]
    )]
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
    #[OA\Put(
        path: '/api/v1/secret/{id}',
        summary: 'Update a secret',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/Secret')
        ),
        tags: ['Secret'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Secret updated'),
            new OA\Response(response: 400, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Secret not found')
        ]
    )]
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
    #[OA\Delete(
        path: '/api/v1/secret/{id}',
        summary: 'Delete a secret',
        tags: ['Secret'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            )
        ],
        responses: [
            new OA\Response(response: 204, description: 'Secret deleted'),
            new OA\Response(response: 404, description: 'Secret not found')
        ]
    )]
    public function delete(#[MapEntity(id: 'id')] Secret $secret): Response
    {
        $this->entityManager->remove($secret);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}