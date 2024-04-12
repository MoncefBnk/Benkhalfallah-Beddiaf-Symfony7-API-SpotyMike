<?php

namespace App\Entity;

use App\Repository\LabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LabelRepository::class)]
class Label
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 90)]
    private ?string $idLabel = null;

    #[ORM\Column(length: 90)]
    private ?string $labelName = null;

    #[ORM\OneToMany(targetEntity: LabelHasArtist::class, mappedBy: 'idLabel')]
    private Collection $labelHasArtist;

    public function __construct()
    {
        $this->labelHasArtist = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdLabel(): ?string
    {
        return $this->idLabel;
    }

    public function setIdLabel(string $idLabel): static
    {
        $this->idLabel = $idLabel;

        return $this;
    }

    public function getLabelName(): ?string
    {
        return $this->labelName;
    }

    public function setLabelName(string $labelName): static
    {
        $this->labelName = $labelName;

        return $this;
    }

    /**
     * @return Collection<int, LabelHasArtist>
     */
    public function getLabelHasArtist(): Collection
    {
        return $this->labelHasArtist;
    }

    public function addLabelHasArtist(LabelHasArtist $labelHasArtist): static
    {
        if (!$this->labelHasArtist->contains($labelHasArtist)) {
            $this->labelHasArtist->add($labelHasArtist);
            $labelHasArtist->setIdLabel($this);
        }

        return $this;
    }

    public function removeLabelHasArtist(LabelHasArtist $labelHasArtist): static
    {
        if ($this->labelHasArtist->removeElement($labelHasArtist)) {
            // set the owning side to null (unless already changed)
            if ($labelHasArtist->getIdLabel() === $this) {
                $labelHasArtist->setIdLabel(null);
            }
        }

        return $this;
    }
    public function labelSerializer()
    {

        return [

            'idLabel' => $this->getIdLabel(),
            'labelName' => $this->getLabelName(),
     
        ];
    }
}
