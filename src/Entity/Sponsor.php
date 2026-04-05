<?php
// src/Entity/Sponsor.php

namespace App\Entity;

use App\Repository\SponsorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SponsorRepository::class)]
#[ORM\Table(name: 'sponsor')]
class Sponsor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idSponsor', type: 'integer')]
    private ?int $idSponsor = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom du sponsor est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\-']+$/",
        message: "Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes"
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Email(
        message: "L'email '{{ value }}' n'est pas un email valide",
        mode: 'html5'
    )]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(
        pattern: "/^[\+\d\s\-\(\)]+$/",
        message: "Le numéro de téléphone n'est pas valide"
    )]
    #[Assert\Length(
        min: 8,
        max: 20,
        minMessage: "Le téléphone doit contenir au moins {{ limit }} chiffres",
        maxMessage: "Le téléphone ne peut pas dépasser {{ limit }} chiffres"
    )]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: 'TypeSponsor', length: 255, nullable: true)]
    #[Assert\Choice(
        choices: ["Platine", "Gold", "Silver", "Bronze"],
        message: "Choisissez un type de sponsor valide (Platine, Gold, Silver, Bronze)"
    )]
    private ?string $TypeSponsor = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(
        min: 20,
        max: 1000,
        minMessage: "La description doit contenir au moins {{ limit }} caractères",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Le montant est obligatoire")]
    #[Assert\Positive(message: "Le montant doit être positif")]
    #[Assert\Range(
        min: 1000,
        max: 1000000,
        notInRangeMessage: "Le montant doit être entre {{ min }} TND et {{ max }} TND"
    )]
    #[Assert\Type(
        type: 'integer',
        message: "Le montant doit être un nombre entier"
    )]
    private ?int $montant = null;

    /**
     * @var Collection<int, Event>
     */
    #[ORM\ManyToMany(targetEntity: Event::class, mappedBy: 'sponsors')]
    private Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function getIdSponsor(): ?int
    {
        return $this->idSponsor;
    }

    public function setIdSponsor(int $idSponsor): static
    {
        $this->idSponsor = $idSponsor;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getTypeSponsor(): ?string
    {
        return $this->TypeSponsor;
    }

    public function setTypeSponsor(?string $TypeSponsor): static
    {
        $this->TypeSponsor = $TypeSponsor;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getMontant(): ?int
    {
        return $this->montant;
    }

    public function setMontant(int $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(Event $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->addSponsor($this);
        }
        return $this;
    }

    public function removeEvent(Event $event): static
    {
        if ($this->events->removeElement($event)) {
            $event->removeSponsor($this);
        }
        return $this;
    }
}