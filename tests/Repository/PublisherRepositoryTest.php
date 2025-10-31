<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Repository;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Repository\PublisherRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(PublisherRepository::class)]
#[RunTestsInSeparateProcesses]
final class PublisherRepositoryTest extends AbstractRepositoryTestCase
{
    private static int $nextPublisherId = 70000;

    protected function getRepository(): PublisherRepository
    {
        return self::getService(PublisherRepository::class);
    }

    private function getUniquePublisherId(): int
    {
        return ++self::$nextPublisherId;
    }

    protected function createNewEntity(): Publisher
    {
        // 使用唯一ID避免冲突
        $publisherId = $this->getUniquePublisherId();

        // 创建发布商但不持久化
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");

        return $publisher;
    }

    protected function onSetUp(): void
    {
        // Repository 测试设置方法
        // 清理EntityManager避免identity map冲突
        self::getEntityManager()->clear();

        // 只清理测试过程中创建的数据，保留DataFixtures的数据
        // 删除publisher_id大于等于70000的Publisher（测试数据范围）
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\Publisher p WHERE p.publisher_id >= 70000')
            ->execute()
        ;
        self::getEntityManager()->clear();
    }

    /**
     * 测试基本CRUD操作
     */
    public function testCrudOperations(): void
    {
        $repository = $this->getRepository();

        // 清理EntityManager避免ID冲突
        self::getEntityManager()->clear();

        // 创建测试发布商
        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");

        // 测试保存
        $repository->save($publisher);
        $this->assertEntityPersisted($publisher);

        // 清理EntityManager以避免身份冲突
        self::getEntityManager()->clear();

        // 测试查找
        $foundPublisher = $repository->find($publisherId);
        $this->assertNotNull($foundPublisher);
        $this->assertSame($publisherId, $foundPublisher->getPublisherId());
        $this->assertSame("test-token-{$publisherId}", $foundPublisher->getToken());

        // 测试更新
        $foundPublisher->setToken('updated-token');
        $repository->save($foundPublisher);

        $updatedPublisher = $repository->find($publisherId);
        $this->assertNotNull($updatedPublisher);
        $this->assertSame('updated-token', $updatedPublisher->getToken());

        // 测试删除（使用从数据库查找的实体）
        $repository->remove($updatedPublisher);
        $this->assertEntityNotExists(Publisher::class, $publisherId);
    }

    /**
     * 测试根据发布商ID查找
     */
    public function testFindByPublisherId(): void
    {
        $repository = $this->getRepository();

        // 创建发布商
        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 测试查找存在的发布商（使用正确的ID）
        $foundPublisher = $repository->findByPublisherId($publisherId);
        $this->assertNotNull($foundPublisher);
        $this->assertSame($publisherId, $foundPublisher->getPublisherId());
        $this->assertSame("test-token-{$publisherId}", $foundPublisher->getToken());

        // 测试查找不存在的发布商
        $nonExistentPublisher = $repository->findByPublisherId(99999);
        $this->assertNull($nonExistentPublisher);
    }

    /**
     * 测试查找或创建发布商
     */
    public function testFindOrCreate(): void
    {
        $repository = $this->getRepository();
        $publisherId = $this->getUniquePublisherId();

        // 测试创建新发布商
        $publisher = $repository->findOrCreate($publisherId, 'test-token');
        $this->assertSame($publisherId, $publisher->getPublisherId());
        $this->assertSame('test-token', $publisher->getToken());

        // 确保发布商已持久化
        $this->assertEntityPersisted($publisher);

        // 测试查找已存在的发布商（相同token）
        $foundPublisher1 = $repository->findOrCreate($publisherId, 'test-token');
        $this->assertSame($publisherId, $foundPublisher1->getPublisherId());
        $this->assertSame('test-token', $foundPublisher1->getToken());

        // 验证是同一个对象（通过ID比较）
        $this->assertSame($publisher->getPublisherId(), $foundPublisher1->getPublisherId());

        // 测试查找已存在的发布商但更新token
        $foundPublisher2 = $repository->findOrCreate($publisherId, 'updated-token');
        $this->assertSame($publisherId, $foundPublisher2->getPublisherId());
        $this->assertSame('updated-token', $foundPublisher2->getToken());

        // 验证token已更新
        $updatedPublisher = $repository->find($publisherId);
        $this->assertNotNull($updatedPublisher);
        $this->assertSame('updated-token', $updatedPublisher->getToken());
    }

    /**
     * 测试多个发布商的查找或创建
     */
    public function testFindOrCreateMultiplePublishers(): void
    {
        $repository = $this->getRepository();

        $publisherId1 = $this->getUniquePublisherId();
        $publisherId2 = $this->getUniquePublisherId();
        $publisherId3 = $this->getUniquePublisherId();

        // 创建多个发布商
        $publisher1 = $repository->findOrCreate($publisherId1, 'token1');
        $publisher2 = $repository->findOrCreate($publisherId2, 'token2');
        $publisher3 = $repository->findOrCreate($publisherId3, 'token3');

        // 验证所有发布商都已创建
        $this->assertEntityPersisted($publisher1);
        $this->assertEntityPersisted($publisher2);
        $this->assertEntityPersisted($publisher3);

        // 验证可以独立查找
        $foundPublisher1 = $repository->findByPublisherId($publisherId1);
        $foundPublisher2 = $repository->findByPublisherId($publisherId2);
        $foundPublisher3 = $repository->findByPublisherId($publisherId3);

        $this->assertNotNull($foundPublisher1);
        $this->assertNotNull($foundPublisher2);
        $this->assertNotNull($foundPublisher3);

        $this->assertSame('token1', $foundPublisher1->getToken());
        $this->assertSame('token2', $foundPublisher2->getToken());
        $this->assertSame('token3', $foundPublisher3->getToken());
    }

    /**
     * 测试发布商ID的唯一性
     */
    public function testPublisherIdUniqueness(): void
    {
        $repository = $this->getRepository();

        // 使用唯一ID避免与其他测试冲突
        $uniqueId = time() + rand(100000, 999999);

        // 创建第一个发布商
        $publisher1 = new Publisher();
        $publisher1->setPublisherId($uniqueId);
        $publisher1->setToken('token1');
        $this->persistAndFlush($publisher1);

        // 清理EntityManager避免身份映射冲突
        self::getEntityManager()->clear();

        // 尝试创建相同ID的发布商（应该失败）
        $this->expectException(UniqueConstraintViolationException::class);

        $publisher2 = new Publisher();
        $publisher2->setPublisherId($uniqueId);
        $publisher2->setToken('token2');
        $this->persistAndFlush($publisher2);
    }

    /**
     * 测试发布商的基本属性
     */
    public function testPublisherBasicProperties(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $publisher->setCreateTime(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $publisher->setUpdateTime(new \DateTimeImmutable('2024-01-02 00:00:00'));

        $repository->save($publisher);

        $foundPublisher = $repository->find($publisherId);
        $this->assertNotNull($foundPublisher);

        // 测试基本属性
        $this->assertSame($publisherId, $foundPublisher->getPublisherId());
        $this->assertSame("test-token-{$publisherId}", $foundPublisher->getToken());

        // 测试时间戳
        $this->assertInstanceOf(\DateTimeInterface::class, $foundPublisher->getCreateTime());
        $this->assertInstanceOf(\DateTimeInterface::class, $foundPublisher->getUpdateTime());

        // 测试字符串表示
        $this->assertIsString((string) $foundPublisher);
        $this->assertStringContainsString((string) $publisherId, (string) $foundPublisher);
    }

    /**
     * 测试发布商的token更新
     */
    public function testPublisherTokenUpdate(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken('original-token');
        $repository->save($publisher);

        // 更新token
        $publisher->setToken('new-token');
        $repository->save($publisher);

        // 验证token已更新
        $updatedPublisher = $repository->find($publisherId);
        $this->assertNotNull($updatedPublisher);
        $this->assertSame('new-token', $updatedPublisher->getToken());

        // 测试通过findOrCreate更新token
        $repository->findOrCreate($publisherId, 'another-token');

        $finalPublisher = $repository->find($publisherId);
        $this->assertNotNull($finalPublisher);
        $this->assertSame('another-token', $finalPublisher->getToken());
    }

    /**
     * 测试发布商的查找性能
     */
    public function testPublisherLookupPerformance(): void
    {
        $repository = $this->getRepository();

        // 创建多个发布商
        $publishers = [];
        for ($i = 1; $i <= 10; ++$i) {
            $publisher = new Publisher();
            $publisher->setPublisherId($i);
            $publisher->setToken("token{$i}");
            $repository->save($publisher);
            $publishers[] = $publisher;
        }

        // 批量查找
        foreach ($publishers as $publisher) {
            $publisherId = $publisher->getPublisherId();
            $this->assertNotNull($publisherId, 'Publisher ID should not be null');

            $foundPublisher = $repository->findByPublisherId($publisherId);
            $this->assertNotNull($foundPublisher);
            $this->assertSame($publisher->getPublisherId(), $foundPublisher->getPublisherId());
            $this->assertSame($publisher->getToken(), $foundPublisher->getToken());
        }

        // 测试查找不存在的发布商
        $nonExistent = $repository->findByPublisherId(99999);
        $this->assertNull($nonExistent);
    }
}
