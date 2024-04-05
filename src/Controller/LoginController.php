<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager =  $entityManager;
        $this->repository =  $entityManager->getRepository(User::class);
    }

    
    #[Route('/login', name: 'app_login_post', methods: ['POST', 'PUT'])]
    public function login(Request $request, JWTTokenManagerInterface $JWTManager ): JsonResponse
    {
        $user  = $this->repository->findOneBy(["email" => "moncef1@gmail.com"]);

        $parametres = json_decode($request->getContent(), true);

        return $this->json([
            'token' => $JWTManager->create($user),
            'user' => json_encode($user),
            'data' => $request->getContent(),
            'message' => 'Welcome to SpotyMike!',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }

    #[Route('/', name: 'app_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupérer le contenu de index.html
        $content = file_get_contents(__DIR__ . '/../../public/index.php');

        // Retourner une réponse avec le contenu de index.html
        return new Response($content);
    }


}
