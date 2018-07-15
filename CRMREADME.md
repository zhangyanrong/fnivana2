


##目录
 * [用户](#user)
   - [获取用户信息 `crm.getUserInfo`](#crm.getUserInfo)
   - [获取最新信息 `crm.getMessage`] (#crm.getMessage)
   - [清空信息记录 `crm.delMessage`] (#crm.delMessage)
   - [充值交易 `crm.tradeList`] (#crm.tradeList)
   - [提货券订单 `crm.cardOrder`] (#crm.cardOrder)
   - [积分查询 `crm.userJf`] (#crm.userJf)
   - [提货券查询 `crm.tiHuoQuan`] (#crm.tiHuoQuan)
   - [充值卡查询 `crm.chargeCard`] (#crm.chargeCard)
   - [优惠券作废 `crm.unSent`] (#crm.unSent)
   - [订单查询 `crm.orderList`] (#crm.orderList)
   - [添加优惠券 `crm.saveCard`] (#crm.saveCard)
   - [扣除用户积分 `crm.operateJf`] (#crm.operateJf)
   - [优惠券查询 `crm.selectCard`] (#crm.selectCard)
   - [置赠品为已领取 `crm.resetPro`] (#crm.resetPro)
   - [置赠品为未领取 `crm.resetProUnused`] (#crm.resetProUnused)
   - [手动同步oms失败订单 `crm.selfOmsOrder`] (#crm.selfOmsOrder)
   - [推送CRM申诉列表 `crm.orderComplaints`] (#crm.orderComplaints)
   - [取消团购挂起的订单 `crm.cancelOrder`] (#crm.cancelOrder)
   - [订单详情 `crm.orderDetail`] (#crm.orderDetail)
   - [推送申诉反馈到CRM `crm.receiveFeedback`] (#crm.receiveFeedback)
   - [运费计算 `crm.getPostFree`] (#crm.getPostFree)
   - [大客户收款 `user.getComplanyService`] (#user.getComplanyService)



##系统约定

###api地址
http://nirvana.fruitday.com/crmApi

###密钥
56b44d6cd9b7f902ef36f1f0c1dac79f

###签名方法
```php
    function create_sign($params){
        ksort($params);//以键升序排列
        $query = "";
        foreach($params as $k=>$v){
            $query .= $k."=".$v."&";
        }//拼接成get字符串
        $sign = md5(substr(md5($query.CRM_SECRET), 0,-1)."w");
        //字符串拼接密钥后md5加密,      去处最后一位再拼接"w"，再md5加密
        return $sign;
    }

###系统级入参
|     参数名    |     类型      |      必须   |     说明       |
| ------------ | ------------- | ------------ | ------------   |
| timestamp    |     int       |       Y      |   unix时间戳   |
| service      |     string    |       Y      |   请求接口名称 |
| sign 		   |     string    |       Y      |    签名        |

###状态码定义
|     参数名    |     说明       |
| ------------  | -------------  |
| 200           |     请求成功   |
| 300           |     业务错误   |
| 500           |     系统错误   |

##<a name="user"/>用户
###<a name="crm.getUserInfo"/>获取用户信息 `crm.getUserInfo`

* 业务入参
|     参数名    |     类型      |      必须     |     说明     |
| ------------  | ------------- | ------------  | ------------ |
| mobile        | varchar       |       Y       | 手机号码     |
| func

* 接口返回
```json
{
	"code" : "200"
	"msg"  : "获取用户信息成功"
	"data":
		[
			{   "id" : 718141 								#用户自增id
				"reg_time" : "2014-01-09 12:23:34"          #用户注册时间
				"username" : "BILL"                         #用户别名
				"money" : 78.00								#用户余额
				"jf" : 100									#用户积分
				"user_rank" :  "鲜果达人V3"					#用户等级
				"next_rank_name" : "鲜果达人V4"				#用户升级的下一个等级
				"diff_ordernum"	: "9"						#升级下一等级还差的订单数
				"diff_ordermoney" : "20"
				"card":										#用户卡券
				[
					{
						"id" : 1							  #卡号自增id
						"uid" : 718141                        #用户id
						"card_number" : "mh7062692250518"	  #卡号
						"card_money" : "70.00"				  #卡金额
						"is_used" : 1                         #1代表已使用，0代表未使用
						"remarks" : "717生鲜大趴红包"		  #说明
						"time" ： "2015-07-01"				  #有效期开始时间
						"to_date" : "2015-07-16"			  #有效期结束时间
						"order_money_limit"					  #订单金额限制
						"product_id" 						  #限制商品id,为空代表不限制商品，不为空代表限定商品
						"channel"							  #使用渠道  为空时代表全站通用，不为空时看channel_web，channel_app，channel_wap
						"channel_web"						  #使用渠道  为1代表pc web可用， 0代表pc web不可用
						"channel_app"  						  #使用渠道  为1代表 app 可用， 0代表app 不可用
						"channel_wap"						  #使用渠道  为1代表 wap 可用， 0代表wap 不可用
					},
					{
						"id" : 2							  #卡号自增id
						"uid" : 718141						  #用户id
						"card_number" : "mh7062692250517"	  #卡号
						"card_money" : "80.00"				  #卡金额
						"is_used" : 1                         #1代表已使用，0代表未使用
						"remarks" : "仅限app购买美国西北樱桃一斤装使用"   #说明
						"time" ： "2015-09-01"				  #有效期开始时间
						"to_date" : "2015-09-10"			  #有效期开始时间
						"order_money_limit"					  #订单金额限制
						"product_id" 						  #限制商品id,为空代表不限制商品，不为空代表限定商品
						"channel"							  #使用渠道  为空时代表全站通用，不为空时看channel_web，channel_app，channel_wap
						"channel_web"						  #使用渠道  为1代表pc web可用， 0代表pc web不可用
						"channel_app"  						  #使用渠道  为1代表 app 可用， 0代表app 不可用
						"channel_wap"						  #使用渠道  为1代表 wap 可用， 0代表wap 不可用
					}
				]
				"gift" :[
					{
						"bonus_order" :	"15010472491"		#订单号，为空表示赠品未领取
						"active_type" 1 					#1表示充值赠品，2表示营销赠品
						"active_id" :1134					#用作重置赠品接口的传参
						"product_id" : "63290"				#商品id
						"gift_source" :"帐户余额充值赠品"	#赠品说明
						"start_time" : "2015-10-29 14:53:26" #赠品开始有效期
						"end_time" : "2015-10-31 14:53:26" #赠品结束有效期
						"has_rec" : 1						#1表示已领取，0表示未领取
						"product_name" : "国产蜜桔（充值后2周内有效）" #赠品名称
						"price"	: "29"						#赠品价格
						"gg_name" : "2斤装/斤"				#赠品规格
					}
				]
			},
			{   "id" : 7181415 								#用户自增id
				"reg_time" : "2014-01-09 12:23:34"          #用户注册时间
				"username" : "BILL"                         #用户别名
				"money" : 787.00								#用户余额
				"jf" : 1007									#用户积分
				"user_rank" :  "鲜果达人V6"					#用户等级
				"card":										#用户卡券
				[
					{
						"id" : 1							  #卡号自增id
						"uid" : 718141                        #用户id
						"card_number" : "mh7062692250518"	  #卡号
						"card_money" : "70.00"				  #卡金额
						"is_used" : 1                         #1代表已使用，0代表未使用
						"remarks" : "717生鲜大趴红包"		  #说明
						"time" ： "2015-07-01"				  #有效期开始时间
						"to_date" : "2015-07-16"			  #有效期结束时间
						"order_money_limit"					  #订单金额限制
						"product_id" 						  #限制商品id,为空代表不限制商品，不为空代表限定商品
						"channel"							  #使用渠道  为空时代表全站通用，不为空时看channel_web，channel_app，channel_wap
						"channel_web"						  #使用渠道  为1代表pc web可用， 0代表pc web不可用
						"channel_app"  						  #使用渠道  为1代表 app 可用， 0代表app 不可用
						"channel_wap"						  #使用渠道  为1代表 wap 可用， 0代表wap 不可用
					},
					{
						"id" : 2							  #卡号自增id
						"uid" : 718141						  #用户id
						"card_number" : "mh7062692250517"	  #卡号
						"card_money" : "80.00"				  #卡金额
						"is_used" : 1                         #1代表已使用，0代表未使用
						"remarks" : "仅限app购买美国西北樱桃一斤装使用"   #说明
						"time" ： "2015-09-01"				  #有效期开始时间
						"to_date" : "2015-09-10"			  #有效期开始时间
						"order_money_limit"					  #订单金额限制
						"product_id" 						  #限制商品id,为空代表不限制商品，不为空代表限定商品
						"channel"							  #使用渠道  为空时代表全站通用，不为空时看channel_web，channel_app，channel_wap
						"channel_web"						  #使用渠道  为1代表pc web可用， 0代表pc web不可用
						"channel_app"  						  #使用渠道  为1代表 app 可用， 0代表app 不可用
						"channel_wap"						  #使用渠道  为1代表 wap 可用， 0代表wap 不可用
					}
				]
			}
		]

}

##<a name="message"/>获取信息
###<a name="crm.getMessage"/>获取最新信息 `crm.getMessage`

* 业务入参
|     参数名    |     类型      |      必须     |     说明     |
| ------------  | ------------- | ------------  | ------------ |
| mobile        | varchar       |       Y       | 手机号码     |
*接口返回
```json
{
	"code" : "200"
	"msg"  : "获取用户信息成功"
	"data":{
			"content" : "568789"					#信息内容
			"create_time" : "2015-09-24 13:07:54"	#收到信息的时间
	}
}

##<a name="del"/>清空信息记录
###<a name="crm.delMessage"/>清空信息记录 `crm.delMessage`

* 业务入参
|     参数名    |     类型      |      必须     |     说明     |
| ------------  | ------------- | ------------  | ------------ |
| mobile        | varchar       |       Y       | 手机号码     |
*接口返回
```json
{
	"code" : "200"
	"msg"  : "清空记录成功"
}

##<a name="tradeList"/>充值交易
###<a name="crm.tradeList"/>充值交易 `crm.tradeList`

* 业务入参
|     参数名    |     类型      |      必须     |     说明     |
| ------------  | ------------- | ------------  | ------------ |
| mobile        | varchar       |       Y       | 手机号码     |

*接口返回
```json
{
	"code" : "200"
	"msg"  : "获取充值交易成功"
	"data":{
			[{
			"uid" : "1"							#用户唯一id
			"trade_number" : "T150916133374"    #外部交易号
			"payment" : "支付宝"				#付款方式
			"money" : 100.00					#交易金额
			"status" : "已充值"					#说明
			"type"   : "income"					#type值分两种，income表示收入，outlay表示支出
			"time" "2015-09-16 14:13:27"		#交易发生时间
			"invoice" : 1						#1发票表示已开，0表示未开
			"has_rec" :1 						#1表示赠品已领，0表示未领
			"bonus_products" ：""				#为空表示没有赠品，不为空表示商品id
			},
			{"uid" : "1"						    #用户唯一id
			"trade_number" : "T150916133377"        #外部交易号
			"payment" : "账户余额支付"				#付款方式
			"money" : -120.00					    #交易金额
			"status" : "支出涉及订单号150916079286"	#说明
			"type" : "outlay"						#type值分两种，income表示收入，outlay表示支出
			"time" "2015-09-16 14:13:27"		    #交易发生时间
			"invoice" : 1						#1发票表示已开，0表示未开
			"has_rec" :1 						#1表示赠品已领，0表示未领
			"bonus_products" ：""				#为空表示没有赠品，不为空表示商品id
			}],
			[{"uid" : "2"							#用户唯一id
			"trade_number" : "T150916133374"        #外部交易号
			"payment" : "支付宝"				    #付款方式
			"money" : 180.00					    #交易金额
			"status" : "已充值"					    #说明
			"type"   : "income"					#type值分两种，income表示收入，outlay表示支出
			"time" "2015-09-16 14:13:27"		    #交易发生时间
			"invoice" : 1						#1发票表示已开，0表示未开
			"has_rec" :1 						#1表示赠品已领，0表示未领
			"bonus_products" ：""				#为空表示没有赠品，不为空表示商品id
			},
			{"uid" : "2"						    #用户唯一id
			"trade_number" : "T150916133377"        #外部交易号
			"payment" : "账户余额支付"				#付款方式
			"money" : -140.00					    #交易金额
			"status" : "支出涉及订单号150916079286"	#说明
			"type" : "outlay"						#type值分两种，income表示收入，outlay表示支出
			"time" "2015-09-16 14:13:27"		    #交易发生时间
			"invoice" : 1						#1发票表示已开，0表示未开
			"has_rec" :1 						#1表示赠品已领，0表示未领
			"bonus_products" ：""				#为空表示没有赠品，不为空表示商品id
			}]
	}
}

##<a name="cardOrder"/>提货券订单
###<a name="crm.cardOrder"/>提货券订单 `crm.cardOrder`

* 业务入参
|     参数名    |     类型      |      必须     |     说明     |
| ------------  | ------------- | ------------  | ------------ |
| mobile        | varchar       |       Y       | 手机号码     |

*接口返回
```json
{
	"code" : "200"
	"msg" : "获取提货券订单成功"
	"data" : [
		{
			"order_name" : "150817337297"			#订单号
			"pay_status": "已支付"					#支付状态
			"operation_id" : "已完成"				#订单状态
			"time" : "2015-09-15 17:15:38"			#下单时间
			"pay_name" : "券卡支付-在线提货券支付"  #支付方式
			"sync_status" 0							#0表示未同步oms，1表示已同步oms，其他表示同步中
		}
	]
}


##<a name="userJf"/>积分查询
###<a name="crm.userJf"/>积分查询 `crm.userJf`

* 业务入参
|     参数名    |     类型      |      必须     |     说明     |
| ------------  | ------------- | ------------  | ------------ |
| mobile        | varchar       |       Y       | 手机号码     |

*接口返回
```json
{
	"code" : "200"
	"msg" : "获取积分成功"
	"data" :{
			[{
				"jf" : "100"               #积分数，正数为得到积分，负数为消费积分
				"reason" : "注册送"        #操作积分的原因
				"time" : "2015-09-16 07:49:19"  #操作时间
			},{
				"jf" : "-2600"               #积分数，正数为得到积分，负数为消费积分
				"reason" : "订单150916450056消费积分2600抵扣26元"        #操作积分的原因
				"time" : "2015-09-15 07:49:19"  #操作时间
			}],
			[{
				"jf" : "10"               #积分数，正数为得到积分，负数为消费积分
				"reason" : "评论新西兰皇后红玫瑰苹果mini6个商品获得10积分"        #操作积分的原因
				"time" : "2015-09-11 07:49:19"  #操作时间
			}]
	}
}

##<a name="tiHuoQuan"/>提货券查询
###<a name="crm.tiHuoQuan"/>提货券查询 `crm.tiHuoQuan`

* 业务入参
|     参数名    |     类型      |      必须     |     说明     |
| ------------  | ------------- | ------------  | ------------ |
| card_number   | varchar       |       Y       | 提货券卡号   |

*接口返回
```json
{
	"code" : "200"
	"msg" : "获取提货券信息成功"
	"data" : {
		"card_number" : "JQSX00000500"              #提货券卡号
		"card_money" : "360.00"						#提货券金额
		"order_name" : "159874673874"				#提货券使用后关联订单号，当提货券未使用时，此字段为"0"
		"is_used" : 1								#1表示已用，0表示未用
		"is_sent": 1								#1表示已激活，0表示未激活，只有激活的提货券才可以使用
		"used_time" : "2015-09-09 13:33:54" 		#使用时间,当提货券未使用时，此字段为"0000-00-00 00:00:00"
		"start_time" : "2015-09-01 00:00:00"		#开始有效期
		"to_date" : "2015-09-29 00:00:00"			#结束有效期
		"product_id" : "65436"						#提货券提货的商品唯一id
		"remarks" : "如意金秋生鲜礼盒提货券 "		#提货券说明
	}
}

##<a name="chargeCard"/>充值卡查询
###<a name="crm.chargeCard"/>充值卡查询 `crm.chargeCard`

* 业务入参
|     参数名    |     类型      |      必须     |     说明     |
| ------------  | ------------- | ------------  | ------------ |
| card_number   | varchar       |       Y       | 充值卡号     |

*接口返回
```json
{
	"code" : "200"
	"msg" : "获取充值卡信息成功"
	"data" : {
		"card_number" : "GF50-516799"            #充值卡号
		"card_money" : "50"						 #充值卡余额
		"to_date" : "2016-12-31"			     #充值卡有效期
		"is_used" : "1"							 #1表示已使用，0表示未使用
		"activation" : "1"					     #1表示已激活，0表示未激活 只有激活的充值卡才可以使用
		"trade_number" : "T18769865"			 #充值交易号，当充值卡使用啦此字段有数据，未使用为空
		"time" : "2015-08-14 10:55:32"			 #充值时间，当充值卡使用啦此字段有数据，未使用为空
		"mobile" : "18964594167"				 #充值的用户手机号，当充值卡使用啦此字段有数据，未使用为空
		"money" :0 								 #如果充值卡使用了，充值卡的原金额。如果充值卡未使用，充值卡的金额看字段card_money
	}
}

##<a name="unSent"/>优惠券作废
###<a name="crm.unSent"/>优惠券作废 `crm.unSent`

* 业务入参
|     参数名    |     类型      |      必须     |     说明     |
| ------------  | ------------- | ------------  | ------------ |
| card_number   | varchar       |       Y       | 优惠券卡号   |

*接口返回
```json
{
	"code" : "200"
	"msg" : "作废成功"
}

##<a name="orderList"/>订单查询
###<a name="crm.orderList"/>订单查询 `crm.orderList`

* 业务入参
|     参数名    |     类型      |      必须     |     说明     |
| ------------  | ------------- | ------------  | ------------ |
| mobile        | varchar       |       Y       | 手机号码     |
| page          | int			|		N		| 当前页，默认1       |
| pagesize      | int           |       N		| 每页显示数量，默认10 |
| order_name    | varchar       |       N       | 订单号，为了查询指定订单 |

*接口返回
```json
{
	"code" : "200"
	"msg" : "获取订单列表成功"
	"data" : [
			{
				"page" : "1"
				"pagesize" : "10"
				"pages" : "1"
				"total" : "7"
				"order_list" [
					{"id" : "138345"                         #订单唯一id
					 "order_name" : "12891238978"			 #订单号
					 "money" : "10.00"						 #订单金额
					 "shtime" : "20150917"					 #送货时间
					 "pay_name" : "支付宝"					 #付款方式
					 "pay_status" : "已经付款"				 #支付状态
					 "operation_id" : "已发货			     #订单状态
					 "time" : "2015-09-17 11:38:18"			 #下单时间
					 "order_type" : "O2O"					 #订单类型
					},
					{
					"id" : "138745"                         #订单唯一id
					 "order_name" : "12491238978"			 #订单号
					 "money" : "60.00"						 #订单金额
					 "shtime" : "20150915"					 #送货时间
					 "pay_name" : "微信支付"					 #付款方式
					 "pay_status" : "已经付款"				 #支付状态
					 "operation_id" : "已完成			     #订单状态
					 "time" : "2015-09-14 11:38:18"			 #下单时间
					  "order_type" : "B2C"					 #订单类型
					}
				]

			},
			{
				"page" : "1"
				"pagesize" : "10"
				"pages" : "1"
				"total" : "7"
				"order_list" [
					{"id" : "1385345"                         #订单唯一id
					 "order_name" : "12891238978"			 #订单号
					 "money" : "20.00"						 #订单金额
					 "shtime" : "20150917"					 #送货时间
					 "pay_name" : "支付宝"					 #付款方式
					 "pay_status" : "已经付款"				 #支付状态
					 "operation_id" : "已发货			     #订单状态
					 "time" : "2015-09-17 11:38:18"			 #下单时间
					},
					{
					"id" : "138745"                         #订单唯一id
					 "order_name" : "12491238978"			 #订单号
					 "money" : "50.00"						 #订单金额
					 "shtime" : "20150915"					 #送货时间
					 "pay_name" : "微信支付"					 #付款方式
					 "pay_status" : "已经付款"				 #支付状态
					 "operation_id" : "已完成			     #订单状态
					 "time" : "2015-09-14 11:38:18"			 #下单时间
					}
				]
			}


	]
}

##<a name="saveCard"/>添加优惠券
###<a name="crm.saveCard"/>添加优惠券 `crm.saveCard`

* 业务入参
|     参数名    |     类型      |      必须     |     说明       |
| ------------  | ------------- | ------------  | ---------------|
|  mobile       | varchar       |       Y       | 用户手机号码   |
| card_money    | decimal       |       Y       | 卡金额         |
| maketing      | int           |       Y       | 营销类型    0表示官网，1表示O2O ，二选一  |
| channel       | array         |       N       | 渠道        传过来的结构是数组array(2,3)。 1表示官网，2，表示APP，3表示WAP，多选。一个不选表示全站通用|
| product_id    | int           |       N       | 产品id，传过来结构24,35 当只有一个商品时传24    不传表示不限制产品   |
| order_money_limit   | decimal |       Y       | 使用优惠券最低消费   |
| remarks       | varchar       |       Y       | 备注           |
| direction     | varchar       |       N       | 使用限制说明   |
| time          | date          |       Y       | 开始有效期     |
| to_date       | date          |       Y       | 结束有效期     |


*接口返回
```json
{
	"code" : "200"
	"msg" : "添加优惠券成功"
}

##<a name="operateJf"/>扣除用户积分
###<a name="crm.operateJf"/>扣除用户积分 `crm.operateJf`

* 业务入参
|     参数名     |     类型      |      必须     |     说明       |
| ------------  | ------------- | ------------  | ---------------|
|  mobile       | varchar       |       Y       |   用户手机号码   |
|	jf          | varchar       |       Y       |   扣除用户积分 -20,给用户添加积分  30
|   reason      | varchar       |       Y       |   扣除用户积分的原因

*接口返回
```json
{
	"code" : "200"
	"msg" : "操作成功"
}


##<a name="selectCard"/>优惠券查询
###<a name="crm.selectCard"/>优惠券查询 `crm.selectCard`

* 业务入参
|     参数名     |     类型      |      必须     |     说明       |
| ------------  | ------------- | ------------  | ---------------|
|  card_number   | varchar       |       Y       |   优惠券卡号   |

*接口返回
```json
{
	"code" : "200"
	"msg" : "操作成功"
	"data" : {
				"id" : 7891
				"card_number" : "gt0800655004"			#卡号
				"card_money" : "20.00"					#卡金额
				"is_used" : 1  							#1表示已使用，0表示未使用
				"is_sent" : 1							#1表示已激活，0表示未激活
				"time" : 2015-09-09						#开始有效期
				"to_date" : 2015-10-10					#结束有效期
				"content" : "订单134445抵扣了30元"		#说明
				"remarks" : "天天到家南非夏橙优惠券"	#备注
				"order_money_limit" : 90  				#订单金额限制
				"product_id" 						    #限制商品id,为空代表不限制商品，不为空代表限定商品
				"channel"							    #使用渠道  为空时代表全站通用，不为空时看channel_web，channel_app，channel_wap
				"channel_web"						    #使用渠道  为1代表pc web可用， 0代表pc web不可用
				"channel_app"  						    #使用渠道  为1代表 app 可用， 0代表app 不可用
				"channel_wap"						    #使用渠道  为1代表 wap 可用， 0代表wap 不可用
				"order_name" : "151551515151"			#优惠券关联的订单号
			 }
}


##<a name="resetPro"/>置赠品为已领取
###<a name="crm.resetPro"/>置赠品为已领取 `crm.resetPro`

* 业务入参
|     参数名     |     类型      |      必须     |     说明       |
| ------------  | ------------- | ------------  | ---------------|
|  active_id    | int            |       Y      |   充值id       |

*接口返回
```json
{
	"code" : "200"
	"msg" : "成功"
}
##<a name="selfOmsOrder"/>手动同步oms失败订单
###<a name="crm.selfOmsOrder"/>手动同步oms失败订单 `crm.selfOmsOrder`

* 业务入参
|     参数名     |     类型      |      必须     |     说明       |
| ------------  | ------------- | ------------  | ---------------|
|  order_name    | array            |       Y      |   订单号，数组形式 |

*接口返回
```json
{
	"code" : "200"
	"msg" : "同步成功"
}


*接口返回
```json
{
	"code" : "200"
	"msg" : "成功"
	"data" : {
				 {
					"order_name" : "150909596858",
					"time" : "2015-09-09 13:28:21"
					"product" :[
						"product_id" :298387						#商品id
						"product_name" :"越南红心火龙果"			#商品名称
						"product_no" : "2150821131"					#商品SKU
						"gg_name" :"5斤装/盒"						#商品规格
						"price" : "49"								#商品价格
						"qty" :1									#购买的商品数量
						"total_money" : 49							#小计总价
						"id" : 66359								#投诉id
					],
					[
						"product_id" :298387						#商品id
						"product_name" :"越南红心火龙果"			#商品名称
						"product_no" : "2150821131"					#商品SKU
						"gg_name" :"5斤装/盒"						#商品规格
						"price" : "49"								#商品价格
						"qty" :1									#购买的商品数量
						"total_money" : 49							#小计总价
						"id" : 66359								#投诉id
					]
				},
				{
					"order_name" : "150909596858",
					"time" : "2015-09-09 13:28:21"
					"product" :[
						"product_id" :298387						#商品id
						"product_name" :"越南红心火龙果"			#商品名称
						"product_no" : "2150821131"					#商品SKU
						"gg_name" :"5斤装/盒"						#商品规格
						"price" : "49"								#商品价格
						"qty" :1									#购买的商品数量
						"total_money" : 49							#小计总价
						"id" : 66359								#投诉id
					]
				}
			}
}


##<a name="orderComplaints"/>推送CRM申诉列表
###<a name="crm.orderComplaints"/>推送CRM申诉列表 `crm.orderComplaints`

* 业务入参
|     参数名     |     类型      |      必须     |     说明       |
| ------------  | ------------- | ------------  | ---------------|

*推送数据结构
```json
{
	"code" : "200"
	"msg" : "成功"
	"data" : {	[
					product_id : 12 		#商品id
					product_name : 苹果		#商品名称
					product_no: 43087		#商品SKU
					gg_name ： 2/斤			#商品规格
					price ： 12             #商品价格
					qty： 2                 #购买数量
					toatal_money：24        #小计价格
					id ： 245342            #申诉表的唯一id
					information ： 18987657865 #手机号码
					description ： 苹果烂啦   #申诉描述
					photo ： {
								[http://apicdn.fruitday.com/product_pic/3771/1/1-180x180-3771-3W2P1WYF.jpg  ]
								[http://apicdn.fruitday.com/product_pic/3771/1/1-180x180-3771-3W2P1WYdsfF.jpg  ]
							 }              #申诉用户上传的图片url
					sstime : 2015-09-12 13:23:23   #申诉时间
					ordername：1452850895	 #订单号
					thum_photo ： http://cdn.fruitday.com/product_pic/3771/1/1-180x180-3771-3W2P1WYdsfF.jpg #商品图片url
					time： 2015-10-12 17:12:23    #下单时间
				],
				[
					product_id : 12 		#商品id
					product_name : 苹果		#商品名称
					product_no: 43087		#商品SKU
					gg_name ： 2/斤			#商品规格
					price ： 12             #商品价格
					qty： 2                 #购买数量
					toatal_money：24        #小计价格
					id ： 245342            #申诉表的唯一id
					information ： 18987657865 #手机号码
					description ： 苹果烂啦   #申诉描述
					photo ： {
								[http://cdn.fruitday.com/product_pic/3771/1/1-180x180-3771-3W2P1WYF.jpg ]
								[http://cdn.fruitday.com/product_pic/3771/1/1-180x180-3771-3W2P1WYdsfF.jpg ]
							}              #申诉用户上传的图片url
					sstime : 2015-09-12 13:23:23   #申诉时间
					ordername：1452850895	 #订单号
					thum_photo ： http://cdn.fruitday.com/product_pic/3771/1/1-180x180-3771-3W2P1WYdsfF.jpg #商品图片url
					time： 2015-10-12 17:12:23    #下单时间
				]
			 }
}

##<a name="cancelOrder"/>取消团购挂起的订单
###<a name="crm.cancelOrder"/>取消团购挂起的订单 `crm.cancelOrder`

* 业务入参
|     参数名     |     类型      |      必须     |     说明       |
| ------------  | ------------- | ------------  | ---------------|
    id          |   int         |     Y         |   订单表唯一id，取订单列表返回的字段id

*接口返回
```json
{
	"code" : "200"
	"msg" : "同步成功"
}


##<a name="orderDetail"/>订单详情
###<a name="crm.orderDetail"/>订单详情 `crm.orderDetail`
* 业务入参
|     参数名     |     类型      |      必须     |     说明       |
| ------------  | ------------- | ------------  | ---------------|
	order_id    |    int        |       Y        |    订单唯一id

{
	"code" : 200
	"msg"	: "获取订单详情成功"
	"data" :{
				"info" :{
							"order_name" : 151122287084   #订单号
							"trade_no":                   #外部交易号
							"time"   :      2015-09-10    #下单时间
							"pay_name"                    #支付方式
							"shtime":					  #配送时间
							"msg":                        #留言
							"hk":						  #贺卡
							"fp":						  #发票抬头
							"fp_dz":					  #送发票地址
							"money":					  #总付款
							"goods_money":                #商品款
							"method_money":				  #配送费
							"today_method_money":		  #加急配送费
							"card_money":				  #优惠券抵扣金额
							"use_money_deduction":		  #帐户余额抵扣
							"jf_money":					  #积分抵扣金额
							"other_msg":				  #附加信息：
						}
				"user_info" :{
								"username" :           #订单会员
								"mobile":			   #会员手机

							 }
				"product_array":[
									{
										"product_name":			#商品名称
										"gg_name":				#商品规格
										"price"					#商品价格
										"qty";					#订购数量
										"total_money"			#
										//"price*qty-total_money"						#促销优惠	这行不是返回字段，只是需要在列表显示
										"sale_rule_type"：1		#促销类型 		0表示空，1表示搭配购,2表示满额折,3表示单品满x减x
										"recomment":{
														"content":				#评价内容
														"star":					#几个星星，最多5，最少1
												    }


									},
									{
										"product_name":			#商品名称
										"gg_name":				#商品规格
										"price"					#商品价格
										"qty";					#订购数量
										"total_money"			#
										//"price*qty-total_money"						#促销优惠	这行不是返回字段，只是需要在列表显示
										"sale_rule_type"：1		#促销类型 		0表示空，1表示搭配购,2表示满额折,3表示单品满x减x
										"recomment":{
														"content":				#评价内容
														"star":					#几个星星，最多5，最少1
												    }
									}
								]
				"address_array" : {
										"position":				#位置
										"name":					#收货人
										"address":				#详细地址
										"email":				#电子邮件
										"telephone":			#固定电话
										"mobile":				#手机号码
								  }
				"op":[
						{
							"manage":							#操作者
							"time":								#操作时间
							"pay_msg operation_msg"	:			#操作内容，取两个字段用空格分开
							"discription":						#备注
						},
						{
							"manage":							#操作者
							"time":								#操作时间
							"pay_msg operation_msg"	:			#操作内容，取两个字段用空格分开
							"discription":						#备注
						}

					]

			}
}


##<a name="getPostFree"/>运费计算
###<a name="crm.getPostFree"/>运费计算 `crm.getPostFree`
* 业务入参
|     参数名     |     类型      |      必须     |     说明       |
| ------------  | ------------- | ------------  | ---------------|
	goods_money    |    decimal(10,2)        |       Y        |   商品金额
	weight    		|    float        |       Y        |   重量
	city_name    |    varchar        |       Y        |   二级城市名称（中文）
{
	"code" : "200"
	"data" : "28"
}

 ##<a name="getPostFree"/>大客户收款
###<a name="user.getComplanyService"/>大客户收款 `user.getComplanyService`
* 业务入参
|     参数名     |     类型      |      必须     |     说明       |
| ------------  | ------------- | ------------  | ---------------|
	connect_id    |    varchar        |       Y        |   用户登录标示

{
	"code" : "200"
	"msg" : "succ"
	"data" : [
				{
					"out_trade_number" : "23421412"
					"money" : "89.98"
					"pay_status" : 0
					"pay_status_desc" : "未支付"
					"uid" :　8
				},
				{
					"out_trade_number" : "2342241"
					"money" : "89.98"
					"pay_status" : １
					"pay_status_desc" : "已支付"
					"uid" :　７
				}


			]
}
