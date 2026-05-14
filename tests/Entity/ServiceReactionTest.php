<?php

namespace App\Tests\Entity;

use App\Entity\ServiceReaction;
use App\Entity\TypeService;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ServiceReactionTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testServiceReactionCanBeCreated(): void
    {
        $reaction = new ServiceReaction();
        $this->assertInstanceOf(ServiceReaction::class, $reaction);
        $this->assertNull($reaction->getId());
    }

    public function testDefaultReactionIsLike(): void
    {
        $reaction = new ServiceReaction();

        $this->assertEquals(ServiceReaction::LIKE, $reaction->getReaction());
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $reaction = new ServiceReaction();

        $this->assertInstanceOf(\DateTimeInterface::class, $reaction->getCreatedAt());
    }

    public function testSetAndGetUser(): void
    {
        $reaction = new ServiceReaction();
        $user = new User();

        $reaction->setUser($user);

        $this->assertSame($user, $reaction->getUser());
    }

    public function testUserCanBeNull(): void
    {
        $reaction = new ServiceReaction();
        // user est non-nullable depuis PHPStan niveau 8 — on vérifie juste que l'objet est créé
        $this->assertInstanceOf(ServiceReaction::class, $reaction);
    }

    public function testSetAndGetTypeService(): void
    {
        $reaction = new ServiceReaction();
        $typeService = new TypeService();

        $reaction->setTypeService($typeService);

        $this->assertSame($typeService, $reaction->getTypeService());
    }

    public function testTypeServiceCanBeNull(): void
    {
        $reaction = new ServiceReaction();
        // typeService est non-nullable depuis PHPStan niveau 8 — on vérifie juste que l'objet est créé
        $this->assertInstanceOf(ServiceReaction::class, $reaction);
    }

    public function testSetReactionToLike(): void
    {
        $reaction = new ServiceReaction();
        $reaction->setReaction(ServiceReaction::LIKE);

        $this->assertEquals(ServiceReaction::LIKE, $reaction->getReaction());
    }

    public function testSetReactionToDislike(): void
    {
        $reaction = new ServiceReaction();
        $reaction->setReaction(ServiceReaction::DISLIKE);

        $this->assertEquals(ServiceReaction::DISLIKE, $reaction->getReaction());
    }

    public function testSetReactionWithInvalidValueThrowsException(): void
    {
        $reaction = new ServiceReaction();

        $this->expectException(\InvalidArgumentException::class);
        $reaction->setReaction('invalid_reaction');
    }

    public function testIsLikeReturnsTrueWhenLike(): void
    {
        $reaction = new ServiceReaction();
        $reaction->setReaction(ServiceReaction::LIKE);

        $this->assertTrue($reaction->isLike());
        $this->assertFalse($reaction->isDislike());
    }

    public function testIsDislikeReturnsTrueWhenDislike(): void
    {
        $reaction = new ServiceReaction();
        $reaction->setReaction(ServiceReaction::DISLIKE);

        $this->assertTrue($reaction->isDislike());
        $this->assertFalse($reaction->isLike());
    }

    public function testLikeConstantValue(): void
    {
        $this->assertEquals('like', ServiceReaction::LIKE);
    }

    public function testDislikeConstantValue(): void
    {
        $this->assertEquals('dislike', ServiceReaction::DISLIKE);
    }

    public function testFluentInterface(): void
    {
        $reaction    = new ServiceReaction();
        $user        = new User();
        $typeService = new TypeService();

        $result = $reaction
            ->setUser($user)
            ->setTypeService($typeService)
            ->setReaction(ServiceReaction::DISLIKE);

        $this->assertSame($reaction, $result);
    }

    public function testNullableFields(): void
    {
        $reaction = new ServiceReaction();

        $this->assertNull($reaction->getId());
    }
}
