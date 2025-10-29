<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class PasswordResetController extends AbstractController
{
    #[Route('/api/password/forgot', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        EmailService $emailService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email'])) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        // Pour des raisons de sécurité, on retourne toujours le même message
        // même si l'utilisateur n'existe pas
        if (!$user) {
            return $this->json([
                'message' => 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation'
            ]);
        }

        try {
            // Générer un token unique
            $resetToken = bin2hex(random_bytes(32));
            $resetTokenExpiry = new \DateTime('+1 hour'); // Token valide 1 heure

            // Sauvegarder le token dans l'utilisateur
            $user->setResetToken($resetToken);
            $user->setResetTokenExpiry($resetTokenExpiry);

            $em->flush();

            // Envoyer l'email
            $emailService->sendPasswordResetEmail(
                $user->getEmail(),
                $user instanceof \App\Entity\Opticien ? $user->getPrenom() : 'Utilisateur',
                $resetToken
            );

            return $this->json([
                'message' => 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de l\'envoi de l\'email',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/password/verify-token/{token}', name: 'verify_reset_token', methods: ['GET'])]
    public function verifyToken(
        string $token,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            return $this->json(['error' => 'Token invalide'], 400);
        }

        // Vérifier si le token n'a pas expiré
        if ($user->getResetTokenExpiry() < new \DateTime()) {
            return $this->json(['error' => 'Token expiré'], 400);
        }

        return $this->json([
            'message' => 'Token valide',
            'email' => $user->getEmail()
        ]);
    }

    #[Route('/api/password/reset', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['token']) || empty($data['password'])) {
            return $this->json(['error' => 'Token et mot de passe requis'], 400);
        }

        // Valider la longueur du mot de passe
        if (strlen($data['password']) < 6) {
            return $this->json(['error' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $data['token']]);

        if (!$user) {
            return $this->json(['error' => 'Token invalide'], 400);
        }

        // Vérifier si le token n'a pas expiré
        if ($user->getResetTokenExpiry() < new \DateTime()) {
            return $this->json(['error' => 'Token expiré'], 400);
        }

        try {
            // Hasher le nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Supprimer le token
            $user->setResetToken(null);
            $user->setResetTokenExpiry(null);

            $em->flush();

            return $this->json([
                'message' => 'Mot de passe réinitialisé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la réinitialisation',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
