<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\RedirectTag;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(RedirectTag::class)]
final class RedirectTagTest extends AbstractEntityTestCase
{
    #[Test]
    public function testConstructorShouldInitializeBasicProperties(): void
    {
        $publisher = $this->createTestPublisher();
        $tag = 'test-tag-123';
        $clickTime = new \DateTimeImmutable();
        $expireTime = $clickTime->modify('+30 days');

        $redirectTag = new RedirectTag();
        $redirectTag->setPublisher($publisher);
        $redirectTag->setTag($tag);
        $redirectTag->setClickTime($clickTime);
        $redirectTag->setExpireTime($expireTime);

        $this->assertSame($publisher, $redirectTag->getPublisher());
        $this->assertSame($tag, $redirectTag->getTag());
        $this->assertInstanceOf(\DateTimeImmutable::class, $redirectTag->getClickTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $redirectTag->getExpireTime());
    }

    #[Test]
    public function testConstructorShouldSetDefaultExpireTime(): void
    {
        $publisher = $this->createTestPublisher();
        $clickTime = new \DateTimeImmutable();
        $expectedExpireTime = $clickTime->modify('+30 days');

        $redirectTag = new RedirectTag();
        $redirectTag->setPublisher($publisher);
        $redirectTag->setTag('test-tag');
        $redirectTag->setClickTime($clickTime);
        $redirectTag->setExpireTime($expectedExpireTime);

        $this->assertEquals($expectedExpireTime, $redirectTag->getExpireTime());
        $this->assertEquals($clickTime, $redirectTag->getClickTime());
    }

    #[Test]
    public function testGenerateTagShouldCreateUniqueHashWithAllParameters(): void
    {
        $publisherId = 12345;
        $campaignId = 67890;
        $userId = 999;

        $tag = RedirectTag::generateTag($publisherId, $campaignId, $userId);

        $this->assertIsString($tag);
        $this->assertSame(64, strlen($tag)); // SHA256 hash length
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $tag);
    }

    #[Test]
    public function testGenerateTagShouldCreateUniqueHashWithoutOptionalParameters(): void
    {
        $publisherId = 12345;

        $tag = RedirectTag::generateTag($publisherId);

        $this->assertIsString($tag);
        $this->assertSame(64, strlen($tag)); // SHA256 hash length
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $tag);
    }

    #[Test]
    public function testGenerateTagShouldCreateDifferentHashesForDifferentInputs(): void
    {
        $tag1 = RedirectTag::generateTag(12345, 67890, 999);
        $tag2 = RedirectTag::generateTag(12345, 67890, 888);
        $tag3 = RedirectTag::generateTag(54321, 67890, 999);

        $this->assertNotSame($tag1, $tag2);
        $this->assertNotSame($tag1, $tag3);
        $this->assertNotSame($tag2, $tag3);
    }

    #[Test]
    public function testSetterAndGetterForUserId(): void
    {
        $redirectTag = $this->createTestRedirectTag();

        $redirectTag->setUserId(12345);

        $this->assertSame(12345, $redirectTag->getUserId());
    }

    #[Test]
    public function testSetterAndGetterForUserIp(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $ip = '192.168.1.100';

        $redirectTag->setUserIp($ip);

        $this->assertSame($ip, $redirectTag->getUserIp());
    }

    #[Test]
    public function testSetterAndGetterForUserAgent(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

        $redirectTag->setUserAgent($userAgent);

        $this->assertSame($userAgent, $redirectTag->getUserAgent());
    }

    #[Test]
    public function testSetterAndGetterForReferrerUrl(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $referrer = 'https://example.com/referrer';

        $redirectTag->setReferrerUrl($referrer);

        $this->assertSame($referrer, $redirectTag->getReferrerUrl());
    }

    #[Test]
    public function testSetterAndGetterForCampaign(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $campaign = $this->createTestCampaign();

        $redirectTag->setCampaign($campaign);

        $this->assertSame($campaign, $redirectTag->getCampaign());
    }

    #[Test]
    public function testContextDataManipulation(): void
    {
        $redirectTag = $this->createTestRedirectTag();

        $redirectTag->setContextData(['key1' => 'value1', 'key2' => 'value2']);

        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $redirectTag->getContextData());
    }

    #[Test]
    public function testAddContextDataShouldAppendToExistingData(): void
    {
        $redirectTag = $this->createTestRedirectTag();

        $redirectTag->setContextData(['existing' => 'data']);
        $redirectTag->addContextData('new_key', 'new_value');

        $expected = ['existing' => 'data', 'new_key' => 'new_value'];
        $this->assertSame($expected, $redirectTag->getContextData());
    }

    #[Test]
    public function testAddContextDataShouldInitializeEmptyArray(): void
    {
        $redirectTag = $this->createTestRedirectTag();

        $redirectTag->addContextData('first_key', 'first_value');

        $this->assertSame(['first_key' => 'first_value'], $redirectTag->getContextData());
    }

    #[Test]
    public function testGetContextValueShouldReturnExistingValue(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $redirectTag->setContextData(['test_key' => 'test_value']);

        $value = $redirectTag->getContextValue('test_key');

        $this->assertSame('test_value', $value);
    }

    #[Test]
    public function testGetContextValueShouldReturnDefaultForMissingKey(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $redirectTag->setContextData(['other_key' => 'other_value']);

        $value = $redirectTag->getContextValue('missing_key', 'default_value');

        $this->assertSame('default_value', $value);
    }

    #[Test]
    public function testGetContextValueShouldReturnNullForMissingKeyWithoutDefault(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $redirectTag->setContextData(['other_key' => 'other_value']);

        $value = $redirectTag->getContextValue('missing_key');

        $this->assertNull($value);
    }

    #[Test]
    public function testIsActiveShouldReturnTrueWhenNotExpired(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $futureTime = new \DateTimeImmutable('+1 day');
        $redirectTag->setExpireTime($futureTime);

        $this->assertTrue($redirectTag->isActive());
    }

    #[Test]
    public function testIsActiveShouldReturnFalseWhenExpired(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $pastTime = new \DateTimeImmutable('-1 day');
        $redirectTag->setExpireTime($pastTime);

        $this->assertFalse($redirectTag->isActive());
    }

    #[Test]
    public function testIsActiveShouldReturnTrueWhenExpireTimeIsNull(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $redirectTag->setExpireTime(null);

        $this->assertTrue($redirectTag->isActive());
    }

    #[Test]
    public function testIsExpiredShouldReturnTrueWhenExpired(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $pastTime = new \DateTimeImmutable('-1 day');
        $redirectTag->setExpireTime($pastTime);

        $this->assertTrue($redirectTag->isExpired());
    }

    #[Test]
    public function testIsExpiredShouldReturnFalseWhenNotExpired(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $futureTime = new \DateTimeImmutable('+1 day');
        $redirectTag->setExpireTime($futureTime);

        $this->assertFalse($redirectTag->isExpired());
    }

    #[Test]
    public function testIsExpiredShouldReturnFalseWhenExpireTimeIsNull(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $redirectTag->setExpireTime(null);

        $this->assertFalse($redirectTag->isExpired());
    }

    #[Test]
    public function testToStringShouldReturnTag(): void
    {
        $publisher = $this->createTestPublisher();
        $tag = 'test-tag-string-123';
        $redirectTag = new RedirectTag();
        $redirectTag->setPublisher($publisher);
        $redirectTag->setTag($tag);

        $this->assertSame($tag, (string) $redirectTag);
    }

    #[Test]
    public function testSetClickTimeShouldUpdateClickTime(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $newTime = new \DateTimeImmutable('2024-01-15 10:30:00');

        $redirectTag->setClickTime($newTime);

        $this->assertEquals($newTime, $redirectTag->getClickTime());
    }

    #[Test]
    public function testSetTagShouldUpdateTag(): void
    {
        $redirectTag = $this->createTestRedirectTag();
        $newTag = 'new-updated-tag-456';

        $redirectTag->setTag($newTag);

        $this->assertSame($newTag, $redirectTag->getTag());
    }

    #[Test]
    public function testContextDataShouldSupportComplexTypes(): void
    {
        $redirectTag = $this->createTestRedirectTag();

        $complexData = [
            'string_value' => 'test',
            'numeric_value' => 123,
            'boolean_value' => true,
            'array_value' => ['nested' => 'data'],
            'null_value' => null,
        ];

        $redirectTag->setContextData($complexData);

        $this->assertSame($complexData, $redirectTag->getContextData());
    }

    #[Test]
    public function testContextDataOverwriteShouldReplaceExistingKey(): void
    {
        $redirectTag = $this->createTestRedirectTag();

        $redirectTag->addContextData('key', 'original_value');
        $redirectTag->addContextData('key', 'updated_value');

        $this->assertSame('updated_value', $redirectTag->getContextValue('key'));
    }

    private function createTestPublisher(): Publisher
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token-12345');

        return $publisher;
    }

    private function createTestCampaign(): Campaign
    {
        $publisher = $this->createTestPublisher();

        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaign->setName('测试活动');
        $campaign->setRegion('CN');
        $campaign->setUrl('https://example.com/test');
        $campaign->setStartTime('2024-01-01 00:00:00');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setDescription('测试活动描述');
        $campaign->setApplicationStatus(CampaignApplicationStatus::NOT_APPLIED);

        return $campaign;
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $properties = [
            'tag' => 'test-tag-updated',
            'userId' => 12345,
            'userIp' => '192.168.1.100',
            'userAgent' => 'Mozilla/5.0 Test Agent',
            'referrerUrl' => 'https://example.com/referrer',
            'clickTime' => new \DateTimeImmutable('2024-01-01 10:00:00'),
            'expireTime' => new \DateTimeImmutable('2024-02-01 10:00:00'),
            'contextData' => ['key' => 'value', 'test' => 123],
        ];

        foreach ($properties as $property => $sampleValue) {
            yield $property => [$property, $sampleValue];
        }
    }

    protected function createEntity(): RedirectTag
    {
        $publisher = $this->createTestPublisher();
        $clickTime = new \DateTimeImmutable();

        $redirectTag = new RedirectTag();
        $redirectTag->setPublisher($publisher);
        $redirectTag->setTag('test-tag-123');
        $redirectTag->setClickTime($clickTime);
        $redirectTag->setExpireTime($clickTime->modify('+30 days'));

        return $redirectTag;
    }

    private function createTestRedirectTag(): RedirectTag
    {
        $publisher = $this->createTestPublisher();
        $clickTime = new \DateTimeImmutable();

        $redirectTag = new RedirectTag();
        $redirectTag->setPublisher($publisher);
        $redirectTag->setTag('test-tag-123');
        $redirectTag->setClickTime($clickTime);
        $redirectTag->setExpireTime($clickTime->modify('+30 days'));

        return $redirectTag;
    }
}
