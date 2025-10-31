<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\YesNoFlag;
use Tourze\GaNetBundle\Repository\CampaignRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(CampaignRepository::class)]
#[RunTestsInSeparateProcesses]
final class CampaignRepositoryTest extends AbstractRepositoryTestCase
{
    protected function getRepository(): CampaignRepository
    {
        return self::getService(CampaignRepository::class);
    }

    private static int $nextId = 10000;

    private static int $incrementCounter = 0;

    private function getUniqueTimestamp(): int
    {
        return time() + (++self::$incrementCounter);
    }

    protected function createNewEntity(): Campaign
    {
        $timestamp = $this->getUniqueTimestamp();
        $publisher = new Publisher();
        $publisher->setPublisherId($timestamp);
        $publisher->setToken("test-token-{$timestamp}");
        $this->persistAndFlush($publisher);

        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaign->setId(++self::$nextId); // 现在可以手动设置ID了
        $campaign->setName("Test Campaign {$timestamp}");
        $campaign->setRegion('CN');
        $campaign->setUrl("https://example.com/{$timestamp}");
        $campaign->setCurrency(Currency::CNY);
        $campaign->setStartTime('2023-01-01 00:00:00');
        $campaign->setCookieExpireTime(2592000);
        $campaign->setSemPermitted(YesNoFlag::NO);
        $campaign->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign->setRebatePermitted(YesNoFlag::NO);
        $campaign->setHasDatafeed(YesNoFlag::NO);
        $campaign->setSupportWeapp(YesNoFlag::NO);
        $campaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);

        return $campaign;
    }

    protected function onSetUp(): void
    {
        // 集成测试设置方法
        // 清理EntityManager避免identity map冲突
        self::getEntityManager()->clear();
    }

    public function testCrudOperations(): void
    {
        $repository = $this->getRepository();

        // 创建测试发布商
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $this->persistAndFlush($publisher);

        // 创建测试活动
        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaign->setId(1001);
        $campaign->setName('Test Campaign');
        $campaign->setRegion('JPN');
        $campaign->setUrl('https://example.com');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setStartTime('14-12-18');
        $campaign->setCookieExpireTime(2592000); // 30天
        $campaign->setSemPermitted(YesNoFlag::NO); // 否
        $campaign->setIsLinkCustomizable(YesNoFlag::NO); // 否
        $campaign->setRebatePermitted(YesNoFlag::NO); // 否
        $campaign->setHasDatafeed(YesNoFlag::NO); // 无
        $campaign->setSupportWeapp(YesNoFlag::NO); // 否
        $campaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);

        // 测试保存
        $repository->save($campaign);
        $this->assertEntityPersisted($campaign);
        $this->assertNotNull($campaign->getId());

        // 获取生成的ID
        $campaignId = $campaign->getId();

        // 清理EntityManager以避免identity map冲突
        self::getEntityManager()->clear();

        // 测试查找
        $foundCampaign = $repository->find($campaignId);
        $this->assertNotNull($foundCampaign);
        $this->assertSame('Test Campaign', $foundCampaign->getName());
        $this->assertSame('JPN', $foundCampaign->getRegion());

        // 测试更新
        $foundCampaign->setName('Updated Campaign');
        $repository->save($foundCampaign);

        // 再次清理EntityManager
        self::getEntityManager()->clear();

        $updatedCampaign = $repository->find($campaignId);
        $this->assertNotNull($updatedCampaign);
        $this->assertSame('Updated Campaign', $updatedCampaign->getName());

        // 测试删除
        $repository->remove($updatedCampaign);
        $this->assertEntityNotExists(Campaign::class, $campaignId);
    }

    public function testFindByPublisher(): void
    {
        $repository = $this->getRepository();

        // 创建两个发布商
        $publisher1 = new Publisher();
        $publisher1->setPublisherId(12345);
        $publisher1->setToken('token1');
        $publisher2 = new Publisher();
        $publisher2->setPublisherId(67890);
        $publisher2->setToken('token2');
        $this->persistAndFlush($publisher1);
        $this->persistAndFlush($publisher2);

        // 为第一个发布商创建活动
        $campaign1 = new Campaign();
        $campaign1->setPublisher($publisher1);
        $campaign1->setId(2101);
        $campaign1->setName('Campaign 1');
        $campaign1->setRegion('JPN');
        $campaign1->setUrl('https://example1.com');
        $campaign1->setCurrency(Currency::CNY);
        $campaign1->setStartTime('14-12-18');
        $campaign1->setCookieExpireTime(2592000);
        $campaign1->setSemPermitted(YesNoFlag::NO);
        $campaign1->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign1->setRebatePermitted(YesNoFlag::NO);
        $campaign1->setHasDatafeed(YesNoFlag::NO);
        $campaign1->setSupportWeapp(YesNoFlag::NO);
        $campaign1->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign1);

        $campaign2 = new Campaign();
        $campaign2->setPublisher($publisher1);
        $campaign2->setId(2102);
        $campaign2->setName('Campaign 2');
        $campaign2->setRegion('USA');
        $campaign2->setUrl('https://example2.com');
        $campaign2->setCurrency(Currency::USD);
        $campaign2->setStartTime('14-12-18');
        $campaign2->setCookieExpireTime(2592000);
        $campaign2->setSemPermitted(YesNoFlag::NO);
        $campaign2->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign2->setRebatePermitted(YesNoFlag::NO);
        $campaign2->setHasDatafeed(YesNoFlag::NO);
        $campaign2->setSupportWeapp(YesNoFlag::NO);
        $campaign2->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign2);

        // 为第二个发布商创建活动
        $campaign3 = new Campaign();
        $campaign3->setPublisher($publisher2);
        $campaign3->setId(2103);
        $campaign3->setName('Campaign 3');
        $campaign3->setRegion('EUR');
        $campaign3->setUrl('https://example3.com');
        $campaign3->setCurrency(Currency::EUR);
        $campaign3->setStartTime('14-12-18');
        $campaign3->setCookieExpireTime(2592000);
        $campaign3->setSemPermitted(YesNoFlag::NO);
        $campaign3->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign3->setRebatePermitted(YesNoFlag::NO);
        $campaign3->setHasDatafeed(YesNoFlag::NO);
        $campaign3->setSupportWeapp(YesNoFlag::NO);
        $campaign3->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign3);

        // 测试查找第一个发布商的活动
        $publisher1Campaigns = $repository->findByPublisher($publisher1);
        $this->assertCount(2, $publisher1Campaigns);

        $campaignIds = array_map(fn ($c) => $c->getId(), $publisher1Campaigns);
        $this->assertContains($campaign1->getId(), $campaignIds);
        $this->assertContains($campaign2->getId(), $campaignIds);

        // 测试查找第二个发布商的活动
        $publisher2Campaigns = $repository->findByPublisher($publisher2);
        $this->assertCount(1, $publisher2Campaigns);
        $this->assertSame($campaign3->getId(), $publisher2Campaigns[0]->getId());
    }

    public function testFindActiveByPublisher(): void
    {
        $repository = $this->getRepository();

        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $this->persistAndFlush($publisher);

        // 创建活跃活动
        $activeCampaign = new Campaign();
        $activeCampaign->setPublisher($publisher);
        $activeCampaign->setId(7001);
        $activeCampaign->setName('Active Campaign');
        $activeCampaign->setRegion('JPN');
        $activeCampaign->setUrl('https://active.com');
        $activeCampaign->setCurrency(Currency::CNY);
        $activeCampaign->setStartTime('14-12-18');
        $activeCampaign->setCookieExpireTime(2592000);
        $activeCampaign->setSemPermitted(YesNoFlag::NO);
        $activeCampaign->setIsLinkCustomizable(YesNoFlag::NO);
        $activeCampaign->setRebatePermitted(YesNoFlag::NO);
        $activeCampaign->setHasDatafeed(YesNoFlag::NO);
        $activeCampaign->setSupportWeapp(YesNoFlag::NO);
        $activeCampaign->setApplicationStatus(CampaignApplicationStatus::APPROVED); // 申请通过
        $this->persistAndFlush($activeCampaign);

        // 创建非活跃活动
        $inactiveCampaign = new Campaign();
        $inactiveCampaign->setPublisher($publisher);
        $inactiveCampaign->setId(7002);
        $inactiveCampaign->setName('Inactive Campaign');
        $inactiveCampaign->setRegion('JPN');
        $inactiveCampaign->setUrl('https://inactive.com');
        $inactiveCampaign->setCurrency(Currency::CNY);
        $inactiveCampaign->setStartTime('14-12-18');
        $inactiveCampaign->setCookieExpireTime(2592000);
        $inactiveCampaign->setSemPermitted(YesNoFlag::NO);
        $inactiveCampaign->setIsLinkCustomizable(YesNoFlag::NO);
        $inactiveCampaign->setRebatePermitted(YesNoFlag::NO);
        $inactiveCampaign->setHasDatafeed(YesNoFlag::NO);
        $inactiveCampaign->setSupportWeapp(YesNoFlag::NO);
        $inactiveCampaign->setApplicationStatus(CampaignApplicationStatus::NOT_APPLIED); // 未通过
        $this->persistAndFlush($inactiveCampaign);

        // 测试查找活跃活动
        $activeCampaigns = $repository->findActiveByPublisher($publisher);
        $this->assertCount(1, $activeCampaigns);
        $this->assertSame($activeCampaign->getId(), $activeCampaigns[0]->getId());
        $this->assertSame('Active Campaign', $activeCampaigns[0]->getName());
    }

    /**
     * 测试根据地区查找活动
     */
    public function testFindByRegion(): void
    {
        $repository = $this->getRepository();

        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $this->persistAndFlush($publisher);

        // 创建不同地区的活动
        $jpnCampaign = new Campaign();
        $jpnCampaign->setPublisher($publisher);
        $jpnCampaign->setId(3001);
        $jpnCampaign->setName('Japan Campaign');
        $jpnCampaign->setRegion('JPN');
        $jpnCampaign->setUrl('https://japan.com');
        $jpnCampaign->setCurrency(Currency::CNY);
        $jpnCampaign->setStartTime('14-12-18');
        $jpnCampaign->setCookieExpireTime(2592000);
        $jpnCampaign->setSemPermitted(YesNoFlag::NO);
        $jpnCampaign->setIsLinkCustomizable(YesNoFlag::NO);
        $jpnCampaign->setRebatePermitted(YesNoFlag::NO);
        $jpnCampaign->setHasDatafeed(YesNoFlag::NO);
        $jpnCampaign->setSupportWeapp(YesNoFlag::NO);
        $jpnCampaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($jpnCampaign);

        $usaCampaign = new Campaign();
        $usaCampaign->setPublisher($publisher);
        $usaCampaign->setId(3002);
        $usaCampaign->setName('USA Campaign');
        $usaCampaign->setRegion('USA');
        $usaCampaign->setUrl('https://usa.com');
        $usaCampaign->setCurrency(Currency::USD);
        $usaCampaign->setStartTime('14-12-18');
        $usaCampaign->setCookieExpireTime(2592000);
        $usaCampaign->setSemPermitted(YesNoFlag::NO);
        $usaCampaign->setIsLinkCustomizable(YesNoFlag::NO);
        $usaCampaign->setRebatePermitted(YesNoFlag::NO);
        $usaCampaign->setHasDatafeed(YesNoFlag::NO);
        $usaCampaign->setSupportWeapp(YesNoFlag::NO);
        $usaCampaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($usaCampaign);

        // 测试按地区查找
        $jpnCampaigns = $repository->findByRegion('JPN');
        $this->assertCount(1, $jpnCampaigns);
        $this->assertSame(3001, $jpnCampaigns[0]->getId());

        // 测试按地区和发布商查找
        $jpnCampaignsWithPublisher = $repository->findByRegion('JPN', $publisher);
        $this->assertCount(1, $jpnCampaignsWithPublisher);
        $this->assertSame(3001, $jpnCampaignsWithPublisher[0]->getId());

        // 测试查找不存在的地区
        $nonExistentCampaigns = $repository->findByRegion('NONEXISTENT');
        $this->assertCount(0, $nonExistentCampaigns);
    }

    /**
     * 测试根据货币类型查找活动
     */
    public function testFindByCurrency(): void
    {
        $repository = $this->getRepository();

        // 清理可能存在的测试数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\Campaign c WHERE c.currency = :currency')
            ->setParameter('currency', 'CNY')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $this->persistAndFlush($publisher);

        // 创建不同货币的活动
        $cnyCampaign = new Campaign();
        $cnyCampaign->setPublisher($publisher);
        $cnyCampaign->setId(4001);
        $cnyCampaign->setName('CNY Campaign');
        $cnyCampaign->setRegion('JPN');
        $cnyCampaign->setUrl('https://cny.com');
        $cnyCampaign->setCurrency(Currency::CNY);
        $cnyCampaign->setStartTime('14-12-18');
        $cnyCampaign->setCookieExpireTime(2592000);
        $cnyCampaign->setSemPermitted(YesNoFlag::NO);
        $cnyCampaign->setIsLinkCustomizable(YesNoFlag::NO);
        $cnyCampaign->setRebatePermitted(YesNoFlag::NO);
        $cnyCampaign->setHasDatafeed(YesNoFlag::NO);
        $cnyCampaign->setSupportWeapp(YesNoFlag::NO);
        $cnyCampaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($cnyCampaign);

        $usdCampaign = new Campaign();
        $usdCampaign->setPublisher($publisher);
        $usdCampaign->setId(4002);
        $usdCampaign->setName('USD Campaign');
        $usdCampaign->setRegion('USA');
        $usdCampaign->setUrl('https://usd.com');
        $usdCampaign->setCurrency(Currency::USD);
        $usdCampaign->setStartTime('14-12-18');
        $usdCampaign->setCookieExpireTime(2592000);
        $usdCampaign->setSemPermitted(YesNoFlag::NO);
        $usdCampaign->setIsLinkCustomizable(YesNoFlag::NO);
        $usdCampaign->setRebatePermitted(YesNoFlag::NO);
        $usdCampaign->setHasDatafeed(YesNoFlag::NO);
        $usdCampaign->setSupportWeapp(YesNoFlag::NO);
        $usdCampaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($usdCampaign);

        // 测试按货币查找
        $cnyCampaigns = $repository->findByCurrency(Currency::CNY->value);
        $this->assertCount(1, $cnyCampaigns);
        $this->assertSame(4001, $cnyCampaigns[0]->getId());

        // 测试按货币和发布商查找
        $cnyCampaignsWithPublisher = $repository->findByCurrency(Currency::CNY->value, $publisher);
        $this->assertCount(1, $cnyCampaignsWithPublisher);
        $this->assertSame(4001, $cnyCampaignsWithPublisher[0]->getId());

        // 测试查找不存在的货币
        $nonExistentCampaigns = $repository->findByCurrency('NONEXISTENT');
        $this->assertCount(0, $nonExistentCampaigns);
    }

    /**
     * 测试查找或创建活动
     */
    public function testFindOrCreate(): void
    {
        $repository = $this->getRepository();

        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $this->persistAndFlush($publisher);

        // 测试创建新活动
        $campaign = $repository->findOrCreate(5001, $publisher);
        $this->assertSame(5001, $campaign->getId());
        $this->assertSame($publisher, $campaign->getPublisher());

        // 确保活动已持久化
        self::getEntityManager()->flush();
        $this->assertEntityPersisted($campaign);

        // 测试查找已存在的活动
        $foundCampaign = $repository->findOrCreate(5001, $publisher);
        $this->assertSame(5001, $foundCampaign->getId());
        $foundCampaignPublisher = $foundCampaign->getPublisher();
        $this->assertNotNull($foundCampaignPublisher);
        $this->assertEquals($publisher->getPublisherId(), $foundCampaignPublisher->getPublisherId());

        // 验证是同一个对象（通过ID比较）
        $this->assertSame($campaign->getId(), $foundCampaign->getId());
    }

    /**
     * 测试活动名称排序
     */
    public function testActiveCampaignsAreOrderedByName(): void
    {
        $repository = $this->getRepository();

        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        $this->persistAndFlush($publisher);

        // 创建多个活跃活动，名称乱序
        $campaignB = new Campaign();
        $campaignB->setPublisher($publisher);
        $campaignB->setId(6001);
        $campaignB->setName('B Campaign');
        $campaignB->setRegion('JPN');
        $campaignB->setUrl('https://b.com');
        $campaignB->setCurrency(Currency::CNY);
        $campaignB->setStartTime('14-12-18');
        $campaignB->setCookieExpireTime(2592000);
        $campaignB->setSemPermitted(YesNoFlag::NO);
        $campaignB->setIsLinkCustomizable(YesNoFlag::NO);
        $campaignB->setRebatePermitted(YesNoFlag::NO);
        $campaignB->setHasDatafeed(YesNoFlag::NO);
        $campaignB->setSupportWeapp(YesNoFlag::NO);
        $campaignB->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaignB);

        $campaignA = new Campaign();
        $campaignA->setPublisher($publisher);
        $campaignA->setId(6002);
        $campaignA->setName('A Campaign');
        $campaignA->setRegion('JPN');
        $campaignA->setUrl('https://a.com');
        $campaignA->setCurrency(Currency::CNY);
        $campaignA->setStartTime('14-12-18');
        $campaignA->setCookieExpireTime(2592000);
        $campaignA->setSemPermitted(YesNoFlag::NO);
        $campaignA->setIsLinkCustomizable(YesNoFlag::NO);
        $campaignA->setRebatePermitted(YesNoFlag::NO);
        $campaignA->setHasDatafeed(YesNoFlag::NO);
        $campaignA->setSupportWeapp(YesNoFlag::NO);
        $campaignA->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaignA);

        $campaignC = new Campaign();
        $campaignC->setPublisher($publisher);
        $campaignC->setId(6003);
        $campaignC->setName('C Campaign');
        $campaignC->setRegion('JPN');
        $campaignC->setUrl('https://c.com');
        $campaignC->setCurrency(Currency::CNY);
        $campaignC->setStartTime('14-12-18');
        $campaignC->setCookieExpireTime(2592000);
        $campaignC->setSemPermitted(YesNoFlag::NO);
        $campaignC->setIsLinkCustomizable(YesNoFlag::NO);
        $campaignC->setRebatePermitted(YesNoFlag::NO);
        $campaignC->setHasDatafeed(YesNoFlag::NO);
        $campaignC->setSupportWeapp(YesNoFlag::NO);
        $campaignC->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaignC);

        // 测试按名称排序
        $activeCampaigns = $repository->findActiveByPublisher($publisher);
        $this->assertCount(3, $activeCampaigns);
        $this->assertSame('A Campaign', $activeCampaigns[0]->getName());
        $this->assertSame('B Campaign', $activeCampaigns[1]->getName());
        $this->assertSame('C Campaign', $activeCampaigns[2]->getName());
    }
}
