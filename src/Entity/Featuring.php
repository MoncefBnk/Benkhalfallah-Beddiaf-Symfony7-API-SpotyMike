<?php

namespace App\Entity;

use App\Repository\FeaturingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeaturingRepository::class)]
class Featuring
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 90)]
    private ?string $idFeaturing = null;

    #[ORM\ManyToOne(inversedBy: 'idArtist')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Song $idSong = null;

    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'featurings')]
    private Collection $idArtist;

    public function __construct()
    {
        $this->idArtist = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdFeaturing(): ?string
    {
        return $this->idFeaturing;
    }

    public function setIdFeaturing(string $idFeaturing): static
    {
        $this->idFeaturing = $idFeaturing;

        return $this;
    }

    public function getIdSong(): ?Song
    {
        return $this->idSong;
    }

    public function setIdSong(?Song $idSong): static
    {
        $this->idSong = $idSong;

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


    //featuring serializer
    public function featuringSerializer()
    {


        $artists = [];
        foreach ($this->getIdArtist() as $artist) {
            $artists[] = $artist->artistSerializer();
        }


        $artistOfSong = $this->getIdSong()->getAlbum()->getArtistUserIdUser()->artistSerializer();
        return [
            'id' => $this->getIdSong()->getId(),
            'title' => $this->getIdSong()->getTitle(),
            'cover' => $this->getIdSong()->getCover(),
            'artist' => $artistOfSong,
            'createdAt' => $this->getIdSong()->getCreateAt()->format('Y-m-d'),
        ];
    }
}
