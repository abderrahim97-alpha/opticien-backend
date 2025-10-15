<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\ImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;


#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[Vich\Uploadable]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['image:read']]),
        new GetCollection(normalizationContext: ['groups' => ['image:read']]),
        new Post(
            denormalizationContext: ['groups' => ['image:write']],
            normalizationContext: ['groups' => ['image:read']]
        )
    ]
)]

class Image
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['image:read', 'opticien:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['image:read', 'opticien:read'])]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['image:read'])]
    private ?\DateTimeImmutable  $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['image:read', 'image:write'])]
    private ?Opticien $opticien = null;

    #[Vich\UploadableField(mapping: 'opticien_images', fileNameProperty: 'imageName')]
    #[Groups(['image:write'])]
    private ?File $imageFile = null;

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if ($imageFile) {
            $this->updatedAt = new \DateTimeImmutable ();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getOpticien(): ?Opticien
    {
        return $this->opticien;
    }

    public function setOpticien(?Opticien $opticien): static
    {
        $this->opticien = $opticien;

        return $this;
    }
}
