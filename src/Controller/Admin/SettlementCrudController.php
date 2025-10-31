<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\GaNetBundle\Entity\Settlement;
use Tourze\GaNetBundle\Enum\SettlementStatus;

/**
 * @extends AbstractCrudController<Settlement>
 */
#[AdminCrud(routePath: '/ga-net/settlement', routeName: 'ga_net_settlement')]
final class SettlementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Settlement::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('结算')
            ->setEntityLabelInPlural('结算管理')
            ->setPageTitle('index', '结算记录列表')
            ->setPageTitle('new', '创建结算记录')
            ->setPageTitle('edit', '编辑结算记录')
            ->setPageTitle('detail', '结算详情')
            ->setHelp('index', '管理GA联盟结算记录')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['settlementPeriod'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', '结算ID')
            ->hideOnForm()
            ->setHelp('结算记录唯一标识')
        ;

        yield TextField::new('orderId', '订单号')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setHelp('订单编号')
        ;

        yield IntegerField::new('websiteId', '网站ID')
            ->setRequired(true)
            ->setHelp('网站标识')
        ;

        yield MoneyField::new('totalPrice', '商品总价')
            ->setRequired(true)
            ->setCurrency('CNY')
            ->setHelp('商品总价格')
        ;

        yield TextField::new('campaignName', '活动名称')
            ->setHelp('活动名称')
        ;

        yield MoneyField::new('totalCommission', '佣金金额')
            ->setCurrency('CNY')
            ->setHelp('佣金金额')
            ->hideOnIndex()
        ;

        yield ChoiceField::new('orderStatus', '订单状态')
            ->setRequired(true)
            ->setChoices([
                '待认证' => SettlementStatus::PENDING->value,
                '已通过' => SettlementStatus::APPROVED->value,
                '已拒绝' => SettlementStatus::REJECTED->value,
            ])
            ->renderAsBadges([
                SettlementStatus::PENDING->value => 'warning',
                SettlementStatus::APPROVED->value => 'success',
                SettlementStatus::REJECTED->value => 'danger',
            ])
            ->setHelp('订单状态')
        ;

        yield TextField::new('balanceTime', '结算月份')
            ->setRequired(true)
            ->setMaxLength(10)
            ->setHelp('结算月份（2019-02）')
        ;

        yield TextField::new('itemName', '商品名称')
            ->setHelp('商品名称')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnDetail()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
                return $action->setLabel('查看详情');
            })
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('id', '结算ID'))
            ->add(TextFilter::new('orderId', '订单号'))
            ->add(NumericFilter::new('websiteId', '网站ID'))
            ->add(NumericFilter::new('campaignId', '活动ID'))
            ->add(TextFilter::new('campaignName', '活动名称'))
            ->add(TextFilter::new('balanceTime', '结算月份'))
            ->add(ChoiceFilter::new('orderStatus', '订单状态')
                ->setChoices([
                    '待认证' => SettlementStatus::PENDING->value,
                    '已通过' => SettlementStatus::APPROVED->value,
                    '已拒绝' => SettlementStatus::REJECTED->value,
                ]))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
