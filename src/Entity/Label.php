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

    #[ORM\Column(length: 45)]
    private ?string $name = null;

   
    public function __construct()
    {
        
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function labelSerializer()
    {
    
        return [
            'name' => $this->getName(),
            'idLabel' => $this->getIdLabel(),
        ];
    }
}
