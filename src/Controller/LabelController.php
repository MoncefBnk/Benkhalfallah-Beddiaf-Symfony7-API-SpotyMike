<?php

namespace App\Controller;

use App\Entity\Label;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LabelController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/label', name: 'app_create_label', methods: ['POST'])]
    public function createLabel(Request $request): JsonResponse
    {
        $idLabel = $request->request->get('idLabel');
        $labelName = $request->request->get('labelName');

        $existingLabelById = $this->entityManager->getRepository(Label::class)->findOneBy(['idLabel' => $idLabel]);
        if ($existingLabelById) {
            throw new BadRequestHttpException('Label with this idLabel already exists');
        }

        $existingLabelByName = $this->entityManager->getRepository(Label::class)->findOneBy(['labelName' => $labelName]);
        if ($existingLabelByName) {
            throw new BadRequestHttpException('Label with this name already exists');
        }

        if (!$idLabel || !$labelName) {
            return $this->json(['message' => 'Required fields are missing!'], 400);
        }

        if (strlen($idLabel) > 90) {
            throw new BadRequestHttpException('idLabel too long');
        }

        if (strlen($labelName) > 90) {
            throw new BadRequestHttpException('Label name too long');
        }

        $label = new Label();
        $label->setIdLabel($idLabel);
        $label->setLabelName($labelName);

        $this->entityManager->persist($label);
        $this->entityManager->flush();

        return $this->json([
            'label' => $label->labelSerializer(),
            'message' => 'Label created successfully!',
            'path' => 'src/Controller/LabelController.php',
        ]);
    }

    #[Route('/label/{id}', name: 'app_update_label', methods: ['PUT'])]
    public function updateLabel(Request $request, int $id): JsonResponse
    {
        $label = $this->entityManager->getRepository(Label::class)->find($id);

        if (!$label) {
            return $this->json(['message' => 'Label not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $idLabel = $request->request->get('idLabel');
        $labelName = $request->request->get('labelName');

        if ($idLabel) {
            $label->setIdLabel($idLabel);
        }
        if ($labelName) {
            $label->setLabelName($labelName);
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
        $label = $this->entityManager->getRepository(Label::class)->find($id);

        if (!$label) {
            return $this->json(['message' => 'Label not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($label);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Label deleted successfully!',
            'path' => 'src/Controller/LabelController.php',
        ]);
    }

    #[Route('/label/all', name: 'app_get_all_labels', methods: ['GET'])]
    public function getAllLabels(): JsonResponse
    {
        $labels = $this->entityManager->getRepository(Label::class)->findAll();

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

    #[Route('/label/{id}', name: 'app_get_label_by_id', methods: ['GET'])]
    public function getLabelById(int $id): JsonResponse
    {
        $label = $this->entityManager->getRepository(Label::class)->find($id);

        if (!$label) {
            return $this->json(['message' => 'Label not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json([
            'label' => $label->labelSerializer(),
            'message' => 'Label retrieved successfully!',
            'path' => 'src/Controller/LabelController.php',
        ]);
    }
}
