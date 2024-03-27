<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager =  $entityManager;
        $this->repository =  $entityManager->getRepository(User::class);
    }

    #[Route('/login', name: 'app_login', methods: 'GET')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }
    #[Route('/login', name: 'app_login_post', methods: ['POST', 'PUT'])]
    public function login(Request $request): JsonResponse
    {
        $user  = $this->repository->findOneBy(["email" => "moncef-benkhalfallah@outlook.com"]);
        return $this->json([
            'user' => json_encode($user),
            'data' => $request->getContent(),
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/LoginController.php',
        ]);
    }
    #[Route('/user', name: 'app_create_user', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $requestData = json_decode($request->getContent(), true);

        $user = new User();
        $user->setIdUser($requestData['idUser'])
            ->setName($requestData['name'])
            ->setEmail($requestData['email'])
            ->setEncrypte($requestData['encrypte'])
            ->setTel($requestData['tel'])
            ->setCreateAt(new \DateTimeImmutable())
            ->setUpdateAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'user' => $user->userSerializer(),
            'message' => 'User created successfully!',
            'path' => 'src/Controller/UserController.php',
        ], Response::HTTP_CREATED);
    }
}
