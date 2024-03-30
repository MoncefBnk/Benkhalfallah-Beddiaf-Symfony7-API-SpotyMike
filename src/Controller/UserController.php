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

class UserController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

    #[Route('/user', name: 'app_create_user', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }

        $existingUserWithIdUser = $this->repository->findOneBy(['idUser' => $requestData['idUser']]);
        if ($existingUserWithIdUser) {
            throw new BadRequestHttpException('idUser already exists');
        }

        $existingUser = $this->repository->findOneBy(['email' => $requestData['email']]);
        if ($existingUser) {
            throw new BadRequestHttpException('Email already exists');
        }
        $dateBirth = DateTimeImmutable::createFromFormat('d-m-Y', $requestData['dateBirth']);

        if ($dateBirth === false) {
            throw new BadRequestHttpException('Invalid birth date format. Please enter the date in dd-mm-yyyy format.');
        }

        switch ($requestData) {
            case 'idUser' && strlen($requestData['idUser']) > 90:
                throw new BadRequestHttpException('idUser too long');
            case 'name' && strlen($requestData['firstname']) > 55:
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
            ->setDateBirth($dateBirth)
            ->setCreateAt(new DateTimeImmutable())
            ->setUpdateAt(new DateTimeImmutable());
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
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json([
                'message' => 'User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $requestData = $request->request->all();

        if ($request->headers->get('content-type') === 'application/json') {
            $requestData = json_decode($request->getContent(), true);
        }



        if (isset($requestData['firstname'])) {
            $user->setFirstname($requestData['firstname']);
        }
        if (isset($requestData['lastname'])) {
            $user->setLastname($requestData['lastname']);
        }
        if (isset($requestData['email'])) {
            $existingUser = $this->repository->findOneBy(['email' => $requestData['email']]);
            if ($existingUser) {
                throw new BadRequestHttpException('Email already exists');
            } else {
                $user->setEmail($requestData['email']);
            }
        }
        if (isset($requestData['sexe'])) {
            $user->setSexe($requestData['sexe']);
        }
        if (isset($requestData['encrypte'])) {
            $user->setEncrypte($requestData['encrypte']);
        }
        if (isset($requestData['tel'])) {
            $user->setTel($requestData['tel']);
        }
        if (isset($requestData['dateBirth'])) {
            $dateBirth = DateTimeImmutable::createFromFormat('d-m-Y', $requestData['dateBirth']);
            if ($dateBirth === false) {
                throw new BadRequestHttpException('Invalid birth date format. Please enter the date in dd-mm-yyyy format.');
            }
            $user->setDateBirth($dateBirth);
        }

        $user->setUpdateAt(new DateTimeImmutable());

        // Persist and flush the updated user entity
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Return JSON response
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
