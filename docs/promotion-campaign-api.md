# 优惠促销API

## 一、功能描述

优惠促销接口，网站主可以查询旗下网站指定活动当前进行中的促销活动。

## 二、交互接口

### 1. API URL
```
https://www.ga-net.com/api/campaign_deals/[publisher_id]
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
| website_id | 网站ID (举例: 1234567) |
| campaign_id | 活动ID (举例: 1234567) |
| timestamp | 时间戳 (举例: 1527674322) |
| sign | 签名方法: sign=MD5(publisher_id+timestamp+token) MD5加密，32位小写 |
| token | [登录查看](https://www.ga-net.com/zh-cn/publisher/user/sign_in) |

## 返回数据

### 格式:
json

### 举例:
```json
{
  "response": 200,
  "total_num": 2,
  "deals": [{
      "id": 1,
      "promotion_type": 2,
      "start_time": "2022-09-30 19:30:00",
      "end_time": "2022-11-29 18:30:00",
      "title": "15% Off On Monastery Of El Escorial, Valley Of The Fallen & Toledo – Tour From Madrid",
      "image": null,
      "url": null,
      "description": "Combine the two most popular day tours from Madrid into one fantastic days sightseeing on this tour to El Escorial, the Valley of the Fallen and Toledo.",
      "coupon_code": "AFFLJN8I",
      "campaign_id": 3356
  }, {
      "id": 2,
      "promotion_type": 2,
      "start_time": "2022-09-02 11:28:00",
      "end_time": "2022-11-30 18:29:00",
      "title": "10% off The Best Of Venice Tour - St. Marks Basilica, Doge's Palace Ticket",
      "image": null,
      "url": null,
      "description": "Get the inside track on political intrigues and scandals in old-time Venice.\nMarvel at the fabulous treasures of the Basilica San Marco.\nLearn about Casanova's famous prison escape.\nTour is led by expert local guide.\nCoupon Code is Valid till 31st Oct 2022.",
      "coupon_code": "AFFF26F7",
      "campaign_id": 3356
  }]
}
```

### 返回数据:

| Field Name | Description |
|------------|-------------|
| response | 返回结果 |
| total_num | 活动总数 |
| **deals** | 促销活动数据数组 |
| id | 促销优惠id |
| promotion_type | 促销方式(1: 降价/打折,2: 优惠券) |
| title | 促销活动名称 |
| image | 促销活动图片 |
| url | 促销活动链接 |
| start_time | 促销开始时间 |
| end_time | 促销结束时间 |
| description | 促销详情 |
| coupon_code | 优惠券码 |
| campaign_id | 活动ID |

### 错误信息:

| Field Name | Description |
|------------|-------------|
| 01 | 字段缺失 |
| 02 | publisher id 错误 |
| 03 | token缺失 |
| 04 | 签名错误 |
| 05 | 时间戳错误 |