<?php

namespace App\Enum;

enum CommandeStatus: string
{
    case PENDING = 'pending';           // En attente vérification admin
    case VALIDATED = 'validated';       // Validée par admin → envoi à acheteur
    case REFUSED = 'refused';           // Refusée → retour vendeur
    case COMPLETED = 'completed';       // Transaction terminée
    case CANCELLED = 'cancelled';       // Annulée
}
