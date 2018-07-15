<?php
namespace bll;

class B2ccart {

    public $user;
    public $channel     = 'app';         // 入口终端pc/wap/app (channel/terminal)
    public $platform    = 'b2c';
    public $region_id   = '106092';
    public $province_id = '106092';
    public $version;
    public $cost;                       // 购物车总价
    public $total;                      // 购物车结算价(减去优惠金额)

    public $items    = [];
    public $contents = [];

    private $_province = '106092';      // 用户省份
    private $_source;                   // terminal 入口终端 1:pc 2:app 3:wap
    private $_cart_item  = [];          // cyc包含商品基本信息
    private $_cart_info  = [];          // cyc包含商品详细信息
    private $_error      = [];
    private $_notice     = [];


    public function __construct($params = []) {
        $this->ci = & get_instance();
        $this->ci->load->library('terminal');

        $this->_source = $this->ci->terminal->get_source();
        $this->channel = $this->ci->terminal->get_source();

        if ( isset($params['session_id']) ) {
            $this->ci->load->library('login');
            $this->ci->login->init($params['session_id']);
            $this->user = $this->ci->login->get_user();
        } else {
            $this->ci->load->library('login');
            $this->user = $this->ci->login->get_user();
        }

        $this->initCart();
    }

    /**
     * 重置用户省份
     *
     * @return void
     * @author
     **/
    public function set_province($region_id)
    {

        $this->ci->load->model('area_model');
        $province = $this->ci->area_model->getProvinceByArea($region_id);
        if ($province['id']) {
            $this->_province = $province['id'];
        }

        $this->region_id = $region_id;

        $this->setWarehouse();

        return $this;
    }



    public function setWarehouse() {

        $this->ci->load->config('region');
        $region_to_warehouse = $this->ci->config->item('region_to_cang');
        $this->warehouse = $region_to_warehouse[(int)$this->region_id];

    }

    public function setVersion($version) {
        $this->version = $version;
    }

    public function get_error()
    {
        return $this->_error;
    }

    public function setCart($cart_item)
    {
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
        $uid = $this->get_userid();

        if ($uid) {
            $this->ci->load->model('cart_model');
            $this->_cart_item = $this->ci->cart_model->get($uid);
        }
    }

    /**
     * 保存
     *
     * @return void
     * @author
     **/
    public function saveCart()
    {
        $uid = $this->get_userid();

        if ($uid) {
            $this->ci->load->model('cart_model');

            // todo
            // 改成false不要持久化
            // 蔡昀辰 2016
            $this->ci->cart_model->save($uid, $this->_cart_item, true);
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

    // 勾选
    // 根据sku_id上勾选已有商品
    public function select($sku_id) {

        foreach( $this->_cart_item as $key=>$item ) {
            if($item['sku_id'] == $sku_id)
                $this->_cart_item[$key]['selected'] = true;
        }

        foreach( $this->_cart_info['items'] as $key=>$item ) {
            if($item['sku_id'] == $sku_id)
                $this->_cart_info['items'][$key]['selected'] = true;
        }

        return $this;

    }

    // 不勾选
    // 根据sku_id上不勾选已有商品
    public function unselect($sku_id) {

        foreach( $this->_cart_item as $key=>$item ) {
            if($item['sku_id'] == $sku_id)
                $this->_cart_item[$key]['selected'] = false;
        }

        foreach( $this->_cart_info['items'] as $key=>$item ) {
            if($item['sku_id'] == $sku_id)
                $this->_cart_info['items'][$key]['selected'] = false;
        }

        return $this;

    }

    // 设置成无效
    public function disable($ik) {
        $this->_cart_info['items'][$ik]['valid'] = false;
    }

    public function get_cart_count()
    {
        $c = 0;

        foreach ((array) $this->_cart_item as $key => $value) {
            $c += (int) $value['qty'];
        }

        return $c;
    }

    // 返回redis中已勾选e而且有效的数量
    public function getSelected() {
        $count = 0;

        foreach( $this->_cart_info['items'] as $key=>$item ) {
            if($item['selected'] && $item['valid'])
                $count += $item['qty'];
        }

        return $count;
    }

    /**
     * 购物车ITEM key取值
     *
     * @return void
     * @author
     **/
    public function get_citem_key($item)
    {
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

        if (!isset($cart_item['item_type']))
            $cart_item['item_type'] = $item_type;

        $key = $this->get_citem_key($cart_item);

//todo by syt
        //只能购买一件限制商品
        $this->ci->load->model('product_model');
        // $today = date('Y-m-d',time());
        // if($today=='2015-08-22' || $today == '2015-08-23'){
        //     if(in_array($cart_item['product_id'],array(5345,5346,5347))){
        //         if (!$this->get_userid()) {
        //             $this->_error[$cart_item['product_no']] = '请先登录';
        //             return false;
        //         }
        //         $this->ci->load->model('order_model');
        //         $res = $this->ci->order_model->checkpf0823($cart_item['product_id'],$this->get_userid());
        //         if($res == false){
        //             $this->_error[$cart_item['product_no']] = "抱歉，您已经购买过啦，浦发专场活动秒杀商品每位用户仅限购买一次哦";
        //             return false;
        //         }
        //     }
        // }
        $product = $this->ci->product_model->getProductSkus($cart_item['product_id'],$this->warehouse);
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
        $this->ci->load->model('cart_model');
        $is_fan = $this->ci->cart_model->fan($this->_cart_item,$cart_item);
        if(is_array($is_fan)){
            $this->_error[$cart_item['product_no']] = $is_fan['msg'];
            return false;
        }

        if (isset($this->_cart_item[$key])) {
            $cart_item['qty'] += $this->_cart_item[$key]['qty'];
        }

        $rs = $this->_check_cart_item($cart_item);

        if ($rs == false)
            return false;

        $this->_cart_item[$key] = $cart_item;

        // 自动勾选
        $this->select($cart_item['sku_id']);

        // 再次全体校验一次
        $this->_clear_cart_items();

        $this->saveCart();

        return true;
    }


    // 删除
    // 蔡昀辰2016优化
    public function removeCart($ik, $item_type='normal') {
        if (!$ik)
            return false;

        // 删除
        unset($this->_cart_item[$ik]);

        // 礼品券
        $gift_coupon_id = $this->_cart_item[$ik]['gift_coupon_id'];
        foreach ($this->_cart_item as $key => $value) {
            if ($value['item_type'] == 'coupon_gift' && $value['gift_coupon_id'] == $gift_coupon_id) {
                unset($this->_cart_item[$key]);
            }
        }

        $this->saveCart();

        return true;
    }

    public function updateCart($cart_item,$item_type='normal')
    {
        if (!isset($cart_item['item_type'])) $cart_item['item_type'] = $item_type;

        if (!$this->_cart_item[$cart_item['ik']]){
            $this->_error[] = '购物车无此商品';
            return false;
        }

        $citem = $this->_cart_item[$cart_item['ik']];
        $citem['qty'] = $cart_item['qty'];

        $rs = $this->_check_cart_item($citem,false);

        if ($rs == false) return false;

        $this->_cart_item[$cart_item['ik']]['qty'] = (int) $cart_item['qty'];

        // $this->_clear_cart_items();
        $this->saveCart();

        return true;
    }

    public function emptyCart()
    {
        $this->_cart_item = array();

        $this->saveCart();

        return true;
    }

    // 删除已勾选的商品
    public function removeSelected() {

        foreach( $this->_cart_item as $key=>$item ) {
            if($item['selected'])
                unset($this->_cart_item[$key]);
        }

        $this->saveCart();

        return true;

    }

    public function get_cart_info($colfilter = array())
    {

        if ($this->_cart_item)
            $this->settle();

        // 过滤字段
        foreach ((array) $this->_cart_info['items'] as $key => $value) {
            if ($colfilter) {
                foreach ($value as $k => $v) {
                        if (in_array($k,$colfilter)) {
                            unset($this->_cart_info['items'][$key][$k]);
                        }
                }
            }

        }

        // 移除未勾选和失效商品
        foreach($this->_cart_info['items'] as $key=>$item) {
            if(!$item['selected'] || !$item['valid'])
                unset($this->_cart_info['items'][$key]);
        }

        return $this->_cart_info;
    }

    public function getContents($colfilter = []) {

        if ($this->_cart_item)
            $this->settle();

        // 过滤字段
        foreach ((array) $this->_cart_info['items'] as $key => $value) {
            if ($colfilter) {
                foreach ($value as $k => $v) {
                    if (in_array($k,$colfilter)) {
                        unset($this->_cart_info['items'][$key][$k]);
                    }
                }
            }
        }

        // 数组倒序排列
        $this->_cart_info['items'] = array_reverse($this->_cart_info['items']);

        return $this->_cart_info;

    }

    public function get_cart_items()
    {
        return $this->_cart_item;
    }

    // c输入购物车基本信息$this->_cart_item
    // c查询每个商品的详细信息 放入到$this->_cart_info
    // c营销规则
    public function settle() {

        $this->_cart_info = array();

        // 对ITEMS进行验证
        $this->_clear_cart_items();
        if (!$this->_cart_item)
            return;

        // cyc查询每个商品的详细信息
        $this->perfect_cart_item();

        // c优惠开始
        // promotion redis benchmark
        // $start = microtime(true);

        // 优惠引擎v1
        $this->ci->load->model('promotion_v1_model');
        $this->ci->promotion_v1_model->loadStrategies($this->channel, 'b2c', $this->region_id, $this->_province, $this->warehouse, $this->user['user_rank']);
        $this->_cart_info = $this->ci->promotion_v1_model->implementStrategies($this->_cart_info);


        // $end  = microtime(true);
        // $this->ci->load->library('fdaylog');
        // $db_log = $this->ci->load->database('db_log', TRUE);
        // $this->ci->fdaylog->add($db_log,'promotion benchmark', floor(($end - $start)*1000)."ms"  );
        // end

        // c检查换购活动的母商品是否存在
        $has_deal_pmt = array();  //已经参加活动ptm_id
        foreach($this->_cart_info['pmt_ids'] as $strategy){
            $pmt_ids[] = $strategy->id;
        }

        foreach($this->_cart_info['items'] as $k => $v){
            if( $v['pmt_id'] && !in_array($v['pmt_id'],$pmt_ids) ){
                $this->unselect($v['sku_id']);
                $this->disable($k);
                // if( $this->ci->env->notProd() )
                //     $this->removeCart($k);
                // unset($this->_cart_info['items'][$k]);
                // unset($this->_cart_item[$k] );
            }else{
                $has_deal_pmt[] = $v['pmt_id'];
            }
        }

        if($has_deal_pmt){
            foreach ($this->_cart_info['pmt_alert'] as $key => $alert) {
                if($alert['solution']['pmt_id'] && in_array($alert['solution']['pmt_id'], $has_deal_pmt)){
                    unset($this->_cart_info['pmt_alert'][$key]);
                }
            }
        }

        // todo：reomove
        $this->_cart_info = $this->pmt_details($this->_cart_info);

        // 会员赠品赠品提醒
        // 需要保留
        // 蔡昀辰 2016
        $draw_usergifts = [];
        foreach ($this->_cart_item as $key => $value) {
            if ($value['item_type'] == 'user_gift')
                $draw_usergifts[$value['gift_send_id']][] = $value['product_id'];
        }
    	$this->ci->load->bll('gsend');
    	$user_gift = $this->ci->bll_gsend->get_usergift_alert($this->get_userid(), $draw_usergifts, $this->_province, 0);
        if ($user_gift)
            $this->_cart_info['pmt_alert'][] = $user_gift;
        // end


        // 计算总价
        // cyc里面有满件减 这里要改造 checkProSale_upto
        $this->total();


        // 免费包邮提醒
        // 需要保留
        // 蔡昀辰 2016
        $this->ci->load->library('terminal');
        if ($this->ci->terminal->is_app()) {
            $this->ci->load->bll('costfreight');
            // if (strcmp($this->_version, '3.6.0') != 0) {
            //     $costfreight = $this->ci->bll_costfreight->cost_freight_alter($this->_cart_info,$this->region_id);
            // }else{
                $costfreight = $this->ci->bll_costfreight->cost_freight_alter_v2($this->_cart_info,$this->region_id);
            // }
            if ($costfreight) {
                $this->_cart_info['pmt_alert'][] = $costfreight;
            }
        }elseif($this->ci->terminal->is_wap()){
            $this->ci->load->bll('costfreight');
            // if (strcmp($this->_version, '3.6.0') != 0) {
            //     $costfreight = $this->ci->bll_costfreight->cost_freight_alter($this->_cart_info,$this->region_id);
            // }else{
                $costfreight = $this->ci->bll_costfreight->cost_freight_alter_v2($this->_cart_info,$this->region_id);
            // }
            if ($costfreight) {
                // $costfreight['solution']['url'] = false;
                $this->_cart_info['pmt_alert'][] = $costfreight;
            }
        }
        $this->_cart_info['pmt_alert'] = array_values($this->_cart_info['pmt_alert']);
        // end

    }

    private function total() {

        $this->ci->load->library('math');

        $total_amount = $pmt_goods = $goods_cost =  $goods_amount = 0;
        $cart_weight = 0.00;

        foreach ($this->_cart_info['items'] as $key=>$value) {

            if ($value['status'] != 'active') {
                unset($this->_cart_info['items'][$key]);
                continue;
            }

            // c未勾选的商品不计入总价不计入总重
            if($value['selected'] != true)
                continue;

            // c失效的商品不计入总价不计入总重
            if($value['valid'] != true)
                continue;

            // c赠品不计入总价不计入总重
            if($value['item_type'] == 'gift')
                continue;

            // c会员赠品不计入总价不计入总重
            if($value['item_type'] == 'user_gift')
                continue;

            // c满百赠品不计入总价不计入总重
            if($value['item_type'] == 'mb_gift')
                continue;

            // c购物券赠品不计入总价不计入总重
            if($value['item_type'] == 'coupon_gift')
                continue;

            $goods_amount = $this->ci->math->add($goods_amount,$value['amount']);

            $pmt_price_total = $this->ci->math->sub($value['price'] * $value['qty'],$value['amount']);
            $pmt_price_total = $pmt_price_total > 0 ? $pmt_price_total : 0;

            $this->_cart_info['items'][$key]['pmt_price_total'] = $pmt_price_total;

            $this->_cart_info['items'][$key]['goods_cost'] = $this->ci->math->mul($value['price'] , $value['qty']);

            $pmt_goods += $pmt_price_total; // todo: remove?
            $goods_cost = $this->ci->math->add($goods_cost,$value['price'] * $value['qty']);

            // 重量计算
            $pro_weight = bcmul($value['weight'],$value['qty'],2);
            $cart_weight = bcadd($cart_weight,$pro_weight,2);

        }

        $total_amount = $goods_amount;

        $this->_cart_info['total_amount'] = number_format($total_amount-$this->_cart_info['pmt_total'],2,'.','');
        $this->_cart_info['goods_amount'] = number_format($goods_amount,2,'.','');
        $this->_cart_info['goods_cost']   = number_format($goods_cost,2,'.','');
        $this->_cart_info['pmt_goods']    = $pmt_goods;
        $this->_cart_info['cost_freight'] = 0; // todo: remove
        $this->_cart_info['pmt_total']    = number_format($this->_cart_info['pmt_total'],2,'.','');  // todo:remove
        if( $this->_cart_info['total_amount'] < 0)
             $this->_cart_info['total_amount'] = 0;

        $this->_cart_info['cart_weight'] = '约'.$cart_weight.'kg';     // todo:remove
        $this->_cart_info['pro_weight']  = $cart_weight;                   // todo:change to weight

        /*$this->ci->load->model('cart_model');
        $uid = $this->get_userid();
        $pro_sale_upto_result = $this->ci->cart_model->checkProSale_upto($this->_cart_info,$uid,$this->_source);
        if($pro_sale_upto_result){
            $this->_cart_info['total_amount'] = $this->_cart_info['total_amount'] - $pro_sale_upto_result['cut_money'];
            //$this->_cart_info['total_amount'] = $this->_cart_info['total_amount'];
            $this->_cart_info['pmt_total'] = $this->_cart_info['pmt_total'] + $pro_sale_upto_result['cut_money'];
        }*/
        //error_log(var_export($this->_cart_info['pmt_order'] , true),3,"/tmp/ff.txt");
        //error_log(var_export($this->_cart_info['pmt_total'] , true),3,"/tmp/ff.txt");
    }

    // 验证普通商品
    private function _check_normal($item,$product)
    {
        $this->ci->load->model('cart_model');
        $this->ci->load->model('product_model');
        $sku_id = $item['sku_id']; $product_id = $item['product_id'];
        // 判断商品是否在架销售

        // 库存检查 @MIGRATED
        if ($product['lack'] == 1 ) {
            $this->_error[$item['product_no']] = '商品缺货';
            return false;
        }

        $stock_info = $this->ci->product_model->getRedisProductStock($sku_id,$this->warehouse,0,$item['product_no']);
        if(!empty($stock_info)){
            if ($stock_info['use_store'] == 1 ) {
                if($item['qty']>$stock_info['stock']){
                    if($stock_info['stock']>0){
//                        $stock_msg = "该商品库存仅剩".$stock_info['stock'].'件，请修改购买数量';
                        $stock_msg = "您购买的数量超出目前库存，请修改后再购买";
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
//                            $stock_msg = "该商品库存仅剩".$stock['stock'].'件，请修改购买数量';
                            $stock_msg = "您购买的数量超出目前库存，请修改后再购买";
                        }else{
                            $stock_msg = "该商品已缺货";
                        }
                        $this->_error[$item['product_no']] = $stock_msg;
                        return false;
                    }
                }
            }
        }
        //

        // @下架商品验证 @MIGRATED
        if($product['free'] != 1) { //企业团购商品不需要进此判断
            if($this->channel == 'app' && $product['app_online'] != 1) {
                $this->_error[$item['product_no']] = '商品已下架';
                return false;
            }
            if($this->channel == 'wap' && $product['mobile_online'] != 1) {
                $this->_error[$item['product_no']] = '商品已下架';
                return false;
            }
            if($this->channel == 'pc' && $product['online'] != 1) {
                $this->_error[$item['product_no']] = '商品已下架';
                return false;
            }
            if($product['skus'][$sku_id]['sku_online'] != 1) {
                $this->_error[$item['product_no']] = '商品已下架';
                return false;
            }
        }
        //

        // @DEPRECATED
        if( !$this->ci->config->item('is_enable_cang') ) {

            // 检查是否受区域限制
            $send_region = @unserialize($product['send_region']);

            if (is_array($send_region) && !in_array($this->_province,$send_region)) {
                $this->_error[$item['product_no']] = '您所在的地域无货';

                return false;
            }

        } else {
            $warehouse = explode(",", $product['cang_id']);

            if( is_array($warehouse) && !in_array($this->warehouse, $warehouse) ) {
                $this->_error[$item['product_no']] = '您所在的地域无货';
                return false;
            }
        }
        //


        // 限时惠商品 @DEPRECATED
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
        //

        //首够限制 @MIGRATED
        if($product['first_limit']==1){
            if (!$this->get_userid()) {
                $this->_error[$item['product_no']] = '请先登录';
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

        // @砍价成功用户限制 @DEPRECATED
        $this->ci->load->library('phpredis');
        $redis = $this->ci->phpredis->getConn();
        $limited_products = $redis->get('bargain:productidList');
        $limited_products = json_decode($limited_products);

        // @需要验证
        if( in_array($item['product_id'], $limited_products) ) {

            $this->ci->db->select('uid')->from('hd_limitbuy')->where('product_id', $item['product_id']);

            $valid_user_array = $this->ci->db->get()->result_array();
            $valid_users = [];
            foreach ($valid_user_array as $user) {
                array_push($valid_users, $user['uid']);
            }

            if( !in_array($this->user['id'], $valid_users) ) {
                $this->_error[$item['product_no']] = '您还没有购买资格哦，去APP首页参加砍价活动就可以带走我啦~';
                return false;
            }

        }
        //

        return true;
    }
    // 验证赠品
    private function _check_user_gift($item,$product)
    {
        if (!$item['gift_send_id']) {
            $this->_error[$item['product_no']] = '赠品参数错误';
            return false;
        }
        $uid = $this->get_userid();
        if (!$uid) {
            $this->_error[$item['product_no']] = '请先登录';
            return false;
        }
        $this->ci->load->model('user_gifts_model');
        if($item['user_gift_id']){
            $user_gift = $this->ci->user_gifts_model->dump(array('id'=>$item['user_gift_id'],'uid'=>$uid));
        }else{
            //$user_gift = $this->ci->user_gifts_model->dump(array('active_id'=>$item['gift_send_id'],'active_type'=>$item['gift_active_type'],'uid'=>$uid));
            $user_gift = $this->ci->db->query("select * from ttgy_user_gifts where active_id=".$item['gift_send_id']." and active_type=".$item['gift_active_type']." and uid=".$uid." order by id desc")->row_array();
        }
        if(empty($user_gift)){
            $this->_error[$item['product_no']] = '赠品异常';
            return false;
        }
        if ($user_gift['start_time'] > date('Y-m-d')) {
            $this->_error[$item['product_no']] = '赠品未到领取时间';
            return false;
        }
        if ($user_gift['end_time'] < date('Y-m-d')) {
            $this->_error[$item['product_no']] = '赠品过期';
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

            // if (!$item['user_gift_id'] && strtotime($gsend['start']) > time()) {
            //     $this->_error[$item['product_no']] = '赠品未到领取时间';
            //     return false;
            // }

            // if (!$item['user_gift_id'] && strtotime($gsend['end']) < time()) {
            //     $this->_error[$item['product_no']] = '赠品过期';
            //     return false;
            // }

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
            // $ugifts = $this->ci->user_gifts_model->dump(array('uid'=>$this->get_userid(),'active_id'=>$item['gift_send_id'],'has_rec'=>0));
            // if (!$ugifts) {
            //     $this->_error[$item['product_no']] = '赠品异常';
            //     return false;
            // }
        }elseif($item['gift_active_type']==1){
             //$trade_gifts = $this->ci->user_gifts_model->get_trade_gifts($this->get_userid(),$this->_province);
             // $trade_check_result = false;
             // foreach ($trade_gifts as $tk => $tv) {
             //     if($tv['gift_send_id']==$item['gift_send_id'] && $tv['product_id']==$item['product_id']){
             //        $trade_check_result = true;
             //        $gsend['qty'] = $tv['qty'];
             //        $gsend['end'] = $tv['end'];
             //        $gsend['order_money_limit'] = 0;
             //        $ugifts = array();
             //        $ugifts['id'] = $tv['user_gift_id'];
             //        break;
             //     }
             // }
             // if(!$trade_check_result){
             //    $this->_error[$item['product_no']] = '充值赠品异常';
             //    return false;
             // }
            $trade_gift = $this->ci->user_gifts_model->getTradeGift($uid,$item['gift_send_id']);
            if(empty($trade_gift)){
                $this->_error[$item['product_no']] = '充值赠品不存在';
                return false;
            }
            if($item['product_id'] != $trade_gift['bonus_products']){
                $this->_error[$item['product_no']] = '充值赠品异常';
                return false;
            }
            $gsend['qty'] = 1;
            $gsend['order_money_limit'] = 0;
        }

        // 判断赠品是否超出数量
        // $itemkey = $this->get_citem_key($item);
        // $qty = $op ? ( $item['qty'] + (int) $this->_cart_item[$itemkey]['qty']) : $item['qty'];
        if ($item['qty'] > $gsend['qty']) {
            // $this->_error[$item['product_no']] = '您只能领取'. $gsend['qty'].'件赠品，请到购物车查看';
            // @为了app判断弹窗跳转到购物车，文案改成写死的字符串，需求方：阙小曼 2016/9/21
            $this->_error[$item['product_no']] = '赠品已被领取啦，请去购物车查看';
            return false;
        }


        $itemkey = $this->get_citem_key($item);
        if ($this->_cart_item[$itemkey]) {
            $this->_cart_item[$itemkey]['endtime']      = $user_gift['end_time']?$user_gift['end_time']:$gsend['end'];
            $this->_cart_item[$itemkey]['user_gift_id'] = $item['user_gift_id']?$item['user_gift_id']:$user_gift['id'];
            $this->_cart_item[$itemkey]['order_money_limit']      = $gsend['order_money_limit'];
        }

        return true;
    }

    /**
     * 礼品赠品 @DEPRECATED
     *
     * @return void
     * @author
     **/
    private function _check_coupon_gift($item,$product)
    {
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
            $this->_cart_item[$ik]['endtime'] = $giftsend['user_gifts']['end_time'];
            $this->_cart_item[$ik]['user_gift_id'] = $giftsend['user_gifts']['id'];
        }

        return true;
    }

    // 验证换购 @MIGRATED
    private function _check_exch($item,$product)
    {

        // 老的换购验证
        // if (!$item['pmt_id']) {
        //     $this->_error[$item['product_no']] = '缺少必要参数';
        //     return false;
        // }

        if (!$this->_cart_item) {
            $this->_error[$item['product_no']] = '您还未购买指定商品';
            return false;
        }


        $this->ci->load->model('promotion_v1_model');
        $strategy = (array)$this->ci->promotion_v1_model->getOneStrategy($item['pmt_id']);


        if (!$strategy) {
            $this->_error[$item['product_no']] = '换购活动已结束';
        }
        $pass = false;
        $ptm_product_white = array_values($strategy['product']->white);
        $ptm_product_black = array_values($strategy['product']->black);
        foreach ($this->_cart_item as $key => $value) {
            if($ptm_product_white && $ptm_product_white[0]){
                if (in_array($value['product_id'],$ptm_product_white)) {
                    $pass = true;
                }
            }else{
                $pass = true;
            }
            if($ptm_product_black && $ptm_product_black[0]){
                if ( in_array($value['product_id'],$ptm_product_white)) {
                    $this->_error[$item['product_no']] = '您购物车总有商品和换购商品有冲突';
                    return false;
                }
            }
        }



        if ($pass === false) {
            $this->_error[$item['product_no']] = '您还未购买指定商品';
            $this->unselect($item->sku_id);
            // return false;
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
        $product = $this->ci->product_model->getProductSkus($item['product_id'],$this->warehouse);

        if (!$product['skus'][$item['sku_id']]) {
            $this->_error[$item['product_no']] = '商品不存在';
            return false;
        }

		/*
		 * 团购活动验证 start @DEPRECATED
		 */
		$uid = $this->get_userid();
		if ($uid) {
			$this->ci->load->model('cart_model');
			$xsh_check_result = $this->ci->cart_model->check_cart_hd_pro($item, $uid);
			if ($xsh_check_result) {
				$this->_error[$item['product_no']] = $xsh_check_result;
				return false;
			}
		}

		/*
		 * 团购活动验证end
		 */

        // @check
        if (method_exists($this, '_check_'.$item['item_type'])) {
            $valid = $this->{'_check_'.$item['item_type']}($item,$product);

            if ($valid == false)
                return false;
        }

        return true;
    }

    /**
     * 满额换购商品验证。 @DEPRECATED
     */
    private function _check_limit2buy($item, $product)
    {
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
     * o2o商品验证 @DEPRECATED
     *
     * @return void
     * @author
     **/
    private function _check_o2o($item,$product)
    {
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

    // 清理购物车
    private function _clear_cart_items() {
        if (!$this->_cart_item)
            return ;

        foreach ($this->_cart_item as $key => $value) {
            $ret = $this->_check_cart_item($value);

            // @检查返回失败设置成失效商品
            if ($ret === false)
                // unset($this->_cart_item[$key]);
                $this->_cart_item[$key]['valid'] = false;

        }

    }


    // 获取uid
    // 蔡昀辰2016优化
    public function get_userid() {
        return $this->user['id'];
    }

     // 限时惠检查 @DEPRECATED
    private function _check_xsh($product,$item)
    {
        $sku = $product['skus'][$item['sku_id']];
        $nowtime = time();
        if ($nowtime < strtotime($sku['start_time'])) { //@ store_product => limit_time_start
            return '抢购活动还未开始';
        }
        if ($nowtime > strtotime($sku['over_time'])) { // @ store_product => limit_time_end
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
            $sms = '此活动商品每个用户限购'.$product['xsh_limit'].'份'; // @store_product => limit_time_count
        }
        $limit_num = $this->ci->db->get()->row_array();
        $limit_num = $limit_num['qty'];
//        if($uid==3614043){
//            $this->ci->load->library('fdaylog');
//            $db_log = $this->ci->load->database('db_log', TRUE);
//            $this->ci->fdaylog->add($db_log,'syt1',$uid."<-uid___".$product['xsh_limit']."<-后台限制个数____".$limit_num."<-买过个数".($limit_num + $item['qty']));
//        }
        return $product['xsh_limit'] < ($limit_num + $item['qty']) ? $sms : '';
    }
    //

    /**
     *
     *
     * @return void
     * @author
     **/
    public function getsaleprice($skuinfo)
    {
        $sale_price = $skuinfo['price'];

        $this->ci->load->library('terminal');
        if ( ($this->ci->terminal->is_app() || $this->ci->terminal->is_wap()) && $skuinfo['mobile_price']>0 ) {
            $sale_price = $skuinfo['mobile_price'];
        }

        return $sale_price;
    }

    // 载入购物车内容
    private function loadContents() {
        $this->ci->load->model('item_v1_model');
        $this->ci->load->model('content_v1_model');

        foreach ($this->_cart_item as $key=>$item) {
            $item             = $this->ci->item_v1_model->create($item);
            $this->items[]    = $item;
            $this->contents[] = (array)$this->ci->content_v1_model->create($item);
        }

        $this->_cart_info['items'] = $this->contents;
    }

    /**
     * 完善购物车信息
     *
     * @return void
     * @author
     **/
    private function perfect_cart_item()
    {

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

            $product = $this->ci->product_model->getProductSkus($item['product_id'],$this->warehouse);
            $sku = $product['skus'][$item['sku_id']];

            if (!$sku)
                continue;

            // 获取产品模板图片
            if ($product['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product['template_id']);
                if (isset($templateImages['main'])) {
                    $product['bphoto'] = $templateImages['main']['big_image'];
                    $product['photo'] = $templateImages['main']['image'];
                    $product['middle_photo'] = $templateImages['main']['middle_image'];
                    $product['thum_photo'] = $templateImages['main']['thumb'];
                    $product['thum_min_photo'] = $templateImages['main']['small_thumb'];
                }
                if (isset($templateImages['whitebg'])) {
                    $product['thum_promotion_photo'] = $templateImages['whitebg']['thumb'];
                    $product['middle_promotion_photo'] = $templateImages['whitebg']['middle_image'];
                    $product['thum_min_promotion_photo'] = $templateImages['whitebg']['small_thumb'];
                }
            }

            $this->_cart_info['items'][$key]                        = $item;
            $this->_cart_info['items'][$key]['name']                = $product['product_name'];
            $this->_cart_info['items'][$key]['unit']                = $sku['unit'];
            $this->_cart_info['items'][$key]['spec']                = $sku['volume'];
            $this->_cart_info['items'][$key]['valid']               = $item['valid'] === false ? false : true; // 默认有效

            // $this->_cart_info['items'][$key]['product_photo']      = PIC_URL. ($product['thum_min_photo'] ? $product['thum_min_photo'] : $product['thum_photo']);
            $this->_cart_info['items'][$key]['photo'] = array(
                    'huge'   => $product['bphoto'] ? PIC_URL.$product['bphoto'] : '',
                    'big'    => $product['photo'] ? PIC_URL.$product['photo'] : '',
                    'middle' => $product['middle_photo'] ? PIC_URL.$product['middle_photo'] : '',
                    'small'  => $product['thum_photo'] ? PIC_URL.$product['thum_photo'] : '',
                    'thum'   => $product['thum_min_photo'] ? PIC_URL.$product['thum_min_photo'] : '',
                    'thum_promotion'=> $product['thum_promotion_photo'] ? PIC_URL.$product['thum_promotion_photo'] : '',
                    'thum_min_promotion'=> $product['thum_min_promotion_photo'] ? PIC_URL.$product['thum_min_promotion_photo'] : '',
                    'middle_promotion'=> $product['middle_promotion_photo'] ? PIC_URL.$product['middle_promotion_photo'] : '',
                );

            $this->_cart_info['items'][$key]['product_id']         = $sku['product_id'];
            $this->_cart_info['items'][$key]['product_no']         = $sku['product_no'];
            $this->_cart_info['items'][$key]['weight']             = $sku['weight'] ? $sku['weight'] : '';

            $this->_cart_info['items'][$key]['tag_id']             = $product['tag_id']; // 商品分类关联
            $this->_cart_info['items'][$key]['cart_tag']           = $product['cart_tag']; // 商品标签(新品, 第二件半价)
            $this->_cart_info['items'][$key]['cang_id']            = explode(",", $product['cang_id']); // 分仓
            $this->_cart_info['items'][$key]['device_limit']       = $product['device_limit']; // 是否限制一个设备只能买一次
            $this->_cart_info['items'][$key]['group_limit']        = $product['group_limit']; // 是否可以单独购买
            $this->_cart_info['items'][$key]['card_limit']         = $product['card_limit']; // 是否限制不能使用优惠券
            $this->_cart_info['items'][$key]['jf_limit']           = $product['jf_limit']; // 是否限制不能使用积分
            $this->_cart_info['items'][$key]['pay_limit']          = $product['pay_limit']; // 是否限制只能线上支付

            $this->_cart_info['items'][$key]['first_limit']        = $product['first_limit']; // 是否显示只能新用户购买
            $this->_cart_info['items'][$key]['active_limit']       = $product['active_limit']; // 是否限制不参加任何促销活动
            $this->_cart_info['items'][$key]['delivery_limit']     = $product['delivery_limit']; // 是否限制只能2-3天送达
            $this->_cart_info['items'][$key]['pay_discount_limit'] = $product['pay_discount_limit']; // 是否限制不参加支付折扣活动
            $this->_cart_info['items'][$key]['free']               = $product['free']; // 是否是企业专享商品
            $this->_cart_info['items'][$key]['offline']            = $product['offline']; // 是否是线下活动商品
            $this->_cart_info['items'][$key]['type']               = $product['type']; //商品类型，1:                 水果;2: 生鲜
            $this->_cart_info['items'][$key]['free_post']          = $product['free_post']; //是否包邮
            $this->_cart_info['items'][$key]['free_post']          = $product['free_post']; //手机端是否包邮
            $this->_cart_info['items'][$key]['is_tuan']            = $product['is_tuan']; //是否在列表页隐藏
            $this->_cart_info['items'][$key]['use_store']          = $product['use_store']; //是否启用库存
            $this->_cart_info['items'][$key]['xsh']                = $product['xsh']; //是否是抢购商品(抢购时间，每人限购一份)
            $this->_cart_info['items'][$key]['xsh_limit']          = $product['xsh_limit']; //抢购商品限购数量
            $this->_cart_info['items'][$key]['qty_limit']          = ($product['xsh_limit'] && $product['xsh'] == 1)  ? $product['xsh_limit']: ''; // 限购数据

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
            } elseif ($item['item_type'] == 'group') {
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
            }elseif ($item['item_type'] == 'exch') {
                $this->ci->load->model('promotion_v1_model');
                $strategy = (array)$this->ci->promotion_v1_model->getOneStrategy($item['pmt_id']);

                if(!$strategy)
                    continue;
                $this->_cart_info['items'][$key]['price']      = $strategy['solution']->add_money;
                $this->_cart_info['items'][$key]['sale_price'] = $strategy['solution']->add_money;
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
    /*public function selpmt($pmt_type,$pmt_id,$outofmoney = 0,$region_id=106092)
    {
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
            $this->ci->bll_pmt_product_process->set_region_id($this->region_id);

            $solution = $this->ci->bll_pmt_product_process->get_pmt_alert_solution($pmt_type,$pmt_id, self::USE_EXCH_AS_LIMIT2BUY);

            return $solution;
        }
    }*/
     public function selpmt($pmt_id,$pmt_type='amount') {
        $this->settle();
        $gift = array();

        foreach ($this->_cart_info['pmt_alert'] as $value) {
            if($pmt_id == $value['solution']['pmt_id'] && $value['solution']['type'] == 'exchange' && $value['solution']['tag'] == '换'){
                $gift = $this->get_ptm_product($pmt_id);
                break;
            }elseif($pmt_id == $value['solution']['pmt_id'] && $value['solution']['tag'] == '促'){
                $outofmoney                     = $value['solution']['outofmoney'];
                $gift['solution']               = $this->getCollectedProduct($outofmoney);
                $gift['solution']['title']      = rtrim($value['solution']['title'],'，去凑单');
                $gift['solution']['name']       = $value['solution']['name'];
                $gift['solution']['outofmoney'] = $value['solution']['outofmoney'];
                $gift['solution']['type']       = $pmt_type;
                $gift['type']                   = $pmt_type;
                break;
            }elseif($pmt_type=='costfreight' && $value['solution']['pmt_type']=='costfreight'){
                $outofmoney = $value['solution']['outofmoney'];
                $gift['solution'] = $this->getCollectedProduct($outofmoney);
                $gift['solution']['title'] = rtrim($value['solution']['title'],'，去凑单');
                $gift['solution']['name']       = $value['solution']['name'];
                $gift['solution']['outofmoney'] = $value['solution']['outofmoney'];
                $gift['solution']['type'] = $pmt_type;
                $gift['type'] = $pmt_type;
                break;
            }
        }
        if($gift){
            // $key  = array_keys($gift)[0];
            // $this->_cart_item[$key] = $gift[$key];
            // $this->saveCart();
            return $gift;
        }else{
            $this->_error[] = '活动已结束';
            return false;
        }
    }

    private function get_ptm_product($pmt_id) {

        $this->ci->load->model('promotion_v1_model');
        $strategy = (array)$this->ci->promotion_v1_model->getOneStrategy($pmt_id);


        if(!$strategy) return false;
        $product_id = $strategy['solution']->product_id;
        $add_money = $strategy['solution']->add_money;
        $number = $strategy['solution']->product_num;

        if(!$product_id)
            return false;

        // 可以换购多个
        $product_id = str_replace('，', ',', $product_id);
        $product_id = explode(',', $product_id);

        if(!is_array($product_id))
            $product_id = [$product_id];

        // 兼容下老版本只能换购第一个
        // if( version_compare($this->version, '3.9.0', '<') ) {

        //     $first_product = array_shift($product_id);
        //     $product_id = [];
        //     $product_id[] = $first_product;
        // }

        foreach($product_id as $pid) {
            // product table
            $product = $this->ci->db->select('id, product_name, bphoto, photo, middle_photo, thum_photo, thum_min_photo, template_id')
                ->from('product')->where('id', $pid)->limit(1)->get()->row();

            // 获取产品模板图片
            if ($product->template_id) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($product->template_id, 'main');
                if (isset($templateImages['main'])) {
                    $product->bphoto = $templateImages['main']['big_image'];
                    $product->photo = $templateImages['main']['image'];
                    $product->middle_photo = $templateImages['main']['middle_image'];
                    $product->thum_photo = $templateImages['main']['thumb'];
                    $product->thum_min_photo = $templateImages['main']['small_thumb'];
                }
            }

            // sku table
            $sku = $this->ci->db->select()->from('product_price')->where('product_id', $pid)->limit(1)->get()->row();
            $r = [
                    'pmt_id'     => $pmt_id,
                    'product_name'       => $product->product_name,
                    'sale_price'     => $strategy['solution']->add_money ,
                    'unit'             => $sku->unit,
                    'spec'             => $sku->volume,
                    'product_price_id' => $sku->id,
                    'product_no'       => $sku->product_no,
                    'price'            => $sku->price,
                    'product_id'       => $sku->product_id,
                    //'amount'     => $add_money*$product_num,
                    // 'tags'       => ['满额赠品'], // 为什么要这个字段？
                    'photo' => [
                        'huge'   => $product->bphoto ? PIC_URL.$product->bphoto : '',
                        'big'    => $product->photo ? PIC_URL.$product->photo : '',
                        'middle' => $product->middle_photo ? PIC_URL.$product->middle_photo : '',
                        'small'  => $product->thum_photo ? PIC_URL.$product->thum_photo : '',
                        'thum'   => $product->thum_min_photo ? PIC_URL.$product->thum_min_photo : '',
                    ],
                    // 'product_photo' => PIC_URL.($product->thum_min_photo ? $product->thum_min_photo : $product->thum_photo), // 为什么要这个字段？
                    // 'pmt_details'   => [0=>['tag'=>'满百赠品','pmt_id'=>$this->id,'pmt_type'=>'amount','pmt_price'=>0]]3, //  为什么要这个字段？

            ];
            $data['solution']['products'][] = $r;
            $data['solution']['type'] = 'exchange';
            $data['type'] = 'exchange';

        }
        return $data;
    }
    //获取购物车商品列表  区分商品类型
    public function getCartProductID($cart_info){
        $pro_ids = array();
        foreach ($cart_info['items'] as $key => $value) {
             $pro_ids[$value['item_type']][] = $value['product_id'];
        }
        return $pro_ids;
    }

    // 获取用户
    // 蔡昀辰2016优化
    public function get_user() {
        return $this->user;
    }


    // todo: remove
    public function pmt_details($cart_info){
        foreach($cart_info['items'] as $ck => $cv){
            if($cv['item_type'] == 'exch'){
                 $cart_info['items'][$ck]['pmt_details'] = array(0=>array('tag'=>'换购','pmt_type'=>'exch','pmt_price'=>0));
            }
            if($cv['item_type'] == 'user_gift' || $cv['item_type'] == 'gift' ){
                 $cart_info['items'][$ck]['pmt_details'] = array(0=>array('tag'=>'赠品','pmt_type'=>'user_gift','pmt_price'=>0));
            }
            if($cv['item_type'] == 'coupon_gift' ){
                 $cart_info['items'][$ck]['pmt_details'] = array(0=>array('tag'=>'礼品券赠品','pmt_type'=>'coupon_gift','pmt_price'=>0));
            }
        }
        return $cart_info;
    }

    function getCollectedProduct($outofmoney=0){
        $solution = array();
        $sql = 'SELECT p.product_name,p.bphoto,p.photo,p.middle_photo,p.thum_photo,p.thum_min_photo,p.cang_id,p.template_id,pp.*
                FROM ttgy_product AS p
                LEFT JOIN ttgy_product_price AS pp ON(p.id=pp.product_id)
                WHERE  p.channel="portal" AND p.lack=0 AND p.iscard=0 AND p.free=0 AND is_tuan = 0 AND p.offline=0 AND p.expect=0';

        $this->ci->load->library('terminal');
        if ($this->ci->terminal->is_app()) {
            $sql .= ' AND p.app_online=1';
        }
        if ($this->ci->terminal->is_wap()) {
            $sql .= ' AND p.mobile_online=1';
        }
        if ($this->ci->terminal->is_web()) {
            $sql .= ' AND p.online=1';
        }

        // added cang_id
        if(  $this->ci->config->item('promotion_warehouse') ) {
            // $sql .= " AND (cang_id LIKE \'' . $this->region_id . ',%\' OR cang_id LIKE \'%,' . $this->region_id . ',%\' OR cang_id LIKE \'%,' . $this->region_id . '\')";
            $sql .= " AND (p.cang_id like '{$this->warehouse},%' OR p.cang_id like '%,{$this->warehouse},%' OR p.cang_id like '%,{$this->warehouse}' OR p.cang_id = '{$this->warehouse}')";
            $sql .= ' AND pp.price >"'.$outofmoney.'" ORDER BY pp.price ASC LIMIT 10';
        } else {
            $sql .= ' AND p.send_region like "%'.$this->region_id.'%" AND pp.price >"'.$outofmoney.'" ORDER BY pp.price ASC LIMIT 10';
        }

        $products = $this->ci->db->query($sql)->result_array();

        // 预售
        $this->ci->load->model('presell_model');

        foreach ($products as $product) {

            // 排除预售商品
            $presell = $this->ci->presell_model->get_list($product['product_id']);
            if( !empty($presell) )
                continue;

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

            $solution['products'][] = array(
                'product_name'     => $product['product_name'],
                'sale_price'       => $product['price'],
                'photo'            => array(
                    'huge'             => $product['bphoto'] ? PIC_URL.$product['bphoto'] : '',
                    'big'              => $product['photo'] ? PIC_URL.$product['photo'] : '',
                    'middle'           => $product['middle_photo'] ? PIC_URL.$product['middle_photo'] : '',
                    'small'            => $product['thum_photo'] ? PIC_URL.$product['thum_photo'] : '',
                    'thum'             => $product['thum_min_photo'] ? PIC_URL.$product['thum_min_photo'] : '',
                ),
                'unit'             => $product['unit'],
                'spec'             => $product['volume'],
                'product_price_id' => $product['id'],
                'product_no'       => $product['product_no'],
                'price'            => $product['price'],
                'product_id'       => $product['product_id'],
                'cang_id'       => $product['cang_id'],
            );
        }

        return $solution;
    }

    public function getPmtInfo($params){
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        $region_id = $params['region_id'] ? $params['region_id'] : 0;

        /*登录初始*/
        $this->ci->load->library('login');
        $this->ci->login->init($session_id);

        $this->ci->load->bll('b2ccart',array('session_id'=>$session_id,'terminal'=>1));
        if ($region_id) $this->ci->bll_b2ccart->set_province($region_id);

        if (!$session_id && $carttmp = @json_decode($params['carttmp'],true)) {
            $this->ci->bll_b2ccart->setCart($carttmp);
        }


        $pmtId= $params['pmt_id'];

        $this->ci->load->model('promotion_v1_model');
        $pmtInfo = (array)$this->ci->promotion_v1_model->getOneStrategy($pmtId);

        return array('code' =>200, 'data'=>$pmtInfo, 'msg'=>'');
    }
}
