<?php

namespace App\Controller;

use App\Entity\User;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginController extends AbstractController
{

    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager =  $entityManager;
        $this->repository = $entityManager->getRepository(User::class);
    }

    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        
        // Récupérer le contenu de index.html
        $content = file_get_contents(__DIR__ . '/../../public/index.php');

        // Retourner une réponse avec le contenu de index.html
        return new Response($content);
    }
    
    #[Route('/login', name: 'app_login_post', methods: ['POST'])]
    public function login(Request $request, JWTTokenManagerInterface $JWTManager, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => true,
                'message' => 'Le format de l\'email est invalide',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }

        // Validate password requirements
        $passwordRequirements = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        if (!preg_match($passwordRequirements, $password)) {
            return $this->json([
                'error' => true,
                'message' => 'Format de mot de passe invalide. Le mot de passe doit comporter au moins 8 caractères et contenir au moins une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial.',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }

        $user = $this->repository->findOneBy(["email" => $email]);

        if (!$user || !$passwordHash->isPasswordValid($user, $password)) {
            return $this->json([
                'message' => 'Invalid credentials. Please try again.',
            ], JsonResponse::HTTP_UNAUTHORIZED); // 401 Unauthorized
        }

        return $this->json([
            'error' => false,
            'message' => 'l\'utilisateur a été authentifié avec succès',
            'user' => $user->userSerializer(),
            'token' => $JWTManager->create($user), // A ENLEVER //
        ]);
    }


    #[Route('/register', name: 'app_create_user', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        $requiredFields = ['firstname', 'lastname', 'email', 'encrypte', 'dateBirth'];

        foreach ($requiredFields as $field) {
            if (!isset($requestData[$field])) {
                return $this->json([
                    'message' => 'Une ou plusieurs données obligatoires sont manquantes : ' . $field,
                ], JsonResponse::HTTP_BAD_REQUEST); // 409 Conflict
            }
        }

        $existingUserWithIdUser = $this->repository->findOneBy(['idUser' => $requestData['idUser']]);
        if ($existingUserWithIdUser) {
            throw new BadRequestHttpException("Un compte utilisant cette IdUser est déjà enregistré");
        } // 409 Conflict

        $existingUser = $this->repository->findOneBy(['email' => $requestData['email']]);
        if ($existingUser) {
            throw new BadRequestHttpException("Un compte utilisant cette adresse mail est déjà enregistré");
        } // 409 Conflict
        $dateBirth = DateTimeImmutable::createFromFormat('d-m-Y', $requestData['dateBirth']);

        if ($dateBirth === false) {
            throw new BadRequestHttpException('Invalid birth date format. Please enter the date in dd-mm-yyyy format.');
        }

        $today = new DateTime();
        $age = $today->diff($dateBirth)->y;

        if ($age < 12) {
            throw new BadRequestHttpException("L'âge de l'utilisateur ne permet pas(12ans)");
        } // 406 Bad Request

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

        if (isset($requestData['email']) && strlen($requestData['email']) > 80) {
            $invalidData[] = 'email';
        }
        if (isset($requestData['encrypt']) && strlen($requestData['encrypt']) > 30) {
            $invalidData[] = 'encrypt';
        }

        if (isset($requestData['tel']) && strlen($requestData['tel']) > 15) {
            $invalidData[] = 'tel';
        }

        if (!empty($invalidData)) {
            return $this->json([
                'message' => 'Une ou plusieurs donnée sont erronées',
                'data' => $invalidData,
            ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
        }

        $password = $requestData['encrypte'] ?? null;
        
        $user = new User();
        $hash = $passwordHash->hashPassword($user, $password);
        $user->setIdUser($requestData['idUser'] ?? null)
            ->setFirstname($requestData['firstname'] ?? null)
            ->setLastname($requestData['lastname'] ?? null)
            ->setEmail($requestData['email'] ?? null)
            ->setSexe($requestData['sexe'] ?? null)
            ->setPassword($hash)
            ->setTel($requestData['tel'] ?? null)
            ->setDateBirth($dateBirth)
            ->setCreateAt(new DateTimeImmutable())
            ->setUpdateAt(new DateTimeImmutable());
        

        $this->entityManager->persist($user);
        $this->entityManager->flush();


        return $this->json([
            'isNotGoodPassword' => ($passwordHash->isPasswordValid($user, 'Zoubida') ),
            'isGoodPassword' => ($passwordHash->isPasswordValid($user, $password) ),
            'user' => $user->userSerializer(),
            'message' => "L'utilisateur a bien été créé avec succès.",
            'path' => 'src/Controller/UserController.php',
        ], Response::HTTP_CREATED);
    }


}
