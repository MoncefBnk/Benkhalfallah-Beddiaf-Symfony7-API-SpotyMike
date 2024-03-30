<?php

namespace App\Controller;

use DateTime;
use DateInterval;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Artist;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Entity\User;

class ArtistController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Artist::class);
    }

    #[Route('/artist', name: 'app_create_artist', methods: ['POST'])]
    public function createArtist(Request $request): JsonResponse
    {
        // Parse request data based on content type
        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        // Check if the required fields are present in the request data
        $requiredFields = ['userId', 'fullname', 'label'];

        foreach ($requiredFields as $field) {
            if (!isset($requestData[$field])) {
                return $this->json([
                    'message' => 'Missing required data: ' . $field,
                ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
            }
        }

        // Find the user
        $userId = $requestData['userId'];
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        // Check if the user exists
        if (!$user) {
            return $this->json([
                'message' => 'User not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Check if the user is already associated with an artist account
        if ($user->getArtist() !== null) {
            return $this->json([
                'message' => 'User already has an artist account',
            ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
        }

        // Check for data conformity
        $invalidData = [];

        if (isset($requestData['fullname']) && strlen($requestData['fullname']) > 90) {
            $invalidData[] = 'fullname';
        }

        if (isset($requestData['label']) && strlen($requestData['label']) > 90) {
            $invalidData[] = 'label';
        }

        if (!empty($invalidData)) {
            return $this->json([
                'message' => 'Invalid data',
                'data' => $invalidData,
            ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
        }

        // Check if an artist with the same name already exists
        $existingArtistWithFullname = $this->repository->findOneBy(['fullname' => $requestData['fullname']]);
        if ($existingArtistWithFullname) {
            throw new BadRequestHttpException('An artist with this name already exists');
        }

        // Create a new artist instance
        $artist = new Artist();
        $artist->setUserIdUser($user);
        $artist->setFullname($requestData['fullname']);
        $artist->setLabel($requestData['label']);
        $artist->setDescription($requestData['description'] ?? null);

        // Persist the artist entity
        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        // Return response
        return $this->json([
            'artist' => $artist->artistSerializer(),
            'message' => 'Your registration has been successfully processed',
            'path' => 'src/Controller/ArtistController.php',
        ], JsonResponse::HTTP_CREATED); // 201 Created
    }


    #[Route('/artists', name: 'app_get_artists', methods: ['GET'])]
    public function getAllArtists(): JsonResponse
    {
        $artists = $this->entityManager->getRepository(Artist::class)->findAll();

        $serializedArtists = [];
        foreach ($artists as $artist) {
            $serializedArtists[] = $artist->artistSerializer();
        }

        return $this->json([
            'artists' => $serializedArtists,
            'message' => 'All artists retrieved successfully!',
            'path' => 'src/Controller/ArtistController.php',
        ]);
    }

    #[Route('/artist/{id}', name: 'app_update_artist', methods: ['PUT'])]
    public function updateArtist(Request $request, int $id): JsonResponse
    {
        $artist = $this->entityManager->getRepository(Artist::class)->find($id);

        if (!$artist) {
            return $this->json([
                'message' => 'Artist not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }



        if (isset($requestData['fullname'])) {
            $existingArtistWithFullname = $this->repository->findOneBy(['fullname' => $requestData['fullname']]);
            if ($existingArtistWithFullname) {
                throw new BadRequestHttpException('un utilisateur avec ce nom existe deja ');
            } else {

                $artist->setFullname($requestData['fullname']);
            }
        }
        if (isset($requestData['label'])) {
            $artist->setLabel($requestData['label']);
        }
        if (isset($requestData['description'])) {
            $artist->setDescription($requestData['description']);
        }
        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $this->json([
            'artist' => $artist->artistSerializer(),
            'message' => 'Artist updated successfully!',
            'path' => 'src/Controller/ArtistController.php',
        ]);
    }


    #[Route('/artist/{id}', name: 'app_delete_artist', methods: ['DELETE'])]
    public function deleteArtist(int $id): JsonResponse
    {
        $artist = $this->entityManager->getRepository(Artist::class)->find($id);

        if (!$artist) {
            return $this->json([
                'message' => 'Artist not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($artist);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Artist deleted successfully!',
            'path' => 'src/Controller/ArtistController.php',
        ]);
    }
}
