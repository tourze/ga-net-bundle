<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\CommissionRule;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\GaNetBundle\Enum\CommissionMode;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\YesNoFlag;
use Tourze\GaNetBundle\Repository\CommissionRuleRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(CommissionRuleRepository::class)]
#[RunTestsInSeparateProcesses]
final class CommissionRuleRepositoryTest extends AbstractRepositoryTestCase
{
    private static int $nextPublisherId = 30000;

    private static int $nextCampaignId = 30000;

    private static int $nextRuleId = 30000;

    private static int $incrementCounter = 0;

    protected function getRepository(): CommissionRuleRepository
    {
        return self::getService(CommissionRuleRepository::class);
    }

    private function getUniquePublisherId(): int
    {
        return ++self::$nextPublisherId;
    }

    private function getUniqueCampaignId(): int
    {
        return ++self::$nextCampaignId;
    }

    private function getUniqueRuleId(): int
    {
        return ++self::$nextRuleId;
    }

    private function getUniqueTimestamp(): int
    {
        return time() + (++self::$incrementCounter);
    }

    protected function createNewEntity(): CommissionRule
    {
        // 使用唯一时间戳避免 ID 冲突
        $timestamp = $this->getUniqueTimestamp();

        // 创建测试发布商和活动
        $publisher = new Publisher();
        $publisher->setPublisherId($timestamp);
        $publisher->setToken("test-token-{$timestamp}");
        $this->persistAndFlush($publisher);

        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaign->setId($timestamp + 1000000); // 确保与Publisher ID不冲突
        $campaign->setName("Test Campaign {$timestamp}");
        $campaign->setRegion('JPN');
        $campaign->setUrl("https://test-{$timestamp}.com");
        $campaign->setCurrency(Currency::CNY);
        $campaign->setStartTime('14-12-18');
        $campaign->setCookieExpireTime(2592000);
        $campaign->setSemPermitted(YesNoFlag::NO);
        $campaign->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign->setRebatePermitted(YesNoFlag::NO);
        $campaign->setHasDatafeed(YesNoFlag::NO);
        $campaign->setSupportWeapp(YesNoFlag::NO);
        $campaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign);

        // 创建佣金规则但不持久化
        $rule = new CommissionRule();
        $rule->setCampaign($campaign);
        $rule->setId($timestamp + 2000000); // 确保与其他实体ID不冲突
        $rule->setName("Test Commission Rule {$timestamp}");
        $rule->setMode(CommissionMode::PERCENTAGE);
        $rule->setRatio('0.05');
        $rule->setCurrency(Currency::CNY->value);
        $rule->setStartTime('14-12-18');

        return $rule;
    }

    protected function onSetUp(): void
    {
        // Repository 测试设置方法
        // 清理EntityManager避免identity map冲突
        self::getEntityManager()->clear();
    }

    /**
     * 测试基本CRUD操作
     */
    public function testCrudOperations(): void
    {
        $repository = $this->getRepository();

        // 清理测试数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\CommissionRule cr')
            ->execute()
        ;
        self::getEntityManager()->clear();

        // 创建测试发布商和活动
        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        $campaignId = $this->getUniqueCampaignId();
        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaign->setId($campaignId);
        $campaign->setName('Test Campaign');
        $campaign->setRegion('JPN');
        $campaign->setUrl('https://example.com');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setStartTime('14-12-18');
        $campaign->setCookieExpireTime(2592000);
        $campaign->setSemPermitted(YesNoFlag::NO);
        $campaign->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign->setRebatePermitted(YesNoFlag::NO);
        $campaign->setHasDatafeed(YesNoFlag::NO);
        $campaign->setSupportWeapp(YesNoFlag::NO);
        $campaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign);

        // 创建测试佣金规则
        $ruleId = $this->getUniqueRuleId();
        $rule = new CommissionRule();
        $rule->setCampaign($campaign);
        $rule->setId($ruleId);
        $rule->setName('Test Rule');
        $rule->setMode(CommissionMode::PERCENTAGE);
        $rule->setRatio('0.05');
        $rule->setCurrency(Currency::CNY->value);
        $rule->setStartTime('14-12-18');

        // 测试保存
        $repository->save($rule);
        $this->assertEntityPersisted($rule);

        // 清理EntityManager以避免identity map冲突
        self::getEntityManager()->clear();

        // 测试查找
        $foundRule = $repository->find($ruleId);
        $this->assertNotNull($foundRule);
        $this->assertSame('Test Rule', $foundRule->getName());
        $this->assertSame(CommissionMode::PERCENTAGE, $foundRule->getMode());

        // 测试更新
        $foundRule->setName('Updated Rule');
        $foundRule->setRatio('0.08');
        $repository->save($foundRule);

        // 再次清理EntityManager
        self::getEntityManager()->clear();

        $updatedRule = $repository->find($ruleId);
        $this->assertNotNull($updatedRule);
        $this->assertSame('Updated Rule', $updatedRule->getName());
        $this->assertSame('0.08', $updatedRule->getRatio());

        // 测试删除
        $repository->remove($updatedRule);
        $this->assertEntityNotExists(CommissionRule::class, $ruleId);
    }

    /**
     * 测试根据活动查找佣金规则
     */
    public function testFindByCampaign(): void
    {
        $repository = $this->getRepository();

        // 清理所有CommissionRule数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\CommissionRule cr')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建两个活动
        $campaign1Id = $this->getUniqueCampaignId();
        $campaign1 = new Campaign();
        $campaign1->setPublisher($publisher);
        $campaign1->setId($campaign1Id);
        $campaign1->setName('Campaign 1');
        $campaign1->setRegion('JPN');
        $campaign1->setUrl('https://campaign1.com');
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

        $campaign2Id = $this->getUniqueCampaignId();
        $campaign2 = new Campaign();
        $campaign2->setPublisher($publisher);
        $campaign2->setId($campaign2Id);
        $campaign2->setName('Campaign 2');
        $campaign2->setRegion('USA');
        $campaign2->setUrl('https://campaign2.com');
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

        // 为第一个活动创建佣金规则
        $rule1Id = $this->getUniqueRuleId();
        $rule1 = new CommissionRule();
        $rule1->setCampaign($campaign1);
        $rule1->setId($rule1Id);
        $rule1->setName('Rule 1');
        $rule1->setMode(CommissionMode::PERCENTAGE);
        $rule1->setRatio('0.05');
        $rule1->setCurrency(Currency::CNY->value);
        $rule1->setStartTime('14-12-18');
        $this->persistAndFlush($rule1);

        $rule2Id = $this->getUniqueRuleId();
        $rule2 = new CommissionRule();
        $rule2->setCampaign($campaign1);
        $rule2->setId($rule2Id);
        $rule2->setName('Rule 2');
        $rule2->setMode(CommissionMode::FIXED);
        $rule2->setCommission('10.00');
        $rule2->setCurrency(Currency::CNY->value);
        $rule2->setStartTime('14-12-19');
        $this->persistAndFlush($rule2);

        // 为第二个活动创建佣金规则
        $rule3Id = $this->getUniqueRuleId();
        $rule3 = new CommissionRule();
        $rule3->setCampaign($campaign2);
        $rule3->setId($rule3Id);
        $rule3->setName('Rule 3');
        $rule3->setMode(CommissionMode::PERCENTAGE);
        $rule3->setRatio('0.08');
        $rule3->setCurrency(Currency::USD->value);
        $rule3->setStartTime('14-12-18');
        $this->persistAndFlush($rule3);

        // 测试查找第一个活动的规则
        $campaign1Rules = $repository->findByCampaign($campaign1);
        $this->assertCount(2, $campaign1Rules);

        // 验证按开始时间降序排序
        $this->assertSame($rule2Id, $campaign1Rules[0]->getId()); // 14-12-19
        $this->assertSame($rule1Id, $campaign1Rules[1]->getId()); // 14-12-18

        // 测试查找第二个活动的规则
        $campaign2Rules = $repository->findByCampaign($campaign2);
        $this->assertCount(1, $campaign2Rules);
        $this->assertSame($rule3Id, $campaign2Rules[0]->getId());
    }

    /**
     * 测试根据佣金模式查找规则
     */
    public function testFindByMode(): void
    {
        $repository = $this->getRepository();

        // 清理所有CommissionRule数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\CommissionRule cr')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        $campaignId = $this->getUniqueCampaignId();
        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaign->setId($campaignId);
        $campaign->setName('Test Campaign');
        $campaign->setRegion('JPN');
        $campaign->setUrl('https://example.com');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setStartTime('14-12-18');
        $campaign->setCookieExpireTime(2592000);
        $campaign->setSemPermitted(YesNoFlag::NO);
        $campaign->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign->setRebatePermitted(YesNoFlag::NO);
        $campaign->setHasDatafeed(YesNoFlag::NO);
        $campaign->setSupportWeapp(YesNoFlag::NO);
        $campaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign);

        // 创建不同模式的规则
        $percentageRuleId = $this->getUniqueRuleId();
        $percentageRule = new CommissionRule();
        $percentageRule->setCampaign($campaign);
        $percentageRule->setId($percentageRuleId);
        $percentageRule->setName('Percentage Rule');
        $percentageRule->setMode(CommissionMode::PERCENTAGE);
        $percentageRule->setRatio('0.05');
        $percentageRule->setCurrency(Currency::CNY->value);
        $percentageRule->setStartTime('14-12-18');
        $this->persistAndFlush($percentageRule);

        $fixedRuleId = $this->getUniqueRuleId();
        $fixedRule = new CommissionRule();
        $fixedRule->setCampaign($campaign);
        $fixedRule->setId($fixedRuleId);
        $fixedRule->setName('Fixed Rule');
        $fixedRule->setMode(CommissionMode::FIXED);
        $fixedRule->setCommission('10.00');
        $fixedRule->setCurrency(Currency::CNY->value);
        $fixedRule->setStartTime('14-12-18');
        $this->persistAndFlush($fixedRule);

        // 测试按模式查找
        $percentageRules = $repository->findByMode(CommissionMode::PERCENTAGE->value);
        $this->assertCount(1, $percentageRules);
        $this->assertSame($percentageRuleId, $percentageRules[0]->getId());

        $fixedRules = $repository->findByMode(CommissionMode::FIXED->value);
        $this->assertCount(1, $fixedRules);
        $this->assertSame($fixedRuleId, $fixedRules[0]->getId());

        // 测试按模式和活动查找
        $percentageRulesWithCampaign = $repository->findByMode(CommissionMode::PERCENTAGE->value, $campaign);
        $this->assertCount(1, $percentageRulesWithCampaign);
        $this->assertSame($percentageRuleId, $percentageRulesWithCampaign[0]->getId());

        // 测试查找不存在的模式
        $nonExistentRules = $repository->findByMode(999);
        $this->assertCount(0, $nonExistentRules);
    }

    /**
     * 测试查找分成模式的佣金规则
     */
    public function testFindPercentageRules(): void
    {
        $repository = $this->getRepository();

        // 清理所有CommissionRule数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\CommissionRule cr')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaignId = $this->getUniqueCampaignId();
        $campaign->setId($campaignId);
        $campaign->setName('Test Campaign');
        $campaign->setRegion('JPN');
        $campaign->setUrl('https://example.com');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setStartTime('14-12-18');
        $campaign->setCookieExpireTime(2592000);
        $campaign->setSemPermitted(YesNoFlag::NO);
        $campaign->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign->setRebatePermitted(YesNoFlag::NO);
        $campaign->setHasDatafeed(YesNoFlag::NO);
        $campaign->setSupportWeapp(YesNoFlag::NO);
        $campaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign);

        // 创建分成模式规则
        $percentageRule = new CommissionRule();
        $percentageRule->setCampaign($campaign);
        $percentageRuleId = $this->getUniqueRuleId();
        $percentageRule->setId($percentageRuleId);
        $percentageRule->setName('Percentage Rule');
        $percentageRule->setMode(CommissionMode::PERCENTAGE);
        $percentageRule->setRatio('0.05');
        $percentageRule->setCurrency(Currency::CNY->value);
        $percentageRule->setStartTime('14-12-18');
        $this->persistAndFlush($percentageRule);

        // 创建固定佣金模式规则
        $fixedRule = new CommissionRule();
        $fixedRule->setCampaign($campaign);
        $fixedRuleId = $this->getUniqueRuleId();
        $fixedRule->setId($fixedRuleId);
        $fixedRule->setName('Fixed Rule');
        $fixedRule->setMode(CommissionMode::FIXED);
        $fixedRule->setCommission('10.00');
        $fixedRule->setCurrency(Currency::CNY->value);
        $fixedRule->setStartTime('14-12-18');
        $this->persistAndFlush($fixedRule);

        // 测试查找分成模式规则
        $percentageRules = $repository->findPercentageRules();

        $this->assertCount(1, $percentageRules);
        $this->assertSame($percentageRuleId, $percentageRules[0]->getId());
        $this->assertSame(CommissionMode::PERCENTAGE, $percentageRules[0]->getMode());

        // 测试按活动查找分成模式规则
        $percentageRulesWithCampaign = $repository->findPercentageRules($campaign);
        $this->assertCount(1, $percentageRulesWithCampaign);
        $this->assertSame($percentageRuleId, $percentageRulesWithCampaign[0]->getId());
    }

    /**
     * 测试查找固定佣金模式的规则
     */
    public function testFindFixedRules(): void
    {
        $repository = $this->getRepository();

        // 清理所有CommissionRule数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\CommissionRule cr')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaignId = $this->getUniqueCampaignId();
        $campaign->setId($campaignId);
        $campaign->setName('Test Campaign');
        $campaign->setRegion('JPN');
        $campaign->setUrl('https://example.com');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setStartTime('14-12-18');
        $campaign->setCookieExpireTime(2592000);
        $campaign->setSemPermitted(YesNoFlag::NO);
        $campaign->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign->setRebatePermitted(YesNoFlag::NO);
        $campaign->setHasDatafeed(YesNoFlag::NO);
        $campaign->setSupportWeapp(YesNoFlag::NO);
        $campaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign);

        // 创建分成模式规则
        $percentageRule = new CommissionRule();
        $percentageRule->setCampaign($campaign);
        $percentageRuleId = $this->getUniqueRuleId();
        $percentageRule->setId($percentageRuleId);
        $percentageRule->setName('Percentage Rule');
        $percentageRule->setMode(CommissionMode::PERCENTAGE);
        $percentageRule->setRatio('0.05');
        $percentageRule->setCurrency(Currency::CNY->value);
        $percentageRule->setStartTime('14-12-18');
        $this->persistAndFlush($percentageRule);

        // 创建固定佣金模式规则
        $fixedRule = new CommissionRule();
        $fixedRule->setCampaign($campaign);
        $fixedRuleId = $this->getUniqueRuleId();
        $fixedRule->setId($fixedRuleId);
        $fixedRule->setName('Fixed Rule');
        $fixedRule->setMode(CommissionMode::FIXED);
        $fixedRule->setCommission('10.00');
        $fixedRule->setCurrency(Currency::CNY->value);
        $fixedRule->setStartTime('14-12-18');
        $this->persistAndFlush($fixedRule);

        // 测试查找固定佣金模式规则
        $fixedRules = $repository->findFixedRules();
        $this->assertCount(1, $fixedRules);
        $this->assertSame($fixedRuleId, $fixedRules[0]->getId());
        $this->assertSame(CommissionMode::FIXED, $fixedRules[0]->getMode());

        // 测试按活动查找固定佣金模式规则
        $fixedRulesWithCampaign = $repository->findFixedRules($campaign);
        $this->assertCount(1, $fixedRulesWithCampaign);
        $this->assertSame($fixedRuleId, $fixedRulesWithCampaign[0]->getId());
    }

    /**
     * 测试根据货币查找佣金规则
     */
    public function testFindByCurrency(): void
    {
        $repository = $this->getRepository();

        // 清理所有CommissionRule数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\CommissionRule cr')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaignId = $this->getUniqueCampaignId();
        $campaign->setId($campaignId);
        $campaign->setName('Test Campaign');
        $campaign->setRegion('JPN');
        $campaign->setUrl('https://example.com');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setStartTime('14-12-18');
        $campaign->setCookieExpireTime(2592000);
        $campaign->setSemPermitted(YesNoFlag::NO);
        $campaign->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign->setRebatePermitted(YesNoFlag::NO);
        $campaign->setHasDatafeed(YesNoFlag::NO);
        $campaign->setSupportWeapp(YesNoFlag::NO);
        $campaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign);

        // 创建不同货币的规则
        $cnyRule = new CommissionRule();
        $cnyRule->setCampaign($campaign);
        $cnyRuleId = $this->getUniqueRuleId();
        $cnyRule->setId($cnyRuleId);
        $cnyRule->setName('CNY Rule');
        $cnyRule->setMode(CommissionMode::PERCENTAGE);
        $cnyRule->setRatio('0.05');
        $cnyRule->setCurrency(Currency::CNY->value);
        $cnyRule->setStartTime('14-12-18');
        $this->persistAndFlush($cnyRule);

        $usdRule = new CommissionRule();
        $usdRule->setCampaign($campaign);
        $usdRuleId = $this->getUniqueRuleId();
        $usdRule->setId($usdRuleId);
        $usdRule->setName('USD Rule');
        $usdRule->setMode(CommissionMode::FIXED);
        $usdRule->setCommission('10.00');
        $usdRule->setCurrency(Currency::USD->value);
        $usdRule->setStartTime('14-12-19');
        $this->persistAndFlush($usdRule);

        // 测试按货币查找
        $cnyRules = $repository->findByCurrency('CNY');
        $this->assertCount(1, $cnyRules);
        $this->assertSame($cnyRuleId, $cnyRules[0]->getId());

        $usdRules = $repository->findByCurrency('USD');
        $this->assertCount(1, $usdRules);
        $this->assertSame($usdRuleId, $usdRules[0]->getId());

        // 测试按货币和活动查找
        $cnyRulesWithCampaign = $repository->findByCurrency('CNY', $campaign);
        $this->assertCount(1, $cnyRulesWithCampaign);
        $this->assertSame($cnyRuleId, $cnyRulesWithCampaign[0]->getId());

        // 测试查找不存在的货币
        $nonExistentRules = $repository->findByCurrency('NONEXISTENT');
        $this->assertCount(0, $nonExistentRules);

        // 验证按开始时间降序排序
        $allRules = $repository->findByCurrency('CNY');
        if (count($allRules) > 1) {
            for ($i = 0; $i < count($allRules) - 1; ++$i) {
                $this->assertGreaterThanOrEqual(
                    $allRules[$i + 1]->getStartTime(),
                    $allRules[$i]->getStartTime(),
                    'Rules should be ordered by start time DESC'
                );
            }
        }
    }

    /**
     * 测试查找最高佣金比例的规则
     */
    public function testFindHighestRatioRules(): void
    {
        $repository = $this->getRepository();

        // 清理所有CommissionRule数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\CommissionRule cr')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaignId = $this->getUniqueCampaignId();
        $campaign->setId($campaignId);
        $campaign->setName('Test Campaign');
        $campaign->setRegion('JPN');
        $campaign->setUrl('https://example.com');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setStartTime('14-12-18');
        $campaign->setCookieExpireTime(2592000);
        $campaign->setSemPermitted(YesNoFlag::NO);
        $campaign->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign->setRebatePermitted(YesNoFlag::NO);
        $campaign->setHasDatafeed(YesNoFlag::NO);
        $campaign->setSupportWeapp(YesNoFlag::NO);
        $campaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign);

        // 创建不同比例的分成规则
        $lowRule = new CommissionRule();
        $lowRule->setCampaign($campaign);
        $lowRuleId = $this->getUniqueRuleId();
        $lowRule->setId($lowRuleId);
        $lowRule->setName('Low Ratio Rule');
        $lowRule->setMode(CommissionMode::PERCENTAGE);
        $lowRule->setRatio('0.02');
        $lowRule->setCurrency(Currency::CNY->value);
        $lowRule->setStartTime('14-12-18');
        $this->persistAndFlush($lowRule);

        $highRule = new CommissionRule();
        $highRule->setCampaign($campaign);
        $highRuleId = $this->getUniqueRuleId();
        $highRule->setId($highRuleId);
        $highRule->setName('High Ratio Rule');
        $highRule->setMode(CommissionMode::PERCENTAGE);
        $highRule->setRatio('0.08');
        $highRule->setCurrency(Currency::CNY->value);
        $highRule->setStartTime('14-12-18');
        $this->persistAndFlush($highRule);

        $mediumRule = new CommissionRule();
        $mediumRule->setCampaign($campaign);
        $mediumRuleId = $this->getUniqueRuleId();
        $mediumRule->setId($mediumRuleId);
        $mediumRule->setName('Medium Ratio Rule');
        $mediumRule->setMode(CommissionMode::PERCENTAGE);
        $mediumRule->setRatio('0.05');
        $mediumRule->setCurrency(Currency::CNY->value);
        $mediumRule->setStartTime('14-12-18');
        $this->persistAndFlush($mediumRule);

        // 创建固定佣金规则（不应该出现在结果中）
        $fixedRule = new CommissionRule();
        $fixedRule->setCampaign($campaign);
        $fixedRuleId = $this->getUniqueRuleId();
        $fixedRule->setId($fixedRuleId);
        $fixedRule->setName('Fixed Rule');
        $fixedRule->setMode(CommissionMode::FIXED);
        $fixedRule->setCommission('10.00');
        $fixedRule->setCurrency(Currency::CNY->value);
        $fixedRule->setStartTime('14-12-18');
        $this->persistAndFlush($fixedRule);

        // 测试查找最高佣金比例规则
        $highestRatioRules = $repository->findHighestRatioRules($campaign, 2);
        $this->assertCount(2, $highestRatioRules);

        // 验证按佣金比例降序排序
        $this->assertSame('0.08', $highestRatioRules[0]->getRatio());
        $this->assertSame('0.05', $highestRatioRules[1]->getRatio());

        // 验证都是分成模式
        foreach ($highestRatioRules as $rule) {
            $this->assertSame(CommissionMode::PERCENTAGE, $rule->getMode());
        }

        // 测试限制数量
        $top1Rule = $repository->findHighestRatioRules($campaign, 1);
        $this->assertCount(1, $top1Rule);
        $this->assertSame('0.08', $top1Rule[0]->getRatio());
    }

    /**
     * 测试查找或创建佣金规则
     */
    public function testFindOrCreate(): void
    {
        $repository = $this->getRepository();

        // 清理所有CommissionRule数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\CommissionRule cr')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaignId = $this->getUniqueCampaignId();
        $campaign->setId($campaignId);
        $campaign->setName('Test Campaign');
        $campaign->setRegion('JPN');
        $campaign->setUrl('https://example.com');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setStartTime('14-12-18');
        $this->persistAndFlush($campaign);

        // 测试创建新规则
        $ruleId = $this->getUniqueRuleId();
        $rule = $repository->findOrCreate($ruleId, $campaign);
        $this->assertSame($ruleId, $rule->getId());
        $this->assertSame($campaign, $rule->getCampaign());

        // 确保规则已持久化
        self::getEntityManager()->flush();
        $this->assertEntityPersisted($rule);

        // 测试查找已存在的规则
        $foundRule = $repository->findOrCreate($ruleId, $campaign);
        $this->assertSame($ruleId, $foundRule->getId());
        $foundRuleCampaign = $foundRule->getCampaign();
        $this->assertNotNull($foundRuleCampaign);
        $this->assertSame($campaign->getId(), $foundRuleCampaign->getId());

        // 验证是同一个对象（通过ID比较）
        $this->assertSame($rule->getId(), $foundRule->getId());
    }
}
