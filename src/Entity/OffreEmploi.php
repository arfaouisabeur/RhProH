<?php

namespace App\Entity;

use App\Repository\OffreEmploiRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Entity\Embeddable\Coordinates;

#[ORM\Entity(repositoryClass: OffreEmploiRepository::class)]
class OffreEmploi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(
        min: 3,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        max: 255,
        maxMessage: 'Le titre ne doit pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(length: 255, nullable: false)]
    private string $titre = '';

    #[Assert\NotBlank(message: 'La localisation est obligatoire.')]
    #[ORM\Column(length: 255, nullable: false)]
    private string $localisation = '';

    #[Assert\NotBlank(message: 'Le type de contrat est obligatoire.')]
    #[ORM\Column(length: 100, nullable: false)]
    private string $typeContrat = '';

    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[ORM\Column(length: 100, nullable: false)]
    private string $statut = '';

    #[Assert\NotNull(message: "La date de publication est obligatoire.")]
    #[ORM\Column(type: 'date_immutable', nullable: false)]
    private \DateTimeImmutable $datePublication;

    #[Assert\NotNull(message: "La date d'expiration est obligatoire.")]
    #[ORM\Column(type: 'date_immutable', nullable: false)]
    private \DateTimeImmutable $dateExpiration;

    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(
        min: 10,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères.'
    )]
    #[ORM\Column(type: 'text', nullable: false)]
    private string $description = '';

    #[ORM\ManyToOne(targetEntity: RH::class)]
    #[ORM\JoinColumn(name: 'rh_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private RH $rh;

    /**
     * @var Collection<int, Candidature>
     */
    #[ORM\OneToMany(mappedBy: 'offreEmploi', targetEntity: Candidature::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $candidatures;

    #[ORM\Embedded(class: Coordinates::class, columnPrefix: false)]
    private Coordinates $coordinates;

    public function __construct()
    {
        $this->candidatures = new ArrayCollection();
        $this->coordinates  = new Coordinates();
    }

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->dateExpiration < $this->datePublication) {
            $context->buildViolation("La date d'expiration doit être postérieure à la date de publication.")
                ->atPath('dateExpiration')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre($titre): static
    {
        $this->titre = (string) ($titre ?? '');
        return $this;
    }

    public function getLocalisation(): string
    {
        return $this->localisation;
    }

    public function setLocalisation($localisation): static
    {
        $this->localisation = (string) ($localisation ?? '');
        return $this;
    }

    public function getTypeContrat(): string
    {
        return $this->typeContrat;
    }

    public function setTypeContrat($typeContrat): static
    {
        $this->typeContrat = (string) ($typeContrat ?? '');
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut($statut): static
    {
        $this->statut = (string) ($statut ?? '');
        return $this;
    }

    public function getDatePublication(): \DateTimeImmutable
    {
        return $this->datePublication;
    }

    public function setDatePublication(\DateTimeImmutable $datePublication): static
    {
        $this->datePublication = $datePublication;
        return $this;
    }

    public function getDateExpiration(): \DateTimeImmutable
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(\DateTimeImmutable $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription($description): static
    {
        $this->description = (string) ($description ?? '');
        return $this;
    }

    public function getRh(): RH
    {
        return $this->rh;
    }

    public function setRh(RH $rh): static
    {
        $this->rh = $rh;
        return $this;
    }

    /**
     * @return Collection<int, Candidature>
     */
    public function getCandidatures(): Collection
    {
        return $this->candidatures;
    }

    public function addCandidature(Candidature $candidature): static
    {
        if (!$this->candidatures->contains($candidature)) {
            $this->candidatures->add($candidature);
            $candidature->setOffreEmploi($this);
        }

        return $this;
    }

    public function removeCandidature(Candidature $candidature): static
    {
        if ($this->candidatures->removeElement($candidature)) {
            if ($candidature->getOffreEmploi() === $this) {
                // Note: This will cause an error since offreEmploi is now required
                // You may need to handle this differently in your business logic
            }
        }

        return $this;
    }

    public function getCoordinates(): Coordinates { return $this->coordinates; }
    public function setCoordinates(Coordinates $coordinates): static { $this->coordinates = $coordinates; return $this; }

    public function getLatitude(): ?float { return $this->coordinates->getLatitude(); }
    public function setLatitude(?float $latitude): static { $this->coordinates->setLatitude($latitude); return $this; }

    public function getLongitude(): ?float { return $this->coordinates->getLongitude(); }
    public function setLongitude(?float $longitude): static { $this->coordinates->setLongitude($longitude); return $this; }
}
