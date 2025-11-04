<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CommandeItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource]
#[ORM\Entity(repositoryClass: CommandeItemRepository::class)]
class CommandeItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['commande:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Commande $commande = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['commande:read', 'commande:write'])]
    private ?Monture $monture = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['commande:read'])]
    private ?User $vendeur = null;

    #[ORM\Column]
    #[Groups(['commande:read', 'commande:write'])]
    private ?int $quantite = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['commande:read'])]
    private ?string $prixUnitaire = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['commande:read'])]
    private ?string $sousTotal = '0.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;
        return $this;
    }

    public function getMonture(): ?Monture
    {
        return $this->monture;
    }

    public function setMonture(?Monture $monture): static
    {
        $this->monture = $monture;

        // Copier le vendeur et le prix depuis la monture
        if ($monture) {
            $this->vendeur = $monture->getOwner();
            $this->prixUnitaire = (string) $monture->getPrice();
            $this->calculateSousTotal();
        }

        return $this;
    }

    public function getVendeur(): ?User
    {
        return $this->vendeur;
    }

    public function setVendeur(?User $vendeur): static
    {
        $this->vendeur = $vendeur;
        return $this;
    }

    #[Groups(['commande:read'])]
    public function getVendeurName(): ?string
    {
        if ($this->vendeur instanceof Opticien) {
            return $this->vendeur->getNom() . ' ' . $this->vendeur->getPrenom();
        }

        return $this->vendeur?->getEmail();
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;
        $this->calculateSousTotal();
        return $this;
    }

    public function getPrixUnitaire(): ?string
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(string $prixUnitaire): static
    {
        $this->prixUnitaire = $prixUnitaire;
        $this->calculateSousTotal();
        return $this;
    }

    public function getSousTotal(): ?string
    {
        return $this->sousTotal;
    }

    public function setSousTotal(string $sousTotal): static
    {
        $this->sousTotal = $sousTotal;
        return $this;
    }

    /**
     * Calculer le sous-total automatiquement
     */
    private function calculateSousTotal(): void
    {
        if ($this->prixUnitaire && $this->quantite) {
            $this->sousTotal = (string) ((float) $this->prixUnitaire * $this->quantite);
        }
    }
}
