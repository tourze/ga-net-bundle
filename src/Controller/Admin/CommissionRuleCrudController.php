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
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\CommissionRule;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\CommissionMode;
use Tourze\GaNetBundle\Enum\Currency;

/**
 * @extends AbstractCrudController<CommissionRule>
 */
#[AdminCrud(routePath: '/ga-net/commission-rule', routeName: 'ga_net_commission_rule')]
final class CommissionRuleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CommissionRule::class;
    }

    public function createEntity(string $entityFqcn): object
    {
        // 创建一个临时Campaign用于CommissionRule构造函数
        // 在实际表单提交时，关联关系会被正确设置
        return new CommissionRule();
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('佣金规则')
            ->setEntityLabelInPlural('佣金规则管理')
            ->setPageTitle('index', '佣金规则列表')
            ->setPageTitle('detail', '佣金规则详情')
            ->setPageTitle('new', '创建佣金规则')
            ->setPageTitle('edit', '编辑佣金规则')
            ->setHelp('index', '管理GA联盟佣金规则配置')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['name', 'currency'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', '规则ID')
            ->hideOnForm()
            ->setHelp('佣金规则唯一标识')
        ;

        yield TextField::new('name', '规则名称')
            ->setRequired(true)
            ->setMaxLength(255)
            ->setHelp('佣金规则名称')
        ;

        yield ChoiceField::new('mode', '佣金模式')
            ->setChoices([
                '分成' => CommissionMode::PERCENTAGE->value,
                '固定' => CommissionMode::FIXED->value,
            ])
            ->setRequired(true)
            ->setHelp('选择分成模式或固定模式')
            ->renderAsBadges([
                CommissionMode::PERCENTAGE->value => 'success',
                CommissionMode::FIXED->value => 'info',
            ])
            ->setFormTypeOption('choice_value', function ($choice) {
                return $choice instanceof CommissionMode ? $choice->value : $choice;
            })
            ->setFormTypeOption('choice_label', function ($value, $key, $index) {
                if ($value === CommissionMode::PERCENTAGE->value) {
                    return '分成';
                }
                if ($value === CommissionMode::FIXED->value) {
                    return '固定';
                }

                return $key;
            })
        ;

        yield NumberField::new('ratio', '分成比例')
            ->setHelp('分成比例，仅在分成模式时有效')
            ->setNumDecimals(6)
            ->hideOnIndex()
        ;

        yield ChoiceField::new('currency', '货币')
            ->setChoices([
                '人民币' => 'CNY',
                '美元' => 'USD',
                '欧元' => 'EUR',
                '英镑' => 'GBP',
            ])
            ->setRequired(true)
            ->setHelp('货币类型')
        ;

        yield NumberField::new('commission', '固定佣金')
            ->setHelp('固定佣金金额，仅在固定模式时有效')
            ->setNumDecimals(2)
            ->hideOnIndex()
        ;

        yield TextField::new('startTime', '开始时间')
            ->setRequired(true)
            ->setMaxLength(20)
            ->setHelp('佣金规则生效时间')
        ;

        yield TextareaField::new('memo', '备注')
            ->setHelp('佣金规则备注说明')
            ->hideOnIndex()
        ;

        yield AssociationField::new('campaign', '关联活动')
            ->setRequired(true)
            ->setHelp('关联的推广活动')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('创建佣金规则');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('编辑佣金规则');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('删除佣金规则');
            })
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('mode', '佣金模式')
                ->setChoices([
                    '分成' => CommissionMode::PERCENTAGE->value,
                    '固定' => CommissionMode::FIXED->value,
                ]))
            ->add(ChoiceFilter::new('currency', '货币')
                ->setChoices([
                    '人民币' => 'CNY',
                    '美元' => 'USD',
                    '欧元' => 'EUR',
                    '英镑' => 'GBP',
                ]))
            ->add(EntityFilter::new('campaign', '关联活动'))
        ;
    }
}
