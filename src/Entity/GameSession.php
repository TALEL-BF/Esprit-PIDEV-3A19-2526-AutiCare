<?php

namespace App\Entity;

use App\Repository\GameSessionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameSessionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class GameSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'enfant_id')]
    #[Assert\NotNull(message: "L'identifiant enfant est obligatoire.")]
    #[Assert\Positive(message: "L'identifiant enfant doit etre positif.")]
    private ?int $enfantId = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Le titre du jeu est obligatoire.')]
    private ?string $gameTitle = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(
        choices: ['memoire', 'social', 'communication'],
        message: 'La competence doit etre memoire, social ou communication.'
    )]
    private ?string $skill = null;

    #[ORM\Column(type: 'float')]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: 'Le score doit etre entre {{ min }} et {{ max }}.'
    )]
    private ?float $score = null;

    #[ORM\Column(type: 'float')]
    #[Assert\Positive(message: 'Le score maximal doit etre positif.')]
    private ?float $maxScore = 100.0;

    #[ORM\Column(name: 'duration_seconds')]
    #[Assert\Positive(message: 'La duree doit etre positive.')]
    private ?int $durationSeconds = null;

    #[ORM\Column(name: 'played_at', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $playedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->playedAt === null) {
            $this->playedAt = new \DateTimeImmutable();
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

    public function getGameTitle(): ?string
    {
        return $this->gameTitle;
    }

    public function setGameTitle(string $gameTitle): static
    {
        $this->gameTitle = trim($gameTitle);

        return $this;
    }

    public function getSkill(): ?string
    {
        return $this->skill;
    }

    public function setSkill(string $skill): static
    {
        $this->skill = strtolower(trim($skill));

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getMaxScore(): ?float
    {
        return $this->maxScore;
    }

    public function setMaxScore(float $maxScore): static
    {
        $this->maxScore = $maxScore;

        return $this;
    }

    public function getDurationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function setDurationSeconds(int $durationSeconds): static
    {
        $this->durationSeconds = $durationSeconds;

        return $this;
    }

    public function getPlayedAt(): ?\DateTimeImmutable
    {
        return $this->playedAt;
    }

    public function setPlayedAt(?\DateTimeImmutable $playedAt): static
    {
        $this->playedAt = $playedAt;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes !== null ? trim($notes) : null;

        return $this;
    }
}
