<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Enum\CommandeStatus;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/orders')]
#[IsGranted('ROLE_ADMIN')]
class AdminCommandeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailService $emailService
    ) {}

    /**
     * Liste de TOUTES les commandes (admin only)
     */
    #[Route('', methods: ['GET'])]
    public function listAll(Request $request): JsonResponse
    {
        $status = $request->query->get('status'); // Filter by status

        $criteria = [];
        if ($status) {
            $criteria['status'] = CommandeStatus::from($status);
        }

        $commandes = $this->entityManager->getRepository(Commande::class)
            ->findBy($criteria, ['createdAt' => 'DESC']);

        return $this->json($commandes, 200, [], ['groups' => ['commande:read']]);
    }

    /**
     * Commandes en attente de validation
     */
    #[Route('/pending', methods: ['GET'])]
    public function pending(): JsonResponse
    {
        $commandes = $this->entityManager->getRepository(Commande::class)
            ->findBy(['status' => CommandeStatus::PENDING], ['createdAt' => 'DESC']);

        return $this->json($commandes, 200, [], ['groups' => ['commande:read']]);
    }

    /**
     * VALIDER une commande (après vérification physique)
     */
    #[Route('/{id}/validate', methods: ['PUT'])]
    public function validate(int $id, Request $request): JsonResponse
    {
        $commande = $this->entityManager->getRepository(Commande::class)->find($id);

        if (!$commande) {
            return $this->json(['error' => 'Commande introuvable'], 404);
        }

        if ($commande->getStatus() !== CommandeStatus::PENDING) {
            return $this->json(['error' => 'Cette commande ne peut plus être validée'], 400);
        }

        try {
            // Récupérer la note admin (optionnelle)
            $data = json_decode($request->getContent(), true);
            $noteAdmin = $data['noteAdmin'] ?? null;

            // Changer le statut
            $commande->setStatus(CommandeStatus::VALIDATED);

            if ($noteAdmin) {
                $commande->setNoteAdmin($noteAdmin);
            }

            $this->entityManager->flush();

            // Envoyer email à l'acheteur
            $acheteur = $commande->getAcheteur();
            $this->emailService->sendCommandeValidatedToAcheteur(
                $acheteur->getEmail(),
                $acheteur->getNom() . ' ' . $acheteur->getPrenom(),
                $commande->getId()
            );

            return $this->json([
                'message' => 'Commande validée avec succès',
                'commande' => $commande
            ], 200, [], ['groups' => ['commande:read']]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * REFUSER une commande (après vérification physique)
     * → Restaurer le stock + retourner montures aux vendeurs
     */
    #[Route('/{id}/refuse', methods: ['PUT'])]
    public function refuse(int $id, Request $request): JsonResponse
    {
        $commande = $this->entityManager->getRepository(Commande::class)->find($id);

        if (!$commande) {
            return $this->json(['error' => 'Commande introuvable'], 404);
        }

        if ($commande->getStatus() !== CommandeStatus::PENDING) {
            return $this->json(['error' => 'Cette commande ne peut plus être refusée'], 400);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $raison = $data['raison'] ?? 'Non spécifiée';

            // RESTAURER LE STOCK pour chaque item
            foreach ($commande->getItems() as $item) {
                $monture = $item->getMonture();
                $monture->incrementStock($item->getQuantite());
            }

            // Changer le statut
            $commande->setStatus(CommandeStatus::REFUSED);
            $commande->setNoteAdmin($raison);

            $this->entityManager->flush();

            // Envoyer email à l'acheteur
            $acheteur = $commande->getAcheteur();
            $this->emailService->sendCommandeRefusedToAcheteur(
                $acheteur->getEmail(),
                $acheteur->getNom() . ' ' . $acheteur->getPrenom(),
                $commande->getId(),
                $raison
            );

            // Envoyer emails aux vendeurs (pour chaque vendeur unique)
            $vendeurs = [];
            foreach ($commande->getItems() as $item) {
                $vendeur = $item->getVendeur();
                $vendeurId = $vendeur->getId()->toString();

                if (!isset($vendeurs[$vendeurId])) {
                    $vendeurs[$vendeurId] = $vendeur;

                    $this->emailService->sendCommandeRefusedToVendeur(
                        $vendeur->getEmail(),
                        $vendeur->getNom() . ' ' . $vendeur->getPrenom(),
                        $commande->getId()
                    );
                }
            }

            return $this->json([
                'message' => 'Commande refusée et stock restauré',
                'commande' => $commande
            ], 200, [], ['groups' => ['commande:read']]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Statistiques des commandes
     */
    #[Route('/stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $repo = $this->entityManager->getRepository(Commande::class);

        $stats = [
            'total' => count($repo->findAll()),
            'pending' => count($repo->findBy(['status' => CommandeStatus::PENDING])),
            'validated' => count($repo->findBy(['status' => CommandeStatus::VALIDATED])),
            'refused' => count($repo->findBy(['status' => CommandeStatus::REFUSED])),
            'completed' => count($repo->findBy(['status' => CommandeStatus::COMPLETED])),
        ];

        return $this->json($stats);
    }

    /**
     * Détails d'une commande (admin view)
     */
    #[Route('/{id}', methods: ['GET'])]
    public function details(int $id): JsonResponse
    {
        $commande = $this->entityManager->getRepository(Commande::class)->find($id);

        if (!$commande) {
            return $this->json(['error' => 'Commande introuvable'], 404);
        }

        return $this->json($commande, 200, [], ['groups' => ['commande:read']]);
    }
}
