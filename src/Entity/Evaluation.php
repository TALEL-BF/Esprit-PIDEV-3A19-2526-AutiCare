<?php

namespace App\Entity;

use App\Repository\EvaluationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
#[ORM\Table(name: 'evaluation')]
class Evaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_eval', type: 'integer')]
    private ?int $idEval = null;

    #[ORM\Column(name: 'id_cours', type: 'integer')]
    private ?int $idCours = null;

    #[ORM\Column(name: 'question', type: 'text')]
    private ?string $question = null;

    #[ORM\Column(name: 'choix1', type: 'string', length: 100)]
    private ?string $choix1 = null;

    #[ORM\Column(name: 'choix2', type: 'string', length: 100)]
    private ?string $choix2 = null;

    #[ORM\Column(name: 'choix3', type: 'string', length: 100)]
    private ?string $choix3 = null;

    #[ORM\Column(name: 'bonne_reponse', type: 'string', length: 100)]
    private ?string $bonneReponse = null;

    #[ORM\Column(name: 'score', type: 'integer', options: ['default' => 1])]
    private ?int $score = 1;

    #[ORM\ManyToOne(targetEntity: Cours::class, inversedBy: 'evaluations')]
    #[ORM\JoinColumn(name: 'id_cours', referencedColumnName: 'id_cours')]
    private ?Cours $cours = null;

    // Getters et Setters

    public function getIdEval(): ?int
    {
        return $this->idEval;
    }

    public function setIdEval(int $idEval): static
    {
        $this->idEval = $idEval;
        return $this;
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

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;
        return $this;
    }

    public function getChoix1(): ?string
    {
        return $this->choix1;
    }

    public function setChoix1(string $choix1): static
    {
        $this->choix1 = $choix1;
        return $this;
    }

    public function getChoix2(): ?string
    {
        return $this->choix2;
    }

    public function setChoix2(string $choix2): static
    {
        $this->choix2 = $choix2;
        return $this;
    }

    public function getChoix3(): ?string
    {
        return $this->choix3;
    }

    public function setChoix3(string $choix3): static
    {
        $this->choix3 = $choix3;
        return $this;
    }

    public function getBonneReponse(): ?string
    {
        return $this->bonneReponse;
    }

    public function setBonneReponse(string $bonneReponse): static
    {
        $this->bonneReponse = $bonneReponse;
        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;
        return $this;
    }

    public function getCours(): ?Cours
    {
        return $this->cours;
    }

    public function setCours(?Cours $cours): static
    {
        $this->cours = $cours;
        return $this;
    }
}