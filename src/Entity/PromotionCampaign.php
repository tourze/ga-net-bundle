<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\GaNetBundle\Enum\PromotionType;
use Tourze\GaNetBundle\Repository\PromotionCampaignRepository;

#[ORM\Entity(repositoryClass: PromotionCampaignRepository::class)]
#[ORM\Table(name: 'ga_net_promotion_campaign', options: ['comment' => 'GA Net 促销活动表'])]
class PromotionCampaign implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '促销优惠id'])]
    #[ORM\CustomIdGenerator]
    private int $id;

    #[ORM\Column(type: Types::INTEGER, enumType: PromotionType::class, options: ['comment' => '促销方式(1: 降价/打折, 2: 优惠券)'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [PromotionType::class, 'cases'])]
    private PromotionType $promotionType;

    #[ORM\Column(type: Types::STRING, length: 30, options: ['comment' => '促销开始时间'])]
    #[Assert\Length(max: 30)]
    #[Assert\NotBlank]
    private string $startTime;

    #[ORM\Column(type: Types::STRING, length: 30, options: ['comment' => '促销结束时间'])]
    #[Assert\Length(max: 30)]
    #[Assert\NotBlank]
    private string $endTime;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '促销活动名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 500)]
    private string $title;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '促销活动简称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '促销活动图片'])]
    #[Assert\Length(max: 1000)]
    #[Assert\Url]
    private ?string $image = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '促销活动链接'])]
    #[Assert\Length(max: 2000)]
    #[Assert\Url]
    private ?string $url = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '促销详情'])]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true, options: ['comment' => '优惠券码'])]
    #[Assert\Length(max: 50)]
    private ?string $couponCode = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '活动ID'])]
    #[Assert\Positive]
    private ?int $campaignId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '最低佣金'])]
    #[Assert\PositiveOrZero]
    private string $minCommission = '0.00';

    #[ORM\ManyToOne(targetEntity: Publisher::class, inversedBy: 'promotionCampaigns')]
    #[ORM\JoinColumn(name: 'publisher_id', referencedColumnName: 'publisher_id')]
    private ?Publisher $publisher = null;

    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'promotionCampaigns')]
    #[ORM\JoinColumn(name: 'campaign_id', referencedColumnName: 'id')]
    private ?Campaign $campaign = null;

    public function __construct()
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getPromotionType(): PromotionType
    {
        return $this->promotionType;
    }

    public function setPromotionType(PromotionType $promotionType): void
    {
        $this->promotionType = $promotionType;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function setEndTime(string $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }

    public function setCouponCode(?string $couponCode): void
    {
        $this->couponCode = $couponCode;
    }

    public function getCampaignId(): ?int
    {
        return $this->campaignId;
    }

    public function setCampaignId(?int $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    public function getMinCommission(): string
    {
        return $this->minCommission;
    }

    public function setMinCommission(string $minCommission): void
    {
        $this->minCommission = $minCommission;
    }

    public function getPublisher(): ?Publisher
    {
        return $this->publisher;
    }

    public function setPublisher(Publisher $publisher): void
    {
        $this->publisher = $publisher;
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    // 促销类型判断方法
    public function isDiscountType(): bool
    {
        return PromotionType::DISCOUNT === $this->promotionType;
    }

    public function isCouponType(): bool
    {
        return PromotionType::COUPON === $this->promotionType;
    }

    public function getPromotionTypeLabel(): string
    {
        return $this->promotionType->getLabel();
    }

    // 检查促销是否有效（基于时间）
    public function isActive(): bool
    {
        $now = new \DateTime();
        $start = \DateTime::createFromFormat('Y-m-d H:i:s', $this->startTime);
        $end = \DateTime::createFromFormat('Y-m-d H:i:s', $this->endTime);

        return false !== $start && false !== $end && $now >= $start && $now <= $end;
    }

    // 检查是否即将过期（7天内）
    public function isExpiringSoon(): bool
    {
        $now = new \DateTime();
        $end = \DateTime::createFromFormat('Y-m-d H:i:s', $this->endTime);

        if (false === $end) {
            return false;
        }

        $diff = $now->diff($end);

        return $diff->days <= 7 && 0 === $diff->invert;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateFromApiData(array $data): self
    {
        $this->updateEnumFields($data);
        $this->updateStringFields($data);
        $this->updateIntegerFields($data);

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateEnumFields(array $data): void
    {
        $this->setPromotionType($this->getEnumValue($data, 'promotion_type', PromotionType::class, PromotionType::DISCOUNT));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateStringFields(array $data): void
    {
        $this->setStartTime($this->getStringValue($data, 'start_time', ''));
        $this->setEndTime($this->getStringValue($data, 'end_time', ''));
        $this->setTitle($this->getStringValue($data, 'title', ''));
        $this->setName($this->getStringValue($data, 'name', $this->getStringValue($data, 'title', '')));
        $this->setImage($this->getOptionalStringValue($data, 'image'));
        $this->setUrl($this->getOptionalStringValue($data, 'url'));
        $this->setDescription($this->getOptionalStringValue($data, 'description'));
        $this->setCouponCode($this->getOptionalStringValue($data, 'coupon_code'));
        $this->setMinCommission($this->getStringValue($data, 'min_commission', '0.00'));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateIntegerFields(array $data): void
    {
        $this->setCampaignId(is_int($data['campaign_id'] ?? null) ? $data['campaign_id'] : null);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getStringValue(array $data, string $key, string $default): string
    {
        return is_string($data[$key] ?? null) ? $data[$key] : $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getOptionalStringValue(array $data, string $key): ?string
    {
        return is_string($data[$key] ?? null) ? $data[$key] : null;
    }

    /**
     * @template T of \BackedEnum
     * @param array<string, mixed> $data
     * @param class-string<T> $enumClass
     * @param T $default
     * @return T
     */
    private function getEnumValue(array $data, string $key, string $enumClass, \BackedEnum $default): \BackedEnum
    {
        if (!isset($data[$key])) {
            return $default;
        }

        $value = $data[$key];
        if (!is_string($value) && !is_int($value)) {
            return $default;
        }

        try {
            return $enumClass::from($value);
        } catch (\ValueError) {
            return $default;
        }
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
