<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Publisher::class)]
class PublisherTest extends AbstractEntityTestCase
{
    private static int $nextPublisherId = 81000;

    private function getUniquePublisherId(): int
    {
        return ++self::$nextPublisherId;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // 不需要额外的设置
    }

    protected function createEntity(): object
    {
        $publisherId = $this->getUniquePublisherId();

        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");

        return $publisher;
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'token' => ['token', 'new-token-xyz789'];
    }

    public function testPublisherCreation(): void
    {
        $publisherId = 12345;
        $token = 'test-token-abc123';

        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken($token);
        $publisher->setCreateTime(new \DateTimeImmutable());

        $this->assertSame($publisherId, $publisher->getPublisherId());
        $this->assertSame($token, $publisher->getToken());
        $this->assertInstanceOf(\DateTimeInterface::class, $publisher->getCreateTime());
        $this->assertNull($publisher->getUpdateTime());
    }

    public function testTokenUpdate(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('old-token');
        $newToken = 'new-token-xyz789';

        $publisher->setToken($newToken);
        $publisher->setUpdateTime(new \DateTimeImmutable());

        $this->assertSame($newToken, $publisher->getToken());
        $this->assertInstanceOf(\DateTimeInterface::class, $publisher->getUpdateTime());
    }

    public function testSignGeneration(): void
    {
        $publisherId = 12345;
        $token = 'test-token';
        $timestamp = 1527674322;

        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken($token);
        $sign = $publisher->generateSign($timestamp);

        $expectedSign = md5($publisherId . $timestamp . $token);
        $this->assertSame($expectedSign, $sign);
    }
}
