<?php

namespace App\Enum;

enum MontureMateriau: string
{
    case ACETATE = 'acetate';
    case METAL = 'metal';
    case PLASTIQUE = 'plastique';
    case TITANE = 'titane';
    case TR90 = 'tr90';
    case ALUMINIUM = 'aluminium';
    case ACIER_INOXYDABLE = 'acier_inoxydable';
    case FIBRE_CARBONE = 'fibre_carbone';
    case BOIS = 'bois';
    case CORNE = 'corne';
    case METAL_PLASTIQUE = 'metal_plastique';

    /**
     * Obtenir le label français pour affichage
     */
    public function label(): string
    {
        return match($this) {
            self::ACETATE => 'Acétate',
            self::METAL => 'Métal',
            self::PLASTIQUE => 'Plastique',
            self::TITANE => 'Titane',
            self::TR90 => 'TR90',
            self::ALUMINIUM => 'Aluminium',
            self::ACIER_INOXYDABLE => 'Acier inoxydable',
            self::FIBRE_CARBONE => 'Fibre de carbone',
            self::BOIS => 'Bois',
            self::CORNE => 'Corne',
            self::METAL_PLASTIQUE => 'Métal/Plastique',
        };
    }

    /**
     * Description du matériau
     */
    public function description(): string
    {
        return match($this) {
            self::ACETATE => 'Léger, confortable, disponible en plusieurs couleurs',
            self::METAL => 'Fin, élégant, durable',
            self::PLASTIQUE => 'Léger, économique, résistant aux chocs',
            self::TITANE => 'Ultra-léger, hypoallergénique, très résistant',
            self::TR90 => 'Flexible, incassable, idéal pour le sport',
            self::ALUMINIUM => 'Moderne, léger, résistant à la corrosion',
            self::ACIER_INOXYDABLE => 'Solide, durable, style classique',
            self::FIBRE_CARBONE => 'Ultra-léger, haute performance, design moderne',
            self::BOIS => 'Écologique, unique, look naturel',
            self::CORNE => 'Matériau noble, artisanal, pièce unique',
            self::METAL_PLASTIQUE => 'Combinaison alliant légèreté et solidité',
        };
    }
}
