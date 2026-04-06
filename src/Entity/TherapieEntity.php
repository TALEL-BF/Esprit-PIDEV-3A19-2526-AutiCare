<?php

namespace App\Entity;

use App\Repository\TherapieEntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TherapieEntityRepository::class)]
#[ORM\Table(name: "therapie")]
class TherapieEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "ID_THERAPIE")]
    private ?int $id = null;

    #[Assert\NotBlank(message: "Le nom de l'exercice est obligatoire")]
    #[Assert\Length(min: 3, max: 255)]
    #[ORM\Column(name: "NOM_EXERCICE", length: 255)]
    private ?string $nomExercice = null;

    #[Assert\NotBlank(message: "Le type est obligatoire")]
    #[ORM\Column(name: "TYPE_EXERCICE", length: 255)]
    private ?string $typeExercice = null;

    #[Assert\NotBlank(message: "L'objectif est obligatoire")]
    #[ORM\Column(name: "OBJECTIF", length: 255)]
    private ?string $objectif = null;

    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[ORM\Column(name: "DESCRIPTION", length: 255)]
    private ?string $description = null;

    #[Assert\NotBlank(message: "La durée est obligatoire")]
    #[Assert\Positive(message: "La durée doit être positive")]
    #[ORM\Column(name: "DUREE_MIN")]
    private ?int $dureeMin = null;

    #[Assert\NotBlank(message: "Le matériel est obligatoire")]
    #[ORM\Column(name: "MATERIEL", length: 2550)]
    private ?string $materiel = null;

    #[Assert\NotBlank(message: "L'adaptation TSA est obligatoire")]
    #[ORM\Column(name: "ADAPTATION_TSA", length: 255)]
    private ?string $adaptationTsa = null;

    #[Assert\NotBlank(message: "La cible est obligatoire")]
    #[ORM\Column(name: "CIBLE", length: 30)]
    private ?string $cible = null;

    #[Assert\NotBlank(message: "Niveau humeur requis")]
    #[ORM\Column(name: "NIVEAUX_HUMEUR", length: 255)]
    private ?string $niveauxHumeur = null;

    #[Assert\NotBlank(message: "Niveau attention requis")]
    #[ORM\Column(name: "NIVEAUX_ATTENTION", length: 255)]
    private ?string $niveauxAttention = null;

    #[Assert\NotBlank(message: "Niveau stress requis")]
    #[ORM\Column(name: "NIVEAUX_STRESSE", length: 255)]
    private ?string $niveauxStresse = null;

    #[Assert\NotBlank(message: "Comportement requis")]
    #[ORM\Column(name: "COMPORTEMENT", length: 255)]
    private ?string $comportement = null;

    #[Assert\NotBlank(message: "Interaction requise")]
    #[ORM\Column(name: "INTERACTION", length: 255)]
    private ?string $interaction = null;

    #[Assert\NotBlank(message: "Le niveau est obligatoire")]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: "Le niveau doit être entre 1 et 5"
    )]
    #[ORM\Column(name: "NIVEAU")]
    private ?int $niveau = null;

    // ================= GETTERS & SETTERS =================

    public function getId(): ?int { return $this->id; }

    public function getNomExercice(): ?string { return $this->nomExercice; }
    public function setNomExercice(string $nomExercice): self { $this->nomExercice = $nomExercice; return $this; }

    public function getTypeExercice(): ?string { return $this->typeExercice; }
    public function setTypeExercice(string $typeExercice): self { $this->typeExercice = $typeExercice; return $this; }

    public function getObjectif(): ?string { return $this->objectif; }
    public function setObjectif(string $objectif): self { $this->objectif = $objectif; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }

    public function getDureeMin(): ?int { return $this->dureeMin; }
    public function setDureeMin(int $dureeMin): self { $this->dureeMin = $dureeMin; return $this; }

    public function getMateriel(): ?string { return $this->materiel; }
    public function setMateriel(string $materiel): self { $this->materiel = $materiel; return $this; }

    public function getAdaptationTsa(): ?string { return $this->adaptationTsa; }
    public function setAdaptationTsa(string $adaptationTsa): self { $this->adaptationTsa = $adaptationTsa; return $this; }

    public function getCible(): ?string { return $this->cible; }
    public function setCible(string $cible): self { $this->cible = $cible; return $this; }

    public function getNiveauxHumeur(): ?string { return $this->niveauxHumeur; }
    public function setNiveauxHumeur(string $niveauxHumeur): self { $this->niveauxHumeur = $niveauxHumeur; return $this; }

    public function getNiveauxAttention(): ?string { return $this->niveauxAttention; }
    public function setNiveauxAttention(string $niveauxAttention): self { $this->niveauxAttention = $niveauxAttention; return $this; }

    public function getNiveauxStresse(): ?string { return $this->niveauxStresse; }
    public function setNiveauxStresse(string $niveauxStresse): self { $this->niveauxStresse = $niveauxStresse; return $this; }

    public function getComportement(): ?string { return $this->comportement; }
    public function setComportement(string $comportement): self { $this->comportement = $comportement; return $this; }

    public function getInteraction(): ?string { return $this->interaction; }
    public function setInteraction(string $interaction): self { $this->interaction = $interaction; return $this; }

    public function getNiveau(): ?int { return $this->niveau; }
    public function setNiveau(int $niveau): self { $this->niveau = $niveau; return $this; }
}