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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SongController extends AbstractController
{
    private $repository;
    private $entityManager;
    private $tokenVerifier;

    public function __construct(TokenVerifierService $tokenVerifier, EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Song::class);
        $this->entityManager = $entityManager;
        $this->tokenVerifier = $tokenVerifier;
    }



    #[Route('/album/{idAlbum}/song', name: 'app_create_song', methods: ['POST'])]
    public function createSong(Request $request, string $idAlbum): JsonResponse
    {
        dd( tmpfile()['uri']);
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

        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        $album = $this->entityManager->getRepository(Album::class)->find($idAlbum);
        if (!$album) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun album trouvé correspondant au nom fourni'
            ], Response::HTTP_NOT_FOUND);
        }

        if ($album->getArtistUserIdUser()->getUserIdUser()->getId() !== $user->getId()) {
            return $this->json(
                [
                    'error' => true,
                    'message' => 'Vous n\'avez pas l\'autorisations pour accéder à cet album.'
                ],
                Response::HTTP_FORBIDDEN
            );
        }
        
        if (!isset($idAlbum)) {
            return $this->json(
                [
                    'error' => true,
                    'message' => 'Une ou plusieurs données obligatoire sont manquantes.'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        //put data as static for test for id, title visibility 
        $idSong = '1234567890';
        $title = 'test';
        $visibility = true;
        $cover = 'cover';
        $featuring = [1];


        $song = new Song();
        $song->setIdSong($idSong);
        $song->setTitle($title);
        $song->setVisibility($visibility ?? true);
        $song->setCover($cover);
        $song->setCreateAt(new \DateTimeImmutable());
        $song->setAlbum($album);

        if (isset($featuring)) {
            $featuring = new Featuring();

            $featuring->setIdSong($song);

            $featuring->setIdFeaturing('1234567890');

            foreach ($featuring as $artistId) {
                $artist = $this->entityManager->getRepository(Artist::class)->find($artistId);
                if ($artist) {
                    $featuring->addIdArtist($artist);
                }
            }
            $this->entityManager->persist($featuring);
        }

        $streamFile = $request->files->get('stream');
         
        //if null return error
        if (!$streamFile) {
            return $this->json([
                'error' => true,
                'message' => 'Erreur sur le fichier stream qui est manquant.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $extension = $streamFile->guessExtension();

        // check if it's wav or mp3 file 
        if ($extension !== 'wav' && $extension !== 'mp3') {
            return $this->json([
                'error' => true,
                'message' => 'Erreur sur le format du fichier qui n\'est pas pris en compte.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $streamDirectory = $this->getParameter('upload_directory') . '/' . $album->getTitle();

        $streamFileName = 'stream_' . $song->getIdSong() . '.' . $extension;
        try {
            $streamFile->move($streamDirectory, $streamFileName);
        } catch (FileException $e) {
            return $this->json([
                'error' => true,
                'message' => 'Error uploading stream file.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $streamPath = $streamDirectory . '/' . $streamFileName;
        $song->setStream($streamPath);

        $this->entityManager->persist($song);
        $this->entityManager->flush();

        return $this->json([
            'song' => $song->songSerializer(),
            'message' => 'Song created successfully!',
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
        ]);
    }
}
