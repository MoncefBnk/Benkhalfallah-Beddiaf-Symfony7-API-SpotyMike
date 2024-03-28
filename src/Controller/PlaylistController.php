<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Playlist;
use App\Repository\PlaylistRepository;

class PlaylistController extends AbstractController
{
    private $playlistRepository;
    private $entityManager;


    public function __construct(PlaylistRepository $playlistRepository, EntityManagerInterface $entityManager)
    {
        $this->playlistRepository = $playlistRepository;
        $this->entityManager = $entityManager;
    }

    #[Route('/playlists', name: 'app_get_all_playlists', methods: ['GET'])]
    public function getAllPlaylists(): JsonResponse
    {
        $playlists = $this->playlistRepository->findAll();

        $serializedPlaylists = [];
        foreach ($playlists as $playlist) {
            $serializedPlaylists[] = $playlist->playlistSerializer();
        }

        return $this->json([
            'playlists' => $serializedPlaylists,
            'message' => 'All playlists retrieved successfully!',
            'path' => 'src/Controller/PlaylistController.php',
        ]);
    }

    #[Route('/playlist/{id}', name: 'app_get_playlist', methods: ['GET'])]
    public function getPlaylist(int $id): JsonResponse
    {
        $playlist = $this->playlistRepository->find($id);

        if (!$playlist) {
            return $this->json(['message' => 'Playlist not found!'], 404);
        }

        return $this->json([
            'playlist' => $playlist->playlistSerializer(),
            'message' => 'Playlist retrieved successfully!',
            'path' => 'src/Controller/PlaylistController.php',
        ]);
    }

    #[Route('/playlist', name: 'app_create_playlist', methods: ['POST'])]
    public function createPlaylist(Request $request): JsonResponse
    {
        $requestData = $request->request->all();

        
        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        if (!isset($requestData['idPlaylist'], $requestData['title'])) {
            return $this->json(['message' => 'Required fields are missing!'], 400);
        }

        $playlist = new Playlist();
        $playlist->setIdPlaylist($requestData['idPlaylist']);
        $playlist->setTitle($requestData['title']);
        $playlist->setPublic($requestData['public'] ?? true);
        $playlist->setCreateAt(new \DateTimeImmutable());
        $playlist->setUpdateAt(new \DateTimeImmutable());


        $this->entityManager->persist($playlist);
        $this->entityManager->flush();

        return $this->json([
            'playlist' => $playlist->playlistSerializer(),
            'message' => 'Playlist created successfully!',
            'path' => 'src/Controller/PlaylistController.php',
        ]);
    }

    #[Route('/playlist/{id}', name: 'app_update_playlist', methods: ['PUT'])]
    public function updatePlaylist(Request $request, int $id): JsonResponse
    {
        $playlist = $this->playlistRepository->find($id);

        if (!$playlist) {
            return $this->json(['message' => 'Playlist not found!'], 404);
        }

        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        
        if (isset($requestData['idPlaylist'])) {
            $playlist->setIdPlaylist($requestData['idPlaylist']);
        }
        if (isset($requestData['title'])) {
            $playlist->setTitle($requestData['title']);
        }
        if (isset($requestData['public'])) {
            $playlist->setPublic($requestData['public']);
        }

        $this->entityManager->flush();

        return $this->json([
            'playlist' => $playlist->playlistSerializer(),
            'message' => 'Playlist updated successfully!',
            'path' => 'src/Controller/PlaylistController.php',
        ]);
    }

    #[Route('/playlist/{id}', name: 'app_delete_playlist', methods: ['DELETE'])]
    public function deletePlaylist(int $id): JsonResponse
    {
        $playlist = $this->playlistRepository->find($id);

        if (!$playlist) {
            return $this->json(['message' => 'Playlist not found!'], 404);
        }

        $this->entityManager->remove($playlist);
        $this->entityManager->flush();

        return $this->json(['message' => 'Playlist deleted successfully!']);
    }
}
