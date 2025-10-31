<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\RedirectTag;

#[When(env: 'test')]
#[When(env: 'dev')]
class RedirectTagFixtures extends Fixture implements DependentFixtureInterface
{
    public const REDIRECT_TAG_1 = 'redirect_tag_1';
    public const REDIRECT_TAG_2 = 'redirect_tag_2';
    public const REDIRECT_TAG_3 = 'redirect_tag_3';

    public function load(ObjectManager $manager): void
    {
        // 获取发布商引用
        $publisher1 = $this->getReference(PublisherFixtures::PUBLISHER_1, Publisher::class);
        $publisher2 = $this->getReference(PublisherFixtures::PUBLISHER_2, Publisher::class);
        $campaign1 = $this->getReference(CampaignFixtures::CAMPAIGN_1, Campaign::class);

        // 创建测试重定向标签
        $tag1 = new RedirectTag();
        $tag1->setPublisher($publisher1);
        $tag1->setTag('test_tag_001');
        $tag1->setUserId(1001);
        $tag1->setUserIp('192.168.1.100');
        $tag1->setUserAgent('Mozilla/5.0 (Test)');
        $tag1->setReferrerUrl('https://images.unsplash.com/photo-1516802273409-68526ee1bdd6?w=400&h=300');
        $tag1->setCampaign($campaign1);
        $tag1->addContextData('source', 'test');
        $manager->persist($tag1);
        $this->addReference(self::REDIRECT_TAG_1, $tag1);

        $tag2 = new RedirectTag();
        $tag2->setPublisher($publisher2);
        $tag2->setTag('test_tag_002');
        $tag2->setUserId(1002);
        $tag2->setUserIp('192.168.1.101');
        $tag2->setUserAgent('Mozilla/5.0 (Chrome/Test)');
        $tag2->setReferrerUrl('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=400&h=300');
        $manager->persist($tag2);
        $this->addReference(self::REDIRECT_TAG_2, $tag2);

        // 创建一个已过期的标签
        $expiredTag = new RedirectTag();
        $expiredTag->setPublisher($publisher1);
        $expiredTag->setTag('test_tag_expired');
        $expiredTag->setUserId(1003);
        $expiredTag->setExpireTime(new \DateTimeImmutable('-1 day'));
        $manager->persist($expiredTag);
        $this->addReference(self::REDIRECT_TAG_3, $expiredTag);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PublisherFixtures::class,
            CampaignFixtures::class,
        ];
    }
}
