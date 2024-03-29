<?php

namespace App\Controller;

use App\Entity\User;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\DataFixtures\AppFixtures;

class UserController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager =  $entityManager;
        $this->repository =  $entityManager->getRepository(User::class);
    }

    #[Route('/users', name: 'app_get_all_users', methods: ['GET'])]
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

    #[Route('/user', name: 'app_create_user', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $requestData = $request->request->all();
    
        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }
      
        switch ($requestData) {
          case 'idUser' && strlen($requestData['idUser']) > 90:
              throw new BadRequestHttpException('idUser too long');
          case 'name' && strlen($requestData['name']) > 55:
              throw new BadRequestHttpException('User name too long');
          case 'email' && strlen($requestData['email']) > 80:
              throw new BadRequestHttpException('User email too long');
          case 'encrypte' && strlen($requestData['encrypte']) > 90:
              throw new BadRequestHttpException('User Password too long');
          case 'tel' && strlen($requestData['tel']) > 15:
              throw new BadRequestHttpException('Phone number too long');
        }
        
        $user = new User();
        $user->setIdUser($requestData['idUser'] ?? null)
            ->setFirstname($requestData['firstname'] ?? null)
            ->setLastname($requestData['lastname'] ?? null)
            ->setEmail($requestData['email'] ?? null)
            ->setSexe($requestData['sexe'] ?? null)
            ->setEncrypte($requestData['encrypte'] ?? null)
            ->setTel($requestData['tel'] ?? null)
            ->setBirthDate($requestData['birthDate'] ?? null)
            ->setCreateAt(new DateTimeImmutable())
            ->setUpdateAt(new DateTime());
    
        // Persist and flush the user entity
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    
        // Return JSON response
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

        $user->setFirstname($requestData['firstname'] ?? $user->getFirstname())
            ->setLastname($requestData['lastname'] ?? $user->getLastname())
            ->setEmail($requestData['email'] ?? $user->getEmail())
            ->setEncrypte($requestData['encrypte'] ?? $user->getEncrypte())
            ->setTel($requestData['tel'] ?? $user->getTel())
            ->setSexe($requestData['sexe'] ?? $user->getSexe())
            ->setUpdateAt(new \DateTime())
            ->setDateBirth($requestData['dateBirth'] ?? $user->getDateBirth());

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
