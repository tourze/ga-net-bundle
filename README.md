# GA-Net Bundle

[English](README.md) | [中文](README.zh-CN.md)

Symfony Bundle for integrating with GA-Net affiliate marketing platform.

## 概述

GA-Net Bundle 是一个用于集成成果网（GA-Net）联盟营销平台的 Symfony Bundle。它提供了完整的数据同步和查询功能，支持成果网的所有主要 API。

## 功能特性

- **完整的实体映射**：严格按照 GA-Net API 文档设计的实体结构
- **数据同步**：支持同步活动、佣金规则、交易、促销活动和结算数据
- **Repository 层**：丰富的查询方法，支持各种业务场景
- **API 客户端**：使用 HttpClientBundle 的可靠 HTTP 客户端
- **命令行工具**：便捷的数据同步命令
- **完整测试覆盖**：35个测试用例，295个断言

## 支持的 API

该 Bundle 支持以下 GA-Net API：

1. **活动列表 API** (`campaign_list`) - 获取可用的营销活动
2. **佣金查询 API** (`commission_list`) - 获取活动的佣金规则
3. **订单查询 API** (`transaction_report`) - 获取交易数据
4. **优惠促销 API** (`campaign_deals`) - 获取促销活动信息
5. **结算查询 API** (`transaction_balance_report`) - 获取结算数据

## 安装

```bash
composer require tourze/ga-net-bundle
```

## 配置

1. 在 `config/bundles.php` 中注册 Bundle：

```php
<?php

return [
    // ... other bundles
    Tourze\GaNetBundle\GaNetBundle::class => ['all' => true],
];
```

2. 在 `.env` 文件中配置 GA-Net 参数：

```env
GA_NET_DOMAIN=https://www.ga-net.com
GA_NET_PUBLISHER_ID=10035611
GA_NET_TOKEN=9a826da6e20f0865a6f41
GA_NET_WEBSITE_ID=10037451
```

## 使用示例

### 1. 数据同步命令

```bash
# 同步活动列表
php bin/console ganet:sync-campaigns

# 同步佣金规则（需要先同步活动列表）
php bin/console ganet:sync-commissions

# 同步交易数据（指定日期范围）
php bin/console ganet:sync-transactions --start-date 2024-01-01 --end-date 2024-01-31

# 同步促销活动（需要先同步活动列表）
php bin/console ganet:sync-promotions

# 同步结算数据（指定结算月份）
php bin/console ganet:sync-settlements --settlement-month 2024-01
```

### 2. 使用 Repository 查询数据

```php
use Tourze\GaNetBundle\Repository\CampaignRepository;
use Tourze\GaNetBundle\Repository\TransactionRepository;

// 查询活跃的活动
$campaigns = $campaignRepository->findActiveByPublisher($publisher);

// 查询指定日期范围的交易
$startDate = new \DateTime('2024-01-01');
$endDate = new \DateTime('2024-01-31');
$transactions = $transactionRepository->findByDateRange($startDate, $endDate, $publisher);

// 计算总佣金  
$totalCommission = $transactionRepository->calculateTotalCommission($publisher, TransactionStatus::CONFIRMED);
```

### 3. 使用 API 客户端

```php
use Tourze\GaNetBundle\Service\GaNetApiClient;
use Tourze\GaNetBundle\Entity\Publisher;

// Publisher 信息会从环境变量中读取
$publisher = new Publisher(
    $_ENV['GA_NET_PUBLISHER_ID'], 
    $_ENV['GA_NET_TOKEN']
);

// 获取活动列表 (Website ID 从环境变量读取)
$campaigns = $apiClient->getCampaignList($publisher, $_ENV['GA_NET_WEBSITE_ID']);

// 获取佣金规则
$commissions = $apiClient->getCommissionList($publisher, $_ENV['GA_NET_WEBSITE_ID'], $campaignId);

// 获取交易报告
$transactions = $apiClient->getTransactionReport(
    $publisher,
    '2024-01-01 00:00:00',
    '2024-01-31 23:59:59',
    TransactionStatus::CONFIRMED
);
```

## 实体说明

### 核心实体

- **Publisher** - 账户信息，包含 API Token 和签名生成方法
- **Campaign** - 营销活动，支持各种状态和属性查询
- **CommissionRule** - 佣金规则，支持分成和固定两种模式
- **Transaction** - 交易数据，包含完整的订单信息
- **PromotionCampaign** - 促销活动，支持降价和优惠券两种类型
- **Settlement** - 结算数据，用于月度财务对账

### 状态常量

**Transaction/Settlement 状态**：
- `STATUS_PENDING = 1` - 待认证
- `STATUS_CONFIRMED/STATUS_APPROVED = 2` - 已认证/已通过
- `STATUS_REJECTED = 3` - 拒绝

**CommissionRule 模式**：
- `MODE_PERCENTAGE = 1` - 分成模式
- `MODE_FIXED = 2` - 固定佣金模式

**PromotionCampaign 类型**：
- `TYPE_DISCOUNT = 1` - 降价/打折
- `TYPE_COUPON = 2` - 优惠券

## Repository 功能

每个实体都有对应的 Repository，提供丰富的查询方法：

- **PublisherRepository** - Publisher 管理和查找
- **CampaignRepository** - 按地区、货币、状态查询活动
- **CommissionRuleRepository** - 按模式、货币查询佣金规则
- **TransactionRepository** - 按状态、日期、活动查询交易
- **PromotionCampaignRepository** - 按类型、状态查询促销
- **SettlementRepository** - 按月份、状态查询结算数据

## 开发说明

### 代码质量

- PHPStan Level 8 零错误
- 35个测试用例，295个断言
- 完整的类型声明和文档注释
- 遵循 PSR-12 编码标准

### 测试覆盖

该 Bundle 包含完整的测试套件：

- **实体测试** - 验证所有实体的创建、更新和业务方法
- **服务测试** - 验证 API 客户端的所有方法和错误处理
- **Mock 测试** - 使用 Mock 对象测试 HTTP 客户端交互

### 字段注释

所有实体字段都包含完整的中文注释，严格对应 API 文档说明：

```php
#[ORM\Column(type: 'integer', options: ['comment' => '活动id'])]
private int $id;

#[ORM\Column(type: 'string', length: 255, options: ['comment' => '活动名称'])]
private string $name;

#[ORM\Column(type: 'integer', options: ['comment' => '佣金模式（1=>分成, 2=>固定）'])]
private int $mode;
```

## 许可证

MIT License

## 贡献

欢迎提交 Issue 和 Pull Request 来完善这个 Bundle。