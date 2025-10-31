<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\YesNoFlag;
use Tourze\GaNetBundle\Repository\CampaignRepository;

#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\Table(name: 'ga_net_campaign', options: ['comment' => 'GA Net 活动表'])]
class Campaign implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '活动id'])]
    #[ORM\CustomIdGenerator]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 10, options: ['comment' => '商家地域'])]
    #[Assert\Length(max: 10)]
    #[Assert\NotBlank]
    private string $region;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '活动名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '活动标题'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $title;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '活动链接'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 2000)]
    #[Assert\Url]
    private string $url;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '活动Logo图片链接'])]
    #[Assert\Length(max: 1000)]
    #[Assert\Url]
    private ?string $logo = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '活动开始时间'])]
    #[Assert\Length(max: 20)]
    #[Assert\NotBlank]
    private string $startTime;

    #[ORM\Column(type: Types::STRING, length: 10, enumType: Currency::class, options: ['comment' => '货币类型'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [Currency::class, 'cases'])]
    private Currency $currency;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '活动描述'])]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'Cookie过期时间(秒)'])]
    #[Assert\Positive]
    private int $cookieExpireTime;

    #[ORM\Column(type: Types::INTEGER, enumType: YesNoFlag::class, options: ['comment' => 'SEM（1=>是, 2 => 否）'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [YesNoFlag::class, 'cases'])]
    private YesNoFlag $semPermitted;

    #[ORM\Column(type: Types::INTEGER, enumType: YesNoFlag::class, options: ['comment' => '自定义链接（1=>是, 2 => 否）'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [YesNoFlag::class, 'cases'])]
    private YesNoFlag $isLinkCustomizable;

    #[ORM\Column(type: Types::INTEGER, enumType: YesNoFlag::class, options: ['comment' => '返利站（1=>是, 2 => 否）'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [YesNoFlag::class, 'cases'])]
    private YesNoFlag $rebatePermitted;

    #[ORM\Column(type: Types::INTEGER, enumType: YesNoFlag::class, options: ['comment' => '商品库 (1=>有, 2=>无)'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [YesNoFlag::class, 'cases'])]
    private YesNoFlag $hasDatafeed;

    #[ORM\Column(type: Types::INTEGER, enumType: YesNoFlag::class, options: ['comment' => '支持微信小程序 (1=>是, 2=>否)'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [YesNoFlag::class, 'cases'])]
    private YesNoFlag $supportWeapp;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '允许投放方式 (1:付费搜索(SEM),2:有机搜索(SEO),3:工具栏/插件,4:手机/平板APP,5:优惠券/促销,6:邮件营销,7:社交购物,8:内容/利基,9:CPA/二级联盟,10:忠诚度/积分返利,11:服务/工具,12:价格比较,13:Media Buy)'])]
    #[Assert\Length(max: 100)]
    private ?string $promotionalMethods = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '结算周期'])]
    #[Assert\Positive]
    private ?int $dataReceptionTime = null;

    #[ORM\Column(type: Types::INTEGER, enumType: CampaignApplicationStatus::class, options: ['comment' => '申请状态(0: 未申请, 1: 申请中, 5: 申请通过, 6: 申请未通过)'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [CampaignApplicationStatus::class, 'cases'])]
    private CampaignApplicationStatus $applicationStatus;

    #[ORM\ManyToOne(targetEntity: Publisher::class, inversedBy: 'campaigns')]
    #[ORM\JoinColumn(name: 'publisher_id', referencedColumnName: 'publisher_id')]
    private ?Publisher $publisher = null;

    /** @var Collection<int, CommissionRule> */
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: CommissionRule::class, cascade: ['persist'])]
    private Collection $commissionRules;

    /** @var Collection<int, Transaction> */
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: Transaction::class, cascade: ['persist'])]
    private Collection $transactions;

    /** @var Collection<int, PromotionCampaign> */
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: PromotionCampaign::class, cascade: ['persist'])]
    private Collection $promotionCampaigns;

    public function __construct()
    {
        $this->commissionRules = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->promotionCampaigns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(Currency $currency): void
    {
        $this->currency = $currency;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCookieExpireTime(): int
    {
        return $this->cookieExpireTime;
    }

    public function setCookieExpireTime(int $cookieExpireTime): void
    {
        $this->cookieExpireTime = $cookieExpireTime;
    }

    public function getSemPermitted(): YesNoFlag
    {
        return $this->semPermitted;
    }

    public function setSemPermitted(YesNoFlag $semPermitted): void
    {
        $this->semPermitted = $semPermitted;
    }

    public function getIsLinkCustomizable(): YesNoFlag
    {
        return $this->isLinkCustomizable;
    }

    public function setIsLinkCustomizable(YesNoFlag $isLinkCustomizable): void
    {
        $this->isLinkCustomizable = $isLinkCustomizable;
    }

    public function getRebatePermitted(): YesNoFlag
    {
        return $this->rebatePermitted;
    }

    public function setRebatePermitted(YesNoFlag $rebatePermitted): void
    {
        $this->rebatePermitted = $rebatePermitted;
    }

    public function getHasDatafeed(): YesNoFlag
    {
        return $this->hasDatafeed;
    }

    public function setHasDatafeed(YesNoFlag $hasDatafeed): void
    {
        $this->hasDatafeed = $hasDatafeed;
    }

    public function getSupportWeapp(): YesNoFlag
    {
        return $this->supportWeapp;
    }

    public function setSupportWeapp(YesNoFlag $supportWeapp): void
    {
        $this->supportWeapp = $supportWeapp;
    }

    public function getPromotionalMethods(): ?string
    {
        return $this->promotionalMethods;
    }

    public function setPromotionalMethods(?string $promotionalMethods): void
    {
        $this->promotionalMethods = $promotionalMethods;
    }

    public function getDataReceptionTime(): ?int
    {
        return $this->dataReceptionTime;
    }

    public function setDataReceptionTime(?int $dataReceptionTime): void
    {
        $this->dataReceptionTime = $dataReceptionTime;
    }

    public function getApplicationStatus(): CampaignApplicationStatus
    {
        return $this->applicationStatus;
    }

    public function setApplicationStatus(CampaignApplicationStatus $applicationStatus): void
    {
        $this->applicationStatus = $applicationStatus;
    }

    public function getPublisher(): ?Publisher
    {
        return $this->publisher;
    }

    public function setPublisher(?Publisher $publisher): void
    {
        $this->publisher = $publisher;
    }

    /**
     * @return Collection<int, CommissionRule>
     */
    public function getCommissionRules(): Collection
    {
        return $this->commissionRules;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    /**
     * @return Collection<int, PromotionCampaign>
     */
    public function getPromotionCampaigns(): Collection
    {
        return $this->promotionCampaigns;
    }

    public function __toString(): string
    {
        return $this->name;
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
        $this->setRegion($this->getStringValue($data, 'region', ''));
        $this->setName($this->getStringValue($data, 'name', ''));
        $this->setTitle($this->getStringValue($data, 'title', $this->getStringValue($data, 'name', '')));
        $this->setUrl($this->getStringValue($data, 'url', ''));
        $this->setLogo($this->getOptionalStringValue($data, 'logo'));
        $this->setStartTime($this->getStringValue($data, 'start_time', ''));
        $this->setDescription($this->getOptionalStringValue($data, 'description'));
        $this->setPromotionalMethods($this->getOptionalStringValue($data, 'promotional_methods'));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateIntegerFields(array $data): void
    {
        $this->setCookieExpireTime(is_int($data['cookie_expire_time'] ?? null) ? $data['cookie_expire_time'] : 0);
        $this->setDataReceptionTime(is_int($data['data_reception_time'] ?? null) ? $data['data_reception_time'] : null);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateEnumFields(array $data): void
    {
        $this->setCurrency($this->safeCurrencyFrom($data['currency'] ?? null));
        $this->setSemPermitted($this->safeYesNoFlagFrom($data['sem_permitted'] ?? null));
        $this->setIsLinkCustomizable($this->safeYesNoFlagFrom($data['is_link_customizable'] ?? null));
        $this->setRebatePermitted($this->safeYesNoFlagFrom($data['rebate_permitted'] ?? null));
        $this->setHasDatafeed($this->safeYesNoFlagFrom($data['has_datafeed'] ?? null));
        $this->setSupportWeapp($this->safeYesNoFlagFrom($data['support_weapp'] ?? null));
        $this->setApplicationStatus($this->safeCampaignApplicationStatusFrom($data['application_status'] ?? null));
    }

    private function safeCurrencyFrom(mixed $value): Currency
    {
        if (!is_string($value)) {
            return Currency::CNY;
        }

        try {
            return Currency::from($value);
        } catch (\ValueError) {
            return Currency::CNY;
        }
    }

    private function safeYesNoFlagFrom(mixed $value): YesNoFlag
    {
        if (!is_int($value)) {
            return YesNoFlag::NO;
        }

        try {
            return YesNoFlag::from($value);
        } catch (\ValueError) {
            return YesNoFlag::NO;
        }
    }

    private function safeCampaignApplicationStatusFrom(mixed $value): CampaignApplicationStatus
    {
        if (!is_int($value)) {
            return CampaignApplicationStatus::NOT_APPLIED;
        }

        try {
            return CampaignApplicationStatus::from($value);
        } catch (\ValueError) {
            return CampaignApplicationStatus::NOT_APPLIED;
        }
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
}
