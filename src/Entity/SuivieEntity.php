<?php

namespace App\Entity;

use App\Repository\SuivieEntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SuivieEntityRepository::class)]
#[ORM\Table(name: "suivie")]
class SuivieEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "ID_SUIVIE")]
    private ?int $id = null;

    #[ORM\Column(name: "NOM_ENFANT", length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'enfant est obligatoire")]
    #[Assert\Length(min: 3, minMessage: "Minimum 3 caractères")]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s]+$/",
        message: "Lettres et espaces uniquement"
    )]
    private ?string $nomEnfant = null;

   #[ORM\Column(name: "EMAIL_PARENT", length: 255, nullable: false)]
#[Assert\NotBlank(message: "L'email est obligatoire")]
#[Assert\Email(message: "Format email invalide (ex: parent@email.com)")]
private ?string $emailParent = null;

    #[ORM\Column(name: "AGE")]
    #[Assert\NotBlank(message: "L'âge est obligatoire")]
    #[Assert\Range(
        min: 1,
        max: 120,
        notInRangeMessage: "Âge entre 1 et 120"
    )]
    private ?int $age = null;

    #[ORM\Column(name: "NOM_PSY", length: 255)]
    #[Assert\NotBlank(message: "Nom du psy obligatoire")]
    #[Assert\Length(min: 3)]
    private ?string $nomPsy = null;

    #[ORM\Column(name: "DATE_SUIVIE", type: "datetime")]
    #[Assert\NotNull(message: "Date obligatoire")]
    private ?\DateTimeInterface $dateSuivie = null;

    #[ORM\Column(name: "SCORE_HUMEUR")]
    #[Assert\Range(min: 0, max: 10)]
    private ?int $scoreHumeur = null;

    #[ORM\Column(name: "SCORE_STRESS")]
    #[Assert\Range(min: 0, max: 10)]
    private ?int $scoreStress = null;

    #[ORM\Column(name: "SCORE_ATTENTION")]
    #[Assert\Range(min: 0, max: 10)]
    private ?int $scoreAttention = null;

    #[ORM\Column(name: "NIVEAU_SEANCE", nullable: true)]
    #[Assert\Range(min: 1, max: 10)]
    private ?int $niveauSeance = null;

    #[ORM\Column(name: "COMPORTEMENT", length: 255)]
    #[Assert\NotBlank(message: "Comportement obligatoire")]
    private ?string $comportement = null;

    #[ORM\Column(name: "INTERACTION_SOCIALE", length: 255)]
    #[Assert\NotBlank(message: "Interaction sociale obligatoire")]
    private ?string $interactionSociale = null;

    #[ORM\Column(name: "OBSERVATION", length: 255)]
    #[Assert\Length(max: 255, maxMessage: "Max 255 caractères")]
    private ?string $observation = null;

    #[ORM\Column(name: "STATUT", length: 255)]
    #[Assert\NotBlank(message: "Statut obligatoire")]
    #[Assert\Choice([
        'normal',
        'immobile',
        'ne_bouge_pas',
        'n_entend_pas',
        'ne_voit_pas'
    ], message: "Statut invalide")]
    private ?string $statut = null;

    #[ORM\ManyToOne(targetEntity: TherapieEntity::class)]
    #[ORM\JoinColumn(name: "ID_THERAPIE_RECO", referencedColumnName: "ID_THERAPIE", nullable: true)]
    private ?TherapieEntity $therapie = null;

    #[ORM\Column(name: "CR_RESUME", type: "text", nullable: true)]
    private ?string $crResume = null;

    #[ORM\Column(name: "CR_PDF_PATH", length: 500, nullable: true)]
    private ?string $crPdfPath = null;

    #[ORM\Column(name: "PARENT_PDF_UPLOADED_AT", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $parentPdfUploadedAt = null;

    // ================= GETTERS & SETTERS =================

    public function getId(): ?int { return $this->id; }

    public function getNomEnfant(): ?string { return $this->nomEnfant; }
    public function setNomEnfant(string $nomEnfant): self { $this->nomEnfant = $nomEnfant; return $this; }

    public function getEmailParent(): ?string { return $this->emailParent; }
    public function setEmailParent(?string $emailParent): self { $this->emailParent = $emailParent; return $this; }

    public function getAge(): ?int { return $this->age; }
    public function setAge(int $age): self { $this->age = $age; return $this; }

    public function getNomPsy(): ?string { return $this->nomPsy; }
    public function setNomPsy(string $nomPsy): self { $this->nomPsy = $nomPsy; return $this; }

    public function getDateSuivie(): ?\DateTimeInterface { return $this->dateSuivie; }
    public function setDateSuivie(\DateTimeInterface $dateSuivie): self { $this->dateSuivie = $dateSuivie; return $this; }

    public function getScoreHumeur(): ?int { return $this->scoreHumeur; }
    public function setScoreHumeur(int $scoreHumeur): self { $this->scoreHumeur = $scoreHumeur; return $this; }

    public function getScoreStress(): ?int { return $this->scoreStress; }
    public function setScoreStress(int $scoreStress): self { $this->scoreStress = $scoreStress; return $this; }

    public function getScoreAttention(): ?int { return $this->scoreAttention; }
    public function setScoreAttention(int $scoreAttention): self { $this->scoreAttention = $scoreAttention; return $this; }

    public function getNiveauSeance(): ?int { return $this->niveauSeance; }
    public function setNiveauSeance(?int $niveauSeance): self { $this->niveauSeance = $niveauSeance; return $this; }

    public function getComportement(): ?string { return $this->comportement; }
    public function setComportement(string $comportement): self { $this->comportement = $comportement; return $this; }

    public function getInteractionSociale(): ?string { return $this->interactionSociale; }
    public function setInteractionSociale(string $interactionSociale): self { $this->interactionSociale = $interactionSociale; return $this; }

    public function getObservation(): ?string { return $this->observation; }
    public function setObservation(string $observation): self { $this->observation = $observation; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }

    public function getTherapie(): ?TherapieEntity { return $this->therapie; }
    public function setTherapie(?TherapieEntity $therapie): self { $this->therapie = $therapie; return $this; }

    public function getCrResume(): ?string { return $this->crResume; }
    public function setCrResume(?string $crResume): self { $this->crResume = $crResume; return $this; }

    public function getCrPdfPath(): ?string { return $this->crPdfPath; }
    public function setCrPdfPath(?string $crPdfPath): self { $this->crPdfPath = $crPdfPath; return $this; }

    public function getParentPdfUploadedAt(): ?\DateTimeInterface { return $this->parentPdfUploadedAt; }
    public function setParentPdfUploadedAt(?\DateTimeInterface $date): self { $this->parentPdfUploadedAt = $date; return $this; }
}
