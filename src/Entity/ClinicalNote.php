<?php

namespace App\Entity;

use App\Repository\ClinicalNoteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClinicalNoteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ClinicalNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'enfant_id')]
    #[Assert\NotNull(message: "L'identifiant enfant est obligatoire.")]
    #[Assert\Positive(message: "L'identifiant enfant doit etre positif.")]
    private ?int $enfantId = null;

    #[ORM\Column(name: 'session_date', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $sessionDate = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La note de seance est obligatoire.')]
    private ?string $sessionNote = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $proposedAction = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $resultAfterAction = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        if ($this->sessionDate === null) {
            $this->sessionDate = $now;
        }

        if ($this->createdAt === null) {
            $this->createdAt = $now;
        }
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

    public function getSessionDate(): ?\DateTimeImmutable
    {
        return $this->sessionDate;
    }

    public function setSessionDate(?\DateTimeImmutable $sessionDate): static
    {
        $this->sessionDate = $sessionDate;

        return $this;
    }

    public function getSessionNote(): ?string
    {
        return $this->sessionNote;
    }

    public function setSessionNote(string $sessionNote): static
    {
        $this->sessionNote = trim($sessionNote);

        return $this;
    }

    public function getProposedAction(): ?string
    {
        return $this->proposedAction;
    }

    public function setProposedAction(?string $proposedAction): static
    {
        $this->proposedAction = $proposedAction !== null ? trim($proposedAction) : null;

        return $this;
    }

    public function getResultAfterAction(): ?string
    {
        return $this->resultAfterAction;
    }

    public function setResultAfterAction(?string $resultAfterAction): static
    {
        $this->resultAfterAction = $resultAfterAction !== null ? trim($resultAfterAction) : null;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
