<?php

namespace App\Controller;

use App\Entity\User;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;



class LoginController extends AbstractController
{

    private $entityManager;
    private $repository;


    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager =  $entityManager;
        $this->repository = $entityManager->getRepository(User::class);

    }

    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(): Response
    {
        
        // Récupérer le contenu de index.html if found, else retrun 404
        if (!file_exists(__DIR__ . '/../../public/index.html')) {
            //retourne http 404 si index.html n'existe pas
            return new Response('404 Not Found', Response::HTTP_NOT_FOUND);
        
            
        } 
        else{
        $content = file_get_contents(__DIR__ . '/../../public/index.html');

        // Retourner une réponse avec le contenu de index.html
        return new Response($content);
        }
    }
    
    #[Route('/login', name: 'app_login_post', methods: ['POST'])]
    public function login(Request $request, JWTTokenManagerInterface $JWTManager, UserPasswordHasherInterface $passwordHash): JsonResponse
    {
        $email = $request->request->get('Email');
        $password = $request->request->get('Password');

        if (empty($email) || empty($password)) {
            return $this->json([
                'error' => true,
                'message' => 'Email/password manquants.'
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => true,
                'message' => 'Le format de l\'email est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }
//check if account actif
        

        // Validate password requirements
        $passwordRequirements = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        if (!preg_match($passwordRequirements, $password)) {
            return $this->json([
                'error' => true,
                'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum.',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }

        $user = $this->repository->findOneBy(["email" => $email]);

        if (!$user || !$passwordHash->isPasswordValid($user, $password)) {
            return $this->json([
                'message' => 'Invalid credentials. Please try again.',
            ], JsonResponse::HTTP_UNAUTHORIZED); // 401 Unauthorized
        }

        if ($user->getActive() !== 'Actif') {
            return $this->json([
                'error' => true,
                'message' => 'Le compte n\'est plus actif ou est suspendu.',
            ], JsonResponse::HTTP_FORBIDDEN); // 403 Forbidden
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

        $requiredFields = ['firstname', 'lastname', 'email', 'password', 'dateBirth'];

        foreach ($requiredFields as $field) {
            if (!isset($requestData[$field])) {
                return $this->json([
                    'error' => true,
                    'message' => 'Des champs obligatoires sont manquants.',
                ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
            }
        }

      

        $existingUser = $this->repository->findOneBy(['email' => $requestData['email']]);
        if ($existingUser) {
            return $this->json([
                'error' => true,
                'message' => 'Cette email est déjà utilisée par un autre compte.',
            ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
        } // 409 Conflict
        $dateBirth = DateTimeImmutable::createFromFormat('d/m/Y', $requestData['dateBirth']);

        if ($dateBirth === false) {           
            return $this->json([
                'error' => true,
                'message' => 'Le format de la date de naissance est invalide. le format attendu est JJ/MM/AAAA.',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }

        $today = new DateTime();
        $age = $today->diff($dateBirth)->y;

        if ($age < 12) {
            return $this->json([
                'error' => true,
                'message' => "l'utilisateur doit avoir au moins 12 ans.",
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        } // 406 Bad Request

        $invalidData = [];


        if (isset($requestData['firstname']) && strlen($requestData['firstname']) > 55) {
            $invalidData[] = 'firstname';
        }

        if (isset($requestData['lastname']) && strlen($requestData['lastname']) > 55) {
            $invalidData[] = 'lastname';
        }

        if (isset($requestData['email']) && strlen($requestData['email']) > 80) {
            $invalidData[] = 'email';
        }
        if (isset($requestData['password']) && strlen($requestData['password']) > 30) {
            $invalidData[] = 'password';
        }

        if (isset($requestData['tel']) && strlen($requestData['tel']) > 15) {
            $invalidData[] = 'tel';
        }

        if (!empty($invalidData)) {
            return $this->json([
                'error' => true,
                'message' => 'Une ou plusieurs donnée sont erronées',
            ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
        }

        $password = $requestData['password'] ?? null;
        $email = $requestData['email'] ?? null;
        $tel = $requestData['tel'] ?? null;

        //validate tel requirements 
        if (!preg_match('/^0[6-7][0-9]{8}$/', $tel)) {
            return $this->json([
                'error' => true,
                'message' => 'Le format du numéro de téléphone est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }

        // Validate password requirements
        $passwordRequirements = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        if (!preg_match($passwordRequirements, $password)) {
            return $this->json([
                'error' => true,
                'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre, un caractère spécial et avoir 8 caractères minimum',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => true,
                'message' => 'Le format de l\'email est invalide.',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }
        
        //if sexe isn't 0 or 1 return error
        if ($requestData['sexe'] != 0 && $requestData['sexe'] != 1) {
            return $this->json([
                'error' => true,
                'message' => 'La valeur du champ sexe est invalide. Les valeurs autorisées sont 0 pour Femme, 1 pour Homme.',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }
        $user = new User();
        $hash = $passwordHash->hashPassword($user, $password);
        $user->setFirstname($requestData['firstname'] ?? null)
            ->setLastname($requestData['lastname'] ?? null)
            ->setEmail($requestData['email'] ?? null)
            ->setSexe($requestData['sexe'] ?? null)
            ->setPassword($hash)
            ->setTel($requestData['tel'] ?? null)
            ->setDateBirth($dateBirth)
            ->setActive('Actif')
            ->setCreateAt(new DateTimeImmutable())
            ->setUpdateAt(new DateTimeImmutable());
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();


        return $this->json([
            'error' => false,
            'message' => "L'utilisateur a bien été créé avec succès.",
            'user' => $user->userSerializer(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/password-lost', name: 'app_reset_password', methods: ['POST'])]
    // it requires email then if it exists in the database it will send an email to the user with a link to reset the password
    public function resetPassword(Request $request): JsonResponse
    {
        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        $email = $requestData['email'] ?? null;
        if (empty($email)) {
            return $this->json([
                'error' => true,
                'message' => 'Email manquant. Veuillez fournir votre email pour la récupération du mot de passe.',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => true,
                'message' => 'Le format de l\'email est invalide. Veuillez entrer un email valide.',
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }

        // Rate limiter
        $cache = new FilesystemAdapter();
        $cacheKey = 'reset_password_' . urlencode($email);
        $cacheItem = $cache->getItem($cacheKey);
        $requestCount = $cacheItem->get() ?? 0;

        if ($requestCount >= 3) {
            return $this->json([
                'error' => true,
                'message' => 'Trop de demande de réinitialisation de mot de passe ( 3 max ). Veuillez attendre avant de réessayer ( Dans 5 min).',
            ], JsonResponse::HTTP_TOO_MANY_REQUESTS); // 429 Too Many Requests
        }

        $cacheItem->set($requestCount + 1);
        $cacheItem->expiresAfter(5); // 20 seconds
        $cache->save($cacheItem);

        $user = $this->repository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json([
                'error' => true,
                'message' => 'Aucun compte n\'est associé à cet email. Veuillez vérifier et réessayer.',
            ], JsonResponse::HTTP_NOT_FOUND); // 404 Not Found
        }

        // send email to the user with a link to reset the password
        // $this->mailer->send($email, 'Reset your password', 'Click on the link below to reset your password: http://localhost:8000/reset-password/' . $user->getId());

        return $this->json([
            'success' => true,
            'message' => 'Un email de réinitialisation de mot de passe a été envoyé à votre adresse email. Veuillez suivre les instructions contenues dans l\'email pour réinitialiser votre mot de passe.',
        ]);
    }
}
