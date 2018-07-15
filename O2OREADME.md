#O2O API

##目录
 * [订单](#order)
   - [获取订单列表 `o2o.orderList`](#o2o.orderList)

##系统约定

###api地址
http://nirvana.fruitday.com/o2o

###密钥
3ca59a237313bdad9244145641244946

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
| service      |     string    |       Y      |   请求接口名称 |



###状态码定义
|     参数名    |     说明      | 
| ------------ | ------------- |
| 200          |     请求成功   |
| 300          |     业务错误   |
| 500          |     系统错误   |



##<a name="order"/>订单
###<a name="o2o.orderList"/>获取订单列表 `o2o.orderList`

* 业务入参
|     参数名    |     类型      |      必须     |     说明     | 
| ------------ | ------------- | ------------ | ------------ |
| order_status   |     int     |       Y      |   订单状态 (0:待生产，1:已取消，2:已收货)  |
| page    |    int  |       Y      |  页数默认为1       |1|
| limit    |    int  |       Y      |  每页个数       |10|
| before    |   unix时间戳  |       Y      |  时间边界值       |1439251124|
| after    |    unix时间戳  |       Y      |  时间边界值       |1439251124|

* 接口返回
```json
{
    "before":"2015-12-28 05:25:00",
    "after":"2015-07-29 04:39:01",
    "page":"1",
    "total_count":1,
    "orders":
    [
      {
        "id":"1304426489",                        #订单主键
        "pay_name":"支付宝",                       #支付方式
        "order_name":"150720262510",              #订单号
        "time":"2015-07-20 15:26:17",             #下单时间
        "shtime":"20150721",                      #配送日期
        "stime":"am",                             #配送时间(am,pm,2hours)
        "money":"45.00",                          #支付金额
        "operation_id":"0",                       #订单状态(0:待审核,5:已取消,9:已收货)
        "goods_money":"45.00",                    #商品金额
        "jf_money":"0",                           #积分抵扣金额
        "card_money":"0.00",                      #优惠券抵扣金额
        "pay_discount":"0",                       #其他抵扣金额
        "method_money":"0",                       #运费
        "address":"上海市浦东新区康桥路488号",         #收货地址
        "name":"张三",                             #收货人
        "mobile":"13333333333",                   #收货人手机
        "store_id":"92",                          #门店id
        "building_id":"2495",                     #楼宇id
        "items":                                  #商品结构
        [
          {
            "id":"2898849",                       #sku id
            "product_id":"3265",                  #商品id
            "product_name":"苹果汁",               #商品名称
            "gg_name":"杯",                       #规格名称
            "price":"5",                          #价格
            "qty":"1",                            #数量
          }
        ]
      }
    ]
}
```


