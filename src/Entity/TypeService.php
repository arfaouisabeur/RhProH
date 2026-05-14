<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TypeServiceRepository;

#[ORM\Entity(repositoryClass: TypeServiceRepository::class)]
#[ORM\Table(name: "type_service")]
class TypeService
{
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: 'bigint')]
private ?string $id = null;

    #[ORM\Column(nullable: false)]
    private string $nom;

    #[ORM\Column(nullable: false)]
    private string $categorie;

    #[ORM\Column(nullable: true)]
    private ?string $description = null;

    /**
     * @var Collection<int, DemandeService>
     */
    #[ORM\OneToMany(mappedBy: 'type', targetEntity: DemandeService::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $demandeServices;

    public function __construct()
    {
        $this->demandeServices = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): static
    {
        $this->categorie = $categorie;

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

    /**
     * @return Collection<int, DemandeService>
     */
    public function getDemandeServices(): Collection
    {
        return $this->demandeServices;
    }

}
