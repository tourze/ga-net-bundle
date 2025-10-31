<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\CommissionRule;
use Tourze\GaNetBundle\Enum\CommissionMode;

#[When(env: 'test')]
#[When(env: 'dev')]
class CommissionRuleFixtures extends Fixture implements DependentFixtureInterface
{
    public const COMMISSION_RULE_1 = 'commission_rule_1';
    public const COMMISSION_RULE_2 = 'commission_rule_2';
    public const COMMISSION_RULE_3 = 'commission_rule_3';
    public const COMMISSION_RULE_4 = 'commission_rule_4';
    public const COMMISSION_RULE_5 = 'commission_rule_5';
    public const COMMISSION_RULE_6 = 'commission_rule_6';
    public const COMMISSION_RULE_7 = 'commission_rule_7';

    public function load(ObjectManager $manager): void
    {
        // 从 CampaignFixtures 获取活动引用
        $campaign1 = $this->getReference(CampaignFixtures::CAMPAIGN_1, Campaign::class);
        $campaign2 = $this->getReference(CampaignFixtures::CAMPAIGN_2, Campaign::class);
        $campaign3 = $this->getReference(CampaignFixtures::CAMPAIGN_3, Campaign::class);
        $campaign4 = $this->getReference(CampaignFixtures::CAMPAIGN_4, Campaign::class);

        // 为活动1创建佣金规则
        $rule1 = new CommissionRule();
        $rule1->setCampaign($campaign1);
        $rule1->setId(3001);
        $rule1->setName('京东通用佣金规则');
        $rule1->setMode(CommissionMode::PERCENTAGE);
        $rule1->setRatio('0.050000'); // 5%
        $rule1->setCurrency('CNY');
        $rule1->setStartTime('2023-01-01 00:00:00');
        $rule1->setMemo('京东通用佣金规则，适用于所有商品');
        $manager->persist($rule1);
        $this->addReference(self::COMMISSION_RULE_1, $rule1);

        $rule2 = new CommissionRule();
        $rule2->setCampaign($campaign1);
        $rule2->setId(3002);
        $rule2->setName('京东电子产品专项');
        $rule2->setMode(CommissionMode::PERCENTAGE);
        $rule2->setRatio('0.080000'); // 8%
        $rule2->setCurrency('CNY');
        $rule2->setStartTime('2023-01-15 00:00:00');
        $rule2->setMemo('电子产品专项佣金规则');
        $manager->persist($rule2);
        $this->addReference(self::COMMISSION_RULE_2, $rule2);

        // 为活动2创建佣金规则
        $rule3 = new CommissionRule();
        $rule3->setCampaign($campaign2);
        $rule3->setId(3003);
        $rule3->setName('淘宝通用佣金');
        $rule3->setMode(CommissionMode::PERCENTAGE);
        $rule3->setRatio('0.030000'); // 3%
        $rule3->setCurrency('CNY');
        $rule3->setStartTime('2023-02-01 00:00:00');
        $rule3->setMemo('淘宝通用佣金规则');
        $manager->persist($rule3);
        $this->addReference(self::COMMISSION_RULE_3, $rule3);

        $rule4 = new CommissionRule();
        $rule4->setCampaign($campaign2);
        $rule4->setId(3004);
        $rule4->setName('淘宝服装专项');
        $rule4->setMode(CommissionMode::FIXED);
        $rule4->setCommission('50.00'); // 固定50元
        $rule4->setCurrency('CNY');
        $rule4->setStartTime('2023-02-10 00:00:00');
        $rule4->setMemo('服装品类固定佣金');
        $manager->persist($rule4);
        $this->addReference(self::COMMISSION_RULE_4, $rule4);

        // 为活动3创建佣金规则
        $rule5 = new CommissionRule();
        $rule5->setCampaign($campaign3);
        $rule5->setId(3005);
        $rule5->setName('Amazon Books');
        $rule5->setMode(CommissionMode::PERCENTAGE);
        $rule5->setRatio('0.100000'); // 10%
        $rule5->setCurrency('USD');
        $rule5->setStartTime('2023-03-01 00:00:00');
        $rule5->setMemo('Amazon书籍品类佣金');
        $manager->persist($rule5);
        $this->addReference(self::COMMISSION_RULE_5, $rule5);

        $rule6 = new CommissionRule();
        $rule6->setCampaign($campaign3);
        $rule6->setId(3006);
        $rule6->setName('Amazon Electronics');
        $rule6->setMode(CommissionMode::PERCENTAGE);
        $rule6->setRatio('0.040000'); // 4%
        $rule6->setCurrency('USD');
        $rule6->setStartTime('2023-03-05 00:00:00');
        $rule6->setMemo('Amazon电子产品佣金');
        $manager->persist($rule6);
        $this->addReference(self::COMMISSION_RULE_6, $rule6);

        // 为活动4创建佣金规则
        $rule7 = new CommissionRule();
        $rule7->setCampaign($campaign4);
        $rule7->setId(3007);
        $rule7->setName('拼多多通用佣金');
        $rule7->setMode(CommissionMode::PERCENTAGE);
        $rule7->setRatio('0.060000'); // 6%
        $rule7->setCurrency('CNY');
        $rule7->setStartTime('2023-04-01 00:00:00');
        $rule7->setMemo('拼多多通用佣金规则');
        $manager->persist($rule7);
        $this->addReference(self::COMMISSION_RULE_7, $rule7);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CampaignFixtures::class,
        ];
    }
}
