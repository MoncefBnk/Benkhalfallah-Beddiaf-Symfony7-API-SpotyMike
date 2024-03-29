<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
        // #[ORM\Id]
    #[ORM\Column(length: 90)]
    private ?string $idUser = null;

    #[ORM\Column(length: 55)]
    private ?string $name = null;

    #[ORM\Column(length: 80)]
    private ?string $email = null;

    #[ORM\Column(length: 90)]
    private ?string $encrypte = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $tel = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updateAt = null;

    #[ORM\OneToOne(mappedBy: 'User_idUser', cascade: ['persist', 'remove'])]
    private ?Artist $artist = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?string
    {
        return $this->idUser;
    }

    public function setIdUser(string $idUser): static
    {
        $this->idUser = $idUser;

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    private function validationEmail(?string $email): bool {
        if ($email === null) {
            return true;
        }
        $regex = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        return preg_match($regex, $email) === 1;
    }

    public function setEmail(string $email): static
    {
        if (!$this->validationEmail($email)) {
            throw new \InvalidArgumentException("Adresse e-mail invalide. Veuillez entrer une adresse email valide.");
        }
        $this->email = $email;

        return $this;
    }

    public function getEncrypte(): ?string
    {
        return $this->encrypte;
    }

    public function setEncrypte(string $encrypte): static
    {
        $this->encrypte = $encrypte;

        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    private function validationTel(?string $numero): bool {
        if ($numero === null) {
            return true;
        }
        $regex = '/^0[1-9](?:[ .-]?[0-9]{2}){4}$/';
        return preg_match($regex, $numero) === 1;
    }

    public function setTel(?string $tel): static
    {
        if (!$this->validationTel($tel)) {
            throw new \InvalidArgumentException("Numéro de téléphone invalide. Veuillez entrer un numéro de téléphone français valide.");
        }
        $this->tel = $tel;

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

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(Artist $artist): static
    {
        // set the owning side of the relation if necessary
        if ($artist->getUserIdUser() !== $this) {
            $artist->setUserIdUser($this);
        }

        $this->artist = $artist;

        return $this;
    }

    public function userSerializer()
    {

        return [
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'tel' => $this->getTel(),
            'created at' =>$this->getCreateAt(),
            'updated at' =>$this->getUpdateAt(),

        ];
    }
}