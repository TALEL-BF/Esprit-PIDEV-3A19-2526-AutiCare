<?php

namespace App\Entity;

use App\Repository\EvaluationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]
#[ORM\Table(name: 'evaluation')]
class Evaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_eval', type: 'integer')]
    private ?int $idEval = null;

    #[ORM\Column(name: 'id_cours', type: 'integer')]
    #[Assert\NotBlank(message: 'Veuillez sélectionner un cours')]
    #[Assert\Positive(message: 'L\'ID du cours doit être valide')]
    private ?int $idCours = null;

    #[ORM\Column(name: 'question', type: 'text')]
    #[Assert\NotBlank(message: 'La question ne peut pas être vide')]
    #[Assert\Length(
        min: 10,
        max: 500,
        minMessage: 'La question doit contenir au moins {{ limit }} caractères',
        maxMessage: 'La question ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $question = null;

    #[ORM\Column(name: 'choix1', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le choix 1 ne peut pas être vide')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le choix 1 ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $choix1 = null;

    #[ORM\Column(name: 'choix2', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le choix 2 ne peut pas être vide')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le choix 2 ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $choix2 = null;

    #[ORM\Column(name: 'choix3', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le choix 3 ne peut pas être vide')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le choix 3 ne peut pas dépasser {{ limit }} caractères'
    )]
    private ?string $choix3 = null;

    #[ORM\Column(name: 'bonne_reponse', type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Veuillez sélectionner la bonne réponse')]
    #[Assert\Choice(
        choices: ['choix1', 'choix2', 'choix3'],
        message: 'La bonne réponse doit être l\'un des choix proposés'
    )]
    private ?string $bonneReponse = null;

    #[ORM\Column(name: 'score', type: 'integer', options: ['default' => 1])]
    #[Assert\NotBlank(message: 'Le score est requis')]
    #[Assert\Positive(message: 'Le score doit être un nombre positif')]
    #[Assert\Range(
        min: 1,
        max: 100,
        notInRangeMessage: 'Le score doit être compris entre {{ min }} et {{ max }} points'
    )]
    private ?int $score = 1;

    #[ORM\ManyToOne(targetEntity: Cours::class, inversedBy: 'evaluations')]
    #[ORM\JoinColumn(name: 'id_cours', referencedColumnName: 'id_cours')]
    private ?Cours $cours = null;

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