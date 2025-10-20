<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create a new admin user',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Ask for email
        $email = $io->ask('Email address', null, function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Email cannot be empty');
            }
            if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Invalid email format');
            }
            return $answer;
        });

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error('A user with this email already exists!');
            return Command::FAILURE;
        }

        // Ask for password (hidden input)
        $helper = $this->getHelper('question');
        $question = new Question('Password: ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $question->setValidator(function ($answer) {
            if (empty($answer)) {
                throw new \RuntimeException('Password cannot be empty');
            }
            if (strlen($answer) < 6) {
                throw new \RuntimeException('Password must be at least 6 characters long');
            }
            return $answer;
        });

        $password = $helper->ask($input, $output, $question);

        // Confirm password
        $confirmQuestion = new Question('Confirm password: ');
        $confirmQuestion->setHidden(true);
        $confirmQuestion->setHiddenFallback(false);
        $confirmPassword = $helper->ask($input, $output, $confirmQuestion);

        if ($password !== $confirmPassword) {
            $io->error('Passwords do not match!');
            return Command::FAILURE;
        }

        // Create admin user
        $admin = new User();
        $admin->setEmail($email);
        $admin->setRoles(['ROLE_ADMIN']);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($admin, $password);
        $admin->setPassword($hashedPassword);

        // Persist to database
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(sprintf('Admin user "%s" has been created successfully!', $email));

        return Command::SUCCESS;
    }
}
