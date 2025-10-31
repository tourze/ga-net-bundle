# 活动列表API

## 一、功能描述

活动列表接口，网站主可以查询旗下网站所有可获取的活动的详细信息。

## 二、交互接口

### 1. API URL
```
https://www.ga-net.com/api/campaign_list/[publisher_id]
```

### 2. 发起方
媒体

### 3. 接收方
Ganet

### 4. 调用方式
GET

### 5. 请求字段

| Field Name | Description |
|------------|-------------|
| publisher_id | 账户id |
| website_id | 网站ID (Example: 1234567) |
| status | 申请状态 (0: 所有, -1: 未申请, 1: 申请中,5: 已通过,6: 已拒绝) |
| timestamp | 时间戳 (Example: 1527674322) |
| sign | 签名方法: sign=MD5(publisher_id +timestamp+token) MD5加密，32位小写 |
| token | [登录查看](https://www.ga-net.com/zh-cn/publisher/user/sign_in) |

## 返回数据

### 格式:
json

### 举例:
```json
{
    "response": 200,
    "total_num": 1,
    "campaigns": [{
        "id": 2914,
        "region": "JPN",
        "name": "Amazon JP（CN）",
        "url": "http://www.amazon.co.jp",
        "logo": "https://www.ga-net.com/uploads/campaign/77e7ce59-007e-453d-a875-2853b3a0ecc8.jpg",
        "start_time": "14-12-18",
        "currency": "CNY",
        "description": " \n        【广告主介绍】:\n            世界上最大的网上商店，致力于成为全球最以客户为中心的公司，使消费者能够在网上找到并发掘任何\n            他们想购买的商品，并力图提供最低的价格。亚马逊及其他销售商为客户提供数千万种独特的全新、翻\n            新以及二手商品，如美容、健康及个人护理用品、珠宝和钟表、美食、体育及运动用品、服饰、图书、\n            音乐、DVD、电子和办公用品、婴幼儿用品、家居园艺用品等。\n\n            【成果定义】：成功购买商品而无退货\n\n            【数据返回时间】：2-7天\n\n            【佣金计算方法】\n            佣金=（商品售价-日本国内消费税10% 部分商品消费税为8%）×佣金比例\n            注意：单件商品佣金上限为800日元，如一个订单购买了5件商品，则该订单佣金上限为4000日元。\n\n            数据认证时间：隔2月结算\n            是否允许积分返点：不允许返利\n            是否允许购买关键字：不允许\n\n            【备注】：\n            1)礼品卡支付以及使用未授权优惠码可能无法获得返利\n            2)广告主不支持丢单\n            查询\n            3)有效结算金额汇率以广告主结算时提供的汇率标准为准\n            4)不允许返利给会员、不允许代购，若发现违规者一律扣除相关订单佣金\n            5)该项目为申请合作制，有意合作请联系客服",
        "cookie_expire_time": 0,
        "sem_permitted": 2,
        "is_link_customizable": 1,
        "rebate_permitted": 2,
        "has_datafeed": 2,
        "support_weapp": 2,
        "promotional_methods": "2|3|4|5|6|7|8|9|10|11|12",
        "data_reception_time": 30,
        "application_status": 5
    }]
}
```

### 返回数据:

| Field Name | Description |
|------------|-------------|
| response | 返回结果 |
| total_num | 活动总数 |
| **campaigns** | 活动数据数组 |
| id | 活动id |
| region | 商家地域 |
| allowed_regions | 允许投放地域（空表示没有区域限制） |
| name | 活动名称 |
| url | 活动链接 |
| sem_permitted | SEM（1=>是 ,2 => 否 ） |
| is_link_customizable | 自定义链接（1=>是 ,2 => 否 ） |
| rebate_permitted | 返利站（1=>是 ,2 => 否 ） |
| has_datafeed | 商品库 (1=>有 ,2=>无) |
| support_weapp | 支持微信小程序 (1=>是 ,2=>否) |
| promotional_methods | 允许投放方式 (1:付费搜索（SEM）,2:有机搜索（SEO）,3:工具栏/插件,4:手机/平板APP,5:优惠券/促销,6:邮件营销,7:社交购物,8:内容/利基,9:CPA/二级联盟,10:忠诚度/积分返利,11:服务/工具,12:价格比较,13:Media Buy) |
| data_reception_time | 结算周期 |
| application_status | 申请状态(0: 未申请,1: 申请中,5: 申请通过,6: 申请未通过) |

### 错误信息:

| Field Name | Description |
|------------|-------------|
| 01 | 字段缺失 |
| 02 | publisher id 错误 |
| 03 | token缺失 |
| 04 | 签名错误 |
| 05 | 时间戳错误 |