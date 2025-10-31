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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\GaNetBundle\Entity\RedirectTag;

/**
 * @extends AbstractCrudController<RedirectTag>
 */
#[AdminCrud(routePath: '/ga-net/redirect-tag', routeName: 'ga_net_redirect_tag')]
final class RedirectTagCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RedirectTag::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('重定向标签')
            ->setEntityLabelInPlural('重定向标签管理')
            ->setPageTitle('index', '重定向标签列表')
            ->setPageTitle('detail', '重定向标签详情')
            ->setPageTitle('new', '创建重定向标签')
            ->setPageTitle('edit', '编辑重定向标签')
            ->setHelp('index', '管理GA联盟重定向标签数据')
            ->setDefaultSort(['clickTime' => 'DESC'])
            ->setSearchFields(['tag', 'userIp'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', '标签ID')
            ->hideOnForm()
            ->setHelp('重定向标签唯一标识')
        ;

        yield TextField::new('tag', '标签值')
            ->setRequired(true)
            ->setMaxLength(64)
            ->setHelp('用于关联订单的唯一标签值')
        ;

        yield IntegerField::new('userId', '用户ID')
            ->setHelp('用户ID（如果已登录）')
        ;

        yield TextField::new('userIp', '用户IP')
            ->setHelp('用户IP地址')
            ->setMaxLength(45)
        ;

        yield TextareaField::new('userAgent', '用户代理')
            ->setHelp('用户浏览器User-Agent信息')
            ->hideOnIndex()
            ->hideOnForm()
        ;

        yield UrlField::new('referrerUrl', '来源URL')
            ->setHelp('用户来源页面URL')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('clickTime', '点击时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('用户点击重定向链接的时间')
        ;

        yield AssociationField::new('campaign', '关联活动')
            ->setHelp('关联的推广活动')
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
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('创建重定向标签');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('编辑重定向标签');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('删除重定向标签');
            })
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('userId', '用户ID'))
            ->add(EntityFilter::new('campaign', '关联活动'))
            ->add(TextFilter::new('tag', '标签值'))
            ->add(TextFilter::new('userIp', '用户IP'))
            ->add(DateTimeFilter::new('clickTime', '点击时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
