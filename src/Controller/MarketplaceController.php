<?php

namespace App\Controller;

use App\Entity\Monture;
use App\Enum\MontureStatus;
use App\Enum\MontureType;
use App\Enum\MontureGenre;
use App\Enum\MontureForme;
use App\Enum\MontureMateriau;
use App\Repository\MontureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/marketplace')]
class MarketplaceController extends AbstractController
{
    /**
     * Liste des montures disponibles dans la marketplace
     * (Uniquement APPROVED + stock > 0 + pas mes propres montures)
     */
    #[Route('/montures', methods: ['GET'])]
    public function listMontures(Request $request, MontureRepository $repo): JsonResponse
    {
        $user = $this->getUser();

        // Paramètres de filtrage
        $search = $request->query->get('search', '');
        $brand = $request->query->get('brand', '');
        $type = $request->query->get('type', '');           // vue | soleil
        $genre = $request->query->get('genre', '');         // homme | femme | enfant | unisexe
        $forme = $request->query->get('forme', '');         // rectangulaire | ronde | etc.
        $couleur = $request->query->get('couleur', '');     // noir | bleu | etc.
        $materiau = $request->query->get('materiau', '');   // acetate | metal | etc.
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $sortBy = $request->query->get('sortBy', 'createdAt'); // createdAt, price, name, brand
        $sortOrder = $request->query->get('sortOrder', 'DESC'); // ASC, DESC
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 12)));

        // Query builder
        $qb = $repo->createQueryBuilder('m')
            ->where('m.status = :status')
            ->andWhere('m.stock > 0')
            ->setParameter('status', MontureStatus::APPROVED);

        // Exclure mes propres montures
        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('m.owner != :user')
                ->setParameter('user', $user);
        }

        // Filtre: recherche par nom ou description
        if ($search) {
            $qb->andWhere('m.name LIKE :search OR m.description LIKE :search OR m.brand LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Filtre: marque
        if ($brand) {
            $qb->andWhere('m.brand = :brand')
                ->setParameter('brand', $brand);
        }

        // ========== NOUVEAUX FILTRES ==========

        // Filtre: type (vue/soleil)
        if ($type && MontureType::tryFrom($type)) {
            $qb->andWhere('m.type = :type')
                ->setParameter('type', MontureType::from($type));
        }

        // Filtre: genre
        if ($genre && MontureGenre::tryFrom($genre)) {
            $qb->andWhere('m.genre = :genre')
                ->setParameter('genre', MontureGenre::from($genre));
        }

        // Filtre: forme
        if ($forme && MontureForme::tryFrom($forme)) {
            $qb->andWhere('m.forme = :forme')
                ->setParameter('forme', MontureForme::from($forme));
        }

        // Filtre: couleur
        if ($couleur) {
            $qb->andWhere('LOWER(m.couleur) LIKE :couleur')
                ->setParameter('couleur', '%' . strtolower($couleur) . '%');
        }

        // Filtre: matériau
        if ($materiau && MontureMateriau::tryFrom($materiau)) {
            $qb->andWhere('m.materiau = :materiau')
                ->setParameter('materiau', MontureMateriau::from($materiau));
        }

        // =====================================

        // Filtre: prix minimum
        if ($minPrice !== null && is_numeric($minPrice)) {
            $qb->andWhere('m.price >= :minPrice')
                ->setParameter('minPrice', (float) $minPrice);
        }

        // Filtre: prix maximum
        if ($maxPrice !== null && is_numeric($maxPrice)) {
            $qb->andWhere('m.price <= :maxPrice')
                ->setParameter('maxPrice', (float) $maxPrice);
        }

        // Tri
        $allowedSortFields = ['createdAt', 'price', 'name', 'brand'];
        if (in_array($sortBy, $allowedSortFields)) {
            $qb->orderBy('m.' . $sortBy, strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC');
        }

        // Pagination
        $totalQuery = clone $qb;
        $total = count($totalQuery->getQuery()->getResult());

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $montures = $qb->getQuery()->getResult();

        return $this->json([
            'data' => $montures,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($total / $limit),
            ],
            'filters' => [
                'search' => $search,
                'brand' => $brand,
                'type' => $type,
                'genre' => $genre,
                'forme' => $forme,
                'couleur' => $couleur,
                'materiau' => $materiau,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
            ]
        ], 200, [], ['groups' => ['monture:read']]);
    }

    /**
     * Détail d'une monture dans la marketplace
     */
    #[Route('/montures/{id}', methods: ['GET'])]
    public function montureDetail(int $id, MontureRepository $repo): JsonResponse
    {
        $monture = $repo->find($id);

        if (!$monture) {
            return $this->json(['error' => 'Monture introuvable'], 404);
        }

        // Vérifier que la monture est bien disponible
        if ($monture->getStatus() !== MontureStatus::APPROVED) {
            return $this->json(['error' => 'Cette monture n\'est pas disponible'], 403);
        }

        if ($monture->getStock() <= 0) {
            return $this->json(['error' => 'Cette monture est en rupture de stock'], 403);
        }

        return $this->json($monture, 200, [], ['groups' => ['monture:read']]);
    }

    /**
     * Liste des marques disponibles (pour le filtre)
     */
    #[Route('/brands', methods: ['GET'])]
    public function availableBrands(MontureRepository $repo): JsonResponse
    {
        $qb = $repo->createQueryBuilder('m')
            ->select('DISTINCT m.brand')
            ->where('m.status = :status')
            ->andWhere('m.stock > 0')
            ->andWhere('m.brand IS NOT NULL')
            ->setParameter('status', MontureStatus::APPROVED)
            ->orderBy('m.brand', 'ASC');

        $brands = array_map(fn($row) => $row['brand'], $qb->getQuery()->getResult());

        return $this->json($brands);
    }

    /**
     * Liste des types disponibles (vue/soleil)
     */
    #[Route('/types', methods: ['GET'])]
    public function availableTypes(): JsonResponse
    {
        $types = array_map(
            fn(MontureType $type) => [
                'value' => $type->value,
                'label' => match($type) {
                    MontureType::VUE => '👓 Lunettes de vue',
                    MontureType::SOLEIL => '🕶️ Lunettes de soleil',
                }
            ],
            MontureType::cases()
        );

        return $this->json($types);
    }

    /**
     * Liste des genres disponibles
     */
    #[Route('/genres', methods: ['GET'])]
    public function availableGenres(): JsonResponse
    {
        $genres = array_map(
            fn(MontureGenre $genre) => [
                'value' => $genre->value,
                'label' => match($genre) {
                    MontureGenre::HOMME => '👨 Homme',
                    MontureGenre::FEMME => '👩 Femme',
                    MontureGenre::ENFANT => '👶 Enfant',
                    MontureGenre::UNISEXE => '⚡ Unisexe',
                }
            ],
            MontureGenre::cases()
        );

        return $this->json($genres);
    }

    /**
     * Liste des formes disponibles
     */
    #[Route('/formes', methods: ['GET'])]
    public function availableFormes(): JsonResponse
    {
        $formes = array_map(
            fn(MontureForme $forme) => [
                'value' => $forme->value,
                'label' => match($forme) {
                    MontureForme::RECTANGULAIRE => 'Rectangulaire',
                    MontureForme::RONDE => 'Ronde',
                    MontureForme::OVALE => 'Ovale',
                    MontureForme::CARREE => 'Carrée',
                    MontureForme::AVIATEUR => 'Aviateur',
                    MontureForme::PAPILLON => 'Papillon (Cat-eye)',
                    MontureForme::CLUBMASTER => 'Clubmaster',
                    MontureForme::SPORT => 'Sport',
                    MontureForme::GEOMETRIQUE => 'Géométrique',
                }
            ],
            MontureForme::cases()
        );

        return $this->json($formes);
    }

    /**
     * Liste des matériaux disponibles
     */
    #[Route('/materiaux', methods: ['GET'])]
    public function availableMateriaux(): JsonResponse
    {
        $materiaux = array_map(
            fn(MontureMateriau $mat) => [
                'value' => $mat->value,
                'label' => $mat->label(),
                'description' => $mat->description()
            ],
            MontureMateriau::cases()
        );

        return $this->json($materiaux);
    }

    /**
     * Liste des couleurs disponibles (depuis la base de données)
     */
    #[Route('/couleurs', methods: ['GET'])]
    public function availableCouleurs(MontureRepository $repo): JsonResponse
    {
        $qb = $repo->createQueryBuilder('m')
            ->select('DISTINCT m.couleur')
            ->where('m.status = :status')
            ->andWhere('m.stock > 0')
            ->andWhere('m.couleur IS NOT NULL')
            ->setParameter('status', MontureStatus::APPROVED)
            ->orderBy('m.couleur', 'ASC');

        $couleurs = array_map(fn($row) => $row['couleur'], $qb->getQuery()->getResult());

        return $this->json($couleurs);
    }

    /**
     * Statistiques de la marketplace
     */
    #[Route('/stats', methods: ['GET'])]
    public function marketplaceStats(MontureRepository $repo): JsonResponse
    {
        $user = $this->getUser();

        $qb = $repo->createQueryBuilder('m')
            ->where('m.status = :status')
            ->andWhere('m.stock > 0')
            ->setParameter('status', MontureStatus::APPROVED);

        // Exclure mes propres montures
        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('m.owner != :user')
                ->setParameter('user', $user);
        }

        $montures = $qb->getQuery()->getResult();

        $totalMontures = count($montures);
        $totalStock = array_sum(array_map(fn($m) => $m->getStock(), $montures));

        // Prix moyen
        $prices = array_map(fn($m) => $m->getPrice(), $montures);
        $avgPrice = $totalMontures > 0 ? array_sum($prices) / $totalMontures : 0;

        // Prix min/max
        $minPrice = $totalMontures > 0 ? min($prices) : 0;
        $maxPrice = $totalMontures > 0 ? max($prices) : 0;

        return $this->json([
            'totalMontures' => $totalMontures,
            'totalStock' => $totalStock,
            'averagePrice' => round($avgPrice, 2),
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
        ]);
    }

    /**
     * Montures similaires (même type, genre, prix proche)
     */
    #[Route('/montures/{id}/similar', methods: ['GET'])]
    public function similarMontures(int $id, MontureRepository $repo): JsonResponse
    {
        $monture = $repo->find($id);

        if (!$monture) {
            return $this->json(['error' => 'Monture introuvable'], 404);
        }

        $user = $this->getUser();

        // Rechercher des montures similaires
        $qb = $repo->createQueryBuilder('m')
            ->where('m.status = :status')
            ->andWhere('m.stock > 0')
            ->andWhere('m.id != :currentId')
            ->setParameter('status', MontureStatus::APPROVED)
            ->setParameter('currentId', $id);

        // Même marque (priorité haute)
        if ($monture->getBrand()) {
            $qb->andWhere('m.brand = :brand')
                ->setParameter('brand', $monture->getBrand());
        }

        // Même type (vue/soleil)
        if ($monture->getType()) {
            $qb->andWhere('m.type = :type')
                ->setParameter('type', $monture->getType());
        }

        // Même genre
        if ($monture->getGenre()) {
            $qb->andWhere('m.genre = :genre')
                ->setParameter('genre', $monture->getGenre());
        }

        // Prix dans une fourchette de ±30%
        $priceRange = $monture->getPrice() * 0.3;
        $qb->andWhere('m.price BETWEEN :minPrice AND :maxPrice')
            ->setParameter('minPrice', $monture->getPrice() - $priceRange)
            ->setParameter('maxPrice', $monture->getPrice() + $priceRange);

        // Exclure mes propres montures
        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('m.owner != :user')
                ->setParameter('user', $user);
        }

        $qb->setMaxResults(4);

        $similar = $qb->getQuery()->getResult();

        return $this->json($similar, 200, [], ['groups' => ['monture:read']]);
    }

    /**
     * Vérifier la disponibilité d'une monture avant l'achat
     */
    #[Route('/montures/{id}/check-availability', methods: ['POST'])]
    public function checkAvailability(int $id, Request $request, MontureRepository $repo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $quantity = $data['quantity'] ?? 1;

        $monture = $repo->find($id);

        if (!$monture) {
            return $this->json(['error' => 'Monture introuvable'], 404);
        }

        if ($monture->getStatus() !== MontureStatus::APPROVED) {
            return $this->json([
                'available' => false,
                'reason' => 'Monture non disponible'
            ], 200);
        }

        if (!$monture->hasEnoughStock($quantity)) {
            return $this->json([
                'available' => false,
                'reason' => 'Stock insuffisant',
                'availableStock' => $monture->getStock()
            ], 200);
        }

        return $this->json([
            'available' => true,
            'stock' => $monture->getStock(),
            'price' => $monture->getPrice(),
            'totalPrice' => $monture->getPrice() * $quantity
        ], 200);
    }

    /**
     * Filtres avancés - Obtenir toutes les options de filtrage en une seule requête
     */
    #[Route('/filters/options', methods: ['GET'])]
    public function filterOptions(MontureRepository $repo): JsonResponse
    {
        // Marques
        $qbBrands = $repo->createQueryBuilder('m')
            ->select('DISTINCT m.brand')
            ->where('m.status = :status')
            ->andWhere('m.stock > 0')
            ->andWhere('m.brand IS NOT NULL')
            ->setParameter('status', MontureStatus::APPROVED)
            ->orderBy('m.brand', 'ASC');
        $brands = array_map(fn($row) => $row['brand'], $qbBrands->getQuery()->getResult());

        // Couleurs
        $qbCouleurs = $repo->createQueryBuilder('m')
            ->select('DISTINCT m.couleur')
            ->where('m.status = :status')
            ->andWhere('m.stock > 0')
            ->andWhere('m.couleur IS NOT NULL')
            ->setParameter('status', MontureStatus::APPROVED)
            ->orderBy('m.couleur', 'ASC');
        $couleurs = array_map(fn($row) => $row['couleur'], $qbCouleurs->getQuery()->getResult());

        // Types
        $types = array_map(
            fn(MontureType $type) => [
                'value' => $type->value,
                'label' => match($type) {
                    MontureType::VUE => '👓 Lunettes de vue',
                    MontureType::SOLEIL => '🕶️ Lunettes de soleil',
                }
            ],
            MontureType::cases()
        );

        // Genres
        $genres = array_map(
            fn(MontureGenre $genre) => [
                'value' => $genre->value,
                'label' => match($genre) {
                    MontureGenre::HOMME => '👨 Homme',
                    MontureGenre::FEMME => '👩 Femme',
                    MontureGenre::ENFANT => '👶 Enfant',
                    MontureGenre::UNISEXE => '⚡ Unisexe',
                }
            ],
            MontureGenre::cases()
        );

        // Formes
        $formes = array_map(
            fn(MontureForme $forme) => [
                'value' => $forme->value,
                'label' => match($forme) {
                    MontureForme::RECTANGULAIRE => 'Rectangulaire',
                    MontureForme::RONDE => 'Ronde',
                    MontureForme::OVALE => 'Ovale',
                    MontureForme::CARREE => 'Carrée',
                    MontureForme::AVIATEUR => 'Aviateur',
                    MontureForme::PAPILLON => 'Papillon (Cat-eye)',
                    MontureForme::CLUBMASTER => 'Clubmaster',
                    MontureForme::SPORT => 'Sport',
                    MontureForme::GEOMETRIQUE => 'Géométrique',
                }
            ],
            MontureForme::cases()
        );

        // Matériaux
        $materiaux = array_map(
            fn(MontureMateriau $mat) => [
                'value' => $mat->value,
                'label' => $mat->label(),
                'description' => $mat->description()
            ],
            MontureMateriau::cases()
        );

        return $this->json([
            'brands' => $brands,
            'types' => $types,
            'genres' => $genres,
            'formes' => $formes,
            'materiaux' => $materiaux,
            'couleurs' => $couleurs,
        ]);
    }
}
