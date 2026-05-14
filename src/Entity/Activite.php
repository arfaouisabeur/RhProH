<?php

namespace App\Entity;

use App\Repository\ActiviteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActiviteRepository::class)]
#[ORM\Index(columns: ['evenement_id'], name: 'idx_activite_evenement')]
class Activite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: "Le titre de l'activité est obligatoire.")]
    private string $titre;

    #[ORM\Column(nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'activites', targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: "evenement_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Evenement $evenement = null;

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getEvenement(): Evenement { return $this->evenement; }
    public function setEvenement(Evenement $evenement): static { $this->evenement = $evenement; return $this; }
}