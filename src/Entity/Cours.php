<?php
// src/Entity/Cours.php

namespace App\Entity;

use App\Repository\CoursRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CoursRepository::class)]
#[ORM\Table(name: 'cours')]
class Cours
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_cours', type: 'integer')]
    private ?int $idCours = null;

    #[ORM\Column(name: 'titre', type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le titre est requis")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(name: 'description', type: 'text')]
    #[Assert\NotBlank(message: "La description est requise")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit contenir au moins {{ limit }} caractères"
    )]
    private ?string $description = null;

    #[ORM\Column(name: 'type_cours', type: 'text')]
    #[Assert\NotBlank(message: "Veuillez sélectionner un type de cours")]
    #[Assert\Choice(
        choices: ['Académique', 'Social', 'Motricité', 'Langage'],
        message: "Type de cours invalide. Choisissez parmi : Académique, Social, Motricité, Langage"
    )]
    private ?string $typeCours = null;

    #[ORM\Column(name: 'niveau', type: 'string', length: 100)]
    #[Assert\NotBlank(message: "Veuillez sélectionner un niveau")]
    #[Assert\Choice(
        choices: ['Débutant', 'Intermédiaire', 'Avancé'],
        message: "Niveau invalide. Choisissez parmi : Débutant, Intermédiaire, Avancé"
    )]
    private ?string $niveau = null;

    #[ORM\Column(name: 'duree', type: 'integer')]
    #[Assert\NotBlank(message: "La durée est requise")]
    #[Assert\Type(type: 'integer', message: "La durée doit être un nombre")]
    #[Assert\Range(
        min: 1,
        max: 300,
        minMessage: "La durée doit être au moins {{ limit }} minute",
        maxMessage: "La durée ne peut pas dépasser {{ limit }} minutes"
    )]
    private ?int $duree = null;

    #[ORM\Column(name: 'image', type: 'string', length: 225)]
    private ?string $image = null;

    #[ORM\Column(name: 'mots', type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "Ajoutez au moins un mot")]
    private ?string $mots = null;

    #[ORM\Column(name: 'images_mots', type: 'text', nullable: true)]
    private ?string $imagesMots = null;

    
    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'cours', cascade: ['remove'])]
    private Collection $evaluations;

    public function __construct()
    {
        $this->evaluations = new ArrayCollection();
    }

    

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

    public function setImage(?string $image): static
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

    
    public function getMotsArray(): array
    {
        if (empty($this->mots)) {
            return [];
        }
        return array_values(array_filter(array_map(fn($m) => strtoupper(trim($m)), explode(';', $this->mots))));
    }

    /**
     * Retourne le nombre de mots
     */
    public function getMotsCount(): int
    {
        return count($this->getMotsArray());
    }

    /**
     * Retourne un tableau associatif [mot => [images]]
     * Version corrigée qui supporte les deux formats :
     * - Format normal: "MOT:image1,image2;AUTRE_MOT:image3"
     * - Format incorrect (ancien): "image1;image2;image3"
     */
    public function getImagesByMot(): array
    {
        $result = [];
        
        if (empty($this->imagesMots)) {
            return $result;
        }

        $items = explode(';', $this->imagesMots);
        $motsArray = $this->getMotsArray();
        
        // Vérifier si c'est le nouveau format (avec des ':')
        $hasNewFormat = false;
        foreach ($items as $item) {
            if (strpos($item, ':') !== false) {
                $hasNewFormat = true;
                break;
            }
        }
        
        if ($hasNewFormat) {
            // Nouveau format: MOT:image1,image2;AUTRE_MOT:image3
            foreach ($items as $item) {
                if (empty($item) || strpos($item, ':') === false) {
                    continue;
                }
                
                $parts = explode(':', $item, 2);
                $mot = strtoupper(trim($parts[0]));
                $imagesString = trim($parts[1]);
                
                if (empty($mot) || empty($imagesString)) {
                    continue;
                }
                
                $images = array_values(array_filter(array_map('trim', explode(',', $imagesString))));
                $result[$mot] = $images;
            }
        } else {
            // Ancien format (incorrect): image1;image2;image3
            // On associe chaque image au mot correspondant par index
            foreach ($items as $index => $item) {
                $image = trim($item);
                if (empty($image)) {
                    continue;
                }
                
                // Associer l'image au mot correspondant dans la même position
                if (isset($motsArray[$index])) {
                    $mot = $motsArray[$index];
                    if (!isset($result[$mot])) {
                        $result[$mot] = [];
                    }
                    $result[$mot][] = $image;
                }
            }
        }
        
        return $result;
    }

    /**
     * Récupère la première image d'un mot spécifique
     */
    public function getFirstImageForMot(string $mot): ?string
    {
        $imagesByMot = $this->getImagesByMot();
        $motKey = strtoupper(trim($mot));

        if (isset($imagesByMot[$motKey]) && !empty($imagesByMot[$motKey])) {
            return $imagesByMot[$motKey][0];
        }

        return null;
    }

    /**
     * Récupère toutes les images d'un mot spécifique
     */
    public function getImagesForMot(string $mot): array
    {
        $imagesByMot = $this->getImagesByMot();
        $motKey = strtoupper(trim($mot));

        return $imagesByMot[$motKey] ?? [];
    }
}