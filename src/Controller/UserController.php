<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
            'user' => $user,
            'data' => $request->getContent(),
            'message' => 'All Users!',
            'path' => 'src/Controller/UserController.php',
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

    #[Route('/user/{id}', name: 'app_update_user', methods: ['PUT'])]
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($id);

        if (!$user) {
            return $this->json([
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $requestData = json_decode($request->getContent(), true);

        $user->setName($requestData['name'] ?? $user->getName())
            ->setEmail($requestData['email'] ?? $user->getEmail())
            ->setEncrypte($requestData['encrypte'] ?? $user->getEncrypte())
            ->setTel($requestData['tel'] ?? $user->getTel())
            ->setUpdateAt(new \DateTime());

        $this->entityManager->flush();

        return $this->json([
            'user' => $user->userSerializer(),
            'message' => 'User updated successfully!',
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
