<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Enum\MontureMateriau;
use App\Enum\MontureStatus;
use App\Enum\MontureType;
use App\Enum\MontureGenre;
use App\Enum\MontureForme;
use App\Repository\MontureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['monture:read']]),
        new GetCollection(normalizationContext: ['groups' => ['monture:read']]),
        new Post(denormalizationContext: ['groups' => ['monture:write']]),
        new Delete(
                    security: "is_granted('ROLE_OPTICIEN') or is_granted('ROLE_ADMIN')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['monture:write']],
            security: "is_granted('ROLE_OPTICIEN') or is_granted('ROLE_ADMIN')"
        )
        ]
)]
#[ORM\Entity(repositoryClass: MontureRepository::class)]
class Monture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private ?string $brand = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private ?int $stock = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: MontureStatus::class)]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private MontureStatus $status = MontureStatus::PENDING;

    #[ORM\Column]
    #[Groups(['monture:read', 'commande:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['monture:read', 'commande:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'montures')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    #[MaxDepth(1)]
    private ?User $owner = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: MontureType::class)]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private ?MontureType $type = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: MontureGenre::class)]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private ?MontureGenre $genre = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: MontureForme::class)]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private ?MontureForme $forme = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private ?string $couleur = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: MontureMateriau::class)]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private ?MontureMateriau $materiau = null;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'monture', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['monture:read', 'monture:write', 'commande:read'])]
    private Collection $images;

    public function __construct()
    {
        $this->images = new ArrayCollection();
        // Automatically set createdAt when a new Monture is created
        $this->createdAt = new \DateTimeImmutable();
        // Initialize updatedAt as null; will be set on first update
        $this->updatedAt = null;
        // Par défaut en attente
        $this->status = MontureStatus::PENDING;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->updateTimestamp();

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updateTimestamp();

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        $this->updateTimestamp();

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;
        $this->updateTimestamp();

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;
        $this->updateTimestamp();

        return $this;
    }

    public function getStatus(): MontureStatus
    {
        return $this->status;
    }

    public function setStatus(MontureStatus $status): static
    {
        $this->status = $status;
        $this->updateTimestamp();
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Automatically set updatedAt when an attribute changes
    private function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        $this->updateTimestamp();

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setMonture($this);
            $this->updateTimestamp();
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getMonture() === $this) {
                $image->setMonture(null);
                $this->updateTimestamp();
            }
        }

        return $this;
    }
    /**
     * Décrémenter le stock (lors d'une commande)
     *
     * @throws \Exception Si stock insuffisant
     */
    public function decrementStock(int $quantity): void
    {
        if ($this->stock === null || $this->stock < $quantity) {
            throw new \Exception(
                "Stock insuffisant pour la monture '{$this->name}'. " .
                "Stock disponible: {$this->stock}, demandé: {$quantity}"
            );
        }

        $this->stock -= $quantity;
        $this->updateTimestamp();
    }

    /**
     * Incrémenter le stock (lors d'un retour/refus de commande)
     */
    public function incrementStock(int $quantity): void
    {
        if ($this->stock === null) {
            $this->stock = 0;
        }

        $this->stock += $quantity;
        $this->updateTimestamp();
    }

    /**
     * Vérifier si le stock est suffisant
     */
    public function hasEnoughStock(int $quantity): bool
    {
        return $this->stock !== null && $this->stock >= $quantity;
    }

    public function getType(): ?MontureType
    {
        return $this->type;
    }

    public function setType(?MontureType $type): static
    {
        $this->type = $type;
        $this->updateTimestamp();
        return $this;
    }

    public function getGenre(): ?MontureGenre
    {
        return $this->genre;
    }

    public function setGenre(?MontureGenre $genre): static
    {
        $this->genre = $genre;
        $this->updateTimestamp();
        return $this;
    }

    public function getForme(): ?MontureForme
    {
        return $this->forme;
    }

    public function setForme(?MontureForme $forme): static
    {
        $this->forme = $forme;
        $this->updateTimestamp();
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;
        $this->updateTimestamp();
        return $this;
    }

    public function getMateriau(): ?MontureMateriau
    {
        return $this->materiau;
    }

    public function setMateriau(?MontureMateriau $materiau): static
    {
        $this->materiau = $materiau;
        $this->updateTimestamp();
        return $this;
    }
}
