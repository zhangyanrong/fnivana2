<?php
/*fday_config*/
$config['area_refelect'] = array(
      1=>array(106092),//上海
      2=>array(1),//江苏
      3=>array(54351),//浙江
      4=>array(106340),//安徽
      5=>array(143949),//北京
      6=>array(144005),//天津
      7=>array(143983),//河北
      8=>array(143967),//河南
      9=>array(143996),//山西
      10=>array(144035),//山东
      11=>array(144039),//陕西
      12=>array(144045),//吉林
      13=>array(144051),//黑龙江
      14=>array(144224),//辽宁
      15=>array(144252),//广东
      16=>array(144370),//海南
      17=>array(144379),//广西
      18=>array(144387),//福建
      19=>array(144412),//湖南
      20=>array(144443),//四川
      21=>array(144522),//重庆
      22=>array(144551),//云南
      23=>array(144595),//贵州
      24=>array(145843),//青海
      25=>array(144643),//湖北
      26=>array(144795),//江西
      27=>array(145855),//上海崇明
      28=>array(144627),//甘肃
      29=>array(145874),//北京五环外
   );

$config['str_area_refelect'] = array(
      '上海'=>106092,
      '江苏'=>1,
      '浙江'=>54351,
      '安徽'=>106340,
      '北京'=>143949,
      '天津'=>144005,
      '石家庄'=>143983,
      '郑州'=>143967,
      '太原'=>143996,
   );

$config['pay_array']  =  array(
      1=>array('name'=>'支付宝','son'=>array()),
      // 2=>array('name'=>'联华OK会员卡在线支付','son'=>array()),
      3=>array('name'=>'网上银行支付','son'=>array(
           // "00021"=>"招商银行(银行卡支付（全国范围）)",
           // "00004"=>"中国工商银行(网上签约注册用户（全国范围）)",
	         "00102"=>"浦发银行信用卡",
	         "00103"=>"交通银行信用卡",
           "00003"=>"中国建设银行",
           // "00017"=>"中国农业银行(网上银行签约客户（全国范围）)",
           // "00083"=>"中国银行(银行卡支付（全国范围）)",
           // "00005"=>"交通银行(太平洋卡（全国范围）)",
           // "00032"=>"浦东发展银行(东方卡（全国范围）)",
           // "00084"=>"上海银行(银行卡支付（全国范围）)",
           // "00052"=>"广东发展银行(银行卡支付（全国范围）)",
           // "00051"=>"邮政储蓄(银联网上支付签约客户（全国范围）)",
           // "00023"=>"深圳发展银行(发展卡支付（全国范围）)",
           // "00054"=>"中信银行(银行卡支付（全国范围）)",
           // "00087"=>"平安银行(平安借记卡（全国范围）)",
           // "00096"=>"东亚银行(银行卡支付（全国范围）)",
           // "00057"=>"光大银行(银行卡支付（全国范围）)",
           // "00041"=>"华夏银行(华夏借记卡（全国范围）)",
           // "00013"=>"民生银行(民生卡（全国范围）)",
           // "00055"=>"南京银行(银行卡支付（全国范围）)",
           // "00016"=>"兴业银行(在线兴业（全国范围）)",
           // "00081"=>"杭州银行(银行卡支付（全国范围）)",
           // "00086"=>"浙商银行(银行卡支付（全国范围）)",
           //"00030"=>"上海农村商业银行(如意借记卡（上海地区）)"
           //"00100"=>"民生银行家园卡",
     )),
      7=>array('name'=>'微信支付','son'=>array()),
      8=>array('name'=>'银联在线支付','son'=>array()),
      9=>array('name'=>'微信支付','son'=>array()),
      5=>array('name'=>'账户余额支付','son'=>array()),
      4=>array('name'=>'线下支付','son'=>array(
            1=>'货到付现金',
            2=>'货到刷银行卡',
            // 3=>'货到刷联华OK卡',
            7=>'红色储值卡支付',
            8=>'金色储值卡支付',
            9=>'果实卡支付',
            // 10=>'提货券支付',//key=10的提货券支付在购买流程会作为判断条件不赠送满赠赠品，不要修改key=10
            11=>'通用券/代金券支付'
      )),
      6=>array('name'=>'券卡支付','son'=>array(1=>'在线提货券支付')),
   );
   //某些支付方式不能开发票，pay_parent[-pay_son]
$config['no_invoice'] = array(
      '2'   => 1,
      '4-3' => 1,
      '4-7' => 1,
      '4-8' => 1,
      '4-9' => 1,
      '4-10' => 1,
      '4-11'=> 1,
      '5'=>1
);


$config['charge_pay_array']  =  array(
      1=>array('name'=>'支付宝','son'=>array()),
      // 3=>array('name'=>'网上银行支付','son'=>array()),
      7=>array('name'=>'微信支付','son'=>array()),
   );


$config['no_order_limit_send_arr'] = array(
    143949,
    144005,
    143983,
    143967,
    143996,
    144035,
    144039,
    144045,
    144051,
    144224,
    144252,
    144370,
    144379,
    144387,
    144412,
    144443,
    144522,
    144551,
    144595,
    144627,
    144643,
    144795,
    145855,
    145874,
);//没有起送金额限制的地区

$config['operation'] = array(
      0=>'待审核',
      1=>'已审核',
      2=>'已发货',
      3=>'已完成',
      4=>'拣货中',
      5=>'已取消',
      6=>'等待完成',
      7=>'退货中',
      8=>'换货中',
      9=>'已收货'
   );
$config['pay'] = array(
      0=>'还未付款',
      1=>'已经付款',
      2=>'到帐确认中',
   );
$config['order_channel'] = array(
      1=>'官网',
      2=>'手机',
      3=>'预售',
      4=>'光明',
      5=>'手机预售',
      6=>'app订单',
      7=>'app线下活动订单',
      8=>'跨境通订单',
      9=>'小米'
);

$config['wd_region_free_post_money_limit'] = array(
      '144261'=>100,//广州
      '144422'=>100,//深圳
      '144274'=>100,//珠海
      '144278'=>100,//汕头
      '144285'=>100,//韶关
      '144296'=>100,//佛山
      '144302'=>100,//江门
      '144313'=>100,//湛江
      '144321'=>100,//茂名
      '144328'=>100,//肇庆
      '144334'=>100,//惠州
      '144338'=>100,//梅州
      '144341'=>100,//汕尾
      '144345'=>100,//河源
      '144347'=>100,//阳江
      '144351'=>100,//清远
      '144355'=>100,//东莞
      '144357'=>100,//中山
      '144359'=>100,//揭阳
      '144364'=>100,//云浮
      '144367'=>100,//潮州
      '144444'=>100,//成都
      // '144450'=>100,//自贡
      // '144472'=>100,//绵阳
      // '144479'=>100,//遂宁
      // '144482'=>100,//内江
      // '144487'=>100,//南充
      // '144494'=>100,//泸州
      // '144499'=>100,//乐山
      // '144502'=>100,//广元
      // '144504'=>100,//宜宾
      // '144508'=>100,//巴中
      // '144510'=>100,//达州
      // '144513'=>100,//广安
      // '144515'=>100,//雅安
      // '144517'=>100,//德阳
      '145855'=>100,//上海崇明
      '145874'=>100,//北京五环外
   );
/*分站列表end*/

   //商品属性
  $config['product_option'] = array(
     'place'=>array(
       'name'=>'产地',
       'val'=>array('1'=>'进口','2'=>'国产'),
      ),
     'brand'=>array(
       'name'=>'品牌',
       'val'=>array('1'=>'经典佳沛','2'=>'新奇士','3'=>'都乐'),
     ),
    'occasion'=>array(
      'name'=>'场合',
      'val'=>array('1'=>'生日快乐','2'=>'早日康复','3'=>'走亲访友','4'=>'宴席聚会'),
    ),
    'size'=>array(
      'name'=>'规格',
      'val'=>array('1'=>'单品','2'=>'套餐','3'=>'礼盒','4'=>'礼篮','5'=>'券卡'),
    ),
    'detail_place' => array(
        'name' => '详细产地',
        'val' => array(
//            '1' => '新疆', '2' => '海南', '3' => '台湾', '4' => '陕西', '5' => '浙江',
//            '6' => '云南', '7' => '桂林', '8' => '重庆', '9' => '河北', '10' => '四川',
//            '11' => '江西', '12' => '甘肃', '13' => '雅安',
            '50' => '中国',
            '51' => '美国', '52' => '智利', '53' => '泰国', '54' => '西班牙', '55' => '新西兰',
            '56' => '意大利', '57' => '埃及', '58' => '墨西哥', '59' => '越南', '60' => '菲律宾',
            '61' => '马来西亚', '62' => '秘鲁', '63' => '澳大利亚', '64' => '挪威', '65' => '日本',
            '66' => '荷兰', '67' => '南非', '68' => '阿根廷', '69' => '朝鲜', '70' => '法国',
            '71' => '韩国', '72' => '加拿大', '73' => '土耳其', '74' => '乌拉圭', '75' => '希腊',
            '76' => '以色列', '77' => '英国', '78' => '厄瓜多尔',
            '999' => '其它',
        )
    ),
    'price'=>array(
       'name'=>'价格',
       'val'=>array('0T100'=>'100以下','100T300'=>'100~300','300T'=>'300以上'),
     ),
    'store' => array(
        'name' => '储藏方法',
        'val' => array('1' => '0°冷藏', '2' => '常温', '3' => '-18°~0°冷冻'),
    ),
);
  // 会员等级
  $config['user_rank'] = array(
    'cycle' => 12,
    'level' => array(
      6 => array(
        'name' => '鲜果达人V5',
        'level_id' => 6,
        'ordernum' => 36,
        'ordermoney' => 6000,
        'icon' => 'small_userrankV5.jpg',
        'bigicon' => 'big_userrankV5.jpg',
        'condition_desc' => '1年中，已完成订单数达到36并且订单总额满足6000元' ,
        'pmt_desc' => '1、送3倍积分',
        'pmt' => array(
          'score' => '3x',
         ),
        'juice'=>array(
          'day_money'=>5,
          'day_num'=>2,
          'week_money'=>0,
          'week_num'=>1
        ),
        'shake_num'=>5
      ),
      5 => array(
        'name' => '鲜果达人V4',
        'level_id' => 5,
        'ordernum' => 26,
        'ordermoney' => 3000,
        'icon' => 'small_userrankV4.jpg',
        'bigicon' => 'big_userrankV4.jpg',
        'condition_desc' => '1年中，已完成订单数达到26并且订单总额满足3000元' ,
        'pmt_desc' => '1、送2.5倍积分',
        'pmt' => array(
          'score' => '2.5x',
         ),
        'juice'=>array(
          'day_money'=>5,
          'day_num'=>2,
          'week_money'=>0,
          'week_num'=>1
        ),
        'shake_num'=>5
      ),
      4 => array(
        'name' => '鲜果达人V3',
        'level_id' => 4,
        'ordernum' => 16,
        'ordermoney' => 1500,
        'icon' => 'small_userrankV3.jpg',
        'bigicon' => 'big_userrankV3.jpg',
        'condition_desc' => '1年中，已完成订单数达到16并且订单总额满足1500元' ,
        'pmt_desc' => '1、送2倍积分',
        'pmt' => array(
          'score' => '2x',
        ),
        'juice'=>array(
          'day_money'=>5,
          'day_num'=>2,
          'week_money'=>0,
          'week_num'=>0
        ),
        'shake_num'=>4
      ),
      3 => array(
        'name' => '鲜果达人V2',
        'level_id' => 3,
        'ordernum' => 5,
        'ordermoney' => 500,
        'icon' => 'small_userrankV2.jpg',
        'bigicon' => 'big_userrankV2.jpg',
        'condition_desc' => '1年中，已完成订单数达到5并且订单总额满足500元' ,
        'pmt_desc' => '1、送1.5倍积分',
        'pmt' => array(
          'score' => '1.5x',
        ),
        'juice'=>array(
          'day_money'=>5,
          'day_num'=>1,
          'week_money'=>0,
          'week_num'=>0
        ),
        'shake_num'=>4
      ),
      2 => array(
        'name' => '鲜果达人V1',
        'level_id' => 2,
        'ordernum' => 2,
        'ordermoney' => 200,
        'icon' => 'small_userrankV1.jpg',
        'bigicon' => 'big_userrankV1.jpg',
        'condition_desc' => '1年中，已完成订单数达到2并且订单总额满足200元' ,
        'pmt_desc' => '1、送1倍积分',
        'pmt' => array(
            'score' => '1x',
          ),
        'juice'=>array(
          'day_money'=>10,
          'day_num'=>1,
          'week_money'=>0,
          'week_num'=>0
        ),
        'shake_num'=>3
      ),
      1 => array(
        'name' => '普通会员',
        'level_id' => 1,
        'ordernum' => 0,
        'ordermoney' => 0,
        'icon' => 'small_userrankV0.jpg',
        'bigicon' => 'big_userrankV0.jpg',
        'condition_desc' => '注册' ,
        'pmt_desc' => '1、送1倍积分',
        'pmt' => array(
            'score' => '1x',
          ),
        'juice'=>array( //果汁
          'day_money'=>0,//每天购买的果汁金额
          'day_num'=>0,//每天购买的果汁数量
          'week_money'=>0,//每星期购买的果汁金额
          'week_num'=>0//每星期噶偶买的果汁数量
        ),
        'shake_num'=>3//每天摇一摇次数
      ),
    ),
  );

$config['limit_service'] = array(
    'user.shake_shake',
);

$config['validate_source'] = array(
    'pc',
    'app',
    'wap',
    'pool',
    'risk'
);

$config['validate_version'] = array(
    '1.0',//wap
    '1.5.0',//ios
    '1.5.1',//ios
);

$config['default_channle'] = array(
    'AppStore',//ios
);

$config['order_product_type'] = array(
      'normal'=>'1',//普通商品
      'gift'=>'2',//赠品
      'mb_gift'=>'3',//满增赠品
      'exch'=>'4',//换购商品
      'user_gift'=>'5',//帐户赠品
      'coupon_gift'=>'6'//礼品券赠品
    );
//互斥商品
$config['huchi'] = array(
    '3739',
    '3941',
    '3956',
    '4196',
    '4220',
    '4262'
);
$config['huchi1'] = array(
//    '4983','4577'
);
$config['huchi2'] = array(
    '4851','4852','4853','4855'
);
$config['huchi3'] = array(
    '6616','6617','6618'
);

$config['fan'] = array(
    '6855','6857','6975','6751','6284','6752','6500','6976','6859','7139','7163','6857','7164','7292', '7293', '7350', '7351', '7352','7358', '7293', '6284', '7446', '7292', '7447', '6752', '7448', '6857', '7449', '7164', '7446', '6859', '7447'
);

$config['photo_base_path'] = "/mnt/www/wapi.fruitday.com/img/";
$config['IMAGE_URL'] = 'http://apicdn.fruitday.com/img/';

/*
仓_配送方式-限制单量
1上海，2广州，3北京，4成都
1自建，2快递
*/
$config['cang_limit'] = array(
    '1_1'=>array('day'=>200000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
    '1_2'=>array('day'=>130000,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
    '2_2'=>array('day'=>95000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
    '3_2'=>array('day'=>35000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
    '4_2'=>array('day'=>15000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
);


/*
地区-仓对应关系
*/
$config['area_cang'] = array(
    1=>array('cang_id'=>1,'type'=>2),//江苏省，上海仓
    54351=>array('cang_id'=>1,'type'=>2),//浙江省，上海仓
    106092=>array('cang_id'=>1,'type'=>1),//上海，上海仓
    106340=>array('cang_id'=>1,'type'=>2),//安徽省，上海仓
    144643=>array('cang_id'=>1,'type'=>2),//湖北省，上海仓
    144795=>array('cang_id'=>1,'type'=>2),//江西省，上海仓
    145855=>array('cang_id'=>1,'type'=>2),//上海市郊，上海仓
    144252=>array('cang_id'=>2,'type'=>2),//广东省，广州仓
    144370=>array('cang_id'=>2,'type'=>2),//海南省，广州仓
    144379=>array('cang_id'=>2,'type'=>2),//广西省，广州仓
    144412=>array('cang_id'=>2,'type'=>2),//湖南省，广州仓
    143949=>array('cang_id'=>3,'type'=>2),//北京，北京仓
    143967=>array('cang_id'=>3,'type'=>2),//河南省，北京仓
    143983=>array('cang_id'=>3,'type'=>2),//河北省，北京仓
    143996=>array('cang_id'=>3,'type'=>2),//山西省，北京仓
    144005=>array('cang_id'=>3,'type'=>2),//天津，北京仓
    144035=>array('cang_id'=>3,'type'=>2),//山东省，北京仓
    144039=>array('cang_id'=>3,'type'=>2),//陕西省，北京仓
    144045=>array('cang_id'=>3,'type'=>2),//吉林省，北京仓
    144051=>array('cang_id'=>3,'type'=>2),//黑龙江，北京仓
    144224=>array('cang_id'=>3,'type'=>2),//辽宁省，北京仓
    144387=>array('cang_id'=>4,'type'=>2),//福建省，成都仓
    144443=>array('cang_id'=>4,'type'=>2),//四川省，成都仓
    144522=>array('cang_id'=>4,'type'=>2),//重庆，成都仓
    144551=>array('cang_id'=>4,'type'=>2),//云南省，成都仓
    144595=>array('cang_id'=>4,'type'=>2),//贵州省，成都仓
    144627=>array('cang_id'=>4,'type'=>2),//甘肃省，成都仓
    145843=>array('cang_id'=>4,'type'=>2),//青海省，成都仓
    145874=>array('cang_id'=>3,'type'=>2),//北京五环外，北京仓
);

//仓特殊商品配置
$config['cang_product'] = array(
    '6275'=>array(
      'cang_id'=>1,
      'type'=>2,
    ),
);

//自定义日期配置
$config['date_cang_limit'] = array(
  '20160119'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160120'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160121'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160122'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160123'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160124'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160125'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160126'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160127'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160128'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160129'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160130'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
  '20160131'=>array(
        '1_1'=>array('day'=>12000,'night'=>800,'cang_id'=>'1','deliver_type'=>'1'),
        '1_2'=>array('day'=>8900,'night'=>0,'cang_id'=>'1','deliver_type'=>'2'),
        '2_2'=>array('day'=>8000,'night'=>0,'cang_id'=>'2','deliver_type'=>'2'),
        '3_2'=>array('day'=>6000,'night'=>0,'cang_id'=>'3','deliver_type'=>'2'),
        '4_2'=>array('day'=>3000,'night'=>0,'cang_id'=>'4','deliver_type'=>'2'),
      ),
);


//下单需要发送验证码商品
$config['need_send_code_pro'] = array('34');

$config['pc_pay_array']  =  array(
    1=>array('name'=>'支付宝','son'=>array()),
    // 2=>array('name'=>'联华OK会员卡在线支付','son'=>array()),
    3=>array('name'=>'网上银行支付','son'=>array(
        "00021"=>"招商银行(银行卡支付（全国范围）)",
        "00004"=>"中国工商银行(网上签约注册用户（全国范围）)",
        "00102"=>"浦发银行信用卡(活动中)",
        "00101"=>"交通银行信用卡",
        "00003"=>"中国建设银行",
        "00017"=>"中国农业银行(网上银行签约客户（全国范围）)",
        "00083"=>"中国银行(银行卡支付（全国范围）)",
        "00005"=>"交通银行(太平洋卡（全国范围）)",
        "00032"=>"浦东发展银行(东方卡（全国范围）)",
        "00084"=>"上海银行(银行卡支付（全国范围）)",
        "00052"=>"广东发展银行(银行卡支付（全国范围）)",
        "00051"=>"邮政储蓄(银联网上支付签约客户（全国范围）)",
        "00023"=>"深圳发展银行(发展卡支付（全国范围）)",
        "00054"=>"中信银行(银行卡支付（全国范围）)",
        "00087"=>"平安银行(平安借记卡（全国范围）)",
        "00096"=>"东亚银行(银行卡支付（全国范围）)",
        "00057"=>"光大银行(银行卡支付（全国范围）)",
        "00041"=>"华夏银行(华夏借记卡（全国范围）)",
        "00013"=>"民生银行(民生卡（全国范围）)",
        "00055"=>"南京银行(银行卡支付（全国范围）)",
        "00016"=>"兴业银行(在线兴业（全国范围）)",
        "00081"=>"杭州银行(银行卡支付（全国范围）)",
        "00086"=>"浙商银行(银行卡支付（全国范围）)",
        "00030"=>"上海农村商业银行(如意借记卡（上海地区）)",
        //"00100"=>"民生银行家园卡",
    )),
    //7=>array('name'=>'微信支付','son'=>array()),
    // 8=>array('name'=>'银联支付','son'=>array()),
    9=>array('name'=>'微信支付','son'=>array()),
    5=>array('name'=>'账户余额支付','son'=>array()),
    4=>array('name'=>'线下支付','son'=>array(
        1=>'货到付现金',
        2=>'货到刷银行卡',
        // 3=>'货到刷联华OK卡',
        7=>'红色储值卡',
        8=>'金色储值卡',
        9=>'果实卡',
        // 10=>'提货券支付',//key=10的提货券支付在购买流程会作为判断条件不赠送满赠赠品，不要修改key=10
        11=>'通用券/代金券'
    )),
    6=>array('name'=>'券卡支付','son'=>array(1=>'在线提货券支付')),
);
?>