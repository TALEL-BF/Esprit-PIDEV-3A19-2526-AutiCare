<?php

namespace App\Entity;

use App\Repository\NiveauJeuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: NiveauJeuRepository::class)]
class NiveauJeu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: "Le niveau est obligatoire.")]
    #[Assert\Choice(
        choices: ['FACILE', 'MOYEN', 'DIFFICILE'],
        message: "Le niveau doit être FACILE, MOYEN ou DIFFICILE."
    )]
    private ?string $libelle = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: "La moyenne minimale est obligatoire.")]
    #[Assert\Type(type: 'numeric', message: "La moyenne minimale doit être un nombre.")]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: "La moyenne minimale doit être entre {{ min }} et {{ max }}."
    )]
    private ?float $minMoyenne = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: "La moyenne maximale est obligatoire.")]
    #[Assert\Type(type: 'numeric', message: "La moyenne maximale doit être un nombre.")]
    #[Assert\Range(
        min: 0,
        max: 20,
        notInRangeMessage: "La moyenne maximale doit être entre {{ min }} et {{ max }}."
    )]
    private ?float $maxMoyenne = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: "La description doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La description ne doit pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'niveauJeu', targetEntity: SuiviTotal::class)]
    private Collection $suiviTotals;

    public function __construct()
    {
        $this->suiviTotals = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validateMoyennes(ExecutionContextInterface $context): void
    {
        if ($this->minMoyenne !== null && $this->maxMoyenne !== null) {
            if ($this->maxMoyenne < $this->minMoyenne) {
                $context->buildViolation('La moyenne maximale doit être supérieure ou égale à la moyenne minimale.')
                    ->atPath('maxMoyenne')
                    ->addViolation();
            }
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = strtoupper(trim($libelle));
        return $this;
    }

    public function getMinMoyenne(): ?float
    {
        return $this->minMoyenne;
    }

    public function setMinMoyenne(float $minMoyenne): static
    {
        $this->minMoyenne = $minMoyenne;
        return $this;
    }

    public function getMaxMoyenne(): ?float
    {
        return $this->maxMoyenne;
    }

    public function setMaxMoyenne(float $maxMoyenne): static
    {
        $this->maxMoyenne = $maxMoyenne;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = trim($description);
        return $this;
    }

    /**
     * @return Collection<int, SuiviTotal>
     */
    public function getSuiviTotals(): Collection
    {
        return $this->suiviTotals;
    }

    public function addSuiviTotal(SuiviTotal $suiviTotal): static
    {
        if (!$this->suiviTotals->contains($suiviTotal)) {
            $this->suiviTotals->add($suiviTotal);
            $suiviTotal->setNiveauJeu($this);
        }

        return $this;
    }

    public function removeSuiviTotal(SuiviTotal $suiviTotal): static
    {
        if ($this->suiviTotals->removeElement($suiviTotal)) {
            if ($suiviTotal->getNiveauJeu() === $this) {
                $suiviTotal->setNiveauJeu(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle ?? '';
    }
}