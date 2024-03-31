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

    #[Route('/artist/all', name: 'app_get_artists', methods: ['GET'])]
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
    
    #[Route('/artist', name: 'app_create_artist', methods: ['POST'])]
    public function createArtist(Request $request): JsonResponse
    {
        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        $requiredFields = ['userId', 'fullname', 'label'];

        foreach ($requiredFields as $field) {
            if (!isset($requestData[$field])) {
                return $this->json([
                    'message' => 'Une ou plusieurs données obligatoires sont manquantes : ' . $field,
                ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
            }
        }

        $userId = $requestData['userId'];
        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->json([
                'message' => 'User not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($user->getArtist() !== null) {
            return $this->json([
                'message' => 'Un compte utilisant est déjà un compte artiste',
            ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
        }

        $invalidData = [];

        if (isset($requestData['fullname']) && strlen($requestData['fullname']) > 90) {
            $invalidData[] = 'fullname';
        }

        if (isset($requestData['label']) && strlen($requestData['label']) > 90) {
            $invalidData[] = 'label';
        }

        if (!empty($invalidData)) {
            return $this->json([
                'message' => 'Une ou plusieurs donnée sont erronées',
                'data' => $invalidData,
            ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
        }

        $existingArtistWithFullname = $this->repository->findOneBy(['fullname' => $requestData['fullname']]);
        if ($existingArtistWithFullname) {
            throw new BadRequestHttpException("Un compte utilisant ce nom d'artiste est déjà enregistré");
        }

        $dateBirth = $user->getDateBirth();

        $today = new DateTime();
        $age = $today->diff($dateBirth)->y;

        if ($age < 16) {
            throw new BadRequestHttpException("l'age de l'utilisateur de ne permet pas (16 ans)");
        }

        $artist = new Artist();
        $artist->setUserIdUser($user);
        $artist->setFullname($requestData['fullname']);
        $artist->setLabel($requestData['label']);
        $artist->setDescription($requestData['description'] ?? null);

        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $this->json([
            'artist' => $artist->artistSerializer(),
            'message' => 'Your registration has been successfully processed',
            'path' => 'src/Controller/ArtistController.php',
        ], JsonResponse::HTTP_CREATED); // 201 Created
    }

    #[Route('/artist', name: 'app_update_artist', methods: ['PUT'])]
    public function updateArtist(Request $request): JsonResponse
    {
        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        $artistId = $requestData['id'] ?? null;

        if (!$artistId) {
            return $this->json([
                'message' => 'Missing artist ID in request body',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $artist = $this->entityManager->getRepository(Artist::class)->find($artistId);

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
                throw new BadRequestHttpException('An artist with this name already exists');
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
