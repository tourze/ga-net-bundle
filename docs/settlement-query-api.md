# 结算查询API

## 一、功能描述

订单结算接口，可以获取每个月的结算数据。报表内容包含某月已经结算订单（含成功和失败的订单，不包含未确认的订单）。此接口数据文件每月生成一次，请在每月10号以后获取结算在上个月的数据。

## 二、交互接口

### 1. API URL
```
https://www.ga-net.com/api/transaction_balance_report/[publisher_id]
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
| timestamp | 时间戳 (Example: 1527674322) |
| month | 结算月份 Example: 2019-02 |
| sign | 签名方法: sign=MD5(publisher_id +timestamp+token) MD5加密，32位小写 |
| token | [登录查看](https://www.ga-net.com/zh-cn/publisher/user/sign_in) |

## Return Data

### Format:
json

### Example:
```json
{
  "response": 200,
  "total_num": 3,
  "month": "2019-02",
  "transactions": [{
    "id": 7402226,
    "order_id": "cancel_order_201901-",
    "website_id": 218,
    "total_price": 0,
    "campaign_id": 2971,
    "campaign_name": "Rakuten Global Market",
    "total_commission": 0,
    "order_time": "2019-01-01 00:03:16 +0000",
    "order_status": 2,
    "currency": "JPY",
    "tag": "0|",
    "balance_time": "2019-02",
    "category_id": "",
    "category_name": "",
    "item_quantity": -1,
    "item_name": "JPY1007557",
    "original_currency": "JPY",
    "original_total_price": 0
  }, {
    "id": 7402401,
    "order_id": "NN18074204741550054",
    "website_id": 218,
    "total_price": "80.0",
    "campaign_id": 2916,
    "campaign_name": "努比亚",
    "total_commission": "2.8",
    "order_time": "2019-01-04 20:47:04 +0000",
    "order_status": 2,
    "currency": "CNY",
    "tag": "",
    "balance_time": "2019-02",
    "category_id": "",
    "category_name": "",
    "item_quantity": 1,
    "item_name": "努比亚圈铁耳机(配件)",
    "original_currency": "CNY",
    "original_total_price": "80.0"
  }, {
    "id": 7402549,
    "order_id": "NN18074204741550054",
    "website_id": 218,
    "total_price": "2499.0",
    "campaign_id": 2916,
    "campaign_name": "努比亚",
    "total_commission": "27.49",
    "order_time": "2019-01-04 20:47:04 +0000",
    "order_status": 2,
    "currency": "CNY",
    "tag": "",
    "balance_time": "2019-02",
    "category_id": "",
    "category_name": "",
    "item_quantity": 1,
    "item_name": "Z17S 全面屏(手机)",
    "original_currency": "CNY",
    "original_total_price": "2499.0"
  }]
}
```

### Response Parameters:

| Field Name | Description |
|------------|-------------|
| response | 返回结果 |
| total_num | 订单总数 |
| month | 认证月份 |
| transactions | 交易数据数组 |
| id | transaction id(唯一) |
| campaign_id | 活动id |
| campaign_name | 活动名称 |
| website_id | 网站id |
| tag | 反馈标签 |
| order_id | 订单号 |
| total_commission | 商品总佣金 |
| total_price | 商品总价格 |
| order_time | 订单时间（2019-01-04 20:47:04 +0000 时间为utc时区） |
| order_status | 1=>待认证 ,2 => 已通过 ,3=> 已拒绝 |
| currency | 根据媒体后台设置的收款币种 |
| balance_time | 结算月份（2019-02） |
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