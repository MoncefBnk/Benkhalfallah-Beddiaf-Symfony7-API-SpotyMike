<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Album;
use App\Entity\Artist;
use App\Repository\AlbumRepository;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AlbumController extends AbstractController
{
    private $albumRepository;
    private $entityManager;

    public function __construct(AlbumRepository $albumRepository, EntityManagerInterface $entityManager)
    {
        $this->albumRepository = $albumRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/album/all', name: 'app_get_all_albums', methods: ['GET'])]
    public function getAllAlbums(): JsonResponse
    {
        $albums = $this->entityManager->getRepository(Album::class)->findAll();

        $serializedAlbums = [];
        foreach ($albums as $album) {
            $serializedAlbums[] = $album->albumSerializer();
        }

        return $this->json([
            'albums' => $serializedAlbums,
            'message' => 'Tous les albums ont été récupérés avec succès!',
            'path' => 'src/Controller/AlbumController.php',
        ]);
    }

    #[Route('/album', name: 'app_create_album', methods: ['POST'])]
    public function createAlbum(Request $request): JsonResponse
    {
        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        // Vérification des champs requis
        $champsRequis = ['idAlbum', 'nom', 'categ'];
        foreach ($champsRequis as $champ) {
            if (!isset($requestData[$champ])) {
                return $this->json([
                    'message' => 'Une ou plusieurs données sont manquantes : ' . $champ,
                ], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        $albumExistId = $this->entityManager->getRepository(Album::class)->findOneBy(['idAlbum' => $requestData['idAlbum']]);
        if ($albumExistId) {
            throw new BadRequestHttpException('Un album avec cet idAlbum existe déjà');
        }

        $albumExistNom = $this->entityManager->getRepository(Album::class)->findOneBy(['nom' => $requestData['nom']]);
        if ($albumExistNom) {
            throw new BadRequestHttpException('"Ce nom a deja été choisi"');
        }

        $donneesInvalides = [];
        if (isset($requestData['idAlbum']) && strlen($requestData['idAlbum']) > 90) {
            $donneesInvalides[] = 'idAlbum';
        }
        if (isset($requestData['nom']) && strlen($requestData['nom']) > 95) {
            $donneesInvalides[] = 'nom';
        }
        if (isset($requestData['categ']) && strlen($requestData['categ']) > 20) {
            $donneesInvalides[] = 'categ';
        }
        if (isset($requestData['cover']) && strlen($requestData['cover']) > 125) {
            $donneesInvalides[] = 'cover';
        }



        if (!empty($donneesInvalides)) {
            return $this->json([
                'message' => 'Une ou plusieurs données sont erronées',
                'donnees' => $donneesInvalides,
            ], JsonResponse::HTTP_CONFLICT);
        }
        //get the label from artist id
        

        $album = new Album();
        $album->setIdAlbum($requestData['idAlbum']);
        $album->setNom($requestData['nom']);
        $album->setCateg($requestData['categ']);
        $album->setCover($requestData['cover'] ?? null);

        $year = DateTimeImmutable::createFromFormat('d-m-Y', $requestData['year']);
        if (!$year) {
            return $this->json(['message' => 'Date de sortie invalide!'], Response::HTTP_BAD_REQUEST);
        }        

        $album->setYear($year);
        $album->setCreateAt(new DateTimeImmutable());
        $album->setUpdateAt(new DateTimeImmutable());

        $artist = $this->entityManager->getRepository(Artist::class)->find($requestData['artistId']);
        if (!$artist) {
            return $this->json(['message' => 'Artiste non trouvé!'], Response::HTTP_NOT_FOUND);
        }

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

        $album = $this->albumRepository->find($albumId);

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



    #[Route('/album/{id}', name: 'app_delete_album', methods: ['DELETE'])]
    public function deleteAlbum(int $id): JsonResponse
    {
        $album = $this->albumRepository->find($id);

        if (!$album) {
            return $this->json(['message' => 'Album not found!'], 404);
        }

        $this->entityManager->remove($album);
        $this->entityManager->flush();

        return $this->json(['message' => 'Album deleted successfully!']);
    }
}
