<?php

namespace App\Tests\Repository;

use App\Entity\OffreEmploi;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OffreEmploiRepositoryTest extends KernelTestCase
{
    public function testCanFindAllOffres(): void
    {
        self::bootKernel();

        $repo = static::getContainer()
            ->get('doctrine')
            ->getRepository(OffreEmploi::class);

        $result = $repo->findAll();

        $this->assertIsArray($result);
    }

    public function testCanFindOffresByStatut(): void
    {
        self::bootKernel();

        $repo = static::getContainer()
            ->get('doctrine')
            ->getRepository(OffreEmploi::class);

        $result = $repo->findBy(['statut' => 'Ouverte']);

        $this->assertIsArray($result);
    }
}
