<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\GaNetBundle\Repository\RedirectTagRepository;

#[ORM\Entity(repositoryClass: RedirectTagRepository::class)]
#[ORM\Table(name: 'ga_net_redirect_tag', options: ['comment' => 'GA Net 重定向标签表，存储用户点击上下文信息'])]
#[ORM\Index(columns: ['user_id', 'campaign_id'], name: 'ga_net_redirect_tag_idx_user_campaign')]
class RedirectTag implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true, options: ['comment' => '唯一标签值，用于关联订单'])]
    #[Assert\Length(max: 64)]
    #[Assert\NotBlank]
    #[IndexColumn]
    private ?string $tag = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '用户ID（如果已登录）'])]
    #[Assert\Positive]
    private ?int $userId = null;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true, options: ['comment' => '用户IP地址'])]
    #[Assert\Length(max: 45)]
    #[Assert\Ip]
    private ?string $userIp = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '用户代理字符串'])]
    #[Assert\Length(max: 2000)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '来源页面URL'])]
    #[Assert\Length(max: 2000)]
    #[Assert\Url]
    private ?string $referrerUrl = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '点击时间'])]
    #[Assert\NotNull]
    #[IndexColumn]
    private ?\DateTimeImmutable $clickTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '过期时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $expireTime = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '额外的上下文信息（JSON格式）'], name: 'context_data')]
    #[Assert\Type(type: 'array')]
    private ?array $contextData = null;

    #[ORM\ManyToOne(targetEntity: Campaign::class)]
    #[ORM\JoinColumn(name: 'campaign_id', referencedColumnName: 'id')]
    private ?Campaign $campaign = null;

    #[ORM\ManyToOne(targetEntity: Publisher::class)]
    #[ORM\JoinColumn(name: 'publisher_id', referencedColumnName: 'publisher_id')]
    private ?Publisher $publisher = null;

    public function __construct()
    {
    }

    public static function generateTag(int $publisherId, ?int $campaignId = null, ?int $userId = null): string
    {
        $data = [
            'publisher' => $publisherId,
            'campaign' => $campaignId,
            'user' => $userId,
            'time' => time(),
            'rand' => random_int(1000000, 9999999),
        ];

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserIp(): ?string
    {
        return $this->userIp;
    }

    public function setUserIp(?string $userIp): void
    {
        $this->userIp = $userIp;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getReferrerUrl(): ?string
    {
        return $this->referrerUrl;
    }

    public function setReferrerUrl(?string $referrerUrl): void
    {
        $this->referrerUrl = $referrerUrl;
    }

    public function getClickTime(): ?\DateTimeImmutable
    {
        return $this->clickTime;
    }

    public function setClickTime(?\DateTimeImmutable $clickTime): void
    {
        $this->clickTime = $clickTime;
    }

    public function getExpireTime(): ?\DateTimeImmutable
    {
        return $this->expireTime;
    }

    public function setExpireTime(?\DateTimeImmutable $expireTime): void
    {
        $this->expireTime = $expireTime;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContextData(): ?array
    {
        return $this->contextData;
    }

    /**
     * @param array<string, mixed>|null $contextData
     */
    public function setContextData(?array $contextData): void
    {
        $this->contextData = $contextData;
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function getPublisher(): ?Publisher
    {
        return $this->publisher;
    }

    public function setPublisher(?Publisher $publisher): void
    {
        $this->publisher = $publisher;
    }

    // 兼容性方法
    public function setUrl(?string $url): void
    {
        $this->setReferrerUrl($url);
    }

    public function getUrl(): ?string
    {
        return $this->referrerUrl;
    }

    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    public function isExpired(): bool
    {
        if (null === $this->expireTime || null === $this->clickTime) {
            return false;
        }

        return new \DateTimeImmutable() > $this->expireTime;
    }

    public function addContextData(string $key, mixed $value): self
    {
        if (null === $this->contextData) {
            $this->contextData = [];
        }

        $this->contextData[$key] = $value;

        return $this;
    }

    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->contextData[$key] ?? $default;
    }

    public function __toString(): string
    {
        return $this->tag ?? '';
    }
}
