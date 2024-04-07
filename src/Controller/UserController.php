<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\DataFixtures\AppFixtures;
use DateTime;
use Doctrine\ORM\Mapping\Id;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;

class UserController extends AbstractController
{
    private $entityManager;
    private $tokenVerifier;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager, TokenVerifierService $tokenVerifier)
    {
        $this->entityManager = $entityManager;
        $this->tokenVerifier = $tokenVerifier;
        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/user/all', name: 'app_get_all_users', methods: ['GET'])]
    public function getAllUsers(): JsonResponse
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();

        $serializedUsers = [];
        foreach ($users as $user) {
            $serializedUsers[] = $user->userSerializer();
        }

        return $this->json([
            'users' => $serializedUsers,
            'message' => 'All songs retrieved successfully!',
            'path' => 'src/Controller/SongController.php',
        ]);
    }

    // #[Route('/user', name: 'app_update_user', methods: ['PUT'])]
    // public function updateUser(Request $request ): JsonResponse
    // {
    //     $dataMiddellware = $this->tokenVerifier->checkToken($request);
    //     if(gettype($dataMiddellware) == 'boolean'){
    //         return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
    //     }
    //     $user = $dataMiddellware;

    //     $requestData = $request->request->all();

    //     if ($request->headers->get('content-type') === 'application/json') {
    //         $requestData = json_decode($request->getContent(), true);
    //     }

    //     if (!$user) {
    //         return $this->json([
    //             'message' => 'Utilisateur non trouvé',
    //         ], Response::HTTP_NOT_FOUND);
    //     }

    //     $hasArtist = $user->getArtist() !== null;

    //     $minimumAge = $hasArtist ? 16 : 12;

    //     $requiredFields = ['firstname', 'lastname', 'dateBirth'];
    //     $missingFields = [];

    //     foreach ($requiredFields as $field) {
    //         if (isset($requestData[$field])) {
    //             if (empty($requestData[$field])) {
    //                 $missingFields[] = $field;
    //             }
    //         }
    //     }

    //     if (!empty($missingFields)) {
    //         return $this->json([
    //             'message' => 'Une ou plusieurs données obligatoires sont manquantes : ' .$missingFields,
    //         ], JsonResponse::HTTP_BAD_REQUEST);
    //     }

    //     $invalidData = [];

    //     if (isset($requestData['idUser']) && strlen($requestData['idUser']) > 90) {
    //         $invalidData[] = 'idUser';
    //     }

    //     if (isset($requestData['firstname']) && strlen($requestData['firstname']) > 55) {
    //         $invalidData[] = 'firstname';
    //     }

    //     if (isset($requestData['lastname']) && strlen($requestData['lastname']) > 55) {
    //         $invalidData[] = 'lastname';
    //     }

    //     if (isset($requestData['tel']) && strlen($requestData['tel']) > 15) {
    //         $invalidData[] = 'tel';
    //     }

    //     if (!empty($invalidData)) {
    //         return $this->json([
    //             'message' => 'Une ou plusieurs données sont erronées',
    //             'data' => $invalidData,
    //         ], JsonResponse::HTTP_CONFLICT);
    //     }

    //     if (isset($requestData['firstname'])) {
    //         $user->setFirstname($requestData['firstname']);
    //     }
    //     if (isset($requestData['lastname'])) {
    //         $user->setLastname($requestData['lastname']);
    //     }
    //     if (isset($requestData['sexe'])) {
    //         $user->setSexe($requestData['sexe']);
    //     }
    //     if (isset($requestData['tel'])) {
    //         $user->setTel($requestData['tel']);
    //     }
    //     if (isset($requestData['dateBirth'])) {
    //         $dateBirth = DateTimeImmutable::createFromFormat('d-m-Y', $requestData['dateBirth']);
    //         if ($dateBirth === false) {
    //             throw new BadRequestHttpException('Format de date de naissance invalide. Veuillez saisir la date au format jj-mm-aaaa.');
    //         }
    //         $today = new DateTime();
    //         $age = $today->diff($dateBirth)->y;

    //         if ($age < $minimumAge) {
    //             throw new BadRequestHttpException('L\'utilisateur doit avoir au moins ' . $minimumAge . ' ans pour être mis à jour.');
    //         }
    //         $user->setDateBirth($dateBirth);
    //     }

    //     $user->setUpdateAt(new DateTimeImmutable());

    //     $this->entityManager->persist($user);
    //     $this->entityManager->flush();

    //     return $this->json([
    //         'user' => $user->userSerializer(),
    //         'message' => 'Utilisateur mis à jour avec succès!',
    //         'path' => 'src/Controller/UserController.php',
    //     ]);
    // }

    #[Route('/user', name: 'app_update_user', methods: ['POST'])]
    public function updateUser(Request $request): JsonResponse
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
        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        $invalidData = [];

        if (isset($requestData['firstname'])) {
            $firstname = $requestData['firstname'];
            // Validate firstname format
            if (!preg_match('/^[a-zA-Z\s]+$/', $firstname)) {
                $invalidData[] = 'firstname';
            }
            // Validate firstname length
            if (strlen($firstname) > 20) {
                $invalidData[] = 'firstname';
            }
        }

        if (isset($requestData['lastname'])) {
            $lastname = $requestData['lastname'];
            // Validate lastname format
            if (!preg_match('/^[a-zA-Z\s]+$/', $lastname)) {
                $invalidData[] = 'lastname';
            }
            // Validate lastname length
            if (strlen($lastname) > 20) {
                $invalidData[] = 'lastname';
            }
        }

        if (isset($requestData['sexe'])) {
            $sexe = $requestData['sexe'];
            if ($sexe != 0 && $sexe != 1) {
                return $this->json([
                    'error' => true,
                    'message' => 'La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme et 1 pour Homme ',
                ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
            }
        }

        if (isset($requestData['tel'])) {
            $tel = $requestData['tel'];
            // Validate tel requirements
            if (!preg_match('/^06[0-9]{8}$/', $tel)) {
                return $this->json([
                    'error' => true,
                    'message' => 'Le format du numéro de téléphone est invalide',
                ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
            }

            // Check if phone number is already used
            $existingUser = $this->repository->findOneBy(['tel' => $tel]);
            if ($existingUser) {
                return $this->json([
                    'error' => true,
                    'message' => 'Conflit de données. Le numéro de téléphone est déjà utilisé par un autre utilisateur',
                ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
            }
        }

        if (!empty($invalidData)) {
            return $this->json([
                'error' => true,
                'message' => 'Les données fournies sont invalides ou incomplètes.',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }

        if (isset($requestData['firstname'])) {
            $user->setFirstname($requestData['firstname']);
        }
        if (isset($requestData['lastname'])) {
            $user->setLastname($requestData['lastname']);
        }
        if (isset($requestData['sexe'])) {
            $sexe = $requestData['sexe'];
            if ($sexe == 0) {
                $user->setSexe('Femme');
            } elseif ($sexe == 1) {
                $user->setSexe('Homme');
            }
        }
        if (isset($requestData['tel'])) {
            $user->setTel($requestData['tel']);
        }

        $user->setUpdateAt(new DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'error' => 'false',
            'message' => 'Votre inscription a été prise en compte',
        ]);
    }

    #[Route('/account-deactivation', name: 'app_delete_user', methods: ['DELETE'])]
    public function deleteUser(Request $request): JsonResponse
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

        if (!$dataMiddellware->getActive()) {
            return $this->json([
                'error' => true,
                'message' => 'Le compte est déjà désactivé.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $dataMiddellware;

        $user->setActive(false);
        $user->setUpdateAt(new DateTimeImmutable());

        //if user has artist profile, deactivate it
        if ($user->getArtist()) {
            $artist = $user->getArtist();
            $artist->setActive('inactive');
            $this->entityManager->persist($artist);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'error' => 'false',
            'message' => 'Votre compte a été désactivé avec succès. Nous sommes désolés de vous voir partir.',
            'path' => 'src/Controller/UserController.php',
        ]);
    }
}
