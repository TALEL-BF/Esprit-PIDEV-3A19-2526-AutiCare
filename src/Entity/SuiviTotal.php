<?php

namespace App\Entity;

use App\Repository\SuiviTotalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SuiviTotalRepository::class)]
class SuiviTotal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "L'identifiant enfant est obligatoire.")]
    #[Assert\Positive(message: "L'identifiant enfant doit être un entier positif.")]
    private ?int $enfantId = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: "La moyenne est obligatoire.")]
    #[Assert\Type(type: 'numeric', message: "La moyenne doit être un nombre.")]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: "La moyenne doit être entre {{ min }} et {{ max }}."
    )]
    private ?float $moyenne = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "La remarque ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $remarque = null;

    #[ORM\ManyToOne(inversedBy: 'suiviTotals')]
    #[ORM\JoinColumn(nullable: true)]
    private ?NiveauJeu $niveauJeu = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnfantId(): ?int
    {
        return $this->enfantId;
    }

    public function setEnfantId(int $enfantId): static
    {
        $this->enfantId = $enfantId;
        return $this;
    }

    public function getMoyenne(): ?float
    {
        return $this->moyenne;
    }

    public function setMoyenne(float $moyenne): static
    {
        $this->moyenne = $moyenne;
        return $this;
    }

    public function getRemarque(): ?string
    {
        return $this->remarque;
    }

    public function setRemarque(?string $remarque): static
    {
        $this->remarque = $remarque !== null ? trim($remarque) : null;
        return $this;
    }

    public function getNiveauJeu(): ?NiveauJeu
    {
        return $this->niveauJeu;
    }

    public function setNiveauJeu(?NiveauJeu $niveauJeu): static
    {
        $this->niveauJeu = $niveauJeu;
        return $this;
    }
}