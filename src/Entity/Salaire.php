<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\SalaireRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SalaireRepository::class)]
class Salaire
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?string $id = null;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "Mois est obligatoire")]
    private string $mois;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "Année est obligatoire")]
    #[Assert\Regex(pattern: "/^\d{4}$/", message: "Année invalide (YYYY)")]
    private string $annee;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "Montant est obligatoire")]
    #[Assert\Positive(message: "Le montant doit être positif")]
    private string $montant;

    #[ORM\Column(nullable: true)]
    private ?string $date_paiement = null;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "Statut obligatoire")]
    private string $statut;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "contract_id", referencedColumnName: "id", onDelete: 'CASCADE')]
    private ?Contract $contract = null;

    // getters/setters unchanged 🔥

    public function getId(): ?string { return $this->id; }
    public function getMois(): string { return $this->mois; }
    public function setMois(string $mois): static { $this->mois = $mois; return $this; }
    public function getAnnee(): string { return $this->annee; }
    public function setAnnee(string $annee): static { $this->annee = $annee; return $this; }
    public function getMontant(): string { return $this->montant; }
    public function setMontant(string $montant): static { $this->montant = $montant; return $this; }
    public function getDatePaiement(): ?string { return $this->date_paiement; }
    public function setDatePaiement(?string $date_paiement): static { $this->date_paiement = $date_paiement; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }
    public function getContract(): ?Contract { return $this->contract; }
    public function setContract(?Contract $contract): static { $this->contract = $contract; return $this; }
}