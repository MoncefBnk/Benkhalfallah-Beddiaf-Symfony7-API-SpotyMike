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
use Symfony\Component\Validator\Constraints\Length;

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
        if ($fullname === ' ') {
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
            $serializedArtists = $artist->artistSearchSerializer();
            return $this->json([
                'error' => false,
                'artist' => $serializedArtists,
            ]);
        } else {
            $artist = $this->repository->findOneBy(['fullname' => $fullname]);

            if (!$artist) {
                return $this->json([
                    'error' => true,
                    'message' => 'Aucun artiste trouvé correspondant au nom fourni.',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $serializedArtists = $artist->artistSearchSerializer();



            return $this->json([
                'error' => false,
                'artist' => $serializedArtists,
            ]);
        }
    }

    #[Route('/artist', name: 'app_get_artists', methods: ['GET'])]
    public function getAllArtists(Request $request): JsonResponse
    {
        // Check token middleware
        $dataMiddleware = $this->tokenVerifier->checkToken($request);
        if (gettype($dataMiddleware) == 'boolean') {
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddleware));
        }

        if (!$dataMiddleware) {
            return $this->json([
                'error' => true,
                'message' => 'Authentification requise. Vous devez être connecté pour effectuer cette action.',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $artists = $this->entityManager->getRepository(Artist::class)->findAll();

        // Serialize artists
        $serializedArtists = [];
        foreach ($artists as $artist) {
            $serializedArtists[] = $artist->artistAllSerializer();
        }

        //check format of limit and page 



        $totalArtist = count($serializedArtists);
        // Pagination
        $limit = $request->query->get('limit', 5);
        $page =  $request->query->get('page');



        if (!is_numeric($limit) || $limit <= 0) {
            return $this->json([
                'error' => true,
                'message' => 'Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (empty($page) ||!is_numeric($page) || $page <= 0) {
            return $this->json([
                'error' => true,
                'message' => 'Le paramètre de pagination est invalide. Veuillez fournir un numéro de page valide',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }



        $offset = ($page - 1) * $limit;
        $paginatedArtists = array_slice($serializedArtists, $offset, $limit);

        if (empty($paginatedArtists)) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun artiste trouvé pour la page demandée.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Return JSON response
        return $this->json([
            'error' => false,
            'artists' => $paginatedArtists,
            'message' => 'Informations des artistes récupérées avec succès.',
            'pagination' => [
                'currentPage' => (int)$page,
                'totalPages' => ceil(count($serializedArtists) / $limit),
                'totalArtists' => $totalArtist
            ],
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
                        'message' => 'Les paramètres fournis sont invalides. Veuillez vérifier les données soumises.',
                    ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
                }
                $existingArtistWithFullname = $this->repository->findOneBy(['fullname' => $requestData['fullname']]);

                if ($existingArtistWithFullname) {
                    return $this->json([
                        'error' => true,
                        'message' => 'Le nom d\'artiste est déjà utilisé. Veuillez choisir un autre nom.',
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

            if (isset($requestData['avatar'])) {
                $parameters = $request->getContent();
                parse_str($parameters, $data);
                $explodeData = explode(",", $data['avatar']);
                if (count($explodeData) == 2) {

                    //check the file format
                    $fileFormat = explode(';', $explodeData[0]);
                    //assign the file format to the variable
                    $fileFormat = explode('/', $fileFormat[0]);
                  
                    //if not png or jpeg return error
                    if ($fileFormat[1] !== 'png' && $fileFormat[1] !== 'jpeg') {
                        return $this->json([
                            'error' => true,
                            'message' => 'Le format de l\'image est invalide. Veuillez fournir une image au format PNG ou JPEG.',
                        ], JsonResponse::HTTP_BAD_REQUEST);
                    }
                    $file = base64_decode($explodeData[1]);
                    //check if the decode is correct 
                    if ($file === false) {
                        return $this->json([
                            'error' => true,
                            'message' => 'Erreur lors du décodage de l\'image.',
                        ], JsonResponse::HTTP_BAD_REQUEST);
                    }
                    
                    $chemin = $this->getParameter('upload_directory') . '/' . $user->getEmail();
                    //check if path exists
                    if (!file_exists($chemin)) {
                        mkdir($chemin);
                    }              
                    file_put_contents($chemin . '/avatar.' . $fileFormat[1], $file);
                    $artist->setAvatar($chemin . '/avatar.' . $fileFormat[1]);
                    $this->entityManager->persist($artist);


                    
                }
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Les informations de l\'artiste ont été mises à jour avec succès.',

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
               
                if (strlen($fullname) < 1 || strlen($fullname) > 30) {
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
            $labelHasArtist = new LabelHasArtist();
            $labelHasArtist->setIdArtist($artist);
            $labelHasArtist->setIdLabel($label);
            $labelHasArtist->setJoinedAt(new DateTime());
            $labelHasArtist->setLeftAt(null);

            if (isset($requestData['avatar'])) {
                $parameters = $request->getContent();
                parse_str($parameters, $data);

                $explodeData = explode(",", $data['avatar']);
                if (count($explodeData) == 2) {

                    //check the file format
                    $fileFormat = explode(';', $explodeData[0]);
                    //assign the file format to the variable
                    $fileFormat = explode('/', $fileFormat[0]);
                    //if not png or jpeg return error
                    if ($fileFormat[1] !== 'png' && $fileFormat[1] !== 'jpeg') {
                        return $this->json([
                            'error' => true,
                            'message' => 'Erreur sur le format du fichier qui n\'est pas pris en compte.',
                        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                    }
                    $file = base64_decode($explodeData[1]);
                    //check if the decode is correct 
                    if ($file === false) {
                        return $this->json([
                            'error' => true,
                            'message' => 'Le serveur ne peut pas décoder le contenu base64 en fichier binaire.',
                        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    //check file size should be between 1Mb and 7Mb
                    // if (strlen($file) < 1000000 || strlen($file) > 7000000) {
                    //     return $this->json([
                    //         'error' => true,
                    //         'message' => 'Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1Mb et 7Mb.',
                    //     ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                    // }

                    $chemin = $this->getParameter('upload_directory') . '/' . $user->getEmail();
                    if (!file_exists($chemin)) {
                        mkdir($chemin);
                    }
                    file_put_contents($chemin . '/avatar.' . $fileFormat[1], $file);
                    $artist->setAvatar($chemin . '/avatar.' . $fileFormat[1]);
                }
            }
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
                'message' => 'Compte artiste non trouvé. Vérifiez les informations fournies et réessayez.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        //if already inactive return error
        if ($user->getArtist()->getActive() === 'Inactive') {
            return $this->json([
                'error' => true,
                'message' => 'Ce compte artiste est déja désactivé.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $artist = $user->getArtist();

        $artist->setActive('Inactive');
        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $this->json([
            'error' => false,
            'message' => 'Le compte a été désactivé avec succès.',

        ]);
    }
}
