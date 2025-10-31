<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\RedirectTag;
use Tourze\GaNetBundle\Repository\RedirectTagRepository;
use Tourze\GaNetBundle\Service\RedirectTagService;

/**
 * @internal
 */
#[CoversClass(RedirectTagService::class)]
final class RedirectTagServiceTest extends TestCase
{
    /** @var RedirectTagRepository&MockObject */
    private RedirectTagRepository $repository;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    private RedirectTagService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(RedirectTagRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new RedirectTagService($this->repository, $this->entityManager);
    }

    #[Test]
    public function testFindByTagShouldDelegateToRepository(): void
    {
        $tag = 'test-tag-123';
        $expectedRedirectTag = $this->createMock(RedirectTag::class);

        $this->repository->expects($this->once())
            ->method('findByTag')
            ->with($tag)
            ->willReturn($expectedRedirectTag)
        ;

        $result = $this->service->findByTag($tag);

        $this->assertSame($expectedRedirectTag, $result);
    }

    #[Test]
    public function testGenerateTrackingUrlShouldReturnCorrectStructure(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $targetUrl = 'https://example.com/product';

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->generateTrackingUrl($publisher, $targetUrl);

        $this->assertArrayHasKey('tag', $result);
        $this->assertArrayHasKey('tracking_url', $result);
        $this->assertArrayHasKey('redirect_tag', $result);
        $this->assertInstanceOf(RedirectTag::class, $result['redirect_tag']);
    }

    #[Test]
    public function testGenerateTrackingUrlShouldAppendTagToUrl(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $targetUrl = 'https://example.com/product';

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->generateTrackingUrl($publisher, $targetUrl);

        $this->assertStringContainsString($targetUrl, $result['tracking_url']);
        $this->assertStringContainsString('?tag=', $result['tracking_url']);
    }

    #[Test]
    public function testGenerateTrackingUrlShouldHandleUrlsWithExistingParameters(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $targetUrl = 'https://example.com/product?param=value';

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->generateTrackingUrl($publisher, $targetUrl);

        $this->assertStringContainsString($targetUrl, $result['tracking_url']);
        $this->assertStringContainsString('&tag=', $result['tracking_url']);
    }

    #[Test]
    public function testCreateRedirectTagShouldPersistAndFlush(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->createRedirectTag($publisher);

        $this->assertInstanceOf(RedirectTag::class, $result);
        $this->assertSame($publisher, $result->getPublisher());
    }

    #[Test]
    public function testCreateRedirectTagShouldSetCampaignWhenProvided(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $campaign = $this->createMock(Campaign::class);

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->createRedirectTag($publisher, $campaign);

        $this->assertSame($campaign, $result->getCampaign());
    }

    #[Test]
    public function testCreateRedirectTagShouldSetUserIdWhenProvided(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $userId = 999;

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->createRedirectTag($publisher, null, $userId);

        $this->assertSame($userId, $result->getUserId());
    }

    #[Test]
    public function testCreateRedirectTagShouldAddRequestContextWhenProvided(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $request = new Request();
        $request->headers->set('User-Agent', 'Test Browser');
        $request->headers->set('Referer', 'https://referrer.com');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->createRedirectTag($publisher, null, null, $request);

        $this->assertSame('Test Browser', $result->getUserAgent());
        $this->assertSame('https://referrer.com', $result->getReferrerUrl());
        $this->assertNotNull($result->getContextData());
    }

    #[Test]
    public function testCleanupExpiredTagsShouldDelegateToRepository(): void
    {
        $before = new \DateTimeImmutable('-1 day');
        $expectedCount = 5;

        $this->repository->expects($this->once())
            ->method('deleteExpiredTags')
            ->with($before)
            ->willReturn($expectedCount)
        ;

        $result = $this->service->cleanupExpiredTags($before);

        $this->assertSame($expectedCount, $result);
    }

    #[Test]
    public function testGetClickStatsShouldDelegateToRepository(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-01-31');
        $expectedStats = [['click_count' => 10, 'click_date' => '2024-01-01']];

        $this->repository->expects($this->once())
            ->method('getClickStatsByPublisher')
            ->with($publisher, $start, $end)
            ->willReturn($expectedStats)
        ;

        $result = $this->service->getClickStats($publisher, $start, $end);

        $this->assertSame($expectedStats, $result);
    }

    #[Test]
    public function testGetCampaignClickStatsShouldDelegateToRepository(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-01-31');
        $expectedStats = [['campaign_id' => 1, 'campaign_name' => 'Test', 'click_count' => 5]];

        $this->repository->expects($this->once())
            ->method('getClickStatsByCampaign')
            ->with($publisher, $start, $end)
            ->willReturn($expectedStats)
        ;

        $result = $this->service->getCampaignClickStats($publisher, $start, $end);

        $this->assertSame($expectedStats, $result);
    }

    #[Test]
    public function testFindByUserIdShouldDelegateToRepository(): void
    {
        $userId = 123;
        $limit = 25;
        $expectedTags = [$this->createMock(RedirectTag::class)];

        $this->repository->expects($this->once())
            ->method('findByUserId')
            ->with($userId, $limit)
            ->willReturn($expectedTags)
        ;

        $result = $this->service->findByUserId($userId, $limit);

        $this->assertSame($expectedTags, $result);
    }

    #[Test]
    public function testFindByPublisherShouldDelegateToRepository(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $limit = 50;
        $expectedTags = [$this->createMock(RedirectTag::class)];

        $this->repository->expects($this->once())
            ->method('findByPublisher')
            ->with($publisher, $limit)
            ->willReturn($expectedTags)
        ;

        $result = $this->service->findByPublisher($publisher, $limit);

        $this->assertSame($expectedTags, $result);
    }

    #[Test]
    public function testUpdateTagWithUserInfoShouldReturnTrueWhenTagExists(): void
    {
        $tag = 'existing-tag';
        $userId = 456;
        $redirectTag = $this->createMock(RedirectTag::class);

        $this->repository->expects($this->once())
            ->method('findActiveByTag')
            ->with($tag)
            ->willReturn($redirectTag)
        ;

        $redirectTag->expects($this->once())
            ->method('setUserId')
            ->with($userId)
        ;

        $this->entityManager->expects($this->once())
            ->method('flush')
        ;

        $result = $this->service->updateTagWithUserInfo($tag, $userId);

        $this->assertTrue($result);
    }

    #[Test]
    public function testUpdateTagWithUserInfoShouldReturnFalseWhenTagNotFound(): void
    {
        $tag = 'non-existent-tag';
        $userId = 456;

        $this->repository->expects($this->once())
            ->method('findActiveByTag')
            ->with($tag)
            ->willReturn(null)
        ;

        $this->entityManager->expects($this->never())
            ->method('flush')
        ;

        $result = $this->service->updateTagWithUserInfo($tag, $userId);

        $this->assertFalse($result);
    }

    #[Test]
    public function testUpdateTagWithUserInfoShouldAddAdditionalContext(): void
    {
        $tag = 'existing-tag';
        $userId = 456;
        $additionalContext = ['source' => 'mobile', 'version' => '1.0'];
        $redirectTag = $this->createMock(RedirectTag::class);

        $this->repository->expects($this->once())
            ->method('findActiveByTag')
            ->with($tag)
            ->willReturn($redirectTag)
        ;

        $redirectTag->expects($this->once())
            ->method('setUserId')
            ->with($userId)
        ;

        $redirectTag->expects($this->exactly(2))
            ->method('addContextData')
        ;

        $result = $this->service->updateTagWithUserInfo($tag, $userId, $additionalContext);

        $this->assertTrue($result);
    }

    #[Test]
    public function testFindActiveByTagShouldDelegateToRepository(): void
    {
        $tag = 'active-tag';
        $expectedTag = $this->createMock(RedirectTag::class);

        $this->repository->expects($this->once())
            ->method('findActiveByTag')
            ->with($tag)
            ->willReturn($expectedTag)
        ;

        $result = $this->service->findActiveByTag($tag);

        $this->assertSame($expectedTag, $result);
    }

    #[Test]
    public function testAddContextDataShouldCallRedirectTagAndFlush(): void
    {
        $redirectTag = $this->createMock(RedirectTag::class);
        $key = 'test_key';
        $value = 'test_value';

        $redirectTag->expects($this->once())
            ->method('addContextData')
            ->with($key, $value)
        ;

        $this->entityManager->expects($this->once())
            ->method('flush')
        ;

        $this->service->addContextData($redirectTag, $key, $value);
    }
}
