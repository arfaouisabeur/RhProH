<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\CongeTtRepository;

#[ORM\Entity(repositoryClass: CongeTtRepository::class)]
#[ORM\Table(name: "conge_tt")]
class CongeTt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $type_conge = '';

    #[ORM\Column(type: "date_immutable")]
    private \DateTimeImmutable $date_debut;

    #[ORM\Column(type: "date_immutable")]
    private \DateTimeImmutable $date_fin;

    #[ORM\Column(length: 50)]
    private string $statut = '';

    #[ORM\Column(nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "employe_id", referencedColumnName: "user_id", nullable: false)]
    private ?Employe $employe = null;

    #[ORM\Column(nullable: true)]
    private ?string $document_path = null;

    #[ORM\Column(type: "boolean", nullable: true)]
    private ?bool $ocr_verified = null;

    // ===== GETTERS & SETTERS =====

    public function getId(): ?int { return $this->id; }

    public function getTypeConge(): string { return $this->type_conge; }
    public function setTypeConge(string $type_conge): static { $this->type_conge = $type_conge; return $this; }

    public function getDateDebut(): \DateTimeImmutable { return $this->date_debut; }
    public function setDateDebut(\DateTimeImmutable $date_debut): static { $this->date_debut = $date_debut; return $this; }

    public function getDateFin(): \DateTimeImmutable { return $this->date_fin; }
    public function setDateFin(\DateTimeImmutable $date_fin): static { $this->date_fin = $date_fin; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getEmploye(): ?Employe { return $this->employe; }
    public function setEmploye(Employe $employe): static { $this->employe = $employe; return $this; }

    public function getDocumentPath(): ?string { return $this->document_path; }
    public function setDocumentPath(?string $document_path): static { $this->document_path = $document_path; return $this; }

    public function getOcrVerified(): ?bool { return $this->ocr_verified; }
    public function setOcrVerified(?bool $ocr_verified): static { $this->ocr_verified = $ocr_verified; return $this; }
}