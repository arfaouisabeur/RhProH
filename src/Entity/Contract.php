<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ContractRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Entity\RH;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
class Contract
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "employe_id", referencedColumnName: "user_id", onDelete: "CASCADE")]
    private ?Employe $employe = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "rh_id", referencedColumnName: "user_id", onDelete: "CASCADE")]
    private ?RH $rh = null;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "Start date is required")]
    private string $date_debut;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "End date is required")]
    private ?string $date_fin = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "Type is required")]
    #[Assert\Length(min: 3, minMessage: "Type must be at least 3 characters")]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "Status is required")]
    private ?string $statut = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: "Salary is required")]
    #[Assert\Positive(message: "Salary must be positive")]
    private ?string $salaire_base = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Length(max: 255, maxMessage: "Description too long")]
    private ?string $description = null;

    // 🔥 CUSTOM VALIDATION (DATE CHECK)
    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->date_debut && $this->date_fin) {
            $start = new \DateTime($this->date_debut);
            $end = new \DateTime($this->date_fin);

            if ($end <= $start) {
                $context->buildViolation('End date must be greater than start date')
                    ->atPath('date_fin')
                    ->addViolation();
            }
        }
    }

    public function getId(): ?int { return $this->id; }
    public function getDateDebut(): ?string { return $this->date_debut; }
    public function setDateDebut(string $date_debut): static { $this->date_debut = $date_debut; return $this; }
    public function getDateFin(): ?string { return $this->date_fin; }
    public function setDateFin(?string $date_fin): static { $this->date_fin = $date_fin; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): static { $this->type = $type; return $this; }
    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(?string $statut): static { $this->statut = $statut; return $this; }
    public function getSalaireBase(): ?string { return $this->salaire_base; }
    public function setSalaireBase(?string $salaire_base): static { $this->salaire_base = $salaire_base; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getEmploye(): ?Employe { return $this->employe; }
    public function setEmploye(?Employe $employe): static { $this->employe = $employe; return $this; }
    public function getRh(): ?RH { return $this->rh; }
    public function setRh(?RH $rh): static { $this->rh = $rh; return $this; }
}