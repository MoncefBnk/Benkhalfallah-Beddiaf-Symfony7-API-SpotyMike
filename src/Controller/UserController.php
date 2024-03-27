<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager =  $entityManager;
        $this->repository =  $entityManager->getRepository(User::class);
    }

    #[Route('/user', name: 'app_user', methods: ['GET'])]
    public function getAllUsers(Request $request): JsonResponse
    {
        $users = $this->repository->findAll();
        $serializedUsers = [];
    
        foreach ($users as $user) {
        $serializedUsers[] = $user->userSerializer();
    }
        $user  = $this->repository->findAll();
        return $this->json([
            'user' => $serializedUsers,
            'data' => $request->getContent(),
            'message' => 'All Users!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }
}
