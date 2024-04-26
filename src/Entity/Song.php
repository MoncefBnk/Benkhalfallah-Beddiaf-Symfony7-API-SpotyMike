<?php

namespace App\Entity;

use App\Repository\SongRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SongRepository::class)]
class Song
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 90)]
    private ?string $idSong = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 125)]
    private ?string $stream = null;

    #[ORM\Column(length: 125)]
    private ?string $cover = null;

    #[ORM\Column]
    private ?string $visibility = '0';

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\ManyToOne(inversedBy: 'song_idSong')]
    private ?Album $album = null;

    #[ORM\ManyToOne(inversedBy: 'Song_idSong')]
    private ?PlaylistHasSong $playlistHasSong = null;

    #[ORM\OneToMany(targetEntity: Featuring::class, mappedBy: 'idSong')]
    private Collection $idFeaturing;

    public function __construct()
    {
        $this->idFeaturing = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdSong(): ?string
    {
        return $this->idSong;
    }

    public function setIdSong(string $idSong): static
    {
        $this->idSong = $idSong;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getStream(): ?string
    {
        return $this->stream;
    }

    public function setStream(string $stream): static
    {
        $this->stream = $stream;

        return $this;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(string $cover): static
    {
        $this->cover = $cover;

        return $this;
    }

    public function isVisibility(): ?bool
    {
        return $this->visibility;
    }

    public function setVisibility(bool $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;

        return $this;
    }
    public function getAlbum(): ?Album
    {
        return $this->album;
    }

    public function setAlbum(?Album $album): static
    {
        $this->album = $album;

        return $this;
    }

    public function getPlaylistHasSong(): ?PlaylistHasSong
    {
        return $this->playlistHasSong;
    }

    public function setPlaylistHasSong(?PlaylistHasSong $playlistHasSong): static
    {
        $this->playlistHasSong = $playlistHasSong;

        return $this;
    }
    
    /**
     * @return Collection<int, Featuring>
     */
    public function getFeaturing(): Collection
    {
        return $this->idFeaturing;
    }
    
    public function addFeaturing(Featuring $featuring): static
    {
        if (!$this->idFeaturing->contains($featuring)) {
            $this->idFeaturing->add($featuring);
            $featuring->setIdSong($this);
        }
        
        return $this;
    }
    
    public function removeFeaturing(Featuring $featuring): static
    {
        if ($this->idFeaturing->removeElement($featuring)) {
            // set the owning side to null (unless already changed)
            if ($featuring->getIdSong() === $this) {
                $featuring->setIdSong(null);
            }
        }
        return $this;
    }

    public function songSerializer()
    {

        $featuring = [];
        foreach ($this->getFeaturing() as $feat) {
            $featuring[] = $feat->featuringSerializer();
        }
        // get artist from album
        $artist = $this->getAlbum()->getArtistUserIdUser();
        $createdAt = $this->getCreateAt() ? $this->getCreateAt()->format('Y-m-d') : null;
        return [
            'id' => strval($this->getId()),
            'title' => $this->getTitle(),
            'cover' => $this->getCover(),
            'createdAt' => $createdAt,
        ];
    }
    public function songSerializerForAlbum()
    {
      //get artist all serializer from featuring table 
        $featuring = [];
        foreach ($this->getFeaturing() as $feat) {

            foreach ($feat->getIdArtist() as $artist) {
            $featuring[] = $artist->artistFeaturingSerializer();
            }

        }

        return [
            'id' => strval($this->getId()),
            'title' => $this->getTitle(),
            'cover' => $this->getCover(),
            'featuring' => $featuring, 
            'createdAt' => $this->getCreateAt()->format('Y-m-d')
        ];
    }
    public function songSerializerForOneAlbum()
    {
      //get artist all serializer from featuring table 
        $featuring = [];
        foreach ($this->getFeaturing() as $feat) {

            foreach ($feat->getIdArtist() as $artist) {
            $featuring[] = $artist->artistFeaturingSerializer();
            }

        }
        // get artist from album
        $createdAt = $this->getCreateAt() ? $this->getCreateAt()->format('Y-m-d') : null;
        return [
            'id' => strval($this->getId()),
            'title' => $this->getTitle(),
            'cover' => $this->getCover(),
            'createdAt' => $createdAt,
            'featuring' => $featuring,
            
        ];
    }
}