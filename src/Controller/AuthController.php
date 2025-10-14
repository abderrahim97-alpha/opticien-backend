<?php
namespace App\Controller;

use App\Entity\Opticien;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AuthController extends AbstractController
{
    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function login(Request $request, JWTTokenManagerInterface $jwtManager, UserProviderInterface $userProvider): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $user = $userProvider->loadUserByUsername($email);
        if (!$user || !password_verify($password, $user->getPassword())) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        $token = $jwtManager->create($user);

        return new JsonResponse(['token' => $token]);
    }

    #[Route('/api/register-opticien', name: 'register_opticien', methods: ['POST'])]
    public function registerOpticien(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate required fields
        $requiredFields = ['email', 'password', 'nom', 'prenom'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => "$field is required"], 400);
            }
        }

        // Check if email already exists
        if ($em->getRepository(User::class)->findOneBy(['email' => $data['email']])) {
            return $this->json(['error' => 'Email already in use'], 400);
        }

        // Create Opticien entity
        $opticien = new Opticien();
        $opticien->setEmail($data['email']);
        $opticien->setNom($data['nom']);
        $opticien->setPrenom($data['prenom']);
        $opticien->setTelephone($data['telephone'] ?? null);
        $opticien->setCity($data['city'] ?? null);

        // Hash password
        $hashedPassword = $passwordHasher->hashPassword($opticien, $data['password']);
        $opticien->setPassword($hashedPassword);

        // Assign ROLE_OPTICIEN
        $opticien->setRoles(['ROLE_OPTICIEN']);

        // Persist to database
        $em->persist($opticien);
        $em->flush();

        return $this->json([
            'message' => 'Opticien registered successfully',
            'id' => $opticien->getId()
        ], 201);
    }

}
