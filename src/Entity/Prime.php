<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PrimeRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: PrimeRepository::class)]
class Prime
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?string $id = null;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "Montant obligatoire")]
    #[Assert\Positive(message: "Le montant doit être positif")]
    private string $montant;

    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank(message: "Date obligatoire")]
    private string $date_attribution;

    #[ORM\Column(nullable: true)]
    #[Assert\Length(max: 255, maxMessage: "Description trop longue")]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "contract_id", referencedColumnName: "id", onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "Contrat obligatoire")]
    private ?Contract $contract = null;

    #[ORM\OneToMany(targetEntity: Tache::class, mappedBy: 'prime')]
    private Collection $taches;


    // 🔥 NO LOGIC CHANGED BELOW

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMontant(): string
    {
        return $this->montant;
    }
    public function setMontant(string $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getDateAttribution(): string
    {
        return $this->date_attribution;
    }
    public function setDateAttribution(string $date_attribution): static
    {
        $this->date_attribution = $date_attribution;
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

    public function getContract(): ?Contract
    {
        return $this->contract;
    }
    public function setContract(?Contract $contract): static
    {
        $this->contract = $contract;
        return $this;
    }
    public function __construct()
    {
        $this->taches = new ArrayCollection();
    }

    public function getTaches(): Collection
    {
        return $this->taches;
    }

    public function addTache(Tache $tache): static
    {
        if (!$this->taches->contains($tache)) {
            $this->taches->add($tache);
            $tache->setPrime($this);
        }
        return $this;
    }

    public function removeTache(Tache $tache): static
    {
        if ($this->taches->removeElement($tache)) {
            if ($tache->getPrime() === $this) {
                $tache->setPrime(null);
            }
        }
        return $this;
    }
}
