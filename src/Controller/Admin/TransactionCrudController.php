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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\GaNetBundle\Entity\Transaction;
use Tourze\GaNetBundle\Enum\TransactionStatus;

/**
 * @extends AbstractCrudController<Transaction>
 */
#[AdminCrud(routePath: '/ga-net/transaction', routeName: 'ga_net_transaction')]
final class TransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Transaction::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('交易')
            ->setEntityLabelInPlural('交易管理')
            ->setPageTitle('index', '交易记录列表')
            ->setPageTitle('detail', '交易详情')
            ->setHelp('index', '管理GA联盟交易记录')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['orderId', 'memo'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', '交易ID')
            ->hideOnForm()
            ->setHelp('交易唯一标识')
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

        yield IntegerField::new('campaignId', '活动ID')
            ->setHelp('关联活动ID')
            ->hideOnIndex()
        ;

        yield TextField::new('campaignName', '活动名称')
            ->setHelp('活动名称')
        ;

        yield ChoiceField::new('orderStatus', '订单状态')
            ->setRequired(true)
            ->setChoices([
                '待认证' => TransactionStatus::PENDING->value,
                '已认证' => TransactionStatus::CONFIRMED->value,
                '拒绝' => TransactionStatus::REJECTED->value,
            ])
            ->renderAsBadges([
                TransactionStatus::PENDING->value => 'warning',
                TransactionStatus::CONFIRMED->value => 'success',
                TransactionStatus::REJECTED->value => 'danger',
            ])
            ->setHelp('订单状态')
        ;

        yield MoneyField::new('totalCommission', '佣金金额')
            ->setCurrency('CNY')
            ->setHelp('佣金金额')
            ->hideOnIndex()
        ;

        yield TextareaField::new('memo', '备注信息')
            ->setHelp('交易额外信息')
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
            ->add(NumericFilter::new('id', '交易ID'))
            ->add(TextFilter::new('orderId', '订单号'))
            ->add(NumericFilter::new('websiteId', '网站ID'))
            ->add(NumericFilter::new('campaignId', '活动ID'))
            ->add(TextFilter::new('campaignName', '活动名称'))
            ->add(ChoiceFilter::new('orderStatus', '订单状态')
                ->setChoices([
                    '待认证' => TransactionStatus::PENDING->value,
                    '已认证' => TransactionStatus::CONFIRMED->value,
                    '拒绝' => TransactionStatus::REJECTED->value,
                ]))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
