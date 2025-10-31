# 订单查询API

## 一、接口描述

此接口是成果网提供给合作媒体来获取订单数据的接口。媒体可以根据自身需求，设定读取频率，获取订单数据，建议一小时一次。

## 二、接口详情

### 1. 接口地址
```
https://www.ga-net.com/api/transaction_report/[publisher_id]
```

### 2. 请求方
媒体

### 3. 接收方  
GANet成果网

### 4. 访问方式
GET

### 5. 请求字段

| Field Name | Description |
|------------|-------------|
| publisher_id | 账户id |
| timestamp | 当前时间戳 Example: 1527674322 |
| start_time | 开始时间 Example: 2019-01-01 00:00:00（时间为utc时区，请按需调整） |
| end_time | 结束时间 Example: 2019-02-01 23:59:59（时间为utc时区，请按需调整） |
| order_status | 订单状态（可选）Example: 1（1 => 待认证 ,2 => 已认证,3 => 拒绝） |
| sign | 签名方法: sign=MD5(publisher_id +timestamp+token) MD5加密，32位小写 |
| token | [登录查看](https://www.ga-net.com/zh-cn/publisher/user/sign_in) |

## Return Data

### Format:
json

### Example:
```json
{
    "response": 200,
    "total_num": 28,
    "start_time": "2018-12-01 00:00:00 +0000",
    "end_time": "2018-12-01 23:59:59 +0000",
    "transactions": [{
        "id": 6841023,
        "memo": "{\"platform\":\"PHONE\"}",
        "order_id": "101287743697_1517414400",
        "website_id": 218,
        "total_price": "2480.0",
        "campaign_id": 2914,
        "campaign_name": "亚马逊（日本）",
        "total_commission": "99.2",
        "order_time": "2018-12-01 00:00:00 +0000",
        "order_status": 2,
        "currency": "JPY",
        "tag": "0|",
        "category_id": "",
        "category_name": "",
        "item_quantity": 1,
        "item_name": "[B01MYESM2H]アネッサ パーフェクトUV (SPF50+・PA++++) 60mL",
        "original_currency": "JPY",
        "original_total_price": "2480.0"
    }, {
        "id": 6841115,
        "memo": "{\"platform\":\"DESKTOP\"}",
        "order_id": "101287743697_1517414400",
        "website_id": 218,
        "total_price": "2037.0",
        "campaign_id": 2914,
        "campaign_name": "亚马逊（日本）",
        "total_commission": "64.78",
        "order_time": "2018-12-01 00:00:00 +0000",
        "order_status": 2,
        "currency": "JPY",
        "tag": "0|",
        "category_id": "",
        "category_name": "",
        "item_quantity": 1,
        "item_name": "[B01BY8P3N0]ラッシュレギンス　キッズ",
        "original_currency": "JPY",
        "original_total_price": "2037.0"
    }]
}
```

### Response Parameters:

| Field Name | Description |
|------------|-------------|
| response | 返回结果 |
| start_time | 开始时间（时间为utc时区） |
| end_time | 结束时间（时间为utc时区） |
| total_num | 总订单数 |
| transactions | 交易数据数组 |
| id | transaction id(唯一) |
| memo | transaction的额外信息 |
| campaign_id | 活动id |
| campaign_name | 活动名称 |
| website_id | 网站id |
| tag | 反馈标签 |
| order_id | 订单号 |
| total_commission | 商品总佣金 |
| total_price | 商品总价格 |
| order_time | 订单时间（2018-12-04 20:47:04 +0000 时间为utc时区） |
| order_status | 订单状态：1 => 待认证 ,2 => 已认证,3 => 拒绝 |
| currency | 根据媒体后台设置的收款币种 |
| category_name | 商品分类名称 |
| category_id | 商品分类id |
| item_name | 商品名称 |
| item_quantity | 商品数量 |
| original_currency | 原始货币 |
| original_total_price | 原始总金额 |

### Response error message error list:

| Field Name | Description |
|------------|-------------|
| 01 | 字段缺失 |
| 02 | publisher id 错误 |
| 03 | token缺失 |
| 04 | 签名错误 |
| 05 | 时间戳错误 |