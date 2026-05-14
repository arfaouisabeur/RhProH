<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReponseRepository;

#[ORM\Entity(repositoryClass: ReponseRepository::class)]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?string $id = null;

    #[ORM\Column(nullable: false)]
    private string $decision;

    #[ORM\Column(nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "rh_id", referencedColumnName: "user_id")]
    private ?RH $rh = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "employe_id", referencedColumnName: "user_id")]
    private ?Employe $employe = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "conge_tt_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private ?CongeTt $conge_tt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "demande_service_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private ?DemandeService $demande_service = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function setDecision(string $decision): static
    {
        $this->decision = $decision;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getRh(): ?RH
    {
        return $this->rh;
    }

    public function setRh(?RH $rh): static
    {
        $this->rh = $rh;
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

    public function getCongeTt(): ?CongeTt
    {
        return $this->conge_tt;
    }

    public function setCongeTt(?CongeTt $conge_tt): static
    {
        $this->conge_tt = $conge_tt;
        return $this;
    }

    public function getDemandeService(): ?DemandeService
    {
        return $this->demande_service;
    }

    public function setDemandeService(?DemandeService $demande_service): static
    {
        $this->demande_service = $demande_service;
        return $this;
    }
}
