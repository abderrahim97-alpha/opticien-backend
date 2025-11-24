<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendPasswordResetEmail(string $to, string $name, string $token): void
    {
        $resetUrl = "http://localhost:3000/reset-password/{$token}";

        $email = (new Email())
            ->from('abdellabdell.007@gmail.com')
            ->to($to)
            ->subject('🔐 Réinitialisation de votre mot de passe')
            ->html("
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h1 style='color: #3b82f6;'>Réinitialisation de mot de passe</h1>
                <p>Bonjour {$name},</p>
                <p>Vous avez demandé à réinitialiser votre mot de passe.</p>
                <div style='background: #dbeafe; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>⚠️ Ce lien est valide pendant 1 heure seulement.</strong></p>
                </div>
                <p>Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>
                <br>
                <a href='{$resetUrl}' style='background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                    Réinitialiser mon mot de passe
                </a>
                <br><br>
                <p style='color: #6b7280; font-size: 14px;'>Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>
                <br>
                <p>Cordialement,<br><strong>L'équipe Optique</strong></p>
            </div>
        ");

        $this->mailer->send($email);
    }

    public function sendAccountCreatedEmail(string $to, string $name): void
    {
        $email = (new Email())
            ->from('abdellabdell.007@gmail.com')
            ->to($to)
            ->subject('Compte créé - En attente de validation')
            ->html("
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h1 style='color: #3b82f6;'>Bienvenue {$name} !</h1>
                    <p>Votre compte opticien a été créé avec succès.</p>
                    <div style='background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <p style='margin: 0;'><strong>⏳ Statut :</strong> En attente de validation</p>
                    </div>
                    <p>Notre équipe va vérifier vos informations dans les plus brefs délais (24-48h).</p>
                    <p>Vous recevrez un email dès que votre compte sera validé.</p>
                    <br>
                    <p>Cordialement,<br><strong>L'équipe Optique</strong></p>
                </div>
            ");

        $this->mailer->send($email);
    }

    public function sendAccountApprovedEmail(string $to, string $name): void
    {
        $email = (new Email())
            ->from('abdellabdell.007@gmail.com')
            ->to($to)
            ->subject('✅ Compte approuvé !')
            ->html("
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h1 style='color: #10b981;'>Félicitations {$name} !</h1>
                    <div style='background: #d1fae5; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <p style='margin: 0;'><strong>✅ Votre compte a été approuvé !</strong></p>
                    </div>
                    <p>Vous pouvez maintenant accéder à toutes les fonctionnalités de la plateforme.</p>
                    <br>
                    <a href='http://localhost:3000/login' style='background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                        Se connecter maintenant
                    </a>
                    <br><br>
                    <p>Cordialement,<br><strong>L'équipe Optique</strong></p>
                </div>
            ");

        $this->mailer->send($email);
    }

    public function sendAccountRejectedEmail(string $to, string $name, string $reason): void
    {
        $email = (new Email())
            ->from('abdellabdell.007@gmail.com')
            ->to($to)
            ->subject('❌ Compte refusé')
            ->html("
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h1 style='color: #ef4444;'>Compte refusé</h1>
                    <p>Bonjour {$name},</p>
                    <p>Malheureusement, nous ne pouvons pas valider votre compte pour la raison suivante :</p>
                    <div style='background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 20px 0;'>
                        <p style='margin: 0; color: #991b1b;'><strong>Raison :</strong> {$reason}</p>
                    </div>
                    <p>Si vous pensez qu'il s'agit d'une erreur, n'hésitez pas à nous contacter à <a href='mailto:support@optique.ma'>support@optique.ma</a></p>
                    <br>
                    <p>Cordialement,<br><strong>L'équipe Optique</strong></p>
                </div>
            ");

        $this->mailer->send($email);
    }
    public function sendCommandeCreatedToAcheteur(string $to, string $name, int $commandeId): void
    {
        $email = (new Email())
            ->from('abdellabdell.007@gmail.com')
            ->to($to)
            ->subject('🛒 Commande créée - En attente de validation')
            ->html("
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h1 style='color: #3b82f6;'>Commande #{$commandeId} créée</h1>
                <p>Bonjour {$name},</p>
                <p>Votre commande a été créée avec succès !</p>
                <div style='background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>⏳ Statut :</strong> En attente de vérification physique</p>
                </div>
                <p>Notre équipe va vérifier l'authenticité des montures commandées.</p>
                <p>Vous recevrez un email dès validation.</p>
                <br>
                <p>Cordialement,<br><strong>L'équipe Optique</strong></p>
            </div>
        ");

        $this->mailer->send($email);
    }

    public function sendCommandeCreatedToAdmin(int $commandeId, string $acheteurName): void
    {
        $email = (new Email())
            ->from('abdellabdell.007@gmail.com')
            ->to('abderrahim.abd1997@gmail.com') // Ton email admin
            ->subject("🔔 Nouvelle commande #{$commandeId} à valider")
            ->html("
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h1 style='color: #f59e0b;'>Nouvelle commande à vérifier</h1>
                <p><strong>Commande #{$commandeId}</strong></p>
                <p><strong>Acheteur :</strong> {$acheteurName}</p>
                <div style='background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>⚠️ Action requise :</strong> Vérification physique des montures</p>
                </div>
                <br>
                <a href='http://localhost:3000/admin/commandes/{$commandeId}' style='background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>
                    Voir la commande
                </a>
            </div>
        ");

        $this->mailer->send($email);
    }

    public function sendCommandeValidatedToAcheteur(string $to, string $name, int $commandeId): void
    {
        $email = (new Email())
            ->from('abdellabdell.007@gmail.com')
            ->to($to)
            ->subject('✅ Commande validée - Expédition en cours')
            ->html("
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h1 style='color: #10b981;'>Commande #{$commandeId} validée !</h1>
                <p>Bonjour {$name},</p>
                <div style='background: #d1fae5; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>✅ Votre commande a été validée !</strong></p>
                </div>
                <p>Les montures ont été vérifiées et sont en cours d'expédition vers votre magasin.</p>
                <br>
                <p>Cordialement,<br><strong>L'équipe Optique</strong></p>
            </div>
        ");

        $this->mailer->send($email);
    }

    public function sendCommandeRefusedToAcheteur(string $to, string $name, int $commandeId, string $raison): void
    {
        $email = (new Email())
            ->from('abdellabdell.007@gmail.com')
            ->to($to)
            ->subject('❌ Commande refusée')
            ->html("
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h1 style='color: #ef4444;'>Commande #{$commandeId} refusée</h1>
                <p>Bonjour {$name},</p>
                <p>Malheureusement, votre commande n'a pas pu être validée :</p>
                <div style='background: #fee2e2; padding: 15px; border-left: 4px solid #ef4444; margin: 20px 0;'>
                    <p style='margin: 0; color: #991b1b;'><strong>Raison :</strong> {$raison}</p>
                </div>
                <p>Les montures seront retournées aux vendeurs et votre stock sera restauré.</p>
                <br>
                <p>Cordialement,<br><strong>L'équipe Optique</strong></p>
            </div>
        ");

        $this->mailer->send($email);
    }

    public function sendCommandeRefusedToVendeur(string $to, string $name, int $commandeId): void
    {
        $email = (new Email())
            ->from('abdellabdell.007@gmail.com')
            ->to($to)
            ->subject('↩️ Retour de montures - Commande refusée')
            ->html("
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h1 style='color: #f59e0b;'>Retour de montures</h1>
                <p>Bonjour {$name},</p>
                <p>Une commande concernant vos montures a été refusée après vérification.</p>
                <div style='background: #fef3c7; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0;'><strong>↩️ Les montures vous seront retournées</strong></p>
                </div>
                <p>Votre stock a été restauré automatiquement.</p>
                <br>
                <p>Cordialement,<br><strong>L'équipe Optique</strong></p>
            </div>
        ");

        $this->mailer->send($email);
    }
}
