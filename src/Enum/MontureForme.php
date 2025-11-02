<?php

namespace App\Enum;

enum MontureForme: string
{
    case RECTANGULAIRE = 'rectangulaire';
    case RONDE = 'ronde';
    case OVALE = 'ovale';
    case CARREE = 'carree';
    case AVIATEUR = 'aviateur';
    case PAPILLON = 'papillon';      // Cat-eye
    case CLUBMASTER = 'clubmaster';
    case SPORT = 'sport';
    case GEOMETRIQUE = 'geometrique';
}
