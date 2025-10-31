<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\CommissionRule;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\CommissionMode;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(CommissionRule::class)]
class CommissionRuleTest extends AbstractEntityTestCase
{
    private Campaign $campaign;

    protected function createEntity(): object
    {
        $publisherId = time() + rand(1, 1000);
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");

        $this->campaign = new Campaign();
        $this->campaign->setPublisher($publisher);
        $this->campaign->setId(2914 + rand(1, 1000));

        $rule = new CommissionRule();
        $rule->setCampaign($this->campaign);

        return $rule;
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'id' => ['id', 12345],
            'name' => ['name', 'Character figures'],
            'mode' => ['mode', CommissionMode::PERCENTAGE],
            'ratio' => ['ratio', '0.004'],
            'currency' => ['currency', 'JPY'],
            'commission' => ['commission', '100.50'],
            'startTime' => ['startTime', '2018-12-31'],
            'memo' => ['memo', 'Character figure'],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $publisherId = time() + rand(1, 1000);
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->campaign = new Campaign();
        $this->campaign->setPublisher($publisher);
        $this->campaign->setId(2914 + rand(1, 1000));
    }

    public function testCommissionRuleCreation(): void
    {
        $rule = new CommissionRule();
        $rule->setCampaign($this->campaign);
        $rule->setId(32826);
        $rule->setName('Character figures');
        $rule->setMode(CommissionMode::PERCENTAGE);
        $rule->setRatio('0.004');
        $rule->setCurrency('JPY');
        $rule->setStartTime('2018-12-31');
        $rule->setMemo('Character figure');
        $rule->setCreateTime(new \DateTimeImmutable());

        $this->assertSame(32826, $rule->getId());
        $this->assertSame('Character figures', $rule->getName());
        $this->assertSame(CommissionMode::PERCENTAGE, $rule->getMode());
        $this->assertSame('0.004', $rule->getRatio());
        $this->assertSame('JPY', $rule->getCurrency());
        $this->assertSame('2018-12-31', $rule->getStartTime());
        $this->assertSame('Character figure', $rule->getMemo());
        $this->assertSame($this->campaign, $rule->getCampaign());
        $this->assertInstanceOf(\DateTimeInterface::class, $rule->getCreateTime());
    }

    public function testModeConstants(): void
    {
        $this->assertSame(1, CommissionMode::PERCENTAGE->value);
        $this->assertSame(2, CommissionMode::FIXED->value);
    }

    public function testModeCheckers(): void
    {
        $rule = new CommissionRule();
        $rule->setCampaign($this->campaign);

        $rule->setMode(CommissionMode::PERCENTAGE);
        $this->assertTrue($rule->isPercentageMode());
        $this->assertFalse($rule->isFixedMode());

        $rule->setMode(CommissionMode::FIXED);
        $this->assertFalse($rule->isPercentageMode());
        $this->assertTrue($rule->isFixedMode());
    }

    public function testUpdateFromApiData(): void
    {
        $rule = new CommissionRule();
        $rule->setCampaign($this->campaign);
        $rule->setId(32826);

        $apiData = [
            'name' => 'PC & electronics',
            'mode' => CommissionMode::FIXED->value,
            'ratio' => '0.016',
            'currency' => 'JPY',
            'commission' => '100.50',
            'start_time' => '2018-12-31',
            'memo' => 'PC electronics memo',
        ];

        $rule->updateFromApiData($apiData);
        $rule->setUpdateTime(new \DateTimeImmutable());

        $this->assertSame('PC & electronics', $rule->getName());
        $this->assertSame(CommissionMode::FIXED, $rule->getMode());
        $this->assertSame('0.016', $rule->getRatio());
        $this->assertSame('JPY', $rule->getCurrency());
        $this->assertSame('100.50', $rule->getCommission());
        $this->assertSame('2018-12-31', $rule->getStartTime());
        $this->assertSame('PC electronics memo', $rule->getMemo());
        $this->assertInstanceOf(\DateTimeInterface::class, $rule->getUpdateTime());
    }

    public function testUpdateFromApiDataWithDefaults(): void
    {
        $rule = new CommissionRule();
        $rule->setCampaign($this->campaign);
        $rule->updateFromApiData([]);

        $this->assertSame('', $rule->getName());
        $this->assertSame(CommissionMode::PERCENTAGE, $rule->getMode());
        $this->assertNull($rule->getRatio());
        $this->assertSame('', $rule->getCurrency());
        $this->assertNull($rule->getCommission());
        $this->assertSame('', $rule->getStartTime());
        $this->assertNull($rule->getMemo());
    }
}
