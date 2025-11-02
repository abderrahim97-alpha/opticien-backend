<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Enum\CommandeStatus;
use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['commande:read']],
            security: "is_granted('ROLE_OPTICIEN') or is_granted('ROLE_ADMIN')"
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['commande:read']],
            security: "is_granted('ROLE_OPTICIEN') or is_granted('ROLE_ADMIN')"
        ),
        new Post(
            denormalizationContext: ['groups' => ['commande:write']],
            security: "is_granted('ROLE_OPTICIEN')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['commande:update']],
            security: "is_granted('ROLE_ADMIN')"
        )
    ]
)]
#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['commande:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Opticien::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['commande:read', 'commande:write'])]
    private ?Opticien $acheteur = null;

    #[ORM\Column(type: 'string', enumType: CommandeStatus::class)]
    #[Groups(['commande:read', 'commande:update'])]
    private CommandeStatus $status = CommandeStatus::PENDING;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['commande:read'])]
    private ?string $totalPrice = '0.00';

    #[ORM\Column]
    #[Groups(['commande:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['commande:read'])]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['commande:read', 'commande:update'])]
    private ?string $noteAdmin = null;

    /**
     * @var Collection<int, CommandeItem>
     */
    #[ORM\OneToMany(targetEntity: CommandeItem::class, mappedBy: 'commande', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['commande:read', 'commande:write'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = CommandeStatus::PENDING;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAcheteur(): ?Opticien
    {
        return $this->acheteur;
    }

    public function setAcheteur(?Opticien $acheteur): static
    {
        $this->acheteur = $acheteur;
        return $this;
    }

    public function getStatus(): CommandeStatus
    {
        return $this->status;
    }

    public function setStatus(CommandeStatus $status): static
    {
        $this->status = $status;

        // Définir validatedAt si la commande est validée
        if ($status === CommandeStatus::VALIDATED && $this->validatedAt === null) {
            $this->validatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    public function getNoteAdmin(): ?string
    {
        return $this->noteAdmin;
    }

    public function setNoteAdmin(?string $noteAdmin): static
    {
        $this->noteAdmin = $noteAdmin;
        return $this;
    }

    /**
     * @return Collection<int, CommandeItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CommandeItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCommande($this);
        }
        return $this;
    }

    public function removeItem(CommandeItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getCommande() === $this) {
                $item->setCommande(null);
            }
        }
        return $this;
    }

    /**
     * Calculer le prix total à partir des items
     */
    public function calculateTotalPrice(): void
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += (float) $item->getSousTotal();
        }
        $this->totalPrice = (string) $total;
    }
}
