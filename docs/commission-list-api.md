# 佣金查询API

## 一、功能描述

佣金查询接口，可以获取指定活动的佣金详细信息。

## 二、交互接口

### 1. API URL
```
https://www.ga-net.com/api/commission_list/[publisher_id]
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
| campaign_id | 活动ID (Example: 1234567) |
| timestamp | 时间戳 (Example: 1527674322) |
| sign | 签名方法: sign=MD5(publisher_id+timestamp+token) MD5加密，32位小写 |
| token | [登录查看](https://www.ga-net.com/zh-cn/publisher/user/sign_in) |

## 返回数据

### 格式:
json

### 举例:
```json
{
    "response": 200,
    "total_num": 1,    
    "commissions": [
      {
          "id": 32826,
          "name": "Character figures",
          "mode": 1,
          "ratio": "0.004",
          "currency": "JPY",
          "commission": 0,
          "start_time": "2018-12-31",
          "memo": "Character figure"
      },
      {
          "id": 32825,
          "name": "PC & electronics",
          "mode": 1,
          "ratio": "0.016",
          "currency": "JPY",
          "commission": 0,
          "start_time": "2018-12-31",
          "memo": "CD\\DVD\\Blu-ray\\game/PC software\\camera\\PC\\Home electronics including home appliance & beauty appliances\\ car goods\\ watches\\ instruments "
      },
      {
          "id": 32824,
          "name": "books & kitchen",
          "mode": 1,
          "ratio": "0.024",
          "currency": "JPY",
          "commission": 0,
          "start_time": "2018-12-31",
          "memo": "Books\\stationery\\office supplies\\toys\\hobby\\kitchen supplies\\bedding\\interior furniture\\ handicraft and household goods\\ painting materials "
      },
      {
          "id": 32820,
          "name": "Amazon Instant Video & Amazon Coin",
          "mode": 1,
          "ratio": "0.08",
          "currency": "JPY",
          "commission": 0,
          "start_time": "2018-12-31",
          "memo": "Amazon Instant Video\\Amazon Coin"
      },
      {
          "id": 32821,
          "name": "Foods & clothes",
          "mode": 1,
          "ratio": "0.064",
          "currency": "JPY",
          "commission": 0,
          "start_time": "2018-12-31",
          "memo": "Kindle accessories\\Kindle book\\Digital music downloadsAndroid app\\food and beverage\\ wine\\ clothes\\ fashion accessories\\ bags\\ shoes\\ jewelry "
      },
      {
          "id": 32823,
          "name": "baby & sports",
          "mode": 1,
          "ratio": "0.032",
          "currency": "JPY",
          "commission": 0,
          "start_time": "2018-12-31",
          "memo": "DIY\\maternity baby products\\sports\\outdoor goods"
      },
      {
          "id": 32822,
          "name": "Health & beauty & cosmetics",
          "mode": 1,
          "ratio": "0.04",
          "currency": "JPY",
          "commission": 0,
          "start_time": "2018-12-31",
          "memo": "﻿Health & beauty products\\pet products\\cosmetics"
      },
      {
          "id": 32764,
          "name": "全部商品",
          "mode": 1,
          "ratio": "0.008",
          "currency": "JPY",
          "commission": 0,
          "start_time": "2018-12-31",
          "memo": ""
      }
    ]
}
```

### 返回数据:

| Field Name | Description |
|------------|-------------|
| response | 返回结果 |
| total_num | 活动总数 |
| **commission_rules** | 佣金规则数组 |
| id | ID |
| name | 名称 |
| mode | 佣金模式（1=>分成 ,2=>固定） |
| ratio | 网站佣金（分成） |
| currency | 佣金货币 |
| commission | 网站佣金（固定） |
| start_time | 开始时间 |
| memo | 备注 |

### 错误信息:

| Field Name | Description |
|------------|-------------|
| 01 | 字段缺失 |
| 02 | publisher id 错误 |
| 03 | token缺失 |
| 04 | 签名错误 |
| 05 | 时间戳错误 |