<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // #[ORM\Id]
    #[ORM\Column(length: 90)]
    private ?string $idUser = null;

    #[ORM\Column(length: 55)]
    private ?string $firstname = null;

    #[ORM\Column(length: 55)]
    private ?string $lastname = null;

    #[ORM\Column(length: 80)]
    private ?string $email = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $tel = null;

    #[ORM\Column(length: 90)]
    private ?string $encrypte = null;

    #[ORM\Column(length: 55, nullable: true)]
    private ?string $sexe = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $active = null;
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateBirth = null;

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

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    private function validationEmail(?string $email): bool
    {
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

    public function getPassword(): ?string
    {
        return $this->encrypte;
    }

    public function setPassword(string $encrypte): static
    {
        $this->encrypte = $encrypte;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): static
    {
        $this->sexe = $sexe;

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
    
    public function getTel(): ?string
    {
        return $this->tel;
    }

    private function validationTel(?string $numero): bool
    {
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

    public function getDateBirth(): ?\DateTimeInterface
    {
        return $this->dateBirth;
    }

    public function setDateBirth(?\DateTimeInterface $dateBirth): static
    {
        $this->dateBirth = $dateBirth;

        return $this;
    }

        /**
     * The public representation of the user (e.g. a username, an email address, etc.)
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

     /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
       
        return ["PUBLIC_ACCESS"];
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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
        $dateBirthFormatted = $this->getDateBirth() ? $this->getDateBirth()->format('d-m-Y') : null;

        return [
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'email' => $this->getEmail(),
            'tel' => $this->getTel(),
            'sexe' => $this->getSexe(),
            'dateBirth'=>$dateBirthFormatted,
            'createdAt' => $this->getCreateAt(),
            'updateAt' => $this->getUpdateAt(),
            'artist' => $this->getArtist() ?  $this->getArtist()->artistSerializer() : [],
        ];
    }
}
