<?php

namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoursRepository::class)]
#[ORM\Table(name: 'cours')]
class Cours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_cours', type: 'integer')]
    private ?int $idCours = null;

    #[ORM\Column(name: 'titre', type: 'string', length: 255)]
    private ?string $titre = null;

    #[ORM\Column(name: 'description', type: 'text')]
    private ?string $description = null;

    #[ORM\Column(name: 'type_cours', type: 'text')]
    private ?string $typeCours = null;

    #[ORM\Column(name: 'niveau', type: 'string', length: 100)]
    private ?string $niveau = null;

    #[ORM\Column(name: 'duree', type: 'integer')]
    private ?int $duree = null;

    #[ORM\Column(name: 'image', type: 'string', length: 225)]
    private ?string $image = null;

    #[ORM\Column(name: 'mots', type: 'text', nullable: true)]
    private ?string $mots = null;

    #[ORM\Column(name: 'images_mots', type: 'text', nullable: true)]
    private ?string $imagesMots = null;

    /**
     * @var Collection<int, Evaluation>
     */
    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'cours', cascade: ['remove'])]
    private Collection $evaluations;

    public function __construct()
    {
        $this->evaluations = new ArrayCollection();
    }

    // Getters et Setters

    public function getIdCours(): ?int
    {
        return $this->idCours;
    }

    public function setIdCours(int $idCours): static
    {
        $this->idCours = $idCours;
        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
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

    public function getTypeCours(): ?string
    {
        return $this->typeCours;
    }

    public function setTypeCours(string $typeCours): static
    {
        $this->typeCours = $typeCours;
        return $this;
    }

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;
        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function getMots(): ?string
    {
        return $this->mots;
    }

    public function setMots(?string $mots): static
    {
        $this->mots = $mots;
        return $this;
    }

    public function getImagesMots(): ?string
    {
        return $this->imagesMots;
    }

    public function setImagesMots(?string $imagesMots): static
    {
        $this->imagesMots = $imagesMots;
        return $this;
    }

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): static
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setCours($this);
        }
        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): static
    {
        if ($this->evaluations->removeElement($evaluation)) {
            if ($evaluation->getCours() === $this) {
                $evaluation->setCours(null);
            }
        }
        return $this;
    }
}