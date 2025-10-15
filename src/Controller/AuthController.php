<?php
namespace App\Controller;

use App\Entity\Opticien;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
    ): JsonResponse {
        // Get form-data fields
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $nom = $request->request->get('nom');
        $prenom = $request->request->get('prenom');
        $telephone = $request->request->get('telephone');
        $city = $request->request->get('city');
        $adresse = $request->request->get('adresse');
        $companyName = $request->request->get('companyName');
        $ICE = $request->request->get('ICE');

        // Validate required fields
        $requiredFields = ['email', 'password', 'nom', 'prenom'];
        foreach ($requiredFields as $field) {
            if (empty($$field)) {
                return $this->json(['error' => "$field is required"], 400);
            }
        }

        // Check if email already exists
        if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Email already in use'], 400);
        }

        // Create Opticien
        $opticien = new Opticien();
        $opticien->setEmail($email)
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setTelephone($telephone)
            ->setCity($city)
            ->setAdresse($adresse)
            ->setCompanyName($companyName)
            ->setICE($ICE)
            ->setRoles(['ROLE_OPTICIEN']);

        // Hash password
        $hashedPassword = $passwordHasher->hashPassword($opticien, $password);
        $opticien->setPassword($hashedPassword);

        // Handle uploaded images (multiple)
        /** @var UploadedFile[] $files */
        $files = $request->files->get('images'); // key name: "images[]"

        if ($files) {
            foreach ($files as $file) {
                if ($file) {
                    $image = new \App\Entity\Image();
                    $image->setImageFile($file);
                    $image->setOpticien($opticien);
                    $opticien->addImage($image);
                    $em->persist($image);
                }
            }
        }

        // Persist opticien
        $em->persist($opticien);
        $em->flush();

        return $this->json([
            'message' => 'Opticien registered successfully',
            'id' => $opticien->getId(),
            'images' => array_map(fn($img) => $img->getImageName(), $opticien->getImages()->toArray())
        ], 201);
    }

}
