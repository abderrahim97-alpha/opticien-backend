<?php
namespace App\Controller;

use App\Entity\Image;
use App\Entity\Opticien;
use App\Entity\User;
use App\Enum\OpticienStatus;
use App\Service\EmailService;
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
        EntityManagerInterface $em,
        EmailService $emailService
    ): JsonResponse {

        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $nom = $request->request->get('nom');
        $prenom = $request->request->get('prenom');
        $telephone = $request->request->get('telephone');
        $city = $request->request->get('city');
        $adresse = $request->request->get('adresse');
        $companyName = $request->request->get('companyName');
        $ICE = $request->request->get('ICE');

        // --- Validation ---
        $requiredFields = ['email', 'password', 'nom', 'prenom'];
        foreach ($requiredFields as $field) {
            if (empty($$field)) {
                return $this->json(['error' => "$field is required"], 400);
            }
        }

        if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Email already in use'], 400);
        }

        // --- Création de l'opticien ---
        $opticien = new Opticien();
        $opticien->setEmail($email)
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setTelephone($telephone)
            ->setCity($city)
            ->setAdresse($adresse)
            ->setCompanyName($companyName)
            ->setICE($ICE)
            ->setRoles(['ROLE_OPTICIEN'])
            ->setStatus(OpticienStatus::PENDING);

        $hashedPassword = $passwordHasher->hashPassword($opticien, $password);
        $opticien->setPassword($hashedPassword);

        // --- Persist de base ---
        $em->persist($opticien);
        $em->flush(); // ⚡ flush ici pour obtenir un ID pour le lien opticien-image

        // --- Gestion des images ---
        /** @var UploadedFile[] $files */
        $files = $request->files->get('images');

        if ($files && is_iterable($files)) {
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $image = new Image();
                    $image->setImageFile($file); // VichUploader prendra le relais
                    $image->setOpticien($opticien);
                    $opticien->addImage($image);

                    // Persiste l'image (elle sera uploadée à flush)
                    $em->persist($image);
                }
            }
        }

        $em->flush(); // ⚡ ici VichUploaderBundle déclenche les events et remplit imageName

        try {
            $emailService->sendAccountCreatedEmail(
                $opticien->getEmail(),
                $opticien->getPrenom() . ' ' . $opticien->getNom()
            );
        } catch (\Exception $e) {
            error_log('Email send error: ' . $e->getMessage());
        }

        // --- Réponse ---
        return $this->json([
            'message' => 'Opticien enregistré avec succès. Un email de confirmation a été envoyé.',
            'id' => $opticien->getId(),
            'status' => $opticien->getStatus()->value,
            'images' => array_map(fn($img) => $img->getImageName(), $opticien->getImages()->toArray())
        ], 201);
    }


}
