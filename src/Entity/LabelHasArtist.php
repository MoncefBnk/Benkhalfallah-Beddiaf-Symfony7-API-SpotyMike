<?php

namespace App\Entity;

use App\Repository\LabelHasArtistRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LabelHasArtistRepository::class)]
class LabelHasArtist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $joinedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $leftAt = null;

    #[ORM\ManyToOne(inversedBy: 'labelHasArtist')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Artist $idArtist = null;

    #[ORM\ManyToOne(inversedBy: 'labelHasArtist')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Label $idLabel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJoinedAt(): ?\DateTimeInterface
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeInterface $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function getLeftAt(): ?\DateTimeInterface
    {
        return $this->leftAt;
    }

    public function setLeftAt(?\DateTimeInterface $leftAt): static
    {
        $this->leftAt = $leftAt;

        return $this;
    }

    public function getIdArtist(): ?Artist
    {
        return $this->idArtist;
    }

    public function setIdArtist(?Artist $idArtist): static
    {
        $this->idArtist = $idArtist;

        return $this;
    }

    public function getIdLabel(): ?Label
    {
        return $this->idLabel;
    }

    public function setIdLabel(?Label $idLabel): static
    {
        $this->idLabel = $idLabel;

        return $this;
    }
}
