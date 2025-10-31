<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\GaNetBundle\Enum\CommissionMode;
use Tourze\GaNetBundle\Repository\CommissionRuleRepository;

#[ORM\Entity(repositoryClass: CommissionRuleRepository::class)]
#[ORM\Table(name: 'ga_net_commission_rule', options: ['comment' => 'GA Net 佣金规则表'])]
class CommissionRule implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    #[ORM\CustomIdGenerator]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::INTEGER, enumType: CommissionMode::class, options: ['comment' => '佣金模式（1=>分成, 2=>固定）'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [CommissionMode::class, 'cases'])]
    private CommissionMode $mode;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 6, nullable: true, options: ['comment' => '网站佣金（分成）'])]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 20)]
    private ?string $ratio = null;

    #[ORM\Column(type: Types::STRING, length: 10, options: ['comment' => '佣金货币'])]
    #[Assert\Length(max: 10)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['CNY', 'USD', 'EUR', 'GBP'])]
    private string $currency;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '网站佣金（固定）'])]
    #[Assert\PositiveOrZero]
    #[Assert\Length(max: 15)]
    private ?string $commission = null;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '开始时间'])]
    #[Assert\Length(max: 20)]
    #[Assert\NotBlank]
    private string $startTime;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 1000)]
    private ?string $memo = null;

    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'commissionRules')]
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getMode(): CommissionMode
    {
        return $this->mode;
    }

    public function setMode(CommissionMode $mode): void
    {
        $this->mode = $mode;
    }

    public function getRatio(): ?string
    {
        return $this->ratio;
    }

    public function setRatio(?string $ratio): void
    {
        $this->ratio = $ratio;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getCommission(): ?string
    {
        return $this->commission;
    }

    public function setCommission(?string $commission): void
    {
        $this->commission = $commission;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function setMemo(?string $memo): void
    {
        $this->memo = $memo;
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function isPercentageMode(): bool
    {
        return CommissionMode::PERCENTAGE === $this->mode;
    }

    public function isFixedMode(): bool
    {
        return CommissionMode::FIXED === $this->mode;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateFromApiData(array $data): self
    {
        $this->updateStringFields($data);
        $this->updateEnumFields($data);

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateStringFields(array $data): void
    {
        $this->setName($this->getStringValue($data, 'name', ''));
        $this->setRatio($this->getOptionalStringValue($data, 'ratio'));
        $this->setCurrency($this->getStringValue($data, 'currency', ''));
        $this->setCommission($this->getOptionalStringValue($data, 'commission'));
        $this->setStartTime($this->getStringValue($data, 'start_time', ''));
        $this->setMemo($this->getOptionalStringValue($data, 'memo'));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateEnumFields(array $data): void
    {
        $this->setMode($this->getEnumValue($data, 'mode', CommissionMode::class, CommissionMode::PERCENTAGE));
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
}
