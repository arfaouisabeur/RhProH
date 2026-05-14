<?php
 
namespace App\Entity;
 
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\ProjetRepository;
use Symfony\Component\Validator\Constraints as Assert;
use \DateTimeInterface;
 
#[ORM\Entity(repositoryClass: ProjetRepository::class)]
class Projet
{
 
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?string $id = null;
 
    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "Le titre du projet est obligatoire.")]
    #[Assert\Regex(
        pattern: "/\d/",
        match: false,
        message: "Le titre du projet ne doit pas contenir de chiffres."
    )]
    private string $titre;
 
    #[ORM\Column(nullable: false)]
    private string $statut;
 
    #[ORM\Column(nullable: true)]
    private ?string $description = null;
 
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "rh_id", referencedColumnName: "user_id", nullable: false)]
    #[Assert\NotBlank(message: "Le responsable RH est obligatoire.")]
    private RH $rh;
 
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "responsable_employe_id", referencedColumnName: "user_id")]
    private ?Employe $responsable_employe = null;
 
    #[ORM\Column(type: "datetime_immutable", nullable: false)]
    #[Assert\NotBlank(message: "La date de début est obligatoire.")]
    #[Assert\GreaterThanOrEqual(
        "today", 
        message: "La date de début doit être aujourd'hui ou une date ultérieure."
    )]
    private \DateTimeImmutable $date_debut;

    #[ORM\Column(type: "datetime_immutable", nullable: false)]
    #[Assert\NotBlank(message: "La date de fin est obligatoire.")]
    #[Assert\GreaterThan(
        propertyPath: "date_debut", 
        message: "La date de fin doit être strictement après la date de début."
    )]
    private \DateTimeImmutable $date_fin;

    #[ORM\Column(type: "boolean", options: ["default" => false])]
    private bool $isMeetingRequested = false;

    /** @var Collection<int, Tache> */
    #[ORM\OneToMany(mappedBy: 'projet', targetEntity: Tache::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $taches;

    public function __construct()
    {
        $this->date_debut = new \DateTimeImmutable();
        $this->date_fin = new \DateTimeImmutable('+1 month');
        $this->statut = 'en_attente';
        $this->titre = '';
        $this->taches = new ArrayCollection();
    }
 
    public function getId(): ?string
    {
        return $this->id;
    }
 
    public function getTitre(): ?string
    {
        return $this->titre;
    }
 
    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
 
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
 
    public function getDescription(): ?string
    {
        return $this->description;
    }
 
    public function setDescription(?string $description): static
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
 
    public function getRh(): ?RH
    {
        return $this->rh;
    }
 
    public function setRh(RH $rh): static
    {
        $this->rh = $rh;
 
        return $this;
    }
 
    public function getResponsableEmploye(): ?Employe
    {
        return $this->responsable_employe;
    }
 
    public function setResponsableEmploye(?Employe $responsable_employe): static
    {
        $this->responsable_employe = $responsable_employe;
 
        return $this;
    }

    public function isMeetingRequested(): bool
    {
        return $this->isMeetingRequested;
    }

    public function setIsMeetingRequested(bool $isMeetingRequested): static
    {
        $this->isMeetingRequested = $isMeetingRequested;

        return $this;
    }

    /**
     * @return Collection<int, Tache>
     */
    public function getTaches(): Collection
    {
        return $this->taches;
    }

    public function addTache(Tache $tache): static
    {
        if (!$this->taches->contains($tache)) {
            $this->taches->add($tache);
            $tache->setProjet($this);
        }

        return $this;
    }

    public function removeTache(Tache $tache): static
    {
        if ($this->taches->removeElement($tache)) {
            // set the owning side to null (unless already changed)
            if ($tache->getProjet() === $this) {
                $tache->setProjet(null);
            }
        }

        return $this;
    }
 
}
 