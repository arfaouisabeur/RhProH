<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Repository\RatingRepository;

#[ORM\Entity(repositoryClass: RatingRepository::class)]
class Rating
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'ratings')]
    #[ORM\JoinColumn(name: "evenement_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Evenement $evenement = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "employe_id", referencedColumnName: "user_id")]
    private ?Employe $employe = null;

    #[ORM\Column(nullable: false)]
    private string $commentaire;

    #[ORM\Column(nullable: false)]
    private string $etoiles;

    #[ORM\Column(nullable: false)]
    private string $date_creation;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCommentaire(): string
    {
        return $this->commentaire;
    }

    public function setCommentaire(string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getEtoiles(): string
    {
        return $this->etoiles;
    }

    public function setEtoiles(string $etoiles): static
    {
        $this->etoiles = $etoiles;

        return $this;
    }

    public function getDateCreation(): string
    {
        return $this->date_creation;
    }

    public function setDateCreation(string $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    public function getEvenement(): Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(Evenement $evenement): static
    {
        $this->evenement = $evenement;

        return $this;
    }

    public function getEmploye(): ?Employe
    {
        return $this->employe;
    }

    public function setEmploye(?Employe $employe): static
    {
        $this->employe = $employe;

        return $this;
    }

}
