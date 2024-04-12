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
use App\Entity\Label;
use App\Entity\LabelHasArtist;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class ArtistController extends AbstractController
{
    private $entityManager;
    private $repository;
    private $tokenVerifier;

    public function __construct(EntityManagerInterface $entityManager, TokenVerifierService $tokenVerifier)
    {
        $this->entityManager = $entityManager;
        $this->tokenVerifier = $tokenVerifier;
        $this->repository = $entityManager->getRepository(Artist::class);
    }


    //add authentification middleware
    


    //get artist by fullname if the fullname of the user authenticated is an artist and has a matching fullname
    #[Route('/artist/{fullname}', name: 'app_get_artist_by_fullname', methods: ['GET'])]
    public function getArtistByFullname(Request $request, string $fullname): JsonResponse
    {
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if (gettype($dataMiddellware) == 'boolean') {
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }

        if (!$dataMiddellware) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $dataMiddellware;

    // if fullname is empty return error
        if (empty($fullname)) {
            return $this->json([
                'error' => true,
                'message' => 'Le nom d\'artiste est obligatoire pour cette requête.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        //full name validation
        if (!preg_match('/^[a-zA-Z\s]+$/', $fullname)) {
            return $this->json([
                'error' => true,
                'message' => 'Le format du nom d\'artiste fourni est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        //if artist is user has artist && artist fullname is the same as the one in the url TOOOO DOOOOO !!!!!!!!!!!!!!!!
        if ($user->getArtist() !== null && $user->getArtist()->getFullname() === $fullname) {
            $artist = $user->getArtist();
            $serializedArtists = $artist->artistAllSerializer();
            return $this->json([
                'error' => false,
                'artist' => $serializedArtists,
            ]);
        } 
        else {
            $artist = $this->repository->findOneBy(['fullname' => $fullname]);

            if (!$artist) {
                return $this->json([
                    'error' => true,
                    'message' => 'Aucun artiste trouvé correspondant au nom fourni.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $serializedArtists = $artist->artistAllSerializer();



            return $this->json([
                'error' => false,
                'artist' => $serializedArtists,
            ]);
        }
    }

    #[Route('/artist', name: 'app_get_artists', methods: ['GET'])]
    public function getAllArtists(Request $request): JsonResponse
    {
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if (gettype($dataMiddellware) == 'boolean') {
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }

        if (!$dataMiddellware) {
            return $this->json([
                'error' => true,
                'message' => 'Authentication required. You must be logged in to perform this action.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        // Your existing code to fetch artists
        $artists = $this->entityManager->getRepository(Artist::class)->findAll();

        $serializedArtists = [];
        foreach ($artists as $artist) {
            $serializedArtists[] = $artist->artistAllSerializer();
        }

        //i want to add pagination where i display 5 artists per page and i can navigate to the next page using an attribute in the body of the get request which refers to the index of the page


        return $this->json([
            'error' => false,
            'artists' => $serializedArtists,
            'message' => 'Information des artistes récupérées avec succès.',
            // 'pagination' =>  $paginatedArtists,
        ]);
    }



    #[Route('/artist', name: 'app_create_artist', methods: ['POST'])]
    public function createArtist(Request $request): JsonResponse
    {
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if (gettype($dataMiddellware) == 'boolean') {
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }

        if (!$dataMiddellware) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $dataMiddellware;

        if (!$user) {
            return $this->json([
                'message' => 'User not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }


        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        if ($user->getArtist() !== null) {
            //updating artist

            $invalidData = [];
            $invalidIdLabel = false;

            if (isset($requestData['fullname'])) {
                $fullname = $requestData['fullname'];
                // Validate lastname format
                if (!preg_match('/^[a-zA-Z\s]+$/', $fullname)) {
                    $invalidData[] = 'fullname';
                }
                if (strlen($fullname) > 20) {
                    $invalidData[] = 'fullname';
                }
                if (!empty($invalidData)) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Les parametres fournies sont invalides ou incomplètes. Veuillez verifier les données soumises.',
                    ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
                }
                $existingArtistWithFullname = $this->repository->findOneBy(['fullname' => $requestData['fullname']]);

                if ($existingArtistWithFullname) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Ce nom d\'artiste est déjà utilisé. Veuillez en choisir un autre.',
                    ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
                }
            }

            if (isset($requestData['label']) && strlen($requestData['label']) > 60) {
                $invalidIdLabel = true;
                if ($invalidIdLabel) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Le format de l\'id du label est invalide.',
                    ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
                }
            }

            $artist = $user->getArtist();

            if (isset($requestData['fullname'])) {
                $artist->setFullname($requestData['fullname']);
            }
            if (isset($requestData['label'])) {
                $labelId = $requestData['label'];
                $label = $this->entityManager->getRepository(Label::class)->find($labelId);


                if (!$label) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Le format de l\'id du label est invalide.',
                    ], JsonResponse::HTTP_BAD_REQUEST);
                }
                $artist = $user->getArtist();
                $oldlabelHasArtist = $this->entityManager->getRepository(LabelHasArtist::class)->findOneBy(['idArtist' => $artist, 'leftAt' => null]);
                if ($oldlabelHasArtist) {
                    $oldlabelHasArtist->setLeftAt(new DateTime());
                    $this->entityManager->persist($oldlabelHasArtist);
                    $this->entityManager->flush();
                }
                $artist->setUpdatedAt(new DateTimeImmutable());
                $labelHasArtist = new LabelHasArtist();
                $labelHasArtist->setIdArtist($artist);
                $labelHasArtist->setIdLabel($label);
                $labelHasArtist->setJoinedAt(new DateTime());
                $labelHasArtist->setLeftAt(null);
                $this->entityManager->persist($labelHasArtist);
                $this->entityManager->flush();
            }

            if (isset($requestData['description'])) {
                $artist->setDescription($requestData['description'] ?? null);
            }
            $this->entityManager->persist($artist);
            $this->entityManager->flush();
            return $this->json([
                'success' => true,
                'message' => 'Les informations de l\'artiste ont été mises à jour avec succès.',
                'id_artist' => strval($artist->getId()),
            ], JsonResponse::HTTP_CREATED);
        } else {

            $requiredFields = ['fullname', 'label'];

            //if one of the requeried fields is missing error field 
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($requestData[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return $this->json([
                    'error' => true,
                    'message' => 'l\'id du label et le fullname sont obligatoires.',
                ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
            }

            $invalidData = [];
            $invalidIdLabel = false;

            if (isset($requestData['fullname'])) {
                $fullname = $requestData['fullname'];
                // Validate lastname format
                if (!preg_match('/^[a-zA-Z\s]+$/', $fullname)) {
                    $invalidData[] = 'fullname';
                }
                if (strlen($fullname) > 20) {
                    $invalidData[] = 'fullname';
                }
            }

            if (isset($requestData['label']) && strlen($requestData['label']) > 60) {
                $invalidIdLabel = true;
            }

            if (!empty($invalidData)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Les données fournies sont invalides ou incomplètes.',
                ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
            }

            if ($invalidIdLabel) {
                return $this->json([
                    'error' => true,
                    'message' => 'Le format de l\'id du label est invalide.',
                ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
            }

            $existingArtistWithFullname = $this->repository->findOneBy(['fullname' => $requestData['fullname']]);

            if ($existingArtistWithFullname) {
                return $this->json([
                    'error' => true,
                    'message' => 'Ce nom d\'artiste est déjà pris. Veuillez en choisir un autre.',
                ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
            }



            $dateBirth = $user->getDateBirth();

            $today = new DateTime();
            $age = $today->diff($dateBirth)->y;

            if ($age < 16) {
                return $this->json([
                    'error' => true,
                    'message' => 'Vous devez avoir au moins 16 ans pour être artiste.',
                ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
            }

            $labelId = $requestData['label'];

            $label = $this->entityManager->getRepository(Label::class)->find($labelId);

            if (!$label) {
                return $this->json([
                    'error' => true,
                    'message' => 'Invalid label provided',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

            $artist = new Artist();
            $artist->setUserIdUser($user);
            $artist->setFullname($requestData['fullname']);
            $artist->setDescription($requestData['description'] ?? null);
            $artist->setActive('active');
            $artist->setCreatedAt(new DateTimeImmutable());
            $artist->setUpdatedAt(new DateTimeImmutable());
            //with the label id we can create a new labelHasArtist
            $labelHasArtist = new LabelHasArtist();
            $labelHasArtist->setIdArtist($artist);
            $labelHasArtist->setIdLabel($label);
            $labelHasArtist->setJoinedAt(new DateTime());
            $labelHasArtist->setLeftAt(null);

            $this->entityManager->persist($labelHasArtist);

            $this->entityManager->persist($artist);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Votre compte artiste a été créé avec succès. Bienvenue dans notre communauté d\'artistes !',
                'id_artist' => strval($artist->getId()),

            ], JsonResponse::HTTP_CREATED); // 201 Created
        }
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

        $existingArtistWithFullname = $this->repository->findOneBy(['fullname' => $requestData['fullname']]);
        if ($existingArtistWithFullname) {
            throw new BadRequestHttpException("Un compte utilisant ce nom d'artiste est déjà enregistré");
        }

        $requiredFields = ['fullname', 'label'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (isset($requestData[$field])) {
                if (empty($requestData[$field])) {
                    $missingFields[] = $field;
                }
            }
        }

        if (!empty($missingFields)) {
            return $this->json([
                'message' => 'Une ou plusieurs données obligatoires sont manquantes : ' . $missingFields,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $invalidData = [];

        if (isset($requestData['fullname']) && strlen($requestData['fullname']) > 90) {
            $invalidData[] = 'fullname';
        }

        if (isset($requestData['label']) && strlen($requestData['label']) > 55) {
            $invalidData[] = 'label';
        }

        if (isset($requestData['description']) && strlen($requestData['description']) > 55) {
            $invalidData[] = 'description';
        }
        if (!empty($invalidData)) {
            return $this->json([
                'message' => 'Une ou plusieurs données sont erronées',
                'data' => $invalidData,
            ], JsonResponse::HTTP_CONFLICT);
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


    //delete artist of current authenticated user
    #[Route('/artist', name: 'app_delete_artist', methods: ['DELETE'])]
    public function deleteArtist(Request $request): JsonResponse
    {
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if (gettype($dataMiddellware) == 'boolean') {
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }

        if (!$dataMiddellware) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user = $dataMiddellware;

        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'User not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        if ($user->getArtist() === null) {
            return $this->json([
                'error' => true,
                'message' => 'Compte Artist non trouvé. Verifiez les informations fournies et réessayez.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        //if already inactive return error
        if ($user->getArtist()->getActive() === 'inactive') {
            return $this->json([
                'error' => true,
                'message' => 'Ce compte artiste est deja désactivé',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $artist = $user->getArtist();

        $artist->setActive('inactive');
        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $this->json([
            'error' => false,
            'message' => 'Le compte a été désactivé avec succès',

        ]);
    }
}
