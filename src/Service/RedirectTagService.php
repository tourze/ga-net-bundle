<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\RedirectTag;
use Tourze\GaNetBundle\Repository\RedirectTagRepository;

readonly class RedirectTagService
{
    public function __construct(
        private RedirectTagRepository $redirectTagRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function findByTag(string $tag): ?RedirectTag
    {
        return $this->redirectTagRepository->findByTag($tag);
    }

    /**
     * @return array{tag: string, tracking_url: string, redirect_tag: RedirectTag}
     */
    public function generateTrackingUrl(
        Publisher $publisher,
        string $targetUrl,
        ?Campaign $campaign = null,
        ?int $userId = null,
        ?Request $request = null,
    ): array {
        $redirectTag = $this->createRedirectTag($publisher, $campaign, $userId, $request);

        $tagValue = $redirectTag->getTag();
        if (null === $tagValue) {
            throw new \RuntimeException('Tag value cannot be null');
        }

        return [
            'tag' => $tagValue,
            'tracking_url' => $this->buildTrackingUrl($targetUrl, $tagValue),
            'redirect_tag' => $redirectTag,
        ];
    }

    public function createRedirectTag(
        Publisher $publisher,
        ?Campaign $campaign = null,
        ?int $userId = null,
        ?Request $request = null,
    ): RedirectTag {
        $publisherId = $publisher->getPublisherId();
        if (null === $publisherId) {
            throw new \InvalidArgumentException('Publisher ID cannot be null');
        }

        $tag = RedirectTag::generateTag(
            $publisherId,
            $campaign?->getId(),
            $userId
        );

        $redirectTag = new RedirectTag();
        $redirectTag->setPublisher($publisher);
        $redirectTag->setTag($tag);
        $redirectTag->setClickTime(new \DateTimeImmutable());

        if (null !== $campaign) {
            $redirectTag->setCampaign($campaign);
        }

        if (null !== $userId) {
            $redirectTag->setUserId($userId);
        }

        if (null !== $request) {
            $this->addRequestContext($redirectTag, $request);
        }

        $this->entityManager->persist($redirectTag);
        $this->entityManager->flush();

        return $redirectTag;
    }

    private function addRequestContext(RedirectTag $redirectTag, Request $request): void
    {
        $redirectTag->setUserIp($request->getClientIp());
        $redirectTag->setUserAgent($request->headers->get('User-Agent'));
        $redirectTag->setReferrerUrl($request->headers->get('Referer'));

        // 添加额外的上下文信息
        $contextData = [
            'accept_language' => $request->headers->get('Accept-Language'),
            'accept_encoding' => $request->headers->get('Accept-Encoding'),
            'request_time' => time(),
            'request_uri' => $request->getRequestUri(),
            'request_method' => $request->getMethod(),
        ];

        // 过滤掉null值
        $contextData = array_filter($contextData, static fn ($value) => null !== $value);

        // 如果有查询参数，也记录下来
        if ($request->query->count() > 0) {
            $contextData['query_params'] = $request->query->all();
        }

        $redirectTag->setContextData($contextData);
    }

    private function buildTrackingUrl(string $targetUrl, string $tag): string
    {
        $separator = str_contains($targetUrl, '?') ? '&' : '?';

        return $targetUrl . $separator . 'tag=' . urlencode($tag);
    }

    public function cleanupExpiredTags(?\DateTimeImmutable $before = null): int
    {
        return $this->redirectTagRepository->deleteExpiredTags($before);
    }

    /**
     * @return array<array{click_count: int, click_date: string}>
     */
    public function getClickStats(Publisher $publisher, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->redirectTagRepository->getClickStatsByPublisher($publisher, $start, $end);
    }

    /**
     * @return array<array{campaign_id: int|null, campaign_name: string|null, click_count: int}>
     */
    public function getCampaignClickStats(Publisher $publisher, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        return $this->redirectTagRepository->getClickStatsByCampaign($publisher, $start, $end);
    }

    /**
     * @return RedirectTag[]
     */
    public function findByUserId(int $userId, int $limit = 50): array
    {
        return $this->redirectTagRepository->findByUserId($userId, $limit);
    }

    /**
     * @return RedirectTag[]
     */
    public function findByPublisher(Publisher $publisher, int $limit = 100): array
    {
        return $this->redirectTagRepository->findByPublisher($publisher, $limit);
    }

    /**
     * @param array<string, mixed>|null $additionalContext
     */
    public function updateTagWithUserInfo(string $tag, int $userId, ?array $additionalContext = null): bool
    {
        $redirectTag = $this->findActiveByTag($tag);

        if (null === $redirectTag) {
            return false;
        }

        $redirectTag->setUserId($userId);

        if (null !== $additionalContext) {
            foreach ($additionalContext as $key => $value) {
                $redirectTag->addContextData($key, $value);
            }
        }

        $this->entityManager->flush();

        return true;
    }

    public function findActiveByTag(string $tag): ?RedirectTag
    {
        return $this->redirectTagRepository->findActiveByTag($tag);
    }

    public function addContextData(RedirectTag $redirectTag, string $key, mixed $value): void
    {
        $redirectTag->addContextData($key, $value);
        $this->entityManager->flush();
    }
}
