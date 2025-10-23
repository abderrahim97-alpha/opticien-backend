<?php

namespace App\Controller;

use App\Entity\Monture;
use App\Entity\Image;
use App\Entity\User;
use App\Enum\MontureStatus;
use App\Repository\MontureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MontureController extends AbstractController
{
    #[Route('/api/montures-upload', name: 'create_monture_with_images', methods: ['POST'])]
    public function createMontureWithImages(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        // Check if user is authenticated and has permission
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        if (!$this->isGranted('ROLE_OPTICIEN') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Only opticiens and admins can create montures'], 403);
        }

        // Ensure user is a User entity (not just UserInterface)
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'Invalid user type'], 401);
        }

        // Get form-data fields
        $name = $request->request->get('name');
        $description = $request->request->get('description');
        $price = $request->request->get('price');
        $brand = $request->request->get('brand');
        $stock = $request->request->get('stock');

        // Validate required fields
        $requiredFields = ['name', 'price'];
        foreach ($requiredFields as $field) {
            if (empty($$field)) {
                return $this->json(['error' => "$field is required"], 400);
            }
        }

        // Validate price is numeric
        if (!is_numeric($price)) {
            return $this->json(['error' => 'Price must be a number'], 400);
        }

        // Create Monture
        $monture = new Monture();
        $monture->setName($name)
            ->setDescription($description)
            ->setPrice((float)$price)
            ->setBrand($brand)
            ->setStock($stock ? (int)$stock : 0)
            ->setOwner($user);

        // Définir le statut selon le rôle
        if ($this->isGranted('ROLE_ADMIN')) {
            $monture->setStatus(MontureStatus::APPROVED); // Admin: approuvé directement
        } else {
            $monture->setStatus(MontureStatus::PENDING); // Opticien: en attente
        }

        // Handle uploaded images (multiple)
        /** @var UploadedFile[] $files */
        $files = $request->files->get('images'); // key name: "images[]"

        if ($files) {
            foreach ($files as $file) {
                if ($file) {
                    $image = new Image();
                    $image->setImageFile($file);
                    $image->setMonture($monture);
                    $monture->addImage($image);
                    $em->persist($image);
                }
            }
        }

        // Persist monture
        $em->persist($monture);
        $em->flush();

        return $this->json([
            'message' => 'Monture created successfully',
            'id' => $monture->getId(),
            'name' => $monture->getName(),
            'price' => $monture->getPrice(),
            'images' => array_map(fn($img) => $img->getImageName(), $monture->getImages()->toArray())
        ], 201);
    }

    #[Route('/api/montures/{id}/edit', name: 'edit_monture', methods: ['POST'])]
    public function editMonture(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        MontureRepository $montureRepo
    ): JsonResponse {
        try {
            $user = $this->getUser();
            if (!$user instanceof \App\Entity\User) {
                return $this->json(['error' => 'Unauthorized'], 401);
            }

            $monture = $montureRepo->find($id);
            if (!$monture) {
                return $this->json(['error' => 'Monture not found'], 404);
            }

            // Vérification du propriétaire
            if ($monture->getOwner()->getId()->toString() !== $user->getId()->toString()) {
                return $this->json(['error' => 'Access denied'], 403);
            }

            // ========== AJOUTEZ CETTE PARTIE ==========
            // Suppression des images existantes
            $imagesToDelete = $request->request->all('imagesToDelete');
            if (!empty($imagesToDelete)) {
                foreach ($imagesToDelete as $imageIri) {
                    // Extraire l'ID de l'IRI (format: /api/images/123)
                    if (preg_match('/\/images\/(\d+)/', $imageIri, $matches)) {
                        $imageId = (int)$matches[1];

                        // Trouver et supprimer l'image
                        foreach ($monture->getImages() as $img) {
                            if ($img->getId() === $imageId) {
                                $monture->removeImage($img);
                                $em->remove($img);
                                break;
                            }
                        }
                    }
                }
            }
            // ========== FIN DE L'AJOUT ==========

            // Récupération des champs
            $name = $request->request->get('name');
            $description = $request->request->get('description');
            $price = $request->request->get('price');
            $brand = $request->request->get('brand');
            $stock = $request->request->get('stock');

            // Mise à jour des champs
            if ($name) {
                $monture->setName($name);
            }
            if ($description !== null) {
                $monture->setDescription($description);
            }
            if ($brand !== null) {
                $monture->setBrand($brand);
            }
            if ($price !== null && is_numeric($price)) {
                $monture->setPrice((float)$price);
            }
            if ($stock !== null && is_numeric($stock)) {
                $monture->setStock((int)$stock);
            }

            // Gestion des nouvelles images
            $files = $request->files->get('images');
            if ($files && is_array($files)) {
                foreach ($files as $file) {
                    if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                        $image = new \App\Entity\Image();
                        $image->setImageFile($file);
                        $image->setMonture($monture);
                        $monture->addImage($image);
                        $em->persist($image);
                    }
                }
            }

            $em->flush(); // VichUploader supprimera automatiquement les fichiers physiques

            return $this->json([
                'message' => 'Monture mise à jour avec succès',
                'monture' => [
                    'id' => $monture->getId(),
                    'name' => $monture->getName(),
                    'price' => $monture->getPrice(),
                    'brand' => $monture->getBrand(),
                    'stock' => $monture->getStock(),
                    'description' => $monture->getDescription(),
                    'updatedAt' => $monture->getUpdatedAt()?->format('Y-m-d H:i:s'),
                    'imagesCount' => $monture->getImages()->count(),
                    'images' => array_map(
                        fn($img) => [
                            'id' => $img->getId(),
                            'imageName' => $img->getImageName()
                        ],
                        $monture->getImages()->toArray()
                    )
                ]
            ], 200);

        } catch (\Exception $e) {
            error_log('Error in editMonture: ' . $e->getMessage());

            return $this->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/my-montures', name: 'my_montures', methods: ['GET'])]
    public function getMyMontures(MontureRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            // Admin voit toutes les montures
            $montures = $repo->findAll();
        } else {
            // Opticien voit seulement ses montures
            $montures = $repo->findBy(['owner' => $user]);
        }

        return $this->json(['member' => $montures], 200, [], ['groups' => ['monture:read']]);
    }

    #[Route('/api/montures/{id}/approve', name: 'approve_monture', methods: ['PATCH'])]
    public function approveMonture(int $id, MontureRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $monture = $repo->find($id);
        if (!$monture) {
            return $this->json(['error' => 'Monture not found'], 404);
        }

        $monture->setStatus(MontureStatus::APPROVED);
        $em->flush();

        return $this->json(['message' => 'Monture approved'], 200);
    }

    #[Route('/api/montures/{id}/reject', name: 'reject_monture', methods: ['PATCH'])]
    public function rejectMonture(int $id, MontureRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $monture = $repo->find($id);
        if (!$monture) {
            return $this->json(['error' => 'Monture not found'], 404);
        }

        $monture->setStatus(MontureStatus::REJECTED);
        $em->flush();

        return $this->json(['message' => 'Monture rejected'], 200);
    }

    #[Route('/api/me', name: 'current_user', methods: ['GET'])]
    public function getCurrentUser(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        return $this->json([
            'id' => $user->getId()->toString(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ]);
    }
}

