<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column] 
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'artist', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $User_idUser = null;

    #[ORM\Column(length: 90)]
    private ?string $fullname = null;


    #[ORM\Column(length: 90)]
    private ?string $active = null;

    //createdAt
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Song::class, mappedBy: 'Artist_idUser')]
    private Collection $songs;

    #[ORM\OneToMany(targetEntity: Album::class, mappedBy: 'artist_User_idUser')]
    private Collection $albums;

    #[ORM\OneToMany(targetEntity: LabelHasArtist::class, mappedBy: 'idArtist')]
    private Collection $labelHasArtist;

   


    public function __construct()
    {
        $this->songs = new ArrayCollection();
        $this->albums = new ArrayCollection();
        $this->labelHasArtist = new ArrayCollection();

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdUser(): ?User
    {
        return $this->User_idUser;
    }

    public function setUserIdUser(User $User_idUser): static
    {
        $this->User_idUser = $User_idUser;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): static
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getActive(): ?string
    {
        return $this->active;
    }

    public function setActive(?string $active): static
    {
        $this->active = $active;

        return $this;
    }
    //getCreatedAt
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    //setCreatedAt
    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
    //getUpdatedAt
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
    //setUpdatedAt
    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
    
    /**
     * @return Collection<int, Song>
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSong(Song $song): static
    {
        if (!$this->songs->contains($song)) {
            $this->songs->add($song);
            $song->addArtistIdUser($this);
        }

        return $this;
    }

    public function removeSong(Song $song): static
    {
        if ($this->songs->removeElement($song)) {
            $song->removeArtistIdUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): static
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->setArtistUserIdUser($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            // set the owning side to null (unless already changed)
            if ($album->getArtistUserIdUser() === $this) {
                $album->setArtistUserIdUser(null);
            }
        }

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
            $labelHasArtist->setIdArtist($this);
        }

        return $this;
    }

    public function removeLabelHasArtist(LabelHasArtist $labelHasArtist): static
    {
        if ($this->labelHasArtist->removeElement($labelHasArtist)) {
            // set the owning side to null (unless already changed)
            if ($labelHasArtist->getIdArtist() === $this) {
                $labelHasArtist->setIdArtist(null);
            }
        }

        return $this;
    }
    public function artistSerializer()
    {
    
        return [
            'fullname' => $this->getFullname(),
            'description' => $this->getDescription(),
        ];
    }

    public function artistAllSerializer()
    {
        $dateBirthFormatted = $this->getUserIdUser()->getDateBirth() ? $this->getUserIdUser()->getDateBirth()->format('Y-m-d') : null;
    
        // $label = $this->labelHasArtist->filter(function($labelHasArtist) {
        //     return $labelHasArtist->getLeftAt() === null;
        // })->map(function($labelHasArtist) {
        //     return $labelHasArtist->getIdLabel()->getLabelName();
        // })->first(); 

        $createdAt = $this->getCreatedAt() ? $this->getCreatedAt()->format('Y-m-d') : null;
        $sexe = $this->getUserIdUser()->getSexe() === '1' ? 'Homme' : 'Femme';
        return [
            'firstname' => $this->getUserIdUser()->getFirstname(),
            'lastname' => $this->getUserIdUser()->getLastname(),
            'sexe' => $sexe,
            'dateBirth'=>$dateBirthFormatted,  
            'Artist.CreatedAt' => $createdAt,    
            'albums' => $this->albums->map(function($album) {
                return $album->albumSerializer();
            }),
        ];
    }
}
