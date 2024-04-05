<?php

namespace App\Entity;

use App\Repository\LabelHasArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    private ?\DateTimeInterface $leftAt = null;

    #[ORM\ManyToMany(targetEntity: Label::class, inversedBy: 'labelHasArtists')]
    private Collection $idLabel;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $joinedAt = null;

    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'labelHasArtists')]
    private Collection $idArtist;

    public function __construct()
    {
        $this->idLabel = new ArrayCollection();
        $this->idArtist = new ArrayCollection();
    }

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

    public function setLeftAt(\DateTimeInterface $leftAt): static
    {
        $this->leftAt = $leftAt;

        return $this;
    }

    /**
     * @return Collection<int, Label>
     */
    public function getIdLabel(): Collection
    {
        return $this->idLabel;
    }

    public function addIdLabel(Label $idLabel): static
    {
        if (!$this->idLabel->contains($idLabel)) {
            $this->idLabel->add($idLabel);
        }

        return $this;
    }

    public function removeIdLabel(Label $idLabel): static
    {
        $this->idLabel->removeElement($idLabel);

        return $this;
    }

    /**
     * @return Collection<int, Artist>
     */
    public function getIdArtist(): Collection
    {
        return $this->idArtist;
    }

    public function addIdArtist(Artist $idArtist): static
    {
        if (!$this->idArtist->contains($idArtist)) {
            $this->idArtist->add($idArtist);
        }

        return $this;
    }

    public function removeIdArtist(Artist $idArtist): static
    {
        $this->idArtist->removeElement($idArtist);

        return $this;
    }
}
