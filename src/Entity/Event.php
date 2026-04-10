<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'event')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idEvent', type: 'integer')]
    private ?int $idEvent = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le titre doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire")]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: "La description doit contenir au moins {{ limit }} caractères",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $description = null;

   #[ORM\Column(name: 'typeEvent', length: 255)]
#[Assert\NotBlank(message: "Le type d'événement est obligatoire")]
private ?string $typeEvent = null;
    
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le lieu est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: "Le lieu doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le lieu ne peut pas dépasser {{ limit }} caractères"
    )]
    private ?string $lieu = null;

    #[ORM\Column(name: 'maxParticipant', type: 'integer')]
    #[Assert\NotBlank(message: "La capacité est obligatoire")]
    #[Assert\Positive(message: "La capacité doit être un nombre positif")]
    #[Assert\LessThanOrEqual(
        value: 10000,
        message: "La capacité ne peut pas dépasser {{ limit }} participants"
    )]
    private ?int $maxParticipant = null;

    #[ORM\Column(name: 'dateDebut', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début est obligatoire")]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(name: 'dateFin', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(name: 'heureDebut', type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: "L'heure de début est obligatoire")]
    private ?\DateTimeInterface $heureDebut = null;

    #[ORM\Column(name: 'heureFin', type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $heureFin = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Image(
        maxSize: '2M',
        maxSizeMessage: "L'image ne doit pas dépasser 2MB",
        mimeTypes: ["image/jpeg", "image/png", "image/gif", "image/jpg"],
        mimeTypesMessage: "Veuillez uploader une image valide (JPG, PNG, GIF)"
    )]
    private ?string $image = null;

    #[ORM\Column(name: 'Planning', type: 'text', nullable: true)]
    private ?string $Planning = null;
    
    #[ORM\Column(name: 'status', type: 'string', length: 50, options: ['default' => 'planifie'])]
    #[Assert\Choice(
        choices: ["planifie", "ouvert", "complet", "annule"],
        message: "Statut invalide"
    )]
    private ?string $status = 'planifie';

    /**
     * @var Collection<int, Sponsor>
     */
    #[ORM\ManyToMany(targetEntity: Sponsor::class, inversedBy: 'events')]
    #[ORM\JoinTable(name: 'event_sponsor')]
    #[ORM\JoinColumn(name: 'idEvent', referencedColumnName: 'idEvent')]
    #[ORM\InverseJoinColumn(name: 'idSponsor', referencedColumnName: 'idSponsor')]
    private Collection $sponsors;

    public function __construct()
    {
        $this->sponsors = new ArrayCollection();
    }

    
    #[Assert\Callback]
    public function validateHeures(ExecutionContextInterface $context): void
    {
      
        if ($this->heureFin !== null && $this->heureDebut !== null) {
            $heureDebutTimestamp = $this->heureDebut->getTimestamp();
            $heureFinTimestamp = $this->heureFin->getTimestamp();
            
            if ($heureFinTimestamp <= $heureDebutTimestamp) {
                $context->buildViolation('L\'heure de fin doit être après l\'heure de début')
                    ->atPath('heureFin')
                    ->addViolation();
            }
        }
    }

    
    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
       
        if ($this->dateDebut !== null && $this->dateDebut < $today) {
            $context->buildViolation('La date de début ne peut pas être dans le passé')
                ->atPath('dateDebut')
                ->addViolation();
        }
        
        
        if ($this->dateFin !== null && $this->dateFin < $today) {
            $context->buildViolation('La date de fin ne peut pas être dans le passé')
                ->atPath('dateFin')
                ->addViolation();
        }
        
       
        if ($this->dateFin !== null && $this->dateDebut !== null) {
            if ($this->dateFin < $this->dateDebut) {
                $context->buildViolation('La date de fin doit être après la date de début')
                    ->atPath('dateFin')
                    ->addViolation();
            }
        }
    }

   
    #[Assert\Callback]
    public function validateDateTimeConsistency(ExecutionContextInterface $context): void
    {
      
        if ($this->dateDebut !== null && $this->dateFin !== null && $this->dateDebut == $this->dateFin) {
          
            if ($this->heureFin !== null && $this->heureDebut !== null) {
                $heureDebutTimestamp = $this->heureDebut->getTimestamp();
                $heureFinTimestamp = $this->heureFin->getTimestamp();
                
                if ($heureFinTimestamp <= $heureDebutTimestamp) {
                    $context->buildViolation('Pour le même jour, l\'heure de fin doit être après l\'heure de début')
                        ->atPath('heureFin')
                        ->addViolation();
                }
            }
        }
    }

   

    public function getIdEvent(): ?int
    {
        return $this->idEvent;
    }

    public function setIdEvent(int $idEvent): static
    {
        $this->idEvent = $idEvent;
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

    public function getTypeEvent(): ?string
    {
        return $this->typeEvent;
    }

    public function setTypeEvent(string $typeEvent): static
    {
        $this->typeEvent = $typeEvent;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getMaxParticipant(): ?int
    {
        return $this->maxParticipant;
    }

    public function setMaxParticipant(int $maxParticipant): static
    {
        $this->maxParticipant = $maxParticipant;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getHeureDebut(): ?\DateTimeInterface
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(\DateTimeInterface $heureDebut): static
    {
        $this->heureDebut = $heureDebut;
        return $this;
    }

    public function getHeureFin(): ?\DateTimeInterface
    {
        return $this->heureFin;
    }

    public function setHeureFin(?\DateTimeInterface $heureFin): static
    {
        $this->heureFin = $heureFin;
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

    public function getPlanning(): ?string
    {
        return $this->Planning;
    }

    public function setPlanning(?string $Planning): static
    {
        $this->Planning = $Planning;
        return $this;
    }
    
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getParticipantsCount(): int
    {
        return 0;
    }

    /**
     * @return Collection<int, Sponsor>
     */
    public function getSponsors(): Collection
    {
        return $this->sponsors;
    }

    public function addSponsor(Sponsor $sponsor): static
    {
        if (!$this->sponsors->contains($sponsor)) {
            $this->sponsors->add($sponsor);
            $sponsor->addEvent($this);
        }
        return $this;
    }

    public function removeSponsor(Sponsor $sponsor): static
    {
        if ($this->sponsors->removeElement($sponsor)) {
            $sponsor->removeEvent($this);
        }
        return $this;
    }
}