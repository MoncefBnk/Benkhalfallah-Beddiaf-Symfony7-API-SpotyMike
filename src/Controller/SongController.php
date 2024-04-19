<?php

namespace App\Controller;

use App\Entity\Album;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Song;
use App\Entity\Artist;
use App\Entity\Featuring;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        $existingSongById = $this->entityManager->getRepository(Song::class)->findOneBy(['idSong' => $requestData['idSong']]);
        if ($existingSongById) {
            throw new BadRequestHttpException('Song with this idSong already exists');
        }

        // Check if title already exists
        $existingSongByTitle = $this->entityManager->getRepository(Song::class)->findOneBy(['title' => $requestData['title']]);
        if ($existingSongByTitle) {
            throw new BadRequestHttpException('Song with this title already exists');
        }

        // Validate required fields
        if (!isset($requestData['idSong'], $requestData['title'])) {
            return $this->json(['message' => 'Required fields are missing!'], 400);
        }

        switch ($requestData) {
            case 'idSong' && strlen($requestData['idSong']) > 90:
                throw new BadRequestHttpException('idSong too long');
            case 'title' && strlen($requestData['title']) > 255:
                throw new BadRequestHttpException('Title too long');
            case 'stream' && strlen($requestData['stream']) > 125:
                throw new BadRequestHttpException('stream too long');
            case 'cover' && strlen($requestData['cover']) > 125:
                throw new BadRequestHttpException('Cover name too long');
        }

        $song = new Song();
        $song->setIdSong($requestData['idSong']);
        $song->setTitle($requestData['title']);
        $song->setStream($requestData['stream'] ?? null);
        $song->setCover($requestData['cover'] ?? null);
        $song->setVisibility($requestData['visibility'] ?? true);
        $song->setCreateAt(new \DateTimeImmutable());

        $album = $this->entityManager->getRepository(Album::class)->find($requestData['albumId']);
        if (!$album) {
            return $this->json(['message' => 'Album non trouvé!'], Response::HTTP_NOT_FOUND);
        }

        $song->setAlbum($album);

        
        if (isset($requestData['featuring']) && is_array($requestData['featuring'])) {
            $featuring = new Featuring(); 
            
            $featuring->setIdSong($song);

            $featuring->setIdFeaturing('1234567890');

            foreach ($requestData['featuring'] as $artistId) {
                $artist = $this->entityManager->getRepository(Artist::class)->find($artistId);
                if ($artist) {
                    $featuring->addIdArtist($artist);
                }
            }
            $this->entityManager->persist($featuring);
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
        if (isset($data['stream'])) {
            $song->setStream($data['stream']);
        }
        if (isset($data['cover'])) {
            $song->setCover($data['cover']);
        }
        if (isset($data['visibility'])) {
            $song->setVisibility($data['visibility']);
        }

        $this->entityManager->persist($song);
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

    #[Route('/song/all', name: 'app_get_all_songs', methods: ['GET'])]
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

    #[Route('/song/{id}', name: 'app_get_song_by_id', methods: ['GET'])]
    public function getSongById(string $id): JsonResponse
    {
        if (!ctype_digit($id)) {
            throw new BadRequestHttpException('Une ou plusieurs données obligatoire sont manquantes');
        }

        $id = (int)$id; 

        $song = $this->entityManager->getRepository(Song::class)->find($id);

        if (!$song) {
            return $this->json([
                'message' => 'Song not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json([
            'song' => $song->songSerializer(),
            'message' => 'Song retrieved successfully!',
            'path' => 'src/Controller/SongController.php',
        ]);
    }
}
