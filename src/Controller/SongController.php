<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Song;
use App\Entity\Artist;

class SongController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/song', name: 'app_create_song', methods: ['POST'])]
    public function createSong(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

     
        $song = new Song();

   
        $song->setIdSong($data['idSong'] ?? null);
        $song->setTitle($data['title'] ?? null);
        $song->setUrl($data['url'] ?? null);
        $song->setCover($data['cover'] ?? null);
        $song->setVisibility($data['visibility'] ?? true); 
        $song->setCreateAt(new \DateTimeImmutable()); 

        
        if (isset($data['artistIds']) && is_array($data['artistIds'])) {
            foreach ($data['artistIds'] as $artistId) {
                $artist = $this->entityManager->getRepository(Artist::class)->find($artistId);
                if ($artist) {
                    $song->addArtistIdUser($artist);
                }
            }
        }

        $this->entityManager->persist($song);
        $this->entityManager->flush();

        return $this->json([
            'song' => $song->songSerializer(),
            'message' => 'Song created successfully!',
            'path' => 'src/Controller/SongController.php',
        ]);
    }

    #[Route('/song/{id}', name: 'app_update_song', methods: ['PUT'])]
    public function updateSong(Request $request, int $id): JsonResponse
    {
        $song = $this->entityManager->getRepository(Song::class)->find($id);

        if (!$song) {
            return $this->json([
                'message' => 'Song not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['idSong'])) {
            $song->setIdSong($data['idSong']);
        }
        if (isset($data['title'])) {
            $song->setTitle($data['title']);
        }
        if (isset($data['url'])) {
            $song->setUrl($data['url']);
        }
        if (isset($data['cover'])) {
            $song->setCover($data['cover']);
        }
        if (isset($data['visibility'])) {
            $song->setVisibility($data['visibility']);
        }

        $this->entityManager->flush();

        return $this->json([
            'song' => $song->songSerializer(),
            'message' => 'Song updated successfully!',
            'path' => 'src/Controller/SongController.php',
        ]);
    }

    #[Route('/song/{id}', name: 'app_delete_song', methods: ['DELETE'])]
    public function deleteSong(int $id): JsonResponse
    {
        $song = $this->entityManager->getRepository(Song::class)->find($id);

        if (!$song) {
            return $this->json([
                'message' => 'Song not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($song);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Song deleted successfully!',
            'path' => 'src/Controller/SongController.php',
        ]);
    }

    #[Route('/songs', name: 'app_get_all_songs', methods: ['GET'])]
    public function getAllSongs(): JsonResponse
    {
        $songs = $this->entityManager->getRepository(Song::class)->findAll();

        $serializedSongs = [];
        foreach ($songs as $song) {
            $serializedSongs[] = $song->songSerializer();
        }

        return $this->json([
            'songs' => $serializedSongs,
            'message' => 'All songs retrieved successfully!',
            'path' => 'src/Controller/SongController.php',
        ]);
    }
}
