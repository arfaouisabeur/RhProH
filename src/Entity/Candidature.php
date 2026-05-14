<?php

namespace App\Entity;

use App\Repository\CandidatureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CandidatureRepository::class)]
#[ORM\Table(name: 'candidature')]
class Candidature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?string $id = null;

    #[Assert\NotNull(message: 'La date de candidature est obligatoire.')]
    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateCandidature = null;

    #[Assert\NotBlank(message: 'Le statut est obligatoire.')]
    #[ORM\Column(type: 'string', length: 20, nullable: false)]
    private string $statut = '';

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $cvPath = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $cvOriginalName = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?string $cvSize = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cvUploadedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $matchScore = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $matchUpdatedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $cvSkills = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aiAnalysis = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $signatureRequestId = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $contractStatus = null;

    #[Assert\NotNull(message: 'Le candidat est obligatoire.')]
    #[ORM\ManyToOne(targetEntity: Candidat::class, inversedBy: 'candidatures')]
    #[ORM\JoinColumn(name: 'candidat_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private Candidat $candidat;

    #[Assert\NotNull(message: "L'offre est obligatoire.")]
    #[ORM\ManyToOne(targetEntity: OffreEmploi::class, inversedBy: 'candidatures')]
    #[ORM\JoinColumn(name: 'offre_emploi_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private OffreEmploi $offreEmploi;

    // ─── NOUVEAUX CHAMPS ────────────────────────────────────────────────────────

    #[Assert\NotBlank(message: 'La lettre de motivation est obligatoire.')]
    #[Assert\Length(
        min: 50,
        max: 1500,
        minMessage: 'La lettre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La lettre ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lettreMotivation = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $disponibilite = null;

    #[Assert\Positive(message: 'La prétention salariale doit être un nombre positif.')]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $pretentionSalariale = null;

    // ─── GETTERS / SETTERS ──────────────────────────────────────────────────────

    public function getLettreMotivation(): ?string
    {
        return $this->lettreMotivation;
    }

    public function setLettreMotivation(?string $lettreMotivation): static
    {
        $this->lettreMotivation = $lettreMotivation;
        return $this;
    }

    public function getDisponibilite(): ?string
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(?string $disponibilite): static
    {
        $this->disponibilite = $disponibilite;
        return $this;
    }

    public function getPretentionSalariale(): ?int
    {
        return $this->pretentionSalariale;
    }

    public function setPretentionSalariale(?int $pretentionSalariale): static
    {
        $this->pretentionSalariale = $pretentionSalariale;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDateCandidature(): ?\DateTimeImmutable
    {
        return $this->dateCandidature;
    }

    public function setDateCandidature(?\DateTimeImmutable $dateCandidature): static
    {
        $this->dateCandidature = $dateCandidature;
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

    public function getCvPath(): ?string
    {
        if ($this->cvPath === null) {
            return null;
        }
        
        // Nettoyer le chemin s'il contient un chemin absolu
        $cleanPath = $this->cvPath;
        
        // Si le chemin contient des séparateurs de répertoire, extraire seulement le nom du fichier
        if (strpos($cleanPath, '/') !== false || strpos($cleanPath, '\\') !== false) {
            $cleanPath = basename($cleanPath);
            
            // Mettre à jour la valeur en base pour éviter de refaire le nettoyage à chaque fois
            $this->cvPath = $cleanPath;
        }
        
        return $cleanPath;
    }

    public function setCvPath(?string $cvPath): static
    {
        if ($cvPath !== null) {
            // S'assurer qu'on ne stocke que le nom du fichier, pas le chemin complet
            $cvPath = basename($cvPath);
        }
        
        $this->cvPath = $cvPath;
        return $this;
    }

    public function getCvOriginalName(): ?string
    {
        return $this->cvOriginalName;
    }

    public function setCvOriginalName(?string $cvOriginalName): static
    {
        $this->cvOriginalName = $cvOriginalName;
        return $this;
    }

    public function getCvSize(): ?string
    {
        return $this->cvSize;
    }

    public function setCvSize(?string $cvSize): static
    {
        $this->cvSize = $cvSize;
        return $this;
    }

    public function getCvUploadedAt(): ?\DateTimeImmutable
    {
        return $this->cvUploadedAt;
    }

    public function setCvUploadedAt(?\DateTimeImmutable $cvUploadedAt): static
    {
        $this->cvUploadedAt = $cvUploadedAt;
        return $this;
    }

    public function getMatchScore(): ?int
    {
        return $this->matchScore;
    }

    public function setMatchScore(?int $matchScore): static
    {
        $this->matchScore = $matchScore;
        return $this;
    }

    public function getMatchUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->matchUpdatedAt;
    }

    public function setMatchUpdatedAt(?\DateTimeImmutable $matchUpdatedAt): static
    {
        $this->matchUpdatedAt = $matchUpdatedAt;
        return $this;
    }

    public function getCvSkills(): ?string
    {
        return $this->cvSkills;
    }

    public function setCvSkills(?string $cvSkills): static
    {
        $this->cvSkills = $cvSkills;
        return $this;
    }

    public function getAiAnalysis(): ?string
    {
        return $this->aiAnalysis;
    }

    public function setAiAnalysis(?string $aiAnalysis): static
    {
        $this->aiAnalysis = $aiAnalysis;
        return $this;
    }

    public function getSignatureRequestId(): ?string
    {
        return $this->signatureRequestId;
    }

    public function setSignatureRequestId(?string $signatureRequestId): static
    {
        $this->signatureRequestId = $signatureRequestId;
        return $this;
    }

    public function getContractStatus(): ?string
    {
        return $this->contractStatus;
    }

    public function setContractStatus(?string $contractStatus): static
    {
        $this->contractStatus = $contractStatus;
        return $this;
    }

    public function getCandidat(): Candidat
    {
        return $this->candidat;
    }

    public function setCandidat(Candidat $candidat): static
    {
        $this->candidat = $candidat;
        return $this;
    }

    public function getOffreEmploi(): OffreEmploi
    {
        return $this->offreEmploi;
    }

    public function setOffreEmploi(OffreEmploi $offreEmploi): static
    {
        $this->offreEmploi = $offreEmploi;
        return $this;
    }
}
