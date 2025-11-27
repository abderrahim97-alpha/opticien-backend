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

    public function sendContactEmail(array $data): void
    {
        $userTypeLabels = [
            'client' => '👤 Client',
            'opticien' => '👓 Opticien',
            'autre' => '🔹 Autre'
        ];

        $userTypeLabel = $userTypeLabels[$data['userType']] ?? $data['userType'];

        $email = (new Email())
            ->from('abdellabdell.007@gmail.com')
            ->to('abderrahim.abd1997@gmail.com') // Your admin email
            ->replyTo($data['email']) // ⭐ This allows you to reply directly
            ->subject('📧 Contact Marketplace - ' . $data['subject'])
            ->html("
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f4f4f4; padding: 20px;'>
                <div style='background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;'>
                        <h1 style='margin: 0;'>📧 Nouveau message de contact</h1>
                        <p style='margin: 10px 0 0 0;'>Reçu via le formulaire de contact</p>
                    </div>
                    <div style='padding: 30px;'>
                        <div style='margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px;'>
                            <strong style='color: #667eea;'>👤 Nom complet:</strong><br>
                            <span style='color: #333;'>{$data['name']}</span>
                        </div>

                        <div style='margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px;'>
                            <strong style='color: #667eea;'>📧 Email:</strong><br>
                            <a href='mailto:{$data['email']}' style='color: #667eea;'>{$data['email']}</a>
                        </div>

                        <div style='margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px;'>
                            <strong style='color: #667eea;'>📱 Téléphone:</strong><br>
                            <span style='color: #333;'>{$data['phone']}</span>
                        </div>

                        <div style='margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px;'>
                            <strong style='color: #667eea;'>👥 Type d'utilisateur:</strong><br>
                            <span style='display: inline-block; padding: 5px 15px; background: #667eea; color: white; border-radius: 20px; font-size: 12px;'>{$userTypeLabel}</span>
                        </div>

                        <div style='margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #667eea; border-radius: 4px;'>
                            <strong style='color: #667eea;'>📋 Sujet:</strong><br>
                            <span style='color: #333;'>{$data['subject']}</span>
                        </div>

                        <div style='margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 5px; border: 1px solid #e0e0e0;'>
                            <strong style='color: #667eea;'>💬 Message:</strong>
                            <div style='margin-top: 10px; color: #333;'>" . nl2br(htmlspecialchars($data['message'])) . "</div>
                        </div>

                        <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; color: #666; font-size: 14px;'>
                            <p><strong>💡 Astuce:</strong> Cliquez sur \"Répondre\" pour envoyer votre réponse directement à {$data['email']}</p>
                        </div>
                    </div>
                </div>
            </div>
        ");

        $this->mailer->send($email);
    }

    /**
     * Send contact confirmation email to user
     */
    public function sendContactConfirmation(string $to, string $name): void
    {
        $email = (new Email())
            ->from('abdellabdell.007@gmail.com')
            ->to($to)
            ->subject('✅ Confirmation de votre message - Opti-Marketplace')
            ->html("
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f4f4f4; padding: 20px;'>
                <div style='background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; text-align: center;'>
                        <div style='font-size: 64px; margin-bottom: 15px;'>✅</div>
                        <h1 style='margin: 0;'>Message bien reçu !</h1>
                        <p style='margin: 10px 0 0 0;'>Nous vous répondrons très bientôt</p>
                    </div>

                    <div style='padding: 40px 30px;'>
                        <p>Bonjour <strong>{$name}</strong>,</p>

                        <p>Nous vous remercions d'avoir pris contact avec nous via notre formulaire de contact.</p>

                        <div style='background: #f0f7ff; border-left: 4px solid #667eea; padding: 20px; margin: 25px 0; border-radius: 4px;'>
                            <p style='margin: 0;'><strong>✨ Votre message a bien été enregistré</strong></p>
                            <p style='margin: 10px 0 0 0; color: #666;'>Notre équipe l'examinera avec attention et vous répondra dans les meilleurs délais.</p>
                        </div>

                        <p><strong>Délai de réponse habituel :</strong> 24-48 heures ouvrées</p>

                        <p>Si votre demande est urgente, n'hésitez pas à nous contacter directement :</p>

                        <div style='background: #fff7f0; border-left: 4px solid #ff9800; padding: 20px; margin: 25px 0; border-radius: 4px;'>
                            <p style='margin: 0;'><strong>📞 Besoin d'une réponse immédiate ?</strong></p>
                            <div style='margin-top: 15px;'>
                                <p style='margin: 5px 0;'>📧 Email : <a href='mailto:contact@opti-maroc.com' style='color: #667eea;'>contact@opti-maroc.com</a></p>
                                <p style='margin: 5px 0;'>📱 Téléphone : <a href='tel:+2125XXXXXXXX' style='color: #667eea;'>+212 5XX-XXXXXX</a></p>
                            </div>
                        </div>

                        <p>Nous sommes impatients d'échanger avec vous !</p>

                        <p style='margin-top: 30px;'>
                            Cordialement,<br>
                            <strong>L'équipe Opti-Marketplace</strong>
                        </p>
                    </div>

                    <div style='text-align: center; padding: 30px; background: #f9f9f9; border-top: 1px solid #e0e0e0; color: #666; font-size: 14px;'>
                        <p><strong>Opti-Marketplace Maroc</strong></p>
                        <p>Oujda, Maroc</p>
                        <p>📧 <a href='mailto:contact@opti-maroc.com' style='color: #667eea;'>contact@opti-maroc.com</a> | 📱 +212 5XX-XXXXXX</p>
                        <p style='margin-top: 20px; font-size: 12px; color: #999;'>
                            Vous recevez cet email en confirmation de votre demande de contact.<br>
                            Merci de ne pas répondre directement à cet email.
                        </p>
                    </div>
                </div>
            </div>
        ");

        $this->mailer->send($email);
    }
}
