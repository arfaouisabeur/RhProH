<?php

namespace App\Entity;

use App\Repository\RHRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RHRepository::class)]
#[ORM\Table(name: 'rh')]
class RH
{
    #[ORM\Id]
    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
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
            $this->userId = $user->getId();
        }
        return $this;
    }
}
