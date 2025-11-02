<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeItem;
use App\Entity\Monture;
use App\Enum\CommandeStatus;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/orders')]
#[IsGranted('ROLE_OPTICIEN')]
class CommandeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailService $emailService
    ) {}

    /**
     * Créer une nouvelle commande (depuis le panier)
     */
    #[Route('/create', methods: ['POST'])] // ← Route devient /api/orders/create
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['items']) || empty($data['items'])) {
                return $this->json(['error' => 'Le panier est vide'], 400);
            }

            $acheteur = $this->getUser();

            // Créer la commande
            $commande = new Commande();
            $commande->setAcheteur($acheteur);

            // Ajouter les items et décrémenter le stock
            foreach ($data['items'] as $itemData) {
                $monture = $this->entityManager->getRepository(Monture::class)
                    ->find($itemData['montureId']);

                if (!$monture) {
                    return $this->json(['error' => "Monture {$itemData['montureId']} introuvable"], 404);
                }

                // Vérifier que la monture est approuvée
                if ($monture->getStatus()->value !== 'approved') {
                    return $this->json(['error' => "La monture '{$monture->getName()}' n'est pas disponible"], 400);
                }

                $quantite = $itemData['quantite'] ?? 1;

                // Vérifier le stock disponible
                if (!$monture->hasEnoughStock($quantite)) {
                    return $this->json([
                        'error' => "Stock insuffisant pour '{$monture->getName()}'. Disponible: {$monture->getStock()}"
                    ], 400);
                }

                // Créer l'item de commande
                $item = new CommandeItem();
                $item->setMonture($monture);
                $item->setQuantite($quantite);

                // Décrémenter le stock IMMÉDIATEMENT
                $monture->decrementStock($quantite);

                $commande->addItem($item);
            }

            // Calculer le prix total
            $commande->calculateTotalPrice();

            // Sauvegarder
            $this->entityManager->persist($commande);
            $this->entityManager->flush();

            // Envoyer les emails
            $this->emailService->sendCommandeCreatedToAcheteur(
                $acheteur->getEmail(),
                $acheteur->getNom() . ' ' . $acheteur->getPrenom(),
                $commande->getId()
            );

            $this->emailService->sendCommandeCreatedToAdmin(
                $commande->getId(),
                $acheteur->getNom() . ' ' . $acheteur->getPrenom()
            );

            return $this->json([
                'message' => 'Commande créée avec succès',
                'commandeId' => $commande->getId(),
                'commande' => $commande
            ], 201, [], ['groups' => ['commande:read']]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Liste de MES commandes (en tant qu'acheteur)
     */
    #[Route('/my-purchases', methods: ['GET'])] // ← Route devient /api/orders/my-purchases
    public function mesAchats(): JsonResponse
    {
        $acheteur = $this->getUser();

        $commandes = $this->entityManager->getRepository(Commande::class)
            ->findBy(['acheteur' => $acheteur], ['createdAt' => 'DESC']);

        return $this->json($commandes, 200, [], ['groups' => ['commande:read']]);
    }

    /**
     * Liste de MES ventes (montures vendues)
     */
    #[Route('/my-sales', methods: ['GET'])] // ← Route devient /api/orders/my-sales
    public function mesVentes(): JsonResponse
    {
        $vendeur = $this->getUser();

        // Récupérer tous les CommandeItem où je suis le vendeur
        $items = $this->entityManager->getRepository(CommandeItem::class)
            ->findBy(['vendeur' => $vendeur]);

        // Grouper par commande
        $ventes = [];
        foreach ($items as $item) {
            $commandeId = $item->getCommande()->getId();
            if (!isset($ventes[$commandeId])) {
                $ventes[$commandeId] = [
                    'commande' => $item->getCommande(),
                    'items' => []
                ];
            }
            $ventes[$commandeId]['items'][] = $item;
        }

        return $this->json(array_values($ventes), 200, [], ['groups' => ['commande:read']]);
    }

    /**
     * Détails d'une commande
     */
    #[Route('/{id}', methods: ['GET'])] // ← Route devient /api/orders/{id}
    public function details(int $id): JsonResponse
    {
        $commande = $this->entityManager->getRepository(Commande::class)->find($id);

        if (!$commande) {
            return $this->json(['error' => 'Commande introuvable'], 404);
        }

        $user = $this->getUser();

        // Vérifier que l'utilisateur a accès (acheteur ou vendeur d'un item)
        $hasAccess = $commande->getAcheteur()->getId() === $user->getId();

        if (!$hasAccess) {
            foreach ($commande->getItems() as $item) {
                if ($item->getVendeur()->getId() === $user->getId()) {
                    $hasAccess = true;
                    break;
                }
            }
        }

        if (!$hasAccess && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        return $this->json($commande, 200, [], ['groups' => ['commande:read']]);
    }
}
