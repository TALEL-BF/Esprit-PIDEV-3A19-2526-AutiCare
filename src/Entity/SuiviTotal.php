<?php

namespace App\Entity;

use App\Repository\SuiviTotalRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SuiviTotalRepository::class)]
#[ORM\HasLifecycleCallbacks]
class SuiviTotal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'enfant_id')]
    #[Assert\NotNull(message: "L'identifiant enfant est obligatoire.")]
    #[Assert\Positive(message: "L'identifiant enfant doit être un entier positif.")]
    private ?int $enfantId = null;

    #[ORM\Column(name: 'note_cours', type: 'float')]
    #[Assert\NotNull(message: "La note de cours est obligatoire.")]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: "La note de cours doit être entre {{ min }} et {{ max }}."
    )]
    private ?float $noteCours = null;

    #[ORM\Column(name: 'note_consultation', type: 'float')]
    #[Assert\NotNull(message: "La note de consultation est obligatoire.")]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: "La note de consultation doit être entre {{ min }} et {{ max }}."
    )]
    private ?float $noteConsultation = null;

    #[ORM\Column(name: 'moyenne_generale', type: 'float')]
    private ?float $moyenneGenerale = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "La remarque ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $remarque = null;

    #[ORM\ManyToOne(inversedBy: 'suiviTotals')]
    #[ORM\JoinColumn(name: 'niveau_jeu_id', nullable: true)]
    private ?NiveauJeu $niveauJeu = null;

    #[ORM\Column(name: 'date_calcul', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateCalcul = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        if ($this->dateCalcul === null) {
            $this->dateCalcul = $now;
        }

        $this->updatedAt = $now;
        $this->calculerMoyenneGenerale();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->calculerMoyenneGenerale();
    }

    public function calculerMoyenneGenerale(): static
    {
        if ($this->noteCours !== null && $this->noteConsultation !== null) {
            $this->moyenneGenerale = round(($this->noteCours + $this->noteConsultation) / 2, 2);
        }

        return $this;
    }

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

    public function getNoteCours(): ?float
    {
        return $this->noteCours;
    }

    public function setNoteCours(float $noteCours): static
    {
        $this->noteCours = $noteCours;
        return $this;
    }

    public function getNoteConsultation(): ?float
    {
        return $this->noteConsultation;
    }

    public function setNoteConsultation(float $noteConsultation): static
    {
        $this->noteConsultation = $noteConsultation;
        return $this;
    }

    public function getMoyenneGenerale(): ?float
    {
        return $this->moyenneGenerale;
    }

    public function setMoyenneGenerale(float $moyenneGenerale): static
    {
        $this->moyenneGenerale = $moyenneGenerale;
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

    public function getDateCalcul(): ?\DateTimeImmutable
    {
        return $this->dateCalcul;
    }

    public function setDateCalcul(?\DateTimeImmutable $dateCalcul): static
    {
        $this->dateCalcul = $dateCalcul;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}