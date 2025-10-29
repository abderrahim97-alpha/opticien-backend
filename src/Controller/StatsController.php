<?php

namespace App\Controller;

use App\Repository\MontureRepository;
use App\Repository\OpticienRepository;
use App\Enum\MontureStatus;
use App\Enum\OpticienStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class StatsController extends AbstractController
{
    #[Route('/api/stats/dashboard', name: 'dashboard_stats', methods: ['GET'])]
    public function getDashboardStats(
        MontureRepository $montureRepo,
        OpticienRepository $opticienRepo
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($isAdmin) {
            // Statistiques pour l'admin
            $stats = [
                'montures' => [
                    'total' => $montureRepo->count([]),
                    'approved' => $montureRepo->count(['status' => MontureStatus::APPROVED]),
                    'pending' => $montureRepo->count(['status' => MontureStatus::PENDING]),
                    'rejected' => $montureRepo->count(['status' => MontureStatus::REJECTED]),
                ],
                'opticiens' => [
                    'total' => $opticienRepo->count([]),
                    'approved' => $opticienRepo->count(['status' => OpticienStatus::APPROVED]),
                    'pending' => $opticienRepo->count(['status' => OpticienStatus::PENDING]),
                    'rejected' => $opticienRepo->count(['status' => OpticienStatus::REJECTED]),
                ],
                'recentMontures' => array_map(
                    fn($m) => [
                        'id' => $m->getId(),
                        'name' => $m->getName(),
                        'status' => $m->getStatus()->value,
                        'createdAt' => $m->getCreatedAt()->format('Y-m-d H:i:s'),
                    ],
                    $montureRepo->findBy([], ['createdAt' => 'DESC'], 5)
                ),
            ];
        } else {
            // Statistiques pour l'opticien
            $stats = [
                'montures' => [
                    'total' => $montureRepo->count(['owner' => $user]),
                    'approved' => $montureRepo->count(['owner' => $user, 'status' => MontureStatus::APPROVED]),
                    'pending' => $montureRepo->count(['owner' => $user, 'status' => MontureStatus::PENDING]),
                    'rejected' => $montureRepo->count(['owner' => $user, 'status' => MontureStatus::REJECTED]),
                ],
                'recentMontures' => array_map(
                    fn($m) => [
                        'id' => $m->getId(),
                        'name' => $m->getName(),
                        'status' => $m->getStatus()->value,
                        'createdAt' => $m->getCreatedAt()->format('Y-m-d H:i:s'),
                    ],
                    $montureRepo->findBy(['owner' => $user], ['createdAt' => 'DESC'], 5)
                ),
            ];
        }

        return $this->json($stats);
    }

    #[Route('/api/stats/montures-by-status', name: 'montures_by_status', methods: ['GET'])]
    public function getMonturesByStatus(MontureRepository $montureRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $criteria = $isAdmin ? [] : ['owner' => $user];

        $data = [
            'approved' => $montureRepo->count(array_merge($criteria, ['status' => MontureStatus::APPROVED])),
            'pending' => $montureRepo->count(array_merge($criteria, ['status' => MontureStatus::PENDING])),
            'rejected' => $montureRepo->count(array_merge($criteria, ['status' => MontureStatus::REJECTED])),
        ];

        return $this->json($data);
    }
}
