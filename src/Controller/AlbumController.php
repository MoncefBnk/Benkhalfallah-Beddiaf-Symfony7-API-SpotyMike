<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Album;
use App\Entity\Artist;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use finfo;

class AlbumController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $tokenVerifier;

    public function __construct(TokenVerifierService $tokenVerifier, EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Album::class);
        $this->entityManager = $entityManager;
        $this->tokenVerifier = $tokenVerifier;

    }

    #[Route('/albums', name: 'app_get_all_albums', methods: ['GET'])]
    public function getAllAlbums(Request $request): JsonResponse
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

        $albums = $this->entityManager->getRepository(Album::class)->findAll();

        $serializedAlbums = [];
        foreach ($albums as $album) {
            $serializedAlbums[] = $album->albumSerializer();
        }



        $totalAlbums = count($serializedAlbums);
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
        $paginatedAlbums = array_slice($serializedAlbums, $offset, $limit);

        if (empty($paginatedAlbums)) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé pour la page demandée.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json([
            'error' => false,
            'albums' => $paginatedAlbums,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => ceil($totalAlbums / $limit),
                'totalAlbums' => $totalAlbums,
            ],
            
            
        ]);
    }

    #[Route('/album', name: 'app_create_album', methods: ['POST'])]
    public function createAlbum(Request $request): JsonResponse
    {

        // AUTHENTIFICATION
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
        $artist = $this->entityManager->getRepository(Artist::class)->findOneBy(['User_idUser' => $user->getId()]);

        // VERIFIER SI L'ARTISTE EXISTE ET AUTORISATION 
        if (!$artist) {
            return $this->json([
                'error' => true,
                'message' => 'Vous n\'avez pas l\'autorisation pour accéder à cet album.'
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }
       
        
        // CHAMPS REQUIS ET INVALIDES 
        $requiredFields = ['title', 'categories', 'visibility', 'cover'];
        $invalidKeys = array_diff(array_keys($requestData), $requiredFields);
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (isset($requestData[$field])) {
                if (empty($requestData[$field])) {
                    $missingFields[] = $field;
                }
            }
        }
        if (!empty($missingFields) && !empty($invalidKeys)) {
            return $this->json([
                'error' => true,
                'message' => 'Un ou plusieurs champs requis sont vides dans la requête : ',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // VISIBILITE INVALIDE
        if ($requestData['visibility'] !== '0' && $requestData['visibility'] !== '1') {
            return $this->json([
                'error' => true,
                'message' => 'La valeur du champ visibility est invalide. Les valeurs autorisées sont 0 pour invisible, 1 pour visible.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        //TITRE DEJA UTILISE 
        $albumExistTitle = $this->entityManager->getRepository(Album::class)->findOneBy(['title' => $requestData['title']]);
        if ($albumExistTitle) {
            return $this->json([
                'error' => true,
                'message' => 'Ce titre est déja pris. Veuillez en choisir un autre.',
            ], JsonResponse::HTTP_CONFLICT);
        }

        //CATEGORIE INVALIDE OR TITLE
        $invalidData = [];
       
        //titre en alphanumerique, no symbole et 90 char max 1 min
        if (strlen($requestData['title']) > 90 || strlen($requestData['title']) < 1) {
            $invalidData[] = 'title';
        }

        //categories should be of format : Un tableau JSON avec toutes les catégories, liste de categories autorise : rap, r'n'b', gospel, jazz, soul, country, hip hop, le Mick 
        
        $allowedCategories = ['rap', 'r\'n\'b', 'gospel', 'jazz', 'soul', 'country', 'hip hop', 'le Mick'];

        $categories =  $requestData['categories'];
        
        
        $categoriesParsed = json_decode($categories, true);
        if (is_null($categoriesParsed)) {
            $categoriesParsed = [];
        }

        $invalidCategories = [];
        if (!is_null($categoriesParsed)) {
             
            $invalidCategories = array_diff($categoriesParsed, $allowedCategories);

        }
        if (!empty($invalidCategories)) {
            return $this->json([
                'error' => true,
                'message' => 'Les catégories suivantes ne sont pas autorisées : ' . implode(', ', $invalidCategories),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        //categories doit être une liste JSON de chaînes de caractères
        if (!is_array($categoriesParsed)) { 
            $invalidData[] = 'categories';
        }

        if (!empty($invalidData)) {
            return $this->json([
                'error' => true,
                'message' => 'Erreur de validation de données',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $album = new Album();

        if (isset($requestData['cover'])) {
            $parameters = $request->getContent();
            parse_str($parameters, $data);

            $explodeData = explode(",", $data['cover']);
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

                // check file size should be between 1Mb and 7Mb
                // if (strlen($file) < 1000000 || strlen($file) > 7000000) {
                //     return $this->json([
                //         'error' => true,
                //         'message' => 'Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1Mb et 7Mb.',
                //     ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
                // }

                $chemin = $this->getParameter('upload_directory') . '/' . $user->getEmail();
                file_put_contents($chemin . '/cover.' . $fileFormat[1], $file);
                $album->setCover($chemin . '/cover.' . $fileFormat[1]);
            }
        }
        $album->setTitle($requestData['title']);
        $album->setCateg($categories);
        $album->setVisibility($requestData['visibility']);
        $album->setCreateAt(new DateTimeImmutable());
        $album->setUpdateAt(new DateTimeImmutable());
        $album->setArtistUserIdUser($artist);

        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return $this->json([
            'album' => $album->albumSerializer(),
            'message' => "Album ajouté avec succès",
            'path' => 'src/Controller/AlbumController.php',
        ], Response::HTTP_CREATED);
    }

    #[Route('/album', name: 'app_update_album', methods: ['PUT'])]
    public function updateAlbum(Request $request): JsonResponse
    {
        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        $albumId = $requestData['id'] ?? null;

        if (!$albumId) {
            return $this->json([
                'message' => 'Identifiant d\'album manquant dans les données de la requête',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $album = $this->repository->find($albumId);

        if (!$album) {
            return $this->json([
                'message' => 'Album non trouvé!',
            ], Response::HTTP_NOT_FOUND);
        }
        $requestData = $request->request->all();

        $albumExistNom = $this->entityManager->getRepository(Album::class)->findOneBy(['nom' => $requestData['nom']]);
        if ($albumExistNom) {
            throw new BadRequestHttpException('"Ce nom a deja été choisi"');
        }

        $requiredFields = ['nom', 'categ'];
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
                'message' => 'Un ou plusieurs champs requis sont vides dans la requête : ' .$missingFields,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $invalidData = [];

        if (isset($requestData['nom']) && strlen($requestData['nom']) > 95) {
            $invalidData[] = 'nom';
        }

        if (isset($requestData['categ']) && strlen($requestData['categ']) > 20) {
            $invalidData[] = 'categ';
        }

        if (isset($requestData['cover']) && strlen($requestData['cover']) > 125) {
            $invalidData[] = 'cover';
        }

        if (!empty($invalidData)) {
            return $this->json([
                'message' => 'Une ou plusieurs données sont erronées',
                'data' => $invalidData,
            ], JsonResponse::HTTP_CONFLICT);
        }

        if (isset($requestData['nom'])) {
            $album->setNom($requestData['nom']);
        }
        if (isset($requestData['categ'])) {
            $album->setCateg($requestData['categ']);
        }
        if (isset($requestData['cover'])) {
            $album->setCover($requestData['cover']);
        }
        if (isset($requestData['year'])) {
            $album->setYear($requestData['year']);
        }

        $album->setUpdateAt(new DateTimeImmutable());

        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return $this->json([
            'album' => $album->albumSerializer(),
            'message' => 'Album mis à jour avec succès!',
            'path' => 'src/Controller/AlbumController.php',
        ]);
    }

    ////
    #[Route('/album/{id}', name: 'app_modification_album', methods: ['PUT'])]
    public function modificationAlbum(Request $request): JsonResponse
    {   
        $requestData = $request->request->all();
        $albumId = $requestData['id'] ?? null;

        // Verification requise OK 
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

        // Accès refusé OK
        $user = $dataMiddellware;
        $artist = $this->entityManager->getRepository(Artist::class)->findOneBy(['User_idUser' => $user->getId()]);

        if (!$artist) {
            return $this->json([
                'error' => true,
                'message' => 'Vous n\'avez pas l\'autorisation pour accéder à cet album.'
            ], JsonResponse::HTTP_FORBIDDEN);
        }
        
        // Paramètres invalides OK
        $requiredFields = ['title', 'categories', 'visibility', 'cover'];
        $invalidKeys = array_diff(array_keys($requestData), $requiredFields);

        if (!empty($invalidKeys)) {
            return $this->json([
                'error' => true,
                'message' => 'Les paramètres fournis sont invalides. Veuillez vérifier les données soumises.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Visibility OK
        if(isset($requestData['visibility'])) {
            if ($requestData['visibility'] !== '0' && $requestData['visibility'] !== '1') {
                return $this->json([
                    'error' => true,
                    'message' => 'La valeur du champ visibility est invalide. Les valeurs autorisées sont 0 pour invisible, 1 pour visible.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }
       
        // Catégories autorisées OK
        $allowedCategories = ['rap', 'r\'n\'b', 'gospel', 'jazz', 'soul', 'country', 'hip hop', 'le Mick'];
        if(isset($requestData['categories'])) {
            $categoriesParsed = json_decode($categories, true);
            if (!is_array($categoriesParsed)) { 
                $invalidData[] = 'categories';
            }
            if (is_null($categoriesParsed)) {
                $categoriesParsed = [];
            }
            $invalidCategories = [];
            if (!is_null($categoriesParsed)) {
                $invalidCategories = array_diff($categoriesParsed, $allowedCategories);
            }
            if (!empty($invalidCategories)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Les catégories ciblées sont invalides.',
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        // Album non trouvé OK
        if (!$albumId) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé correspondant au nom fourni.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Titre d'album déjà utilisé OK
        if(isset($requestData['title'])) {
            $albumExistTitle = $this->entityManager->getRepository(Album::class)->findOneBy(['title' => $requestData['title']]);
            if ($albumExistTitle) {
                return $this->json([
                    'error' => true,
                    'message' => 'Ce titre est déja pris. Veuillez en choisir un autre.',
                ], JsonResponse::HTTP_CONFLICT);
            }
        }
        
        // Erreur de validation des données OK
        if(isset($requestData['title'])) {
            if (!empty($invalidCategories) || strlen($requestData['title']) > 90 || strlen($requestData['title']) < 1) {
                return $this->json([
                    'error' => true,
                    'message' => 'Erreur de validation des données.',
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        // Erreur de décodage OK
        $decodedData = base64_decode($file, true);
        if(isset($requestData['cover'])) {
            if ($decodedData === false || empty($requestData['cover'])) { 
                return $this->json([
                    'error' => true,
                    'message' => 'Le serveur ne peut pas décoder le contenu base64 en fichier binaire.',
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
        
        // Format de fichier OK
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($decodedData);
        if(isset($requestData['cover'])) {
            if ($mimeType != 'image/jpeg' && $mimeType != 'image/png') {
                return $this->json([
                    'error' => true,
                    'message' => 'Erreur sur le format du fichier qui n\'est pas pris en compte.',
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }  
        }
      
        // Taille du fichier OK
        $minSize = 1000000; // 1 Mo
        $maxSize = 7000000; // 7 Mo
        if(isset($requestData['cover'])) {

            if (strlen($requestData['cover']) > $maxSize || strlen($requestData['cover']) < $minSize) {
                return $this->json([
                    'error' => true,
                    'message' => 'Le fichier envoyé est trop ou pas assez volumineux. Vous devez respecter la taille entre 1Mb et 7Mb.',
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        if(isset($requestData['visibility'])) {
            $album->setVisibility($requestData['visibility']);
        }
        if(isset($requestData['cover'])) {
            $album->setCover($cover);
        }
        if(isset($requestData['title'])) {
            $album->setTitle($requestData['title']);
        }  
        if(isset($requestData['categorie'])) {
            $album->setCateg($categories);
        }

        $this->entityManager->persist($album);
        $this->entityManager->flush();

        // Succès OK
        return $this->json([
            'error' => false,
            'message' => 'Album mis à jour avec succès.',
        ]);

    }
    //// 


    #[Route('/album/{id}', name: 'app_delete_album', methods: ['DELETE'])]
    public function deleteAlbum(int $id): JsonResponse
    {
        $album = $this->repository->find($id);

        if (!$album) {
            return $this->json(['message' => 'Album not found!'], 404);
        }

        $this->entityManager->remove($album);
        $this->entityManager->flush();

        return $this->json(['message' => 'Album deleted successfully!']);
    }
}
