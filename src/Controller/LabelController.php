<?php

namespace App\Controller;

use App\Entity\Label;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LabelController extends AbstractController
{
    private $entityManager;
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Label::class);
    }

    #[Route('/label/all', name: 'app_get_all_labels', methods: ['GET'])]
    public function getAllLabels(): JsonResponse
    {
        $labels = $this->repository->findAll();

        $serializedLabels = [];
        foreach ($labels as $label) {
            $serializedLabels[] = $label->labelSerializer();
        }

        return $this->json([
            'labels' => $serializedLabels,
            'message' => 'All labels retrieved successfully!',
            'path' => 'src/Controller/LabelController.php',
        ]);
    }

    #[Route('/label', name: 'app_create_label', methods: ['POST'])]
    public function createLabel(Request $request): JsonResponse
    {
        $requestData = $request->request->all();

        $label = new Label();
        $label->setIdLabel($requestData['idLabel'] ?? null)
            ->setName($requestData['name'] ?? null);

        $this->entityManager->persist($label);
        $this->entityManager->flush();

        return $this->json([
            'label' => $label->labelSerializer(),
            'message' => "Label created successfully.",
            'path' => 'src/Controller/LabelController.php',
        ], Response::HTTP_CREATED);
    }

    #[Route('/label/{id}', name: 'app_update_label', methods: ['PUT'])]
    public function updateLabel(Request $request, int $id): JsonResponse
    {
        $label = $this->repository->find($id);
    
        if (!$label) {
            return $this->json([
                'message' => 'Label not found',
            ], Response::HTTP_NOT_FOUND);
        }
    
        $requestData = $request->request->all();

        if (isset($requestData['idLabel'])) {
            $label->setIdLabel($requestData['idLabel']);
        }
    
        if (isset($requestData['name'])) {
            $label->setName($requestData['name']);
        }
    
        
        $this->entityManager->flush();
    
        return $this->json([
            'label' => $label->labelSerializer(),
            'message' => 'Label updated successfully!',
            'path' => 'src/Controller/LabelController.php',
        ]);
    }
    

    #[Route('/label/{id}', name: 'app_delete_label', methods: ['DELETE'])]
    public function deleteLabel(int $id): JsonResponse
    {
        $label = $this->repository->find($id);

        if (!$label) {
            return $this->json([
                'message' => 'Label not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($label);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Label deleted successfully!',
            'path' => 'src/Controller/LabelController.php',
        ]);
    }
}
