<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Repository\EventParticipationRepository;
#[ORM\Entity(repositoryClass: EventParticipationRepository::class)]
#[ORM\Table(name: "event_participation")]
class EventParticipation
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: false)]
    private string $date_inscription;

    #[ORM\Column(nullable: false)]
    private string $statut;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(name: "evenement_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Evenement $evenement = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "employe_id", referencedColumnName: "user_id")]
    private ?Employe $employe = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateInscription(): ?string
    {
        return $this->date_inscription;
    }

    public function setDateInscription(string $date_inscription): static
    {
        $this->date_inscription = $date_inscription;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

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
