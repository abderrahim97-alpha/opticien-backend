<?php

namespace App\Controller;

use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api')]
class ContactController extends AbstractController
{
    #[Route('/contact', name: 'api_contact', methods: ['POST'])]
    public function contact(
        Request $request,
        ValidatorInterface $validator,
        EmailService $emailService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validation
        $constraints = new Assert\Collection([
            'name' => [
                new Assert\NotBlank(['message' => 'Le nom est requis']),
                new Assert\Length(['min' => 2, 'max' => 100])
            ],
            'email' => [
                new Assert\NotBlank(['message' => 'L\'email est requis']),
                new Assert\Email(['message' => 'Email invalide'])
            ],
            'phone' => new Assert\Optional([
                new Assert\Regex([
                    'pattern' => '/^[0-9\s\+\-\(\)]+$/',
                    'message' => 'Numéro de téléphone invalide'
                ])
            ]),
            'subject' => [
                new Assert\NotBlank(['message' => 'Le sujet est requis']),
                new Assert\Length(['min' => 3, 'max' => 200])
            ],
            'message' => [
                new Assert\NotBlank(['message' => 'Le message est requis']),
                new Assert\Length(['min' => 10, 'max' => 2000])
            ],
            'userType' => [
                new Assert\NotBlank(['message' => 'Le type d\'utilisateur est requis']),
                new Assert\Choice([
                    'choices' => ['client', 'opticien', 'autre'],
                    'message' => 'Type d\'utilisateur invalide'
                ])
            ]
        ]);

        $violations = $validator->validate($data, $constraints);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $propertyPath = $violation->getPropertyPath();
                // Remove [brackets] from property path
                $propertyPath = trim($propertyPath, '[]');
                $errors[$propertyPath] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], 400);
        }

        try {
            // Send emails using EmailService
            $emailService->sendContactEmail($data);
            $emailService->sendContactConfirmation($data['email'], $data['name']);

            return $this->json([
                'success' => true,
                'message' => 'Votre message a été envoyé avec succès'
            ]);

        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Contact form error: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi du message'
            ], 500);
        }
    }
}
