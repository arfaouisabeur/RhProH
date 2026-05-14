<?php

namespace App\Entity;

use App\Repository\EmployeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmployeRepository::class)]
#[ORM\Table(name: 'employe')]
class Employe
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    private ?string $userId = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 60, unique: true)]
    private string $matricule = '';

    #[ORM\Column(type: 'string', length: 120, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $dateEmbauche;
    public function __construct()
    {
        $this->dateEmbauche = new \DateTimeImmutable();
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

    public function getMatricule(): string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): self
    {
        $this->matricule = $matricule;
        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getDateEmbauche(): \DateTimeImmutable
    {
        return $this->dateEmbauche;
    }

    public function setDateEmbauche(\DateTimeImmutable $dateEmbauche): self
    {
        $this->dateEmbauche = $dateEmbauche;
        return $this;
    }
}
