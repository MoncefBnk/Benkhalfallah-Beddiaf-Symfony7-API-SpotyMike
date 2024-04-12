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
            'message' => 'All users retrieved successfully!',
            'path' => 'src/Controller/SongController.php',
        ]);
    }

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
        if(isset($requestData['firstname']) &&
            empty($requestData['firstname']) || //firstname empty
            strlen($requestData['firstname']) > 90

       
         ) {
            return $this->json([
                'error' => true,
                'message' => 'Erreur de validation des données.',
            ], JsonResponse:: HTTP_UNPROCESSABLE_ENTITY);
        }

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
                    'message' => 'La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme.',
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
                    'message' => 'Conflit de données. Le numéro de téléphone est déjà utilisé par un autre utilisateur.',
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
             $user->setSexe($requestData['sexe']);
        }
        if (isset($requestData['tel'])) {
            $user->setTel($requestData['tel']);
        }

        $user->setUpdateAt(new DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'error' => false,
            'message' => 'Votre inscription a bien été prise en compte',
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

        // if active = 'inactive' return error
        if ($dataMiddellware->getActive() === 'Inactive') {
            return $this->json([
                'error' => true,
                'message' => 'Le compte est déjà désactivé.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $dataMiddellware;

        $user->setActive('Inactive');
        $user->setUpdateAt(new DateTimeImmutable());

        //if user has artist profile, deactivate it
        if ($user->getArtist()) {
            $artist = $user->getArtist();
            $artist->setActive('Inactive');
            $this->entityManager->persist($artist);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Votre compte a été désactivé avec succès. Nous sommes désolés de vous voir partir.',
          
        ]);
    }
}

//verifier les données
//tester toutes les routes
//mettre les guards pour la restriction sur le login
//definir le token de reinitialisation du mdp 
