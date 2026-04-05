<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\SeanceRepository;

#[ORM\Entity(repositoryClass: SeanceRepository::class)]
#[ORM\Table(name: 'seance')]
#[ORM\HasLifecycleCallbacks]
class Seance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_seance', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'titre_seance', type: Types::STRING, length: 100)]
    private ?string $titreSeance = null;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'date_seance', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateSeance = null;

    #[ORM\Column(name: 'jours_semaine', type: Types::STRING, length: 20)]
    private ?string $joursSemaine = null;

    #[ORM\Column(name: 'duree', type: Types::INTEGER)]
    private ?int $duree = null;

    #[ORM\Column(name: 'statut_seance', type: Types::STRING, length: 20, options: ['default' => 'planifiee'])]
    private ?string $statutSeance = null;

    #[ORM\Column(name: 'id_autiste', type: Types::INTEGER)]
    private ?int $idAutiste = null;

    #[ORM\Column(name: 'id_professeur', type: Types::INTEGER)]
    private ?int $idProfesseur = null;

    #[ORM\Column(name: 'id_cours', type: Types::INTEGER)]
    private ?int $idCours = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitreSeance(): ?string
    {
        return $this->titreSeance;
    }

    public function setTitreSeance(?string $titreSeance): self
    {
        $this->titreSeance = $titreSeance;

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

    public function getDateSeance(): ?\DateTimeInterface
    {
        return $this->dateSeance;
    }

    public function setDateSeance(?\DateTimeInterface $dateSeance): self
    {
        $this->dateSeance = $dateSeance;

        return $this;
    }

    public function getJoursSemaine(): ?string
    {
        return $this->joursSemaine;
    }

    public function setJoursSemaine(?string $joursSemaine): self
    {
        $this->joursSemaine = $joursSemaine;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): self
    {
        $this->duree = $duree;

        return $this;
    }

    public function getStatutSeance(): ?string
    {
        return $this->statutSeance;
    }

    public function setStatutSeance(?string $statutSeance): self
    {
        $this->statutSeance = $statutSeance;

        return $this;
    }

    public function getIdAutiste(): ?int
    {
        return $this->idAutiste;
    }

    public function setIdAutiste(?int $idAutiste): self
    {
        $this->idAutiste = $idAutiste;

        return $this;
    }

    public function getIdProfesseur(): ?int
    {
        return $this->idProfesseur;
    }

    public function setIdProfesseur(?int $idProfesseur): self
    {
        $this->idProfesseur = $idProfesseur;

        return $this;
    }

    public function getIdCours(): ?int
    {
        return $this->idCours;
    }

    public function setIdCours(?int $idCours): self
    {
        $this->idCours = $idCours;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTime();
        $this->createdAt ??= $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
