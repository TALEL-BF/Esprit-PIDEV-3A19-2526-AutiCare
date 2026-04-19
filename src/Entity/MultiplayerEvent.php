<?php

namespace App\Entity;

use App\Repository\MultiplayerEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MultiplayerEventRepository::class)]
class MultiplayerEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $gameCode = null;

    #[ORM\Column]
    private ?int $player1Id = null;

    #[ORM\Column(nullable: true)]
    private ?int $player2Id = null;

    #[ORM\Column]
    private ?int $player1Score = null;

    #[ORM\Column]
    private ?int $player2Score = null;

    #[ORM\Column]
    private ?int $currentChallenge = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGameCode(): ?string
    {
        return $this->gameCode;
    }

    public function setGameCode(string $gameCode): static
    {
        $this->gameCode = $gameCode;

        return $this;
    }

    public function getPlayer1Id(): ?int
    {
        return $this->player1Id;
    }

    public function setPlayer1Id(int $player1Id): static
    {
        $this->player1Id = $player1Id;

        return $this;
    }

    public function getPlayer2Id(): ?int
    {
        return $this->player2Id;
    }

    public function setPlayer2Id(?int $player2Id): static
    {
        $this->player2Id = $player2Id;

        return $this;
    }

    public function getPlayer1Score(): ?int
    {
        return $this->player1Score;
    }

    public function setPlayer1Score(int $player1Score): static
    {
        $this->player1Score = $player1Score;

        return $this;
    }

    public function getPlayer2Score(): ?int
    {
        return $this->player2Score;
    }

    public function setPlayer2Score(int $player2Score): static
    {
        $this->player2Score = $player2Score;

        return $this;
    }

    public function getCurrentChallenge(): ?int
    {
        return $this->currentChallenge;
    }

    public function setCurrentChallenge(int $currentChallenge): static
    {
        $this->currentChallenge = $currentChallenge;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
