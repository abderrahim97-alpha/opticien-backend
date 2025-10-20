<?php

namespace App\Controller;

use App\Entity\Monture;
use App\Entity\Image;
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
}
