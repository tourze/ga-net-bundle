<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\CommissionRule;
use Tourze\GaNetBundle\Entity\PromotionCampaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\RedirectTag;
use Tourze\GaNetBundle\Entity\Settlement;
use Tourze\GaNetBundle\Entity\Transaction;

readonly final class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('成果网')) {
            $item->addChild('成果网');
        }

        $gaMenu = $item->getChild('成果网');
        if (null === $gaMenu) {
            return;
        }

        // 发布商管理
        $gaMenu->addChild('发布商管理')
            ->setUri($this->linkGenerator->getCurdListPage(Publisher::class))
            ->setAttribute('icon', 'fas fa-user-tie')
        ;

        // 活动管理
        $gaMenu->addChild('活动管理')
            ->setUri($this->linkGenerator->getCurdListPage(Campaign::class))
            ->setAttribute('icon', 'fas fa-bullhorn')
        ;

        // 推广活动
        $gaMenu->addChild('推广活动')
            ->setUri($this->linkGenerator->getCurdListPage(PromotionCampaign::class))
            ->setAttribute('icon', 'fas fa-rocket')
        ;

        // 佣金规则管理
        $gaMenu->addChild('佣金规则管理')
            ->setUri($this->linkGenerator->getCurdListPage(CommissionRule::class))
            ->setAttribute('icon', 'fas fa-percentage')
        ;

        // 重定向标签管理
        $gaMenu->addChild('重定向标签管理')
            ->setUri($this->linkGenerator->getCurdListPage(RedirectTag::class))
            ->setAttribute('icon', 'fas fa-tags')
        ;

        // 交易记录
        $gaMenu->addChild('交易记录')
            ->setUri($this->linkGenerator->getCurdListPage(Transaction::class))
            ->setAttribute('icon', 'fas fa-receipt')
        ;

        // 结算记录
        $gaMenu->addChild('结算记录')
            ->setUri($this->linkGenerator->getCurdListPage(Settlement::class))
            ->setAttribute('icon', 'fas fa-calculator')
        ;
    }
}
