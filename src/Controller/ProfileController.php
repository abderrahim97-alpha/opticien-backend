<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Opticien;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/api/profile', name: 'get_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $response = [
            'id' => $user->getId()->toString(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ];

        // Si c'est un opticien, ajouter les infos spécifiques
        if ($user instanceof Opticien) {
            $response['nom'] = $user->getNom();
            $response['prenom'] = $user->getPrenom();
            $response['telephone'] = $user->getTelephone();
            $response['city'] = $user->getCity();
            $response['status'] = $user->getStatus()->value;
            $response['companyName'] = $user->getCompanyName();
            $response['adresse'] = $user->getAdresse();
            $response['ICE'] = $user->getICE();
            $response['images'] = array_map(
                fn($img) => [
                    'id' => $img->getId(),
                    'imageName' => $img->getImageName(),
                    '@id' => '/api/images/' . $img->getId()
                ],
                $user->getImages()->toArray()
            );
        }

        return $this->json($response);
    }

    #[Route('/api/profile/update', name: 'update_profile', methods: ['PUT', 'POST'])]
    public function updateProfile(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);

        try {
            // Vérifier si l'email est déjà utilisé par un autre utilisateur
            if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
                $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
                if ($existingUser) {
                    return $this->json(['error' => 'Email already in use'], 400);
                }
                $user->setEmail($data['email']);
            }

            // Si c'est un opticien, mettre à jour les infos spécifiques
            if ($user instanceof Opticien) {
                if (isset($data['nom'])) {
                    $user->setNom($data['nom']);
                }
                if (isset($data['prenom'])) {
                    $user->setPrenom($data['prenom']);
                }
                if (isset($data['telephone'])) {
                    $user->setTelephone($data['telephone']);
                }
                if (isset($data['city'])) {
                    $user->setCity($data['city']);
                }
                if (isset($data['adresse'])) {
                    $user->setAdresse($data['adresse']);
                }
                if (isset($data['companyName'])) {
                    $user->setCompanyName($data['companyName']);
                }
                if (isset($data['ICE'])) {
                    $user->setICE($data['ICE']);
                }
            }

            $em->flush();

            return $this->json([
                'message' => 'Profil mis à jour avec succès',
                'user' => [
                    'id' => $user->getId()->toString(),
                    'email' => $user->getEmail(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/profile/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);

        // Validation
        if (empty($data['currentPassword']) || empty($data['newPassword'])) {
            return $this->json(['error' => 'Current password and new password are required'], 400);
        }

        // Vérifier l'ancien mot de passe
        if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
            return $this->json(['error' => 'Current password is incorrect'], 400);
        }

        // Valider la longueur du nouveau mot de passe
        if (strlen($data['newPassword']) < 6) {
            return $this->json(['error' => 'New password must be at least 6 characters'], 400);
        }

        try {
            // Hasher et sauvegarder le nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
            $user->setPassword($hashedPassword);

            $em->flush();

            return $this->json([
                'message' => 'Mot de passe modifié avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors du changement de mot de passe',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
