<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Enum\Currency;

/**
 * @extends AbstractCrudController<Campaign>
 */
#[AdminCrud(routePath: '/ga-net/campaign', routeName: 'ga_net_campaign')]
final class CampaignCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Campaign::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('活动')
            ->setEntityLabelInPlural('活动管理')
            ->setPageTitle('index', '活动列表')
            ->setPageTitle('detail', '活动详情')
            ->setPageTitle('new', '创建活动')
            ->setPageTitle('edit', '编辑活动')
            ->setHelp('index', '管理GA联盟推广活动')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['name', 'region'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', '活动ID')
            ->hideOnForm()
            ->setHelp('活动唯一标识')
        ;

        yield AssociationField::new('publisher', '发布商')
            ->setRequired(true)
            ->setHelp('活动所属发布商')
            ->autocomplete()
        ;

        yield TextField::new('region', '商家地域')
            ->setRequired(true)
            ->setMaxLength(10)
            ->setHelp('商家所在地域')
        ;

        yield TextField::new('name', '活动名称')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setHelp('活动名称')
        ;

        yield UrlField::new('url', '活动链接')
            ->setRequired(true)
            ->setHelp('活动访问链接')
            ->hideOnIndex()
        ;

        yield ImageField::new('logo', '活动Logo')
            ->setHelp('活动Logo图片')
            ->hideOnIndex()
            ->hideOnForm()
        ;

        yield UrlField::new('logo', 'Logo链接')
            ->setHelp('活动Logo图片链接')
            ->onlyOnDetail()
        ;

        yield TextField::new('startTime', '开始时间')
            ->setRequired(true)
            ->setMaxLength(20)
            ->setHelp('活动开始时间')
        ;

        yield ChoiceField::new('currency', '货币类型')
            ->setRequired(true)
            ->setHelp('货币类型')
            ->setChoices([
                Currency::CNY->getLabel() => Currency::CNY,
                Currency::USD->getLabel() => Currency::USD,
                Currency::EUR->getLabel() => Currency::EUR,
                Currency::GBP->getLabel() => Currency::GBP,
            ])
            ->formatValue(function ($value, $entity) {
                return $value instanceof Currency ? $value->getLabel() : $value;
            })
            ->hideOnIndex()
        ;

        yield TextareaField::new('description', '活动描述')
            ->setHelp('活动详细描述')
            ->hideOnIndex()
        ;

        yield ChoiceField::new('applicationStatus', '申请状态')
            ->setChoices([
                '未申请' => 0,
                '申请中' => 1,
                '申请通过' => 5,
                '申请未通过' => 6,
            ])
            ->renderAsBadges([
                0 => 'secondary',
                1 => 'warning',
                5 => 'success',
                6 => 'danger',
            ])
            ->setHelp('活动申请状态')
            ->hideOnForm()
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
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('创建活动');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('编辑活动');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('删除活动');
            })
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('id', '活动ID'))
            ->add(TextFilter::new('name', '活动名称'))
            ->add(TextFilter::new('region', '商家地域'))
            ->add(TextFilter::new('startTime', '开始时间'))
            ->add(ChoiceFilter::new('currency', '货币类型')
                ->setChoices([
                    Currency::CNY->getLabel() => Currency::CNY,
                    Currency::USD->getLabel() => Currency::USD,
                    Currency::EUR->getLabel() => Currency::EUR,
                    Currency::GBP->getLabel() => Currency::GBP,
                ]))
            ->add(ChoiceFilter::new('applicationStatus', '申请状态')
                ->setChoices([
                    '未申请' => 0,
                    '申请中' => 1,
                    '申请通过' => 5,
                    '申请未通过' => 6,
                ]))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
