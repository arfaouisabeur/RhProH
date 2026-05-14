<?php

namespace App\Entity;

use App\Repository\CandidatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CandidatRepository::class)]
#[ORM\Table(name: 'candidat')]
class Candidat
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    private ?string $userId = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $niveauEtude = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $experience = 0;

    #[ORM\OneToMany(mappedBy: 'candidat', targetEntity: Candidature::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $candidatures;

    #[ORM\ManyToMany(targetEntity: OffreEmploi::class)]
    #[ORM\JoinTable(
        name: 'candidat_offre_favori',
        joinColumns: [new ORM\JoinColumn(name: 'candidat_id', referencedColumnName: 'user_id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'offre_emploi_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    )]
    private Collection $offresFavorites;

    public function __construct()
    {
        $this->candidatures    = new ArrayCollection();
        $this->offresFavorites = new ArrayCollection();
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = (string) $userId;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        if ($user !== null) {
            $this->userId = (string) $user->getId();
        }

        return $this;
    }

    public function getNiveauEtude(): ?string
    {
        return $this->niveauEtude;
    }

    public function setNiveauEtude(?string $niveauEtude): self
    {
        $this->niveauEtude = $niveauEtude;
        return $this;
    }

    public function getExperience(): int
    {
        return $this->experience;
    }

    public function setExperience(int $experience): self
    {
        $this->experience = $experience;
        return $this;
    }

    public function getCandidatures(): Collection
    {
        return $this->candidatures;
    }

    public function addCandidature(Candidature $candidature): self
    {
        if (!$this->candidatures->contains($candidature)) {
            $this->candidatures->add($candidature);
            $candidature->setCandidat($this);
        }

        return $this;
    }

    public function removeCandidature(Candidature $candidature): self
    {
        if ($this->candidatures->removeElement($candidature)) {
            if ($candidature->getCandidat() === $this) {
                $candidature->setCandidat(null);
            }
        }

        return $this;
    }

    // ── Favoris ─────────────────────────────────────────────────────────────

    public function getOffresFavorites(): Collection
    {
        return $this->offresFavorites;
    }

    public function addOffreFavorite(OffreEmploi $offre): self
    {
        if (!$this->offresFavorites->contains($offre)) {
            $this->offresFavorites->add($offre);
        }
        return $this;
    }

    public function removeOffreFavorite(OffreEmploi $offre): self
    {
        $this->offresFavorites->removeElement($offre);
        return $this;
    }

    public function hasOffreFavorite(OffreEmploi $offre): bool
    {
        return $this->offresFavorites->contains($offre);
    }
}
