<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\EvenementRepository;
use App\Entity\Embeddable\Coordinates;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: false)]
    private string $titre;

    #[ORM\Column(nullable: false)]
    private string $date_debut;

    #[ORM\Column(nullable: false)]
    private string $date_fin;

    #[ORM\Column(nullable: false)]
    private string $lieu;

    #[ORM\Column(nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "rh_id", referencedColumnName: "user_id")]
    private ?RH $rh = null;

    #[ORM\Column(nullable: true)]
    private ?string $image_url = null;

    #[ORM\Embedded(class: Coordinates::class, columnPrefix: false)]
    private Coordinates $coordinates;

    /** @var Collection<int, Activite> */
    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: Activite::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $activites;

    /** @var Collection<int, Rating> */
    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: Rating::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $ratings;

    /** @var Collection<int, EventParticipation> */
    #[ORM\OneToMany(mappedBy: 'evenement', targetEntity: EventParticipation::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $participations;

    public function __construct()
    {
        $this->activites    = new ArrayCollection();
        $this->ratings      = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->coordinates  = new Coordinates();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDateDebut(): ?string { return $this->date_debut ?? null; }
    public function setDateDebut(string $date_debut): static { $this->date_debut = $date_debut; return $this; }

    public function getDateFin(): ?string { return $this->date_fin ?? null; }
    public function setDateFin(string $date_fin): static { $this->date_fin = $date_fin; return $this; }

    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(string $lieu): static { $this->lieu = $lieu; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getImageUrl(): ?string { return $this->image_url; }
    public function setImageUrl(?string $image_url): static { $this->image_url = $image_url; return $this; }

    public function getCoordinates(): Coordinates { return $this->coordinates; }
    public function setCoordinates(Coordinates $coordinates): static { $this->coordinates = $coordinates; return $this; }

    public function getLatitude(): ?string { return $this->coordinates->getLatitude() !== null ? (string) $this->coordinates->getLatitude() : null; }
    public function setLatitude(?string $latitude): static { $this->coordinates->setLatitude($latitude !== null ? (float) $latitude : null); return $this; }

    public function getLongitude(): ?string { return $this->coordinates->getLongitude() !== null ? (string) $this->coordinates->getLongitude() : null; }
    public function setLongitude(?string $longitude): static { $this->coordinates->setLongitude($longitude !== null ? (float) $longitude : null); return $this; }

    public function getRh(): ?RH { return $this->rh; }
    public function setRh(?RH $rh): static { $this->rh = $rh; return $this; }

    /** @return Collection<int, Activite> */
    public function getActivites(): Collection { return $this->activites; }

    public function addActivite(Activite $activite): static
    {
        if (!$this->activites->contains($activite)) {
            $this->activites->add($activite);
            $activite->setEvenement($this);
        }
        return $this;
    }

    public function removeActivite(Activite $activite): static
    {
        if ($this->activites->removeElement($activite)) {
            if ($activite->getEvenement() === $this) {
                $activite->setEvenement(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Rating> */
    public function getRatings(): Collection { return $this->ratings; }

    /** @return Collection<int, EventParticipation> */
    public function getParticipations(): Collection { return $this->participations; }
}