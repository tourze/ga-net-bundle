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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\GaNetBundle\Entity\Publisher;

/**
 * @extends AbstractCrudController<Publisher>
 */
#[AdminCrud(routePath: '/ga-net/publisher', routeName: 'ga_net_publisher')]
final class PublisherCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Publisher::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('发布商')
            ->setEntityLabelInPlural('发布商管理')
            ->setPageTitle('index', '发布商列表')
            ->setPageTitle('detail', '发布商详情')
            ->setPageTitle('new', '创建发布商')
            ->setPageTitle('edit', '编辑发布商')
            ->setHelp('index', '管理GA联盟发布商账户')
            ->setDefaultSort(['publisher_id' => 'DESC'])
            ->setSearchFields(['publisher_id', 'token'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('publisher_id', '发布商ID')
            ->setRequired(true)
            ->setHelp('GA联盟发布商唯一标识')
        ;

        yield TextField::new('token', 'API Token')
            ->setRequired(true)
            ->setMaxLength(100)
            ->setHelp('API访问令牌')
            ->hideOnIndex()
        ;

        yield AssociationField::new('campaigns', '关联活动')
            ->setHelp('该发布商的所有活动')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                if (!is_object($value) || !method_exists($value, 'count')) {
                    return '暂无活动';
                }
                $countValue = $value->count();
                $count = is_numeric($countValue) ? (int) $countValue : 0;

                return $count > 0 ? "共 {$count} 个活动" : '暂无活动';
            })
        ;

        yield AssociationField::new('transactions', '交易记录')
            ->setHelp('该发布商的所有交易')
            ->onlyOnDetail()
            ->formatValue(function ($value) {
                if (!is_object($value) || !method_exists($value, 'count')) {
                    return '暂无交易';
                }
                $countValue = $value->count();
                $count = is_numeric($countValue) ? (int) $countValue : 0;

                return $count > 0 ? "共 {$count} 笔交易" : '暂无交易';
            })
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
                return $action->setLabel('创建发布商');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('编辑发布商');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('删除发布商');
            })
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('publisher_id', '发布商ID'))
            ->add(TextFilter::new('token', 'Token'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }
}
