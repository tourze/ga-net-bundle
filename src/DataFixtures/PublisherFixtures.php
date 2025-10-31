<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\GaNetBundle\Entity\Publisher;

#[When(env: 'test')]
#[When(env: 'dev')]
class PublisherFixtures extends Fixture
{
    public const PUBLISHER_1 = 'publisher_1';
    public const PUBLISHER_2 = 'publisher_2';
    public const PUBLISHER_3 = 'publisher_3';

    public function load(ObjectManager $manager): void
    {
        // 创建测试发布商
        $publisher1 = new Publisher();
        $publisher1->setPublisherId(1001);
        $publisher1->setToken('test_token_123456');
        $manager->persist($publisher1);
        $this->addReference(self::PUBLISHER_1, $publisher1);

        $publisher2 = new Publisher();
        $publisher2->setPublisherId(1002);
        $publisher2->setToken('test_token_789012');
        $manager->persist($publisher2);
        $this->addReference(self::PUBLISHER_2, $publisher2);

        $publisher3 = new Publisher();
        $publisher3->setPublisherId(1003);
        $publisher3->setToken('test_token_345678');
        $manager->persist($publisher3);
        $this->addReference(self::PUBLISHER_3, $publisher3);

        $manager->flush();
    }
}
