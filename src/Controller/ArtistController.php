<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Artist;
use App\Entity\User;

class ArtistController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/artist/{userId}', name: 'app_create_artist', methods: ['POST'])]
    public function createArtist(Request $request, int $userId): JsonResponse
    {
        // Retrieve the User object associated with the provided userId
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->json([
                'message' => 'User not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Create a new Artist object and associate it with the User
        $artist = new Artist();
        $artist->setUserIdUser($user); // Set the User object

        // Decode the JSON request body to get artist data
        $data = json_decode($request->getContent(), true);

        // Set other properties of the Artist
        $artist->setFullname($data['fullname'] ?? null);
        $artist->setLabel($data['label'] ?? null);
        $artist->setDescription($data['description'] ?? null);

        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $this->json([
            'artist' => $artist->artistSerializer(),
            'message' => 'Artist created successfully!',
            'path' => 'src/Controller/ArtistController.php',
        ]);
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

        // Decode the JSON request body to get artist data
        $data = json_decode($request->getContent(), true);

        // Update artist properties if they exist in the request data
        if (isset($data['fullname'])) {
            $artist->setFullname($data['fullname']);
        }
        if (isset($data['label'])) {
            $artist->setLabel($data['label']);
        }
        if (isset($data['description'])) {
            $artist->setDescription($data['description']);
        }

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
