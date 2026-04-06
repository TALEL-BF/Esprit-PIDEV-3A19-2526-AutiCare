<?php

namespace App\Entity;

use App\Repository\ArticleEntityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleEntityRepository::class)]
#[ORM\Table(name: "article_conseille")]
class ArticleEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_article")]
    private ?int $id = null;

    #[ORM\Column(name: "titre", length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire")]
    #[Assert\Length(min: 5, minMessage: "Minimum 5 caractères")]
    private ?string $titre = null;

    #[ORM\Column(name: "contenu", type: "text")]
    #[Assert\NotBlank(message: "Le contenu est obligatoire")]
    #[Assert\Length(min: 10, minMessage: "Le contenu est trop court")]
    private ?string $contenu = null;

    #[ORM\Column(name: "categorie", length: 50)]
    #[Assert\NotBlank(message: "La catégorie est obligatoire")]
    private ?string $categorie = null;

    #[ORM\Column(name: "date_creation", type: "datetime")]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(name: "Auteur", length: 255)]
    #[Assert\NotBlank(message: "L'auteur est obligatoire")]
    private ?string $auteur = null;

    #[ORM\Column(name: "likes_count")]
    private ?int $likesCount = 0;

    #[ORM\Column(name: "auteur_image", length: 255, nullable: true)]
    private ?string $auteurImage = null;

    // ================= GETTERS & SETTERS =================

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): self { $this->titre = $titre; return $this; }

    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(string $contenu): self { $this->contenu = $contenu; return $this; }

    public function getCategorie(): ?string { return $this->categorie; }
    public function setCategorie(string $categorie): self { $this->categorie = $categorie; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(\DateTimeInterface $date): self { $this->dateCreation = $date; return $this; }

    public function getAuteur(): ?string { return $this->auteur; }
    public function setAuteur(string $auteur): self { $this->auteur = $auteur; return $this; }

    public function getLikesCount(): ?int { return $this->likesCount; }
    public function setLikesCount(int $likesCount): self { $this->likesCount = $likesCount; return $this; }

    public function getAuteurImage(): ?string { return $this->auteurImage; }
    public function setAuteurImage(?string $auteurImage): self { $this->auteurImage = $auteurImage; return $this; }
}