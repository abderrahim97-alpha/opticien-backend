<?php

namespace App\Enum;

enum MontureStatus: string
{
    case PENDING = 'pending';      // En attente de validation
    case APPROVED = 'approved';    // Approuvé par l'admin
    case REJECTED = 'rejected';    // Rejeté par l'admin
}
