<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\RdvRepository;

#[ORM\Entity(repositoryClass: RdvRepository::class)]
#[ORM\Table(name: 'rdv')]
#[ORM\HasLifecycleCallbacks]
class Rdv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_rdv', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'type_consultation', type: Types::STRING, length: 50)]
    private ?string $typeConsultation = null;

    #[ORM\Column(name: 'date_heure_rdv', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateHeureRdv = null;

    #[ORM\Column(name: 'statut_rdv', type: Types::STRING, length: 20, options: ['default' => 'planifiee'])]
    private ?string $statutRdv = null;

    #[ORM\Column(name: 'duree_rdv_minutes', type: Types::INTEGER)]
    private ?int $dureeRdvMinutes = null;

    #[ORM\Column(name: 'id_psychologue', type: Types::INTEGER)]
    private ?int $idPsychologue = null;

    #[ORM\Column(name: 'id_autiste', type: Types::INTEGER)]
    private ?int $idAutiste = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'zoom_join_url', type: Types::STRING, length: 255, nullable: true)]
    private ?string $zoomJoinUrl = null;

    #[ORM\Column(name: 'zoom_start_url', type: Types::TEXT, nullable: true)]
    private ?string $zoomStartUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeConsultation(): ?string
    {
        return $this->typeConsultation;
    }

    public function setTypeConsultation(?string $typeConsultation): self
    {
        $this->typeConsultation = $typeConsultation;

        return $this;
    }

    public function getDateHeureRdv(): ?\DateTimeInterface
    {
        return $this->dateHeureRdv;
    }

    public function setDateHeureRdv(?\DateTimeInterface $dateHeureRdv): self
    {
        $this->dateHeureRdv = $dateHeureRdv;

        return $this;
    }

    public function getStatutRdv(): ?string
    {
        return $this->statutRdv;
    }

    public function setStatutRdv(?string $statutRdv): self
    {
        $this->statutRdv = $statutRdv;

        return $this;
    }

    public function getDureeRdvMinutes(): ?int
    {
        return $this->dureeRdvMinutes;
    }

    public function setDureeRdvMinutes(?int $dureeRdvMinutes): self
    {
        $this->dureeRdvMinutes = $dureeRdvMinutes;

        return $this;
    }

    public function getIdPsychologue(): ?int
    {
        return $this->idPsychologue;
    }

    public function setIdPsychologue(?int $idPsychologue): self
    {
        $this->idPsychologue = $idPsychologue;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getZoomJoinUrl(): ?string
    {
        return $this->zoomJoinUrl;
    }

    public function setZoomJoinUrl(?string $zoomJoinUrl): self
    {
        $this->zoomJoinUrl = $zoomJoinUrl;

        return $this;
    }

    public function getZoomStartUrl(): ?string
    {
        return $this->zoomStartUrl;
    }

    public function setZoomStartUrl(?string $zoomStartUrl): self
    {
        $this->zoomStartUrl = $zoomStartUrl;

        return $this;
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
