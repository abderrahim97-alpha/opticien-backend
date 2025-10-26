<?php

namespace App\Controller;

use App\Entity\Opticien;
use App\Enum\OpticienStatus;
use App\Repository\OpticienRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class OpticienController extends AbstractController
{
    #[Route('/api/opticiens/{id}/approve', name: 'approve_opticien', methods: ['PATCH', 'POST'])]
    public function approveOpticien(string $id, OpticienRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_ADMIN')) {
                return $this->json(['error' => 'Forbidden'], 403);
            }

            // Gérer à la fois les UUID et les int
            if (Uuid::isValid($id)) {
                $opticien = $repo->findOneBy(['id' => Uuid::fromString($id)]);
            } else {
                $opticien = $repo->find((int)$id);
            }

            if (!$opticien) {
                return $this->json(['error' => 'Opticien not found'], 404);
            }

            $opticien->setStatus(OpticienStatus::APPROVED);
            $em->flush();

            return $this->json([
                'message' => 'Opticien approved',
                'status' => $opticien->getStatus()->value
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/opticiens/{id}/reject', name: 'reject_opticien', methods: ['PATCH', 'POST'])]
    public function rejectOpticien(string $id, OpticienRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        try {
            if (!$this->isGranted('ROLE_ADMIN')) {
                return $this->json(['error' => 'Forbidden'], 403);
            }

            // Gérer à la fois les UUID et les int
            if (Uuid::isValid($id)) {
                $opticien = $repo->findOneBy(['id' => Uuid::fromString($id)]);
            } else {
                $opticien = $repo->find((int)$id);
            }

            if (!$opticien) {
                return $this->json(['error' => 'Opticien not found'], 404);
            }

            $opticien->setStatus(OpticienStatus::REJECTED);
            $em->flush();

            return $this->json([
                'message' => 'Opticien rejected',
                'status' => $opticien->getStatus()->value
            ], 200);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/my-opticiens', name: 'my_opticiens', methods: ['GET'])]
    public function getMyOpticiens(OpticienRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            // Admin voit tous les opticiens
            $opticiens = $repo->findAll();
        } else {
            // Les autres ne voient que les opticiens approuvés
            $opticiens = $repo->findBy(['status' => OpticienStatus::APPROVED]);
        }

        return $this->json(['member' => $opticiens], 200, [], ['groups' => ['opticien:read']]);
    }
}
