<?php
namespace bll\app;
include_once("app.php");

/**
 * 商品相关接口
 */
class User extends app {
    function __construct($params = array()) {
        $this->ci = &get_instance();
        $this->ci->load->model('user_model');
    }


    /*
    *会员登录
    */
    public function signin($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            if (isset($params['device_id']) && !empty($params['device_id'])) {
                $this->ci->user_model->bind_registration($result['userinfo']['id'], $params['device_id']);
            }
        }
        return $result;
    }


    /*
    *会员注册
    */
    public function register($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            $user_score = $this->ci->user_model->getUserScore($result['id']);
            $coupon_num = $this->ci->user_model->getCouponNum($result['id'], 0);
            $return = array_merge($result, $user_score);
            $return['coupon_num'] = $coupon_num;
            if (isset($params['device_id']) && !empty($params['device_id'])) {
                $this->ci->user_model->bind_registration($return['id'], $params['device_id']);
            }
        }
        return $return;
    }

    /**
     * 会员赠品
     *
     * @return void
     * @author
     **/
    public function giftsGet($params) {

        $session_id = $params['connect_id'];
        if (!$session_id) {
            return array('code' => 500, 'msg' => 'connect id can not be null');
        }

        /*判断是否登录*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        if (!$this->ci->login->is_login()) {
            return array('code' => 300, 'msg' => '登录超时');
        }

        $valid = isset($params['valid']) ? $params['valid'] : 1;
        $region_id = $params['region_id'] ? $params['region_id'] : 106092;
        $gift_type = isset($params['gift_type']) ? $params['gift_type'] : 2;
        $this->ci->load->bll('user');
        $gifts = $this->ci->bll_user->giftsGet($region_id, $valid, $gift_type);

        if ($gifts && !$gifts['code']) {
            foreach ((array)$gifts as $key => $value) {
                $gifts[$key]['photo'] = $value['photo']['thum'];
                $gifts[$key]['end_time'] = $value['end'];
                unset($gifts[$key]['end']);
            }
        }
        return $gifts;
    }


    /**
     * @api              {post} / 用户赠品
     * @apiDescription   用户赠品
     * @apiGroup         user
     * @apiName          giftsGetNew
     *
     * @apiParam {String} connect_id 用户登录状态
     * @apiParam {String} store_id_list 门店id,逗号分隔
     *
     * @apiSampleRequest /api/test?service=user.giftsGetNew&source=app
     */
    public function giftsGetNew($params) {
        $session_id = $params['connect_id'];
        $storeIdList = explode(',', $params['store_id_list']);

        if (!$session_id) {
            return array('code' => 500, 'msg' => 'connect id can not be null');
        }
        if (!$storeIdList) {
            return array('code' => 500, 'msg' => 'store id list id can not be null');
        }

        /*判断是否登录*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        if (!$this->ci->login->is_login()) {
            return array('code' => 300, 'msg' => '登录超时');
        }

        $gift_type = isset($params['gift_type']) ? $params['gift_type'] : 2;
        $this->ci->load->bll('user');

        //过期
        $expiredGifts = $this->ci->bll_user->giftsGetNew($storeIdList, 0, $gift_type);
        if($expiredGifts['code'] == 300){
            return $expiredGifts;
        }

        if ($expiredGifts && !$expiredGifts['code']) {
            foreach ((array)$expiredGifts as $key => $value) {
                $expiredGifts[$key]['photo'] = $value['photo']['thum'];
                $expiredGifts[$key]['end_time'] = $value['end'] . ' +86';
                unset($expiredGifts[$key]['end']);
                $expiredGifts[$key]['start_time'] = $value['start'] . ' +86';
                unset($expiredGifts[$key]['start']);
                unset($expiredGifts[$key]['critical_status']);
            }
        }

        //有效
        $validGifts = $this->ci->bll_user->giftsGetNew($storeIdList, 1, $gift_type);
        if($validGifts['code'] == 300){
            return $validGifts;
        }

        if ($validGifts && !$validGifts['code']) {
            foreach ((array)$validGifts as $key => $value) {
                $validGifts[$key]['photo'] = $value['photo']['thum'];
                $validGifts[$key]['end_time'] = $value['end'] . ' +86';
                unset($validGifts[$key]['end']);
                $validGifts[$key]['start_time'] = $value['start'] . ' +86';
                unset($validGifts[$key]['start']);
            }
        }

        //已使用
        $hasResGifts = $this->ci->bll_user->giftsGetNew($storeIdList, 2, $gift_type);
        if($hasResGifts['code'] == 300){
            return $hasResGifts;
        }

        if ($hasResGifts && !$hasResGifts['code']) {
            foreach ((array)$hasResGifts as $key => $value) {
                $hasResGifts[$key]['photo'] = $value['photo']['thum'];
                $hasResGifts[$key]['end_time'] = $value['end'] . ' +86';
                unset($hasResGifts[$key]['end']);
                $hasResGifts[$key]['start_time'] = $value['start'] . ' +86';
                unset($hasResGifts[$key]['start']);
                unset($hasResGifts[$key]['critical_status']);
            }
        }

        $giftUrl = '';  //赠品订单列表

        return array('code' => 200, 'msg' => '', 'data' => array('expired' => $expiredGifts, 'valid' => $validGifts, 'hasRes' => $hasResGifts,'giftUrl'=>$giftUrl));
    }

    /**
     * 礼品卷
     *
     * @return void
     * @author
     **/
    public function gcouponGet($params) {
        $session_id = $params['connect_id'];

        if (!$session_id) {
            return array('code' => 500, 'msg' => 'connect id can not be null');
        }

        $card_number = $params['card_number'];
        if (!$card_number) {
            return array('code' => 300, 'msg' => '礼品券不能为空');
        }

        /*判断是否登录*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        if (!$this->ci->login->is_login()) {

            return array('code' => 300, 'msg' => '登录超时');
        }

        /*临时购物车*/
        $this->ci->load->bll('b2ccart', $params);
        // if (!$session_id && $carttmp = @json_decode($params['carttmp'],true)) {
        //     $this->ci->bll_cart->setCart($carttmp);
        // }
        // $cart_items = $this->ci->bll_cart->get_cart_items();


        $this->ci->load->bll('gcoupon');
        $uid = $this->ci->login->get_uid();
        $gifts = $this->ci->bll_gcoupon->giftsGet($card_number, $uid, $msg);

        if ($gifts === false) {
            return array('code' => 300, 'msg' => $msg);
        }

        /*加入购物车*/
        foreach ($gifts['products'] as $key => $value) {
            $item = array(
                'sku_id' => $value['product_price_id'],
                'product_id' => $value['product_id'],
                'qty' => $gifts['giftsend']['qty'],
                'product_no' => $value['product_no'],
                'item_type' => 'user_gift',
                'gift_active_type' => 2,
                'gift_send_id' => $gifts['giftsend']['id'],
                'gift_coupon_id' => $gifts['giftcoupon']['id'],
                'gift_coupon_number' => $gifts['giftcoupon']['card_number'],
                'user_gift_id' => $gifts['giftsend']['user_gifts']['id'],
            );

            $rs = $this->ci->bll_b2ccart->addCart($item, 'coupon_gift');

            if (!$rs) {
                $error = $this->ci->bll_b2ccart->get_error();

                return array('code' => 300, 'msg' => implode('、', $error),);
            }

        }

        // $data = array(
        //     'cartcount' => $this->ci->bll_cart->get_cart_count()
        //     );

        return array('code' => 200, 'msg' => '兑换成功，请在我的赠品里查看');
    }


    /*
    *会员信息
    */
    public function userInfo($params) {
        $result = parent::call_bll($params);
        if (isset($result['code']) && $result['code'] != '200') {
            return $result;
        } else {
            $return = $result;
            $user = $result;
            // 会员等级
            $rank = $this->ci->user_model->get_rank($user['user_rank']);

            $next_user_rank = $user['user_rank'] + 1;
            $next_rank = $this->ci->user_model->get_rank($next_user_rank);

            $cycle = $this->ci->user_model->get_cycle();
            // $cycle += 1;

            // $end_time = date('Y-m-d 59:59:59',strtotime('this month'));
            // $start_time = date('Y-m-d 59:59:59',strtotime("last day of -{$cycle} month"));
            $start_time = date('Y-m-d', strtotime("- {$cycle} month"));
            $end_time = date('Y-m-d H:i:s');

            $order_stat = $this->ci->user_model->user_rank_order_info($user['id'], $start_time, $end_time);
            $rank['curr_ordernum'] = (int)$order_stat['ordernum'];
            $rank['curr_ordermoney'] = floor($order_stat['ordermoney']);

            $return['rank_info'] = $rank;
            unset($return['rank_info']['pmt'], $return['rank_info']['ordernum'], $return['rank_info']['ordermoney']);

            $return['rank_info']['next_rank_name'] = '';
            $return['rank_info']['diff_ordernum'] = 0;
            $return['rank_info']['diff_ordermoney'] = 0;
            $return['rank_info']['rate_ordernum'] = 0;
            $return['rank_info']['rate_ordermoney'] = 0;
            if ($next_rank) {
                $return['rank_info']['next_rank_name'] = $next_rank['name'];
                $return['rank_info']['diff_ordernum'] = $next_rank['ordernum'] > $order_stat['ordernum'] ? $next_rank['ordernum'] - $order_stat['ordernum'] : 0;
                $return['rank_info']['diff_ordermoney'] = $next_rank['ordermoney'] > $order_stat['ordermoney'] ? $next_rank['ordermoney'] - $order_stat['ordermoney'] : 0;

                $return['rank_info']['rate_ordernum'] = $order_stat['ordernum'] > 0 && $next_rank['ordernum'] > 0 ? min(100, intval($order_stat['ordernum'] / $next_rank['ordernum'] * 100)) : 0;
                $return['rank_info']['rate_ordermoney'] = $next_rank['ordermoney'] > 0 && $order_stat['ordermoney'] > 0 ? min(100, intval($order_stat['ordermoney'] / $next_rank['ordermoney'] * 100)) : 0;

                $return['rank_info']['diff_ordermoney'] = floor($return['rank_info']['diff_ordermoney']);
            }

            if (strstr($return['userface'], 'default_userpic.png')) {
                $return['userface'] = '';
            }

            //new - 用户中心
            $sign_status = 0;
            $sign_url = 'http://huodong.fruitday.com/cms/indexapp/555?';  //即将开始

            $this->ci->load->library('phpredis');
            $this->redis = $this->ci->phpredis->getConn();

            //url
            $m_sign_url = $this->redis->get('active:signin');
            if (!empty($m_sign_url)) {
                $sign_url = $m_sign_url;
            }

            //status
            $m_sign_status = $this->redis->get('active:signin:' . $return['id']);
            if (!empty($m_sign_status)) {
                $now = date('Y-m-d');
                if ($m_sign_status == $now) {
                    $sign_status = 1;
                }
            }

            //IOS Bug 3.6.0
//            if($params['platform'] == 'IOS')
//            {
//                $sign_status = 0;
//            }

            $return['sign_url'] = $sign_url;
            $return['sign_status'] = $sign_status;

            $this->ci->load->model('user_gifts_model');
            $return['gift_num'] = $this->ci->user_gifts_model->gift_count($return['id']);
        }
        return $return;
    }


    /*
    * 配置
    */
    public function config($params) {

        $this->ci->load->bll('app/product');
        $pr_params = array(
            'service' => 'product.searchKey',
            'region_id' =>'106092',
            'source' => $params['source'],
            'version' => $params['version'],
        );
        $search = $this->ci->bll_app_product->searchKey($pr_params);

        $data = array();
        $data['register_gift_desc'] = '享新客专属折扣';
        //$data['shake_url'] = 'http://huodong.fruitday.com/sale/shake_v3/app.html?';

        $data['service_phone_alert'] = 0;
        $data['service_alert_text'] = '客服电话处在高峰,推荐使用其他方式';
        $data['reported_interval'] = 20;

        //搜索-config
        $data['search_hint'] = $search['search_hint'];
        $data['searchKey'] = $search['searchKey'];
        $data['search_banner'] = $search['search_banner'];

        //积分规则
        $data['jf_rule'] = '最多可抵扣订单金额(除运费外)的10%';

        //53kf-app
        $data['company_id'] = '70722519';

        //53kf-开启(app)
        // if($params['platform']=='IOS'){
        $data['support_online_service'] = 1;
        // }else{
        // $data['support_online_service'] = 0;
        // }
        $now_time = time();
        if ($now_time >= 1477929600 && $now_time <= 1477944000) {
            $data['support_online_service'] = 0;
        }

        //果实卡
        //$data['do_order_alert'] = '果实卡用户:请移步电脑端/手机网页端下单';
        $data['do_order_alert'] = '';

        //app更新提示
        if(version_compare($params['version'], '5.9.2') >= 0)
        {
            //$data['app_update_alert'] = '1.【新增】我的订单增加搜索功能，找订单一键触达\\n2.【新增】电子发票增加填写通知手机号，开票完成即刻通知您，一键完成下载\\n3.【优化】统一记录发票抬头和纳税人识别号，随单、补开、充值开票省时省力\\n4.【优化】配送小哥地图使用简化地图，配送坐标更清晰，体验更佳\\n5.【优化】优惠券展示优化，限制条件更明确、更清晰，使用更方便\\n6.【优化】调整订单修改配送时间限制，订单下单之后，5天内可以修改配送时间\\n7.【优化】个人中心调整我的收藏、我的地址入口为一级入口，寻找更便捷\\n8.【优化】iOS 11兼容性优化';
            //$data['app_update_alert'] = '1.【新增】美食指南 ，新模块只为给你的生活添一点不一样的色彩。\\n2.【新增】门店自提，增加商品线下门店自提业务，方便您对购物的不同需求。\\n3.【新增】再来一单，买过好东西再也不愁不方便加车，一键加车快捷出乎你想象。\\n4.【新增】订单分享红包 ，超大红包等你来撩，用心抢，放肆买。\\n5.【新增】果园门店，快快来围观果园的门店，就是要给你不一样的购物体验。\\n6.【新增】客服人员评价，聊天窗口即可对客服人员进行评价，就是要不断给你更好的服务。';
            $data['app_update_alert'] = '1.【新增】果园大厨来了，做菜买菜一应俱全，分分钟让您成为大厨。\\n2.【新增】增值税专用发票，资质开通、专票开具统统系统化，让您开票无忧。\\n3.【新增】即时达订单超时赔付，赔付不是最终目的，而是要求配送更准时。\\n4.【优化】商品详情页商品主图增加大图预览功能，商品详情页面滑动更顺畅。\\n5.【优化】购物车商品使用限制展示更详细，使用限制一目了然。\\n6.【优化】扫码功能优化，扫码更准、更快，扫一扫，体验嗖嗖的。\\n每一次的更新都是为了给您更好的服务，用果园，极新鲜';
        }
        else
        {
            $data['app_update_alert'] = '有新的版本更新，是否前往更新？';
        }

        //发现－达人
        $data['sns_expert'] = 1;

        //运费提醒
        $data['post_free_desc'] = '单个包裹满69包邮';

        //app审核广告
        $this->ci->load->library('phpredis');
        $this->redis = $this->ci->phpredis->getConn();
        $iosad_setting = json_decode($this->redis->get('iosadkey2017'),1);
        if(isset($iosad_setting['is_active']) && $iosad_setting['is_active']=='true' && $params['version']==$iosad_setting['version']){
            $data['is_show_ad'] = 1;            
        }else{
            $data['is_show_ad'] = 0;
        }

        if($params['channel'] == 'm360' || $params['channel'] == 'baidu' || $params['channel'] == 'qq' || $params['channel'] == 'wandoujia')
        {
            $data['pusher'] = 1;
        }
        else
        {
            $data['pusher'] = 0;
        }

        //邮费特权
        $data['is_postage'] = 1;

        //个人中心
        $data['userCenter'] = array(
            0=>array(
                'name'=>'会员中心',
                'subtitle'=>'享等级特权',
                'url'=>'https://huodong.fruitday.com/member/index?',
                'fullscreen'=>'true',
                'color'=>'#878787'
            ),
            1=>array(
                'name'=>'我的福利',
                'subtitle'=>'领取专属优惠',
                'url'=>'',
                'fullscreen'=>'false',
                'color'=>'#ff8000'
            ),
            2=>array(
                'name'=>'邮费特权',
                'subtitle'=>'最高累计减免75元',
                'url'=>'https://m.fruitday.com/statics/postage/index.html?',
                'fullscreen'=>'true',
                'color'=>'#ff8000'
            ),
            3=>array(
                'name'=>'卡券中心',
                'subtitle'=>'充值卡、提货券兑换',
                //'url'=>'https://m.fruitday.com/card/center/1?',
                'url'=>'https://m.fruitday.com/statics/ticket/index.html?',
                'fullscreen'=>'true',
                'color'=>'#878787'
            ),
            4=>array(
                'name'=>'企业购',
                'subtitle'=>'',
                'url'=>'https://m.fruitday.com/vip/business',
                'fullscreen'=>'false',
                'color'=>'#878787'
            ),
        );

        //闪光灯
        $data['is_open_light'] = 1;

        //运能不足
        if(version_compare($params['version'], '5.6.0') > 0)
        {
            $data['no_need_transport'] = '';
        }
        else
        {
            $data['no_need_transport'] = '部分时段订单较多，以下为当前可选择送达时间段';
        }

        //包裹提醒
        $hours =  intval(date('H'));
        if($hours >= 20 && $hours <= 23)
        {
            $data['package_title'] = '已过20:00，现在下单，当日达明天10:00起送';
        }
        else if($hours >= 0 && $hours <= 8)
        {
            $data['package_title'] = '现在下单，当日达今天10:00起送';
        }
        else
        {
            $data['package_title'] = '';
        }

        //刷新时间
        $data['refresh_time'] = '900';  //15分钟

        //app定位筛选
        $data['poiQueryType'] = '汽车服务|汽车销售|汽车维修|摩托车服务|餐饮服务|购物服务|生活服务|体育休闲服务|医疗保健服务|住宿服务|风景名胜|商务住宅|政府机构及社会团体|科教文化服务|金融保险服务|公司企业|地名地址信息|通行设施';
        $data['poiBlackCodeType'] = '150202|150203|150204|150205|150206|150207|150208|150209|2202|150210|1503|1504|1505|1506|1507|1508|1509|1510|1511|1512|1513|1603|18|190101|190102|190103|190104|190105|1902|190302|1501|1502|1503|1504|1505|1506|1507|1508|1509|1510|1511|1512|1513|1602|1603|18|1901|1902|1903|2|97';

        //隐藏闪鲜卡
        $data['is_open_fresh'] = '0';

        //自提
        $data['self_pick'] = '温馨提示：请按时到门店自提';

        //自提门店
        $data['self_pick_store'] = array(
            array('id'=>'6','lonlat'=>'121.386644,31.214329'),
            array('id'=>'21','lonlat'=>'121.564242,31.229547'),
            array('id'=>'22','lonlat'=>'121.500421,31.234079'),
            array('id'=>'23','lonlat'=>'121.380937,31.231803'),
            array('id'=>'24','lonlat'=>'121.468434,31.206575'),
            array('id'=>'25','lonlat'=>'121.44243,31.192078'),
            array('id'=>'26','lonlat'=>'121.429669,31.202556'),
            array('id'=>'27','lonlat'=>'121.451935,31.226761'),
            array('id'=>'28','lonlat'=>'121.387743,31.186736'),
            array('id'=>'29','lonlat'=>'121.33776,31.16396'),
            array('id'=>'30','lonlat'=>'121.2892,31.210334'),
            array('id'=>'31','lonlat'=>'121.322962,31.295791'),
            array('id'=>'32','lonlat'=>'121.594616,31.239431'),
            array('id'=>'33','lonlat'=>'121.621268,31.203884'),
            array('id'=>'34','lonlat'=>'121.399053,31.163941'),
            array('id'=>'35','lonlat'=>'121.511151,31.236215'),
            array('id'=>'36','lonlat'=>'121.484256,31.234533'),
            array('id'=>'37','lonlat'=>'121.403859,31.147068'),
            array('id'=>'38','lonlat'=>'121.538377,31.205616'),
        );

        //配送方式配置
        $data['deliver_config'] = array(
            0=>array('deliver_type'=>1,'color'=>'#65A032','text'=>'即时达'),
            1=>array('deliver_type'=>2,'color'=>'#FF8000','text'=>'明日达'),
            2=>array('deliver_type'=>3,'color'=>'#3A3A3A','text'=>'预售'),
        );

        //server time
        $data['server_time'] = time();

        //超时赔付
        $data['open_over_time'] = 1;
        $data['over_time_text'] = "若配送超时5分钟，将赔付您5元优惠券";

        
        return array('code'=>200,'msg'=>'succ','data'=>$data);
    }

    /*
   * 在线客服配置
   */
    public function customerConfig($params)
    {
        $data  = array();
        $data[0] =  array('name'=>'商品退换货','type'=>'refund','url'=>'');
        $data[1] =  array('name'=>'补开发票','type'=>'invoice','url'=>'');
        if(version_compare($params['version'], '5.7.0') >= 0)
        {
            $data[2] =  array('name'=>'修改发货时间/收货地址','type'=>'pendingorder','url'=>'');
        }
        else{
            $data[2] =  array('name'=>'修改发货时间','type'=>'pendingorder','url'=>'');
        }
        $data[3] =  array('name'=>'卡券兑换','type'=>'coupon','url'=>'https://m.fruitday.com//statics/ticket/index.html?');
        $data[4] =  array('name'=>'修改账户信息','type'=>'userinfo','url'=>'');
        //$data[5] =  array('name'=>'链接','type'=>'url','url'=>'https://m.fruitday.com//statics/ticket/index.html?');

        return array('code'=>200,'msg'=>'succ','data'=>$data);
    }

}