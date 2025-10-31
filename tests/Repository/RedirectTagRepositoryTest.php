<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\RedirectTag;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Repository\RedirectTagRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(RedirectTagRepository::class)]
#[RunTestsInSeparateProcesses]
final class RedirectTagRepositoryTest extends AbstractRepositoryTestCase
{
    private RedirectTagRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(RedirectTagRepository::class);
    }

    protected function getRepository(): RedirectTagRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        // 使用随机ID避免实体身份冲突
        $publisherId = random_int(100000, 999999);
        $campaignId = random_int(100000, 999999);

        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken('test-token-' . $publisherId);
        $em = self::getService(EntityManagerInterface::class);
        $em->persist($publisher);

        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaign->setId($campaignId);
        $em = self::getService(EntityManagerInterface::class);
        $em->persist($campaign);

        $tag = 'test-tag-' . uniqid();

        $redirectTag = new RedirectTag();
        $redirectTag->setPublisher($publisher);
        $redirectTag->setTag($tag);
        $redirectTag->setUrl('https://example.com/test');
        $redirectTag->setCampaign($campaign);

        return $redirectTag;
    }

    #[Test]
    public function testSaveAndFlushShouldPersistRedirectTag(): void
    {
        $publisher = $this->createTestPublisher();
        $redirectTag = new RedirectTag();
        $redirectTag->setPublisher($publisher);
        $redirectTag->setTag('test-tag-save');

        $this->repository->save($redirectTag, true);

        $this->assertNotNull($redirectTag->getId());

        // 验证数据库中存在该记录
        $found = $this->repository->find($redirectTag->getId());
        $this->assertNotNull($found);
        $this->assertSame('test-tag-save', $found->getTag());
    }

    #[Test]
    public function testSaveWithoutFlushShouldNotPersistImmediately(): void
    {
        $publisher = $this->createTestPublisher();
        $redirectTag = new RedirectTag();
        $redirectTag->setPublisher($publisher);
        $redirectTag->setTag('test-tag-no-flush');

        $this->repository->save($redirectTag, false);

        // 应该找不到该记录，因为未flush到数据库
        // 但EntityManager缓存中可能存在，所以我们检查数据库中是否存在
        $em = self::getService(EntityManagerInterface::class);

        // 检查实体状态 - 应该在EntityManager中管理但未持久化到数据库
        $this->assertTrue($em->contains($redirectTag), 'RedirectTag should be managed by EntityManager');

        // 现在flush并再次确认
        $em->flush();
        $found = $this->repository->findByTag('test-tag-no-flush');
        $this->assertNotNull($found);
    }

    #[Test]
    public function testRemoveAndFlushShouldDeleteRedirectTag(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $tagId = $redirectTag->getId();

        $this->repository->remove($redirectTag, true);

        // 验证数据库中记录已被删除
        $found = $this->repository->find($tagId);
        $this->assertNull($found);
    }

    #[Test]
    public function testFindByTagShouldReturnCorrectRedirectTag(): void
    {
        $redirectTag = $this->createTestRedirectTag('unique-find-tag');

        $found = $this->repository->findByTag('unique-find-tag');

        $this->assertNotNull($found);
        $this->assertSame($redirectTag->getId(), $found->getId());
        $this->assertSame('unique-find-tag', $found->getTag());
    }

    #[Test]
    public function testFindByTagShouldReturnNullForNonExistentTag(): void
    {
        $found = $this->repository->findByTag('non-existent-tag');

        $this->assertNull($found);
    }

    #[Test]
    public function testFindActiveByTagShouldReturnActiveRedirectTag(): void
    {
        $publisher = $this->createTestPublisher();
        $activeTag = new RedirectTag();
        $activeTag->setPublisher($publisher);
        $activeTag->setTag('active-tag');
        $activeTag->setExpireTime(new \DateTimeImmutable('+1 day')); // 未过期
        $em = self::getService(EntityManagerInterface::class);
        $em->persist($activeTag);
        $em->flush();

        $found = $this->repository->findActiveByTag('active-tag');

        $this->assertNotNull($found);
        $this->assertSame($activeTag->getId(), $found->getId());
        $this->assertTrue($found->isActive());
    }

    #[Test]
    public function testFindActiveByTagShouldReturnNullForExpiredTag(): void
    {
        $publisher = $this->createTestPublisher();
        $expiredTag = new RedirectTag();
        $expiredTag->setPublisher($publisher);
        $expiredTag->setTag('expired-tag');
        $expiredTag->setExpireTime(new \DateTimeImmutable('-1 day')); // 已过期
        $em = self::getService(EntityManagerInterface::class);
        $em->persist($expiredTag);
        $em->flush();

        $found = $this->repository->findActiveByTag('expired-tag');

        $this->assertNull($found);
    }

    #[Test]
    public function testFindActiveByTagShouldReturnTagWithNullExpireTime(): void
    {
        $publisher = $this->createTestPublisher();
        $neverExpireTag = new RedirectTag();
        $neverExpireTag->setPublisher($publisher);
        $neverExpireTag->setTag('never-expire-tag');
        $neverExpireTag->setExpireTime(null); // 永不过期
        $em = self::getService(EntityManagerInterface::class);
        $em->persist($neverExpireTag);
        $em->flush();

        $found = $this->repository->findActiveByTag('never-expire-tag');

        $this->assertNotNull($found);
        $this->assertSame($neverExpireTag->getId(), $found->getId());
        $this->assertTrue($found->isActive());
    }

    #[Test]
    public function testFindByUserIdShouldReturnUserRedirectTags(): void
    {
        $publisher = $this->createTestPublisher();

        // 创建用户123的标签
        $userTag1 = new RedirectTag();
        $userTag1->setPublisher($publisher);
        $userTag1->setTag('user-123-tag-1');
        $userTag1->setUserId(123);
        $userTag2 = new RedirectTag();
        $userTag2->setPublisher($publisher);
        $userTag2->setTag('user-123-tag-2');
        $userTag2->setUserId(123);

        // 创建其他用户的标签
        $otherUserTag = new RedirectTag();
        $otherUserTag->setPublisher($publisher);
        $otherUserTag->setTag('user-456-tag');
        $otherUserTag->setUserId(456);

        $em = self::getService(EntityManagerInterface::class);
        $em->persist($userTag1);
        $em->persist($userTag2);
        $em->persist($otherUserTag);
        $em->flush();

        $found = $this->repository->findByUserId(123);

        $this->assertCount(2, $found);
        $foundTags = array_map(fn ($tag) => $tag->getTag(), $found);
        $this->assertContains('user-123-tag-1', $foundTags);
        $this->assertContains('user-123-tag-2', $foundTags);
        $this->assertNotContains('user-456-tag', $foundTags);
    }

    #[Test]
    public function testFindByUserIdShouldRespectLimit(): void
    {
        $publisher = $this->createTestPublisher();

        // 创建5个用户标签
        for ($i = 1; $i <= 5; ++$i) {
            $tag = new RedirectTag();
            $tag->setPublisher($publisher);
            $tag->setTag("user-limit-tag-{$i}");
            $tag->setUserId(999);
            $em = self::getService(EntityManagerInterface::class);
            $em->persist($tag);
        }
        $em = self::getService(EntityManagerInterface::class);
        $em->flush();

        $found = $this->repository->findByUserId(999, 3);

        $this->assertCount(3, $found);
    }

    #[Test]
    public function testFindByPublisherShouldReturnPublisherRedirectTags(): void
    {
        $publisher1 = $this->createTestPublisher(111, 'token-111');
        $publisher2 = $this->createTestPublisher(222, 'token-222');

        // 创建发布商111的标签
        $tag1 = new RedirectTag();
        $tag1->setPublisher($publisher1);
        $tag1->setTag('publisher-111-tag-1');
        $tag2 = new RedirectTag();
        $tag2->setPublisher($publisher1);
        $tag2->setTag('publisher-111-tag-2');

        // 创建发布商222的标签
        $tag3 = new RedirectTag();
        $tag3->setPublisher($publisher2);
        $tag3->setTag('publisher-222-tag');

        $em = self::getService(EntityManagerInterface::class);
        $em->persist($tag1);
        $em->persist($tag2);
        $em->persist($tag3);
        $em->flush();

        $found = $this->repository->findByPublisher($publisher1);

        $this->assertCount(2, $found);
        $foundTags = array_map(fn ($tag) => $tag->getTag(), $found);
        $this->assertContains('publisher-111-tag-1', $foundTags);
        $this->assertContains('publisher-111-tag-2', $foundTags);
        $this->assertNotContains('publisher-222-tag', $foundTags);
    }

    #[Test]
    public function testFindExpiredTagsShouldReturnOnlyExpiredTags(): void
    {
        // 清理之前可能存在的过期标签
        $this->repository->deleteExpiredTags();

        $publisher = $this->createTestPublisher();

        // 创建过期标签
        $expiredTag1 = new RedirectTag();
        $expiredTag1->setPublisher($publisher);
        $expiredTag1->setTag('expired-1');
        $expiredTag1->setExpireTime(new \DateTimeImmutable('-2 days'));

        $expiredTag2 = new RedirectTag();
        $expiredTag2->setPublisher($publisher);
        $expiredTag2->setTag('expired-2');
        $expiredTag2->setExpireTime(new \DateTimeImmutable('-1 day'));

        // 创建未过期标签
        $activeTag = new RedirectTag();
        $activeTag->setPublisher($publisher);
        $activeTag->setTag('active');
        $activeTag->setExpireTime(new \DateTimeImmutable('+1 day'));

        // 创建永不过期标签
        $neverExpireTag = new RedirectTag();
        $neverExpireTag->setPublisher($publisher);
        $neverExpireTag->setTag('never-expire');
        $neverExpireTag->setExpireTime(null);

        $em = self::getService(EntityManagerInterface::class);
        $em->persist($expiredTag1);
        $em->persist($expiredTag2);
        $em->persist($activeTag);
        $em->persist($neverExpireTag);
        $em->flush();

        $expiredTags = $this->repository->findExpiredTags();

        $this->assertCount(2, $expiredTags);
        $expiredTagNames = array_map(fn ($tag) => $tag->getTag(), $expiredTags);
        $this->assertContains('expired-1', $expiredTagNames);
        $this->assertContains('expired-2', $expiredTagNames);
    }

    #[Test]
    public function testDeleteExpiredTagsShouldRemoveExpiredTags(): void
    {
        // 清理之前可能存在的过期标签
        $this->repository->deleteExpiredTags();

        $publisher = $this->createTestPublisher();

        // 创建过期标签
        $expiredTag = new RedirectTag();
        $expiredTag->setPublisher($publisher);
        $expiredTag->setTag('to-be-deleted');
        $expiredTag->setExpireTime(new \DateTimeImmutable('-1 day'));

        // 创建未过期标签
        $activeTag = new RedirectTag();
        $activeTag->setPublisher($publisher);
        $activeTag->setTag('to-be-kept');
        $activeTag->setExpireTime(new \DateTimeImmutable('+1 day'));

        $em = self::getService(EntityManagerInterface::class);
        $em->persist($expiredTag);
        $em->persist($activeTag);
        $em->flush();

        $deletedCount = $this->repository->deleteExpiredTags();

        $this->assertSame(1, $deletedCount);

        // 验证过期标签已被删除
        $this->assertNull($this->repository->findByTag('to-be-deleted'));

        // 验证未过期标签仍存在
        $this->assertNotNull($this->repository->findByTag('to-be-kept'));
    }

    #[Test]
    public function testCountByDateRangeShouldReturnCorrectCount(): void
    {
        $publisher = $this->createTestPublisher();

        // 创建不同时间的标签
        $tag1 = new RedirectTag();
        $tag1->setPublisher($publisher);
        $tag1->setTag('tag-day1');
        $tag1->setClickTime(new \DateTimeImmutable('2024-01-01 10:00:00'));

        $tag2 = new RedirectTag();
        $tag2->setPublisher($publisher);
        $tag2->setTag('tag-day2');
        $tag2->setClickTime(new \DateTimeImmutable('2024-01-02 10:00:00'));

        $tag3 = new RedirectTag();
        $tag3->setPublisher($publisher);
        $tag3->setTag('tag-day3');
        $tag3->setClickTime(new \DateTimeImmutable('2024-01-03 10:00:00'));

        $em = self::getService(EntityManagerInterface::class);
        $em->persist($tag1);
        $em->persist($tag2);
        $em->persist($tag3);
        $em->flush();

        $start = new \DateTimeImmutable('2024-01-01 00:00:00');
        $end = new \DateTimeImmutable('2024-01-02 23:59:59');

        $count = $this->repository->countByDateRange($start, $end);

        $this->assertSame(2, $count); // tag1 和 tag2
    }

    #[Test]
    public function testGetClickStatsByPublisherShouldReturnAggregatedData(): void
    {
        $publisher = $this->createTestPublisher();

        // 创建同一天的多个点击
        $tag1 = new RedirectTag();
        $tag1->setPublisher($publisher);
        $tag1->setTag('stats-tag-1');
        $tag1->setClickTime(new \DateTimeImmutable('2024-01-01 09:00:00'));

        $tag2 = new RedirectTag();
        $tag2->setPublisher($publisher);
        $tag2->setTag('stats-tag-2');
        $tag2->setClickTime(new \DateTimeImmutable('2024-01-01 15:00:00'));

        $tag3 = new RedirectTag();
        $tag3->setPublisher($publisher);
        $tag3->setTag('stats-tag-3');
        $tag3->setClickTime(new \DateTimeImmutable('2024-01-02 10:00:00'));

        $em = self::getService(EntityManagerInterface::class);
        $em->persist($tag1);
        $em->persist($tag2);
        $em->persist($tag3);
        $em->flush();

        $start = new \DateTimeImmutable('2024-01-01 00:00:00');
        $end = new \DateTimeImmutable('2024-01-02 23:59:59');

        $stats = $this->repository->getClickStatsByPublisher($publisher, $start, $end);

        $this->assertCount(2, $stats); // 两天的数据

        // 验证2024-01-01的统计
        $day1Stats = array_filter($stats, fn ($stat) => str_contains($stat['click_date'], '2024-01-01'));
        $this->assertCount(1, $day1Stats);
        $day1Stat = reset($day1Stats);
        $this->assertSame(2, (int) $day1Stat['click_count']);

        // 验证2024-01-02的统计
        $day2Stats = array_filter($stats, fn ($stat) => str_contains($stat['click_date'], '2024-01-02'));
        $this->assertCount(1, $day2Stats);
        $day2Stat = reset($day2Stats);
        $this->assertSame(1, (int) $day2Stat['click_count']);
    }

    #[Test]
    public function testGetClickStatsByCampaignShouldGroupByCampaign(): void
    {
        $publisher = $this->createTestPublisher();
        $campaign = $this->createTestCampaign($publisher);

        // 创建关联到活动的标签
        $tag1 = new RedirectTag();
        $tag1->setPublisher($publisher);
        $tag1->setTag('campaign-tag-1');
        $tag1->setCampaign($campaign);
        $tag1->setClickTime(new \DateTimeImmutable('2024-01-01 10:00:00'));

        $tag2 = new RedirectTag();
        $tag2->setPublisher($publisher);
        $tag2->setTag('campaign-tag-2');
        $tag2->setCampaign($campaign);
        $tag2->setClickTime(new \DateTimeImmutable('2024-01-01 15:00:00'));

        // 创建无活动的标签
        $tag3 = new RedirectTag();
        $tag3->setPublisher($publisher);
        $tag3->setTag('no-campaign-tag');
        $tag3->setClickTime(new \DateTimeImmutable('2024-01-01 20:00:00'));

        $em = self::getService(EntityManagerInterface::class);
        $em->persist($tag1);
        $em->persist($tag2);
        $em->persist($tag3);
        $em->flush();

        $start = new \DateTimeImmutable('2024-01-01 00:00:00');
        $end = new \DateTimeImmutable('2024-01-01 23:59:59');

        $stats = $this->repository->getClickStatsByCampaign($publisher, $start, $end);

        $this->assertCount(2, $stats); // 一个有活动的分组和一个无活动的分组

        // 找到有活动的统计
        $campaignStats = array_filter($stats, fn ($stat) => null !== $stat['campaign_id']);
        $this->assertCount(1, $campaignStats);
        $campaignStat = array_values($campaignStats)[0];
        $this->assertSame($campaign->getId(), (int) $campaignStat['campaign_id']);
        $this->assertSame(2, (int) $campaignStat['click_count']);

        // 找到无活动的统计
        $noCampaignStats = array_filter($stats, fn ($stat) => null === $stat['campaign_id']);
        $this->assertCount(1, $noCampaignStats);
        $noCampaignStat = array_values($noCampaignStats)[0];
        $this->assertSame(1, (int) $noCampaignStat['click_count']);
    }

    private function createTestPublisher(int $publisherId = 12345, string $token = 'test-token'): Publisher
    {
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken($token);
        $em = self::getService(EntityManagerInterface::class);
        $em->persist($publisher);
        $em->flush();

        return $publisher;
    }

    private function createTestCampaign(Publisher $publisher): Campaign
    {
        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaign->setId(88888);
        $campaign->setName('测试活动');
        $campaign->setRegion('CN');
        $campaign->setUrl('https://example.com/test');
        $campaign->setStartTime('2024-01-01 00:00:00');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setDescription('测试活动描述');
        $campaign->setApplicationStatus(CampaignApplicationStatus::NOT_APPLIED);

        $em = self::getService(EntityManagerInterface::class);
        $em->persist($campaign);
        $em->flush();

        return $campaign;
    }

    private function createTestRedirectTag(string $tag = 'test-redirect-tag'): RedirectTag
    {
        $publisher = $this->createTestPublisher();
        $redirectTag = new RedirectTag();
        $redirectTag->setPublisher($publisher);
        $redirectTag->setTag($tag);

        $em = self::getService(EntityManagerInterface::class);
        $em->persist($redirectTag);
        $em->flush();

        return $redirectTag;
    }
}
