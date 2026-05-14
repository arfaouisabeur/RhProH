<?php

namespace App\Tests\Repository;

use App\Entity\Candidature;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CandidatureRepositoryTest extends KernelTestCase
{
    public function testCanFindAllCandidatures(): void
    {
        self::bootKernel();

        $repo = static::getContainer()
            ->get('doctrine')
            ->getRepository(Candidature::class);

        $result = $repo->findAll();

        $this->assertIsArray($result);
    }

    public function testCanFindCandidaturesByStatut(): void
    {
        self::bootKernel();

        $repo = static::getContainer()
            ->get('doctrine')
            ->getRepository(Candidature::class);

        $result = $repo->findBy(['statut' => 'en_attente']);

        $this->assertIsArray($result);
    }
}
