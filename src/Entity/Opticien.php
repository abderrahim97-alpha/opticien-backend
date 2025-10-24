<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\OpticienRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['opticien:read']]),
        new GetCollection(normalizationContext: ['groups' => ['opticien:read']]),
        new Post(denormalizationContext: ['groups' => ['opticien:write']])
    ]
)]
#[ORM\Entity(repositoryClass: OpticienRepository::class)]
class Opticien extends User
{

    #[ORM\Column(length: 255)]
    #[Groups(['opticien:read', 'opticien:write', 'image:read', 'monture:read'])]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(['opticien:read', 'opticien:write', 'image:read', 'monture:read'])]
    private ?string $prenom = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['opticien:read', 'opticien:write'])]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['opticien:read', 'opticien:write'])]
    private ?string $city = null;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'opticien', orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[Groups(['opticien:read'])]
    private Collection $images;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['opticien:read', 'opticien:write'])]
    private ?string $adresse = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['opticien:read', 'opticien:write'])]
    private ?string $companyName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['opticien:read', 'opticien:write'])]
    private ?string $ICE = null;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

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
            $image->setOpticien($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getOpticien() === $this) {
                $image->setOpticien(null);
            }
        }

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getICE(): ?string
    {
        return $this->ICE;
    }

    public function setICE(?string $ICE): static
    {
        $this->ICE = $ICE;

        return $this;
    }
}
