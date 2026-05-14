<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\TacheRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use \DateTimeInterface;

#[ORM\Entity(repositoryClass: TacheRepository::class)]
class Tache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?string $id = null;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Regex(
        pattern: "/\d/",
        match: false,
        message: "Le titre ne doit pas contenir de chiffres."
    )]
    private string $titre;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    private string $statut;

    #[ORM\Column(type: "text", nullable: false)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        min: 10,
        minMessage: "La description doit contenir au moins {{ limit }} caractères."
    )]
    private string $description;

    #[ORM\ManyToOne(inversedBy: 'taches')]
    #[ORM\JoinColumn(name: "projet_id", referencedColumnName: "id", nullable: true, onDelete: "CASCADE")]
    private ?Projet $projet = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "employe_id", referencedColumnName: "user_id", nullable: false)]
    #[Assert\NotBlank(message: "L'employé responsable est obligatoire.")]
    private Employe $employe;

    #[ORM\ManyToOne(inversedBy: 'taches')]
    #[ORM\JoinColumn(name: "prime_id", referencedColumnName: "id")]
    private ?Prime $prime = null;

    #[ORM\Column(type: "datetime_immutable", nullable: false)]
    #[Assert\NotBlank(message: "La date de début est obligatoire.")]
    private \DateTimeImmutable $date_debut;

    #[ORM\Column(type: "datetime_immutable", nullable: false)]
    #[Assert\NotBlank(message: "La date de fin est obligatoire.")]
    private \DateTimeImmutable $date_fin;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "La priorité est obligatoire.")]
    private string $level;

    public function __construct()
    {
        $this->date_debut = new \DateTimeImmutable();
        $this->date_fin = new \DateTimeImmutable('+1 week');
        $this->statut = 'a_faire';
        $this->level = 'moyenne';
        $this->description = '';
        $this->titre = '';
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateDebut(): \DateTimeImmutable
    {
        return $this->date_debut;
    }

    public function setDateDebut(\DateTimeImmutable $date_debut): static
    {
        $this->date_debut = $date_debut;
        return $this;
    }

    public function getDateFin(): \DateTimeImmutable
    {
        return $this->date_fin;
    }

    public function setDateFin(\DateTimeImmutable $date_fin): static
    {
        $this->date_fin = $date_fin;
        return $this;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getProjet(): ?Projet
    {
        return $this->projet;
    }

    public function setProjet(?Projet $projet): static
    {
        $this->projet = $projet;
        return $this;
    }

    public function getEmploye(): Employe
    {
        return $this->employe;
    }

    public function setEmploye(Employe $employe): static
    {
        $this->employe = $employe;
        return $this;
    }

    public function getPrime(): ?Prime
    {
        return $this->prime;
    }

    public function setPrime(?Prime $prime): static
    {
        $this->prime = $prime;
        return $this;
    }

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        $pStart = $this->projet->getDateDebut();
        $pEnd = $this->projet->getDateFin();

        // Check if task dates are within project dates
        if ($this->date_debut < $pStart) {
            $context->buildViolation('La date de début de la tâche ne peut pas être avant la date de début du projet (' . $pStart->format('d/m/Y') . ')')
                ->atPath('date_debut')
                ->addViolation();
        }

        if ($this->date_fin > $pEnd) {
            $context->buildViolation('La date de fin de la tâche ne peut pas être après la date de fin du projet (' . $pEnd->format('d/m/Y') . ')')
                ->atPath('date_fin')
                ->addViolation();
        }

        // Logical check: End must be after Start
        if ($this->date_fin < $this->date_debut) {
            $context->buildViolation('La date de fin doit être après ou égale à la date de début.')
                ->atPath('date_fin')
                ->addViolation();
        }
    }
}
