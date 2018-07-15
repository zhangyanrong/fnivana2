<?php
namespace bll;

class Cart{
    const USE_EXCH_AS_LIMIT2BUY = false;

    private $_province   = '106092';    // 用户省份
    private $_cmpversion  = '3.2.0';    // 用户省份
    private $_source;                   // terminal 入口终端 1:pc 2:app 3:wap
    private $_uid        = 0;
    private $_sessid     = '';          // cyc就是API传入的connect_id

    private $_cart_item  = array();     // cyc包含商品基本信息
    private $_cart_info  = array();     // cyc包含商品详细信息
    private $_error      = array();
    private $_notice     = array();
    private $_use_coupon = array();     // 优惠券使用

    public function __construct($params = array())
    {
        $this->ci = & get_instance();
        $this->_source = $params['terminal'];
        $this->_version = $params['version'];
        if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $this->ci->load->bll('b2ccart',$params);
        }else{

        // if ($params['session_id']) $this->ci->load->library('session',array('session_id'=>$params['session_id']));
        // if (isset($params['terminal'])) $this->_terminal = $params['terminal'];
        if ($params['session_id']) $this->_sessid = $params['session_id'];

        $this->initCart();
        }
    }

    /**
     * 重置用户省份
     *
     * @return void
     * @author
     **/
    public function set_province($region_id)
    {
        if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($region_id);
        }

        $this->ci->load->model('area_model');
        $province = $this->ci->area_model->getProvinceByArea($region_id);
        if ($province['id']) {
            $this->_province = $province['id'];
        }

        $this->_region_id = $region_id;

        return $this;
    }

    /**
     * 加入使用的优惠券
     *
     * @return void
     * @author
     **/
    public function add_coupon($coupon = array())
    {
        if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($coupon);
        }

        $this->_use_coupon = $coupon;
    }

    public function get_error()
    {
        return $this->_error;
    }

    public function setCart($cart_item)
    {
        if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($cart_item);
        }

        $this->_cart_item = $cart_item;

        return $this;
    }

    /**
     * 获取
     *
     * @return void
     * @author
     **/
    public function initCart()
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        $uid = $this->get_userid();

        if ($uid) {
            $this->ci->load->model('cart_model');

            $this->_cart_item = $this->ci->cart_model->get($uid);

            if (self::USE_EXCH_AS_LIMIT2BUY) {
                foreach ($this->_cart_item as $key => $value) {
                    if (strpos($key, 'limit2buy') !== false) {
                        unset($this->_cart_item[$key]);

                        $new_key = str_replace('limit2buy', 'exch', $key);
                        $value['item_type'] = 'exch';
                        $this->_cart_item[$new_key] = $value;
                    }
                }
            }
        }

    }

    /**
     * 保存
     *
     * @return void
     * @author
     **/
    private function saveCart()
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        $uid = $this->get_userid();

        if ($uid) {
            $this->ci->load->model('cart_model');

            $this->ci->cart_model->save($uid,$this->_cart_item,true);
        }
    }

    /**
     * 登录注册后的操作
     *
     * @return void
     * @author
     **/
    public function after_signin_regist($carttmp)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($carttmp);
        }
        $uid = $this->get_userid();

        if ($uid && $carttmp) {

            // 如果原先购物车里就有东西
            foreach ($carttmp as $key => $value) {
                if (isset($this->_cart_item[$key])) {
                    $this->_cart_item[$key]['qty'] += $value['qty'];
                } else {
                    $this->_cart_item[$key] = $value;
                }
            }

            $this->saveCart();
        }
    }

    public function get_cart_count()
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        $c = 0;

        foreach ((array) $this->_cart_item as $key => $value) {
            $c += (int) $value['qty'];
        }

        return $c;
    }

    /**
     * 购物车ITEM key取值
     *
     * @return void
     * @author
     **/
    public function get_citem_key($item)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($item);
        }
        $key = '';
        switch ($item['item_type']) {
            case 'normal':
                $key = 'normal_'.$item['sku_id'];
                break;
            case 'gift':
                $key = 'gift_'.$item['sku_id'];
                break;
            case 'mb_gift':
                $key = 'mb_gift_'.$item['sku_id'];
                break;
            case 'exch':
                $key = 'exch_'.$item['sku_id'];
                break;
            case 'user_gift':
                $key = 'user_gift_'.$item['gift_send_id'].'_'.$item['sku_id'];
                break;
            case 'coupon_gift':
                $key = 'coupon_gift_'.$item['gift_send_id'].'_'.$item['sku_id'];
                break;
            case 'limit2buy':
                $key = 'limit2buy_' . $item['sku_id'];
                break;
        }

        return $key;
    }

    /**
     * 加入购物车
     * @param Array $cart_item
     * $cart_time = array(
     *  'sku_id' => '@规则ID@',
     *  'product_id' => '@商品ID@',
     *  'product_no' => '@商品货号@',
     *  'qty' => '商品数量',
     *  'item_type' => '商品类型',
     * );
     **/
    public function addCart($cart_item,$item_type='normal')
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($cart_item,$item_type);
        }
        if (self::USE_EXCH_AS_LIMIT2BUY and $cart_item['item_type'] == 'exch') {
            $cart_item['item_type'] = 'limit2buy';
        }

        if (!isset($cart_item['item_type'])) $cart_item['item_type'] = $item_type;

        $key = $this->get_citem_key($cart_item);

        //只能购买一件限制商品
        $this->ci->load->model('product_model');
        $today = date('Y-m-d',time());
        if($today=='2015-08-22' || $today == '2015-08-23'){
            if(in_array($cart_item['product_id'],array(5345,5346,5347))){
                if (!$this->get_userid()) {
                    $this->_error[$cart_item['product_no']] = '请先登录';
                    return false;
                }
                $this->ci->load->model('order_model');
                $res = $this->ci->order_model->checkpf0823($cart_item['product_id'],$this->get_userid());
                if($res == false){
                    $this->_error[$cart_item['product_no']] = "抱歉，您已经购买过啦，浦发专场活动秒杀商品每位用户仅限购买一次哦";
                    return false;
                }
            }
        }
        $product = $this->ci->product_model->getProductSkus($cart_item['product_id']);
        if(!empty($this->_cart_item)){
            foreach($this->_cart_item as $v){
                $cart_product_ids[] = $v['product_id'];
            }
        }
        if(($product['can_buy_one']&&$cart_item['qty']>1)||($product['can_buy_one']&&in_array($cart_item['product_id'],$cart_product_ids))){
            $this->_error[$cart_item['product_no']] = "特殊商品只能购买一件";
            return false;
        }

        /*会员等级限制购买商品开始*/
        $this->ci->load->model('active_model');
        $product_rank_limit = $this->ci->active_model->getProductRankLimit();
        $uid = $this->get_userid();
        if(!empty($product_rank_limit)){
            $can_buy = $this->ci->active_model->checkRankBuy($uid,$product_rank_limit,$cart_item['product_id']);
            if(!empty($can_buy)){
                $this->_error[$cart_item['product_no']] = $can_buy;
                return false;
            }

        }
        /*会员等级限制购买商品结束*/

        //互斥商品判断
//        $is_huchi = $this->ci->cart_model->huchi($this->_cart_item,$this->get_userid(),$cart_item);
//        if(is_array($is_huchi)){
//            $this->_error[$cart_item['product_no']] = $is_huchi['msg'];
//            return false;
//        }
//
//        $is_huchi1 = $this->ci->cart_model->huchi1($this->_cart_item,$this->get_userid(),$cart_item);
//        if(is_array($is_huchi1)){
//            $this->_error[$cart_item['product_no']] = $is_huchi1['msg'];
//            return false;
//        }
//
//        $is_huchi2 = $this->ci->cart_model->huchi2($this->_cart_item,$this->get_userid(),$cart_item);
//        if(is_array($is_huchi2)){
//            $this->_error[$cart_item['product_no']] = $is_huchi2['msg'];
//            return false;
//        }
//
//        $is_huchi3 = $this->ci->cart_model->huchi3($this->_cart_item,$this->get_userid(),$cart_item);
//        if(is_array($is_huchi3)){
//            $this->_error[$cart_item['product_no']] = $is_huchi3['msg'];
//            return false;
//        }

        //互斥处理
        $this->ci->load->model('cart_model');
        $is_fan = $this->ci->cart_model->fan($this->_cart_item,$cart_item);
        if(is_array($is_fan)){
            $this->_error[$cart_item['product_no']] = $is_fan['msg'];
            return false;
        }

        //满额赠活动限制
        $this->ci->load->model('promotion_model');
        $rules = $this->ci->promotion_model->get_limit_gift_rule();
        $pro_ids = explode(',',$rules['product_ids']);
        if(!empty($pro_ids)){
            if(in_array($cart_item['product_id'],$pro_ids)) {
                if ($rules['is_repel']) {
                    if (!empty($this->_cart_item)) {
                        foreach ($this->_cart_item as $val) {
                            if (in_array($val['product_id'], $pro_ids)) {
                                $this->_error[$cart_item['product_no']] = '单笔订单只能换购1份，若想重新选择，可先从购物车内移除原商品';
                                return false;
                            }
                        }
                    }
                }
            }
        }

        if (isset($this->_cart_item[$key])) {
            $cart_item['qty'] += $this->_cart_item[$key]['qty'];
        }

        $rs = $this->_check_cart_item($cart_item);

        if ($rs == false) return false;

        $this->_cart_item[$key] = $cart_item;

        // 再次全体校验一次
        $this->_clear_cart_items();

        $this->saveCart();

        return true;
    }

    public function removeCart($ik,$item_type='normal')
    {
        if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($ik,$item_type);
        }

        if (!$ik) return false;

        // $key = $item_type.'_'.$sku_id;
        $product_id = $this->_cart_item[$ik]['product_id'];
        $gift_coupon_id = $this->_cart_item[$ik]['gift_coupon_id'];
        unset($this->_cart_item[$ik]);

        foreach ($this->_cart_item as $key => $value) {
            if ($value['item_type'] == 'coupon_gift' && $value['gift_coupon_id'] == $gift_coupon_id) {
                unset($this->_cart_item[$key]);
            }
        }

        // $this->_clear_cart_items();
        $this->saveCart();

        return true;
    }

    public function updateCart($cart_item,$item_type='normal')
    {
        if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($cart_item,$item_type);
        }

        if (!isset($cart_item['item_type'])) $cart_item['item_type'] = $item_type;

        if (!$this->_cart_item[$cart_item['ik']]){
            $this->_error[] = '购物车无此商品';
            return false;
        }

        $citem = $this->_cart_item[$cart_item['ik']];
        $citem['qty'] = $cart_item['qty'];


//todo by syt
        $this->ci->load->model('promotion_model');
        $rules = $this->ci->promotion_model->get_limit_gift_rule();
        $pro_ids = explode(',',$rules['product_ids']);
        if(!empty($pro_ids)){
            if(in_array($citem['product_id'],$pro_ids)) {
                $this->_error[$citem['product_no']] = '特殊商品只能购买一件';
                return false;
            }
        }

        $rs = $this->_check_cart_item($citem,false);

        if ($rs == false) return false;

        $this->_cart_item[$cart_item['ik']]['qty'] = (int) $cart_item['qty'];

        // $this->_clear_cart_items();
        $this->saveCart();

        return true;
    }

    public function emptyCart()
    {
        if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        $this->_cart_item = array();

        $this->saveCart();

        return true;
    }

    public function removeSelected() {
        $fun = __FUNCTION__;
        return $this->ci->bll_b2ccart->$fun();
    }

    public function get_cart_info($colfilter = array())
    {
        if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($colfilter);
        }
        if ($this->_cart_item) $this->settle();

        if ($colfilter) {
            foreach ((array) $this->_cart_info['items'] as $key => $value) {
                foreach ($value as $k => $v) {
                    if (in_array($k,$colfilter)) {
                        unset($this->_cart_info['items'][$key][$k]);
                    }
                }
            }
        }

        return $this->_cart_info;
    }

    public function get_cart_items()
    {
        return $this->_cart_item;
    }

  // cyc输入购物车基本信息$this->_cart_item
    // cyc查询每个商品的详细信息 放入到$this->_cart_info
    // cyc营销规则
    public function settle()
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        // $this->_cart_info = array('use_coupon'=>array(0=>array('card_money'=>'500')));

        $this->_cart_info = array();

        // 对ITEMS进行验证
        // $this->check_cart_item($cart_item);
        // if (!$cart_item) return true;
        $this->_clear_cart_items();
        if (!$this->_cart_item) return ;

        // $this->_cart_info['use_coupon'] = $this->_use_coupon;

        // cyc查询每个商品的详细信息
        $this->perfect_cart_item();

        // cyc新购物车
        // $this->ci->load->library('Promotion/promotion');
        // $user = $this->get_user();
        // $this->ci->promotion->loadStrategies('pc', 'b2c', $this->_province, $user);
        // print_r($this->ci->promotion->strategies);die;
        // $this->_cart_info = $this->ci->promotion->implementStrategies($this->_cart_info);
        // echo json_encode($this->_cart_info);die;
        // 临时总价计算
        // $total = 0;
        // foreach($this->_cart_info['items'] as $item) {
        //     $total += $item['amount'];
        // }
        // echo $total - $this->_cart_info['pmt_total'];
        // die;


        // cyc新购物车
		// if( $this->_source == '1' ){
		// 	$this->ci->load->library('Promotion/promotion');
		// 	$user = $this->get_user();
		// 	$this->ci->promotion->loadStrategies('pc','b2c', $this->_province, $user);
		// 	$this->_cart_info = $this->ci->promotion->implementStrategies($this->_cart_info);
		// }else{
			// cyc购物车营销开始
			$this->ci->load->bll('pmt/product/process',null,'bll_pmt_product_process');
			$this->ci->bll_pmt_product_process->set_cart($this->_cart_info);

			$enjoy_order_pmt = true;
			$order_pmt_type = 0;
			// 商品促销
			foreach ($this->_cart_info['items'] as $itemkey=>$item) {
				if ($item['pmt_pass'] != true && $item['item_type'] == 'normal') {
					// cyc单件商品促销 这里要改造
					$this->ci->bll_pmt_product_process->cal($itemkey,$item);
				}

				if ($item['item_type'] == 'kjt'|| $item['item_type'] == 'group' || $item['item_type'] == 'presell') $enjoy_order_pmt = false;
				if ($item['item_type'] == 'o2o') $order_pmt_type = 2;
			}

			// 优惠提醒
			// cyc购物车优惠提醒 这里要改造
			$this->ci->bll_pmt_product_process->pmt_alert();
			$this->_cart_info = $this->ci->bll_pmt_product_process->get_cart();

			// 赠品提醒
			// cyc赠品提醒 这里要改造
			$draw_usergifts = array();
			foreach ($this->_cart_item as $key => $value) {
				if ($value['item_type'] == 'user_gift') {
					$draw_usergifts[$value['gift_send_id']][] = $value['product_id'];
				}
			}

			$this->ci->load->bll('gsend');
			$user_gift = $this->ci->bll_gsend->get_usergift_alert($this->get_userid(),$draw_usergifts,$this->_province,0);

			if ($user_gift){
				$this->_cart_info['pmt_alert'][] = $user_gift;
			}
        //}
        // 各类活动

        // 计算总价
        // cyc里面有满件减 这里要改造 checkProSale_upto
        $this->total();

        // 订单促销
        if ($enjoy_order_pmt) {
            $this->ci->load->bll('pmt/order/process',null,'bll_pmt_order_process');
            $this->ci->bll_pmt_order_process->set_cart($this->_cart_info);
            $this->ci->bll_pmt_order_process->set_province($this->_province);
            // cyc满额减 这里要改造 moneyupto
            $this->ci->bll_pmt_order_process->cal($order_pmt_type);

            $this->_cart_info = $this->ci->bll_pmt_order_process->get_cart();
        }

        // 满额换购
        $this->ci->load->bll('pmt/limit2buy', null, 'bll_pmt_limit2buy');
        $this->ci->bll_pmt_limit2buy->cal($this->_cart_info, self::USE_EXCH_AS_LIMIT2BUY);

        // cyc购物车营销结束

        // 免费提醒
        $this->ci->load->library('terminal');
        if ($this->ci->terminal->is_app()) {
            $this->ci->load->bll('costfreight');
            $costfreight = $this->ci->bll_costfreight->cost_freight_alter($this->_cart_info,$this->_region_id);
            if ($costfreight) {
                $this->_cart_info['pmt_alert'][] = $costfreight;
            }
        }elseif($this->ci->terminal->is_wap()){
            $this->ci->load->bll('costfreight');
            $costfreight = $this->ci->bll_costfreight->cost_freight_alter($this->_cart_info,$this->_region_id);
            if ($costfreight) {
                $costfreight['solution']['url'] = false;
                $this->_cart_info['pmt_alert'][] = $costfreight;
            }
        }
    }

    /**
     * 确立配送时间
     *
     * @return void
     * @author
     **/
    private function _gen_shipping_time()
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        // 商品上是否标识两到三天配送
        $pid = array();
        foreach ($this->_cart_info['items'] as $key => $value) {
            $pid[] = $value['product_id'];
        }

        if ($pid) {
            $fixtime = $this->ci->db->select('id')
                                    ->from('product')
                                    ->where('delivery_limit',1)
                                    ->count_all_results();
            if ($fixtime > 0) {
                return 'after2to3days';
            }

            if ($this->_province) {
                $this->ci->load->model('area_model');
                $area = $this->ci->area_model->dump(array('id'=>$this->_province));

                if (preg_match("/\d+/", $area['identify_code'],$matches)) {
                    switch ($matches[0]) {
                        case '4':
                        case '9':
                            return 'after2to3days';break;
                        default:
                            # code...
                            break;
                    }
                }
            }
        }

        return '';
    }

    private function _cost_freight()
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        $sdate = $shipping_time['sdate'];

        // 包邮
        $pid = array(); $weight = 0;
        foreach ($this->_cart_info['items'] as $key => $value) {
            $pid[] = $value['product_id'];

            $weight += $value['weight'];
        }

        if ($pid) {
            $pinkage = $this->ci->db->select('id')
                                    ->from('product')
                                    ->or_where('free_post','1')
                                    ->or_where('ignore_order_money','1')
                                    ->count_all_results();
            if ($pinkage == 0) {

                $cost_freight = 0;
                // 计算邮费
                $area_refelect = $this->ci->config->item('area_refelect');
                if ($this->_province == $area_refelect['1'][0] || $this->_province == $area_refelect['5'][0]) { // 其他城市
                    $area = $this->_get_area();
                    if (!$area['first_weight'] || !$area['first_weight_money'] || !$area['follow_weight_money']) {
                        $cost_freight = 0;
                    } else {
                        $free_config = $this->config->item("wd_region_free_post_money_limit");
                        $free_low_money = isset($free_config[$area['city']]) ? $free_config[$area['city']] : 300;

                        // 订单金额达不到最低限额
                        if( $this->_cart_info['total_amount'] < $free_low_money) {
                            if($weight <= $area['first_weight']) {
                                $cost_freight = $area['first_weight_money'];
                            }else {
                                $cost_freight = $area['first_weight_money'] + ceil($weight - $area['first_weight']) * $area['follow_weight_money'];
                            }
                        }
                    }
                } else {
                    // 上海，北京

                }

                if ($cost_freight > 0) {
                    $this->_cart_info['cost_freight'] = $cost_freight;
                    $this->_cart_info['total_amount'] += $cost_freight;
                }
            }
        }
    }

    /**
     * 获取地址信息
     *
     * @return void
     * @author
     **/
    private function _get_area()
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        static $area;

        if (!$area[$this->_province]) {
            $this->ci->load->model('area_model');
            $area[$this->_province] = $this->ci->area_model->dump(array('id'=>$this->_province));
        }

        return $area[$this->_province];
    }

    private function total()
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        $this->ci->load->library('math');

        $total_amount = $pmt_goods = $goods_cost =  $goods_amount = 0;

        foreach ($this->_cart_info['items'] as $key=>$value) {

            if ($value['status'] != 'active') {
                unset($this->_cart_info['items'][$key]);
                continue;
            }

            $goods_amount = $this->ci->math->add($goods_amount,$value['amount']);

            $pmt_price_total = $this->ci->math->sub($value['price'] * $value['qty'],$value['amount']);
            $pmt_price_total = $pmt_price_total > 0 ? $pmt_price_total : 0;

            $this->_cart_info['items'][$key]['pmt_price_total'] = $pmt_price_total;

            $this->_cart_info['items'][$key]['goods_cost'] = $this->ci->math->mul($value['price'] , $value['qty']);
            //float
            $this->_cart_info['items'][$key]['amount'] = number_format((float)$value['amount'], 2,'.','');  //jackchen

            $pmt_goods += $pmt_price_total;
            $goods_cost = $this->ci->math->add($goods_cost,$value['price'] * $value['qty']);

        }

        $total_amount = $goods_amount;//

        // 优惠卷
        // if ($this->_cart_info['use_coupon']){
        //     foreach ($this->_cart_info['use_coupon'] as $key => $value) {
        //         $total_amount = bccomp($total_amount, $value['card_money'],3) == 1 ?  bcsub($total_amount, $value['card_money'],3) : 0;

        //         $this->_cart_info['pmt_order'] = bcadd((float) $this->_cart_info['pmt_order'], $value['card_money'],3);
        //     }
        // }

        $this->_cart_info['total_amount'] = number_format((float)$total_amount, 2,'.','');
        $this->_cart_info['goods_amount'] = number_format((float)$goods_amount, 2,'.','');
        $this->_cart_info['goods_cost']   = number_format((float)$goods_cost, 2,'.','');
        $this->_cart_info['pmt_goods'] = $pmt_goods;
        $this->_cart_info['cost_freight'] = 0;
        $this->_cart_info['pmt_total'] = $this->ci->math->add($this->_cart_info['pmt_goods'],$this->_cart_info['pmt_order']);

        $this->ci->load->model('cart_model');
        $uid = $this->get_userid();
        $pro_sale_upto_result = $this->ci->cart_model->checkProSale_upto($this->_cart_info,$uid,$this->_source);
        if($pro_sale_upto_result){
            $this->_cart_info['total_amount'] = $this->_cart_info['total_amount'] - $pro_sale_upto_result['cut_money'];
            //$this->_cart_info['total_amount'] = $this->_cart_info['total_amount'];
            $this->_cart_info['pmt_total'] = $this->_cart_info['pmt_total'] + $pro_sale_upto_result['cut_money'];
        }
    }

    // 验证普通商品
    private function _check_normal($item,$product)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($item,$product);
        }
        $this->ci->load->model('cart_model');
        $this->ci->load->model('product_model');
        $sku_id = $item['sku_id']; $product_id = $item['product_id'];
        // 判断商品是否在架销售

        if ($product['lack'] == 1 ) {
            $this->_error[$item['product_no']] = '商品缺货';
            return false;
        }

        $stock_info = $this->ci->product_model->getRedisProductStock($sku_id);
        if(!empty($stock_info)){
            if ($stock_info['use_store'] == 1 ) {
                if($item['qty']>$stock_info['stock']){
                    if($stock_info['stock']>0){
                        $stock_msg = "该商品库存仅剩".$stock_info['stock'].'件，请修改购买数量';
                    }else{
                        $stock_msg = "该商品已缺货";
                    }
                    $this->_error[$item['product_no']] = $stock_msg;
                    return false;
                }
            }
        }else{
            if ($product['use_store'] == 1 ) {
                $stock = $this->ci->cart_model->getProStock($sku_id);
                if(!empty($stock)){
                    if($item['qty']>$stock['stock']){
                        if($stock['stock']>0){
                            $stock_msg = "该商品库存仅剩".$stock['stock'].'件，请修改购买数量';
                        }else{
                            $stock_msg = "该商品已缺货";
                        }
                        $this->_error[$item['product_no']] = $stock_msg;
                        return false;
                    }
                }
            }
        }

        $this->ci->load->library('terminal');
        if ( ($this->ci->terminal->is_app() && $product['app_online'] != 1) ||
             ($this->ci->terminal->is_wap() && $product['mobile_online'] != 1) ||
             ($this->ci->terminal->is_web() && $product['online'] != 1)
         ) {
            if($product['free']!=1){//企业团购商品不需要进此判断
                $this->_error[$item['product_no']] = '商品已下架';
                return false;
            }
        }

        // 检查是否受区域限制
        $send_region = @unserialize($product['send_region']);

        if (is_array($send_region) && !in_array($this->_province,$send_region)) {
            $this->_error[$item['product_no']] = '您所在的地域无货';

            return false;
        }

        // 限时惠商品
        if ($product['xsh'] == 1) {
            if (!$this->get_userid()) {
                $this->_error[$item['product_no']] = '请先登录';
                return false;
            }

            $xsh_exceed = $this->_check_xsh($product,$item);

            if ($xsh_exceed != '') {
                $this->_error[$item['product_no']] = $xsh_exceed;
                return false;
            }

        }

        //首够限制
        if($product['first_limit']==1){
            if (!$this->get_userid()) {
                $this->_error[$item['product_no']] = '此商品为活动商品，请先登陆';
                return false;
            }else{
                $uid = $this->get_userid();
                $this->ci->db->from('order');
                $this->ci->db->where(array('uid'=>$uid,'order_status'=>'1','operation_id !='=>'5','order_type !='=>'2'));
                $is_first = $this->ci->db->count_all_results();
                if($is_first){
                    $this->_error[$item['product_no']] = '此商品为新客专享商品，您可以挑选其他优惠商品';
                    return false;
                }
            }
        }

        return true;
    }

    // 验证赠品
    private function _check_user_gift($item,$product)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($item,$product);
        }
        if (!$item['gift_send_id']) {
            $this->_error[$item['product_no']] = '赠品参数错误';
            return false;
        }

        if (!$this->get_userid()) {
            $this->_error[$item['product_no']] = '请先登录';
            return false;
        }

        if($item['gift_active_type']==2){
        // 判断赠品是否有效
        $this->ci->load->model('gsend_model');
        if(!is_numeric($item['gift_send_id'])){
            $this->_error[$item['product_no']] = '赠品不存在';
            return false;
        }
        $gsend = $this->ci->gsend_model->dump(array('id'=>$item['gift_send_id']));

        if (!$gsend) {
            $this->_error[$item['product_no']] = '赠品不存在';
            return false;
        }

        if (strtotime($gsend['start']) > time()) {
            $this->_error[$item['product_no']] = '赠品未到领取时间';
            return false;
        }

        if (strtotime($gsend['end']) < time()) {
            $this->_error[$item['product_no']] = '赠品过期';
            return false;
        }

        if (!in_array($item['product_id'],explode(',',$gsend['product_id']))) {
            $this->_error[$item['product_no']] = '赠品不包含在活动期间内';
            return false;
        }

        // 判断是否允许单领取
        if ($gsend['can_single_buy'] == 1) {
            $pass = false;
            foreach ($this->_cart_item as $key => $value) {
                if ($value['item_type'] == 'normal') {
                    $pass = true;break;
                }
            }

            if ($pass === false) {
                $this->_error[$item['product_no']] = '必须购物其他商品才能领取';
                return false;
            }
        }
        }elseif($item['gift_active_type']==1){
             $this->ci->load->model('user_gifts_model');
             $trade_gifts = $this->ci->user_gifts_model->get_trade_gifts($this->get_userid(),$this->_province);
             $trade_check_result = false;
             foreach ($trade_gifts as $tk => $tv) {
                 if($tv['gift_send_id']==$item['gift_send_id'] && $tv['product_id']==$item['product_id']){
                    $trade_check_result = true;
                    $gsend['qty'] = $tv['qty'];
                    $gsend['end'] = $tv['end'];
                    $gsend['order_money_limit'] = 0;
                 }
             }
             if(!$trade_check_result){
                $this->_error[$item['product_no']] = '充值赠品异常';
                return false;
             }
        }
        $this->ci->load->model('user_gifts_model');
        $ugifts = $this->ci->user_gifts_model->dump(array('uid'=>$this->get_userid(),'active_id'=>$item['gift_send_id'],'has_rec'=>0));
        if (!$ugifts) {
            $this->_error[$item['product_no']] = '赠品异常';
            return false;
        }

        // 判断赠品是否超出数量
        // $itemkey = $this->get_citem_key($item);
        // $qty = $op ? ( $item['qty'] + (int) $this->_cart_item[$itemkey]['qty']) : $item['qty'];
        if ($item['qty'] > $gsend['qty']) {
            $this->_error[$item['product_no']] = '您只能领取'. $gsend['qty'].'件赠品';
            return false;
        }


        $itemkey = $this->get_citem_key($item);
        if ($this->_cart_item[$itemkey]) {
            $this->_cart_item[$itemkey]['endtime']      = $gsend['end'];
            $this->_cart_item[$itemkey]['user_gift_id'] = $ugifts['id'];
            $this->_cart_item[$itemkey]['order_money_limit']      = $gsend['order_money_limit'];
        }

        return true;
    }

    /**
     * 礼品赠品
     *
     * @return void
     * @author
     **/
    private function _check_coupon_gift($item,$product)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($item,$product);
        }
        if (!$item['gift_coupon_id'] || !$item['gift_coupon_number']) {
            $this->_error[$item['product_no']] = '礼品码参数错误';
            return false;
        }

        if (!$this->get_userid()) {
            $this->_error[$item['product_no']] = '请先登录';
            return false;
        }

        $this->ci->load->model('gcoupon_model');
        $giftcard = $this->ci->gcoupon_model->dump(array('id'=>$item['gift_coupon_id']));

        $this->ci->load->bll('gcoupon');
        $giftsend = $this->ci->bll_gcoupon->check_gift_coupon($giftcard,$msg);
        if ($giftsend === false) {
            $this->_error[$item['product_no']] = $msg;
            return false;
        }

        // 判断ITEM是否包含
        if ($giftsend['id'] != $item['gift_send_id'] || !in_array($item['product_id'],explode(',',$giftsend['product_id']))) {
            $this->_error[$item['product_no']] = '礼品包不包含此商品';
            return false;
        }

        // $ik = $this->get_citem_key($item);
        // $qty = $op ? ((int) $item['qty'] + (int) $this->_cart_item[$ik]['qty']) : $item['qty'];
        if ($item['qty'] > $giftsend['qty']) {
            $this->_error[$item['product_no']] = '您已领取';
            return false;
        }

        // 计算页进入赋值
        $ik = $this->get_citem_key($item);
        if ($this->_cart_item[$ik]) {
            $this->_cart_item[$ik]['endtime'] = $giftsend['end'];
            $this->_cart_item[$ik]['user_gift_id'] = $giftsend['user_gifts']['id'];
        }

        return true;
    }

    // 验证换购
    private function _check_exch($item,$product)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($item,$product);
        }
        if (!$item['pmt_id']) {
            $this->_error[$item['product_no']] = '缺少必要参数';
            return false;
        }

        if (!$this->_cart_item) {
            $this->_error[$item['product_no']] = '您还未购买指定商品';
            return false;
        }

        $this->ci->load->model('promotion_model');
        $pmt = $this->ci->promotion_model->get_one_single_promotion($item['pmt_id']);
        if (!$pmt) {
            $this->_error[$item['product_no']] = '换购活动已结束';
            return false;
        }

        $pass = false;
        foreach ($this->_cart_item as $key => $value) {
            if ($value['item_type'] == 'normal' && in_array($value['product_id'],explode(',',$pmt['product_id']))) {
                $pass = true;
            }
        }
        if ($pass === false) {
            $this->_error[$item['product_no']] = '您还未购买指定商品';
            return false;
        }

        if ($item['qty'] > 1) {
            $this->_error[$item['product_no']] = '只能换购一件该商品';
            return false;
        }

        // 判断满足条件的商品是否存在

        return true;
    }

    /**
     * 校验购物车
     *
     * @return void
     * @author
     **/
    private function _check_cart_item($item)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($item);
        }
        if (!$item['sku_id'] || !$item['product_id'] || $item['qty'] < 1) {
            $this->_error[$item['product_no']] = '参数异常';
            return false;
        }

        if (!in_array($item['item_type'],array('normal','mb_gift','gift','user_gift','exch','coupon_gift','o2o','kjt', 'limit2buy', 'group','presell'))) {
            $this->_error[$item['product_no']] = '商品类型参数异常';
            return false;
        }

        // 商品读取
        $this->ci->load->model('product_model');
        $product = $this->ci->product_model->getProductSkus($item['product_id']);

        if (!$product['skus'][$item['sku_id']]) {
            $this->_error[$item['product_no']] = '商品不存在';
            return false;
        }

        if ((self::USE_EXCH_AS_LIMIT2BUY && $item['item_type'] === 'exch' && $item['pmt_id'] < 34) || $item['item_type'] === 'limit2buy') {
            return $this->_check_limit2buy($item, $product);
        } else if (method_exists($this, '_check_'.$item['item_type'])) {
            $valid = $this->{'_check_'.$item['item_type']}($item,$product);

            if ($valid == false) return false;
        }

        return true;
    }

    /**
     * 满额换购商品验证。
     */
    private function _check_limit2buy($item, $product)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($item, $product);
        }
        $check_normal = $this->_check_normal($item, $product);

        if ($check_normal === false) {
            return false;
        }

        $this->ci->load->model('promotion_model');
        $pmt = $this->ci->promotion_model->get_limit2buy_promotion($item['pmt_id']);

        $now = $_SERVER['REQUEST_TIME'];
        $end_time = strtotime($pmt['end_time']);

        if ($now > $end_time) {
            // 活动已结束。
            return false;
        }

        return true;
    }

    /**
     * o2o商品验证
     *
     * @return void
     * @author
     **/
    private function _check_o2o($item,$product)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($item,$product);
        }
        $this->ci->load->model('o2o_store_goods_model');
        $store_goods = $this->ci->o2o_store_goods_model->dump(array('store_id'=>$item['store_id'],'product_id'=>$item['product_id']));
        if (!$store_goods) {
            $this->_error[$item['product_no']] = '门店不提供商品['.$product['product_name'].']';
            return false;
        }

        // 库存判
        if ($item['qty'] > $store_goods['stock']) {
            $this->_error[$item['product_no']] =  '商品['.$product['product_name'].']库存不足';
            return false;
        }

        return true;
    }

    /**
     * 清理购物车
     *
     * @return void
     * @author
     **/
    private function _clear_cart_items()
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        if (!$this->_cart_item) return ;

        foreach ($this->_cart_item as $key => $value) {
            $rs = $this->_check_cart_item($value);

            if ($rs === false) {
                unset($this->_cart_item[$key]);
            }
        }
    }

    /**
     * 获取会员
     *
     * @return void
     * @author
     **/
    public function get_userid()
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        // if(is_object($this->ci->session)){
        //     $uid = $this->ci->session->userdata('id');

        //     if (!$uid) {
        //         $userdata = $this->ci->session->userdata('user_data');
        //         if ($userdata) {
        //             $userdata = @unserialize($userdata);
        //             $uid = $userdata['id'];
        //         }
        //     }
        // }
        $this->ci->load->library('login');
        $this->ci->login->init($this->_sessid);

        return $this->ci->login->get_uid();
    }

    /**
     * 设置会员ID
     *
     * @return void
     * @author
     **/
    // public function set_userid($uid)
    // {
    //     $this->_uid = $uid;

    //     return $this;
    // }

    /**
     * 限时惠检查
     *
     * @return void
     * @author
     **/
    private function _check_xsh($product,$item)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($product,$item);
        }
        $sku = $product['skus'][$item['sku_id']];
        $nowtime = time();
        if ($nowtime < strtotime($sku['start_time'])) {
            return '抢购活动还未开始';
        }
        if ($nowtime > strtotime($sku['over_time'])) {
            return '抢购活动已结束';
        }

        $uid = $this->get_userid();

        $this->ci->db->select_sum('qty')
                    ->from('order as o')
                    ->join('order_product as i','o.id=i.order_id','left')
                    ->where('o.uid',$uid)
//                    ->where('o.time >=',date('Y-m-d 00:00:00'))
//                    ->where('o.time <=',date('Y-m-d 59:59:59'))
                    ->where('i.product_id',$sku['product_id'])
                    ->where('o.order_status','1')
                    ->where('o.operation_id !=','5');

        if($product['is_xsh_time_limit']!=1){//不是永久
                $this->ci->db->where('o.time >=',date('Y-m-d 00:00:00'))
                    ->where('o.time <=',date('Y-m-d 59:59:59'));
                $sms = '此活动商品每个用户每天限购'.$product['xsh_limit'].'份';
        }else{
            $sms = '此活动商品每个用户限购'.$product['xsh_limit'].'份';
        }
        $limit_num = $this->ci->db->count_all_results();
//        if($uid==3614043){
//            $this->ci->load->library('fdaylog');
//            $db_log = $this->ci->load->database('db_log', TRUE);
//            $this->ci->fdaylog->add($db_log,'syt1',$uid."<-uid___".$product['xsh_limit']."<-后台限制个数____".$limit_num."<-买过个数".($limit_num + $item['qty']));
//        }
        return $product['xsh_limit'] < ($limit_num + $item['qty']) ? $sms : '';
    }

    /**
     *
     *
     * @return void
     * @author
     **/
    public function getsaleprice($skuinfo)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($skuinfo);
        }
        $sale_price = $skuinfo['price'];

        $this->ci->load->library('terminal');
        if ( ($this->ci->terminal->is_app() || $this->ci->terminal->is_wap()) && $skuinfo['mobile_price']>0 ) {
            $sale_price = $skuinfo['mobile_price'];
        }

        return $sale_price;
    }

    /**
     * 完善购物车信息
     *
     * @return void
     * @author
     **/
    private function perfect_cart_item()
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        // 读取会员价
        $this->ci->load->model('user_model');
        // $user_lv = array();
        // if ($this->ci->user_model->userinfo) {
        //     $user_lv = $this->ci->user_model->userinfo['badge'] ? unserialize($this->ci->user_model->userinfo['badge']) : '';
        // }

        $this->ci->load->model('product_model');
        // $this->ci->load->model('skus_model');

        // $skuids = array();
        // foreach ($cart_item as $item) {
        //     $skuids[] = $item['sku_id'];
        // }

        // $ppriceinfo = $this->ci->product_model->get_sku_product($skuids);
        // $ppriceinfo = array();
        // $skulist = $this->ci->skus_model->getList('*',array('id'=>$skuids));
        // foreach ($skulist as $value) {
        //     $product = array();
        //     if ($value['product_id'])
        //         $product = $this->ci->product_model->dump(array('id'=>$value['product_id']),'pay_limit,iscard,product_name,thum_photo,thum_min_photo,device_limit,card_limit,jf_limit,pay_limit,first_limit,active_limit,delivery_limit,group_limit,pay_discount_limit,free,offline,type,free_post,free_post,is_tuan,use_store,xsh,xsh_limit,ignore_order_money,group_pro');

        //     $ppriceinfo[$value['id']] = $value;
        //     $ppriceinfo[$value['id']]['productinfo'] = $product;
        // }

        foreach ($this->_cart_item as $key => $item) {
            // $sku_id = $item['sku_id'];

            $product = $this->ci->product_model->getProductSkus($item['product_id']);
            $sku = $product['skus'][$item['sku_id']];

            if (!$sku) continue;

            // 获取产品模板图片
            if ($product['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $product['bphoto'] = $templateImages['main']['big_image'];
                    $product['photo'] = $templateImages['main']['image'];
                    $product['middle_photo'] = $templateImages['main']['middle_image'];
                    $product['thum_photo'] = $templateImages['main']['thumb'];
                    $product['thum_min_photo'] = $templateImages['main']['small_thumb'];
                }
            }

            $this->_cart_info['items'][$key]                  = $item;



            $this->_cart_info['items'][$key]['name']               = $product['product_name'];
            $this->_cart_info['items'][$key]['unit']               = $sku['unit'];
            $this->_cart_info['items'][$key]['spec']               = $sku['volume'];
            // $this->_cart_info['items'][$key]['product_photo']      = PIC_URL. ($product['thum_min_photo'] ? $product['thum_min_photo'] : $product['thum_photo']);
            $this->_cart_info['items'][$key]['photo'] = array(
                    'huge'   => $product['bphoto'] ? PIC_URL.$product['bphoto'] : '',
                    'big'    => $product['photo'] ? PIC_URL.$product['photo'] : '',
                    'middle' => $product['middle_photo'] ? PIC_URL.$product['middle_photo'] : '',
                    'small'  => $product['thum_photo'] ? PIC_URL.$product['thum_photo'] : '',
                    'thum'   => $product['thum_min_photo'] ? PIC_URL.$product['thum_min_photo'] : '',
                );

            $this->_cart_info['items'][$key]['product_id']         = $sku['product_id'];
            $this->_cart_info['items'][$key]['product_no']         = $sku['product_no'];
            $this->_cart_info['items'][$key]['weight']             = $sku['weight'];
            $this->_cart_info['items'][$key]['device_limit']       = $product['device_limit']; // 是否限制一个设备只能买一次
            $this->_cart_info['items'][$key]['group_limit']       = $product['group_limit']; // 是否可以单独购买
            $this->_cart_info['items'][$key]['card_limit']         = $product['card_limit']; // 是否限制不能使用优惠券
            $this->_cart_info['items'][$key]['jf_limit']           = $product['jf_limit']; // 是否限制不能使用积分
            $this->_cart_info['items'][$key]['pay_limit']           = $product['pay_limit']; // 是否限制只能线上支付

            $this->_cart_info['items'][$key]['first_limit']        = $product['first_limit']; // 是否显示只能新用户购买
            $this->_cart_info['items'][$key]['active_limit']       = $product['active_limit']; // 是否限制不参加任何促销活动
            $this->_cart_info['items'][$key]['delivery_limit']     = $product['delivery_limit']; // 是否限制只能2-3天送达
            $this->_cart_info['items'][$key]['pay_discount_limit'] = $product['pay_discount_limit']; // 是否限制不参加支付折扣活动
            $this->_cart_info['items'][$key]['free']               = $product['free']; // 是否是企业专享商品
            $this->_cart_info['items'][$key]['offline']            = $product['offline']; // 是否是线下活动商品
            $this->_cart_info['items'][$key]['type']               = $product['type']; //商品类型，1:水果;2:生鲜
            $this->_cart_info['items'][$key]['free_post']          = $product['free_post']; //是否包邮
            $this->_cart_info['items'][$key]['free_post']   = $product['free_post']; //手机端是否包邮
            $this->_cart_info['items'][$key]['is_tuan']            = $product['is_tuan']; //是否在列表页隐藏
            $this->_cart_info['items'][$key]['use_store']          = $product['use_store']; //是否启用库存
            $this->_cart_info['items'][$key]['xsh']                = $product['xsh']; //是否是抢购商品(抢购时间，每人限购一份)
            $this->_cart_info['items'][$key]['xsh_limit']          = $product['xsh_limit']; //抢购商品限购数量
            $this->_cart_info['items'][$key]['qty_limit']          = ($product['xsh_limit'] && $product['xsh'] == 1)  ? $product['xsh_limit'] : ''; // 限购数据

            $this->_cart_info['items'][$key]['ignore_order_money'] = $product['ignore_order_money']; //是否无起送限制，单独收取运费
            $this->_cart_info['items'][$key]['group_pro']          = $product['group_pro']; //组合商品
            $this->_cart_info['items'][$key]['iscard']             = $product['iscard']; //组合商品

            $this->_cart_info['items'][$key]['expect']             = $product['expect']; //单独购买

            if ($item['item_type'] == 'normal') {
                $this->_cart_info['items'][$key]['price']         = $this->getsaleprice($sku);
                $this->_cart_info['items'][$key]['sale_price']    = $this->getsaleprice($sku);
                $this->_cart_info['items'][$key]['pmt_price']     = 0;
                $this->_cart_info['items'][$key]['amount']        = $this->_cart_info['items'][$key]['sale_price'] * $item['qty'];
                $this->_cart_info['items'][$key]['status']        = 'active';


                // if ($sku['mem_lv_price'] > 0
                //     && in_array($sku['mem_lv'],$user_lv) ) {
                //     // 享受会员价后，不享受商品优惠
                //     $this->_cart_info['items'][$key]['sale_price'] = $sku['mem_lv_price'];
                //     $this->_cart_info['items'][$key]['pmt_price'] = bcsub($this->_cart_info['items'][$key]['price'], $this->_cart_info['items'][$key]['sale_price'],3);

                //     $this->_cart_info['items'][$key]['pmt_pass'] = true;
                // }
            } elseif ($item['item_type'] == 'user_gift') {
                $this->_cart_info['items'][$key]['price']      = $sku['price'];
                $this->_cart_info['items'][$key]['sale_price'] = 0;
                $this->_cart_info['items'][$key]['pmt_price']  = $sku['price'];
                $this->_cart_info['items'][$key]['amount']     = 0;
                $this->_cart_info['items'][$key]['status']     = 'active';
                $this->_cart_info['items'][$key]['pmt_pass']   = true;
                $this->_cart_info['items'][$key]['pmt_details'] = array(0=>array('tag'=>'赠品','pmt_type'=>'user_gift','pmt_price'=>0));

            } elseif ($item['item_type'] == 'coupon_gift') {
                $this->_cart_info['items'][$key]['price']      = $sku['price'];
                $this->_cart_info['items'][$key]['sale_price'] = 0;
                $this->_cart_info['items'][$key]['pmt_price']  = $sku['price'];
                $this->_cart_info['items'][$key]['amount']     = 0;
                $this->_cart_info['items'][$key]['status']     = 'active';
                $this->_cart_info['items'][$key]['pmt_pass']   = true;
                $this->_cart_info['items'][$key]['pmt_details'] = array(0=>array('tag'=>'礼品券赠品','pmt_type'=>'coupon_gift','pmt_price'=>0));
            } elseif ($item['item_type'] == 'o2o') {
                $this->_cart_info['items'][$key]['price']      = $this->getsaleprice($sku);
                $this->_cart_info['items'][$key]['sale_price'] = $this->getsaleprice($sku);
                $this->_cart_info['items'][$key]['pmt_price']  = 0;
                $this->_cart_info['items'][$key]['amount']     = $this->_cart_info['items'][$key]['sale_price'] * $item['qty'];
                $this->_cart_info['items'][$key]['status']     = 'active';
                $this->_cart_info['items'][$key]['pmt_pass']   = true;
            }elseif ($item['item_type'] == 'kjt') {
                $this->_cart_info['items'][$key]['price']      = $this->getsaleprice($sku);
                $this->_cart_info['items'][$key]['sale_price'] = $this->getsaleprice($sku);
                $this->_cart_info['items'][$key]['pmt_price']  = 0;
                $this->_cart_info['items'][$key]['amount']     = $this->_cart_info['items'][$key]['sale_price'] * $item['qty'];
                $this->_cart_info['items'][$key]['status']     = 'active';
                $this->_cart_info['items'][$key]['pmt_pass']   = true;
            } elseif ($item['item_type'] == 'limit2buy' || (self::USE_EXCH_AS_LIMIT2BUY && $item['item_type'] == 'exch')) {
                $this->ci->load->model('promotion_model');
                $pmt = $this->ci->promotion_model->get_limit2buy_promotion($item['pmt_id']);

                foreach ($pmt['content_arr'] as $product_info) {
                    if ($product_info['product_sku_id'] == $item['sku_id']) {
                        $this->_cart_info['items'][$key]['sale_price'] = $product_info['product_price'];
                    }
                }

                $this->_cart_info['items'][$key]['status']     = 'active';
                $this->_cart_info['items'][$key]['price']      = $sku['price'];
                $this->_cart_info['items'][$key]['amount']     = $this->_cart_info['items'][$key]['sale_price'] * $item['qty'];
            }elseif ($item['item_type'] == 'group') {
                $this->_cart_info['items'][$key]['price']      = $this->getsaleprice($sku);
                $this->_cart_info['items'][$key]['sale_price'] = $this->getsaleprice($sku);
                $this->_cart_info['items'][$key]['pmt_price']  = 0;
                $this->_cart_info['items'][$key]['amount']     = $this->_cart_info['items'][$key]['sale_price'] * $item['qty'];
                $this->_cart_info['items'][$key]['status']     = 'active';
                $this->_cart_info['items'][$key]['pmt_pass']   = true;
            }
            elseif ($item['item_type'] == 'presell') {
                $this->_cart_info['items'][$key]['price']      = $this->getsaleprice($sku);
                $this->_cart_info['items'][$key]['sale_price'] = $this->getsaleprice($sku);
                $this->_cart_info['items'][$key]['pmt_price']  = 0;
                $this->_cart_info['items'][$key]['amount']     = $this->_cart_info['items'][$key]['sale_price'] * $item['qty'];
                $this->_cart_info['items'][$key]['status']     = 'active';
                $this->_cart_info['items'][$key]['pmt_pass']   = true;
            }

        }

    }

    /**
     * 获取能加个购物车的优惠商品
     *
     * @return void
     * @author
     **/
    public function selpmt($pmt_type,$pmt_id,$outofmoney = 0,$region_id=106092)
    {
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($pmt_id,$pmt_type);
        }
        $solution = array();

        if ($pmt_type == 'amount' || $pmt_type == 'costfreight') {
            $this->settle();

            if (!$this->_cart_info) return $solution;

            $this->ci->load->bll('pmt/order/condition/amount',null,'bll_pmt_order_condition_amount');
            $solution['pmt_type'] = $pmt_type;
            $solution['solution'] = $this->ci->bll_pmt_order_condition_amount->get_solution($pmt_id,$pmt_type,$this->_cart_info,$this->_province);

            return $solution;
        } else {
            $this->perfect_cart_item();
            if (!$this->_cart_info) return $solution;

            $this->ci->load->bll('pmt/product/process',null,'bll_pmt_product_process');
            $this->ci->bll_pmt_product_process->set_cart($this->_cart_info);
            $this->ci->bll_pmt_product_process->set_region_id($this->_region_id);

            $solution = $this->ci->bll_pmt_product_process->get_pmt_alert_solution($pmt_type,$pmt_id, self::USE_EXCH_AS_LIMIT2BUY);

            return $solution;
        }
    }

    //获取购物车商品列表  区分商品类型
    public function getCartProductID($cart_info){
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun($cart_info);
        }
        $pro_ids = array();
        foreach ($cart_info['items'] as $key => $value) {
             $pro_ids[$value['item_type']][] = $value['product_id'];
        }
        return $pro_ids;
    }

	public function get_user(){
		if (strcmp($this->_version, $this->_cmpversion) > 0) {
            $fun = __FUNCTION__;
            return $this->ci->bll_b2ccart->$fun();
        }
        $this->ci->load->library('login');
        $this->ci->login->init($this->_sessid);

        return $this->ci->login->get_user();
    }
}
