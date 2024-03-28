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

class AlbumController extends AbstractController
{
    private $albumRepository;
    private $entityManager;

    public function __construct(AlbumRepository $albumRepository, EntityManagerInterface $entityManager)
    {
        $this->albumRepository = $albumRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/albums', name: 'app_get_all_albums', methods: ['GET'])]
    public function getAllAlbums(): JsonResponse
    {
        $albums = $this->albumRepository->findAll();

        $serializedAlbums = [];
        foreach ($albums as $album) {
            $serializedAlbums[] = $album->songSerializer();
        }

        return $this->json([
            'albums' => $serializedAlbums,
            'message' => 'All albums retrieved successfully!',
            'path' => 'src/Controller/AlbumController.php',
        ]);
    }

    #[Route('/album/{id}', name: 'app_get_album', methods: ['GET'])]
    public function getAlbum(int $id): JsonResponse
    {
        $album = $this->albumRepository->find($id);

        if (!$album) {
            return $this->json(['message' => 'Album not found!'], 404);
        }

        return $this->json([
            'album' => $album->songSerializer(),
            'message' => 'Album retrieved successfully!',
            'path' => 'src/Controller/AlbumController.php',
        ]);
    }

    #[Route('/album', name: 'app_create_album', methods: ['POST'])]
    public function createAlbum(Request $request): JsonResponse
{
    $requestData = $request->request->all();

    if (!isset($requestData['idAlbum'], $requestData['nom'], $requestData['categ'], $requestData['artistId'])) {
        return $this->json(['message' => 'Des champs requis sont manquants!'], 400);
    }
        $album = new Album();
        $album->setIdAlbum($requestData['idAlbum']);
        $album->setNom($requestData['nom']);
        $album->setCateg($requestData['categ']);
        $album->setCover($requestData['cover'] ?? null);
        $album->setYear($requestData['year'] ?? 2024);

        // Récupérer l'artiste associé à partir de son identifiant
        $artist = $this->entityManager->getRepository(Artist::class)->find($requestData['artistId']);
        if (!$artist) {
            return $this->json(['message' => 'Artiste non trouvé!'], 404);
        }

        $album->setArtistUserIdUser($artist);

        // Enregistrer l'album dans la base de données
        $this->entityManager->persist($album);
        $this->entityManager->flush();

        return $this->json([
            'album' => $album->songSerializer(),
            'message' => 'Album créé avec succès!',
            'path' => 'src/Controller/AlbumController.php',
        ]);
    }

    #[Route('/album/{id}', name: 'app_update_album', methods: ['PUT'])]
    public function updateAlbum(Request $request, int $id): JsonResponse
    {
        $album = $this->albumRepository->find($id);
    
        if (!$album) {
            return $this->json(['message' => 'Album not found!'], 404);
        }
    
        $requestData = $request->request->all();
    
        // Update album properties
        if (isset($requestData['idAlbum'])) {
            $album->setIdAlbum($requestData['idAlbum']);
        }
        if (isset($requestData['nom'])) {
            $album->setNom($requestData['nom']);
        }
        if (isset($requestData['categ'])) {
            $album->setCateg($requestData['categ']);
        }
        // Update other album properties as needed
    
        $this->entityManager->flush();
    
        return $this->json([
            'album' => $album->songSerializer(),
            'message' => 'Album updated successfully!',
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
