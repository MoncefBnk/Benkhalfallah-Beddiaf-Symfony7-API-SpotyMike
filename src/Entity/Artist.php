<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'artist', targetEntity: User::class, cascade: ['persist', 'remove'])]
     private ?User $user = null;

     


    #[ORM\Column(length: 90)]
    private ?string $fullname = null;

    #[ORM\Column(length: 90)]
    private ?string $label = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;


    #[ORM\OneToMany(mappedBy: 'artist', targetEntity: Album::class, cascade: ['persist', 'remove'])]
    private Collection $albums;


    public function __construct()
    {
   
        $this->albums = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): self
    {
        $this->fullname = $fullname;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    
    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): self
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->setArtistUserIdUser($this);
        }
        return $this;
    }

    public function removeAlbum(Album $album): self
    {
        if ($this->albums->removeElement($album)) {
            if ($album->getArtistUserIdUser() === $this) {
                $album->setArtistUserIdUser(null);
            }
        }
        return $this;
    }

    public function serializer(): array
{
    $user = $this->getUser();
    $userData = $user ? [
        'firstname' => $user->getFirstname(),
        'lastname' => $user->getLastname(),
        'sexe' => $user->getSexe(),
        'dateBirth' => $user->getDateBirth()->format('Y-m-d'), 
    ] : null;

    return [
        'id' => $this->getId(),
        'fullname' => $this->getFullname(),
        'label' => $this->getLabel(),
        'description' => $this->getDescription(),
        'user' => $userData,
        'albums' => $this->getAlbums()->map(function ($album) {
            return $album->serializer();
        })->toArray(),
       
    ];
}

}
