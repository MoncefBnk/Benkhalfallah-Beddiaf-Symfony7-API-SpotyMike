<?php

namespace App\Controller;

use App\Entity\Featuring;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class FeaturingController extends AbstractController
{

    private $entityManager;
    private $repository;
    private $tokenVerifier;

    public function __construct(EntityManagerInterface $entityManager, TokenVerifierService $tokenVerifier)
    {
        $this->entityManager = $entityManager;
        $this->tokenVerifier = $tokenVerifier;
        $this->repository = $entityManager->getRepository(Featuring::class);
    }

    #[Route('/featuring', name: 'app_get_featurings', methods: ['GET'])]
    public function getAllFeaturings(Request $request): JsonResponse
    {
        // Get all featurings
        $featurings = $this->repository->findAll();

        // Serialize featurings
        $serializedFeaturings = [];
        foreach ($featurings as $featuring) {
            $serializedFeaturings[] = $featuring->featuringSerializer();
        }

        // Return JSON response
        return $this->json([
            'error' => false,
            'featurings' => $serializedFeaturings,
            'message' => 'Featurings retrieved successfully.',
        ]);
    }

}
