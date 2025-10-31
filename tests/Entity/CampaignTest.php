<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\YesNoFlag;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Campaign::class)]
class CampaignTest extends AbstractEntityTestCase
{
    private Publisher $publisher;

    private static int $nextPublisherId = 80000;

    private function getUniquePublisherId(): int
    {
        return ++self::$nextPublisherId;
    }

    protected function createEntity(): object
    {
        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");

        $campaign = new Campaign();
        $campaign->setPublisher($publisher);

        return $campaign;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $publisherId = $this->getUniquePublisherId();
        $this->publisher = new Publisher();
        $this->publisher->setPublisherId($publisherId);
        $this->publisher->setToken("test-token-{$publisherId}");
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'id' => ['id', 80001],
            'name' => ['name', 'Test Campaign'],
            'region' => ['region', 'JPN'],
            'url' => ['url', 'https://example.com'],
            'logo' => ['logo', 'https://example.com/logo.jpg'],
            'startTime' => ['startTime', '14-12-18'],
            'currency' => ['currency', Currency::USD],
            'description' => ['description', 'Test description'],
            'cookieExpireTime' => ['cookieExpireTime', 86400],
            'semPermitted' => ['semPermitted', YesNoFlag::YES],
            'isLinkCustomizable' => ['isLinkCustomizable', YesNoFlag::NO],
            'rebatePermitted' => ['rebatePermitted', YesNoFlag::YES],
            'hasDatafeed' => ['hasDatafeed', YesNoFlag::YES],
            'supportWeapp' => ['supportWeapp', YesNoFlag::NO],
            'promotionalMethods' => ['promotionalMethods', '2|3|4|5'],
            'dataReceptionTime' => ['dataReceptionTime', 30],
            'applicationStatus' => ['applicationStatus', CampaignApplicationStatus::APPROVED],
        ];
    }

    public function testCampaignCreation(): void
    {
        $campaign = new Campaign();
        $campaign->setId(2914);
        $campaign->setPublisher($this->publisher);
        $campaign->setName('Amazon JP（CN）');
        $campaign->setRegion('JPN');
        $campaign->setUrl('http://www.amazon.co.jp');
        $campaign->setCurrency(Currency::USD);
        $campaign->setCreateTime(new \DateTimeImmutable());

        $this->assertSame(2914, $campaign->getId());
        $this->assertSame('Amazon JP（CN）', $campaign->getName());
        $this->assertSame('JPN', $campaign->getRegion());
        $this->assertSame('http://www.amazon.co.jp', $campaign->getUrl());
        $this->assertSame(Currency::USD, $campaign->getCurrency());
        $this->assertSame($this->publisher, $campaign->getPublisher());
        $this->assertInstanceOf(\DateTimeInterface::class, $campaign->getCreateTime());
    }

    public function testUpdateFromApiData(): void
    {
        $campaign = new Campaign();
        $campaign->setPublisher($this->publisher);
        $campaign->setId(2914);

        $apiData = [
            'region' => 'JPN',
            'name' => 'Amazon JP（CN）',
            'url' => 'http://www.amazon.co.jp',
            'logo' => 'https://example.com/logo.jpg',
            'start_time' => '14-12-18',
            'currency' => 'USD',
            'description' => 'Test description',
            'cookie_expire_time' => 86400,
            'sem_permitted' => 1,
            'is_link_customizable' => 2,
            'rebate_permitted' => 1,
            'has_datafeed' => 1,
            'support_weapp' => 2,
            'promotional_methods' => '2|3|4|5',
            'data_reception_time' => 30,
            'application_status' => 5,
        ];

        $campaign->updateFromApiData($apiData);
        $campaign->setUpdateTime(new \DateTimeImmutable());

        $this->assertSame('JPN', $campaign->getRegion());
        $this->assertSame('Amazon JP（CN）', $campaign->getName());
        $this->assertSame('http://www.amazon.co.jp', $campaign->getUrl());
        $this->assertSame('https://example.com/logo.jpg', $campaign->getLogo());
        $this->assertSame('14-12-18', $campaign->getStartTime());
        $this->assertSame(Currency::USD, $campaign->getCurrency());
        $this->assertSame('Test description', $campaign->getDescription());
        $this->assertSame(86400, $campaign->getCookieExpireTime());
        $this->assertSame(YesNoFlag::YES, $campaign->getSemPermitted());
        $this->assertSame(YesNoFlag::NO, $campaign->getIsLinkCustomizable());
        $this->assertSame(YesNoFlag::YES, $campaign->getRebatePermitted());
        $this->assertSame(YesNoFlag::YES, $campaign->getHasDatafeed());
        $this->assertSame(YesNoFlag::NO, $campaign->getSupportWeapp());
        $this->assertSame('2|3|4|5', $campaign->getPromotionalMethods());
        $this->assertSame(30, $campaign->getDataReceptionTime());
        $this->assertSame(CampaignApplicationStatus::APPROVED, $campaign->getApplicationStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $campaign->getUpdateTime());
    }

    public function testApplicationStatusDefaults(): void
    {
        $campaign = new Campaign();
        $campaign->setPublisher($this->publisher);
        $campaign->updateFromApiData([]);

        $this->assertSame(CampaignApplicationStatus::NOT_APPLIED, $campaign->getApplicationStatus());
        $this->assertSame(YesNoFlag::NO, $campaign->getSemPermitted());
        $this->assertSame(YesNoFlag::NO, $campaign->getIsLinkCustomizable());
        $this->assertSame(YesNoFlag::NO, $campaign->getRebatePermitted());
        $this->assertSame(YesNoFlag::NO, $campaign->getHasDatafeed());
        $this->assertSame(YesNoFlag::NO, $campaign->getSupportWeapp());
    }
}
