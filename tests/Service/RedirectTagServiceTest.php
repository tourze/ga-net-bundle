<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Request;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\RedirectTag;
use Tourze\GaNetBundle\Repository\RedirectTagRepository;
use Tourze\GaNetBundle\Service\RedirectTagService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\UserServiceContracts\UserManagerInterface;

/**
 * @internal
 */
#[CoversClass(RedirectTagService::class)]
#[RunTestsInSeparateProcesses]
final class RedirectTagServiceTest extends AbstractIntegrationTestCase
{
    private RedirectTagService $service;
    private RedirectTagRepository $repository;
    private UserManagerInterface $userManager;

    protected function onSetUp(): void
    {
        $this->service = self::getService(RedirectTagService::class);
        $this->repository = self::getService(RedirectTagRepository::class);
        $this->userManager = self::getService(UserManagerInterface::class);
    }

    #[Test]
    public function testFindByTagShouldDelegateToRepository(): void
    {
        $tag = 'test-tag-123';
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');

        $redirectTag = new RedirectTag();
        $redirectTag->setTag($tag);
        $redirectTag->setPublisher($publisher);
        $em = self::getEntityManager();
        $em->persist($publisher);
        $em->persist($redirectTag);
        $em->flush();

        $result = $this->service->findByTag($tag);

        $this->assertSame($redirectTag, $result);
        $this->assertSame($tag, $result->getTag());
    }

    #[Test]
    public function testGenerateTrackingUrlShouldReturnCorrectStructure(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $targetUrl = 'https://example.com/product';

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
        $em = self::getEntityManager();
        $em->persist($publisher);
        $em->flush();

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
        $campaign = new Campaign();
        $campaign->setId(123);
        $campaign->setName('Test Campaign');

        $em = self::getEntityManager();
        $em->persist($campaign);
        $em->persist($publisher);
        $em->flush();

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

        $em = self::getEntityManager();
        $em->persist($publisher);
        $em->flush();

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

        $em = self::getEntityManager();
        $em->persist($publisher);
        $em->flush();

        $result = $this->service->createRedirectTag($publisher, null, null, $request);

        $this->assertSame('Test Browser', $result->getUserAgent());
        $this->assertSame('https://referrer.com', $result->getReferrerUrl());
        $this->assertNotNull($result->getContextData());
    }

    #[Test]
    public function testCleanupExpiredTagsShouldDelegateToRepository(): void
    {
        $before = new \DateTimeImmutable('-1 day');
        $expectedCount = 0;

        $result = $this->service->cleanupExpiredTags($before);

        $this->assertSame($expectedCount, $result);
    }

    #[Test]
    public function testGetClickStatsShouldDelegateToRepository(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $em = self::getEntityManager();
        $em->persist($publisher);
        $em->flush();

        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-01-31');

        $result = $this->service->getClickStats($publisher, $start, $end);

        $this->assertIsArray($result);
    }

    #[Test]
    public function testGetCampaignClickStatsShouldDelegateToRepository(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $em = self::getEntityManager();
        $em->persist($publisher);
        $em->flush();

        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-01-31');

        $result = $this->service->getCampaignClickStats($publisher, $start, $end);

        $this->assertIsArray($result);
    }

    #[Test]
    public function testFindByUserIdShouldDelegateToRepository(): void
    {
        $userId = 123;
        $limit = 25;

        $result = $this->service->findByUserId($userId, $limit);

        $this->assertIsArray($result);
    }

    #[Test]
    public function testFindByPublisherShouldDelegateToRepository(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $limit = 50;

        $em = self::getEntityManager();
        $em->persist($publisher);
        $em->flush();

        $result = $this->service->findByPublisher($publisher, $limit);

        $this->assertIsArray($result);
    }

    #[Test]
    public function testUpdateTagWithUserInfoShouldReturnTrueWhenTagExists(): void
    {
        $tag = 'existing-tag-' . uniqid();
        $userId = 456;
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');

        $redirectTag = new RedirectTag();
        $redirectTag->setTag($tag);
        $redirectTag->setPublisher($publisher);

        $em = self::getEntityManager();
        $em->persist($publisher);
        $em->persist($redirectTag);
        $em->flush();

        $result = $this->service->updateTagWithUserInfo($tag, $userId);

        $this->assertTrue($result);
    }

    #[Test]
    public function testUpdateTagWithUserInfoShouldReturnFalseWhenTagNotFound(): void
    {
        $tag = 'non-existent-tag-' . uniqid();
        $userId = 456;

        $result = $this->service->updateTagWithUserInfo($tag, $userId);

        $this->assertFalse($result);
    }

    #[Test]
    public function testUpdateTagWithUserInfoShouldAddAdditionalContext(): void
    {
        $tag = 'existing-tag-' . uniqid();
        $userId = 456;
        $additionalContext = ['source' => 'mobile', 'version' => '1.0'];
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');

        $redirectTag = new RedirectTag();
        $redirectTag->setTag($tag);
        $redirectTag->setPublisher($publisher);

        $em = self::getEntityManager();
        $em->persist($publisher);
        $em->persist($redirectTag);
        $em->flush();

        $result = $this->service->updateTagWithUserInfo($tag, $userId, $additionalContext);

        $this->assertTrue($result);
    }

    #[Test]
    public function testFindActiveByTagShouldDelegateToRepository(): void
    {
        $tag = 'active-tag-' . uniqid();
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');

        $redirectTag = new RedirectTag();
        $redirectTag->setTag($tag);
        $redirectTag->setPublisher($publisher);

        $em = self::getEntityManager();
        $em->persist($publisher);
        $em->persist($redirectTag);
        $em->flush();

        $result = $this->service->findActiveByTag($tag);

        $this->assertSame($redirectTag, $result);
    }

    #[Test]
    public function testAddContextDataShouldCallRedirectTagAndFlush(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');

        $redirectTag = new RedirectTag();
        $redirectTag->setTag('test-tag-' . uniqid());
        $redirectTag->setPublisher($publisher);

        $em = self::getEntityManager();
        $em->persist($publisher);
        $em->persist($redirectTag);
        $em->flush();

        $key = 'test_key';
        $value = 'test_value';

        $this->service->addContextData($redirectTag, $key, $value);

        $updatedTag = self::getEntityManager()->find(RedirectTag::class, $redirectTag->getId());
        $contextData = $updatedTag->getContextData();
        $this->assertArrayHasKey($key, $contextData);
        $this->assertSame($value, $contextData[$key]);
    }
}