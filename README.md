#Fruitday API

##目录
 * [地区](#region)
   - [获取地区分站列表 `region.regionSiteList`](#region.regionSiteList)
   - [获取地区 `region.getRegion`](#region.getRegion)
   - [获取配送时间 `region.getSendTime`](#region.getSendTime)
   - [获取支付方式 `region.getPay`](#region.getPay)
   - [获取支付方式 `region.getChargePay`](#region.getChargePay)
 * [首页](#marketing)
   - [首页版面 `marketing.banner`](#marketing.banner)
   - [充值营销规则说明 `marketing.chargeRules`](#marketing.chargeRules)
 * [会员](#user)
   - [登陆验证 `user.signin`](#user.signin)
   - [联合登陆验证(新浪、qq、支付宝) `user.oAuthSignin`](#user.oAuthSignin)
   - [验证手机 `user.sendPhoneTicket`](#user.sendPhoneTicket)
   - [会员注册验证 `user.checkRegister`](#user.checkRegister)
   - [注册 `user.register`](#user.register)
   - [手机快捷登录 `user.mobileLogin`](#user.mobileLogin)
   - [绑定手机 `user.bindMobile`](#user.bindMobile)
   - [密码修改 `user.password`](#user.password)
   - [注销帐户 `user.signout`](#user.signout)
   - [短信验证码专用接口 `user.sendVerCode`](#user.sendVerCode)
   - [密码取回 `user.forgetPasswd`](#user.forgetPasswd)
   - [获取会员信息 `user.userInfo`](#user.userInfo)
   - [获取会员积分信息 `user.userScore`](#user.userScore)
   - [获取会员账户余额 `user.userTransaction`](#user.userTransaction)
   - [分享奖励接口 `user.shareReward`](#user.shareReward)
   - [获取会员抵扣码列表 `user.userCouponList`](#user.userCouponList)
   - [验证用户礼品码 `user.gcouponGet`](#user.gcouponGet)
   - [领取赠品 `user.giftsGet`](#user.giftsGet)
   - [企业帐户绑定 `user.bindAccount`](#user.bindAccount)
   - [摇一摇 `user.shake_shake`](#user.shake_shake)
   - [摇一摇中奖历史记录 `user.shake_history`](#user.shake_history)
   - [摇一摇积分置换当日摇奖次数 `user.shake_exchange`](#user.shake_exchange)
   - [充值卡充值 `user.giftCardCharge`](#user.giftCardCharge)
   - [在线支付充值 `user.userCharge`](#user.userCharge)
   - [特权列表 `user.privilegeList`](#user.privilegeList)
   - [用户信息更新 `user.upUserInfo`](#user.upUserInfo)
   - [大客户需求留言 `user.welfare`](#user.welfare)
   - [用户商品推荐 `user.recommend`](#user.recommend)
   - [会员中心消息提示 `user.notice`](#user.notice)
   - [会员升降级日志 `user.levelLog`](#user.levelLog)
   - [会员未读信息 `user.checkRedIndicator`](#user.checkRedIndicator)
   - [会员新信息已读 `user.cancelRedIndicator`](#user.cancelRedIndicator)
   - [收集用户手机系统数据 `user.collectMobileData`](#user.collectMobileData)
   - [收集用户投放渠道追踪码 `user.collectChannelTracking`](#user.collectChannelTracking)
   - [充值发票历史 `user.tradeInvoiceHistory`](#user.tradeInvoiceHistory)
 * [会员](#user)
   - [会员中心配置的特惠商品 `vip.getSaleProduct`](#vip.getSaleProduct)
 * [商品](#product)
   - [评论各类占比 `product.commentsRate`](#product.commentsRate)
   - [对应商品的评论 `product.comments`](#product.comments)
   - [根据分类id获取商品 `product.category`](#product.category)
   - [根据搜索条件i取商品 `product.search`](#product.search)
   - [根据商品id获取列表 `product.productList`](#product.productList)
   - [根据价格区间搜索 `product.priceSearch`(未完成)](#product.priceSearch)
   - [获取搜索关键字 `product.searchKey`](#product.searchKey)
   - [根据促销板块获取商品 `product.tag`(未完成)](#product.tag)
   - [商品详情 `product.productInfo`](#product.productInfo)
   - [获取所有分类 `product.getCatList`](#product.getCatList)
   - [获取专题页商品 `product.pageListProducts`](#product.pageListProducts)
   - [企业专享商品 `product.enterpriseProducts`](#product.enterpriseProducts)
   - [关注商品 `product.mark`](#product.mark)
   - [取消关注商品 `product.cancelMark`](#product.cancelMark)
   - [获取商品关注情况 `product.markStatus`](#product.markStatus)
   - [已关注商品列表 `product.markList`](#product.markList)
   - [已关注商品的相关商品 `product.markedProducts`](#product.markedProducts)
   - [关联商品推荐 `product.recommend`](#product.recommend)
   - [获取商品配送信息 `product.sendInfo`](#product.sendInfo)
   - [商品关键词搜索V2 `product.search_v2`](#product.search_v2)
   - [补全关联搜索关键字 `product.getKeyword`](#product.getKeyword)
 * [订单](#order)
   - [订单初始化(进入订单结算页，首先调用该接口) `order.orderInit`](#order.orderInit)
   - [获取收获地址 `order.getAddrList`](#order.getAddrList)
   - [添加收货地址 `order.addAddr`](#order.addAddr)
   - [修改收货地址 `order.updateAddr`](#order.updateAddr)
   - [删除收获地址 `order.deleteAddr`](#order.deleteAddr)
   - [选择收货地址 `order.choseAddr`](#order.choseAddr)
   - [查询选择的收货地址 `order.getAddr`](#order.getAddr)
   - [选择配送时间 `order.choseSendtime`](#order.choseSendtime)
   - [选择支付方式 `order.chosePayment`](#order.chosePayment)
   - [帐户余额支付方式选择验证 `order.checkUserMoney`](#order.checkUserMoney)
   - [积分使用 `order.usejf`](#order.usejf)
   - [取消积分使用 `order.cancelUsejf`](#order.cancelUsejf)
   - [抵扣码使用 `order.useCard`](#order.useCard)
   - [取消抵扣码使用 `order.cancelUseCard`](#order.cancelUseCard)
   - [运费计算 `order.postFree`](#order.postFree)
   - [索取发票 `order.useInvoice`](#order.useInvoice)
   - [索取充值单发票 `order.useTradeInvoice`](#order.useTradeInvoice)
   - [取消索取发票 `order.cancelInvoice`](#order.cancelInvoice)
   - [发票信息 `order.invoiceInfo`](#order.invoiceInfo)
   - [发票抬头 `order.invoiceTitleList`](#order.invoiceTitleList)
   - [订单创建 `order.createOrder`](#order.createOrder)
   - [用户订单查询 `order.orderList`](#order.orderList)
   - [订单详情查询 `order.orderInfo`](#order.orderInfo)
   - [订单取消 `order.orderCancel`](#order.orderCancel)
   - [订单确认收货 `order.confirmReceive`](#order.confirmReceive)
   - [订单支付状态更改 `order.orderPayed`](#order.orderPayed)
   - [订单申诉列表 `order.appealList`](#order.appealList)
   - [订单商品评论 `order.doComment`](#order.doComment)
   - [订单商品申诉 `order.doAppeal`](#order.doAppeal)
   - [物流查询接口 `order.logisticTrace`](#order.logisticTrace)
   - [商品申诉列表 `order.complaintsList`](#order.complaintsList)
   - [商品申诉详情 `order.complaintsDetail`](#order.complaintsDetail)
   - [申诉反馈 `order.complaintsFeedback`](#order.complaintsFeedback)

 * [提货券](#cardchange)
   - [验证提货券 `cardchange.checkExchange`](#cardchange.checkExchange)
   - [使用提货券下单 `cardchange.createOrder`](#cardchange.createOrder)
 * [购物车](#cart)
   - [加入购物车 `cart.add`](#cart.add)
   - [更新购物车 `cart.update`](#cart.update)
   - [获取购物车 `cart.get`](#cart.get)
   - [删除购物车 `cart.remove`](#cart.remove)
   - [清空购物车 `cart.clear`](#cart.clear)
   - [优惠提醒 `cart.selpmt`](#cart.selpmt)
   - [获取购物车优惠信息] `b2ccart.getPmtInfo`(#b2ccart.getPmtInfo)
 * [门店](#o2o)
 	- [根据ID获取门店地区下一级 `o2o.regionlist`](#o2o.regionlist)
 	- [定位最近的商区 `o2o.nearbyBszone`](#o2o.nearbyBszone)
 	- [定位最近的楼宇 `o2o.nearbyBuilding`](#o2o.nearbyBuilding)
 	- [获取上次下单成功的配送地址 `o2o.lastAddress`](#o2o.lastAddress)
 	- [获取历史地址列表 `o2o.addresslist`](#o2o.addresslist)
 	- [获取门店商品 `o2o.storeproducts`](#o2o.storeproducts)
 	- [选择商品后计算价格 `o2o.total`](#o2o.total)
 	- [订单初始化 `o2o.orderInit`](#o2o.orderInit)
 	- [生成订单 `o2o.createOrder`](#o2o.createOrder)
 	- [获取就近门店 `o2o.nearbystore`](#o2o.nearbystore)
 	- [获取用户常用自提门店 `o2o.commonstores`](#o2o.commonstores)
 	- [获取城市的所有门店 `o2o.citystores`](#o2o.citystores)
 	- [门店兑换券 `o2o.exchgcoupon`](#o2o.exchgcoupon)
 	- [搜索楼宇 `o2o.searchBuilding`](#o2o.searchBuilding)
 	- [门店信息 `o2o.getStroeInfo`](#o2o.getStroeInfo)
 	- [附近楼宇 `o2o.nearbyBuilding_new`](#o2o.nearbyBuilding_new)
 	- [楼宇ID获取门店信息,商品信息 `o2o.getStoreList`](#o2o.getStoreList)
 	- [获取门店其他商品信息 `o2o.getStoreOtherGoods`](#o2o.getStoreOtherGoods)
  - [加入购物车 `o2ocart.add`](#o2ocart.add)
  - [更新购物车 `o2ocart.update`](#o2ocart.update)
  - [获取购物车 `o2ocart.get`](#o2ocart.get)
  - [删除购物车 `o2ocart.remove`](#o2ocart.remove)
  - [清空购物车 `o2ocart.clear`](#o2ocart.clear)
  - [校验购物车数据 `o2ocart.checkCartInit`](#o2ocart.checkCartInit)
  - [换购 `o2ocart.selpmt`](#o2ocart.selpmt)
  - [加入购物车 `o2o.pageListProducts`](#o2o.pageListProducts)
  - [商品详情BANNER `o2o.getGoodsDetailBanner`](#o2o.getGoodsDetailBanner)
 * [果食](#fruit)
  - [果食主题列表 `fruit.getTopicList`](#fruit.getTopicList)
  - [果食主题 `fruit.getDetailTopic`](#fruit.getDetailTopic)
  - [获取混合果食文章列表 `fruit.getMaxArticleList`](#fruit.getMaxArticleList)
  - [删除评论 `fruit.delComment`](#fruit.delComment)
  - [删除文章 `fruit.delArticle`](#fruit.delArticle)
  - [检测黑名单 `fruit.checkInvate`](#fruit.checkInvate)
  - [发布文章 `fruit.doArticle`](#fruit.doArticle)
  - [上传用户头像 `fruit.doUserface`](#fruit.doUserface)
  - [评论文章或回复文章下的评论 `fruit.doComment`](#fruit.doComment)
  - [点赞文章(old) `fruit.doWorth`](#fruit.doWorth)
  - [取消点赞(old) `fruit.undoWorth`](#fruit.undoWorth)
  - [设置点赞 `fruit.setWorth`](#fruit.setWorth)
  - [获取文章列表 `fruit.getArticleList`](#fruit.getArticleList)
  - [获取文章详情 `fruit.getDetailArticle`](#fruit.getDetailArticle)
  - [获取文章评论列表 `fruit.getArtCommentList`](#fruit.getArtCommentList)
  - [获取用户果食信息(old) `fruit.getFruitUserInfo`](#fruit.getFruitUserInfo)
  - [获取用户果食文章列表(old) `fruit.getUserArticleList`](#fruit.getUserArticleList)
  - [获取用户果食评论文章(old) `fruit.getCommentArticleList`](#fruit.getCommentArticleList)
  - [获取用户果食点赞文章(old) `fruit.getWorthArticleList`](#fruit.getWorthArticleList)
  - [获取果食消息列表(old) `fruit.listNotify`](#fruit.listNotify)
  - [已阅读果食消息(old) `fruit.upNotify`](#fruit.upNotify)
* [百科](#bake)
  - [百科版块列表 `bake.getSectionList`](#bake.getSectionList)
  - [百科文章列表 `bake.getArticleList`](#bake.getArticleList)
  - [百科文章详情 `bake.getDetailArticle`](#bake.getDetailArticle)
  - [百科设置点赞 `bake.setWorth`](#bake.setWorth)
  - [百科设置收藏 `bake.setCollect`](#bake.setCollect)
  - [百科文章评论 `bake.doComment`](#bake.doComment)
  - [百科评论列表 `bake.getArtCommentList`](#bake.getArtCommentList)
  - [搜索关键词 `bake.getSearchTagList`](#bake.getSearchTagList)
* [百科社区中心](#snscenter)
  - [获取用户果食信息 `snscenter.getUserInfo`](#snscenter.getUserInfo)
  - [获取用户果食文章列表 `snscenter.getUserArticleList`](#snscenter.getUserArticleList)
  - [获取用户果食评论文章 `snscenter.getMaxCommentArticleList`](#snscenter.getMaxCommentArticleList)
  - [获取用户百科收藏文章 `snscenter.getCollectArticleList`](#snscenter.getCollectArticleList)
  - [获取果食消息列表 `snscenter.listNotify`](#snscenter.listNotify)
  - [已阅读果食消息 `snscenter.upNotify`](#snscenter.upNotify)
 * [试吃](#foretaste)
  - [获取当前正在进去中的试吃 `foretaste.getCurList`](#foretaste.getCurList)
  - [获取试吃明细 `foretaste.getDetail`](#foretaste.getDetail)
  - [试吃申请提交 `foretaste.doApply`](#foretaste.doApply)
  - [验证是否已申请过 `foretaste.checkApply`](#foretaste.checkApply)
  - [个人试吃申请集合 `foretaste.ownerApply`](#foretaste.ownerApply)
  - [获取试吃报告 `foretaste.getCommentList`](#foretaste.getCommentList)
  - [填写试吃报告 `foretaste.doComment`](#foretaste.doComment)

##系统约定

###api地址
http://nirvana.fruitday.com/api

###密钥
7600w212ec04a3j814d50b6a5ff6f6b67e16

###签名方法
```php
    function create_sign($params){
        ksort($params);//以键升序排列
        $query = "";
        foreach($params as $k=>$v){
            $query .= $k."=".$v."&";
        }//拼接成get字符串
        $sign = md5(substr(md5($query.API_SECRET), 0,-1)."w");
        //字符串拼接密钥后md5加密,      去处最后一位再拼接"w"，再md5加密
        return $sign;
    }
```
###系统级入参
|     参数名    |     类型      |      必须     |     说明     |
| ------------ | ------------- | ------------ | ------------ |
| timestamp    |     int       |       Y      |   unix时间戳  |
| service      |     string    |       Y      |   请求接口名称  |
| source       |     string    |       Y      |   请求来源(pc、app、wap)  |
| version      |     string    |       Y      |   请求版本(1.0)  |
| device_id      |     string    |       Y      |   设备号  |


###状态码定义
|     参数名    |     说明      |
| ------------ | ------------- |
| 200          |     请求成功   |
| 300          |     业务错误   |
| 400          |     session超时|
| 430          |     O2O商品不支持配送地址|
| 500          |     系统错误   |
| 600          |     余额不足提示|
| 700          |     未绑定手机提示|
| 800          |     密码重复发送提示|
| 900          |     APP更新提示|



##<a name="region"/>地区
###<a name="region.regionSiteList"/>获取地区分站列表 `region.regionSiteList`

* 业务入参
		无

* 接口返回
```json
[
    {
        "region_id":144005,        //地区id
        "region_name":"天津",       //地区名称
        "is_store_exist":1         //是否支持线下门店
    },
    {
        "region_id":143983,
        "region_name":"河北",
        "son_region":               //如果有son_region结构说明该地区下还有子地区需要选择
        [
            {
                "region_id":143984,
                "region_name":"石家庄",
                "is_store_exist":0         //是否支持线下门店
            }
        ]
    }
]
```


###<a name="region.getRegion"/>获取地区 `region.getRegion`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| area_pid    |    int  |       Y      |  地区父id       | 1 |



* 接口返回
```json
    [
        {
            "id":"106092",     #地区id
            "pid":"0",         #地区父id
            "name":"上海"      #地区名称
        },
        {
            "id":"54351",
            "pid":"0",
            "name":"浙江"
        }
    ]
```



###<a name="region.getSendTime"/>获取配送时间 `region.getSendTime`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |   示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| area_id    |    int  |       Y      |  地区父id       | 1 |
| region_id    |    int  |       Y      |  当前分站id       | 1 |

* 接口返回1:
```json
    [
        {
            "date_key":20131206,                      #日期的键(收货日期提交该值)
            "date_value":"12-06|周五",                #日期的值
            "time":                                   #时间结构
                [
                    {
                        "time_key":"0918",            #时间的键(收货时间提交该值)
                        "time_value":"09:00-18:00",   #时间的值
                        "disable":"false"             #是否可配送,可选值(true|false),true:可以配送;false:不可以配送
                    },
                    {
                        "time_key":1822,
                        "time_value":"18:00-22:00",
                        "disable":"true"
                    }
                ]
        }
    ]
```


* 接口返回2
```json

    {
            "date_key":after2to3days,                      #部分地区不返回具体配送时间，指返回默认时间2-3天
            "date_value":"下单后的2-3天送达",
            "time":                                   #时间结构
                [
                    {
                        "time_key":"weekday",            #工作日
                        "time_value":"仅在工作日配送",     #时间的值
                        "disable":"true"                 #是否可配送,可选值(true|false),true:可以配送;false:不可以配送
                    },
                    {
                        "time_key":"weekend",            #周末
                        "time_value":"仅在双休日、假日配送",   #时间的值
                        "disable":"true"             #是否可配送,可选值(true|false),true:可以配送;false:不可以配送
                    },
                    {
                        "time_key":"all",            #均可配送
                        "time_value":"工作日、双休日与假日均可配送",   #时间的值
                        "disable":"true"             #是否可配送,可选值(true|false),true:可以配送;false:不可以配送
                    },
                ]
    }
```

###<a name="region.getPay"/>获取支付方式 `region.getPay`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| province_id    |    int  |       Y      |  省级地区id    |1|

* 接口返回
```json
    [
        {
            "pay_parent_id":1,                  #支付父id
            "pay_parent_name":"支付宝付款",      #支付名称
            "son":[
                {
                    "pay_id":0,                 #支付子id
                    "pay_name":"支付宝付款",      #支付名称
                    "has_invoice":0             #是否可以开发票(可选值：0|1，0：不可以，1:可以)
                }
            ]
        },
        {
            "pay_parent_id":4,
            "pay_parent_name":"线下支付",
            "son":[
                {
                    "pay_id":1,
                    "pay_name":"货到付现金",
                    "has_invoice":0             #是否可以开发票(可选值：0|1，0：不可以，1:可以)
                },
                {
                    "pay_id":2,
                    "pay_name":"货到刷银行卡",
                    "has_invoice":0             #是否可以开发票(可选值：0|1，0：不可以，1:可以)
                },

            ]
        }
    ]
```

###<a name="region.getChargePay"/>获取充值支付方式 `region.getChargePay`
* 业务入参
无

* 接口返回
```json
    [
        {
            "pay_parent_id":1,                  #支付父id
            "pay_parent_name":"支付宝付款",      #支付名称
            "son":[
                {
                    "pay_id":0,                 #支付子id
                    "pay_name":"支付宝付款",      #支付名称
                }
            ]
        },
        {
            "pay_parent_id":4,
            "pay_parent_name":"线下支付",
            "son":[
                {
                    "pay_id":1,
                    "pay_name":"货到付现金",
                },
                {
                    "pay_id":2,
                    "pay_name":"货到刷银行卡",
                },

            ]
        }
    ]
```

##<a name="marketing"/>首页
###<a name="marketing.banner"/>首页版面 `marketing.banner`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ | ------------|
| region_id    |     int       |       Y      |   地区id(region.regionSiteList接口返回的分站地区对应的id)  |  106092  |
| channel      |     string    |       N      |   渠道名称  |   portal  |


* 接口返回
```json
    {
        "rotation": [    #轮播图
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",            #目标页面类型可选值(1|2)，1:专题，2:详情
                "target_id": "12345",   #目标页面对应id，如果是专题则为专题id,如果是详情则为详情id,如果是列表则为列表id
                "description": "天天果园描述"
            },
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184251_pic.jpg",
                "price": "2",
                "title": "天天果园-枇杷手机端",
                "type": "1",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
         "mix_product_banner": [    #轮播图
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",            #目标页面类型可选值(1|2)，1:专题，2:详情
                "target_id": "12345",   #目标页面对应id，如果是专题则为专题id,如果是详情则为详情id,如果是列表则为列表id
                "description": "天天果园描述"
            },
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184251_pic.jpg",
                "price": "2",
                "title": "天天果园-枇杷手机端",
                "type": "1",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
         "horizontal_product_banner": [    #轮播图
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",            #目标页面类型可选值(1|2)，1:专题，2:详情
                "target_id": "12345",   #目标页面对应id，如果是专题则为专题id,如果是详情则为详情id,如果是列表则为列表id
                "description": "天天果园描述"
            },
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184251_pic.jpg",
                "price": "2",
                "title": "天天果园-枇杷手机端",
                "type": "1",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
        "banner": [     #广告位
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
        "new_product_list": [     #新品尝鲜
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
        "day_product_list": [     #每日特惠
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
        "hot_product_list": [     #热门推荐
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
        "mobile_product_list": [     #手机专享
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
        "qiangxian_product_list":[ #抢鲜
            {
                "photo":"http:\/\/cdn.fruitday.com\/images\/2014-08-11\/1407723889_pic.jpg",
                "price":"0",
                "title":"\u62a2\u9c9c\u5566\uff01",
                "type":"7",
                "target_id":"64",
                "description":"",
                "page_url":""
            },
        "register_gift_desc":"时令鲜果一份",  #注册奖励文字描述
        "is_o2o_initial":1,                 #0表示默认b2c,1表示默认o2o
    }
```

###<a name="marketing.chargeRules"/>充值营销规则说明 `marketing.chargeRules`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| region_id    |    int  |       Y      |   地区标识id  |  106092    |



* 接口返回
  - 成功
    * app返回结构
```json
  {
    "charge_gifts":
      [
        {
          "money_upto":618,
          "product_name":null,
          "gg_name":"\/",
          "price":null,
          "products_num":"1",
          "photo":"http:\/\/cdn.fruitday.com\/",
          "desc":""
        }
      ],
    "charge_rules":
      [
        "啦啦啦"
      ]
  }
```


##<a name="user"/>会员
###<a name="user.signin"/>登陆验证 `user.signin`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------|
| mobile    |     string       |       Y      |   手机/邮箱  |  13671981025|
| password      |     string    |      Y      |   md5之后的密码  |e10adc3949ba59abbe56e057f20f883e|

* 接口返回
```json
    {
        "connect_id":"7b5982f5747672d329d98352e2556f39"
    }
```



###<a name="user.oAuthSignin"/>联合登陆验证(新浪、qq、支付宝) `user.oAuthSignin`

* 业务入参:

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------|
| open_user_id    |    string  |       Y      |   sso获取的access_token  |  599180e6b7afe2ca71|
| signin_channel      |     int    |       Y     |   登陆渠道(1:新浪、2:qq、3:支付宝、4:微信 5小米 6返利 )  |    1   |
| user_name      |     string    |       N      |   用户昵称  |    王大拿  |
| user_photo      |     string    |       N      |   用户头像  |    http://xxxx.xxx.com/xxx.jpg  |

* 接口返回
```json
    {
        "connect_id":"7b5982f5747672d329d98352e2556f39"
    }
```

###<a name="user.sendPhoneTicket"/>验证手机 `user.sendPhoneTicket`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ | ---------|
| mobile    |    string  |       Y      |   手机号  |   13671981025  |


* 接口返回
```json

    {
        "connect_id":"7b5982f5747672d329d98352e2556f39"
    }
```

###<a name="user.checkRegister"/>会员注册验证 `user.checkRegister`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ | ---------|
| mobile    |    string  |       Y      |   手机号  |   13671981025  |


* 接口返回
```json
    {
        "code":"200",
        "msg":"succ",
    }
```


###<a name="user.register"/>注册 `user.register`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| mobile    |    string  |       Y      |   手机号  | 13671981025  |
| password    |    string  |       Y      |   md5之后的密码  |7b5982f5747672d329d98352e2556f39  |
| register_verification_code    |    int  |       Y      |   手机验证码  | 1234  |
| connect_id    |    string  |       Y      |   手机验证中获取的标识  |7b5982f5747672d329d98352e2556f39  |

* 接口返回
```json
    {
        "id": "201684",
        "email": "",
        "username": null,
        "money": "0.00",
        "mobile": "13671625589",
        "mobile_status": null,
        "reg_time": "2013-11-29 15:44:29",
        "last_login_time": null,
        "jf": "0"
    }
```

###<a name="user.mobileLogin"/>手机快捷登录 `user.mobileLogin`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| mobile    |    string  |       Y      |   手机号  | 13671981025  |
| register_verification_code    |    int  |       Y      |   手机验证码  | 1234  |
| connect_id    |    string  |       Y      |   手机验证中获取的标识  |7b5982f5747672d329d98352e2556f39  |

* 接口返回
```json
    {
        "id": "201684",
        "email": "",
        "username": null,
        "money": "0.00",
        "mobile": "13671625589",
        "mobile_status": null,
        "reg_time": "2013-11-29 15:44:29",
        "last_login_time": null,
        "jf": "0"
    }
```

###<a name="user.bindMobile"/>绑定手机 `user.bindMobile`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| mobile    |    string  |       Y      |   手机号  | 13671981025 |
| password    |    string  |       Y      |   md5之后的密码  |  7b5982f5747672d329d98352e2556f39 |
| ver_code_connect_id|string|       Y      |   手机验证接口appuser.sendPhoneTicket中获取的标识  |  7b5982f5747672d329d98352e2556f39 |
| register_verification_code    |    int  |       Y      |   手机验证码  |  1234 |
| connect_id    |    string  |       Y      |   登录标识  | 7b5982f5747672d329d98352e2556f39 |

* 接口返回
```json
    {
        "code": "200",
        "msg":"绑定成功，获取1000积分"
    }
```


###<a name="user.password"/>密码修改 `user.password`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| old_password    |    string  |       Y      |   md5之后的旧密码  |7b5982f5747672d329d98352e2556f39 |
| password    |    string  |       Y      |   md5之后的新密码  |7b5982f5747672d329d98352e2556f39 |
| re_password    |    string  |       Y      |   md5之后的新密码  |7b5982f5747672d329d98352e2556f39 |
| connect_id    |    string  |       Y      |   登录标识  |7b5982f5747672d329d98352e2556f39 |

* 接口返回
```json
    {
        "code": "200",
        "msg": "修改成功"
    }
```

###<a name="user.signout"/>注销帐户 `user.signout`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |   示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   登录标识  |7b5982f5747672d329d98352e2556f39 |

* 接口返回
```json
    {
        "code":"200",
        "msg":"退出成功",
    }
```



###<a name="user.sendVerCode"/>短信验证码专用接口 `user.sendVerCode`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| mobile    |    string  |       Y      |  手机号   |13671981025 |
| connect_id    |    string  |       N      |   登录标识  |7b5982f5747672d329d98352e2556f39 |
| use_case    |    string  |       N      |   场景标识(在订单里传order，手机快捷登录传mobileLogin)  |order |


* 接口返回
```json
    {
        "connect_id":"7b5982f5747672d329d98352e2556f39"
    }
```



###<a name="user.forgetPasswd"/>密码取回 `user.forgetPasswd`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------    |
| mobile    |    string  |       Y      |  手机号   | 13671981025    |
| verification_code    |    string  |       Y      |  手机验证码   | 1234    | |
| password    |    string  |       Y      |  md5之后的新密码   | 7b5982f5747672d329d98352e2556f39    |
| re_password    |    string  |       Y      |  重复md5之后的新密码   | 7b5982f5747672d329d98352e2556f39    |
| connect_id    |    string  |       N      |   登录标识  | 7b5982f5747672d329d98352e2556f39    |


* 接口返回
```json
    {
        "code":"200",
        "msg":"密码修改成功，请重新登录",
    }
```



###<a name="user.userInfo"/>获取会员信息 user.userInfo

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |   登录标识  |7b5982f5747672d329d98352e2556f39    |

* 接口返回
```json

    {
        "id": "1",                      #会员id
        "email": "63962937@qq.com",     #邮箱
        "username": "koikamo",          #用户名
        "money": "0.00",                #帐户余额
        "mobile": "18621180913",        #手机
        "mobile_status": "1",           #是否绑定手机
        "reg_time": null,               #注册时间
        "last_login_time": "2013-04-22 10:08:56",   #最后登录时间
        "jf": "701",                    #积分
        "coupon_num":"1",               #可用抵扣码数量
        "user_badge":1,                 #用户等级，可选值(0|1)，0:普通会员，1:鲜果达人
        "qr_code":"http://qr.liantu.com/api.php?el=l&w=150&m=10&text=http://fdayapi/appMarketing/userActive/276008/e5ba73d0ea892b6a9af5a6d46e71cf11",   #二维码
        "share_url":"http://www.fruitday.com/sale/fruitdayIphone/appstore.html",   #分享承接链接
        "share_desc":"好好好",
        "is_bind_company": 0, #是否绑定企业
        "user_grade": 0,   #用户等级
         "sex": "1",   #0保密1-男2-女
       "birthday": "1388505600",   #生日日期（时间戳）
       "is_enterprise":"1",        #是否是企业用户
    }
```


###<a name="user.userScore"/>获取会员积分信息 `user.userScore`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |   登录标识  |7b5982f5747672d329d98352e2556f39    |
| page    |    int  |       N      |   分页页数(默认为1)  |  1   |
| limit    |    int  |       N      |  每页个数  |  10  |


* 接口返回
```json
    [
        {
            "jf":"+1000",                       #积分数量
            "type":"1",                         #类型,可选值(1|2,1:获得积分|2:使用积分)
            "reason":"验证手机成功，赠送1000积分", #获取原因
            "time":"2014-06-11 09:39:23"        #获取时间
        }
    ]
```


###<a name="user.userTransaction"/>获取会员账户余额 `user.userTransaction`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
| page    |    int  |       N      |     分页页数(默认为1)  |  1   |
| limit    |    int  |       N      |  每页个数  |  10 |

* 接口返回
```json

    {
        "amount": "0.00",
        "list": [
            {
                "time": "2014-04-18 15:17:46",
                "money": "+1000.00",
                "trade_number": "T140418179594",
                "status": "等待支付",
            "type": 1,#1充值 2消费
            }
        ]
    }
```


###<a name="user.shareReward"/>分享奖励接口 `user.shareReward`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ | ------------ |
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
| share_type    |    int  |       Y      |   分享类型(1:特权、2:商品、3:评论、4:试吃、5:果实（非本人发布的果实）、6:本人发布的果实)、 7:摇一摇、8:内部html5页面分享、9:试吃分享（试吃详情）、10:下单支付后的分享)  |  1  |
| share_channel    |    int  |       Y     |  分享渠道(1:微博、2:微信朋友圈、3:微信好友)  |   1   |
| extra    |    string  |       N     |  分享类型需带的参数(特权：apiname(特权请求接口名)、商品：商品id、 评论：商品id 、试吃报告：applyid、 非本人果实：果实id 、  内部html5：分享链接 、试吃分享：试吃id 、 下单支付后分享：订单号 、 其余没提到的情况 extra字段为空)  |    1   |


* 接口返回
```json
    {
        "code": "200",
        "msg": "恭喜分享成功，获得500积分"
    }
```


###<a name="user.userCouponList"/>获取会员抵扣码列表 `user.userCouponList`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
| page    |    int  |       N      |   分页页数(默认为1)  |  1   |
| limit    |    int  |       N      |  每页个数  |    10   |
| coupon_status    |    int  |       Y      |  抵扣码状态(0:未使用、1:已使用、2:已过期)  |    0   |

* 接口返回
```json
    [
        {
            "id":"223247",                  #抵扣码id
            "card_number":"lsc7783517939",  #抵扣码卡号
            "card_money":"20.00",           #抵扣金额
            "is_used":"0",                  #是否使用
            "remarks":"222",                #抵扣码说明
            "to_date":"2014-05-10"          #到期时间
        }
    ]
```



###<a name="user.gcouponGet"/>获取会员赠品 `user.gcouponGet`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
| card_number    |    string  |       Y      |   礼品券  |  lpj1234    |
* 接口返回:
 - 成功
	* app返回结构
```json
	{
		"code":200,
		"msg":"操作成功"
	}
```
	* wap/web返回结构
```json
	{
		"code":200,
		"msg":"操作成功"
	}
```
 - 失败
```json
	{
	    "code": "300",
	    "msg": "操作失败"
	}
```


###<a name="user.giftsGet"/>获取会员赠品 `user.giftsGet`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
|gift_type      |   init   |    N        |    赠品类型  0：B2C  1：o2o 2:全部（默认）  |  1 |
| region_id    |    int  |       Y      |   地区id |  106092    |



* 接口返回
	- 成功
		* app返回结构
```json
{[
    {
        "product_name": "新疆阿克苏冰糖心苹果",
        "sale_price": 0,
        "photo":  "http://cdn.fruitday.com/product_pic/3263/1/1-100x100-3263-7A7YR5HC.jpg",
        "unit": "盒",
        "spec": "8斤装",
        "product_price_id": "4920",
        "product_no": "201411658",
        "price": "99",
        "product_id": "3263",
        "qty": "3",
        "end_time": "2015-03-29 00:00:03",
        "gift_send_id": "7",
        "gg_name": "8斤装/盒",
        "active_id": "7",
        "status": 0,
        "active_type": "2",
        "gift_source": "",
        "gift_type": 1
    }
]}
```
		* wap/web返回结构
```json
	{
	    "code": 200,
	    "msg": "",
	    "data": {
	        "usergifts": [
	            {
	                "product_name": "新疆阿克苏冰糖心苹果",
	                "sale_price": 0,
	                "photo": {
	                    "huge": "http://cdn.fruitday.com/product_pic/3263/1/1-1000x1000-3263-7A7YR5HC.jpg",
	                    "big": "http://cdn.fruitday.com/product_pic/3263/1/1-370x370-3263-7A7YR5HC.jpg",
	                    "middle": "http://cdn.fruitday.com/product_pic/3263/1/1-270x270-3263-7A7YR5HC.jpg",
	                    "small": "http://cdn.fruitday.com/product_pic/3263/1/1-180x180-3263-7A7YR5HC.jpg",
	                    "thum": "http://cdn.fruitday.com/product_pic/3263/1/1-100x100-3263-7A7YR5HC.jpg"
	                },
	                "unit": "盒",
	                "spec": "8斤装",
	                "product_price_id": "4920",
	                "product_no": "201411658",
	                "price": "99",
	                "product_id": "3263",
	                "qty": "3",
	                "end": "2015-03-29 00:00:03",
	                "gift_send_id": "7",
	                "gg_name": "8斤装/盒",
	                "active_id": "7",
	                "status": 0,
	                "active_type": "2",
	                "gift_source": ""
	            }
	        ]
	    }
	}
```
	- 失败
```json
	{
	    "code": "300",
	    "msg": "操作失败"
	}
```

###<a name="user.bindAccount"/>企业用户帐号认证 `user.bindAccount`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
| enterprise_tag    |    string  |       Y      |   企业标识  |  ucySm3    |
| name    |    string  |       Y      |   姓名  |  陆盛超    |
| mobile    |    string  |       Y      |   联系方式  |  13671981025    |



* 接口返回
  - 成功
    * app返回结构
```json
  {
      "code": "200",
      "msg": "认证成功"
  }
```

###<a name="user.shake_shake"/>摇一摇 `user.shake_shake`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
| type    |    int  |       Y      |   类型  |  2是查询昨日中特等奖用户，1是摇奖,其他为查询剩余次数    |
| region_id    |    int  |       Y      |   地区标识id  |  106092    |



* 接口返回
  - 成功
    * app返回结构
```json
  {
    "code":"200",
    "msg":"您今天已经用完3次摇奖机会，明日再来试试吧。",
    "chance_left"=>0,
    "is_win"=>true,
    "share_msg"=>"我在天天果园摇到了100积分！",
    "app_url"=>"http:\/\/www.fruitday.com\/sale\/wap-app\/index.html",
}
```

###<a name="user.shake_exchange"/>摇一摇积分置换当日摇奖次数 `user.shake_exchange`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |



* 接口返回
  - 成功
    * app返回结构
```json
  {"code":"200","msg":"置换成功."}
```

###<a name="user.shake_history"/>摇一摇历史获奖记录 `user.shake_history`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
| page    |    int  |       Y      |   当前页码  |  1    |
| limit    |    int  |       Y      |   每页条数  |  10    |



* 接口返回
  - 成功
    * app返回结构
```json
[
{"id":"57","gift_name":"admin","gift_type":"5","gift_price_id":null,"gift_product_id":null,"gift_activity_url":"http:\/\/www.fruitday.com\/sale\/tongyong\/index.html","time":"2015-08-13"},
{"id":"55","gift_name":"\u667a\u5229\u751c\u5fc3\u6a31\u6843J\uff08\u8d60\u5c14\u51ac\u5409\u706b\u5c71\u5ca9\u51b7\u6cc9\u5151\u6362\u5238\uff09","gift_type":"3","gift_price_id":"4925","gift_product_id":"1111","gift_activity_url":null,"time":"2015-08-13"},
{"id":"54","gift_name":"\u667a\u5229\u751c\u5fc3\u6a31\u6843J","gift_type":"4","gift_price_id":null,"gift_product_id":null,"gift_activity_url":null,"time":"2015-08-13"},
{"id":"53","gift_name":"\u7f8e\u56fd\u7ea2\u5b89\u742a12\u4e2a\u88c515\u5143\u4f18\u60e0\u5238","gift_type":"2","gift_price_id":null,"gift_product_id":null,"gift_activity_url":null,"time":"2015-08-13"},
{"id":"51","gift_name":"10\u79ef\u5206","gift_type":"1","gift_price_id":null,"gift_product_id":null,"gift_activity_url":null,"time":"2015-08-13"}
]
```
返回gift_type 对应类型
        1=>'积分',
        2=>'优惠券',
        3=>'特惠商品',
        4=>'随单赠品',
        5=>'h5页面'


###<a name="user.giftCardCharge"/>充值卡充值 `user.giftCardCharge`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
| charge_code    |    string  |       Y      |   充值码  |  aaaabbbbccccdddd    |
| region_id    |    int  |       Y      |   地区标识id  |  106092    |



* 接口返回
  - 成功
    * app返回结构
```json
  {
      "code":"200",
      "msg":"充值成功"
  }
```



###<a name="user.userCharge"/>在线支付充值 `user.userCharge`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
| pay_type    |    int  |       Y      |   充值方式  |  1:支付宝,7:微信    |
| money    |    int  |       Y      |   充值金额  |  1000  |
| region_id    |    int  |       Y      |   地区标识id  |  106092    |



* 接口返回
  - 成功
    * app返回结构
```json
  {
      "code":"200",
      "msg":"T140516269434"  #充值订单号，作为外部订单号传入支付宝
  }
```


###<a name="user.privilegeList"/>特权列表 `user.privilegeList`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |

* 接口返回
  - 成功
    * app返回结构
```json
  [
    {
        "privilege_type":"juice",         #特权类型
        "active_banner":"http:\/\/cdn.fruitday.com\/test.jpg",     #banner图
        "qr_code":"http:\/\/fdayapi\/appMarketing\/userActive\/1\/72700600a50abda3822ef552d8ef8418",   #二维码
        "active_action":"\u6bcf\u5929\u53ef\u4eab\u53d710\u5143\u679c\u6c411\u676f"   #action
    },
    {
        "privilege_type":"share",
        "active_banner":"http:\/\/cdn.fruitday.com\/test.jpg",
        "active_action":"appuser.userShareActive"
    }
]
```



###<a name="user.upUserInfo"/>用户信息更新 `user.upUserInfo`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
| username    |    string  |       N     |   昵称  |  张三    |
| sex    |    int  |       N      |   性别(0:保密、1:男、2:女)  |  1    |
| birthday    |    string  |       N      |   生日  |  2014-09-01    |
| photo    |    string  |       N     |   头像  |      |

* 接口返回
  - 成功
    * app返回结构
```json
  {
    "code": "200",
    "msg": "更新成功"
  }
```


###<a name="user.welfare"/>大客户需求留言 `user.welfare`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | --------|
| name    |    string  |       Y      |   姓名  |  张三    |
| mobile    |    string  |       Y     |   手机  |  13671981025    |
| company    |    string  |       Y      |  公司  |  天天鲜果   |
| demand    |    string  |       Y      |   需求  |  啦啦啦    |


* 接口返回
  - 成功
    * app返回结构
```json
  {
    "code": 200,
    "msg": "succ"
}
```

###<a name="user.recommend"/>用户商品推荐 `user.recommend`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| region_id    |    int  |       Y      |  分站地区id | 1  |
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |

* 接口返回
```json
  [
    {
        "price": "228",             #价格
        "pc_price":"0",             #pc端价格，为0表示与手机端一致
        "mem_lv": "",               #会员等级
        "mem_lv_price": "",         #会员价格
        "can_mem_buy": "1",         #是否普通会员可以购买
        "stock": "",                #库存
        "old_price": "0",           #原价
        "volume": "双层礼盒",        #规格
        "price_id": "546",          #sku_id
        "product_name": "Mom"s Love",#商品名称
        "summary": "                #商品详情

优选越南火龙果-2个；佳沛新西兰绿奇异果-4个；澳大利亚葡萄柚-3个；南非柠檬-3个；新西兰红玫瑰苹果（rose）-6个；智利姬娜苹果-6个
赠品：双层礼盒拎袋-1个
",
        "thum_photo": "product_pic/436/1/1-180x180-436.jpg",    #缩略图
        "photo": "product_pic/436/1/1-370x370-436.jpg",         #主图
        "id": "436",                #商品id
        "yd": "0",                  #保留字段
        "types": "a:5:{s:3:"hot";i:0;s:4:"pnew";i:0;s:6:"import";i:0;s:4:"sale";i:0;s:2:"yj";i:0;}",    #商品类型
        "lack": "0",                #是否缺货
        "maxgifts": "0",            #购买该商品可以获取的最大赠品数
        "parent_id": "0",           #保留字段
        "gift_photo": "",           #赠品图片
        "use_store": "0"            #是否开启库存
    },
    ...
]
```


###<a name="user.notice"/>会员中心消息提示 `user.notice`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |

* 接口返回
```json
  {
      "pay_num":50,       #待支付订单
      "comment_num":2,    #待评论订单
      "gift_num":0,       #待领取赠品
      "privilege_num":1,  #待使用特权
      "foretaste_num":0   #待发布试吃报告
  }
```


###<a name="user.levelLog"/>会员升降级日志 `user.levelLog`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |

* 接口返回
```json
  {
    now_rank: "3",
    logs: [
      {
        time: "2015-05-11",
        expire_date: "2015-08-11",         #等级有效期
        type: "2",
        to_rank: "3",
        reason: "由于您的订单数量以及订单金额已经满足“鲜果达人V2”的要求，会员等级由“普通会员”升级为“鲜果达人V2”。"
      }
    ]
  }
```

###<a name="user.checkRedIndicator"/>会员未读信息 `user.checkRedIndicator`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |

* 接口返回
```json
  {
    user_center: 0,
    guoshi: 0
  }
```

###<a name="user.cancelRedIndicator"/>会员新信息已读 `user.cancelRedIndicator`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |

* 接口返回
```json
  {
    code: "200",
    msg: "succ"
  }
```

###<a name="user.collectMobileData"/>收集用户手机系统数据 `user.collectMobileData`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| version_number    |    string  |       Y      |   操作系统版本号  |  V4.4.0或4.1.0    |
| device_number    |    string  |       N      |   手机的IMEI,IDFA,MEID  |  2ECCF8EA-8BBE-4C18-9716-A8CE959F9FBB    |
| android_id    |    string  |       N      |   安卓系统的Android Id  |  27914efd1b5cc989    |
| ip    |    string  |       Y      |   外网IP  |  127.0.0.1    |
| ua    |    string  |       Y      |   浏览器User-Agent, urlencode后传值  |  Mozilla/5.0 (iPhone; CPU iPhone OS 10_0 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Mobile/14A5341a    |

* 接口返回
```json
  {
    code: "200",
    msg: "succ"
  }
```

###<a name="user.collectChannelTracking"/>收集用户投放渠道追踪码 `user.collectChannelTracking`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| trackingId    |    string  |       Y      |   渠道追踪码  |  JAMDTPVvPF    |

* 接口返回
```json
  {
    code: "200",
    msg: "succ"
  }
```

###<a name="user.tradeInvoiceHistory"/>收集用户手机系统数据 `user.tradeInvoiceHistory`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   登录标识  |  7b5982f5747672d329d98352e2556f39    |
| page    |    int  |       N      |   分页，页数  |  1   |
| limit    |    int  |       N      |   每页显示数量  |  10    |

* 接口返回
```json
	[
		{
			uid: "5667371",                            #会员ID
			username: "嗯呀",                          #会员名
			mobile: "18149715819",
			name: "IE11507合并开票",                   #开票title
			address: "测试",
			money: "1073.89",
			time: "2016-03-30 18:05:15",               #发票申请时间
			province: "安徽省",
			city: "合肥市",
			area: "瑶海区",
			express: "韵达快递",                       #快递公司名称
			tracking_number: "1201599662402",          #运单号
			trade_list: [                              #涉及充值单号
				"T160318022424",
				"T160322411537",
				"T160322415646",
				"T160322417679",
				"T160322424454",
				"T160330502439",
				"T160330510285",
				"T160330510976",
				"T160330511675",
				"T160330512397",
				"T160330512828",
				"T160330513146",
				"T160330514341",
				"T160330517916",
				"T160330518990",
				"T160330539231"
			],
			status: 1                                 #发票状态 0未发出，1已发出，2已发出暂无运单号
		},
		{
			uid: "5667371",
			username: "吴盈盈",
			mobile: "18149715819",
			name: "上海尚然实业有限公司",
			address: "书院镇丽正路1059弄88号20",
			money: "13.00",
			time: "2016-03-30 17:44:47",
			province: null,
			city: null,
			area: null,
			express: "韵达快递",
			tracking_number: "1201599662384",
			trade_list: [
				"T160330549588"
			],
			status: 1
		},
		{
			uid: "5667371",
			username: "dsafsafdsafsaf",
			mobile: "18917588301",
			name: "fdfdsafdsaf",
			address: "fdsfdsafsaf",
			money: "505.00",
			time: "2016-03-30 16:47:27",
			province: "上海",
			city: "上海市",
			area: "浦东新区（外环线以外）",
			express: null,
			tracking_number: null,
			trade_list: [
				"T160323126376"
			],
			status: 2
		},
	]
```

##<a name="vip"/>会员中心

###<a name="vip.getSaleProduct"/>会员中心配置的特惠商品 `vip.getSaleProduct`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| region_id    |    string  |       Y      |   地区 Id  |  27914efd1b5cc989    |

* 接口返回
```json
  {
      "code": 200,
      "msg": [
          {
              "id": "2937",
              "product_name": "精选青柠檬",
              "photo": "http://image6.fruitday.com/images/product_pic/2937/1/1-270x270-2937-F5PF2FTS.jpg",
              "middle_photo": "images/product_pic/2937/1/1-270x270-2937-F5PF2FTS.jpg",
              "promotion_photo": "http://image4.fruitday.com/images/product_pic/2937/1/1-370x370-2937-U5PT7FS6.jpg",
              "product_no": "201411174",
              "volume": "1斤",
              "unit": "g",
              "price": "18.00",
              "price_id": "4230"
          },
          {
              "id": "6177",
              "product_name": "美国青苹果",
              "photo": "http://image4.fruitday.com/images/product_pic/6177/1/1-270x270-6177-R1A24CYF.jpg",
              "middle_photo": "images/product_pic/6177/1/1-270x270-6177-R1A24CYF.jpg",
              "promotion_photo": "",
              "product_no": "2151014106",
              "volume": "3个",
              "unit": "个",
              "price": "18.00",
              "price_id": "8301"
          },
          {
              "id": "7683",
              "product_name": "菲律宾香蕉",
              "photo": "http://image9.fruitday.com/images/product_pic/7683/1/1-270x270-7683-R6751WKR.jpg",
              "middle_photo": "images/product_pic/7683/1/1-270x270-7683-R6751WKR.jpg",
              "promotion_photo": "",
              "product_no": "2151221118",
              "volume": "2斤",
              "unit": "g",
              "price": "19.00",
              "price_id": "9854"
          }
      ]
  }
```
* 接口失败
```json
  {
    code: "300",
    msg: "xxx"
  }
```

###<a name="vip.recommend"/>会员中心配置的会员专享 `vip.recommend`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| region_id    |    string  |       Y      |   地区 Id  |  27914efd1b5cc989    |
| connect_id    |    string  |       Y      |   链接信息  |  27914efd1b5cc989    |

* 接口返回
```json
  [
      {
          "id": "9555",
          "product_name": "【会员中心】四川不知火柑",
          "photo": "http://image6.fruitday.com/images/product_pic/3973/1/1-270x270-3973-77A52UHR.jpg",
          "middle_photo": "images/product_pic/3973/1/1-270x270-3973-77A52UHR.jpg",
          "promotion_photo": "http://image1.fruitday.com/images/images/2016-02-29/1456743968_promotion_photo.jpg",
          "product_no": "2160311104",
          "volume": "5斤",
          "unit": "g",
          "price": "55.00",
          "price_id": "11755",
          "tag": "V2-V5会员专享"
      }
  ]
```
* 接口失败
```json
  {
    code: "300",
    msg: "xxx"
  }
```


###<a name="pointcanteen.getlist"/>会员中心配置的会员专享 `vip.recommend`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| region_id    |    string  |       Y      |   地区 Id  |  27914efd1b5cc989    |
| user_rank    |    int     |       Y      |   等级  |  1    |
| sort         |    int     |              |   排序 0正1逆 |  1    |

* 接口返回
```json
    {
        "code": 200,
        "msg": {
            "2": {
                "id": "3",
                "tag": "ny6OS3",
                "user_rank": "3",
                "bonus_point": "3",
                "region": "106092",
                "picture": "http: //imagews1.fruitday.com/images/images/2016-08-16/1471346691_photo.jpg",
                "order": "2",
                "begin_time": "2016-08-0100: 56: 23",
                "end_time": "2016-08-3123: 56: 23",
                "is_del": "0",
                "up_time": "2016-08-1214: 56: 41",
                "info": {
                    "product": {
                        "id": "10243",
                        "product_name": "城市小葱",
                        "discription": ""
                    }
                }
            }
        }
    }
```
* 接口失败
```json
  {
    code: "300",
    msg: "xxx"
  }
```


###<a name="pointcanteen.exchange"/>会员中心配置的会员专享 `vip.recommend`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   connect Id  |  27914efd1b5cc989    |
| id    |    int     |       Y      |   id  |  1    |

* 接口返回
```json
    {
        "code": 200,
        "msg":"兑换成功"
    }
```
* 接口失败
```json
  {
    code: "300",
    msg: "xxx"
  }
```

###<a name="foryousave.getlist"/>会员中心配置的会员专享 `vip.recommend`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| region_id    |    string  |       Y      |   地区 Id  |  27914efd1b5cc989    |
| user_rank    |    int     |       Y      |   等级  |  1    |
| sort         |    int     |              |   排序 0正1逆 |  1    |

* 接口返回
```json
    {
        "code": 200,
        "msg": {
            "2": {
                "id": "3",
                "tag": "ny6OS3",
                "user_rank": "3",
                "bonus_point": "3",
                "region": "106092",
                "picture": "http: //imagews1.fruitday.com/images/images/2016-08-16/1471346691_photo.jpg",
                "order": "2",
                "begin_time": "2016-08-0100: 56: 23",
                "end_time": "2016-08-3123: 56: 23",
                "is_del": "0",
                "up_time": "2016-08-1214: 56: 41",
                "info": {
                    "product": {
                        "id": "10243",
                        "product_name": "城市小葱",
                        "discription": ""
                    }
                }
            }
        }
    }
```
* 接口失败
```json
  {
    code: "300",
    msg: "xxx"
  }
```


###<a name="foryousave.exchange"/>会员中心配置的会员专享 `vip.recommend`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   connect Id  |  27914efd1b5cc989    |
| id    |    int     |       Y      |   id  |  1    |

* 接口返回
```json
    {
        "code": 200,
        "msg":"兑换成功"
    }
```
* 接口失败
```json
  {
    code: "300",
    msg: "xxx"
  }
```

###<a name="newproducttry.getlist"/>会员中心配置的会员专享 `vip.recommend`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| region_id    |    string  |       Y      |   地区 Id  |  27914efd1b5cc989    |
| user_rank    |    int     |       Y      |   等级  |  1    |
| sort         |    int     |              |   排序 0正1逆 |  1    |

* 接口返回
```json
    {
        "code": 200,
        "msg": {
            "2": {
                "id": "3",
                "tag": "ny6OS3",
                "user_rank": "3",
                "bonus_point": "3",
                "region": "106092",
                "picture": "http: //imagews1.fruitday.com/images/images/2016-08-16/1471346691_photo.jpg",
                "order": "2",
                "begin_time": "2016-08-0100: 56: 23",
                "end_time": "2016-08-3123: 56: 23",
                "is_del": "0",
                "up_time": "2016-08-1214: 56: 41",
                "info": {
                    "product": {
                        "id": "10243",
                        "product_name": "城市小葱",
                        "discription": ""
                    }
                }
            }
        }
    }
```
* 接口失败
```json
  {
    code: "300",
    msg: "xxx"
  }
```


###<a name="newproducttry.get"/>会员中心配置的会员专享 `vip.recommend`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   connect Id  |  27914efd1b5cc989    |
| id    |    int     |       Y      |   id  |  1    |

* 接口返回
```json
    {
        "code": 200,
        "msg":"兑换成功"
    }
```
* 接口失败
```json
  {
    code: "300",
    msg: "xxx"
  }
```



###<a name="birthdaygift.getThisMonthGift"/>会员中心配置的会员专享 `vip.recommend`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   connect Id  |  27914efd1b5cc989    |
| region_id    |    string  |       Y      |   地区 Id  |  27914efd1b5cc989    |

* 接口返回
```json
    {
        "id": "1",
        "tag": "MQtMm1",
        "month": "8",
        "picture": "http://imagews1.fruitday.com/images/images/2016-08-16/1471344079_photo.jpg",
        "region": "106092",
        "is_del": "0",
        "info": {
            "product": {
                "id": "2937",
                "product_name": "精选青柠檬",
                "discription": ""
            }
        }
    }
```
* 接口失败
```json
  {
    code: "300",
    msg: "xxx"
  }
```


###<a name="birthdaygift.get"/>会员中心配置的会员专享 `vip.recommend`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |   connect Id  |  27914efd1b5cc989    |
| tag    |    string  |       Y      |   tag    |  MQtMm1    |

* 接口返回
```json
    {
        code: "200",
        msg: "领取成功"
      }
```
* 接口失败
```json
  {
    code: "300",
    msg: "xxx"
  }
```



##<a name="product"/>商品

###<a name="product.commentsRate"/>评论各类占比 `product.commentsRate`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ |  ------|
| id    |    int  |       N     |   商品id  |       1111     |


* 接口返回
```json
{
    "good": 92,
    "normal": 6,
    "bad": 2,
    "num": {
        "total": 200,
        "good": 184,
        "normal": 12,
        "bad": 4,
        "has_image": 78
    }
}
```


###<a name="product.comments"/>对应商品的评论 `product.comments`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ |  ------|
| id    |    int  |       N     |   商品id  |       1111     |
| curr_page    |    int  |       N      |  分页页数(默认为0)  |   0  |
| page_size    |    int  |       N      |  每页个数  |    10  |
| type    |    string  |       N      |   评论级别  |    good,normal,bad |
| comment_type    |    int  |       N      |  评论类型  |    1有图片评论，0全部  |



* 接口返回
```json
	{
	    "list": [
	        {
	            "content": "活动价格很给力 ",
	            "time": "2014-04-09 09:16:41",
	            "star": "5",
	            "images": "http://7teb5m.com1.z0.glb.clouddn.com/img/images/share_comment/app541fdc44f1c6e6055270.jpg,http://7teb5m.com1.z0.glb.clouddn.com/img/images/share_comment/app541fdc44f1ef56055271.jpg",
	            "uid": "107974",
	            "user_name": "1379****939",
                    "userface": "http://cdn.fruitday.com/up_images/default_userpic.png"
	        },
	        {
	            "content": "有些硬，放的时间比较长~",
	            "time": "2014-04-06 19:02:14",
	            "star": "5",
	            "is_pic_tmp": "0",
	            "images": null,
	            "uid": "91043",
	            "user_name": "1391****739",
                    "userface": "http://cdn.fruitday.com/up_images/default_userpic.png"
	        },
	        {
	            "content": "真心太硬了，放了好久都没变软",
	            "time": "2014-04-05 21:17:54",
	            "star": "5",
	            "is_pic_tmp": "0",
	            "images": null,
	            "uid": "227581",
	            "user_name": "1381****607",
                    "userface": "http://cdn.fruitday.com//up_images/avatar/12e8bc147311e2f5bf7ba36fd0039a59/avatar_130.jpg"
	        }
	    ]
	}
```

###<a name="product.category"/>根据分类id获取商品 `product.category`

* 业务入参:

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ |  ------|
| class_id    |    int  |       Y      |   分类id  |       1     |
| sort    |    int  |       N      |   排序类型(0:默认、1:销量、2:价格低到高、3:价格高到低、4:浏览量)  |    0  |
| region_id    |    int  |       N      |  地区id  |    106092  |
| curr_page    |    int  |       N      |  分页页数(默认为0)  |   0  |
| page_size    |    int  |       N      |  每页个数  |    10  |
| channel    |    string  |       N      |  渠道ID  |   portal  |


* 接口返回:
 	- 成功
```json
{
    "classname": "品牌鲜果",
    "list": [
        {
            "id": "2203",
            "product_name": "佳沛意大利绿奇异果（原装） ",
            "thum_photo": "http://cdn.fruitday.com/product_pic/2203/1/1-180x180-2203.jpg",
            "photo": "http://cdn.fruitday.com/product_pic/2203/1/1-370x370-2203.jpg",
            "price": "128",
            "stock": null,
            "volume": "36个原装",
            "price_id": "2965",
            "product_no": "20149832",
            "product_id": "2203",
            "old_price": "0"
        },
        {
            "id": "2314",
            "product_name": "新奇士美国柠檬",
            "thum_photo": "http://cdn.fruitday.com/product_pic/2314/1/1-180x180-2314.jpg",
            "photo": "http://cdn.fruitday.com/product_pic/2314/1/1-370x370-2314.jpg",
            "price": "38",
            "stock": "0",
            "volume": "1斤装",
            "price_id": "3139",
            "product_no": "20149711",
            "product_id": "2314",
            "old_price": "0"
        }
    ]
}
```
 	- 失败
```json
{
	"code":300,
    "msg":"操作失败",
}
```



###<a name="product.search"/>根据搜索条件i取商品 `product.search`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ |  -----|
| keyword    |    string  |       Y      |   搜索内容  |  樱桃   |
| sort    |    int  |       N      |   排序类型(0:默认、1:销量、2:价格低到高、3:价格高到低、4:浏览量 ) |   0  |
| region_id    |    int  |       N      |  地区id  | 106092 |
| curr_page    |    int  |       N      |  分页页数(默认为0)  |   0  |
| page_size    |    int  |       N      |  每页个数  | 10  |
| channel    |    string  |       N      |  渠道ID  | portal |

* 接口返回
```json
	{
	    "classname": "樱桃",
	    "list": [
	        {
	            "id": "2203",
	            "product_name": "佳沛意大利绿奇异果（原装） ",
	            "thum_photo": "http://cdn.fruitday.com/product_pic/2203/1/1-180x180-2203.jpg",
	            "photo": "http://cdn.fruitday.com/product_pic/2203/1/1-370x370-2203.jpg",
	            "price": "128",
	            "stock": null,
	            "volume": "36个原装",
	            "price_id": "2965",
	            "product_no": "20149832",
	            "product_id": "2203",
	            "old_price": "0"
	        },
	        {
	            "id": "2314",
	            "product_name": "新奇士美国柠檬",
	            "thum_photo": "http://cdn.fruitday.com/product_pic/2314/1/1-180x180-2314.jpg",
	            "photo": "http://cdn.fruitday.com/product_pic/2314/1/1-370x370-2314.jpg",
	            "price": "38",
	            "stock": "0",
	            "volume": "1斤装",
	            "price_id": "3139",
	            "product_no": "20149711",
	            "product_id": "2314",
	            "old_price": "0"
	        }
	    ]
	}
```



###<a name="product.productList"/>根据商品id获取商品列表 `product.productList`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ |  -----|
| ids    |    string  |       Y      |   id串  |  1111,1112,1113   |
| sort    |    int  |       N      |   排序类型(0:默认、1:销量、2:价格低到高、3:价格高到低、4:浏览量 ) |   0  |
| region_id    |    int  |       N      |  地区id  | 106092 |
| curr_page    |    int  |       N      |  分页页数(默认为0)  |   0  |
| page_size    |    int  |       N      |  每页个数  | 10  |
| channel    |    string  |       N      |  渠道ID  | portal |

* 接口返回
```json
  {
      [
          {
              "id": "2203",
              "product_name": "佳沛意大利绿奇异果（原装） ",
              "thum_photo": "http://cdn.fruitday.com/product_pic/2203/1/1-180x180-2203.jpg",
              "photo": "http://cdn.fruitday.com/product_pic/2203/1/1-370x370-2203.jpg",
              "price": "128",
              "stock": null,
              "volume": "36个原装",
              "price_id": "2965",
              "product_no": "20149832",
              "product_id": "2203",
              "old_price": "0"
          },
          {
              "id": "2314",
              "product_name": "新奇士美国柠檬",
              "thum_photo": "http://cdn.fruitday.com/product_pic/2314/1/1-180x180-2314.jpg",
              "photo": "http://cdn.fruitday.com/product_pic/2314/1/1-370x370-2314.jpg",
              "price": "38",
              "stock": "0",
              "volume": "1斤装",
              "price_id": "3139",
              "product_no": "20149711",
              "product_id": "2314",
              "old_price": "0"
          }
      ]
  }
```



###<a name="product.priceSearch"/>根据价格区间搜索 `product.priceSearch`(未完成)

* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ | ------------ |
| key    |    int  |       Y      |   搜索区间标识(1:100以下、2:100-300、3:300-500、4:500以上)  |   1   |
| sort    |    int  |       N      |   排序类型(0:默认、1:销量、2:价格低到高、3:价格高到低、4:浏览量)  |  0  |
| region_id    |    int  |       N      |  地区id  |      106092   |
| curr_page    |    int  |       N      |  分页页数(默认为0)  |   0  |
| page_size    |    int  |       N      |  每页个数  |   10    |
| channel    |    string  |       N      |  渠道ID  |     portal   |

* 接口返回
```json
   [
        {
            "price": "228",             #价格
            "pc_price":"0",             #pc端价格，为0表示与手机端一致
            "mem_lv": "",               #会员等级
            "mem_lv_price": "",         #会员价格
            "can_mem_buy": "1",         #是否普通会员可以购买
            "stock": "",                #库存
            "old_price": "0",           #原价
            "volume": "双层礼盒",        #规格
            "price_id": "546",          #sku_id
            "product_name": "Mom"s Love",#商品名称
            "summary": "优选越南火龙果-2个 ",#商品详情
            "thum_photo": "product_pic/436/1/1-180x180-436.jpg",    #缩略图
            "photo": "product_pic/436/1/1-370x370-436.jpg",         #主图
            "id": "436",                #商品id
            "yd": "0",                  #保留字段
            "types": "a:5:{s:3:"hot";i:0;s:4:"pnew";i:0;s:6:"import";i:0;s:4:"sale";i:0;s:2:"yj";i:0;}",    #商品类型
            "lack": "0",                #是否缺货
            "maxgifts": "0",            #购买该商品可以获取的最大赠品数
            "parent_id": "0",           #保留字段
            "gift_photo": "",           #赠品图片
            "use_store": "0"            #是否开启库存
        }
    ]
```


###<a name="product.searchKey"/>获取搜索关键字 `product.searchKey`
* 业务入参

	|     参数名    |     类型      |      必须     |     说明     |  示例    |
  | ------------ | ------------- | ------------ | ------------ |------------ |
  | region_id    |    int  |       N      |  地区id  |  106092   |

* 接口返回
wap返回
```json
    [
        "奇异果",
        "橙",
        "苹果"
    ]
```
app返回
```json
    {
    "searchKey":["\u5947\u5f02\u679c","\u6a59","\u82f9\u679c","\u68a8","\u6a31\u6843"],
    "search_banner":[
        {
            "photo":"http:\/\/cdn.fruitday.com\/images\/2014-11-14\/1415956609_pic.jpg",
            "price":"0",
            "title":"\u667a\u5229\u6a31\u6843\u9884\u552e",
            "type":"2",
            "target_id":"1111",
            "description":"",
            "page_url":""
        }
    ]
}
```

###<a name="product.tag"/>根据促销板块获取商品 `product.tag`(未完成)
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| type    |    string  |       Y      |  板块类型(new:新品、top:热销、sale:折扣、recommend:推荐、gift:赠品 )  |   new  |
| sort    |    int  |       N      |   排序类型(0:默认、1:销量、2:价格低到高、3:价格高到低、4:浏览量)  |   0   |
| region_id    |    int  |       N      |  地区id  |  106092   |
| curr_page    |    int  |       N      |  分页页数(默认为0)  |   0  |
| page_size    |    int  |       N      |  每页个数  |   10   |
| channel    |    string  |       N      |  渠道ID  |   portal   |

* 接口返回
```json
    [
        {
            "price": "228",             #价格
            "pc_price":"0",             #pc端价格，为0表示与手机端一致
            "mem_lv": "",               #会员等级
            "mem_lv_price": "",         #会员价格
            "can_mem_buy": "1",         #是否普通会员可以购买
            "stock": "",                #库存
            "old_price": "0",           #原价
            "volume": "双层礼盒",        #规格
            "price_id": "546",          #sku_id
            "product_name": "Mom"s Love",#商品名称
            "summary": "优选越南火龙果-2个 ",#商品详情
            "thum_photo": "product_pic/436/1/1-180x180-436.jpg",    #缩略图
            "photo": "product_pic/436/1/1-370x370-436.jpg",         #主图
            "id": "436",                #商品id
            "yd": "0",                  #保留字段
            "types": "a:5:{s:3:"hot";i:0;s:4:"pnew";i:0;s:6:"import";i:0;s:4:"sale";i:0;s:2:"yj";i:0;}",    #商品类型
            "lack": "0",                #是否缺货
            "maxgifts": "0",            #购买该商品可以获取的最大赠品数
            "parent_id": "0",           #保留字段
            "gift_photo": "",           #赠品图片
            "use_store": "0"            #是否开启库存
        }
    ]
```


###<a name="product.productInfo"/>商品详情 `product.productInfo`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |   示例    |
| ------------ | ------------- | ------------ | ------------ | ------------ |
| id    |    int  |       Y      |  商品id  |    1111   |
| channel    |    string  |       N      |  渠道ID  |  portal  |

* 接口返回
 - 成功

```json
	{
	    "product": {
	        "id": "2203",
	        "product_name": "佳沛意大利绿奇异果（原装） ",
	        "discription": "<img width=\"789\" height=\"483\" alt=\"\" src=\"http://cdn.fruitday.com/up_images/1392887668.jpg\" /><img width=\"789\" height=\"1118\" alt=\"\" src=\"http://cdn.fruitday.com/up_images/1392887683.jpg\" /><img width=\"788\" height=\"376\" alt=\"\" src=\"http://cdn.fruitday.com/up_images/1392887702.jpg\" /><img width=\"788\" height=\"447\" alt=\"\" src=\"http://cdn.fruitday.com/up_images/1392887714.jpg\" /><img width=\"788\" height=\"686\" alt=\"\" src=\"http://cdn.fruitday.com/up_images/1392887746.jpg\" /><img width=\"800\" height=\"435\" alt=\"\" src=\"http://cdn.fruitday.com/up_images/1395223287.jpg\" /><img width=\"800\" height=\"195\" alt=\"\" src=\"http://cdn.fruitday.com/up_images/1395223295.jpg\" />",
	        "photo": "product_pic/2203/1/1-370x370-2203.jpg",
	        "thum_photo": "product_pic/2203/1/1-180x180-2203.jpg",
	        "free": "0",
                "op_place": "1",
                "op_detail_place": "59",
                "op_size": "1",
                "tag_id": "13",
                "op_weight": "",
                "parent_id": "40"
	    },
	    "items": [
	        {
	            "price": "128",
	            "volume": "36个原装",
	            "id": "2965",
	            "product_no": "20149832",
	            "product_id": "2203",
	            "old_price": "0"
	        }
	    ],
	    "photo": [
	        {
	            "thum_photo": "http://cdn.fruitday.com/product_pic/2203/1/1-180x180-2203.jpg",
	            "photo": "http://cdn.fruitday.com/product_pic/2203/1/1-370x370-2203.jpg"
	        },
	        {
	            "thum_photo": "http://cdn.fruitday.com/product_pic/2203/4/4-180x180-2203.jpg",
	            "photo": "http://cdn.fruitday.com/product_pic/2203/4/4-370x370-2203.jpg"
	        },
	        {
	            "thum_photo": "http://cdn.fruitday.com/product_pic/2203/2/2-180x180-2203.jpg",
	            "photo": "http://cdn.fruitday.com/product_pic/2203/2/2-370x370-2203.jpg"
	        }
	    ],
	    "share_url": "http://m.fruitday.com/pro/2203",
        "promotion": [
            {
                "title": "满100-20",
                "type": "h5",
                "target_url": "http://huodong.fruitday.com/sale/o2o1130/30tw.html?",
                "target_product_id": "9960",
            }
        ]
	}
```
 - 失败
```json
	{
	    "code": "300",
	    "msg": "该商品已售罄"
	}
```

###<a name="product.getCatList"/>获取所有分类 product.getCatList

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| region_id    |    int  |       Y      |  商品地区id  |     1      |

* 接口返回
 - 有数据时
```json
	{
	    "hot": [
	        {
	            "id": "40",
	            "name": "所有鲜果",
	            "ename": "",
	            "is_hot": "1",
	            "photo": "",
	            "class_photo": "http://cdn.fruitday.com/images/2014-12-22/1419231531_class_photo.jpg"
	        },
	        {
	            "id": "81",
	            "name": "国产鲜果",
	            "ename": "",
	            "is_hot": "1",
	            "photo": "",
	            "class_photo": "http://cdn.fruitday.com/images/2015-01-29/1422513579_class_photo.jpg"
	        }
	    ],
	    "common": [
	        {
	            "id": "59",
	            "name": "品牌鲜果",
	            "ename": "",
	            "is_hot": "0",
	            "photo": "",
	            "class_photo": ""
	        },
	        {
	            "id": "82",
	            "name": "尝鲜小包装",
	            "ename": "",
	            "is_hot": "0",
	            "photo": "",
	            "class_photo": ""
	        }
	    ]
	}
```


- 无数据时
```json
	{
	    "hot": [],
	    "common": []
	}
```


###<a name="product.pageListProducts"/>获取专题页商品 `product.pageListProducts`
    (未完成):抢购列表页再进详情页面示											例:http://wapi.fruitday.com/appProduct/pageListProducts/4/0/0/106092/2806     最后一栏是product_id,target_id补0

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| page_type    |    int  |       Y      |  marketing.banner返回的type  |  1  |
| target_id    |    int  |       Y      |  marketing.banner返回的target_id  | 2  |
| sort    |    int  |       Y      |  排序类型(0:默认 | 1:销量 | 2:价格低到高 | 3:价格高到低 | 4:浏览量  | 0  |
| region_id    |    int  |       Y      |  商品地区id  |  1  |
| channel    |    string  |       Y      |  渠道ID  |  portal  |

* 接口返回
```json

    {
        "title": "test",    #页面title
        "page_photo": "http:\/\/static.fruitday.com\/a.jpg",
        "recommend":[     #推荐
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
        "rotation": [    #轮播图
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",            #目标页面类型可选值(1|2)，1:专题，2:详情
                "target_id": "12345",   #目标页面对应id，如果是专题则为专题id,如果是详情则为详情id,如果是列表则为列表id
                "description": "天天果园描述"
            },
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184251_pic.jpg",
                "price": "2",
                "title": "天天果园-枇杷手机端",
                "type": "1",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
        "products":[
            {
                "price": "228",             #价格
                "pc_price":"0",             #pc端价格，为0表示与手机端一致
                "mem_lv": "",               #会员等级
                "mem_lv_price": "",         #会员价格
                "can_mem_buy": "1",         #是否普通会员可以购买
                "stock": "",                #库存
                "old_price": "0",           #原价
                "volume": "双层礼盒",        #规格
                "price_id": "546",          #sku_id
                "product_name": "Mom"s Love",#商品名称
                "summary": "优选越南火龙果-2个", #商品详情
                "thum_photo": "product_pic/436/1/1-180x180-436.jpg",    #缩略图
                "photo": "product_pic/436/1/1-370x370-436.jpg",         #主图
                "id": "436",                #商品id
                "yd": "0",                  #保留字段
                "types": "a:5:{s:3:"hot";i:0;s:4:"pnew";i:0;s:6:"import";i:0;s:4:"sale";i:0;s:2:"yj";i:0;}",    #商品类型
                "lack": "0",                #是否缺货
                "maxgifts": "0",            #购买该商品可以获取的最大赠品数
                "parent_id": "0",           #保留字段
                "gift_photo": "",           #赠品图片
                "use_store": "0"            #是否开启库存
            }
        ],
        "flash_sale":[
            {
                "price": "228",             #价格
                "pc_price":"0",             #pc端价格，为0表示与手机端一致
                "mem_lv": "",               #会员等级
                "mem_lv_price": "",         #会员价格
                "can_mem_buy": "1",         #是否普通会员可以购买
                "stock": "",                #库存
                "old_price": "0",           #原价
                "volume": "双层礼盒",        #规格
                "price_id": "546",          #sku_id
                "product_name": "Mom"s Love",#商品名称
                "summary": "优选越南火龙果-2个", #商品详情
                "thum_photo": "product_pic/436/1/1-180x180-436.jpg",    #缩略图
                "photo": "product_pic/436/1/1-370x370-436.jpg",         #主图
                "id": "436",                #商品id
                "yd": "0",                  #保留字段
                "types": "a:5:{s:3:"hot";i:0;s:4:"pnew";i:0;s:6:"import";i:0;s:4:"sale";i:0;s:2:"yj";i:0;}",    #商品类型
                "lack": "0",                #是否缺货
                "maxgifts": "0",            #购买该商品可以获取的最大赠品数
                "parent_id": "0",           #保留字段
                "gift_photo": "",           #赠品图片
                "use_store": "0"            #是否开启库存
            }
        ],
         "advance":[
             {
                 "price": "228",             #价格
                 "pc_price":"0",             #pc端价格，为0表示与手机端一致
                 "mem_lv": "",               #会员等级
                 "mem_lv_price": "",         #会员价格
                 "can_mem_buy": "1",         #是否普通会员可以购买
                 "stock": "",                #库存
                 "old_price": "0",           #原价
                 "volume": "双层礼盒",        #规格
                 "price_id": "546",          #sku_id
                 "product_name": "Mom"s Love",#商品名称
                 "summary": "优选越南火龙果-2个", #商品详情
                 "thum_photo": "product_pic/436/1/1-180x180-436.jpg",    #缩略图
                 "photo": "product_pic/436/1/1-370x370-436.jpg",         #主图
                 "id": "436",                #商品id
                 "yd": "0",                  #保留字段
                 "types": "a:5:{s:3:"hot";i:0;s:4:"pnew";i:0;s:6:"import";i:0;s:4:"sale";i:0;s:2:"yj";i:0;}",    #商品类型
                 "lack": "0",                #是否缺货
                 "maxgifts": "0",            #购买该商品可以获取的最大赠品数
                 "parent_id": "0",           #保留字段
                 "gift_photo": "",           #赠品图片
                 "use_store": "0"            #是否开启库存
             }
         ]
    }
```


###<a name="product.enterpriseProducts"/>企业用户专享商品 `product.enterpriseProducts`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |  登录标识  | 7b5982f5747672d329d98352e2556f39  |
| sort    |    int  |       Y      |  排序类型(0:默认 | 1:销量 | 2:价格低到高 | 3:价格高到低 | 4:浏览量  | 0  |
| region_id    |    int  |       Y      |  商品地区id  |  1  |

* 接口返回
```json

    {
        "title": "test",    #页面title
        "page_photo": "http:\/\/static.fruitday.com\/a.jpg",
        "recommend":[     #推荐
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
        "products":[
            {
                "price": "228",             #价格
                "pc_price":"0",             #pc端价格，为0表示与手机端一致
                "mem_lv": "",               #会员等级
                "mem_lv_price": "",         #会员价格
                "can_mem_buy": "1",         #是否普通会员可以购买
                "stock": "",                #库存
                "old_price": "0",           #原价
                "volume": "双层礼盒",        #规格
                "price_id": "546",          #sku_id
                "product_name": "Mom"s Love",#商品名称
                "summary": "优选越南火龙果-2个", #商品详情
                "thum_photo": "product_pic/436/1/1-180x180-436.jpg",    #缩略图
                "photo": "product_pic/436/1/1-370x370-436.jpg",         #主图
                "id": "436",                #商品id
                "yd": "0",                  #保留字段
                "types": "a:5:{s:3:"hot";i:0;s:4:"pnew";i:0;s:6:"import";i:0;s:4:"sale";i:0;s:2:"yj";i:0;}",    #商品类型
                "lack": "0",                #是否缺货
                "maxgifts": "0",            #购买该商品可以获取的最大赠品数
                "parent_id": "0",           #保留字段
                "gift_photo": "",           #赠品图片
                "use_store": "0"            #是否开启库存
            }
    ],
    "rotation": [    #轮播图
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184330_pic.jpg",
                "price": "1",
                "title": "天天果园-枇杷手机端",
                "type": "2",            #目标页面类型可选值(1|2)，1:专题，2:详情
                "target_id": "12345",   #目标页面对应id，如果是专题则为专题id,如果是详情则为详情id,如果是列表则为列表id
                "description": "天天果园描述"
            },
            {
                "photo": "http://static.fruitday.com/images/2014-04-11/1397184251_pic.jpg",
                "price": "2",
                "title": "天天果园-枇杷手机端",
                "type": "1",
                "target_id": "12345",
                "description": "天天果园描述"
            }
        ],
    }
```


###<a name="product.mark"/>关注商品 `product.mark`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |  登录标识  | 7b5982f5747672d329d98352e2556f39  |
| product_id    |    int  |       Y      |  商品id  |  1  |

* 接口返回
```json
  {
    "code":"200",
    "msg":"succ"
  }
```


###<a name="product.cancelMark"/>取消关注 `product.cancelMark`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |  登录标识  | 7b5982f5747672d329d98352e2556f39  |
| product_id    |    int  |       Y      |  商品id  |  1  |

* 接口返回
```json
  {
    "code":"200",
    "msg":"succ"
  }
```


###<a name="product.markStatus"/>获取商品关注情况 `product.markStatus`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |  登录标识  | 7b5982f5747672d329d98352e2556f39  |
| product_id    |    int  |       Y      |  商品id  |  1  |

* 接口返回
```json
  {
    "code":"200",
    "msg":"true"  #可选值(true|false)true:已关注,false:未关注
  }
```


###<a name="product.markList"/>获取用户已经关注的商品列表 `product.markList`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       Y      |  登录标识  | 7b5982f5747672d329d98352e2556f39  |

* 接口返回
```json
  [
    {
        "id":"1882",
        "product_name":"云南蒙自石榴",
        "tag_id":"40",
        "photo":"http:\/\/static.fruitday.com\/product_pic\/1882\/1\/1-100x100-1882.jpg",
        "mark_time":"2014-04-14 14:45:30"
    }
  ]
```

###<a name="product.markedProducts"/>获取用户已关注的商品的相关商品 `product.markedProducts`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| tag_id    |    int  |       Y      |  类型id(product.markList接口返回的tag_id)  | 1  |
| sort    |    int  |       N      |  排序  | 1  |
| region_id    |    int  |       N      |  地区id  | 1  |
| page_size    |    int  |       N      |  每页多少条，默认10条  | 10  |
| curr_page    |    int  |       N      |  当前页码，默认0  | 1  |

* 接口返回
```json
  [
    {
        "price": "228",             #价格
        "pc_price":"0",             #pc端价格，为0表示与手机端一致
        "mem_lv": "",               #会员等级
        "mem_lv_price": "",         #会员价格
        "can_mem_buy": "1",         #是否普通会员可以购买
        "stock": "",                #库存
        "old_price": "0",           #原价
        "volume": "双层礼盒",        #规格
        "price_id": "546",          #sku_id
        "product_name": "Mom"s Love",#商品名称
        "summary": "                #商品详情

优选越南火龙果-2个；佳沛新西兰绿奇异果-4个；澳大利亚葡萄柚-3个；南非柠檬-3个；新西兰红玫瑰苹果（rose）-6个；智利姬娜苹果-6个
赠品：双层礼盒拎袋-1个
",
        "thum_photo": "product_pic/436/1/1-180x180-436.jpg",    #缩略图
        "photo": "product_pic/436/1/1-370x370-436.jpg",         #主图
        "id": "436",                #商品id
        "yd": "0",                  #保留字段
        "types": "a:5:{s:3:"hot";i:0;s:4:"pnew";i:0;s:6:"import";i:0;s:4:"sale";i:0;s:2:"yj";i:0;}",    #商品类型
        "lack": "0",                #是否缺货
        "maxgifts": "0",            #购买该商品可以获取的最大赠品数
        "parent_id": "0",           #保留字段
        "gift_photo": "",           #赠品图片
        "use_store": "0"            #是否开启库存
    },
    ...
]
```


###<a name="product.recommend"/>关联商品推荐 `product.recommend`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| id    |    int  |       Y      |  商品id | 1  |
| region_id    |    int  |       Y      |  分站地区id | 1  |

* 接口返回
```json
  [
    {
        "price": "228",             #价格
        "pc_price":"0",             #pc端价格，为0表示与手机端一致
        "mem_lv": "",               #会员等级
        "mem_lv_price": "",         #会员价格
        "can_mem_buy": "1",         #是否普通会员可以购买
        "stock": "",                #库存
        "old_price": "0",           #原价
        "volume": "双层礼盒",        #规格
        "price_id": "546",          #sku_id
        "product_name": "Mom"s Love",#商品名称
        "summary": "                #商品详情

优选越南火龙果-2个；佳沛新西兰绿奇异果-4个；澳大利亚葡萄柚-3个；南非柠檬-3个；新西兰红玫瑰苹果（rose）-6个；智利姬娜苹果-6个
赠品：双层礼盒拎袋-1个
",
        "thum_photo": "product_pic/436/1/1-180x180-436.jpg",    #缩略图
        "photo": "product_pic/436/1/1-370x370-436.jpg",         #主图
        "id": "436",                #商品id
        "yd": "0",                  #保留字段
        "types": "a:5:{s:3:"hot";i:0;s:4:"pnew";i:0;s:6:"import";i:0;s:4:"sale";i:0;s:2:"yj";i:0;}",    #商品类型
        "lack": "0",                #是否缺货
        "maxgifts": "0",            #购买该商品可以获取的最大赠品数
        "parent_id": "0",           #保留字段
        "gift_photo": "",           #赠品图片
        "use_store": "0"            #是否开启库存
    },
    ...
]
```


###<a name="product.sendInfo"/>获取商品配送信息 `product.sendInfo`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| connect_id    |    string  |       N      |  登录标识  | 7b5982f5747672d329d98352e2556f39  |
| product_id    |    int  |       Y      |  商品id  |  1  |
| region_id     |    int  |       Y      |  分站地区id | 1  |
| area_id       |    int  |       N      |  三级地区id         |333|

* 接口返回
```json
  {
    "code":"200",
    "msg":
    {
      "can_buy":1,
      "send_desc":"12月02日09:00-18:00送达",
      "area_info":
      {
        "province":
          {
            "id":"106092",
            "name":"上海"
          },
        "city":
          {
            "id":"106093",
            "name":"上海市"
          },
        "area":
          {
            "id":"106094",
            "name":"浦东新区（外环线以内）"
          }
      }
    }
  }
```

###<a name="product.getPriceInfo"/>获取商品配送信息 `product.getPriceInfo`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| id    |    int  |       Y      |  商品id  |  1  |

* 接口返回
```json
[
    {
        "id": "8226",
        "price": "38.00",
        "volume": "6个",
        "product_no": "2151012110",
        "product_id": "6107",
        "old_price": "0",
        "unit": "盒",
        "stock": "900"
    }
]
```

###<a name="product.search_v2"/>获取商品配送信息 `product.search_v2`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     | 示例    |
| ------------ | ------------- | ------------ | ------------ |  -----|
| keyword    |    string  |       Y      |   搜索内容  |  樱桃   |
| curr_page    |    int  |       N      |  分页页数(默认为0)  |   0  |
| page_size    |    int  |       N      |  每页个数  | 10  |
| channel    |    string  |       N      |  渠道ID  | portal |

* 接口返回
```json
  {
      "classname": "樱桃",
      "list": [
          {
              id: "1389",
              product_name: "优选海南红心木瓜",
              thum_photo: "http://imgws3.fruitday.com/images/product_pic/1389/1/1-180x180-1389-7P1PKP2A.jpg",
              photo: "http://imgws3.fruitday.com/images/product_pic/1389/1/1-270x270-1389-7P1PKP2A.jpg",
              middle_photo: "http://imgws3.fruitday.com/images/product_pic/1389/1/1-270x270-1389-7P1PKP2A.jpg",
              promotion_photo: "http://imgws3.fruitday.com/images/product_pic/1389/1/1-370x370-1389-W7X56A3S.jpg",
              middle_promotion_photo: "http://imgws3.fruitday.com/images/product_pic/1389/1/1-270x270-1389-W7X56A3S.jpg",
              thum_promotion_photo: "http://imgws3.fruitday.com/images/product_pic/1389/1/1-180x180-1389-W7X56A3S.jpg",
              product_types: {
              hot: 0,
              pnew: 0,
              import: 0,
              sale: 0,
              yj: 0,
              th: 0
              },
              product_desc: "果实硕大丰满 甜糯红心",
              long_photo: "http://imgws3.fruitday.com/",
              op_detail_place: "50",
              op_size: "1",
              op_occasion: "3",
              summary: "天天果园精选海南红心木瓜，更天然更营养。果实硕大，外形美观，果肉呈红色，厚实细致、清甜香浓、软滑多汁。</span></p>",
              lack: "0",
              use_store: "0",
              cart_tag: "",
              cang_id: "",
              price: "40.00",
              mobile_price: "0",
              stock: "0",
              volume: "2个",
              price_id: "1855",
              product_no: "31563 ",
              product_id: "1389",
              old_price: "0",
              sku_online: "1",
              prodcut_desc: "果实硕大丰满 甜糯红心",
              is_hidecart: 0
          },
      ],
      commend: []
  }
```

###<a name="product.getKeyword"/>获取商品配送信息 `product.getKeyword`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| keyword    |    string  |       Y      |   搜索内容  |  樱桃   |
| channel    |    string  |       N      |  渠道ID  | portal |

* 接口返回
```json
  [
      "绿袍蜜桔",
      "蜜橘",
      "大红蜜橘",
      "桔子",
      "失眠",
      "瘦身",
      "柑橘",
      "橘子",
      "ganju",
      "juzi"
  ]
```


##<a name="cart"/>购物车<a name="cart_items"/>*cart_items结构*

    {
        "normal_4840":{
            "sku_id":"4840",
            "product_id":"3221",
            "qty":3,
            "product_no":"201410550",
            "item_type":"normal"
        },
        "exch_4422":{
            "sku_id":"4422",
            "product_id":"3035",
            "qty":1,
            "product_no":"20149460",
            "item_type":"exch",
            "pmt_id":"30"
        }
    }

###<a name="cart.add"/>加入购物车 `cart.add`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |  ---------- |
| connect_id    |    string  |       N      |  登录标识  |  7b5982f5747672d329d98352e2556f39  |
| region_id    |    int  |       Y      |  地区id  |   1   |
| carttmp    |    string  |       N      |  未登录时添加的购物车结构(见[cart_items](#cart_items))  |  cart_items  |
| items    |    string  |       Y      |  购物车入参结构(见items)  |   items  |

	items结构:
	    [
	        {
	            "ppid":"3221" //规格商品ID
	            "pid":"4840" //主商品ID
	            "qty":"1" //数量
	            "pno":"20149460" //货号
	            "type":"normal" //普通商品类型
	        },
	        {
	            "ppid":"3221" //规格商品ID
	            "pid":"4840" //主商品ID
	            "qty":"1" //数量
	            "pno":"20149460" //货号
	            "type":"exch" //换购商品类型
	            "pmt_id":"30" //换购的优惠ID
	        },
	        {
	            "ppid":"3221" //规格商品ID
	            "pid":"4840" //主商品ID
	            "qty":"1" //数量
	            "pno":"20149460" //货号
              "gift_send_id":28,
              "gift_active_type":1,
	            "type":"user_gift" //赠品领取类型,
	        }
	    ]

* 接口返回
	- 成功
	 	*  app返回结构
```json
	{
		cartcount:3
	}
```
	 	*  wap/web返回结构
```json
	{
		"code":200,
		"msg":"",
		"data":
		{
		    "cart_items":{
		        "normal_3687":{
		            "sku_id":"3687",
		            "product_id":"2623",
		            "qty":2,
		            "product_no":"20149460",
		            "item_type":"normal"
		        },
				"user_gift":{
		            "ppid":"3221",
		            "pid":"4840",
		            "qty":"1",
		            "pno":"20149460",
                "gift_send_id":28,
		            "type":"user_gift"
				}
		    },
			cartcount:3
		}
	}
```
	- 失败
```json
	{
		"code":300,
		"msg":"加入购物车失败"
	}
```

###<a name="cart.update"/>更新购物车 `cart.update`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |---------- |
| connect_id    |    string  |       N      |  登录标识  | 7b5982f5747672d329d98352e2556f39  |
| region_id    |    int  |       Y      |  地区id  |   1   |
| carttmp    |    string  |       N      |  未登录时添加的购物车结构(见[cart_items](#cart_items))  | cart_items  |
| item    |    string  |       Y      |  购物车入参结构(见item)  |  item  |

	item结构
		{
			"ik":"normal_3687" //购物车ITEM的KEY值
	        "qty":"1" //数量
	        "type":"normal" //普通商品类型
	    }

* 接口返回
 - 成功
	* app返回结构
```json
    {
        "cart":
            {
                "items":
                    {
                        "normal_3687":
                            {
                                "sku_id":"3687",
                                "product_id":"2623",
                                "qty":1,
                                "product_no":"201410550",
                                "item_type":"normal",
                                "name":"奇异果",
                                "unit":"盒",
                                "spec":"33个装",
								"photo": {
                                        "huge": "http://cdn.fruitday.com/product_pic/3035/1/1-1000x1000-3035-TH17W9UA.jpg",
                                        "big": "http://cdn.fruitday.com/product_pic/3035/1/1-370x370-3035-TH17W9UA.jpg",
                                        "middle": "http://cdn.fruitday.com/product_pic/3035/1/1-270x270-3035-TH17W9UA.jpg",
                                        "small": "http://cdn.fruitday.com/product_pic/3035/1/1-180x180-3035-TH17W9UA.jpg",
                                        "thum": "http://cdn.fruitday.com/product_pic/3035/1/1-100x100-3035-TH17W9UA.jpg"
                                    },
                                "weight":"0.00",
                                "price":"218",
                                "sale_price":"218",
                                "pmt_price":0,
                                "amount":218,
                                "status":"active",
                                "pmt_price_total":0,
                                "goods_cost":"218"
                            }
                    },
                    "total_amount":"218",
                    "goods_amount":"218",
                    "goods_cost":"218",
                    "pmt_goods":0,
                    "cost_freight":0,
                    "pmt_total":"0",
                    "pmt_alert":
                        [
                            {
                                "pmt_type":"amount",
                                "solution":
                                    {
                                        "title":"满300元送优选海南甜脆小番茄",
                                        "tag":"促"
                                    }
                            }
                        ]
            },
		"cartcount":1,

    }
```
	* wap/web返回结构
```json
    {
        "code":200,
        "msg":"更新成功",
        "data":
            {
                "cart":
                    {
                        "items":
                            {
                                "normal_3687":
                                    {
                                        "sku_id":"3687",
                                        "product_id":"2623",
                                        "qty":1,
                                        "product_no":"201410550",
                                        "item_type":"normal",
                                        "name":"奇异果",
                                        "unit":"盒",
                                        "spec":"33个装",
                                        "product_photo":"http:\/\/cdn.fruitday.com\/product_pic\/2623\/1\/1-100x100-2623-W2WRCBPA.jpg",
                                        "weight":"0.00",
                                        "price":"218",
                                        "sale_price":"218",
                                        "pmt_price":0,
                                        "amount":218,
                                        "status":"active",
                                        "pmt_price_total":0,
                                        "goods_cost":"218"
                                    }
                            },
                            "total_amount":"218",
                            "goods_amount":"218",
                            "goods_cost":"218",
                            "pmt_goods":0,
                            "cost_freight":0,
                            "pmt_total":"0",
                            "pmt_alert":
                                [
                                    {
                                        "pmt_type":"amount",
                                        "solution":
                                            {
                                                "title":"满300元送优选海南甜脆小番茄",
                                                "tag":"促"
                                            }
                                    }
                                ]
                    },
                    "cart_items":
                        {
                            "normal_3687":
                                {
                                    "sku_id":"3687",
                                    "product_id":"2623",
                                    "qty":1,
                                    "product_no":null,
                                    "item_type":"normal"
                                }
                        }
					cartcount:1
            }
    }
```
 - 失败
```json
	{
		"code":300,
		"msg":"操作失败"
	}
```

###<a name="cart.get"/>获取购物车 `cart.get`


* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |---------- |
| connect_id    |    string  |       N      |  登录标识  | 7b5982f5747672d329d98352e2556f39  |
| region_id    |    int  |       Y      |  地区id  |  1   |
| carttmp    |    string  |       N      |  未登录时添加的购物车结构(见[cart_items](#cart_items))  |   cart_items  |


* 接口返回
 - 成功
 	* app返回结构
```json
    {
        "cart":
            {
                "items":
                    {
                        "normal_3687":
                            {
                                "sku_id":"3687",
                                "product_id":"2623",
                                "qty":1,
                                "product_no":"201410550",
                                "item_type":"normal",
                                "name":"奇异果",
                                "unit":"盒",
                                "spec":"33个装",
								"photo": {
                                        "huge": "http://cdn.fruitday.com/product_pic/3035/1/1-1000x1000-3035-TH17W9UA.jpg",
                                        "big": "http://cdn.fruitday.com/product_pic/3035/1/1-370x370-3035-TH17W9UA.jpg",
                                        "middle": "http://cdn.fruitday.com/product_pic/3035/1/1-270x270-3035-TH17W9UA.jpg",
                                        "small": "http://cdn.fruitday.com/product_pic/3035/1/1-180x180-3035-TH17W9UA.jpg",
                                        "thum": "http://cdn.fruitday.com/product_pic/3035/1/1-100x100-3035-TH17W9UA.jpg"
                                    },
                                "weight":"0.00",
                                "price":"218",
                                "sale_price":"218",
                                "pmt_price":0,
                                "amount":218,
                                "status":"active",
                                "pmt_price_total":0,
                                "goods_cost":"218"
                            }
                    },
                    "total_amount":"218",
                    "goods_amount":"218",
                    "goods_cost":"218",
                    "pmt_goods":0,
                    "cost_freight":0,
                    "pmt_total":"0",
                    "pmt_alert":
                        [
                            {
                                "pmt_type":"amount",
                                "solution":
                                    {
                                        "title":"满300元送优选海南甜脆小番茄",
                                        "tag":"促"
                                    }
                            }
                        ]
            },
		"cartcount":1
    }
```
 	* wap/web返回结构
```json
    {
        "code":200,
        "msg":"更新成功",
        "data":
            {
                "cart":
                    {
                        "items":
                            {
                                "normal_3687":
                                    {
                                        "sku_id":"3687",
                                        "product_id":"2623",
                                        "qty":1,
                                        "product_no":"201410550",
                                        "item_type":"normal",
                                        "name":"奇异果",
                                        "unit":"盒",
                                        "spec":"33个装",
										"photo": {
                                        "huge": "http://cdn.fruitday.com/product_pic/3035/1/1-1000x1000-3035-TH17W9UA.jpg",
                                        "big": "http://cdn.fruitday.com/product_pic/3035/1/1-370x370-3035-TH17W9UA.jpg",
                                        "middle": "http://cdn.fruitday.com/product_pic/3035/1/1-270x270-3035-TH17W9UA.jpg",
                                        "small": "http://cdn.fruitday.com/product_pic/3035/1/1-180x180-3035-TH17W9UA.jpg",
                                        "thum": "http://cdn.fruitday.com/product_pic/3035/1/1-100x100-3035-TH17W9UA.jpg"
                                    		},
                                        "weight":"0.00",
                                        "price":"218",
                                        "sale_price":"218",
                                        "pmt_price":0,
                                        "amount":218,
                                        "status":"active",
                                        "pmt_price_total":0,
                                        "goods_cost":"218"
                                    }
                            },
                            "total_amount":"218",
                            "goods_amount":"218",
                            "goods_cost":"218",
                            "pmt_goods":0,
                            "cost_freight":0,
                            "pmt_total":"0",
                            "pmt_alert":
                                [
                                    {
                                        "pmt_type":"amount",
                                        "solution":
                                            {
                                                "title":"满300元送优选海南甜脆小番茄",
                                                "tag":"促"
                                            }
                                    }
                                ]
                    },
                    "cart_items":
                        {
                            "normal_3687":
                                {
                                    "sku_id":"3687",
                                    "product_id":"2623",
                                    "qty":1,
                                    "product_no":null,
                                    "item_type":"normal"
                                }
                        }
            }
    }
```
 - 失败
```json
	{
		"code":300,
		"msg":"获取购物车失败"
	}
```


###<a name="cart.remove"/>删除购物车 `cart.remove`


* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       N      |  登录标识  |7b5982f5747672d329d98352e2556f39  |
| region_id    |    int  |       Y      |  地区id  |  1  |
| carttmp    |    string  |       N      |  未登录时添加的购物车结构(见[cart_items](#cart_items))  |  cart_items  |
| item    |    string  |       Y      |  购物车入参结构(见item)  |   item  |

    item结构
	{
		"ik":"normal_3687" //购物车ITEM的KEY值
	    "type":"normal" //普通商品类型
	}
* 接口返回
 - 成功
	* app返回结构
```json
	{
        "cart":
            {
                "items":
                    {
                        "normal_3687":
                            {
                                "sku_id":"3687",
                                "product_id":"2623",
                                "qty":1,
                                "product_no":"201410550",
                                "item_type":"normal",
                                "name":"奇异果",
                                "unit":"盒",
                                "spec":"33个装",
								"photo": {
                                        "huge": "http://cdn.fruitday.com/product_pic/3035/1/1-1000x1000-3035-TH17W9UA.jpg",
                                        "big": "http://cdn.fruitday.com/product_pic/3035/1/1-370x370-3035-TH17W9UA.jpg",
                                        "middle": "http://cdn.fruitday.com/product_pic/3035/1/1-270x270-3035-TH17W9UA.jpg",
                                        "small": "http://cdn.fruitday.com/product_pic/3035/1/1-180x180-3035-TH17W9UA.jpg",
                                        "thum": "http://cdn.fruitday.com/product_pic/3035/1/1-100x100-3035-TH17W9UA.jpg"
                                },
                                "weight":"0.00",
                                "price":"218",
                                "sale_price":"218",
                                "pmt_price":0,
                                "amount":218,
                                "status":"active",
                                "pmt_price_total":0,
                                "goods_cost":"218"
                            }
                    },
                    "total_amount":"218",
                    "goods_amount":"218",
                    "goods_cost":"218",
                    "pmt_goods":0,
                    "cost_freight":0,
                    "pmt_total":"0",
                    "pmt_alert":
                        [
                            {
                                "pmt_type":"amount",
                                "solution":
                                    {
                                        "title":"满300元送优选海南甜脆小番茄",
                                        "tag":"促"
                                    }
                            }
                        ]
            },
		"cartcount":1
    }
```
	* wap/web返回结构
```json
    {
        "code":200,
        "msg":"操作成功",
        "data":
            {
                "cart":
                    {
                        "items":
                            {
                                "normal_3687":
                                    {
                                        "sku_id":"3687",
                                        "product_id":"2623",
                                        "qty":1,
                                        "product_no":"201410550",
                                        "item_type":"normal",
                                        "name":"奇异果",
                                        "unit":"盒",
                                        "spec":"33个装",
	                                    "photo": {
	                                        "huge": "http://cdn.fruitday.com/product_pic/3035/1/1-1000x1000-3035-TH17W9UA.jpg",
	                                        "big": "http://cdn.fruitday.com/product_pic/3035/1/1-370x370-3035-TH17W9UA.jpg",
	                                        "middle": "http://cdn.fruitday.com/product_pic/3035/1/1-270x270-3035-TH17W9UA.jpg",
	                                        "small": "http://cdn.fruitday.com/product_pic/3035/1/1-180x180-3035-TH17W9UA.jpg",
	                                        "thum": "http://cdn.fruitday.com/product_pic/3035/1/1-100x100-3035-TH17W9UA.jpg"
	                                    },
                                        "weight":"0.00",
                                        "price":"218",
                                        "sale_price":"218",
                                        "pmt_price":0,
                                        "amount":218,
                                        "status":"active",
                                        "pmt_price_total":0,
                                        "goods_cost":"218"
                                    }
                            },
                            "total_amount":"218",
                            "goods_amount":"218",
                            "goods_cost":"218",
                            "pmt_goods":0,
                            "cost_freight":0,
                            "pmt_total":"0",
                            "pmt_alert":
                                [
                                    {
                                        "pmt_type":"amount",
                                        "solution":
                                            {
                                                "title":"满300元送优选海南甜脆小番茄",
                                                "tag":"促"
                                            }
                                    }
                                ]
                    },
                    "cart_items":
                        {
                            "normal_3687":
                                {
                                    "sku_id":"3687",
                                    "product_id":"2623",
                                    "qty":1,
                                    "product_no":null,
                                    "item_type":"normal"
                                }
                        }
					"cartcount":1
            }
    }
```
 - 失败
```json
	{
		"code":300,
		"msg":"删除购物车失败"
	}
```



###<a name="cart.clear"/>清空购物车 `cart.clear`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识  |  7b5982f5747672d329d98352e2556f39  |



* 接口返回
 - 成功
```json
    {
        "code":200,
        "msg":"清空成功"
    }
```
 - 失败
```json
    {
        "code":300,
        "msg":"清空失败"
    }
```


###<a name="cart.selpmt"/>优惠提醒 `cart.selpmt`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       N      |  登录标识  |7b5982f5747672d329d98352e2556f39  |
| carttmp    |    string  |       N      |  未登录时添加的购物车结构(见[cart_items](#cart_items))  |  cart_items  |
| pmt_type    |    int  |       Y      |  优惠类型,可选(singleskuexch,dapeigou)  |   singleskuexch  |
| pmt_id 	|    int  |       Y      |  优惠id  |   1  |

* 接口返回
	- 成功
		* app返回结构
```json
	{
        "pmt_type": "singleskuexch",
        "solution": {
            "pmt_type": "exch",
            "products": [
                {
                    "pmt_id": "30",
                    "product_name": "苹果梨",
                    "sale_price": "1",
                    "photo": {
                        "huge": "http://cdn.fruitday.com/product_pic/3035/1/1-1000x1000-3035-TH17W9UA.jpg",
                        "big": "http://cdn.fruitday.com/product_pic/3035/1/1-370x370-3035-TH17W9UA.jpg",
                        "middle": "http://cdn.fruitday.com/product_pic/3035/1/1-270x270-3035-TH17W9UA.jpg",
                        "small": "http://cdn.fruitday.com/product_pic/3035/1/1-180x180-3035-TH17W9UA.jpg",
                        "thum": "http://cdn.fruitday.com/product_pic/3035/1/1-100x100-3035-TH17W9UA.jpg"
                    },
                    "unit": "盒",
                    "spec": "5斤装",
                    "product_price_id": "4422",
                    "product_no": "20149460",
                    "price": "78",
                    "product_id": "3035"
                },
                {
                    "pmt_id": "30",
                    "product_name": "智利樱桃",
                    "sale_price": "1",
                    "photo": {
                        "huge": "http://cdn.fruitday.com/product_pic/1111/1/1-1000x1000-1111-YPY2AS1C.jpg",
                        "big": "http://cdn.fruitday.com/product_pic/1111/1/1-370x370-1111-YPY2AS1C.jpg",
                        "middle": "http://cdn.fruitday.com/product_pic/1111/1/1-270x270-1111-YPY2AS1C.jpg",
                        "small": "http://cdn.fruitday.com/product_pic/1111/1/1-180x180-1111-YPY2AS1C.jpg",
                        "thum": "http://cdn.fruitday.com/product_pic/1111/1/1-100x100-1111-YPY2AS1C.jpg"
                    },
                    "unit": "盒",
                    "spec": "2斤装",
                    "product_price_id": "4986",
                    "product_no": "201411695",
                    "price": "118",
                    "product_id": "3309"
                }
            ]
        }
	}
```


###<a name="cart_v1.get"/>获取购物车 `cart_v1.get`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |---------- |
| connect_id   |    string  |       N      |  登录标识   | 7b5982f5747672d329d98352e2556f39  |
| device_id    |    string  |       Y      |  设备标识   | EBAA3DD9-8E3C-40BE-98DE-76D4AD8ED279  |
| region_id    |    int     |       Y      |  地区id    |  106092   |
| source       |    int     |       Y      |  终端类型    |  app  |

* 接口返回
购物车全集

###<a name="cart_v1.add"/>加入购物车 `cart_v1.add`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |  ---------- |
| connect_id   |    string  |       N      |  登录标识   | 7b5982f5747672d329d98352e2556f39  |
| device_id    |    string  |       Y      |  设备标识   | EBAA3DD9-8E3C-40BE-98DE-76D4AD8ED279  |
| items    |    string  |       Y      | items  |   items  |

	items结构:
	    [
	        {
	            "sku_id":"3221" //规格商品ID
	            "product_id":"4840" //主商品ID
                "product_no":"20149460" //货号
                "product_name":"火龙果" //商品名称
	            "qty":"1" //数量
	            "type":"normal" //普通商品类型
	        },
	        {
	            "sku_id":"3221" //规格商品ID
	            "product_id":"4840" //主商品ID
	            "qty":"1" //数量
	            "product_no":"20149460" //货号
                "product_name":"火龙果" //商品名称
	            "type":"exch" //换购商品类型
	            "pmt_id":"30" //换购的优惠ID
	        },
	        {
	            "sku_id":"3221" //规格商品ID
	            "product_id":"4840" //主商品ID
	            "qty":"1" //数量
	            "product_no":"20149460" //货号
                "product_name":"火龙果" //商品名称
                "gift_send_id":28,
                "gift_active_type":1,
	            "type":"user_gift" //赠品领取类型,
	        }
	    ]

###<a name="cart_v1.update"/>更新购物车 `cart_v1.update`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |  ---------- |
| connect_id   |    string  |       N      |  登录标识   | 7b5982f5747672d329d98352e2556f39  |
| device_id    |    string  |       Y      |  设备标识   | EBAA3DD9-8E3C-40BE-98DE-76D4AD8ED279  |
| region_id    |    int     |       Y      |  地区id    |  106092   |
| source       |    int     |       Y      |  终端类型    |  app  |
| items       |    string     |       Y      |  items    | {sku_id,item_type}  |

返回全量购物车

###<a name="cart_v1.remove"/>删除购物车 `cart_v1.remove`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |  ---------- |
| connect_id   |    string  |       N      |  登录标识   | 7b5982f5747672d329d98352e2556f39  |
| device_id    |    string  |       Y      |  设备标识   | EBAA3DD9-8E3C-40BE-98DE-76D4AD8ED279  |
| region_id    |    int     |       Y      |  地区id    |  106092   |
| source       |    int     |       Y      |  终端类型    |  app  |
| clear       |    int     |       N      |  清空购物车    |  清空购物车  |
| items       |    string     |       Y      |  items    | {sku_id,item_type}  |

返回全量购物车

###<a name="cart_v1.clear"/>清空购物车 `cart_v1.clear`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |  ---------- |
| connect_id   |    string  |       N      |  登录标识   | 7b5982f5747672d329d98352e2556f39  |
| device_id    |    string  |       Y      |  设备标识   | EBAA3DD9-8E3C-40BE-98DE-76D4AD8ED279  |

返回简略购物车

###<a name="cart_v1.select"/>勾选购物车 `cart_v1.select`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |  ---------- |
| connect_id   |    string  |       N      |  登录标识   | 7b5982f5747672d329d98352e2556f39  |
| device_id    |    string  |       Y      |  设备标识   | EBAA3DD9-8E3C-40BE-98DE-76D4AD8ED279  |
| region_id    |    int     |       Y      |  地区id    |  106092   |
| source       |    int     |       Y      |  终端类型    |  app  |
| items       |    string     |       Y      |  items    | [sku_id, ...]  |

返回全量购物车

###<a name="cart_v1.pmt"/>获取优惠详情 `cart_v1.pmt`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |  ---------- |
| connect_id   |    string  |       N      |  登录标识   | 7b5982f5747672d329d98352e2556f39  |
| device_id    |    string  |       Y      |  设备标识   | EBAA3DD9-8E3C-40BE-98DE-76D4AD8ED279  |
| region_id    |    int     |       Y      |  地区id    |  106092   |
| source       |    int     |       Y      |  终端类型    |  app  |
| pmt_id       |    string     |       Y      |  优惠id    | 22  |
| pmt_type       |    string     |       Y      |  优惠类型    | amount  |

返回去凑单/换购列表 

###<a name="cart_v1.mark"/>关注/收藏 `cart_v1.mark`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |  ---------- |
| connect_id   |    string  |       N      |  登录标识   | 7b5982f5747672d329d98352e2556f39  |
| device_id    |    string  |       Y      |  设备标识   | EBAA3DD9-8E3C-40BE-98DE-76D4AD8ED279  |
| items       |    string     |       Y      |  items    | [product_id, ...]  |

返回关注成功 


###<a name="b2ccart.getPmtInfo"/>获取购物车优惠信息 `b2ccart.getPmtInfo`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识  |7b5982f5747672d329d98352e2556f39  |
| region_id    |    string  |       Y      |  地区id  |  cart_items  |
| pmt_id 	|    int  |       Y      |  优惠id  |   1  |

* 接口返回
	- 成功
		* app返回结构
    ```json
        {
            "code": 200,
            "data": {
                "active": "true",
                "solution": {
                    "type": "exchange",
                    "reduce_money": "",
                    "add_money": "100",
                    "mutex": "false",
                    "product_id": "6107",
                    "product_num": "",
                    "dep_product_id": "",
                    "strategy": "",
                    "low_amount": "",
                    "is_send_more": ""
                },
                "product": {
                    "all": "true"
                },
                "type": "amount",
                "platform": "[\"b2c\"]",
                "province": "[\"106092\"]",
                "updated": "1458185707",
                "condition": {
                    "min": "100",
                    "max": "9999",
                    "combo": "one",
                    "combo_num": "",
                    "repeat": "false",
                    "alert": "true",
                    "can_card": "false"
                },
                "first": "false",
                "id": "0118-ee20",
                "start": "1453014410",
                "remarks": "满100换天水金星苹果",
                "end": "1895956200",
                "member": "[\"1\",\"2\",\"3\",\"4\",\"5\",\"6\"]",
                "created": "1453100810",
                "channel": "[\"pc\",\"wap\",\"app\"]"
            },
            "msg": ""
        }
    ```
    - 失败
    ```json
        {
            "code":300,
            "msg":"操作失败"
        }
    ```

		* wap/web返回结构
```json
	{
	    "code": 200,
	    "data": {
	        "pmt_type": "singleskuexch",
	        "solution": {
	            "pmt_type": "exch",
	            "products": [
	                {
	                    "pmt_id": "30",
	                    "product_name": "苹果梨",
	                    "sale_price": "1",
	                    "photo": {
	                        "huge": "http://cdn.fruitday.com/product_pic/3035/1/1-1000x1000-3035-TH17W9UA.jpg",
	                        "big": "http://cdn.fruitday.com/product_pic/3035/1/1-370x370-3035-TH17W9UA.jpg",
	                        "middle": "http://cdn.fruitday.com/product_pic/3035/1/1-270x270-3035-TH17W9UA.jpg",
	                        "small": "http://cdn.fruitday.com/product_pic/3035/1/1-180x180-3035-TH17W9UA.jpg",
	                        "thum": "http://cdn.fruitday.com/product_pic/3035/1/1-100x100-3035-TH17W9UA.jpg"
	                    },
	                    "unit": "盒",
	                    "spec": "5斤装",
	                    "product_price_id": "4422",
	                    "product_no": "20149460",
	                    "price": "78",
	                    "product_id": "3035"
	                },
	                {
	                    "pmt_id": "30",
	                    "product_name": "智利樱桃",
	                    "sale_price": "1",
	                    "photo": {
	                        "huge": "http://cdn.fruitday.com/product_pic/1111/1/1-1000x1000-1111-YPY2AS1C.jpg",
	                        "big": "http://cdn.fruitday.com/product_pic/1111/1/1-370x370-1111-YPY2AS1C.jpg",
	                        "middle": "http://cdn.fruitday.com/product_pic/1111/1/1-270x270-1111-YPY2AS1C.jpg",
	                        "small": "http://cdn.fruitday.com/product_pic/1111/1/1-180x180-1111-YPY2AS1C.jpg",
	                        "thum": "http://cdn.fruitday.com/product_pic/1111/1/1-100x100-1111-YPY2AS1C.jpg"
	                    },
	                    "unit": "盒",
	                    "spec": "2斤装",
	                    "product_price_id": "4986",
	                    "product_no": "201411695",
	                    "price": "118",
	                    "product_id": "3309"
	                }
	            ]
	        }
	    },
	    "msg": ""
	}
```
	- 失败
```json
    {
        "code":300,
        "msg":"操作失败"
    }
```

##<a name="order"/>订单
###<a name="order.orderInit"/>订单初始化(进入订单结算页，首先调用该接口) `order.orderInit`

* 业务入参:

|     参数名    |     类型      |      必须     |     说明     |    示例   |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识  |7b5982f5747672d329d98352e2556f39  |
| region_id    |    int  |       Y      |  地区id  |106092  |


* 接口返回:
	- 成功
```json
    {
        "order_address":{                               #配送地区
            "id":"136",                                 #地址id
            "name":"张三",                              #收获人姓名
            "province":{                                #省
                "id":"106092",                          #省id
                "name":"上海"                            #省名
            },
            "city":{                                    #市
                "id":"106093",                          #市id
                "name":"上海市"                          #市名
            },
            "area":{                                    #区
                "id":"106095",                          #区id
                "name":"浦东新区"                        #区名
            },
            "address":"xxx路20号",                      #地址
            "telephone":"",                            #电话(冗余字段)
            "mobile":"13671981025",                     #手机
            "flag":"公司",                              #收货地址标记
            "isDefault":"0"                             #是否默认地址
        },
        "shtime":{                                      #收货日期
            "20150202":"02-02|周二"
        },
        "stime":{                                       #收货时间
            "0918":"09:00-18:00"
        },
        "address_id":"136",                             #地址id
        "order_id":133,                                 #订单id
        "uid":"17",                                     #购买人id
        "pay_parent_id":"1",                            #支付父id
        "pay_id":"0",                                   #支付id
        "pay_name":"支付宝",                             #支付方式名称
        "has_invoice":1,                                #是否有发票
        "use_card":null,                                #是否使用优惠券
        "use_jf":"0",                                   #是否使用积分
        "pay_discount_money":0,                         #支付折扣金额
        "is_enterprise":"0",                            #是否是企业订单
        "send_times":[{                                 #配送时间结构
            "date_key":20150202,
            "date_value":"02-02|周二",
            "time":[{
                "time_key":"0918",
                "time_value":"09:00-18:00",
                "disable":"true"
            }]
        }],
        "payments":{                                    #支付方式结构
            "online":{
                "name":"\u5728\u7ebf\u652f\u4ed8",
                "pays":[{
                    "pay_parent_id":1,
                    "pay_id":0,
                    "pay_name":"\u652f\u4ed8\u5b9d\u4ed8\u6b3e",
                    "has_invoice":1,
                    "icon":"http:\/\/cdn.fruitday.com\/assets\/images\/bank\/app\/1_0.png"
                }]}
        },
        "cart_info":[],                                #购物车结构
        "user_money":"1107",                           #帐户余额
        "user_coupon_num":2,                           #优惠券数量
        "user_mobile":"13671981025",                   #购买人手机
        "order_money":"0",                             #订单金额
        "method_money":"0",                            #运费
        "order_limit":"60",                            #订单起送金额
        "jf_money":"0",                                #积分抵扣金额
        "card_money":"0",                              #优惠券抵扣金额
        "pay_discount":"0",                            #支付抵扣金额
        "order_jf_limit":"0"                           #积分限制使用金额
        "need_send_code":"0"                           #是否需要验证码验证 0-不需要 1-需要
    }
```

###<a name="order.getAddrList"/>获取收获地址 `order.getAddrList`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ | ----------|
| connect_id    |    string  |       Y      |  登录标识  |7b5982f5747672d329d98352e2556f39|
| use_case    |    string  |       N      |  场景标识(在订单里传order)  | order |
| region_id    |    int  |       Y      |  地区id  |106092  |
* 接口返回
```json
    [
        {
            "id":"62806",          #user_address_id
            "name":"陆盛超",        #收货人
            "province":            #省
            {
                id":"106092",
                "name":"上海"
            },
            "city":                #市
            {
                id":"106093",
                "name":"上海市"
            },
            "area":                #区
            {
                id":"106098",
                "name":"静安区"
            },
            "address":"康桥路797号",#地址
            "telephone":"",        #电话
            "mobile":"13671981025" #手机
            "flag":"公司"          #地址标注
            "isDefault":"0"       #是否是默认地址，0:不是，1:是
        }
    ]
```


###<a name="order.addAddr"/>添加收货地址 `order.addAddr`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ | ---------|
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| name          |    string  |       Y      |  收货人         |张三|
| province          |    int  |       Y      |  省         |111|
| city          |    int  |       Y      |  市         |222|
| area          |    int  |       Y      |  区         |333|
| address          |    string  |       Y      |  地址         |xxx路x号|
| telephone          |    int  |       N      |  电话         |55555555|
| mobile          |    int  |       Y      |  手机         |1361616161|
| flag          |    string  |       N      |  地址标注         |公司|
| default          |    int  |       N      |  是否是默认地址         |0|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
```json
    {
        "uid":"1",
        "name":"陆盛超",
        "province":"106092",
        "city":"106093",
        "area":"106098",
        "address":"康桥路797号",
        "telephone":"58999222",
        "mobile":"13671981025",
        "address_id":173432      #插入的address_id
        "flag":"公司"           #地址标注
    }
```



###<a name="order.updateAddr"/>修改收货地址 `order.updateAddr`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ | ---------|
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| address_id          |    int  |       Y      |  地址id         |1|
| name          |    string  |       Y      |  收货人         |张三|
| province          |    int  |       Y      |  省         |111|
| city          |    int  |       Y      |  市         |222|
| area          |    int  |       Y      |  区         |333|
| address          |    string  |       Y      |  地址         |xxx路x号|
| telephone          |    int  |       N      |  电话         |55555555|
| mobile          |    int  |       Y      |  手机         |1361616161|
| flag          |    string  |       N      |  地址标注         |公司|
| default          |    int  |       N      |  是否是默认地址         |0|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
```json
    {
        "uid":"1",
        "name":"陆盛超",
        "province":"106092",
        "city":"106093",
        "area":"106098",
        "address":"康桥路797号",
        "telephone":"58999222",
        "mobile":"13671981025",
        "address_id":173432      #插入的address_id
        "flag":"公司"           #地址标注
    }
```

###<a name="order.deleteAddr"/>删除收获地址 `order.deleteAddr`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |   示例    |
| ------------ | ------------- | ------------ | ------------ |---------|
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| address_id    |    int  |       Y      |  地址id         |1|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
```json
    {
        "code":"200",
        "msg":"succ",
    }
```



###<a name="order.choseAddr"/>选择收货地址 `order.choseAddr`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |   示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| address_id    |    int  |       Y      |  地址id         |1|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
 - 成功
```json
    {
        "code":"200",
        "msg":"succ",
    }
```
 - 失败
```json
    {
        "code":"300",
        "msg":"您购买的新西兰奇异果暂时无法配送到你选择的收货地址"
    }
```

###<a name="order.getAddr"/>查询选择的收货地址 `order.getAddr`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |   示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| address_id    |    int  |       Y      |  地址id         |1|
| region_id    |    int  |       Y      |  地区id  |106092  |
* 接口返回
```json
    {
        "id":"62806",          #user_address_id
        "name":"陆盛超",        #收货人
        "province":            #省
        {
            id":"106092",
            "name":"上海"
        },
        "city":                #市
        {
            id":"106093",
            "name":"上海市"
        },
        "area":                #区
        {
            id":"106098",
            "name":"静安区"
        },
        "address":"康桥路797号",#地址
        "telephone":"",        #电话
        "mobile":"13671981025" #手机
        "flag":"公司"          #地址标注
        "isDefault":"1"        #是否是默认地址
    }
```





###<a name="order.choseSendtime"/>选择配送时间 `order.choseSendtime`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| send_date    |    string  |       Y      |  配送日期(没有具体时间的传"after2to3days")  |20151212或者after2to3days|
| send_time    |    string  |       N      |  配送时间       | 0918或者send_date为"after2to3days"时传(weekday|weekend|all)|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
```json

    {
        "code":"200",
        "msg":"succ",
    }
```





###<a name="order.chosePayment"/>选择支付方式 `order.chosePayment`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |   示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| pay_parent_id    |    int  |       Y      |  支付父id       |1|
| pay_id    |    int  |       Y      |  支付子id    |0|
| region_id    |    int  |       Y      |  地区id  |106092  |
* 接口返回
```json
    {
        "code":"200",
        "pay_discount_money":"10",  #支付方式促销活动减免金额
        "need_send_code":"1"        #是否需要验证码验证 0-不需要 1-需要
    }
```




###<a name="order.checkUserMoney"/>帐户余额支付方式选择验证 `order.checkUserMoney`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| region_id    |    int  |       Y      |  地区id  |106092  |
* 接口返回
```json
    {
        "code":"200",
        "msg":"succ",
    }
```




###<a name="order.usejf"/>积分使用 `order.usejf`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| jf    |    int  |       Y      |  使用积分(元)       |10|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
```json
    {
        "code":"200",
        "msg":"succ"
    }
```


###<a name="order.cancelUsejf"/>取消积分使用 `order.cancelUsejf`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
```json
    {
        "error":"200",
        "msg":"succ"
    }
```



###<a name="order.useCard"/>抵扣码使用 `order.useCard`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| card    |    string  |       Y      |  抵扣码       | 124124|
| region_id    |    int  |       Y      |  地区id  |106092  |
* 接口返回
```json
    {
        "code":"200",
        "msg":"20"
    }
```


###<a name="order.cancelUseCard"/>取消抵扣码使用 `order.cancelUseCard`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
```json
    {
        "code":"200",
        "msg":"succ"
    }
```



###<a name="order.postFree"/>运费计算 `order.postFree`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| region_id    |    int  |       Y      |  地区id  |106092  |
* 接口返回
```json
    {
        "method_money":5,    #运费
        "order_limit":0      #成单的最低金额，如果order_limit大于0则提示用户无法配送，金额必须大于order_limit
    }
```


###<a name="user.getInvoice"/>发票列表 `user.getInvoice`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| ctime    |    string  |       Y      |  类型       | 0:可开具发票(小10元不能开票) 1:已过期 2:已开票|
| page    |    int  |       Y      |  页数默认为1       |1|
| limit    |    int  |       Y      |  每页个数       |10|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
```array (
  'code' => 200,
  'data' =>
  array (
    0 =>
    array (
      'id' => '73947',
      'uid' => '268938',
      'trade_number' => 'T160105006662',
      'out_trade_no' => NULL,
      'payment' => '支付宝',
      'payer_email' => NULL,
      'money' => '500.00',
      'bonus' => '0.00',
      'bonus_point' => NULL,
      'bonus_products' => NULL,
      'bonus_order' => NULL,
      'bonus_score' => '0',
      'has_rec' => '0',
      'bonus_expire_time' => NULL,
      'invoice' => '1391',
      'card_number' => NULL,
      'trade_no' => NULL,
      'status' => '等待支付',
      'msg' => NULL,
      'type' => 'income',
      'time' => '2016-04-29 16:00:45',
      'post_at' => '2016-04-15 16:00:45',
      'has_deal' => '1',
      'region' => '1',
      'order_name' => '',
    ),
  ),
```

###<a name="order.useInvoice"/>索取发票 `order.useInvoice`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| fp    |    string  |       Y      |  发票抬头       | 个人|
| fp_dz    |    string  |       Y      |  发票寄送地址       |  xxx路|
| invoice_username    |    string  |       Y      |  发票收件人       |张三|
| invoice_mobile    |    string  |       Y      |  发票收件人手机       |13666666666|
| invoice_province    |    string  |       Y      |  发票收件省id       |1|
| invoice_city    |    string  |       Y      |  发票收件市id       |2|
| invoice_area    |    string  |       Y      |  发票收件区id       |3|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
```json
    {
        "code":"200",
        "msg":"succ"
    }
```

###<a name="order.useTradeInvoice"/>索取充值发票 `order.useTradeInvoice`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| fp    |    string  |       Y      |  发票抬头       | 个人|
| fp_dz    |    string  |       Y      |  发票寄送地址       |  xxx路|
| invoice_username    |    string  |       Y      |  发票收件人       |张三|
| invoice_mobile    |    string  |       Y      |  发票收件人手机       |13666666666|
| invoice_province    |    string  |       Y      |  发票收件省id       |1|
| invoice_city    |    string  |       Y      |  发票收件市id       |2|
| invoice_area    |    string  |       Y      |  发票收件区id       |3|
| region_id    |    int  |       Y      |  地区id  |106092  |
| trade_number    |    string  |       Y      |  充值订单号(可多选)  | T151217593950,T151217593951  |

* 接口返回
```json
    {
        "code":"200",
        "msg":"succ"
    }
```

###<a name="order.cancelInvoice"/>取消索取发票 `order.cancelInvoice`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
```json
    {
        "code":"200",
        "msg":"succ"
    }
```

###<a name="order.invoiceInfo"/>发票信息 `order.invoiceInfo`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| region_id    |    int  |       Y      |  地区id  |106092  |

* 接口返回
```json
    {
        "invoice_type":"1",             #1:个人;2:公司
        "invoice_username":"个人"        #抬头
        "invoice_address_type":"1"      #1:采用收货地址;2:另配送
        "invoice_address":"上海市浦东新区康桥路888号"  #发票收货地址
        "invoice_mobile":"13671981025"            #联系人手机
        "invoice_name":"张三"                      #姓名
        "invoice_province_key":"106092"           #地区id
        "invoice_province":"上海"                  #地区名称
        "invoice_city_key":"106093"
        "invoice_city":"上海市"
        "invoice_area_key":"106094"
        "invoice_area":"浦东新区"
    }
```

###<a name="order.invoiceTitleList"/>发票抬头信息 `order.invoiceTitleList`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|

* 接口返回
```json
    {
      "code":"200",
      "msg":"succ",
      "data":
      [
        "a",
        "b"
      ]
    }
```

###<a name="order.createOrder"/>订单创建 `order.createOrder`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |  示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| msg    |    string  |       Y      |  订单留言       | 啦啦啊|
| hk    |    string  |       Y      |  贺卡内容       |喀拉拉
| device_code    |    string  |       Y      |  设备号       |21313|
| verification_code    |    string  |       Y      |  验证码       |231
| ver_code_connect_id    |    string  |       Y      |  user.sendVerCode获取的connect_id       |7b5982f5747672d329d98352e2556f39|
| region_id    |    int  |       Y      |  地区id  |106092  |
* 接口返回
```json
    {
        "code":"200",
        "msg":"succ"
    }
```


###<a name="order.orderList"/>用户订单查询 `order.orderList`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |   示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| page    |    int  |       Y      |  页数默认为1       |1|
| limit    |    int  |       Y      |  每页个数       |10|
| order_status    |    int  |       Y      |  订单状态可选值(0:所有订单、1:未完成、2:已完成、3:已取消、4:已完成＋已收货  |0|


* 接口返回
 - 成功
```json
	{
	    "list": [
	        {
	            "id": "5149",
	            "uid": "281873",
	            "order_name": "201101280135173094",
	            "time": "2011-01-28 13:35:17",
	            "pay_name": "线下支付 - 货到付现金",
	            "shtime": "29/01/2011",
	            "money": "166.00",
	            "pay_status": "还未付款",
	            "pay_parent_id": "4",
	            "had_comment": "0",
	            "has_bask": "0",
	            "order_status": "未完成",
	            "can_comment": "false",
	            "can_confirm_receive": "false",
	            "can_pay": "false",
	            "can_cancel": "false",
	            "item": [
	                {
	                    "thum_photo": "http://cdn.fruitday.com/product_pic/2011-03-23/thum/2011-03-23-14-41-56-01.jpg",
	                    "order_product_id": "9472",
	                    "product_name": "DOLE帝皇香蕉",
	                    "product_id": "143",
	                    "gg_name": "2.5KG大盒装　68元/盒",
	                    "price": "68",
	                    "qty": "1",
	                    "order_product_type": "1",
	                    "product_no": null,
	                    "order_id": "5149"
	                },
	                {
	                    "thum_photo": "http://cdn.fruitday.com/product_pic/2010-10-31/thum/2010-10-31-10-15-38-01.jpg",
	                    "order_product_id": "9473",
	                    "product_name": "欢乐周末水果套餐",
	                    "product_id": "155",
	                    "gg_name": "中盒装　118元/盒",
	                    "price": "118",
	                    "qty": "1",
	                    "order_product_type": "1",
	                    "product_no": null,
	                    "order_id": "5149"
	                }
	            ]
	        }
	    ]
	}
```

  - 无数时
```json
	{
	    "list": []
	}
```


###<a name="order.orderInfo"/>订单详情查询 `order.orderInfo`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |    示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| order_name    |    string  |       Y      |  订单号       |131128003558|


* 接口返回
- 成功
  * app返回结构
```json
 {
    "id": "1300309924",
    "pay_parent_id": "1",
    "pay_id": "0",
    "uid": "281873",
    "order_name": "141113231396",
    "time": "2015-03-02 00:00:00",
    "pay_name": "支付宝付款",
    "shtime": "2015-03-03 09:00-18:00",
    "stime": "0918",
    "money": "785.00",
    "pay_status": "已经付款",
    "operation_id": "3",
    "fp": "",
    "goods_money": "785.00",
    "jf_money": "0",
    "card_money": "0.00",
    "had_comment": "1",
    "has_bask": "1",
    "address": "上海上海市浦东新区（外环线以外，郊环线以内）康衫路488号",
    "name": "test",
    "telephone": "",
    "mobile": "18721338269",
    "order_status": "已完成",
    "pay_status_key": "1",
    "can_comment": "false",
    "can_confirm_receive": "false",
    "can_pay": "false",
    "can_cancel": "false",
    "pay_discount_money": 0,
    "mail_money": "0",
    "score_desc":"发表评论审核通过增加10积分，附加晒单图片再送10积分",
    "item": [
        {
            "thum_photo": "http://cdn.fruitday.com/product_pic/1428/1/1-180x180-1428.jpg",
            "order_product_id": "1178910",
            "product_name": "越南红心火龙果",
            "product_id": "1428",
            "gg_name": "5斤装/盒",
            "price": "99",
            "qty": "1",
            "product_no": "20149135",
            "order_id": "1300309924",
            "product_type": 1,
            "can_comment": "false",
            "can_report_issue": "true",
            "share_url": "http://m.fruitday.com/detail/index/1428"
        },
        {
            "thum_photo": "http://cdn.fruitday.com/product_pic/346/1/1-180x180-346.jpg",
            "order_product_id": "1178911",
            "product_name": "智利西梅买2斤送2斤",
            "product_id": "346",
            "gg_name": "2斤装/盒",
            "price": "98",
            "qty": "7",
            "product_no": "31653 ",
            "order_id": "1300309924",
            "product_type": 1,
            "can_comment": "false",
            "can_report_issue": "true",
            "share_url": "http://m.fruitday.com/detail/index/346"
        },
        {
            "thum_photo": "",
            "order_product_id": "1178912",
            "product_name": "智利西梅2斤",
            "product_id": "0",
            "gg_name": "件",
            "price": "0",
            "qty": "7",
            "product_no": "31653",
            "order_id": "1300309924",
            "product_type": 3,
            "can_comment": "false",
            "can_report_issue": "true"
        }
    ]
}
```
  * wap/web返回结构
  ```json
  {
    "id": "1300309924",
    "pay_parent_id": "1",
    "pay_id": "0",
    "uid": "281873",
    "order_name": "141113231396",
    "time": "2015-03-02 00:00:00",
    "pay_name": "支付宝付款",
    "shtime": "2015-03-03 09:00-18:00",
    "stime": "0918",
    "money": "785.00",
    "pay_status": "已经付款",
    "operation_id": "3",
    "fp": "",
    "goods_money": "785.00",
    "jf_money": "0",
    "card_money": "0.00",
    "had_comment": "1",
    "has_bask": "1",
    "address": "上海上海市浦东新区（外环线以外，郊环线以内）康衫路488号",
    "name": "test",
    "telephone": "",
    "mobile": "18721338269",
    "order_status": "已完成",
    "pay_status_key": "1",
    "can_comment": "false",
    "can_confirm_receive": "false",
    "can_pay": "false",
    "can_cancel": "false",
    "item": [
        {
            "thum_photo": "http://cdn.fruitday.com/product_pic/1428/1/1-180x180-1428.jpg",
            "order_product_id": "1178910",
            "product_name": "越南红心火龙果",
            "product_id": "1428",
            "gg_name": "5斤装/盒",
            "price": "99",
            "qty": "1",
            "product_no": "20149135",
            "order_id": "1300309924",
            "product_type": 1
        },
        {
            "thum_photo": "http://cdn.fruitday.com/product_pic/346/1/1-180x180-346.jpg",
            "order_product_id": "1178911",
            "product_name": "智利西梅买2斤送2斤",
            "product_id": "346",
            "gg_name": "2斤装/盒",
            "price": "98",
            "qty": "7",
            "product_no": "31653 ",
            "order_id": "1300309924",
            "product_type": 1
        },
        {
            "thum_photo": "",
            "order_product_id": "1178912",
            "product_name": "智利西梅2斤",
            "product_id": "0",
            "gg_name": "件",
            "price": "0",
            "qty": "7",
            "product_no": "31653",
            "order_id": "1300309924",
            "product_type": 3
        }
    ],
    "pay_discount_money": 0,
    "mail_money": "0"
}
```


###<a name="order.orderCancel"/>订单取消 `order.orderCancel`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| order_name    |    string  |       Y      |  订单号       |131128003558|

* 接口返回
```json
    {
        "code":"200",
        "msg":"succ"
    }
```



###<a name="order.confirmReceive"/>订单确认收货 `order.confirmReceive`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| order_name    |    string  |       Y      |  订单号       |131128003558|

* 接口返回
```json
    {
        "code":"200",
        "msg":"succ"
    }
```


###<a name="order.orderPayed"/>订单支付状态更改 `order.orderPayed`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| order_name    |    string  |       Y      |  订单号       |131128003558|

* 接口返回
```json
    {
        "code":"200",
        "msg":"succ"
    }
```

###<a name="order.appealList"/>订单申诉列表 `order.appealList`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|

* 接口返回
```json
[
    {
        "order_name": "1288962087",
        "order_time": "2010-11-05 21:01:27",
        "product": [
            {
                "id": "8",
                "order_id": "7",
                "product_id": "222",
                "product_no": null,
                "product_name": "清肺润喉套餐I",
                "gg_name": "中盒装　118元/盒",
                "price": "118",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0"
            }
        ]
    },
    {
        "order_name": "1288984723",
        "order_time": "2010-11-06 03:18:43",
        "product": [
            {
                "id": "35",
                "order_id": "31",
                "product_id": "51",
                "product_no": null,
                "product_name": "美国新奇士橙",
                "gg_name": "20个装　108元/盒",
                "price": "108",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0"
            },
            {
                "id": "36",
                "order_id": "31",
                "product_id": "200",
                "product_no": null,
                "product_name": "美国无籽黑提",
                "gg_name": "1Kg装　65元/Kg",
                "price": "65",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0"
            },
            {
                "id": "37",
                "order_id": "31",
                "product_id": "48",
                "product_no": null,
                "product_name": "阿根廷蓝莓",
                "gg_name": "125g（1盒）　45元/盒",
                "price": "45",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0"
            }
        ]
    }
]
```

###<a name="order.doComment"/>订单商品评论 `order.doComment`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| star    |    int  |       Y      |  评论星级       |2|
| order_id    |    int  |       Y      |  订单id       |1300309924|
| product_id    |    int  |       Y      |  商品id       |1432|
| content    |    string  |       Y      |  评论内容(字符大于10个)       |testtesttestetestsestsetsteett|
| photo[1-3]    |    obj  |       Y      |  图片资源(上传1-3张图片:模拟表单提交)       |obj|

* 接口返回
```json
    {
        "code":"200",
        "msg":"评论成功"
    }
```

###<a name="order.commentList"/>订单评论列表 `order.commentList`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| order_name   |    int  |       Y      |  订单id       |1300309924|

* 接口返回
```json
[
    {
        "order_name": "1288962087",
        "order_time": "2010-11-05 21:01:27",
        "product": [
            {
                "id": "8",
                "order_id": "7",
                "product_id": "222",
                "product_no": null,
                "product_name": "清肺润喉套餐I",
                "gg_name": "中盒装　118元/盒",
                "price": "118",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288962087",
                "photo": "http://cdn.fruitday.com/product_pic/2010-11-02/thum/2010-11-02-14-19-00-01.jpg"
            }
        ]
    },
    {
        "order_name": "1288984723",
        "order_time": "2010-11-06 03:18:43",
        "product": [
            {
                "id": "35",
                "order_id": "31",
                "product_id": "51",
                "product_no": null,
                "product_name": "美国新奇士橙",
                "gg_name": "20个装　108元/盒",
                "price": "108",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288984723",
                "photo": "http://cdn.fruitday.com/product_pic/2011-06-20/thum/2011-06-20-18-11-50-01.jpg"
            },
            {
                "id": "36",
                "order_id": "31",
                "product_id": "200",
                "product_no": null,
                "product_name": "美国无籽黑提",
                "gg_name": "1Kg装　65元/Kg",
                "price": "65",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288984723",
                "photo": ""
            },
            {
                "id": "37",
                "order_id": "31",
                "product_id": "48",
                "product_no": null,
                "product_name": "阿根廷蓝莓",
                "gg_name": "125g（1盒）　45元/盒",
                "price": "45",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288984723",
                "photo": "http://cdn.fruitday.com/product_pic/2011-03-11/thum/2011-03-11-13-17-04-01.jpg"
            }
        ]
    },
    {
        "order_name": "1288984754",
        "order_time": "2010-11-06 03:19:14",
        "product": [
            {
                "id": "38",
                "order_id": "32",
                "product_id": "196",
                "product_no": null,
                "product_name": "新西兰有机金奇异果",
                "gg_name": "8个家庭装　75元/盒",
                "price": "75",
                "qty": "2",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288984754",
                "photo": "http://cdn.fruitday.com/product_pic/2012-02-02/thum/2012-02-02-17-32-12-01.jpg"
            },
            {
                "id": "39",
                "order_id": "32",
                "product_id": "223",
                "product_no": null,
                "product_name": "台湾红西柚",
                "gg_name": "<font color=red>限时惠产品</font>",
                "price": "1",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288984754",
                "photo": "http://cdn.fruitday.com/product_pic/2010-11-03/thum/2010-11-03-09-51-47-01.jpg"
            }
        ]
    },
    {
        "order_name": "1288985298",
        "order_time": "2010-11-06 03:28:18",
        "product": [
            {
                "id": "40",
                "order_id": "33",
                "product_id": "65",
                "product_no": null,
                "product_name": "泰国金枕头榴莲",
                "gg_name": "500g　11元/500g",
                "price": "11",
                "qty": "10",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288985298",
                "photo": "http://cdn.fruitday.com/product_pic/2010-09-26/thum/2010-09-26-16-51-21-01.jpg"
            }
        ]
    },
    {
        "order_name": "1288985511",
        "order_time": "2010-11-06 03:31:51",
        "product": [
            {
                "id": "41",
                "order_id": "34",
                "product_id": "223",
                "product_no": null,
                "product_name": "台湾红西柚",
                "gg_name": "<font color=red>限时惠产品</font>",
                "price": "1",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288985511",
                "photo": "http://cdn.fruitday.com/product_pic/2010-11-03/thum/2010-11-03-09-51-47-01.jpg"
            },
            {
                "id": "42",
                "order_id": "34",
                "product_id": "202",
                "product_no": null,
                "product_name": "美国姬娜果",
                "gg_name": "3个家庭装　16元/盒",
                "price": "16",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288985511",
                "photo": "http://cdn.fruitday.com/product_pic/2010-10-31/thum/2010-10-31-16-34-07-01.jpg"
            },
            {
                "id": "43",
                "order_id": "34",
                "product_id": "203",
                "product_no": null,
                "product_name": "韩国闻京梨",
                "gg_name": "2个家庭装　22元/盒",
                "price": "22",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288985511",
                "photo": "http://cdn.fruitday.com/product_pic/203/1/1-180x180-203.jpg"
            },
            {
                "id": "44",
                "order_id": "34",
                "product_id": "204",
                "product_no": null,
                "product_name": "美国脆口红地厘蛇果",
                "gg_name": "2个家庭装　15元/盒",
                "price": "15",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288985511",
                "photo": "http://cdn.fruitday.com/product_pic/2010-10-31/thum/2010-10-31-16-42-04-01.jpg"
            },
            {
                "id": "45",
                "order_id": "34",
                "product_id": "177",
                "product_no": null,
                "product_name": "海南木瓜",
                "gg_name": "6个中盒装　50元/盒",
                "price": "50",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288985511",
                "photo": "http://cdn.fruitday.com/product_pic/177/1/1-180x180-177.jpg"
            }
        ]
    },
    {
        "order_name": "1288986657",
        "order_time": "2010-11-06 03:50:57",
        "product": [
            {
                "id": "48",
                "order_id": "36",
                "product_id": "103",
                "product_no": null,
                "product_name": "新西兰有机金奇异果",
                "gg_name": "25个原包装　168元/盒",
                "price": "168",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288986657",
                "photo": "http://cdn.fruitday.com/product_pic/2012-02-02/thum/2012-02-02-17-31-43-01.jpg"
            },
            {
                "id": "49",
                "order_id": "36",
                "product_id": "223",
                "product_no": null,
                "product_name": "台湾红西柚",
                "gg_name": "<font color=red>限时惠产品</font>",
                "price": "1",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288986657",
                "photo": "http://cdn.fruitday.com/product_pic/2010-11-03/thum/2010-11-03-09-51-47-01.jpg"
            }
        ]
    },
    {
        "order_name": "1288988092",
        "order_time": "2010-11-06 04:14:52",
        "product": [
            {
                "id": "50",
                "order_id": "37",
                "product_id": "223",
                "product_no": null,
                "product_name": "台湾红西柚",
                "gg_name": "<font color=red>限时惠产品</font>",
                "price": "1",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288988092",
                "photo": "http://cdn.fruitday.com/product_pic/2010-11-03/thum/2010-11-03-09-51-47-01.jpg"
            },
            {
                "id": "51",
                "order_id": "37",
                "product_id": "73",
                "product_no": null,
                "product_name": "新西兰佳沛绿奇异果",
                "gg_name": "盒　110元/盒",
                "price": "110",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288988092",
                "photo": "http://cdn.fruitday.com/product_pic/2010-09-26/thum/2010-09-26-16-53-17-01.jpg"
            }
        ]
    },
    {
        "order_name": "1288988210",
        "order_time": "2010-11-06 04:16:50",
        "product": [
            {
                "id": "52",
                "order_id": "38",
                "product_id": "103",
                "product_no": null,
                "product_name": "新西兰有机金奇异果",
                "gg_name": "25个原包装　168元/盒",
                "price": "168",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288988210",
                "photo": "http://cdn.fruitday.com/product_pic/2012-02-02/thum/2012-02-02-17-31-43-01.jpg"
            }
        ]
    },
    {
        "order_name": "1288989222",
        "order_time": "2010-11-06 04:33:42",
        "product": [
            {
                "id": "53",
                "order_id": "39",
                "product_id": "103",
                "product_no": null,
                "product_name": "新西兰有机金奇异果",
                "gg_name": "25个原包装　168元/盒",
                "price": "168",
                "qty": "1",
                "score": null,
                "type": "1",
                "total_money": "0",
                "order_name": "1288989222",
                "photo": "http://cdn.fruitday.com/product_pic/2012-02-02/thum/2012-02-02-17-31-43-01.jpg"
            }
        ]
    },
    {
        "order_name": "140227054296",
        "order_time": "2014-03-09 10:25:56",
        "product": [
            {
                "id": "1092616",
                "order_id": "1300265515",
                "product_id": "1362",
                "product_no": "20149588 ",
                "product_name": "新西兰奇异莓（kiwi berry）",
                "gg_name": "2盒/盒",
                "price": "78",
                "qty": "1",
                "score": "156",
                "type": "1",
                "total_money": "0",
                "order_name": "140227054296",
                "photo": "http://cdn.fruitday.com/product_pic/1362/1/1-180x180-1362.jpg"
            },
            {
                "id": "1092617",
                "order_id": "1300265515",
                "product_id": "1336",
                "product_no": "31433",
                "product_name": "奇异果脐橙套餐",
                "gg_name": "拎盒装/盒",
                "price": "88",
                "qty": "1",
                "score": "176",
                "type": "1",
                "total_money": "0",
                "order_name": "140227054296",
                "photo": "http://cdn.fruitday.com/product_pic/1336/1/1-180x180-1336.jpg"
            },
            {
                "id": "1092618",
                "order_id": "1300265515",
                "product_id": "1731",
                "product_no": "32395",
                "product_name": "葡萄柚甜橙套餐",
                "gg_name": "1盒/盒",
                "price": "78",
                "qty": "2",
                "score": "156",
                "type": "1",
                "total_money": "0",
                "order_name": "140227054296",
                "photo": "http://cdn.fruitday.com/product_pic/1731/1/1-180x180-1731.jpg"
            }
        ]
    }
]
```

###<a name="order.doAppeal"/>订单商品申诉 `order.doAppeal`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| information    |    int  |       Y      |  联系方式       |1872133333|
| description    |    string  |       Y      |  申诉描述       |testest|
| ordername    |    int  |       Y      |  订单号       |141113231396|
| product_id    |    int  |       Y      |   商品id      |1426|
| photo[1-3]    |    obj  |       Y      |  图片资源(上传1-3张图片:模拟表单提交)       |obj|

* 接口返回
```json
    {
        "code":"200",
        "msg":"提交成功"
    }
```

###<a name="order.logisticTrace"/>物流查询 `order.logisticTrace`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| order_name    |    int  |       Y      |  订单号       |141113231396|

* 接口返回
```json
    {
      type:0,1      物流类型 自建或第三方
      driver_name : xxx - 司机姓名 仅自建物流返回
      driver_phone: 131111…. - 司机电话 仅自建物流返回
      logistic_company: 顺风快递 - 第三方物流公司名称 仅第三方物流返回
      logistic_logo : http://….jpg - 第三方物流logo 仅第三方物流返回
      logistic_order  : 111111111 - 物流单号 仅第三方物流返回
      logistic_trace : [数组] - 物流追踪
          {
          trace_desc : 已发货 快递已接收 - 追踪描述
          trace_time : 2015-01-06 11:40:30 - 追踪时间戳
          }
    }
```

###<a name="order.complaintsList"/>商品申诉列表 `order.complaintsList`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| order_name    |    varchar  |       N     |  订单号       |1511132631396|
| status    |    tinyint  |       N     |  申诉状态       |1      //传1表示查询申诉已完成列表，传3表示查询申诉未完成列表
| page    |    int  |       N     |  当前页码       |1
| pagesize    |    int  |       N     | 每页显示列表的数量      |5


* 接口返回
```json
    {
      [
            order_name : 1511132631396    订单号
            time : 2015-09-19             下单时间
            product :{
                    [
                      product_id:4916               商品id
                      product_name :精选百香果      商品名称
                      product_no ：2150721101       商品SKU
                      gg_name : 6个装/个            商品规格
                      price ： 25                   商品价格
                      total_money 25                小计价格
                      id : 66667                    申诉唯一id
                      time ：2015-09-11 17:34:35    申诉时间
                      status ： 0                   申诉状态，0表示未处理，2表示处理中，1表示处理完成
                      statusDes : 未处理            申诉状态中文说明
                      thum_photo ：http://cdn.fruitday.com/images/product_pic/4719/1/1-180x180-4719-9X39BCP8.jpg 商品图片url
                    ],
                    [
                      product_id:4916               商品id
                      product_name :精选百香果      商品名称
                      product_no ：2150721101       商品SKU
                      gg_name : 6个装/个            商品规格
                      price ： 25                   商品价格
                      total_money 25                小计价格
                      id : 66667                    申诉唯一id
                      time ：2015-09-11 17:34:35    申诉时间
                      status ： 0                   申诉状态，0表示未处理，2表示处理中，1表示处理完成
                      statusDes : 未处理            申诉状态中文说明
                      thum_photo ：http://cdn.fruitday.com/images/product_pic/4719/1/1-180x180-4719-9X39BCP8.jpg 商品图片url
                    ]

          }
      ],
      [
            order_name : 1511132631396    订单号
            time : 2015-09-19             下单时间
            product :{
                    [
                      product_id:4916               商品id
                      product_name :精选百香果      商品名称
                      product_no ：2150721101       商品SKU
                      gg_name : 6个装/个            商品规格
                      price ： 25                   商品价格
                      total_money 25                小计价格
                      id : 66667                    申诉唯一id
                      time ：2015-09-11 17:34:35    申诉时间
                      status ： 0                   申诉状态，0表示未处理，2表示处理中，1表示处理完成
                      statusDes : 未处理            申诉状态中文说明
                      thum_photo ：http://cdn.fruitday.com/images/product_pic/4719/1/1-180x180-4719-9X39BCP8.jpg 商品图片url
                    ]
          }
      ]
    }
```

###<a name="order.complaintsDetail"/>商品申诉详情 `order.complaintsDetail`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------  | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string     |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| id            |    int        |       Y      |  申诉号       |1124|


* 接口返回
```json
    {
      qcid : 2347     申诉号
      description ： 烂啦   申诉描述
      photo ： http://cdn.fruitday.com/images/product_pic/4719/1/1-180x180-4719-9X39BCP8.jpg   申诉时上传的图片
      status ： 0 申诉状态   //0表示未处理，2表示处理中，1表示已处理
      statusDes: 未处理      申诉状态中文说明
      log : {
          [
            id : 23 申诉日志表唯一id
            quality_complaints_id ： 24321  申诉号
            process_id ： 41432  crm那边
            time ： 2015-09-12   处理时间
            log ：下次再联系         处理说明
            act_user ： 客服1    处理人
          ],
          [
            id : 23 申诉日志表唯一id
            quality_complaints_id ： 24321  申诉号
            process_id ： 41432  crm那边
            time ： 2015-09-12   处理时间
            log ： 退款          处理说明
            act_user ： 客服1    处理人
          ]
      }

    }
```

###<a name="order.complaintsFeedback"/>申诉处理反馈 `order.complaintsFeedback`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------  | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string     |       Y      |  登录标识       |7b5982f5747672d329d98352e2556f39|
| qcid            |    int       |       Y      |  申诉号       |1124|
| stars        | tinyint           |         Y     |   几颗星      | 2


 * 接口返回
  - 成功
  {
      "code" : 200
      "msg" : "评价成功"
  }



##<a name="cardchange">提货券

###<a name="cardchange.checkExchange">验证提货券，获取提货券商品列表 `cardchange.checkExchange`
 * 业务入参

    | 参数名 | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | card_number | string | Y | 券号 | Xmmb200000008 |
    | card_passwd | string | Y | 密码 | 111111 |

 * 接口返回
  - 成功
    * app返回结构
        ```json
        {
            "code":"200",
            "pro_info":[
                {
                    "price_id":"4213",
                    "product_name":"佳沛新西兰有机绿奇异果",
                    "send_region":"a:6:{i:0;s:6:\"144443\";i:1;s:6:\"144522\";i:2;s:6:\"144551\";i:3;s:6:\"144595\";i:4;s:6:\"144627\";i:5;s:6:\"145843\";}",
                    "summary":"天然、纯净的环境下培育，通过新西兰官方100%有机认证，进口水果中唯一通过国内有机认证标准，产量稀少珍贵，营养全面丰富，助力婴幼儿童茁壮成长，为中老年人身体健康提供有效营养保障。",
                    "photo":"product_pic/2586/1/1-370x370-2586-18RF4CY9.jpg",
                    "price":"158",
                    "volume":"36个原装",    #规格名称
                    "unit":"盒"
                },
                .....
            ],
        }
        ```


###<a name="cardchange.createOrder">提货券下单 `cardchange.createOrder`
 * 业务入参

    | 参数名 | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | is_2to3day | string | Y | 是否是2-3天送达(is_2to3day和shtime 2选一) | true |
    | shtime | string | Y | 配送日期 | 20141012 |
    | stime | string | Y | 配送时间 | 0918 |
    | card_number | string | Y | 券号 | Xmmb200000008 |
    | card_passwd | string | Y | 密码 | 1111 |
    | name | string | Y | 配送时间 | 0918 |
    | province | string | Y | 配送时间 | 0918 |
    | city | string | Y | 配送时间 | 0918 |
    | area | string | Y | 配送时间 | 0918 |
    | address | string | Y | 配送时间 | 0918 |
    | mobile | string | Y | 配送时间 | 0918 |
    | price_id | string | Y | 配送时间 | 0918 |
    | hk | string | N | 配送时间 | 0918 |
    | msg | string | N | 配送时间 | 0918 |

 * 接口返回
  - 成功
    * app返回结构
        ```json
        {
        code: "200",
        msg: "下单成功",
        order_info: {
                    order_name: "141013217306",
                    address_info: {
                        address: "上海上海市徐汇区桂林路396号",
                        mobile: "13524780797",
                        name: "songtao",
                        shtime: "20141012 09~18",
                        msg: "留言",
                        hk: "贺卡"
                        }
                    }
        }
        ```


##<a name="o2o">门店

###<a name="o2o.regionlist">根据ID获取门店地区下一级 `o2o.regionlist`
 * 业务入参

    | 参数名 | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | pid | int | Y | 区域ID,如果值为0，那就获取最上级，城市列表 | 1 |

 * 接口返回
	- 成功
		* app返回结构
        ```json
      	#其他返回
        [
            {
                "name": "上海市",
                "id": "2"
            }
        ]
		#楼宇返回
        [
            {
                "name": "金茂",
                "id": "5",
                "store": {
                    "id": "1"
                },
                "latitude": "31.23058",
                "longitude": "121.538598",
                "bszone": {
                    "id": "4",
                    "name": "陆家嘴商区"
                },
                "area": {
                    "id": "3",
                    "name": "浦东新区（外环线以外，郊环线以内）"
                },
                "city": {
                    "id": "2",
                    "name": "上海市"
                },
                "province": {
                    "id": "1",
                    "name": "上海"
                }
            },
            {
                "name": "证大",
                "id": "6",
                "store": {
                    "id": "1"
                },
                "latitude": "",
                "longitude": "",
                "bszone": {
                    "id": "4",
                    "name": "陆家嘴商区"
                },
                "area": {
                    "id": "3",
                    "name": "浦东新区（外环线以外，郊环线以内）"
                },
                "city": {
                    "id": "2",
                    "name": "上海市"
                },
                "province": {
                    "id": "1",
                    "name": "上海"
                }
            }
        ]
        ```
###<a name="o2o.nearbyBuilding">定位最近的楼宇 `o2o.nearbyBuilding`
* 业务入参

    | 参数名 | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | latitude | string | Y | 纬度 | 37.4 |
    | longitude | string | Y | 经度 | 40.1 |
    | area_id | int | Y | 三级行政区 | 1 |

* 接口返回
 - 成功
  * app返回结构
  ```json
[
    {
        "id": "5",
        "pid": "4",
        "name": "金茂",
        "latitude": "31.23058",
        "longitude": "121.538598",
        "store": {
            "id": "1"
        },
        "bszone": {
            "id": "4",
            "name": "陆家嘴商区"
        },
        "area": {
            "id": "3",
            "name": "浦东新区（外环线以外，郊环线以内）"
        },
        "city": {
            "id": "2",
            "name": "上海市"
        },
        "province": {
            "id": "1",
            "name": "上海"
        }
    }
]
```

###<a name="o2o.nearbyBszone">定位最近的商区 `o2o.nearbyBszone`
* 业务入参

    | 参数名 | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | latitude | string | Y | 纬度 | 37.4 |
    | longitude | string | Y | 经度 | 40.1 |
    | area_id | int | Y | 三级行政区 | 1 |

* 接口返回
	- 成功
 		* app返回结构
        ```json
        {
            "city":{
            	"id":1,
                "name":"上海",
            },
      		"area":{
            	"id":1,
           		"name":"浦东新区"
            },
      		"bszone":{
            	"id":1,
           		"name":"陆家嘴商业区",
            }
        }
        ```

###<a name="o2o.lastAddress">获取上次下单成功的配送地址 `o2o.lastAddress`
* 业务入参

    | 参数名 | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | region_id | int | Y | 地区标识 | 1 |

* 接口返回
	- 成功
 		* app返回结构
```json
    {
        "building": {
            "id": "6",
            "name": "证大"
        },
        "bszone": {
            "id": "4",
            "name": "陆家嘴商区"
        },
        "area": {
            "id": "3",
            "name": "浦东新区（外环线以外，郊环线以内）"
        },
        "city": {
            "id": "2",
            "name": "上海市"
        },
        "province": {
            "id": "1",
            "name": "上海"
        },
        "address": "桂林路1",
        "name": "右边1",
        "mobile": "13818994476",
        "telephone": "",
        "store": {
            "id": "1",
            "name": "234"
        }
    }
```

###<a name="o2o.addresslist">获取历史地址列表 `o2o.addresslist`
* 业务入参

    | 参数名 | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | region_id | int | Y | 地区标识 | 1 |

* 接口返回
	- 成功
		* app结构返回
        ```json
[
    {
        "building": {
            "id": "5",
            "name": "金茂"
        },
        "bszone": {
            "id": "4",
            "name": "陆家嘴商区"
        },
        "area": {
            "id": "3",
            "name": "浦东新区"
        },
        "city": {
            "id": "2",
            "name": "上海市"
        },
        "province": {
            "id": "1",
            "name": "上海"
        },
        "address": "桂林路",
        "mobile": "13818994476",
        "name": "陈平",
        "store": {
            "id": "1",
            "name": "234",
            "opentime": "",
            "phone": "24",
            "address": "24",
            "longitude": "24",
            "latitude": "24"
        }
    },
    {
        "building": {
            "id": "6",
            "name": "证大"
        },
        "bszone": {
            "id": "4",
            "name": "陆家嘴商区"
        },
        "area": {
            "id": "3",
            "name": "浦东新区"
        },
        "city": {
            "id": "2",
            "name": "上海市"
        },
        "province": {
            "id": "1",
            "name": "上海"
        },
        "address": "桂林路1",
        "mobile": "13818994476",
        "name": "右边1",
        "store": {
            "id": "1",
            "name": "234",
            "opentime": "",
            "phone": "24",
            "address": "24",
            "longitude": "24",
            "latitude": "24"
        }
    }
]
        ```

###<a name="o2o.storeproducts">获取门店商品 `o2o.storeproducts`
* 业务入参

    | 参数名 | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | store_id | int | Y | 门店ID(自提此这段必须) | 1 |
    | building_id | int | Y | 门店ID(配送此这段必须) | 1 |
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |

* 接口返回
	- 成功
	 * app返回结构
```json
[
    {
        "id": "128",
        "product_name": "菲律宾凤梨",
        "thum_photo": "http://cdn.fruitday.com/product_pic/40/1/1-180x180-40-D3HW2YDR.jpg",
        "photo": "http://cdn.fruitday.com/product_pic/40/1/1-370x370-40-D3HW2YDR.jpg",
        "price": "45",
        "stock": "91",
        "volume": "1个装",
        "price_id": "128",
        "product_no": "Box0142",
        "product_id": "40",
        "old_price": "45",
        "ptype": 1,
        "buy_limit": "0"
    }
]
```

###<a name="o2o.total">选择商品后计算价格 `o2o.total`
* 业务入参

    | 参数名 | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | region_id | int | Y | 所在地区 | 10086|
    | items | ITEMS | Y | 商品列表 |  &nbsp;|
   | store_id | int | Y | 门店ID |  &nbsp;|

	 - ITEMS结构
            [
				{
                	"pid":2203,
 					"ppid":2965,
					"qty":1,
                    "pno":20149711,
                },
				{
                	"pid":2314,
 					"ppid":3139,
					"qty":2,
                    "pno":20149711,
                }
            ]

* 接口返回
	- 成功
		* app返回结构

```json
{
    "cart":
        {
            "items":
                {
                    "o2o_3687":
                        {
                            "sku_id":"3687",
                            "product_id":"2623",
                            "qty":1,
                            "product_no":"201410550",
                            "item_type":"o2o",
                            "name":"奇异果",
                            "unit":"盒",
                            "spec":"33个装",
                            "photo": {
                                    "huge": "http://cdn.fruitday.com/product_pic/3035/1/1-1000x1000-3035-TH17W9UA.jpg",
                                    "big": "http://cdn.fruitday.com/product_pic/3035/1/1-370x370-3035-TH17W9UA.jpg",
                                    "middle": "http://cdn.fruitday.com/product_pic/3035/1/1-270x270-3035-TH17W9UA.jpg",
                                    "small": "http://cdn.fruitday.com/product_pic/3035/1/1-180x180-3035-TH17W9UA.jpg",
                                    "thum": "http://cdn.fruitday.com/product_pic/3035/1/1-100x100-3035-TH17W9UA.jpg"
                                },
                            "weight":"0.00",
                            "price":"218",
                            "sale_price":"218",
                            "pmt_price":0,
                            "amount":218,
                            "status":"active",
                            "pmt_price_total":0,
                            "goods_cost":"218"
                        }
                },
                "total_amount":"218",
                "goods_amount":"218",
                "goods_cost":"218",
                "pmt_goods":0,
                "cost_freight":0,
                "pmt_total":"0",
        },

}
```

###<a name="o2o.orderInit">订单初始化 `o2o.orderInit`
* 业务入参

    | 参数名 | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | items | ITEMS | Y | 商品列表 |  &nbsp;|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | region_id | int | Y | 所在地区 | 10086|
    | order_type| int | Y | 订单类型 | 1:线下配送 2:线下自提|
    | building_id| int | Y | 楼宇ID | 1 |
	| jfmoney| int | N | 使用积分 | 10 |
   | card| String | N | 使用优惠券 | gh12345 |
 	| pay_parent_id| int | N | 支付平台 | 1 |
    | pay_id| int | N | 支付方式 | 0 |
	 | name| string | N | 收货人姓名 | 张三 |
     | mobile| string | N | 手机 | 13812345987 |
   | address| string | N | 收货地址 | 5楼402 |

	 - ITEMS结构
            [
				{
                	"pid":2203,
 					"ppid":2965,
					"qty":1,
                    "pno":20149711,
                }
            ]

* 接口返回
 	- 成功
 		* app返回结构
```json
{
    "cart_info": {
        "items": [
            {
                "sku_id": "128",
                "product_id": "40",
                "qty": "2",
                "product_no": "Box0142",
                "item_type": "o2o",
                "store_id": "1",
                "name": "菲律宾凤梨",
                "unit": "袋",
                "spec": "1个装",
                "photo": {
                    "huge": "http://cdn.fruitday.com/product_pic/40/1/1-1000x1000-40-D3HW2YDR.jpg",
                    "big": "http://cdn.fruitday.com/product_pic/40/1/1-370x370-40-D3HW2YDR.jpg",
                    "middle": "http://cdn.fruitday.com/product_pic/40/1/1-270x270-40-D3HW2YDR.jpg",
                    "small": "http://cdn.fruitday.com/product_pic/40/1/1-180x180-40-D3HW2YDR.jpg",
                    "thum": "http://cdn.fruitday.com/product_pic/40/1/1-100x100-40-D3HW2YDR.jpg"
                },
                "weight": "1.50",
                "price": "45",
                "sale_price": "45",
                "pmt_price": "0",
                "amount": "90",
                "status": "active",
                "pmt_price_total": "0",
                "goods_cost": "90"
            }
        ],
        "total_amount": "90",
        "goods_amount": "90",
        "goods_cost": "90",
        "pmt_goods": "0",
        "cost_freight": "0",
        "pmt_total": "0"
    },
    "uid": "332021",
    "goods_money": "90",
    "method_money": "0",
    "pay_discount": "0",
    "jf_money": "1",
    "card_money": "",
    "pmoney": "90",
    "msg": "",
    "money": "89",
    "need_authen_code": "0",
    "shtime": "",
    "stime": "两小时之内配送",
    "can_use_card": "1",
    "can_use_jf": "1",
    "jf_limit_pro": "",
    "user_mobile": "13818994476",
    "user_money": "9763.00",
    "user_coupon_num": "0",
    "order_jf_limit": "13",
    "order_address": {
        "building": {
            "id": "5",
            "name": "金茂"
        },
        "bszone": {
            "id": "4",
            "name": "陆家嘴商区"
        },
        "area": {
            "id": "3",
            "name": "浦东新区（外环线以外，郊环线以内）"
        },
        "city": {
            "id": "2",
            "name": "上海市"
        },
        "province": {
            "id": "1",
            "name": "上海"
        },
        "name": "",
        "mobile": "",
        "address": ""
    },
    "pay_parent_id": "1",
    "pay_id": "0",
    "pay_name": "支付宝付款",
    "icon": "http://cdn.fruitday.com/assets/images/bank/app/1_0.png",
    "use_jf": "100",
    "use_card": "",
    "need_send_code":"1"        #是否需要验证码验证 0-不需要 1-需要
}
```

###<a name="o2o.createOrder">生成订单 `o2o.createOrder`
* 业务入参

    | 参数名 | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | items | ITEMS | Y | 商品列表 |  &nbsp;|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | msg    |    string  |       Y      |  订单留言       | 啦啦啊|
    | device_code    |    string  |       Y      |  设备号       |21313|
    | verification_code    |    string  |       Y      |  验证码       |231
    | ver_code_connect_id    |    string  |       Y      |  user.sendVerCode获取的connect_id       |7b5982f5747672d329d98352e2556f39|
    | building_id | int | N | 楼宇ID(线下配送此字段必须) |  1 |
    | address | string | N | 收货地址(线下配送此字段必须) | 五楼302室|
    | store_id | int | N | 门店ID (线下自提此字段必须) | 1 |
    | name | string | Y | 收货人 | 张三 |
    | mobile | string | Y | 联系方式 | 138XXXXXXXX |
    | order_type| int | Y | 订单类型 | 3:线下配送 4:线下自提|
	| jfmoney| int | N | 使用积分 | 10 |
   | card| String | N | 使用优惠券 | gh12345 |
 	| pay_parent_id| int | N | 支付平台 | 1 |
    | pay_id| int | N | 支付方式 | 0 |

	 - ITEMS结构
            [{
                "pid":2203,
                "ppid":2965,
                "qty":1,
                "pno":20149711,
            }]

* 接口返回
 	- app返回示例
```json
{
	"code":200,
    "msg":"1500000001",
    "pay_parent_id":1,
  	"money":100
}
```

###<a name="o2o.nearbystore">获取就近门店 `o2o.nearbystore`
* 业务入参

    | 参数名 	| 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | latitude | string | Y | 用户位置纬度坐标 | 37.4 |
    | longitude | string | Y | 用户位置经度坐标 | 40.1 |
    | area_id | int | Y | 三级行政区 | 106092 |

* 接口返回
	- app返回示例
```json
{
		"store_id":"00001",
		"store_name":"中天科技园店",
		"store_open_time":"10:00-18:00",
		"store_phone":"021-5xxxxxxx",
		"store_address":"上海 上海 浦东新区 中天科技园 5号楼大厅",
		"store_longitude":"30.11111111111",
        "store_latitude":"120.11111111111",
}
```

###<a name="o2o.commonstores">获取用户常用自提门店 `o2o.commonstores`
* 业务入参

    | 参数名 	| 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | region_id | int | Y | 所在地区 | 106092 |

* 接口返回
	- app返回示例
```json
[{
		"store_id":"00001",
		"store_name":"中天科技园店",
		"store_open_time":"10:00-18:00",
		"store_phone":"021-5xxxxxxx",
		"store_address":"上海 上海 浦东新区 中天科技园 5号楼大厅",
		"store_longitude":"30.11111111111",
        "store_latitude":"120.11111111111",
}]
```

###<a name="o2o.citystores">获取城市的所有门店 `o2o.citystores`
* 业务入参

    | 参数名 	| 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | region_id | int | Y | 所在地区 | 106092 |

* 接口返回
	- app返回示例
```json
[{
    "store_id":"00001",
    "store_name":"中天科技园店",
    "store_open_time":"10:00-18:00",
    "store_phone":"021-5xxxxxxx",
    "store_address":"上海 上海 浦东新区 中天科技园 5号楼大厅",
    "store_longitude":"30.11111111111",
    "store_latitude":"120.11111111111",
}]
```

###<a name="o2o.exchgcoupon">门店兑换券 `o2o.exchgcoupon`
* 业务入参

    | 参数名 	| 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | coupon | string | Y | 兑换券 | test1234 |
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |

* 接口返回
	- app返回示例
```json
{
	"code":200,
    "msg":"succ",
}
```

###<a name="o2o.searchBuilding">搜索楼宇 `o2o.searchBuilding`
* 业务入参

    | 参数名 	| 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | building_name | string | Y | 楼宇名称 | SOHO |
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |

* 接口返回
	- app返回示例
```json
{
    1: {
        name: "SOHO世纪广场",
        id: "517",
        store: {
            id: "5"
        },
        latitude: "31.232324",
        longitude: "121.539586",
        bszone: {
            id: "447",
            name: "陆家嘴"
        },
        area: {
            id: "3",
            name: "浦东新区（外环线以内）"
        },
        city: {
            id: "2",
            name: "上海市"
        },
        province: {
            id: "1",
            name: "上海"
        }
    }
}
```

###<a name="o2o.getStroeInfo">店铺信息 `o2o.getStroeInfo`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | store_id | int | Y | 店铺ID | 1 |
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |

* 接口返回
  - app返回示例
```json
  {
    store_info: {
      id: "1",
      name: "东方大厦店",
      opentime: "09:00-16:00",
      phone: "021-38860247",
      address: "上海市浦东新区世纪大道1500号东方大厦后大厅",
      post: "免费送",
      send_time: "16：00前下单，两小时内送达；16：00后下单，第二天配送"
      },
    store_banner: [
      {
        photo: "http://cdn.fruitday.com/images/2015-05-12/1431432714_pic.jpg"
      },
      {
        photo: "http://cdn.fruitday.com/images/2015-05-11/1431309013_pic.jpg"
      }
    ]
  }
```

###<a name="o2o.nearbyBuilding_new">附近楼宇 `o2o.nearbyBuilding_new`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | type   | int | Y | 类型 | 可选值(1,2) |
    | latitude | string | Y | 经纬度 | 31.178713 |
    | longitude | string | Y | 纬度 | 121.123123 |
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |

* 接口返回
  - app返回示例
```json
  [
    {
      id: "170",
      pid: "168",
      name: "贝岭大厦",
      latitude: "31.178713",
      longitude: "121.413446",
      store: {
        id: "11"
      },
      bszone: {
        id: "168",
        name: "漕河泾"
      },
      area: {
        id: "8",
        name: "徐汇区"
      },
      city: {
        id: "2",
        name: "上海市"
      },
      province: {
        id: "1",
        name: "上海"
      }
    },
  ]
```
```json
  [
    {
      id: "193",
      pid: "171",
      name: "漕河泾现代服务园大厦",
      latitude: "31.17061",
      longitude: "121.404895",
      store: {
        id: "11"
      },
      bszone: {
        id: "171",
        name: "漕河泾"
      },
      area: {
        id: "19",
        name: "闵行区（外环线以内及莘庄地区）"
      },
      city: {
        id: "2",
        name: "上海市"
      },
      province: {
        id: "1",
        name: "上海"
      },
      distance: "1米"
    },
  ]
```

###<a name="o2o.getStoreList">附近楼宇 `o2o.getStoreList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | building_id   | int | Y | 类型 | 10 |
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |

* 接口返回
  - app返回示例
```json
  {
    store: [
      {
        store_info: {
          id: "1",
          name: "东方大厦店",
          opentime: "09:00-16:00",
          phone: "021-38860247",
          address: "上海市浦东新区世纪大道1500号东方大厦后大厅",
          post: "免费送",
          send_time: "16：00前下单，两小时内送达；16：00后下单，第二天配送"
        },
        store_banner: [
          {
            id: "44",
            store_id: "1",
            photo: "http://cdn.fruitday.com/images/2015-05-25/9f74a948c2b538ed68b4466e9333e9ba.jpg",
            title: "1231231",
            type: "5",
            target_id: "1111",
            page_url: "http://dev.fruitday.com/?",
            sort: "123",
            is_show: "1",
            time: "1432625016",
            start_time: "1970-01-01 00:00:00",
            end_time: "2038-01-01 00:00:00",
            page_photo: "http://cdn.fruitday.com/"
          },
          {
            id: "43",
            store_id: "1",
            photo: "",
            title: "测试",
            type: "6",
            target_id: "1111",
            page_url: "http://dev.fruitday.com/?",
            sort: "12",
            is_show: "1",
            time: "1432624320",
            start_time: "1970-01-01 00:00:00",
            end_time: "2038-01-01 00:00:00"
          },
        ]
        products: [
          {
            id: "128",
            product_name: "菲律宾凤梨（买一送一）",
            thum_photo: "http://cdn.fruitday.com/product_pic/40/1/1-180x180-40-D2WDRCH5.jpg",
            photo: "http://cdn.fruitday.com/product_pic/40/1/1-370x370-40-D2WDRCH5.jpg",
            price: "45",
            stock: "1",
            volume: "1个装",
            price_id: "128",
            product_no: "Box0142",
            product_id: "40",
            old_price: "",
            ptype: 1,
            buy_limit: "0"
          },
          {
            id: "1614",
            product_name: "心之松露夹心巧克力礼盒装",
            thum_photo: "http://cdn.fruitday.com/product_pic/1236/1/1-180x180-1236.jpg",
            photo: "http://cdn.fruitday.com/product_pic/1236/1/1-370x370-1236.jpg",
            price: "70",
            stock: "1",
            volume: "礼盒装",
            price_id: "1614",
            product_no: "30903",
            product_id: "1236",
            old_price: "",
            ptype: 1,
            buy_limit: "0"
          }
        ]
      }
    ]
  }
```

###<a name="o2o.getStoreOtherGoods">获取门店其他商品信息 `o2o.getStoreOtherGoods`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | store_id   | int | Y | 类型 | 10 |
    | product_id   | int | Y | 类型 | 10 |
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | nums | int | N | 获取商品数量 | 2 |

* 接口返回
  - app返回示例
```json
  [
    {
      id: "128",
      product_name: "菲律宾凤梨（买一送一）",
      thum_photo: "http://cdn.fruitday.com/product_pic/40/1/1-180x180-40-D2WDRCH5.jpg",
      photo: "http://cdn.fruitday.com/product_pic/40/1/1-370x370-40-D2WDRCH5.jpg",
      price: "45",
      stock: "94",
      volume: "1个装",
      price_id: "128",
      product_no: "Box0142",
      product_id: "40",
      old_price: "",
      ptype: 1,
      buy_limit: "0",
      store_id: "1"
    },
    {
      id: "1614",
      product_name: "心之松露夹心巧克力礼盒装",
      thum_photo: "http://cdn.fruitday.com/product_pic/1236/1/1-180x180-1236.jpg",
      photo: "http://cdn.fruitday.com/product_pic/1236/1/1-370x370-1236.jpg",
      price: "70",
      stock: "1",
      volume: "礼盒装",
      price_id: "1614",
      product_no: "30903",
      product_id: "1236",
      old_price: "",
      ptype: 1,
      buy_limit: "0",
      store_id: "1"
    }
  ]
```
###<a name="o2ocart.add"/>加入购物车 `o2ocart.add`
* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |  ---------- |
| connect_id    |    string  |       N      |  登录标识  |  7b5982f5747672d329d98352e2556f39  |
| region_id    |    int  |       Y      |  地区id  |   1   |
| carttmp    |    string  |       N      |  未登录时添加的购物车结构  |  cart_items  |
| items    |    string  |       Y      |  购物车入参结构(见items)  |   items  |

  items结构:
      [
          {
              "ppid":"3221" //规格商品ID
              "pid":"4840" //主商品ID
              "qty":"1" //数量
              "pno":"20149460" //货号
              "type":"o2o" //普通商品类型
        "store_id":"25"
          },
          {
              "ppid":"3221" //规格商品ID
              "pid":"4840" //主商品ID
              "qty":"1" //数量
              "pno":"20149460" //货号
              "type":"exch" //换购商品类型
              "pmt_id":"30" //换购的优惠ID
          },
          {
              "ppid":"3221" //规格商品ID
              "pid":"4840" //主商品ID
              "qty":"1" //数量
              "pno":"20149460" //货号
        "gift_send_id":28,
        "gift_active_type":1,
              "type":"user_gift" //赠品领取类型,
          }
      ]

* 接口返回
```json
        {
            cart: {
                items: [
                    {
                        store_id: 25,
                        store_name: "天天果园SH13",
                        store_items: [
                            {
                                name: "韩国闻京梨",
                                sku_id: "126",
                                product_id: "41",
                                product_no: "20149889",
                                item_type: "o2o",
                                status: "active",
                                qty: 12,
                                unit: "盒",
                                spec: "6个装",
                                price: "20",
                                sale_price: "20",
                                amount: 240,
                                photo: {
                                    huge: "http://cdn.fruitday.com/product_pic/41/1/1-1000x1000-41.jpg",
                                    big: "http://cdn.fruitday.com/product_pic/41/1/1-370x370-41.jpg",
                                    middle: "http://cdn.fruitday.com/product_pic/41/1/1-270x270-41.jpg",
                                    small: "http://cdn.fruitday.com/product_pic/41/1/1-180x180-41.jpg",
                                    thum: "http://cdn.fruitday.com/product_pic/41/1/1-100x100-41.jpg"
                                },
                                    store_id: 25
                                }
                        ]
                    },
                    {
                        store_id: 26,
                        store_name: "天天果园SH14",
                        store_items: [
                            {
                                name: "韩国闻京梨",
                                sku_id: "126",
                                product_id: "41",
                                product_no: "20149889",
                                item_type: "o2o",
                                status: "active",
                                qty: 12,
                                unit: "盒",
                                spec: "6个装",
                                price: "20",
                                sale_price: "20",
                                amount: 240,
                                photo: {
                                    huge: "http://cdn.fruitday.com/product_pic/41/1/1-1000x1000-41.jpg",
                                    big: "http://cdn.fruitday.com/product_pic/41/1/1-370x370-41.jpg",
                                    middle: "http://cdn.fruitday.com/product_pic/41/1/1-270x270-41.jpg",
                                    small: "http://cdn.fruitday.com/product_pic/41/1/1-180x180-41.jpg",
                                    thum: "http://cdn.fruitday.com/product_pic/41/1/1-100x100-41.jpg"
                                },
                                store_id: 26
                            }
                        ]
                    }
                ],
                total_amount: 480,
                goods_amount: "480",
                goods_cost: "480",
                pmt_goods: 0,
                cost_freight: 0,
                pmt_total: "0"
            },
            cartcount: 24
        }


###<a name="o2ocart.update"/>更新购物车 `o2ocart.update`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |---------- |
| connect_id    |    string  |       N      |  登录标识  | 7b5982f5747672d329d98352e2556f39  |
| region_id    |    int  |       Y      |  地区id  |   1   |
| carttmp    |    string  |       N      |  未登录时添加的购物车结构 | cart_items  |
| item    |    string  |       Y      |  购物车入参结构(见item)  |  item  |

  item结构
    {
      "ik":"o2o_126_25" //购物车ITEM的KEY值
          "qty":"1" //数量
          "type":"o2o" //普通商品类型
      }

* 接口返回
* app返回结构
```json
        {
            cart: {
                items: [
                    {
                        store_id: 25,
                        store_name: "天天果园SH13",
                        store_items: [
                            {
                                name: "韩国闻京梨",
                                sku_id: "126",
                                product_id: "41",
                                product_no: "20149889",
                                item_type: "o2o",
                                status: "active",
                                qty: 2,
                                unit: "盒",
                                spec: "6个装",
                                price: "20",
                                sale_price: "20",
                                amount: 40,
                                photo: {
                                    huge: "http://cdn.fruitday.com/product_pic/41/1/1-1000x1000-41.jpg",
                                    big: "http://cdn.fruitday.com/product_pic/41/1/1-370x370-41.jpg",
                                    middle: "http://cdn.fruitday.com/product_pic/41/1/1-270x270-41.jpg",
                                    small: "http://cdn.fruitday.com/product_pic/41/1/1-180x180-41.jpg",
                                    thum: "http://cdn.fruitday.com/product_pic/41/1/1-100x100-41.jpg"
                                },
                                store_id: 25
                            }
                        ]
                    },
                    {
                        store_id: 26,
                        store_name: "天天果园SH14",
                        store_items: [
                            {
                                name: "韩国闻京梨",
                                sku_id: "126",
                                product_id: "41",
                                product_no: "20149889",
                                item_type: "o2o",
                                status: "active",
                                qty: 12,
                                unit: "盒",
                                spec: "6个装",
                                price: "20",
                                sale_price: "20",
                                amount: 240,
                                photo: {
                                    huge: "http://cdn.fruitday.com/product_pic/41/1/1-1000x1000-41.jpg",
                                    big: "http://cdn.fruitday.com/product_pic/41/1/1-370x370-41.jpg",
                                    middle: "http://cdn.fruitday.com/product_pic/41/1/1-270x270-41.jpg",
                                    small: "http://cdn.fruitday.com/product_pic/41/1/1-180x180-41.jpg",
                                    thum: "http://cdn.fruitday.com/product_pic/41/1/1-100x100-41.jpg"
                                },
                                store_id: 26
                            }
                        ]
                    }
                ],
                total_amount: 280,
                goods_amount: "280",
                goods_cost: "280",
                pmt_goods: 0,
                cost_freight: 0,
                pmt_total: "0"
            },
            cartcount: 14
        }


###<a name="o2ocart.get"/>获取购物车 `o2ocart.get`


* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |---------- |
| connect_id    |    string  |       N      |  登录标识  | 7b5982f5747672d329d98352e2556f39  |
| region_id    |    int  |       Y      |  地区id  |  1   |
| carttmp    |    string  |       N      |  未登录时添加的购物车结构(见[cart_items](#cart_items))  |   cart_items  |


* 接口返回
* app返回结构
```json
        {
            cart: {
                items: [
                {
                    store_id: 25,
                    store_name: "天天果园SH13",
                    store_items: [
                        {
                            name: "韩国闻京梨",
                            sku_id: "126",
                            product_id: "41",
                            product_no: "20149889",
                            item_type: "o2o",
                            status: "active",
                            qty: 2,
                            unit: "盒",
                            spec: "6个装",
                            price: "20",
                            sale_price: "20",
                            amount: 40,
                            photo: {
                                huge: "http://cdn.fruitday.com/product_pic/41/1/1-1000x1000-41.jpg",
                                big: "http://cdn.fruitday.com/product_pic/41/1/1-370x370-41.jpg",
                                middle: "http://cdn.fruitday.com/product_pic/41/1/1-270x270-41.jpg",
                                small: "http://cdn.fruitday.com/product_pic/41/1/1-180x180-41.jpg",
                                thum: "http://cdn.fruitday.com/product_pic/41/1/1-100x100-41.jpg"
                            },
                            store_id: 25
                        }
                    ]
                },
                {
                    store_id: 26,
                    store_name: "天天果园SH14",
                    store_items: [
                        {
                            name: "韩国闻京梨",
                            sku_id: "126",
                            product_id: "41",
                            product_no: "20149889",
                            item_type: "o2o",
                            status: "active",
                            qty: 12,
                            unit: "盒",
                            spec: "6个装",
                            price: "20",
                            sale_price: "20",
                            amount: 240,
                            photo: {
                                huge: "http://cdn.fruitday.com/product_pic/41/1/1-1000x1000-41.jpg",
                                big: "http://cdn.fruitday.com/product_pic/41/1/1-370x370-41.jpg",
                                middle: "http://cdn.fruitday.com/product_pic/41/1/1-270x270-41.jpg",
                                small: "http://cdn.fruitday.com/product_pic/41/1/1-180x180-41.jpg",
                                thum: "http://cdn.fruitday.com/product_pic/41/1/1-100x100-41.jpg"
                            },
                            store_id: 26
                        }
                    ]
                }
                ],
                total_amount: 280,
                goods_amount: "280",
                goods_cost: "280",
                pmt_goods: 0,
                cost_freight: 0,
                pmt_total: "0"
                },
            cartcount: 14
        }


###<a name="o2ocart.remove"/>删除购物车某一项 `o2ocart.remove`


* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       N      |  登录标识  |7b5982f5747672d329d98352e2556f39  |
| region_id    |    int  |       Y      |  地区id  |  1  |
| item    |    string  |       Y      |  购物车入参结构(见item)  |   item  |

    item结构
  {
    "ik":"o2o_126_25" //购物车ITEM的KEY值
      "type":"o2o" //普通商品类型
  }

###<a name="o2ocart.clear"/>清空购物车 `o2ocart.clear`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识  |  7b5982f5747672d329d98352e2556f39  |
| store_id    |    int  |       N      |  门店ID,有门店ID就清空该门店的商品，没有就清空全部  |  25  |

###<a name="o2ocart.checkCartInit"/>校验购物车数据 `o2ocart.checkCartInit`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识  |  7b5982f5747672d329d98352e2556f39  |
| building_id    |    int  |       N      | 楼宇ID   |  25  |
| latitude    |    string  |       N      | 纬度   |    |
| longitude    |    string  |       N      | 经度   |    |
| is_clear    |    int  |       Y      | 是否清除不支持配送门店商品   |  1清除 0不清除，返回报错信息  |

* 接口返回
* app返回结构
```json
        {
      code:200,
      msg:succ
    }
        {
      code:300,
      msg:您选择的商品无法配送至同一地址，请重新选择
    }

###<a name="o2ocart.selpmt"/>换购商品 `o2ocart.selpmt`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |      示例    |
| ------------ | ------------- | ------------ | ------------ |------------ |
| connect_id    |    string  |       Y      |  登录标识  |  7b5982f5747672d329d98352e2556f39  |
| pmt_id    |    string  |       Y      | 活动ID   |  1130-398b  |

* 接口返回
* app返回结构
```json
        {
            exch_6784: {
                pmt_id: "1130-398b",
                name: "测试商品1",
                sku_id: "6784",
                product_id: "4744",
                product_no: "168AUH0A89a0002",
                item_type: "exch",
                status: "active",
                qty: 1,
                unit: "盒",
                spec: "4斤装（原装）",
                price: "5",
                photo: {
                    huge: "http://cdn.fruitday.com/product_pic/3559/1/1-1000x1000-3559-S2KW7TB1.jpg",
                    big: "http://cdn.fruitday.com/product_pic/3559/1/1-370x370-3559-S2KW7TB1.jpg",
                    middle: "http://cdn.fruitday.com/product_pic/3559/1/1-270x270-3559-S2KW7TB1.jpg",
                    small: "http://cdn.fruitday.com/product_pic/3559/1/1-180x180-3559-S2KW7TB1.jpg",
                    thum: "http://cdn.fruitday.com/product_pic/3559/1/1-100x100-3559-S2KW7TB1.jpg"
                }
            }
        }



###<a name="o2o.pageListProducts"/>获取专题页商品 `o2o.pageListProducts`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| page_type    |    int  |       Y      |  o2o banner返回的type  |  1  |
| target_id    |    int  |       Y      |  o2o banner返回的target_id  | 2  |
| store_id    |    int  |       Y      |  门店ID  | 25  |

* 接口返回
```json

        {
            products: [
                {
                    id: "7645",
                    product_name: "乌拉圭草饲牛尾",
                    thum_photo: "http://cdn.fruitday.com/images/product_pic/5552/1/1-180x180-5552-W8F2TSWR.jpg",
                    photo: "http://cdn.fruitday.com/images/product_pic/5552/1/1-370x370-5552-W8F2TSWR.jpg",
                    price: "33.00",
                    stock: "988",
                    volume: "350克",
                    price_id: "7645",
                    product_no: "2150901118",
                    product_id: "5552",
                    old_price: "38.00",
                    ptype: 1,
                    buy_limit: "0",
                    store_id: "25"
                }
            ],
            title: "测试O2O专题",
            page_photo: "http://cdn.fruitday.com/images/2015-11-25/2d9ba69f0ff3953e2c2ca1d83b128e0e.jpg"
        }



###<a name="o2o.getGoodsDetailBanner"/>商品详情广告 `o2o.getGoodsDetailBanner`

* 业务入参

|     参数名    |     类型      |      必须     |     说明     |     示例    |
| ------------ | ------------- | ------------ | ------------ |  ------------ |
| store_id    |    int  |       Y      |    |  25  |
| banner_id    |    int  |      N      |  如过详情是从banner点入的，过滤这个banner  | 2575  |
| nums    |    int  |       N     |  返回数量，默认是1  | 2  |

* 接口返回
```json

        [
            {
                id: "2663",
                store_id: "25",
                photo: "http://cdn.fruitday.com/images/2015-11-27/e4fc2e099cb34e265d47e37865725733.jpg",
                title: "上海-犁记(测试专题页)",
                type: "17",
                target_id: "243",
                page_url: "http://huodong.fruitday.com/sale/o2o1130/30tw.html?",
                sort: "2147483647",
                is_show: "1",
                time: "1449134508",
                start_time: "2015-11-26 00:00:00",
                end_time: "2015-12-04 17:00:00"
            },
            {
                id: "2542",
                store_id: "25",
                photo: "http://cdn.fruitday.com/images/2015-11-13/bc09176c8fe178b4fdb489a702705c71.jpg",
                title: "上海 满25元赠 牛油果柚子",
                type: "14",
                target_id: "1",
                page_url: "",
                sort: "2006",
                is_show: "1",
                time: "1449199845",
                start_time: "2015-12-01 00:00:00",
                end_time: "2015-12-10 11:55:00"
            }
        ]

- - -

##<a name="fruit">果食

###<a name="fruit.getTopicList">果食主题列表 `fruit.getTopicList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | type | int | Y | 列表类型(0-全部简单主题1-详细主题) | 1 |
    | curr_page    |    int  |       N      |  分页页数(默认为0)  |   0  |
    | page_size    |    int  |       N      |  每页个数  |    10  |
    | keyword | string | N | 搜索关键词 | 主题 |

* 接口返回
  - app返回示例
```json
(type=1)
[
    {
        "id": "5",
        "title": "xihuan",
        "topic_state": 1,// 1-话题周期内  2-话题周期未到 3-话题周期过期
        "photo": "",
        "thumbs": "",
        "description": "111111",
        "num": "14"
    },
    {
        "id": "1",
        "title": "test",
        "topic_state": 1,
        "photo": "",
        "thumbs": "",
        "description": "test",
        "num": "1"
    },
    {
        "id": "4",
        "title": "test2",
        "topic_state": 1,
        "photo": "http://cdn.fruitday.com/images/2014-12-26/70b654923e52801e8ff3f368a64f52b7.jpg",
        "thumbs": "http://cdn.fruitday.com/images/2014-12-26/70b654923e52801e8ff3f368a64f52b7_thumb.jpg",
        "description": "",
        "num": 0
    }
]
(type=0)
[
    {
        "id": "1",
        "title": "test",
        "topic_state": 1
    },
    {
        "id": "2",
        "title": "adasd",
        "topic_state": 1
    },
    {
        "id": "3",
        "title": "test1",
        "topic_state": 1
    }
]
```

###<a name="fruit.getDetailTopic">果食主题 `fruit.getDetailTopic`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | id | int | Y |  主题id | 1 |

* 接口返回
  - app返回示例
```json
{
    "1": {
        "id": "1",
        "title": "test",
        "topic_state": 1,
        "photo":  ["http://cdn.fruitday.com/images/2014-12-26/70b654923e52801e8ff3f368a64f52b7.jpg"],
        "thumbs":  ["http://cdn.fruitday.com/images/2014-12-26/70b654923e52801e8ff3f368a64f52b7.jpg"],
        "description": "test",
        "summary" : "",
        "type": "1",
        "ptype": 3,
        "ctime": "1419563657",
        "num": 0
    }
}
```

###<a name="fruit.delComment">删除评论 `fruit.delComment`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | id    |    int  |       Y      |  评论id  |   0  |

* 接口返回
  - app返回示例
```json
{
    "code": "200",
    "msg": "删除成功"
}
```

###<a name="fruit.delArticle">删除文章 `fruit.delArticle`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | id    |    int  |       Y      |  文章id  |   0  |

* 接口返回
  - app返回示例
```json
{
    "code": "200",
    "msg": "删除成功"
}
```

###<a name="fruit.checkInvate">检测黑名单 `fruit.checkInvate`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |

* 接口返回
  - app返回示例
```json
{
    "code": "200",
    "msg": "281873"  #用户id
}
```

###<a name="fruit.doArticle">发布文章 `fruit.doArticle`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | title | string | Y | 内容标题 | 很赞的果食 |
    | description | string | Y | 内容描述 | 很赞的果食 |
    | photo[1-9] | obj | Y | 文章图片(1到9张,模拟文件上传提交) | file资源 |
    | tid | int | N | 主题id | 2 |

* 接口返回
  - app返回示例
```json
{
    "code": "200",
    "msg": "发布成功"
}
```

###<a name="fruit.doUserface">上传用户头像 `fruit.doUserface`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | photo| obj | Y | 用户头像(模拟文件上传提交) | file资源 |

* 接口返回
  - app返回示例
```json
{
    "code": "200",
    "msg": "上传成功"
}
```

  ###<a name="fruit.doComment">评论文章或回复文章下的评论 `fruit.doComment`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | aid| int | Y | 文章id | 13 |
    | content| string | Y | 评论内容 | 同上 |
    | replay_id| int | N | 回复的评论id | 57 |
    | replay_uid| int | N | 回复的评论id | 215741 |
    | replay_username| int | N | 回复的评论id | 陈胖胖 |
    | replay_content| int | N | 回复的评论id | 喜欢这文章 |

* 接口返回
  - app返回示例
```json
{
    "ctime": "1425369726",
    "content": "同上",
    "id": "57",
    "username": "果食编辑部",
    "userface": null,
    "stime": 1425369726,
    "is_replay": 1,
    "replay": {
        "uid": "215741",
        "username": "陈胖胖",
        "content": "喜欢这篇文章"
    },
    #下面是兼容安卓
    "replay_uid": "215741",
    "replay_username": "陈胖胖",
    "replay_content": "喜欢这篇文章"
}
```

###<a name="fruit.doWorth">收藏文章 `fruit.doWorth`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | aid| int | Y | 文章id | 13 |

* 接口返回
  - app返回示例
```json
{
    "code": "200",
    "msg": "点赞成功"
}
```

###<a name="fruit.setWorth">收藏文章 `fruit.setWorth`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | aid| int | Y | 文章id | 13 |

* 接口返回
  - app返回示例
```json
{
    "code": "200",
    "msg": "点赞成功"
}
```

###<a name="fruit.undoWorth">取消收藏 `fruit.undoWorth`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | aid| int | Y | 文章id | 13 |

* 接口返回
  - app返回示例
```json
{
    "code": "200",
    "msg": "取消成功"
}
```

###<a name="fruit.getArticleList">获取文章列表 `fruit.getArticleList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | N | 登录标识(判断是否可删除等) | 7b5982f5747672d329d98352e2556f39 |
    | tid| int | N | 主题id | 2 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |
    | type| int | N | 类型0-普通，1-精选 | 0 |

* 接口返回
  - app返回示例
```json
[
    {
        "id": "38",
        "ctime": "1419497388",
        "description": "sdasd",
        "photo": [
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/ef60f39066940bdf8184896b1d23b9ad.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/dd97eb10c7369bb95de41d9195009e98.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/2c2e12d9525db33f27bdfb62421382d1.jpg"
        ],
        "type": "1",
        "ptype": "0",
        "content": "",
        "uid": "0",
        "images_thumbs": [
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/ef60f39066940bdf8184896b1d23b9ad_thumb.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/dd97eb10c7369bb95de41d9195009e98_thumb.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/2c2e12d9525db33f27bdfb62421382d1_thumb.jpg"
        ],
        "username": "\u679c\u98df\u7f16\u8f91\u90e8",
        "userface": "http:\/\/cdn.fruitday.com\/up_images\/default_userpic.png",
        "topic_id": "",
        "topic_title": "",
        "share_url": "http:\/\/www.fruitday.com\/web\/fruitshare\/38",
        "stime": 1425372614,
        "comment_num": 0,
        "worth_num": 0,
        "is_liked": 0,
        "can_delete": false,
        "latest_comments": [

        ]
    },
    {
        "id": "37",
        "ctime": "1417499524",
        "description": "rew",
        "photo": "",
        "type": "0",
        "ptype": "1",
        "content": "test",
        "uid": "0",
        "username": "\u679c\u98df\u7f16\u8f91\u90e8",
        "userface": "http:\/\/cdn.fruitday.com\/up_images\/default_userpic.png",
        "topic_id": "5",
        "topic_title": "",
        "share_url": "http:\/\/www.fruitday.com\/web\/fruitshare\/37",
        "stime": 1425372614,
        "comment_num": "4",
        "worth_num": 0,
        "is_liked": 0,
        "can_delete": false,
        "latest_comments": [
            {
                "ctime": "1425372503",
                "content": "\u540c\u4e0a",
                "id": "61",
                "username": "\u679c\u98df\u7f16\u8f91\u90e8",
                "userface": null,
                "stime": 1425372614,
                "is_replay": 0,
                "replay": [

                ],
                "replay_uid": "",
                "replay_username": "",
                "replay_content": ""
            },
            {
                "ctime": "1425372502",
                "content": "\u540c\u4e0a",
                "id": "60",
                "username": "\u679c\u98df\u7f16\u8f91\u90e8",
                "userface": null,
                "stime": 1425372614,
                "is_replay": 0,
                "replay": [

                ],
                "replay_uid": "",
                "replay_username": "",
                "replay_content": ""
            },
            {
                "ctime": "1425372502",
                "content": "\u540c\u4e0a",
                "id": "59",
                "username": "\u679c\u98df\u7f16\u8f91\u90e8",
                "userface": null,
                "stime": 1425372614,
                "is_replay": 0,
                "replay": [

                ],
                "replay_uid": "",
                "replay_username": "",
                "replay_content": ""
            }
        ]
    },
    {
        "id": "57",
        "ctime": "1425368540",
        "description": "test",
        "photo": [
            "http:\/\/apicdn.fruitday.com\/img\/images\/2015-03-03\/5023b2c3f1f8dbbe3c856abb763d3104.jpg",
            "http:\/\/apicdn.fruitday.com\/img\/images\/2015-03-03\/a6e00dfcc99fc9024540f1fc7b2661c2.jpg"
        ],
        "type": "0",
        "ptype": "0",
        "content": null,
        "uid": "281873",
        "images_thumbs": [
            "http:\/\/apicdn.fruitday.com\/img\/images\/2015-03-03\/5023b2c3f1f8dbbe3c856abb763d3104_thumb.jpg",
            "http:\/\/apicdn.fruitday.com\/img\/images\/2015-03-03\/a6e00dfcc99fc9024540f1fc7b2661c2_thumb.jpg"
        ],
        "username": "\u679c\u98df\u7f16\u8f91\u90e8",
        "userface": "http:\/\/cdn.fruitday.com\/up_images\/default_userpic.png",
        "topic_id": "",
        "topic_title": "",
        "share_url": "http:\/\/www.fruitday.com\/web\/fruitshare\/57",
        "stime": 1425372614,
        "comment_num": 0,
        "worth_num": 0,
        "is_liked": 0,
        "can_delete": false,
        "latest_comments": [

        ]
    }
]
```

###<a name="fruit.getMaxArticleList">获取混合果食文章列表 `fruit.getMaxArticleList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | N | 登录标识(判断是否可删除等) | 7b5982f5747672d329d98352e2556f39 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |

* 接口返回
  - app返回示例
```json
[
    "top": [
          {
            "ptype":"0",
            "data":{
                "id": "38",
                "ctime": "1419497388",
                "stime": 1425372614,
                "type": 1,//是否精选0否1是
                "ptype": "0",//0-用户1-官方2-主题
                "title": "test2",
                "description": "sdasd",
                "content": "",
                "photo": [
                    "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/ef60f39066940bdf8184896b1d23b9ad.jpg",
                    "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/dd97eb10c7369bb95de41d9195009e98.jpg",
                    "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/2c2e12d9525db33f27bdfb62421382d1.jpg"
                ],
                "images_thumbs": [
                    "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/ef60f39066940bdf8184896b1d23b9ad_thumb.jpg",
                    "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/dd97eb10c7369bb95de41d9195009e98_thumb.jpg",
                    "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/2c2e12d9525db33f27bdfb62421382d1_thumb.jpg"
                ],
                //用户信息
                "uid": "0",
                "username": "\u679c\u98df\u7f16\u8f91\u90e8",
                "userrank":5,
                "userface": "http:\/\/cdn.fruitday.com\/up_images\/default_userpic.png",
                //主题信息
                "topic_id": "",
                "topic_title": "",
                "share_url": "http:\/\/www.fruitday.com\/web\/fruitshare\/38",
                "comment_num": 0,
                "worth_num": 0,
                "is_liked": 0,
                "can_delete": false,
            }
          },
          {
            "ptype":"2",
            "data":{
                "id": "4",
                "ctime": "1419497388",//活动开始时间
                "stime": 1425372614,
                "type": 1,
                "ptype":"2",//0-用户1-官方2-主题
                "title": "test2",
                "topic_state": 1,
                "photo": ["http://cdn.fruitday.com/images/2014-12-26/70b654923e52801e8ff3f368a64f52b7.jpg"],
                "thumbs": ["http://cdn.fruitday.com/images/2014-12-26/70b654923e52801e8ff3f368a64f52b7_thumb.jpg"],
                "description": "",
                "num": 0
              }
          }
    ],
    "main":[
          {
            "ptype":"0",
            "data":{
              "id": "38",
              "ctime": "1419497388",
              "stime": 1425372614,
              "type": 1,
              "ptype": "0",//0-用户1-官方3-主题
              "title": "test2",
              "description": "sdasd",
              "content": "",
              "photo": [
                  "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/ef60f39066940bdf8184896b1d23b9ad.jpg",
                  "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/dd97eb10c7369bb95de41d9195009e98.jpg",
                  "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/2c2e12d9525db33f27bdfb62421382d1.jpg"
              ],
              "images_thumbs": [
                  "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/ef60f39066940bdf8184896b1d23b9ad_thumb.jpg",
                  "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/dd97eb10c7369bb95de41d9195009e98_thumb.jpg",
                  "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/2c2e12d9525db33f27bdfb62421382d1_thumb.jpg"
              ],
              //用户信息
              "uid": "0",
              "username": "\u679c\u98df\u7f16\u8f91\u90e8",
              "userrank":5,
              "userface": "http:\/\/cdn.fruitday.com\/up_images\/default_userpic.png",
              //主题信息
              "topic_id": "",
              "topic_title": "",
              "share_url": "http:\/\/www.fruitday.com\/web\/fruitshare\/38",
              "comment_num": 0,
              "worth_num": 0,
              "is_liked": 0,
              "can_delete": false,
            }
          },
          {
            "ptype":"0",
            "data":{
              "id": "4",
              "ctime": "1419497388",//活动开始时间
              "stime": 1425372614,
              "type": 1,
              "ptype":"2",//0-用户1-官方2-主题3-百科
              "title": "test2",
              "topic_state": 1,
              "photo": ["http://cdn.fruitday.com/images/2014-12-26/70b654923e52801e8ff3f368a64f52b7.jpg"],
              "thumbs":["http://cdn.fruitday.com/images/2014-12-26/70b654923e52801e8ff3f368a64f52b7_thumb.jpg"],
              "description": "",
              "num": 0
            }
        }
    ]
]
```

###<a name="fruit.getDetailArticle">获取文章详情 `fruit.getDetailArticle`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | id| int | Y | 文章id | 38 |
    | connect_id | string | N | 登录标识(判断是否可删除等) | 7b5982f5747672d329d98352e2556f39 |

* 接口返回
  - app返回示例
```json
{
    "id": "38",
    "ctime": "1419497388",
    "description": "sdasd",
    "photo": [
        "http://cdn.fruitday.com/images/2014-12-25/ef60f39066940bdf8184896b1d23b9ad.jpg",
        "http://cdn.fruitday.com/images/2014-12-25/dd97eb10c7369bb95de41d9195009e98.jpg",
        "http://cdn.fruitday.com/images/2014-12-25/2c2e12d9525db33f27bdfb62421382d1.jpg"
    ],
    "type": "1",
    "ptype": "0",
    "content": "",
    "thumbs": [
        "images/2014-12-25/ef60f39066940bdf8184896b1d23b9ad_thumb.jpg",
        "images/2014-12-25/dd97eb10c7369bb95de41d9195009e98_thumb.jpg",
        "images/2014-12-25/2c2e12d9525db33f27bdfb62421382d1_thumb.jpg"
    ],
    "uid": "0",
    "images_thumbs": [
        "http://cdn.fruitday.com/images/2014-12-25/ef60f39066940bdf8184896b1d23b9ad_thumb.jpg",
        "http://cdn.fruitday.com/images/2014-12-25/dd97eb10c7369bb95de41d9195009e98_thumb.jpg",
        "http://cdn.fruitday.com/images/2014-12-25/2c2e12d9525db33f27bdfb62421382d1_thumb.jpg"
    ],
    "username": "果食编辑部",
    "userface": "http://cdn.fruitday.com/up_images/default_userpic.png",
    'userrank':5,
    "topic_id": "",
    "topic_title": "",
    "share_url": "http://www.fruitday.com/web/fruitshare/38",
    "stime": 1425373098,
    "comment_num": 0,
    "worth_num": 0,
    "is_liked": 0,
    "can_delete": false
}
```

###<a name="fruit.getArtCommentList">获取文章评论列表 `fruit.getArtCommentList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | aid| int | Y | 文章id | 37 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |

* 接口返回
  - app返回示例
```json
[
    {
        "ctime": "1425372503",
        "content": "同上",
        "id": "61",
        "username": "果食编辑部",
        "userface": "http://cdn.fruitday.com/up_images/default_userpic.png",
        'userrank':5,
        "stime": 1425373735,
        "is_replay": 0,
        "replay": [],
        #下面是兼容安卓
        "replay_uid": "",
        "replay_username": "",
        "replay_content": ""
    },
    {
        "ctime": "1425372502",
        "content": "同上",
        "id": "60",
        "username": "果食编辑部",
        "userface": "http://cdn.fruitday.com/up_images/default_userpic.png",
        'userrank':5,
        "stime": 1425373735,
        "is_replay": 0,
        "replay": [],
        #下面是兼容安卓
        "replay_uid": "",
        "replay_username": "",
        "replay_content": ""
    }
]
```

###<a name="fruit.getFruitUserInfo">获取用户果食信息 `fruit.getFruitUserInfo`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |

* 接口返回
  - app返回示例
```json
{
    "username": "test1",
    "userface": "http://apicdn.fruitday.com/img/up_images/avatar/281873/58626bc681b1ecf629c34a09503db8bb_130.jpg",
    "cartnums": "1",
    "wartnums": "0",
    "uartnums": "6",
}
```

###<a name="fruit.getUserArticleList">获取用户果食文章列表 `fruit.getUserArticleList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |

* 接口返回
  - app返回示例
```json
[
    {
        "id": "38",
        "ctime": "1419497388",
        "description": "sdasd",
        "photo": [
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/ef60f39066940bdf8184896b1d23b9ad.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/dd97eb10c7369bb95de41d9195009e98.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/2c2e12d9525db33f27bdfb62421382d1.jpg"
        ],
        "type": "1",
        "ptype": "0",
        "content": "",
        "uid": "281873",
        "state":"1",//0-未通过1-通过审核
        "images_thumbs": [
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/ef60f39066940bdf8184896b1d23b9ad_thumb.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/dd97eb10c7369bb95de41d9195009e98_thumb.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/2c2e12d9525db33f27bdfb62421382d1_thumb.jpg"
        ],
        "username": "\u679c\u98df\u7f16\u8f91\u90e8",
        "userface": "http:\/\/cdn.fruitday.com\/up_images\/default_userpic.png",
        "topic_id": "",
        "topic_title": "",
        "share_url": "http:\/\/www.fruitday.com\/web\/fruitshare\/38",
        "stime": 1425375002,
        "comment_num": 0,
        "worth_num": 0,
        "is_liked": 0,
        "can_delete": true
    },
    {
        "id": "37",
        "ctime": "1417499524",
        "description": "rew",
        "photo": "",
        "type": "0",
        "ptype": "1",
        "state":"1",//0-未通过1-通过审核
        "content": "<\/head>test
\t\t\t<\/p><\/body><\/html>",
        "uid": "281873",
        "images_thumbs": [

        ],
        "username": "\u679c\u98df\u7f16\u8f91\u90e8",
        "userface": "http:\/\/cdn.fruitday.com\/up_images\/default_userpic.png",
        "topic_id": "5",
        "topic_title": "",
        "share_url": "http:\/\/www.fruitday.com\/web\/fruitshare\/37",
        "stime": 1425375002,
        "comment_num": "4",
        "worth_num": 0,
        "is_liked": 0,
        "can_delete": true
    }
]
```

###<a name="fruit.getCommentArticleList">获取用户果食评论文章 `fruit.getCommentArticleList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |

* 接口返回
  - app返回示例
```json
[
    {
        "id": "37",
        "ctime": "1417499524",
        "description": "rew",
        "photo": "",
        "type": "0",
        "ptype": "1",
        "content": "<\/head>test
\t\t\t<\/p><\/body><\/html>",
        "uid": "281873",
        "images_thumbs": [

        ],
        "username": "\u679c\u98df\u7f16\u8f91\u90e8",
        "userface": "http:\/\/cdn.fruitday.com\/up_images\/default_userpic.png",
        "topic_id": "5",
        "topic_title": "",
        "share_url": "http:\/\/www.fruitday.com\/web\/fruitshare\/37",
        "stime": 1425375547,
        "comment_num": "4",
        "worth_num": 0,
        "is_liked": 0,
        "can_delete": true
    }
]
```

###<a name="fruit.getWorthArticleList">获取用户果食点赞文章 `fruit.getWorthArticleList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |

* 接口返回
  - app返回示例
```json
[
    {
        "id": "57",
        "ctime": "1425368540",
        "description": "test",
        "photo": [
            "http://apicdn.fruitday.com/img/images/2015-03-03/5023b2c3f1f8dbbe3c856abb763d3104.jpg",
            "http://apicdn.fruitday.com/img/images/2015-03-03/a6e00dfcc99fc9024540f1fc7b2661c2.jpg"
        ],
        "type": "0",
        "ptype": "0",
        "content": null,
        "uid": "281873",
        "images_thumbs": [
            "http://apicdn.fruitday.com/img/images/2015-03-03/5023b2c3f1f8dbbe3c856abb763d3104_thumb.jpg",
            "http://apicdn.fruitday.com/img/images/2015-03-03/a6e00dfcc99fc9024540f1fc7b2661c2_thumb.jpg"
        ],
        "username": "果食编辑部",
        "userface": "http://cdn.fruitday.com/up_images/default_userpic.png",
        "topic_id": "",
        "topic_title": "",
        "share_url": "http://www.fruitday.com/web/fruitshare/57",
        "stime": 1425375382,
        "comment_num": 0,
        "worth_num": "1",
        "is_liked": 1,
        "can_delete": true
    }
]
```

###<a name="fruit.listNotify">获取果食消息列表 `fruit.listNotify`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | state| int | N | 0-未读1-已读(不传则代表全部) | 1 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |

* 接口返回
  - app返回示例
```json
{
    "notify_num": "30",
    "notify_data": [
        {
            "id": "38",
            "aid": "37",
            "type": "2",//1-被赞,2-fruit被评论,3-被回复,4-bake被评论
            "state": "0",
            "ctime": "1425372503",
            "notify_info": {
                "uid": "281873",
                "username": "test1",
                "content": "同上"
            }
        },
        {
            "id": "37",
            "aid": "37",
            "type": "2",
            "state": "0",
            "ctime": "1425372502",
            "notify_info": {
                "uid": "281873",
                "username": "test1",
                "content": "同上"
            }
        }
    ]
}
```

###<a name="fruit.upNotify">已阅读果食消息 `fruit.upNotify`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | id| int | Y | 果食消息id | 38 |

* 接口返回
  - app返回示例
```json
{
    "code": "200",
    "msg": "更新成功"
}
```

- - -

##<a name="bake">百科

###<a name="bake.getSectionList">百科版块列表 `bake.getSectionList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | pid| int | N | 父级百科ID | 6 |
    | all| int | N | 0 返回下一级， 1 返回所有子集 | 1 |


* 接口返回
  - app返回示例
```json
[
    {
        "id": "5",
        "name": "xihuan",
        "photo": "",
        "num": "14",
        "son": [
            {
                "id": "51",
                "name": "ceshi",
                "photo": "",
                "num": "14",
            }
        ]
    },
    {
        "id": "4",
        "name": "test2",
        "photo": "http://cdn.fruitday.com/images/2014-12-26/70b654923e52801e8ff3f368a64f52b7.jpg",
        "num": "0",
        "son": []
    }
]
```

###<a name="bake.getArticleList">百科列表 `bake.getArticleList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | N | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | sec_id| int | N | 版块id | 1 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |
    | keyword| string | N | 关键词 | 榴莲 |

* 接口返回
  - app返回示例
```json
{
    "top": [
      {
            "id": "3",
            "title": "test",
            "summary": "",
            "content": "<html><head><meta content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;\" name=\"viewport\" /></head><body>this is a test</body></html>",
            "type": "1",
            "photo": "http://cdn.fruitday.com/images/fruits/app/25976101407998843.jpg",
            "images_thumbs": "http://cdn.fruitday.com/images/fruits/app/25976101407998843.jpg",
            "share_url": "http://www.fruitday.com/web/fruitshare/3",
            "stime": 1430705944,
            "comment_num": 0,
            "worth_num": 0,
            "collection_num": 0,
            "is_worth": 0,
            "is_collect": 0
        },
    ],
    "main": [
        {
            "id": "2",
            "title": "test",
            "summary": "",
            "content": "<html><head><meta content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;\" name=\"viewport\" /></head><body>this is a test</body></html>",
            "type": "0",
            "photo": "http://cdn.fruitday.com/images/fruits/app/25976101407998843.jpg",
            "images_thumbs": "http://cdn.fruitday.com/images/fruits/app/25976101407998843.jpg",
            "share_url": "http://www.fruitday.com/web/fruitshare/2",
            "stime": 1430705944,
            "comment_num": 0,
            "worth_num": 0,
            "collection_num": 0,
            "is_worth": 0,
            "is_collect": 0
        },
        {
            "id": "1",
            "title": "test",
            "summary": "",
            "content": "<html><head><meta content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;\" name=\"viewport\" /></head><body>this is a test</body></html>",
            "type": "0",
            "photo": "http://cdn.fruitday.com/images/fruits/app/25976101407998843.jpg",
            "images_thumbs": "http://cdn.fruitday.com/images/fruits/app/25976101407998843.jpg",
            "share_url": "http://www.fruitday.com/web/fruitshare/1",
            "stime": 1430705944,
            "comment_num": "5",
            "worth_num": 0,
            "collection_num": "1",
            "is_worth": 0,
            "is_collect": 0
        }
    ]
}
```

###<a name="bake.getDetailArticle">百科详情列表 `bake.getDetailArticle`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | N | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | id| int | Y | 文章id | 1 |

* 接口返回
  - app返回示例
```json
{
    "id": "1",
    "title": "test",
    "summary": "",
    "content": "<html><head><meta content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;\" name=\"viewport\" /></head><body>this is a test</body></html>",
    "type": "0",
    "photo": "http://cdn.fruitday.com/images/fruits/app/25976101407998843.jpg",
    "images_thumbs": "http://cdn.fruitday.com/images/fruits/app/25976101407998843.jpg",
    "share_url": "http://www.fruitday.com/web/fruitshare/1",
    "stime": 1430273514,
    "comment_num": 0,
    "worth_num": 0,
    "collection_num": 0,
    "is_worth": 0,
    "is_collect": 0
}
```

###<a name="bake.setWorth"> 设置点赞 `bake.setWorth`
* 业务入参
    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | aid| int | Y | 文章id | 1 |

* 接口返回
  - app返回示例
```json
{
    "type": 1//1-未喜欢0-喜欢
}
```
###<a name="bake.setCollect">设置收藏 `bake.setCollect`
* 业务入参
    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | aid| int | Y | 文章id | 1 |

* 接口返回
  - app返回示例
```json
{
    "type": 1//1-未收藏0-收藏
}
```

###<a name="bake.doComment">百科版块列表 `bake.doComment`
* 业务入参
    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | aid| int | Y | 文章id | 13 |
    | content| string | Y | 评论内容 | 同上 |
    | replay_id| int | N | 回复的评论id | 57 |
    | replay_uid| int | N | 回复的评论id | 215741 |
    | replay_username| int | N | 回复的评论id | 陈胖胖 |
    | replay_content| int | N | 回复的评论id | 喜欢这文章 |

* 接口返回
  - app返回示例
```json
{
    "ctime": "1430290053",
    "content": "test",
    "id": "5",
    "uid": "281873",
    "username": "test1",
    "userface": "http://apicdn.fruitday.com/img/up_images/avatar/281873/7de562ab3b59a07bb7896221aab8b9e2_130.jpg",
    "stime": 1430290053,
    "is_replay": 1,
    "replay": {
        "uid": "0",
        "username": 果食编辑部,
        "content": "test"
    }
}
```

###<a name="bake.getArtCommentList">百科评论列表 `bake.getArtCommentList`
* 业务入参
    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | aid| int | Y | 文章id | 13 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |

* 接口返回
  - app返回示例
```json
[
    {
        "ctime": "1430290053",
        "content": "test",
        "id": "5",
        "uid": "281873",
        "username": "test1",
        "userface": "http://apicdn.fruitday.com/img/up_images/avatar/281873/7de562ab3b59a07bb7896221aab8b9e2_130.jpg",
        "userrank": 0,
        "stime": 1430377216,
        "is_replay": 1,
        "replay": {
            "uid": "0",
            "username": "果食编辑部",
            "content": "test"
        }
    },
    {
        "ctime": "1430289958",
        "content": "test",
        "id": "4",
        "uid": "281873",
        "username": "test1",
        "userface": "http://apicdn.fruitday.com/img/up_images/avatar/281873/7de562ab3b59a07bb7896221aab8b9e2_130.jpg",
        "userrank": 0,
        "stime": 1430377216,
        "is_replay": 1,
        "replay": {
            "uid": "0",
            "username": "果食编辑部",
            "content": "test"
        }
    },
    {
        "ctime": "1430289927",
        "content": "test",
        "id": "3",
        "uid": "281873",
        "username": "test1",
        "userface": "http://apicdn.fruitday.com/img/up_images/avatar/281873/7de562ab3b59a07bb7896221aab8b9e2_130.jpg",
        "userrank": 0,
        "stime": 1430377216,
        "is_replay": 0,
        "replay": []
    },
    {
        "ctime": "1430289860",
        "content": "test",
        "id": "2",
        "uid": "281873",
        "username": "test1",
        "userface": "http://apicdn.fruitday.com/img/up_images/avatar/281873/7de562ab3b59a07bb7896221aab8b9e2_130.jpg",
        "userrank": 0,
        "stime": 1430377216,
        "is_replay": 0,
        "replay": []
    },
    {
        "ctime": "1430289773",
        "content": "",
        "id": "1",
        "uid": "281873",
        "username": "test1",
        "userface": "http://apicdn.fruitday.com/img/up_images/avatar/281873/7de562ab3b59a07bb7896221aab8b9e2_130.jpg",
        "userrank": 0,
        "stime": 1430377216,
        "is_replay": 0,
        "replay": []
    }
]
```

###<a name="bake.getSearchTagList">百科搜索关键词列表 `bake.getSearchTagList`
* 业务入参
    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|

* 接口返回
  - app返回示例
```json
[
    {
        "name": "test"
    },
    {
        "name": "test2"
    }
]
```
- - -

##<a name="snscenter">百科果食用户中心

###<a name="snscenter.getUserInfo">用户中心 `snscenter.getUserInfo`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | uid | int | N | 用户id | 45468 |

* 接口返回
  - app返回示例
```json
{
    "username": "test1",
    "userface": "http://apicdn.fruitday.com/img/up_images/avatar/281873/58626bc681b1ecf629c34a09503db8bb_130.jpg",
    "cartnums": "1",//文章评论 warning:如果uid是登陆用户则显示
    "ufruitnums": "0",//用户发表社区文章 warning:如果uid是登陆用户则显示
    "cbakenums": "6",//百科收藏文章 warning:如果uid是登陆用户则显示
}
```

###<a name="snscenter.getUserArticleList">获取用户果食文章列表 `snscenter.getUserArticleList`
* 业务入参
    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | uid | int | N | 用户id(查看别的用户文章) | 45468 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |

* 接口返回
  - app返回示例
```json
[
    {
        "id": "38",
        "ctime": "1419497388",
        "description": "sdasd",
        "photo": [
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/ef60f39066940bdf8184896b1d23b9ad.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/dd97eb10c7369bb95de41d9195009e98.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/2c2e12d9525db33f27bdfb62421382d1.jpg"
        ],
        "type": "1",
        "state":"1",//0-未通过1-通过审核
        "ptype": "0",
        "content": "",
        "uid": "281873",
        "images_thumbs": [
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/ef60f39066940bdf8184896b1d23b9ad_thumb.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/dd97eb10c7369bb95de41d9195009e98_thumb.jpg",
            "http:\/\/cdn.fruitday.com\/images\/2014-12-25\/2c2e12d9525db33f27bdfb62421382d1_thumb.jpg"
        ],
        "username": "\u679c\u98df\u7f16\u8f91\u90e8",
        "userface": "http:\/\/cdn.fruitday.com\/up_images\/default_userpic.png",
        "topic_id": "",
        "topic_title": "",
        "share_url": "http:\/\/www.fruitday.com\/web\/fruitshare\/38",
        "stime": 1425375002,
        "comment_num": 0,
        "worth_num": 0,
        "is_liked": 0,
        "can_delete": true
    },
    {
        "id": "37",
        "ctime": "1417499524",
        "description": "rew",
        "photo": "",
        "type": "0",
        "state":"1",//0-未通过1-通过审核
        "ptype": "1",
        "content": "<\/head>test
\t\t\t<\/p><\/body><\/html>",
        "uid": "281873",
        "images_thumbs": [

        ],
        "username": "\u679c\u98df\u7f16\u8f91\u90e8",
        "userface": "http:\/\/cdn.fruitday.com\/up_images\/default_userpic.png",
        "topic_id": "5",
        "topic_title": "",
        "share_url": "http:\/\/www.fruitday.com\/web\/fruitshare\/37",
        "stime": 1425375002,
        "comment_num": "4",
        "worth_num": 0,
        "is_liked": 0,
        "can_delete": true
    }
]
```

###<a name="snscenter.getMaxCommentArticleList">获取用户果食评论文章 `snscenter.getMaxCommentArticleList`
* 业务入参
    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |

* 接口返回
  - app返回示例
```json
[
    {
        "id": "4",
        "ctime": "1430289958",
        "aid": "1",
        "type": "2",
        "content": "test",
        "stime": 1431594328,
        "is_replay": 1,
        "replay": {
            "uid": "0",
            "username": "水果君",
            "content": "test"
        },
        "article": {
            "id": "1",
            "title": "天天果园获第十届艾瑞金瑞奖“最佳创新奖”",
            "summary": "[ 本文内容源自网易新闻 ] 中国互联网及移动互联网领域最权威的奖项之一，2015年第10届金瑞奖(iResearchAwards)颁奖典礼昨日(4月15日)在北京国家会议中心落下帷幕。",
            "content": "<html><head><meta content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;\" name=\"viewport\" /></head><body><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; text-align: center; background-color: rgb(249, 249, 249);\">&nbsp;[ 本文内容源自网易新闻 ]</p><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; text-align: center; background-color: rgb(249, 249, 249);\"><a href=\"http://blog.fruitday.com/wp-content/uploads/2015/04/1429150621308.jpg\" style=\"margin: 0px; padding: 0px; border: 0px; font-weight: inherit; font-style: inherit; font-family: inherit; vertical-align: baseline; outline: none; -webkit-transition: all 0.1s ease-in; transition: all 0.1s ease-in; color: rgb(122, 156, 173); text-decoration: underline; background-image: initial; background-attachment: initial; background-size: initial; background-origin: initial; background-clip: initial; background-position: initial; background-repeat: initial;\"><img class=\"aligncenter size-full wp-image-4026 img-responsive\" alt=\"1429150621308\" src=\"http://blog.fruitday.com/wp-content/uploads/2015/04/1429150621308.jpg\" width=\"530\" height=\"332\" style=\"margin: 5px auto 10px; padding: 5px; border: 1px solid rgba(255, 255, 255, 0.952941); font-weight: inherit; font-style: inherit; font-family: inherit; clear: both; box-shadow: rgba(0, 0, 0, 0.0470588) 0px 3px 3px; background: rgba(255, 255, 255, 0.8);\"/></a></p><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; background-color: rgb(249, 249, 249);\">中国互联网及移动互联网领域最权威的奖项之一，2015年第10届金瑞奖(iResearchAwards)颁奖典礼昨日(4月15日)在北京国家会议中心落下帷幕。国内最大的水果生鲜电商—天天果园获得2015年度艾瑞金瑞奖的“最佳创新奖”。</p><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; background-color: rgb(249, 249, 249);\">金瑞奖由国内权威的研究咨询服务机构艾瑞集团主办，从2006年开始已经连续举办了10届。艾瑞金瑞奖设立的初衷是表彰中国互联网及移动互联网 领域有突出表现的创新产品、软件服务、网络应用，以及创新领袖人物。经过10年的积累，金瑞奖已经成为中国互联网界一块颇具含金量的招牌，并且由于该评选 以挖掘最具发展前景的互联网/移动互联网产品和企业而著名，因此一直深受互联网行业和投资界的重视。</p><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; background-color: rgb(249, 249, 249);\">2015年度的艾瑞金瑞奖主要分为三大类奖项：最佳创新奖、最佳成长奖和最佳影响力奖。其中最佳创新奖的评选标准是“整体增长较快且模式较新的 产品”，在评选过程中严格参考用户规模及增长率等指标，并对业内最权威的专家进行定性访谈，最终确定获奖名单。天天果园APP凭借在2014年度的颠覆性 创新、用户同比增长率达三位数等硬性指标，展示出广阔的发展前景，最终从数百个入围企业中脱颖而出获选。</p><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; background-color: rgb(249, 249, 249);\">天天果园成立于2009年，是国内最大的水果生鲜电商，通过线上渠道提供高品质鲜果商品和个性化鲜果服 务。目前天天果园在上海、北京、广州、深圳、杭州、成都拥有6大仓库，业务遍及全国300多个城市，2014年销售额突破5亿元、同比增长 150%，2015年1月整体销售额已过亿，被视为最具创新力和未来发展潜力的水果生鲜电商品牌。</p><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; background-color: rgb(249, 249, 249);\">天天果园APP界面简洁、操作便捷，其研发团队的目标是让用户“在等一个红绿灯的时间就能下好一单”。除了基本的购买功能，天天果园APP还推 出了“手机专享商品”“摇一摇送福利”、“免费试吃”、“评价商品赚积分”等用户福利，用户确认收获后还可参与“物流服务调查问卷”，直接监督和反馈配送 服务质量，真正做到了“以用户为中心”,这也正是艾瑞金瑞奖“最佳创新奖”所推崇的。</p><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; background-color: rgb(249, 249, 249);\">2014年，天天果园登顶苹果App Store分类榜榜首，并多次名列热搜榜单。</p><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; background-color: rgb(249, 249, 249);\">过去几年里，获得过金瑞奖“最佳创新奖”的企业和产品包括小米科技、唯品会、汽车之家、58同城、360搜索、迅雷、百度影音等，在各自的行业 内均处于最顶尖和领衔的地位。今年与天天果园同时入选2015年度艾瑞金瑞奖“最佳创新奖”的企业还包括饿了么、今日头条、易到用车、春雨医生等。</p><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; background-color: rgb(249, 249, 249);\">———————————————————————————————————————</p><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; background-color: rgb(249, 249, 249);\">[ 以上图片及内容源自网易新闻 ]</p><p style=\"margin-top: 0px; margin-bottom: 10px; padding: 0px; border: 0px; font-size: 14px; font-family: Roboto; vertical-align: baseline; line-height: normal; white-space: normal; background-color: rgb(249, 249, 249);\">[欢迎分享，请勿转载。]</p><p><br/></p></body></html>",
            "type": "1",
            "photo": "http://cdn.fruitday.com/images/2015-05-08/388a6dc771420317f750e130f36efcf7.jpg",
            "sec_id": "8",
            "is_recommend": "1",
            "images_thumbs": "http://cdn.fruitday.com/images/2015-05-08/388a6dc771420317f750e130f36efcf7_thumb.jpg",
            "share_url": "http://www.fruitday.com/web/fruitshare/1",
            "stime": 1431594328,
            "comment_num": "6",
            "worth_num": 0,
            "collection_num": "1",
            "is_worth": 0,
            "is_collect": 0,
            "sec_name": null,
            "sec_photo": null,
            "ptype": 3
        }
    },
    {
        "id": "26",
        "ctime": "1415067957",
        "aid": "23",
        "type": "1",
        "content": "我们已经关注",
        "stime": 1431594328,
        "is_replay": 1,
        "replay": {
            "uid": 259761,
            "username": "test",
            "content": "评论"
        },
        "article": {
            "id": "23",
            "ctime": "1408559893",
            "description": "描述………………………",
            "photo": [
                "http://cdn.fruitday.com/images/2014-08-21/images/2014-08-21/1408559893_app.jpg"
            ],
            "type": "1",
            "ptype": "0",
            "content": null,
            "thumbs": null,
            "uid": "281873",
            "username": "test1",
            "userrank": 0,
            "userface": "http://apicdn.fruitday.com/img/up_images/avatar/281873/7de562ab3b59a07bb7896221aab8b9e2_130.jpg",
            "topic_id": "5",
            "topic_title": "xihuan",
            "share_url": "http://www.fruitday.com/web/fruitshare/23",
            "stime": 1431594328,
            "comment_num": "8",
            "worth_num": 0,
            "is_liked": 0,
            "can_delete": true
        }
    }
]
```

###<a name="snscenter.getCollectArticleList">用户收藏 `snscenter.getCollectArticleList`
* 业务入参
    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |

* 接口返回
  - app返回示例
```json
[
    {
        "id": "1",
        "title": "test",
        "summary": "",
        "content": "<html><head><meta content=\"width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0;\" name=\"viewport\" /></head><body>this is a test</body></html>",
        "type": "0",
        "photo": "http://cdn.fruitday.com/images/fruits/app/25976101407998843.jpg",
        "images_thumbs": "http://cdn.fruitday.com/images/fruits/app/25976101407998843.jpg",
        "share_url": "http://www.fruitday.com/web/fruitshare/1",
        "stime": 1430378642,
        "comment_num": "5",
        "worth_num": 0,
        "collection_num": "1",
        "is_worth": 0,
        "is_collect": 1,
        "ctime":"1430378642"
    }
]
```

  ###<a name="snscenter.listNotify">获取果食消息列表 `snscenter.listNotify`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | state| int | N | 0-未读1-已读(不传则代表全部) | 1 |
    | page| int | N | 当前页 | 1 |
    | limit| int | N | 一页几条 | 10 |

* 接口返回
  - app返回示例
```json
{
    "notify_num": "30",
    "notify_data": [
        {
            "id": "38",
            "aid": "37",
            "type": "2",//1-被赞,2-fruit被评论,3-被回复,4-bake被评论
            "state": "0",
            "ctime": "1425372503",
            "notify_info": {
                "uid": "281873",
                "username": "test1",
                "content": "同上"
            }
        },
        {
            "id": "37",
            "aid": "37",
            "type": "2",
            "state": "0",
            "ctime": "1425372502",
            "notify_info": {
                "uid": "281873",
                "username": "test1",
                "content": "同上"
            }
        }
    ]
}
```

###<a name="snscenter.upNotify">已阅读果食消息 `snscenter.upNotify`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 登录标识 | 7b5982f5747672d329d98352e2556f39 |
    | id| int | N | 果食消息id(warning:为空则已读所有) | 38 |

* 接口返回
  - app返回示例
```json
{
    "code": "200",
    "msg": "更新成功"
}
```
- - -

##<a name="foretaste">试吃

###<a name="foretaste.getCurList">获取当前正在进去中的试吃 `foretaste.getCurList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | type | int | Y | 列表类型(1-已开始2-已结束) | 1 |
    | page_no    |    int  |       N      |  分页页数  |   1  |
    | page_size    |    int  |       N      |  每页个数  |    20  |

* 接口返回
  - app返回示例
```json
{
    "status": "succ",
    "code": 200,
    "data": [
        {
            "periods": "1",
            "id": "1",
            "name": "测试",
            "end_time": "2015-03-31 00:00:00",
            "start_time": "2015-03-08 00:00:00",
            "quantity": "50",
            "applycount": "0",
            "answer_url": "http://www.fruitday.com/web/pro/5792",
            "pro_url": "http://www.fruitday.com/web/pro/5792",
            "product": {
                "name": "佳沛意大利金奇异果",
                "photo": "http://cdn.fruitday.com/product_pic/2199/1/1-370x370-2199.jpg",
                "desc": "...",
                "price": "298",
                "product_no": "20149858",
                "id": "2199",
                "detail_place": "63",
                "summary": "...",
                "share_url": "http://www.fruitday.com/foretaste/share/1"
            }
        }
    ],
    "totalResult": 1
}
```

###<a name="foretaste.getDetail">获取试吃明细 `foretaste.getDetail`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | N | 用户标识 | 67d7300709c0b87ac8eff886bc4b2098 |

* 接口返回
  - app返回示例
```json
{
    "status": "succ",
    "code": 200,
    "data": {
        "setting": [],
        "product": {
            "photo": [
                {
                    "photo": "http://cdn.fruitday.com/product_pic/2199/1/1-370x370-2199.jpg",
                    "thum_photo": "http://cdn.fruitday.com/product_pic/2199/1/1-180x180-2199.jpg"
                },
                {
                    "photo": "http://cdn.fruitday.com/product_pic/2199/4/4-370x370-2199.jpg",
                    "thum_photo": "http://cdn.fruitday.com/product_pic/2199/4/4-180x180-2199.jpg"
                },
                {
                    "photo": "http://cdn.fruitday.com/product_pic/2199/3/3-370x370-2199.jpg",
                    "thum_photo": "http://cdn.fruitday.com/product_pic/2199/3/3-180x180-2199.jpg"
                }
            ],
            "productId": "2199",
            "name": "佳沛意大利金奇异果",
            "price": "298",
            "product_no": "20149858",
            "unit": "盒",
            "volume": "36个装",
            "summary": "<p><span style=\"font-size: 13px\"><span style=\"font-family: 宋体\">佳沛意大利金奇异果，</span><span style=\"font-family: 宋体\">金色的果肉更软糯，果香更醇厚，甜蜜</span><span style=\"font-family: 宋体\">又</span><span style=\"font-family: 宋体\">多汁</span><span style=\"font-family: 宋体\">。货源稀少，限量供应。</span></span></p>\r\n<p>单个重量约83-94克</p>\r\n<p class=\"p0\" style=\"margin-top: 0pt; margin-bottom: 0pt\"><span style=\"font-family: '宋体'; font-size: 10.5pt; mso-spacerun: 'yes'\"><o:p></o:p></span></p>",
            "discription": "<p><img width=\"789\" height=\"427\" src=\"http://cdn.fruitday.com/up_images/1392886859.jpg\" alt=\"\" /><img width=\"789\" height=\"501\" src=\"http://cdn.fruitday.com/up_images/1392886877.jpg\" alt=\"\" /><img width=\"788\" height=\"500\" alt=\"\" data-pinit=\"registered\" src=\"http://cdn.fruitday.com/up_images/1390910037.jpg\" /><img width=\"788\" height=\"500\" alt=\"\" data-pinit=\"registered\" src=\"http://cdn.fruitday.com/up_images/1390910048.jpg\" /><img width=\"788\" height=\"500\" alt=\"\" data-pinit=\"registered\" src=\"http://cdn.fruitday.com/up_images/1390910057.jpg\" /><img width=\"788\" height=\"500\" alt=\"\" data-pinit=\"registered\" src=\"http://cdn.fruitday.com/up_images/1390910067.jpg\" /><img width=\"788\" height=\"500\" alt=\"\" data-pinit=\"registered\" src=\"http://cdn.fruitday.com/up_images/1390910077.jpg\" /><img width=\"788\" height=\"625\" alt=\"\" data-pinit=\"registered\" src=\"http://cdn.fruitday.com/up_images/1390910086.jpg\" /></p>",
            "op_weight": ""
        },
        "id": "1",
        "name": "测试",
        "end_time": "2015-03-31 00:00:00",
        "start_time": "2015-03-08 00:00:00",
        "quantity": "50",
        "applycount": "0",
        "share_url": "http://www.fruitday.com/foretaste/share/1",
        "curr_time": "2015-03-10 12:17:14",
        "periods": "1",
        "addr_list": [
            {
                "id": "243295",
                "uid": "508825",
                "name": "test",
                "province": {
                    "id": "144045",
                    "name": "吉林"
                },
                "city": {
                    "id": "144046",
                    "name": "长春市"
                },
                "area": {
                    "id": "144211",
                    "name": "农安县"
                },
                "address": "test",
                "telephone": "",
                "mobile": "18721338269",
                "flag": "",
                "isDefault": "1"
            }
        ]
    }
}
```

###<a name="foretaste.doApply">试吃申请提交 `foretaste.doApply`
* 业务入参

      - 有地址时

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 用户标识 | 67d7300709c0b87ac8eff886bc4b2098 |
    | foretaste_id    |    int  |       Y      |  试吃商品  |   1  |
    | addr_id    |    int  |       Y      |  地址di  |    243265  |

     - 新增地址

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 用户标识 | 67d7300709c0b87ac8eff886bc4b2098 |
    | foretaste_id    |    int  |       Y      |  试吃商品  |   1  |
    | province    |    int  |       Y      |  省  |    106092  |
    | city    |    int  |       Y      |  市  |    106093  |
    | area    |    int  |       Y      |  区  |    106095  |
    | address    |    string  |       Y      |  详细地址  |    康衫路488号  |
    | name    |    string  |       N      |  用户名  |    xxxx  |
    | telephone    |    string  |       N      |  电话  |    021xxxxxx  |
    | mobile    |    int  |       N      |  手机号  |    187xxxxxx  |
* 接口返回
  - app返回示例
```json
{
    "code": "200",
    "msg": "申请成功，等待审核"
}
```

###<a name="foretaste.checkApply">验证是否已申请过 `foretaste.checkApply`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 用户标识 | 67d7300709c0b87ac8eff886bc4b2098 |
    | foretaste_id    |    int  |       Y      |  试吃商品  |   1  |
* 接口返回
  - app返回示例
```json
{
    "status": "succ",
    "code": 200,
    "data": {
        "has_apply": "true"
    }
}
```

###<a name="foretaste.ownerApply">个人试吃申请集合 `foretaste.ownerApply`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 用户标识 | 67d7300709c0b87ac8eff886bc4b2098 |
    | page_no    |    int  |       N      |  分页页数  |   1  |
    | page_size    |    int  |       N      |  每页个数  |    20  |

* 接口返回
  - app返回示例
```json
{
    "status": "succ",
    "code": "200",
    "data": [
        {
            "name": "测试",
            "periods": "1",
            "type": "free",
            "apply_time": "2015-03-10 13:10:50",
            "apply_status": "0",
            "has_comment": "0",
            "foretaste_goods_id": "1",
            "id": "558355",
            "product": {
                "id": "2199",
                "name": "佳沛意大利金奇异果",
                "photo": "http://cdn.fruitday.com/product_pic/2199/1/1-370x370-2199.jpg"
            }
        }
    ],
    "totalResult": 1
}
```

###<a name="foretaste.getCommentList">获取试吃报告 `foretaste.getCommentList`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | foretaste_goods_id    |    int  |       Y      |  试吃商品  |   1  |
    | page_no    |    int  |       N      |  分页页数  |   1  |
    | page_size    |    int  |       N      |  每页个数  |    20  |

* 接口返回
  - app返回示例
```json
{
    "status": "succ",
    "code": "200",
    "data": [
        {
            "foretaste": {
                "name": "测试",
                "id": "1"
            },
            "userinfo": {
                "username": "test1",
                "mobile": "187***8269",
                "id": "281873",
                "userface": "http://cdn.fruitday.com/up_images/default_userpic.png",
            },
            "meminfo": "test",
            "content": "test",
            "title": "test",
            "apply_id": "558355",
            "rank": "5",
            "createtime": "2015-03-10 15:27:03",
            "pic_urls": [
                "http://apicdn.fruitday.com/img/images/2015-03-10/c78dca6597827292d803983912da39dc.jpg",
                "http://apicdn.fruitday.com/img/images/2015-03-10/e2a56585241d998ad277f89102d7eb4f.jpg"
            ],
            "id": "784"
        }
    ],
    "totalResult": 1
}
```

###<a name="foretaste.doComment">填写试吃报告 `foretaste.doComment`
* 业务入参

    | 参数名   | 类型 | 必须 | 说明 | 示例 |
    |--------|--------|--------|--------|--------|
    | connect_id | string | Y | 用户标识 | 67d7300709c0b87ac8eff886bc4b2098 |
    | apply_id    |    int  |       Y      |  试吃申请id  |   558355  |
    | title    |    string  |       Y      |  标题  |    test  |
    | content    |    string  |       Y      |  内容  |    test  |
    | meminfo    |    string  |       Y      |  个人信息  |    test  |
    | rank    |    int  |       Y      |  等级  |    test  |
    | photo[1-3]    |    obj  |       Y      |  图片资源(上传1-3张图片:模拟表单提交)    |    obj  |

* 接口返回
  - app返回示例
```json
{
    "status": "succ",
    "code": "200"
}
```