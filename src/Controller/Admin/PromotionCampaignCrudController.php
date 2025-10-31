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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\GaNetBundle\Entity\PromotionCampaign;
use Tourze\GaNetBundle\Enum\PromotionType;

/**
 * @extends AbstractCrudController<PromotionCampaign>
 */
#[AdminCrud(routePath: '/ga-net/promotion-campaign', routeName: 'ga_net_promotion_campaign')]
final class PromotionCampaignCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PromotionCampaign::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('推广活动')
            ->setEntityLabelInPlural('推广活动管理')
            ->setPageTitle('index', '推广活动列表')
            ->setPageTitle('detail', '推广活动详情')
            ->setPageTitle('new', '创建推广活动')
            ->setPageTitle('edit', '编辑推广活动')
            ->setHelp('index', '管理GA联盟推广活动')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['title'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', '推广ID')
            ->hideOnForm()
            ->setHelp('推广活动唯一标识')
        ;

        yield IntegerField::new('campaignId', '活动ID')
            ->setRequired(true)
            ->setHelp('关联的活动ID')
        ;

        yield ChoiceField::new('promotionType', '推广方式')
            ->setRequired(true)
            ->setChoices([
                '降价/打折' => PromotionType::DISCOUNT,
                '优惠券' => PromotionType::COUPON,
            ])
            ->renderAsBadges([
                PromotionType::DISCOUNT->value => 'success',
                PromotionType::COUPON->value => 'info',
            ])
            ->setHelp('促销方式')
        ;

        yield TextField::new('title', '推广活动名称')
            ->setRequired(true)
            ->setMaxLength(500)
            ->setHelp('推广活动名称')
        ;

        yield UrlField::new('url', '推广链接')
            ->setHelp('推广活动链接')
            ->hideOnIndex()
        ;

        yield UrlField::new('image', '活动图片')
            ->setHelp('推广活动图片')
            ->hideOnIndex()
        ;

        yield TextareaField::new('description', '促销详情')
            ->setHelp('促销活动详情')
            ->hideOnIndex()
        ;

        yield TextField::new('couponCode', '优惠券码')
            ->setMaxLength(50)
            ->setHelp('优惠券码（仅优惠券类型有效）')
            ->hideOnIndex()
        ;

        yield TextField::new('startTime', '开始时间')
            ->setRequired(true)
            ->setMaxLength(20)
            ->setHelp('推广开始时间')
        ;

        yield TextField::new('endTime', '结束时间')
            ->setRequired(true)
            ->setMaxLength(20)
            ->setHelp('推广结束时间')
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
            ->disable(Action::NEW, Action::EDIT)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('id', '推广ID'))
            ->add(NumericFilter::new('campaignId', '活动ID'))
            ->add(TextFilter::new('title', '推广活动名称'))
            ->add(ChoiceFilter::new('promotionType', '推广方式')
                ->setChoices([
                    '降价/打折' => PromotionType::DISCOUNT,
                    '优惠券' => PromotionType::COUPON,
                ]))
            ->add(TextFilter::new('startTime', '开始时间'))
            ->add(TextFilter::new('endTime', '结束时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
