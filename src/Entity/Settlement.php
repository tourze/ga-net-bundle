<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\GaNetBundle\Enum\SettlementStatus;
use Tourze\GaNetBundle\Repository\SettlementRepository;

/**
 * 结算实体 - 基于settlement-query-api.md
 * 这是Transaction的结算版本，具有相同的数据结构但用于不同的业务场景
 */
#[ORM\Entity(repositoryClass: SettlementRepository::class)]
#[ORM\Table(name: 'ga_net_settlement', options: ['comment' => 'GA Net 结算表'])]
class Settlement implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'transaction id(唯一)'])]
    #[ORM\CustomIdGenerator]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '订单号'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $orderId;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '网站id'])]
    #[Assert\Positive]
    private int $websiteId;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '商品总价格'])]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 15)]
    private string $totalPrice;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '活动id'])]
    #[Assert\Positive]
    private ?int $campaignId = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '活动名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $campaignName;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '商品总佣金'])]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 15)]
    private string $totalCommission;

    #[ORM\Column(type: Types::STRING, length: 30, options: ['comment' => '订单时间（时间为utc时区）'])]
    #[Assert\Length(max: 30)]
    #[Assert\NotBlank]
    private string $orderTime;

    #[ORM\Column(type: Types::INTEGER, enumType: SettlementStatus::class, options: ['comment' => '订单状态：1=>待认证, 2 => 已通过, 3=> 已拒绝'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [SettlementStatus::class, 'cases'])]
    private SettlementStatus $orderStatus;

    #[ORM\Column(type: Types::STRING, length: 10, options: ['comment' => '根据媒体后台设置的收款币种'])]
    #[Assert\Length(max: 10)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['CNY', 'USD', 'EUR', 'GBP'])]
    private string $currency;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '反馈标签'])]
    #[Assert\Length(max: 100)]
    private ?string $tag = null;

    #[ORM\Column(type: Types::STRING, length: 10, options: ['comment' => '结算月份（2019-02）'])]
    #[Assert\Length(max: 10)]
    #[Assert\NotBlank]
    private string $balanceTime;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '商品分类id'])]
    #[Assert\Length(max: 100)]
    private ?string $categoryId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '商品分类名称'])]
    #[Assert\Length(max: 255)]
    private ?string $categoryName = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '商品数量'])]
    #[Assert\Positive]
    private int $itemQuantity;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '商品名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 1000)]
    private string $itemName;

    #[ORM\Column(type: Types::STRING, length: 10, options: ['comment' => '原始货币'])]
    #[Assert\Length(max: 10)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['CNY', 'USD', 'EUR', 'GBP'])]
    private string $originalCurrency;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '原始总金额'])]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 15)]
    private string $originalTotalPrice;

    #[ORM\ManyToOne(targetEntity: Publisher::class)]
    #[ORM\JoinColumn(name: 'publisher_id', referencedColumnName: 'publisher_id')]
    private ?Publisher $publisher = null;

    #[ORM\ManyToOne(targetEntity: Campaign::class)]
    #[ORM\JoinColumn(name: 'campaign_entity_id', referencedColumnName: 'id')]
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

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getWebsiteId(): int
    {
        return $this->websiteId;
    }

    public function setWebsiteId(int $websiteId): void
    {
        $this->websiteId = $websiteId;
    }

    public function getTotalPrice(): string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    public function getCampaignId(): ?int
    {
        return $this->campaignId;
    }

    public function setCampaignId(?int $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    public function getCampaignName(): string
    {
        return $this->campaignName;
    }

    public function setCampaignName(string $campaignName): void
    {
        $this->campaignName = $campaignName;
    }

    public function getTotalCommission(): string
    {
        return $this->totalCommission;
    }

    public function setTotalCommission(string $totalCommission): void
    {
        $this->totalCommission = $totalCommission;
    }

    public function getOrderTime(): string
    {
        return $this->orderTime;
    }

    public function setOrderTime(string $orderTime): void
    {
        $this->orderTime = $orderTime;
    }

    public function getOrderStatus(): SettlementStatus
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(SettlementStatus $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): void
    {
        $this->tag = $tag;
    }

    public function getBalanceTime(): string
    {
        return $this->balanceTime;
    }

    public function setBalanceTime(string $balanceTime): void
    {
        $this->balanceTime = $balanceTime;
    }

    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    public function setCategoryId(?string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function setCategoryName(?string $categoryName): void
    {
        $this->categoryName = $categoryName;
    }

    public function getItemQuantity(): int
    {
        return $this->itemQuantity;
    }

    public function setItemQuantity(int $itemQuantity): void
    {
        $this->itemQuantity = $itemQuantity;
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }

    public function setItemName(string $itemName): void
    {
        $this->itemName = $itemName;
    }

    public function getOriginalCurrency(): string
    {
        return $this->originalCurrency;
    }

    public function setOriginalCurrency(string $originalCurrency): void
    {
        $this->originalCurrency = $originalCurrency;
    }

    public function getOriginalTotalPrice(): string
    {
        return $this->originalTotalPrice;
    }

    public function setOriginalTotalPrice(string $originalTotalPrice): void
    {
        $this->originalTotalPrice = $originalTotalPrice;
    }

    public function getPublisher(): ?Publisher
    {
        return $this->publisher;
    }

    public function setPublisher(?Publisher $publisher): void
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

    // 状态判断方法
    public function isPending(): bool
    {
        return $this->orderStatus->isPending();
    }

    public function isApproved(): bool
    {
        return $this->orderStatus->isApproved();
    }

    public function isRejected(): bool
    {
        return $this->orderStatus->isRejected();
    }

    public function getStatusLabel(): string
    {
        return $this->orderStatus->getLabel();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateFromApiData(array $data): self
    {
        $this->updateStringFields($data);
        $this->updateIntegerFields($data);
        $this->updateEnumFields($data);

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateStringFields(array $data): void
    {
        $this->setOrderId($this->getStringValue($data, 'order_id', ''));
        $this->setTotalPrice($this->getStringValue($data, 'total_price', '0'));
        $this->setCampaignName($this->getStringValue($data, 'campaign_name', ''));
        $this->setTotalCommission($this->getStringValue($data, 'total_commission', '0'));
        $this->setOrderTime($this->getStringValue($data, 'order_time', ''));
        $this->setCurrency($this->getStringValue($data, 'currency', ''));
        $this->setTag($this->getOptionalStringValue($data, 'tag'));
        $this->setBalanceTime($this->getStringValue($data, 'balance_time', ''));
        $this->setCategoryId($this->getSafeStringValue($data, 'category_id'));
        $this->setCategoryName($this->getOptionalStringValue($data, 'category_name'));
        $this->setItemName($this->getStringValue($data, 'item_name', ''));
        $this->setOriginalCurrency($this->getStringValue($data, 'original_currency', ''));
        $this->setOriginalTotalPrice($this->getStringValue($data, 'original_total_price', '0'));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateIntegerFields(array $data): void
    {
        $this->setWebsiteId(is_int($data['website_id'] ?? null) ? $data['website_id'] : 0);
        $this->setCampaignId(is_int($data['campaign_id'] ?? null) ? $data['campaign_id'] : null);
        $this->setItemQuantity(is_int($data['item_quantity'] ?? null) ? $data['item_quantity'] : 0);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateEnumFields(array $data): void
    {
        $this->setOrderStatus($this->getEnumValue($data, 'order_status', SettlementStatus::class, SettlementStatus::PENDING));
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
     * @param array<string, mixed> $data
     */
    private function getSafeStringValue(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (null === $value) {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
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
        return $this->orderId;
    }
}
