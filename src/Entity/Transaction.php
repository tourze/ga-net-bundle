<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\TransactionStatus;
use Tourze\GaNetBundle\Repository\TransactionRepository;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'ga_net_transaction', options: ['comment' => 'GA Net 交易表'])]
class Transaction implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'transaction id(唯一)'])]
    #[ORM\CustomIdGenerator]
    private int $id;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'transaction的额外信息'])]
    #[Assert\Length(max: 1000)]
    private ?string $memo = null;

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

    #[ORM\Column(name: 'campaign_id', type: Types::INTEGER, nullable: true, options: ['comment' => '活动id'])]
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

    #[ORM\Column(type: Types::INTEGER, enumType: TransactionStatus::class, options: ['comment' => '订单状态：1 => 待认证, 2 => 已认证, 3 => 拒绝'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [TransactionStatus::class, 'cases'])]
    private TransactionStatus $orderStatus;

    #[ORM\Column(type: Types::STRING, length: 10, enumType: Currency::class, options: ['comment' => '根据媒体后台设置的收款币种'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [Currency::class, 'cases'])]
    private Currency $currency;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '反馈标签'])]
    #[Assert\Length(max: 100)]
    private ?string $tag = null;

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

    #[ORM\Column(type: Types::STRING, length: 10, enumType: Currency::class, options: ['comment' => '原始货币'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [Currency::class, 'cases'])]
    private Currency $originalCurrency;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, options: ['comment' => '原始总金额'])]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 15)]
    private string $originalTotalPrice;

    // 结算相关字段（settlement-query-api.md特有）
    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '结算月份（2019-02）'])]
    #[Assert\Length(max: 10)]
    private ?string $balanceTime = null;

    #[ORM\ManyToOne(targetEntity: Publisher::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'publisher_id', referencedColumnName: 'publisher_id')]
    private ?Publisher $publisher = null;

    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(name: 'campaign_id', referencedColumnName: 'id')]
    private ?Campaign $campaign = null;

    #[ORM\ManyToOne(targetEntity: RedirectTag::class)]
    #[ORM\JoinColumn(name: 'redirect_tag_id', referencedColumnName: 'id', nullable: true)]
    private ?RedirectTag $redirectTag = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '用户ID（来自RedirectTag或直接设置）'])]
    #[Assert\Positive]
    private ?int $userId = null;

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

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function setMemo(?string $memo): void
    {
        $this->memo = $memo;
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

    public function getOrderStatus(): TransactionStatus
    {
        return $this->orderStatus;
    }

    public function setOrderStatus(TransactionStatus $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): void
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

    public function getOriginalCurrency(): Currency
    {
        return $this->originalCurrency;
    }

    public function setOriginalCurrency(Currency $originalCurrency): void
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

    public function getBalanceTime(): ?string
    {
        return $this->balanceTime;
    }

    public function setBalanceTime(?string $balanceTime): void
    {
        $this->balanceTime = $balanceTime;
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

    public function getRedirectTag(): ?RedirectTag
    {
        return $this->redirectTag;
    }

    public function setRedirectTag(?RedirectTag $redirectTag): void
    {
        $this->redirectTag = $redirectTag;

        // 自动同步用户ID
        if (null !== $redirectTag && null !== $redirectTag->getUserId()) {
            $this->userId = $redirectTag->getUserId();
        }
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    // 别名方法，兼容性支持
    public function getStatus(): TransactionStatus
    {
        return $this->orderStatus;
    }

    public function setStatus(TransactionStatus $status): void
    {
        $this->orderStatus = $status;
    }

    // 状态判断方法
    public function isPending(): bool
    {
        return $this->orderStatus->isPending();
    }

    public function isConfirmed(): bool
    {
        return $this->orderStatus->isConfirmed();
    }

    public function isRejected(): bool
    {
        return $this->orderStatus->isRejected();
    }

    public function getStatusLabel(): string
    {
        return $this->orderStatus->getLabel();
    }

    // 是否已结算
    public function isSettled(): bool
    {
        return null !== $this->balanceTime;
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
        $this->setMemo($this->getOptionalStringValue($data, 'memo'));
        $this->setOrderId($this->getStringValue($data, 'order_id', ''));
        $this->setTotalPrice($this->getStringValue($data, 'total_price', '0'));
        $this->setCampaignName($this->getStringValue($data, 'campaign_name', ''));
        $this->setTotalCommission($this->getStringValue($data, 'total_commission', '0'));
        $this->setOrderTime($this->getStringValue($data, 'order_time', ''));
        $this->setTag($this->getOptionalStringValue($data, 'tag'));
        $this->setCategoryId($this->getSafeStringValue($data, 'category_id'));
        $this->setCategoryName($this->getOptionalStringValue($data, 'category_name'));
        $this->setItemName($this->getStringValue($data, 'item_name', ''));
        $this->setOriginalTotalPrice($this->getStringValue($data, 'original_total_price', '0'));
        $this->setBalanceTime($this->getOptionalStringValue($data, 'balance_time'));
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
        $this->setOrderStatus($this->getEnumValue($data, 'order_status', TransactionStatus::class, TransactionStatus::PENDING));
        $this->setCurrency($this->getEnumValue($data, 'currency', Currency::class, Currency::CNY));
        $this->setOriginalCurrency($this->getEnumValue($data, 'original_currency', Currency::class, Currency::CNY));
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
