<?php

namespace App\Entity;

use App\Repository\EmploiDuTempsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmploiDuTempsRepository::class)]
#[ORM\Table(name: 'emploi_du_temps')]
#[ORM\HasLifecycleCallbacks]
class EmploiDuTemps
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_emploi', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'annee_scolaire', type: Types::STRING, length: 20)]
    private string $anneeScolaire = '';

    #[ORM\Column(name: 'jour_semaine', type: Types::STRING, length: 20)]
    private ?string $jourSemaine = null;

    #[ORM\Column(name: 'tranche_horaire', type: Types::STRING, length: 20)]
    private ?string $trancheHoraire = null;

    #[ORM\Column(name: 'id_rdv', type: Types::INTEGER, nullable: true)]
    private ?int $idRdv = null;

    #[ORM\Column(name: 'id_seance', type: Types::INTEGER, nullable: true)]
    private ?int $idSeance = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnneeScolaire(): string
    {
        return $this->anneeScolaire;
    }

    public function setAnneeScolaire(string $anneeScolaire): self
    {
        $this->anneeScolaire = $anneeScolaire;

        return $this;
    }

    public function getJourSemaine(): ?string
    {
        return $this->jourSemaine;
    }

    public function setJourSemaine(?string $jourSemaine): self
    {
        $this->jourSemaine = $jourSemaine;

        return $this;
    }

    public function getTrancheHoraire(): ?string
    {
        return $this->trancheHoraire;
    }

    public function setTrancheHoraire(?string $trancheHoraire): self
    {
        $this->trancheHoraire = $trancheHoraire;

        return $this;
    }

    public function getIdRdv(): ?int
    {
        return $this->idRdv;
    }

    public function setIdRdv(?int $idRdv): self
    {
        $this->idRdv = $idRdv;

        return $this;
    }

    public function getIdSeance(): ?int
    {
        return $this->idSeance;
    }

    public function setIdSeance(?int $idSeance): self
    {
        $this->idSeance = $idSeance;

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
