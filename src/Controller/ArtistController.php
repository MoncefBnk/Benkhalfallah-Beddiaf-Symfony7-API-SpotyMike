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

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/artist/{userId}', name: 'app_create_artist', methods: ['POST'])]
    public function createArtist(Request $request, int $userId): JsonResponse
    {
        // Find the user
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        // Check if the user exists
        if (!$user) {
            return $this->json([
                'message' => 'User not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Parse request data based on content type
        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        switch ($requestData) {
            case 'fullname' && strlen($requestData['fullname']) > 90:
                throw new BadRequestHttpException('Une ou plusieurs donnée sont erronées');
            case 'label' && strlen($requestData['label']) > 90:
                throw new BadRequestHttpException('Une ou plusieurs donnée sont erronées');
        }


        // Get the birth date of the user
        $birthDate = $user->getBirthDate();

        // Calculate the age
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;

        // Check if the age is greater than 16
        if ($age < 16) {
            throw new BadRequestHttpException("l'age de l'utilisateur de ne permet pas (16 ans)");
        }

        // Create a new artist instance
        $artist = new Artist();
        $artist->setUserIdUser($user);
        $artist->setFullname($requestData['fullname'] ?? null);
        $artist->setLabel($requestData['label'] ?? null);
        $artist->setDescription($requestData['description'] ?? null);

        // Persist the artist entity
        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        // Return response
        return $this->json([
            'artist' => $artist->artistSerializer(),
            'message' => 'Votre inscription a bien été prise en compte',
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


        $data = json_decode($request->getContent(), true);


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
