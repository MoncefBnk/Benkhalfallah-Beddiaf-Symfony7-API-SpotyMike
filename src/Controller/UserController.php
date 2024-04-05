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

    // #[Route('/register', name: 'app_create_user', methods: ['POST'])]
    // public function createUser(Request $request): JsonResponse
    // {
    //     $requestData = $request->request->all();

    //     if ($request->headers->get('content-type') === 'application/json') {
    //         $requestData = json_decode($request->getContent(), true);
    //     }

    //     $requiredFields = ['firstname', 'firstname', 'lastname', 'email', 'encrypte', 'dateBirth'];

    //     foreach ($requiredFields as $field) {
    //         if (!isset($requestData[$field])) {
    //             return $this->json([
    //                 'message' => 'Une ou plusieurs données obligatoires sont manquantes : ' . $field,
    //             ], JsonResponse::HTTP_BAD_REQUEST); // 409 Conflict
    //         }
    //     }

    //     $existingUserWithIdUser = $this->repository->findOneBy(['idUser' => $requestData['idUser']]);
    //     if ($existingUserWithIdUser) {
    //         throw new BadRequestHttpException("Un compte utilisant cette IdUser est déjà enregistré");
    //     } // 409 Conflict

    //     $existingUser = $this->repository->findOneBy(['email' => $requestData['email']]);
    //     if ($existingUser) {
    //         throw new BadRequestHttpException("Un compte utilisant cette adresse mail est déjà enregistré");
    //     } // 409 Conflict
    //     $dateBirth = DateTimeImmutable::createFromFormat('d-m-Y', $requestData['dateBirth']);

    //     if ($dateBirth === false) {
    //         throw new BadRequestHttpException('Invalid birth date format. Please enter the date in dd-mm-yyyy format.');
    //     }

    //     $today = new DateTime();
    //     $age = $today->diff($dateBirth)->y;

    //     if ($age < 12) {
    //         throw new BadRequestHttpException("L'âge de l'utilisateur ne permet pas(12ans)");
    //     } // 406 Bad Request

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

    //     if (isset($requestData['email']) && strlen($requestData['email']) > 80) {
    //         $invalidData[] = 'email';
    //     }
    //     if (isset($requestData['encrypt']) && strlen($requestData['encrypt']) > 30) {
    //         $invalidData[] = 'encrypt';
    //     }

    //     if (isset($requestData['tel']) && strlen($requestData['tel']) > 15) {
    //         $invalidData[] = 'tel';
    //     }

    //     if (!empty($invalidData)) {
    //         return $this->json([
    //             'message' => 'Une ou plusieurs donnée sont erronées',
    //             'data' => $invalidData,
    //         ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
    //     }

    //     $user = new User();
    //     $user->setIdUser($requestData['idUser'] ?? null)
    //         ->setFirstname($requestData['firstname'] ?? null)
    //         ->setLastname($requestData['lastname'] ?? null)
    //         ->setEmail($requestData['email'] ?? null)
    //         ->setSexe($requestData['sexe'] ?? null)
    //         ->setEncrypte($requestData['encrypte'] ?? null)
    //         ->setTel($requestData['tel'] ?? null)
    //         ->setDateBirth($dateBirth)
    //         ->setCreateAt(new DateTimeImmutable())
    //         ->setUpdateAt(new DateTimeImmutable());

    //     $this->entityManager->persist($user);
    //     $this->entityManager->flush();


    //     return $this->json([
    //         'user' => $user->userSerializer(),
    //         'message' => "L'utilisateur a bien été créé avec succès.",
    //         'path' => 'src/Controller/UserController.php',
    //     ], Response::HTTP_CREATED);
    // }

    #[Route('/user', name: 'app_update_user', methods: ['PUT'])]
    public function updateUser(Request $request ): JsonResponse
    {
        $dataMiddellware = $this->tokenVerifier->checkToken($request);
        if(gettype($dataMiddellware) == 'boolean'){
            return $this->json($this->tokenVerifier->sendJsonErrorToken($dataMiddellware));
        }
        $user = $dataMiddellware;

        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        $hasArtist = $user->getArtist() !== null;

        $minimumAge = $hasArtist ? 16 : 12;

        $requiredFields = ['firstname', 'lastname', 'dateBirth'];
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
                'message' => 'Une ou plusieurs données obligatoires sont manquantes : ' .$missingFields,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $invalidData = [];

        if (isset($requestData['idUser']) && strlen($requestData['idUser']) > 90) {
            $invalidData[] = 'idUser';
        }

        if (isset($requestData['firstname']) && strlen($requestData['firstname']) > 55) {
            $invalidData[] = 'firstname';
        }

        if (isset($requestData['lastname']) && strlen($requestData['lastname']) > 55) {
            $invalidData[] = 'lastname';
        }

        if (isset($requestData['tel']) && strlen($requestData['tel']) > 15) {
            $invalidData[] = 'tel';
        }

        if (!empty($invalidData)) {
            return $this->json([
                'message' => 'Une ou plusieurs données sont erronées',
                'data' => $invalidData,
            ], JsonResponse::HTTP_CONFLICT);
        }

        if (isset($requestData['firstname'])) {
            $user->setFirstname($requestData['firstname']);
        }
        if (isset($requestData['lastname'])) {
            $user->setLastname($requestData['lastname']);
        }
        if (isset($requestData['sexe'])) {
            $user->setSexe($requestData['sexe']);
        }
        if (isset($requestData['tel'])) {
            $user->setTel($requestData['tel']);
        }
        if (isset($requestData['dateBirth'])) {
            $dateBirth = DateTimeImmutable::createFromFormat('d-m-Y', $requestData['dateBirth']);
            if ($dateBirth === false) {
                throw new BadRequestHttpException('Format de date de naissance invalide. Veuillez saisir la date au format jj-mm-aaaa.');
            }
            $today = new DateTime();
            $age = $today->diff($dateBirth)->y;

            if ($age < $minimumAge) {
                throw new BadRequestHttpException('L\'utilisateur doit avoir au moins ' . $minimumAge . ' ans pour être mis à jour.');
            }
            $user->setDateBirth($dateBirth);
        }

        $user->setUpdateAt(new DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'user' => $user->userSerializer(),
            'message' => 'Utilisateur mis à jour avec succès!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }


    #[Route('/user/{id}', name: 'app_delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json([
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User deleted successfully!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }
}
