<?php

namespace App\Entity;

use App\Repository\ServiceReactionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceReactionRepository::class)]
#[ORM\Table(name: 'service_reaction')]
#[ORM\UniqueConstraint(name: 'unique_user_type', columns: ['user_id', 'type_service_id'])]
class ServiceReaction
{
    public const LIKE    = 'like';
    public const DISLIKE = 'dislike';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /** L'utilisateur qui a réagi */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** Le type de service concerné */
    #[ORM\ManyToOne(targetEntity: TypeService::class)]
    #[ORM\JoinColumn(name: 'type_service_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private TypeService $typeService;

    /** 'like' ou 'dislike' */
    #[ORM\Column(type: 'string', length: 10)]
    private string $reaction = self::LIKE;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: false)]
    private User $createdBy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'updated_by_id', referencedColumnName: 'id', nullable: true)]
    private ?User $updatedBy = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ── Getters / Setters ────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTypeService(): ?TypeService
    {
        return $this->typeService;
    }

    public function setTypeService(TypeService $typeService): static
    {
        $this->typeService = $typeService;
        return $this;
    }

    public function getReaction(): string
    {
        return $this->reaction;
    }

    public function setReaction(string $reaction): static
    {
        if (!in_array($reaction, [self::LIKE, self::DISLIKE], true)) {
            throw new \InvalidArgumentException('Réaction invalide : ' . $reaction);
        }
        $this->reaction = $reaction;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): static
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function isLike(): bool
    {
        return $this->reaction === self::LIKE;
    }

    public function isDislike(): bool
    {
        return $this->reaction === self::DISLIKE;
    }
}
