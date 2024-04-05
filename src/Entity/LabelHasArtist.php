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

    #[ORM\OneToMany(targetEntity: Artist::class, mappedBy: 'LabelHasArtist')]
    private Collection $idArtist;

    #[ORM\OneToMany(targetEntity: Label::class, mappedBy: 'labelHasArtist')]
    private Collection $idLabel;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $joinedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $leftAt = null;

    public function __construct()
    {
        $this->idArtist = new ArrayCollection();
        $this->idLabel = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $idArtist->setLabelHasArtist($this);
        }

        return $this;
    }

    public function removeIdArtist(Artist $idArtist): static
    {
        if ($this->idArtist->removeElement($idArtist)) {
            // set the owning side to null (unless already changed)
            if ($idArtist->getLabelHasArtist() === $this) {
                $idArtist->setLabelHasArtist(null);
            }
        }

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
            $idLabel->setLabelHasArtist($this);
        }

        return $this;
    }

    public function removeIdLabel(Label $idLabel): static
    {
        if ($this->idLabel->removeElement($idLabel)) {
            // set the owning side to null (unless already changed)
            if ($idLabel->getLabelHasArtist() === $this) {
                $idLabel->setLabelHasArtist(null);
            }
        }

        return $this;
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
}
