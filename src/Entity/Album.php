<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 95)]
    private ?string $title = null;

    #[ORM\Column(length: 20)]
    private ?string $categ = null; 

    #[ORM\Column(length: 125)]
    private ?string $cover = null;

    //add visibility
    #[ORM\Column]
    private ?string $visibility = '0';

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updateAt = null;

    #[ORM\ManyToOne(inversedBy: 'albums')]
    private ?Artist $artist_User_idUser = null;

    #[ORM\OneToMany(targetEntity: Song::class, mappedBy: 'album')]
    private Collection $song_idSong;

    public function __construct()
    {
        $this->song_idSong = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCateg(): ?string
    {
        return $this->categ;
    }

    public function setCateg(string $categ): static
    {
        $this->categ = $categ;

        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }   

    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

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

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeInterface $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    public function getArtistUserIdUser(): ?Artist
    {
        return $this->artist_User_idUser;
    }

    public function setArtistUserIdUser(?Artist $artist_User_idUser): static
    {
        $this->artist_User_idUser = $artist_User_idUser;

        return $this;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getSongIdSong(): Collection
    {
        return $this->song_idSong;
    }

    public function addSongIdSong(Song $songIdSong): static
    {
        if (!$this->song_idSong->contains($songIdSong)) {
            $this->song_idSong->add($songIdSong);
            $songIdSong->setAlbum($this);
        }

        return $this;
    }

    public function removeSongIdSong(Song $songIdSong): static
    {
        if ($this->song_idSong->removeElement($songIdSong)) {
            // set the owning side to null (unless already changed)
            if ($songIdSong->getAlbum() === $this) {
                $songIdSong->setAlbum(null);
            }
        }

        return $this;
    }

    public function albumAllSerializer()
    {
        $songs = [];
        foreach ($this->getSongIdSong() as $song) {
            $songs[] = $song->songSerializerForAlbum();
        }

        $artist = $this->getArtistUserIdUser();
        $year = $this->getCreateAt();
        $formatedYear = $year ? $year->format('Y') : null;

        $label = null;
        $labelHasArtist = $artist->getLabelHasArtist()->filter(function($labelHasArtist) use ($year) {
            $joinedAt = $labelHasArtist->getJoinedAt();
            $leftAt = $labelHasArtist->getLeftAt();

            return $joinedAt<= $year && ($leftAt === null || $leftAt > $year);
        })->first();

        if ($labelHasArtist) {
            $label = $labelHasArtist->getIdLabel()->getLabelName();
        }
        $createdAt = $this->getCreateAt() ? $this->getCreateAt()->format('Y-m-d') : null;
        return [
            'id' => strval($this->getId()),
            'nom' => $this->getTitle(),
            'categ' => $this->getCateg(),
            'label' => $label,
            'cover' => $this->getCover(),
            'year' => $formatedYear,
            'createdAt' => $createdAt,
            'songs' => $songs,
            'artist' => $artist->artistAlbumSerializer(),

        ];
    }
    public function albumSerializer()
    {
        $songs = [];
        foreach ($this->getSongIdSong() as $song) {
            $songs[] = $song->songSerializerForOneAlbum();
        }

        $artist = $this->getArtistUserIdUser();
        $year = $this->getCreateAt();
        $formatedYear = $year ? $year->format('Y') : null;

        $label = null;
        $labelHasArtist = $artist->getLabelHasArtist()->filter(function($labelHasArtist) use ($year) {
            $joinedAt = $labelHasArtist->getJoinedAt();
            $leftAt = $labelHasArtist->getLeftAt();

            return $joinedAt<= $year && ($leftAt === null || $leftAt > $year);
        })->first();

        if ($labelHasArtist) {
            $label = $labelHasArtist->getIdLabel()->getLabelName();
        }
        $createdAt = $this->getCreateAt() ? $this->getCreateAt()->format('Y-m-d') : null;
        return [
            'id' => strval($this->getId()),
            'nom' => $this->getTitle(),
            'categ' => $this->getCateg(),
            'label' => $label,
            'cover' => $this->getCover(),
            'year' => $formatedYear,
            'createdAt' => $createdAt,
            'songs' => $songs,
            'artist' => $artist->artistAlbumSerializer(),

        ];
    }
}
