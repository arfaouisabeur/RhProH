<?php

namespace App\Entity\Embeddable;

use Doctrine\ORM\Mapping as ORM;

/**
 * Value Object regroupant latitude et longitude.
 * Utilisé comme Embeddable dans Evenement et OffreEmploi.
 * Les colonnes restent nommées `latitude` et `longitude` en base (columnPrefix: false).
 */
#[ORM\Embeddable]
class Coordinates
{
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    public function __construct(?float $latitude = null, ?float $longitude = null)
    {
        $this->latitude  = $latitude;
        $this->longitude = $longitude;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->latitude === null && $this->longitude === null;
    }
}
