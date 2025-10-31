# 个人项目
## Base URLs
- 正式环境: https://tracking.ga-net.com

## Authentication
- HTTP Authentication, scheme: basic
- Username:你的Publisher ID,Password: 你的API Token

# 链接转换API
## POST 推广链接转换接口
POST /convert

针对成果网无法使用固定规则的小程序推广商家，通过成果网统一整合入口，各媒体通过API调用的方式动态获取适合自身投放方式的推广信息进行推广。

### Body Parameters
```json
{
  "campaign_id": 8566,
  "identifier": 1,
  "tag": "",
  "to": 2,
  "type": 1,
  "value": "RBDGC",
  "website_id": 1003444
}
```

### Params
|Name|Location|Type|Required|Title|Description|
|---|---|---|---|---|---|
|body|body|object|no||none|
|» campaign_id|body|integer|yes|活动ID|成果网后台获取|
|» identifier|body|integer|yes|标识符|请求值数据格式,1:ID:1,2:URL,3:PATH|
|» tag|body|string|no|标签|网站自定义的信息|
|» to|body|integer|yes|转换目标|返回值格式,1:Web URL,2:小程序PATH|
|» type|body|integer|yes|类型|请求值数据类型,1:商品，2:活动，3:成果网素材|
|» value|body|string|yes|具体信息|按照type和identifier的值来提供|
|» website_id|body|integer|yes|网站ID|成果网后台获取|

### Response Examples
#### 成功
```json
{
  "code": 0,
  "data": {
    "allow_tag": "1",
    "extra": "{\"a\":1,\"b\":2}",
    "info": {
      "campaign_id": 852,
      "campaign_name": "饿了么",
      "expired_at": "1983-02-09 18:42:53",
      "weapp_name": "饿了么小程序",
      "weapp_id": "hU4cg7[pS)@xW^P$Za"
    },
    "type": 2,
    "value": "/page/index"
  }
}
```

#### 请求有误
```json
{
  "code": 495,
  "message": "azoE"
}
```

### Responses
|HTTP Status Code|Meaning|Description|Data schema|
|---|---|---|---|
|200|OK|成功|Inline|
|400|Bad Request|请求有误|Inline|

### Responses Data Schema
#### HTTP Status Code 200
|Name|Type|Required|Restrictions|Title|description|
|---|---|---|---|---|---|
|» code|integer|true|none|状态值|0:正常，1:不支持转换,2:转换失败,3:格式有误|
|» data|object|true|none|返回数据|none|
|»» allow_tag|string|true|none|是否允许tag|返回是否允许tag参数，0:不允许，1:允许|
|»» extra|string|false|none|额外参数|小程序跳转可能需要的额外extra信息|
|»» info|object|true|none|描述信息|可能包含小程序名称，商户名称，活动名称等|
|»»» campaign_id|integer|true|none|活动ID|成果网后台活动ID|
|»»» campaign_name|string|true|none|活动名称|成果网后台活动名称|
|»»» expired_at|string|false|none|过期时间|链接或路径的过期时间|
|»»» weapp_name|string|false|none|小程序名称|小程序名称|
|»»» weapp_id|string|false|none|小程序ID|小程序ID|
|»» type|integer|true|none|类型|1:Web URL,2:小程序PATH|
|»» value|string|true|none|值|具体的URL地址或者小程序路径|

#### HTTP Status Code 400
|Name|Type|Required|Restrictions|Title|description|
|---|---|---|---|---|---|
|» code|integer|true|none||错误代码|
|» message|string|true|none||错误信息|

# Data Schema
