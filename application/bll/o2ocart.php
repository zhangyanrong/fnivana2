<?php
namespace bll;

class O2ocart{
    private $_province   = '106092';    // 用户省份
    private $_source;                   // terminal 入口终端 1:pc 2:app 3:wap
    private $_uid        = 0;
    private $_sessid     = '';          // cyc就是API传入的connect_id

    private $_cart_item  = array();     // cyc包含商品基本信息
    private $_cart_info  = array();     // cyc包含商品详细信息
    private $_error      = array();
    private $_notice     = array();
    private $_use_coupon = array();     // 优惠券使用

    private $_o2oTuan_productids = array(8324, 8647, 8613, 8614, 8615, 8846);
    //private $_o2oGoddess_productids = array(9113,9114,9115,9116);
    private $_o2oGoddess2_productids = array(9296,9297,9298,9299);
    private $_o2oGoddess3_productids = array(9499,9500,9501,9502);
    private $_o2oGoddess4_productids = array(9628,9629,9642,9643);

    public function __construct($params = array())
    {
        $this->ci = & get_instance();

        $this->ci->load->library('terminal');
        $source = $this->ci->terminal->get_source();
        $this->_source = $source?$source:$params['terminal'];
        // if ($params['session_id']) $this->ci->load->library('session',array('session_id'=>$params['session_id']));

        // if (isset($params['terminal'])) $this->_terminal = $params['terminal'];
        if ($params['session_id']) $this->_sessid = $params['session_id'];

        $this->initCart();
    }

    /**
     * 重置用户省份
     *
     * @return void
     * @author
     **/
    public function set_province($region_id,$building_id = 0,$store_id = 0)
    {
        $store_id or $store_id = $this->getO2oCartStore();
        if($building_id || $store_id){
            $region_id = $this->getO2ORegion($building_id,$store_id);
        }
        //@storeid代替cangid
        $this->store_id = $store_id;
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
        $this->_use_coupon = $coupon;
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
            $this->ci->load->model('o2o_cart_model');

            $this->_cart_item = $this->ci->o2o_cart_model->get($uid);

            $this->_clear_cart_items();
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
        $uid = $this->get_userid();

		if ($uid) {
            $this->ci->load->model('o2o_cart_model');

            $this->ci->o2o_cart_model->save($uid,$this->_cart_item,true);
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

    public function get_cart_count()
    {
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
            case 'o2o':
                $key = 'o2o_'.$item['sku_id'].'_'.$item['store_id'];
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
    public function addCart($cart_item,$item_type='o2o')
    {

        if (!isset($cart_item['item_type'])) $cart_item['item_type'] = $item_type;

        $key = $this->get_citem_key($cart_item);

        $this->ci->load->model('product_model');


        if (isset($this->_cart_item[$key])) {
            $cart_item['qty'] += $this->_cart_item[$key]['qty'];
        }

        $rs = $this->_check_cart_item($cart_item);

        if ($rs == false)
			return false;

		$this->_cart_item[$key] = $cart_item;

		// 再次全体校验一次
        $this->_clear_cart_items();

        $this->saveCart();

        return true;
    }

    public function removeCart($ik,$item_type='o2o')
    {
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

        $this->_clear_cart_items();
        $this->saveCart();

        return true;
    }

    public function updateCart($cart_item,$item_type='o2o')
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

    public function clearCart($store_id){
        foreach ($this->_cart_item as $key => $value) {
            if($value['store_id'] == $store_id){
                unset($this->_cart_item[$key]);
            }
        }
        $this->saveCart();
        return true;
    }

    public function get_cart_info($colfilter = array())
    {
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

    public function settle()
    {
        // $this->_cart_info = array('use_coupon'=>array(0=>array('card_money'=>'500')));

        $this->_cart_info = array();

        // 对ITEMS进行验证
        // $this->check_cart_item($cart_item);
        // if (!$cart_item) return true;
        $this->_clear_cart_items();
        if (!$this->_cart_item) return ;

        // $this->_cart_info['use_coupon'] = $this->_use_coupon;

        $this->perfect_cart_item();

        // 计算总价
        $this->total();

        // cyc新购物车
        $this->ci->load->library('Promotion/promotion');
        $user = $this->get_user();
        $this->ci->promotion->loadStrategies($this->_source,'o2o', $this->_province, $user);
        $this->_cart_info = $this->ci->promotion->implementStrategies($this->_cart_info);

        // 优惠引擎v1
        // $this->ci->load->model('promotion_v1_model');
        // $this->ci->promotion_v1_model->loadStrategies('app', 'b2c', $this->_region_id, $this->_province, 1, $this->user['user_rank']);
        // $this->_cart_info = $this->ci->promotion_v1_model->implementStrategies($this->_cart_info);


        $has_deal_pmt = array();
        $has_deal_exch_pmt = array();
        $exchange_pmt = array();
        foreach ($this->_cart_info['items'] as $item) {
            $item['pmt_id'] and $has_deal_pmt[] = $item['pmt_id'];
            if($item['pmt_id'] && $item['item_type'] == 'exch'){
                $has_deal_exch_pmt[] = $item['pmt_id'];
            }
        }
        if($has_deal_pmt){
            foreach ($this->_cart_info['pmt_alert'] as $key => $alert) {
                if($alert['solution']['pmt_id'] && $alert['solution']['type']=='exchange' && $alert['solution']['tag']=='换'){
                    $exchange_pmt[] = $alert['solution']['pmt_id'];
                }
                if($alert['solution']['pmt_id'] && in_array($alert['solution']['pmt_id'], $has_deal_pmt)){
                    if($alert['solution']['type']=='exchange' && $alert['solution']['tag']=='促'){

                    }else{
                        unset($this->_cart_info['pmt_alert'][$key]);
                    }

                }
            }
            $can_not_use = array_diff($has_deal_exch_pmt, $exchange_pmt);
            if($can_not_use){
                foreach ($can_not_use as $ptm_id) {
                    foreach ($this->_cart_info['items'] as $key => $item) {
                        if($item['pmt_id'] == $ptm_id && $item['item_type'] == 'exch'){
                            unset($this->_cart_info['items'][$key]);
                        }
                    }
                    foreach ($this->_cart_item as $key => $value) {
                        if($value['item_type'] == 'exch' && $value['pmt_id'] == $ptm_id){
                            unset($this->_cart_item[$key]);
                        }
                    }
                }
                $this->saveCart();
            }
        }
        $this->total();

        $draw_usergifts = array();
        foreach ($this->_cart_item as $key => $value) {
            if ($value['item_type'] == 'user_gift') {
                $draw_usergifts[$value['gift_send_id']][] = $value['product_id'];
            }
        }
        $this->ci->load->bll('gsend');
        $user_gift = $this->ci->bll_gsend->get_usergift_alert($this->get_userid(),$draw_usergifts,$this->_province,1);
        if ($user_gift){
            $this->_cart_info['pmt_alert'][] = $user_gift;
        }
        //免费提醒
        $this->ci->load->library('terminal');
        if ($this->ci->terminal->is_app()) {
            $this->ci->load->bll('costfreight');
            $costfreight = $this->ci->bll_costfreight->o2o_cost_freight_alter($this->_cart_info,$this->_region_id);
            if ($costfreight) {
                $this->_cart_info['pmt_alert'][] = $costfreight;
            }
        }elseif($this->ci->terminal->is_wap()){
            $this->ci->load->bll('costfreight');
            $costfreight = $this->ci->bll_costfreight->o2o_cost_freight_alter($this->_cart_info,$this->_region_id);
            if ($costfreight) {
                $costfreight['solution']['url'] = false;
                $this->_cart_info['pmt_alert'][] = $costfreight;
            }
        }
        $this->_cart_info['pmt_alert'] = array_values($this->_cart_info['pmt_alert']);
    }

    /**
     * 确立配送时间
     *
     * @return void
     * @author
     **/
    private function _gen_shipping_time()
    {
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
        static $area;

        if (!$area[$this->_province]) {
            $this->ci->load->model('area_model');
            $area[$this->_province] = $this->ci->area_model->dump(array('id'=>$this->_province));
        }

        return $area[$this->_province];
    }

    private function total()
    {
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

        $this->_cart_info['total_amount'] = number_format($total_amount-$this->_cart_info['pmt_total'],2,'.','');
        $this->_cart_info['goods_amount'] = number_format($goods_amount,2,'.','');
        $this->_cart_info['goods_cost']   = number_format($goods_cost,2,'.','');
        $this->_cart_info['pmt_goods'] = $pmt_goods;
        $this->_cart_info['cost_freight'] = 0;
        $this->_cart_info['pmt_total'] = number_format($this->_cart_info['pmt_total'],2,'.','');
        //$this->_cart_info['pmt_total'] = $this->ci->math->add($this->_cart_info['pmt_goods'],$this->_cart_info['pmt_order']);

        // $this->ci->load->model('o2o_cart_model');
        // $uid = $this->get_userid();
        // $pro_sale_upto_result = $this->ci->o2o_cart_model->checkProSale_upto($this->_cart_info,$uid,$this->_source);
        // if($pro_sale_upto_result){
        //     $this->_cart_info['total_amount'] = $this->_cart_info['total_amount'] - $pro_sale_upto_result['cut_money'];
        //     $this->_cart_info['total_amount'] = (string)$this->_cart_info['total_amount'];
        //     $this->_cart_info['pmt_total'] = $this->_cart_info['pmt_total'] + $pro_sale_upto_result['cut_money'];
        // }
    }

    // 验证普通商品
    private function _check_normal($item,$product)
    {
        $this->ci->load->model('o2o_cart_model');
        $sku_id = $item['sku_id']; $product_id = $item['product_id'];
        // 判断商品是否在架销售

        if ($product['lack'] == 1 ) {
            $this->_error[$item['product_no']] = '商品缺货';
            return false;
        }

        if ($product['use_store'] == 1 ) {
            $stock = $this->ci->o2o_cart_model->getProStock($sku_id);
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

            if($gsend['gift_type'] == 0){
                $this->_error[$item['product_no']] = '该赠品非天天到家赠品';
                return false;
            }

            // if (strtotime($gsend['start']) > time()) {
            //     $this->_error[$item['product_no']] = '赠品未到领取时间';
            //     return false;
            // }

            // if (strtotime($gsend['end']) < time()) {
            //     $this->_error[$item['product_no']] = '赠品过期';
            //     return false;
            // }

            if (!in_array($item['product_id'],explode(',',$gsend['product_id']))) {
                $this->_error[$item['product_no']] = '赠品不包含在活动期间内';
                return false;
            }

            if($gsend['send_region'] && $gsend['send_region']!=','){
                $send_regions = explode(',', $gsend['send_region']);
                $region_arr = array();
                $this->ci->load->model('area_model');
                foreach ($send_regions as $region) {
                    $region_new = $this->ci->area_model->getProvinceByArea($region);
                    $region_new and $region_arr[] = $region_new['id'];
                    $region_new and $region_name[] = $region_new['name'];
                }
                if($region_arr && $this->_region_id && !in_array($this->_region_id,$region_arr)){
                    $region_name = array_unique($region_name);
                    $can_send = implode(',', $region_name);
                    $this->_error[$item['product_no']] = '赠品仅限'.$can_send.'领取';
                    return false;
                }
            }

            // 判断是否允许单领取
            if ($gsend['can_single_buy'] == 1) {
                $pass = false;
                foreach ($this->_cart_item as $key => $value) {
                    if ($value['item_type'] == 'o2o') {
                        $pass = true;break;
                    }
                }

                if ($pass === false) {
                    $this->_error[$item['product_no']] = '必须购物其他商品才能领取';
                    return false;
                }
            }
        }elseif($item['gift_active_type']==1){
            $this->_error[$item['product_no']] = '该赠品非天天到家赠品';
            return false;
             // $this->ci->load->model('user_gifts_model');
             // $trade_gifts = $this->ci->user_gifts_model->get_trade_gifts($this->get_userid(),$this->_province);
             // $trade_check_result = false;
             // foreach ($trade_gifts as $tk => $tv) {
             //     if($tv['gift_send_id']==$item['gift_send_id'] && $tv['product_id']==$item['product_id']){
             //        $trade_check_result = true;
             //        $gsend['qty'] = $tv['qty'];
             //        $gsend['end'] = $tv['end'];
             //        $gsend['order_money_limit'] = 0;
             //     }
             // }
             // if(!$trade_check_result){
             //    $this->_error[$item['product_no']] = '充值赠品异常';
             //    return false;
             // }
        }
        //$this->ci->load->model('user_gifts_model');
        // $ugifts = $this->ci->user_gifts_model->dump(array('uid'=>$this->get_userid(),'active_id'=>$item['gift_send_id'],'has_rec'=>0));
        // if (!$ugifts) {
        //     $this->_error[$item['product_no']] = '赠品异常';
        //     return false;
        // }

        // 判断赠品是否超出数量
        // $itemkey = $this->get_citem_key($item);
        // $qty = $op ? ( $item['qty'] + (int) $this->_cart_item[$itemkey]['qty']) : $item['qty'];
        if ($item['qty'] > $gsend['qty']) {
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
     * 礼品赠品
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
            $this->_cart_item[$ik]['endtime'] = $giftsend['end'];
            $this->_cart_item[$ik]['user_gift_id'] = $giftsend['user_gifts']['id'];
        }

        return true;
    }

    // 验证换购
    private function _check_exch($item,$product)
    {
        if (!$item['pmt_id']) {
            $this->_error[$item['product_no']] = '缺少必要参数';
            return false;
        }

        if (!$this->_cart_item) {
            $this->_error[$item['product_no']] = '您还未购买指定商品';
            return false;
        }

        $this->ci->load->model('strategy_model');
        $strategy = $this->ci->strategy_model->get($item['pmt_id']);
        if (!$strategy) {
            $this->_error[$item['product_no']] = '换购活动已结束';
        }

        // $this->ci->load->model('promotion_model');
        // $pmt = $this->ci->promotion_model->get_one_single_promotion($item['pmt_id']);
        // if (!$pmt) {
        //     $this->_error[$item['product_no']] = '换购活动已结束';
        // }

        $pass = false;
        $ptm_product_white = array_values($strategy['product']->white);
        $ptm_product_black = array_values($strategy['product']->black);
        foreach ($this->_cart_item as $key => $value) {
            if($ptm_product_white && $ptm_product_white[0]){
                if ($value['item_type'] == 'o2o' && in_array($value['product_id'],$ptm_product_white)) {
                    $pass = true;
                }
            }else{
                $pass = true;
            }
            if($ptm_product_black && $ptm_product_black[0]){
                if ($value['item_type'] == 'o2o' && in_array($value['product_id'],$ptm_product_white)) {
                    $this->_error[$item['product_no']] = '您购物车总有商品和换购商品有冲突';
                    return false;
                }
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
        if (!$item['sku_id'] || !$item['product_id'] || $item['qty'] < 1) {
            $this->_error[$item['product_no']] = '参数异常';
            return false;
        }

        if (!in_array($item['item_type'],array('mb_gift','gift','user_gift','exch','coupon_gift','o2o'))) {
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

        if (method_exists($this, '_check_'.$item['item_type'])) {
            $valid = $this->{'_check_'.$item['item_type']}($item,$product);

            if ($valid == false) return false;
        }

		$uid = $this->get_userid();
		if ($uid) {
			$this->ci->load->model('o2o_cart_model');
			$xsh_check_result = $this->ci->o2o_cart_model->check_cart_tuan_pro($item, $uid);
			if ($xsh_check_result) {
				$this->_error[$item['product_no']] = $xsh_check_result;
				return false;
			}
		}

		if(in_array($item['product_id'], $this->_o2oTuan_productids)){
            if($item['qty']>1){
                $this->_error[$item['product_no']] = '团购商品每单限购买1件';
                return false;
            }
            // if(count((array)$this->_cart_item) >= 1){
            //     $this->_error[$item['product_no']] = '团购商品只能单独购买';
            //     return false;
            // }
            foreach ($this->_cart_item as $key => $value) {
                if(!in_array($value['product_id'], $this->_o2oTuan_productids)){
                    $this->_error[$item['product_no']] = '团购商品只能单独购买';
                    return false;
                }
            }
            $result = $this->_check_o2o_tuan($msg, $item['product_id']);
            if($result == false){
                $this->_error[$item['product_no']] = $msg;
                return false;
            }
        }elseif(in_array($item['product_id'], $this->_o2oGoddess2_productids)){
            if($item['qty']>1){
                $this->_error[$item['product_no']] = '活动商品每单限购买1件';
                return false;
            }
            $result = $this->_check_goddess2($msg, $item['product_id']);
            if($result == false){
                $this->_error[$item['product_no']] = $msg;
                return false;
            }
        }elseif(in_array($item['product_id'], $this->_o2oGoddess3_productids)){
            if($item['qty']>1){
                $this->_error[$item['product_no']] = '活动商品每单限购买1件';
                return false;
            }
            $result = $this->_check_goddess3($msg, $item['product_id']);
            if($result == false){
                $this->_error[$item['product_no']] = $msg;
                return false;
            }
        }elseif(in_array($item['product_id'], $this->_o2oGoddess4_productids)){
            if($item['qty']>1){
                $this->_error[$item['product_no']] = '活动商品每单限购买1件';
                return false;
            }
            $result = $this->_check_goddess4($msg, $item['product_id']);
            if($result == false){
                $this->_error[$item['product_no']] = $msg;
                return false;
            }
        }else{
            foreach ($this->_cart_item as $key => $value) {
                if(in_array($value['product_id'], $this->_o2oTuan_productids)){
                    $this->_error[$item['product_no']] = '团购商品只能单独购买';
                    return false;
                }
            }
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
        $this->ci->load->model('o2o_store_goods_model');
        $store_goods = $this->ci->o2o_store_goods_model->dump(array('store_id'=>$item['store_id'],'product_id'=>$item['product_id']));
        if (!$store_goods) {
            $this->_error[$item['product_no']] = '门店不提供商品['.$product['product_name'].']';
            return false;
        }
        $this->ci->load->model('o2o_store_model');
        $store = $this->ci->o2o_store_model->dump(array('id'=>$item['store_id']));
        if(!$store || $store['isopen'] == 0){
            $this->_error[$item['product_no']] = '商品['.$product['product_name'].']门店尚未营业';
            return false;
        }
        // 库存判
        if ($item['qty'] > $store_goods['stock']) {
            $this->_error[$item['product_no']] =  '商品['.$product['product_name'].']库存不足';
            return false;
        }

        //限购
        if ($store_goods['qtylimit'] && $item['qty'] > $store_goods['qtylimit']) {
            $this->_error[$item['product_no']] =  '商品['.$product['product_name'].']限购'.$store_goods['qtylimit'].'件';
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

        //首次购买限制
        if($product['first_limit']==1){
            $uid = $this->get_userid();
            if (!$uid) {
                $this->_error[$item['product_no']] = '此商品为活动商品，请先登陆';
                return false;
            }else{
                $orders = $this->ci->db->select('count(*) as cou')
                    ->from('ttgy_order')
                    ->where(array('uid' => $uid, 'operation_id !=' => 5))
                    ->where_in('order_type', array(3, 4))
                    ->get()->row_array();
                if($orders['cou']){
                    $this->_error[$item['product_no']] = '此商品为闪电送新客专享商品，您可以挑选其他优惠商品';
                    return false;
                }
            }
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

    public function get_user(){
        $this->ci->load->library('login');
        $this->ci->login->init($this->_sessid);

        return $this->ci->login->get_user();
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
        $limit_num = $this->ci->db->get()->row_array();
        $limit_num = $limit_num['qty'];
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
        $sale_price = $skuinfo['price'];

        $this->ci->load->library('terminal');
        if ( ($this->ci->terminal->is_app() || $this->ci->terminal->is_wap()) && $skuinfo['mobile_price'] ) {
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

        // 读取会员价
        $this->ci->load->model('user_model');

        $this->ci->load->model('product_model');
        $this->ci->load->model('strategy_model');

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



            $this->_cart_info['items'][$key]['selected'] = true;
            $this->_cart_info['items'][$key]['valid']    = true;
            $this->_cart_info['items'][$key]['name']     = $product['product_name'];
            $this->_cart_info['items'][$key]['unit']     = $sku['unit'];
            $this->_cart_info['items'][$key]['spec']     = $sku['volume'];
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
            }elseif ($item['item_type'] == 'exch') {
                $strategy = $this->ci->strategy_model->get($item['pmt_id']);
                if(!$strategy) continue;
                $this->_cart_info['items'][$key]['price']      = $strategy['solution']->add_money;
                $this->_cart_info['items'][$key]['sale_price'] = $strategy['solution']->add_money;
                $this->_cart_info['items'][$key]['pmt_price']  = 0;
                $this->_cart_info['items'][$key]['amount']     = $this->_cart_info['items'][$key]['sale_price'] * $item['qty'];
                $this->_cart_info['items'][$key]['status']     = 'active';
                $this->_cart_info['items'][$key]['pmt_pass']   = true;
            }

        }

    }

    //获取购物车商品列表  区分商品类型
    public function getCartProductID($cart_info){
        $pro_ids = array();
        foreach ($cart_info['items'] as $key => $value) {
             $pro_ids[$value['item_type']][] = $value['product_id'];
        }
        return $pro_ids;
    }

    public function checkCartInit($latitude,$longitude,$building_id=0,$store_info,$is_unset=false){
        $uid = $this->get_userid();
        $this->ci->load->model('user_model');
        $user = $this->ci->user_model->getUser($uid);
        if (!$user) {
            $this->_error = '帐号异常，请重新登录试试';
            return false;
        }
        $s_ids = array();
        foreach ($this->_cart_item as $key => $value) {
            if($is_unset == false){
                $rs = $this->_check_cart_item($value);
                if ($rs === false) {
                    return false;
                }
            }
            if($value['item_type'] == 'o2o' || $value['item_type'] == 'exch'){
                $s_ids[] = $value['store_id'];
            }
        }
        $s_ids = array_unique($s_ids);
        if($s_ids){
            if($building_id){
                $this->ci->load->model('o2o_store_building_model');
                $result = $this->ci->o2o_store_building_model->getList('store_id',array('building_id'=>$building_id));
                //$sql = "select store_id from ttgy_o2o_store_building where building_id=".$building_id;
                //$result = $this->ci->db->query($sql)->result_array();
                foreach ($result as $key => $value) {
                    $store_ids[] = $value['store_id'];
                }
            }elseif($store_info){
                foreach ($store_info['store'] as $key => $value) {
                    $store_ids[] = $value['store_info']['id'];
                }
            }else{
                $this->ci->load->model('o2o_store_model');
                $this->ci->load->library('geohash');
                $this->ci->load->model('o2o_store_building_model');
                $this->ci->load->model('o2o_region_model');
                $hash = $this->ci->geohash->encode($latitude,$longitude);
                $hash = substr($hash,0,6);
                $neighbors = $this->ci->geohash->neighbors($hash);
                $neighbors['self'] = $hash;
                $result = array();
                foreach ($neighbors as $value) {
                    $sql = "select * from ttgy_o2o_region where attr=5 and geohash like '".$value."%'";
                    $region = $this->ci->db->query($sql)->result_array();
                    $result = array_merge($result,$region);
                }
                foreach ($result as $key => $value) {
                    $sql="select distinct(sb.store_id) from ttgy_o2o_store_building sb join ttgy_o2o_store s on s.id=sb.store_id where s.isopen=1 and sb.building_id=".$value['id'];
                    $store_building = $this->ci->db->query($sql)->result_array();
                    if (!$store_building) continue;
                    foreach ($store_building as $s_building) {
                        $store_ids[] = $s_building['store_id'];
                    }
                }
            }
            if($diff_arr = array_diff($s_ids,$store_ids)){
                if($is_unset==true){
                    foreach ($this->_cart_item as $key => $value) {
                        if($value['item_type'] == 'o2o'){
                            if(in_array($value['store_id'], $diff_arr)){
                                unset($this->_cart_item[$key]);
                                $this->saveCart();
                            }
                        }
                    }
                }else{
                    $this->_error[] = '您选择的商品无法配送至同一地址，请重新选择';
                    return false;
                }
            }
        }
        return true;
    }

    public function checkCartInit_v2($store_id,$is_unset=false){
        if(empty($store_id)) return true;
        $store_ids = array($store_id);
        $uid = $this->get_userid();
        $this->ci->load->model('user_model');
        $user = $this->ci->user_model->getUser($uid);
        if (!$user) {
            $this->_error = '帐号异常，请重新登录试试';
            return false;
        }
        $s_ids = array();
        foreach ($this->_cart_item as $key => $value) {
            if($is_unset == false){
                $rs = $this->_check_cart_item($value);
                if ($rs === false) {
                    return false;
                }
            }
            if($value['item_type'] == 'o2o' || $value['item_type'] == 'exch'){
                $s_ids[] = $value['store_id'];
            }
        }
        $s_ids = array_unique($s_ids);
        if($s_ids){
            if($diff_arr = array_diff($s_ids,$store_ids)){
                if($is_unset==true){
                    foreach ($this->_cart_item as $key => $value) {
                        if($value['item_type'] == 'o2o' || $value['item_type'] == 'exch'){
                            if(in_array($value['store_id'], $diff_arr)){
                                unset($this->_cart_item[$key]);
                                $this->saveCart();
                            }
                        }
                    }
                }else{
                    $this->_error[] = '您选择的商品无法配送至同一地址，请重新选择';
                    return false;
                }
            }
        }
        return true;
    }

    public function selpmt($pmt_id)
    {
        $this->settle();
        $gift = array();
        // $this->ci->load->model('strategy_model');
        // $strategy = $this->ci->strategy_model->get($pmt_id);
        // if(!$strategy){
        //     $this->_error[] = '活动已结束';
        //     return false;
        // }
        foreach ($this->_cart_info['pmt_alert'] as $value) {
            if($pmt_id == $value['solution']['pmt_id'] && $value['solution']['type'] == 'exchange'){
                $gift = $this->get_ptm_product($pmt_id);
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
        $this->ci->load->model('strategy_model');
        $strategy = $this->ci->strategy_model->get($pmt_id);
        if(!$strategy) return false;
        $product_id = $strategy['solution']->product_id;
        $add_money = $strategy['solution']->add_money;
        $number = $strategy['solution']->product_num;
        if($product_id){
            // product table
            $product = $this->ci->db->select('id, product_name, bphoto, photo, middle_photo, thum_photo, thum_min_photo, template_id')
                ->from('product')->where('id', $product_id)->limit(1)->get()->row();

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
            $sku = $this->ci->db->select()->from('product_price')->where('product_id', $product_id)->limit(1)->get()->row();
            $r = [
                    [
                    'pmt_id'     => $pmt_id,
                    'name'       => $product->product_name,
                    'sku_id'     => $sku->id,
                    'product_id' => $product->id,
                    'product_no' => $sku->product_no,
                    'item_type'  => 'exch',
                    'status'     => 'active',
                    'qty'        => $product_num ? $product_num : 1,
                    'unit'       => $sku->unit,
                    'spec'       => $sku->volume,
                    'price'      => $add_money,
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
                ]
            ];
            return $r;
        }
        return false;
    }

    function getO2ORegion($building_id = 0,$store_id = 0){
        $region_id = 0;
        if($building_id){
          $this->ci->load->model('o2o_region_model');
          $parents = $this->ci->o2o_region_model->getParents($building_id);
          $province=end($parents);
          $region_id = $province['area_id'];
        }elseif($store_id){
          $this->ci->load->model('o2o_store_model');
          $store = $this->ci->o2o_store_model->dump(array('id'=>$store_id));
          $region_id = $store['province_id'];
        }
        return $region_id;
    }

    function getO2oCartStore(){
        $store_id = 0 ;
        if($this->_cart_item){
            foreach ($this->_cart_item as $value) {
                if($value['store_id']){
                    $store_id = $value['store_id'];
                    break;
                }
            }
        }
        return $store_id;
    }

    function _check_o2o_tuan(&$msg, $product_id){
        $today_time = date("Y-m-d 00:00:00");
        $uid = $this->get_userid();
        if(!$uid){
            return false;
        }
        $tuan_tag = $product_id.date("md");
        $this->ci->load->model('active_model');
        $is_full = $this->ci->active_model->check_tuan_member($tuan_tag);
        if(!$is_full){
            $msg = "本团今日已满，请明天继续";
            return false;
        }
        $is_join = $this->ci->active_model->check_tuan_is_join($uid, $tuan_tag);
        if(!$is_join){
            $msg = "您已经参加，明天再来";
            return false;
        }
        $is_order = $this->ci->active_model->check_order_is_exists($uid, $product_id);
        if(!empty($is_order)){//曾经购买过
            if($is_order['time'] > $today_time){//今天购买了
                $msg = "您已经参加，明天再来";
                return false;
            }
            $is_share = $this->ci->active_model->check_user_share($uid, $today_time);//判断今天是否有分享
            if(!$is_share){//未分享
                $msg = "活动期间仅限成功参团购买一次哦，分享成功后可再获得再次购买资格";
                return false;
            }
        }
        return true;
    }

    function _check_goddess2(&$msg, $product_id){
        $uid = $this->get_userid();
        //$cur = $this->getmonsun();
        //$week_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y")));
        //$week_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y")));
        $week_start = date('2016-03-04 12:00:00');
        $week_end = date('2016-03-13 17:00:00');
        $week_start_timestamp = strtotime($week_start);
        $week_end_timestamp = strtotime($week_end);
        $today_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $today_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d"), date("Y")));
        // 查看今天是否购买过
        $sql = "select count(*) as bought from ttgy_order_product op left join ttgy_order o on op.order_id = o.id where o.uid={$uid} and op.product_id = '".$product_id."' and o.operation_id !=5 and o.order_type in(3,4) and o.time >= '" . $today_start . "' and o.time <= '" . $today_end . "'";
        $bought_num = $this->ci->db->query($sql)->row_array();
        $is_bought = $bought_num['bought'] > 0 ? 1 : 0;
        if ($is_bought == 1){
            $msg = "您已经购买，明天再来";
            return false;
        }
        // 判断是否有购买资格(当前周分享且已认证)
        $sql = "select count(*) as share_num from ttgy_share_num where active_tag = 'o2ogoddess2' and uid = {$uid} and time >= '{$week_start}' and time <= '{$week_end}'";
        $share_result = $this->ci->db->query($sql)->row_array();
        $is_share = $share_result['share_num'] > 0 ? 1 : 0;
        $sql = "SELECT COUNT(*) as count_goddess FROM `ttgy_o2o_goddess_assess2` WHERE uid = '" . $uid . "' AND (assess = '性感女神' or assess = '女神') and ctime >= UNIX_TIMESTAMP('".$week_start."') and ctime <= UNIX_TIMESTAMP('".$week_end."')";
        $count_result = $this->ci->db->query($sql)->row_array();
        $is_goddess = $count_result['count_goddess'] > 0 ? 1 : 0;
        $can_buy = 0;
        if ($is_share == 1 && $is_goddess == 1) {
            $can_buy = 1;
        } else {
            $msg = "您未获得购买资格";
            return false;
        }
        return true;
    }

    function _check_goddess3(&$msg, $product_id){
        $uid = $this->get_userid();
        //$cur = $this->getmonsun();
        //$week_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y")));
        //$week_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y")));
        $week_start = date('2016-03-11 12:00:00');
        $week_end = date('2016-03-20 17:00:00');
        $week_start_timestamp = strtotime($week_start);
        $week_end_timestamp = strtotime($week_end);
        $today_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $today_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d"), date("Y")));
        // 查看今天是否购买过
        $sql = "select count(*) as bought from ttgy_order_product op left join ttgy_order o on op.order_id = o.id where o.uid={$uid} and op.product_id = '".$product_id."' and o.operation_id !=5 and o.order_type in(3,4) and o.time >= '" . $today_start . "' and o.time <= '" . $today_end . "'";
        $bought_num = $this->ci->db->query($sql)->row_array();
        $is_bought = $bought_num['bought'] > 0 ? 1 : 0;
        if ($is_bought == 1){
            $msg = "您已经购买，明天再来";
            return false;
        }
        // 判断是否有购买资格(当前周分享且已认证)
        $sql = "select count(*) as share_num from ttgy_share_num where active_tag = 'o2ogoddess3' and uid = {$uid} and time >= '{$week_start}' and time <= '{$week_end}'";
        $share_result = $this->ci->db->query($sql)->row_array();
        $is_share = $share_result['share_num'] > 0 ? 1 : 0;
        $sql = "SELECT COUNT(*) as count_goddess FROM `ttgy_o2o_goddess_assess3` WHERE uid = '" . $uid . "' AND (assess = '气质女神' or assess = '女神') and ctime >= UNIX_TIMESTAMP('".$week_start."') and ctime <= UNIX_TIMESTAMP('".$week_end."')";
        $count_result = $this->ci->db->query($sql)->row_array();
        $is_goddess = $count_result['count_goddess'] > 0 ? 1 : 0;
        $can_buy = 0;
        if ($is_share == 1 && $is_goddess == 1) {
            $can_buy = 1;
        } else {
            $msg = "您未获得购买资格";
            return false;
        }
        return true;
    }

    function _check_goddess4(&$msg, $product_id){
        $uid = $this->get_userid();
        //$cur = $this->getmonsun();
        //$week_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1, date("Y")));
        //$week_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - date("w") + 7, date("Y")));
        $week_start = date('2016-03-18 12:00:00');
        $week_end = date('2016-03-27 17:00:00');
        $week_start_timestamp = strtotime($week_start);
        $week_end_timestamp = strtotime($week_end);
        $today_start = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $today_end = date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d"), date("Y")));
        // 查看今天是否购买过
        $sql = "select count(*) as bought from ttgy_order_product op left join ttgy_order o on op.order_id = o.id where o.uid={$uid} and op.product_id = '".$product_id."' and o.operation_id !=5 and o.order_type in(3,4) and o.time >= '" . $today_start . "' and o.time <= '" . $today_end . "'";
        $bought_num = $this->ci->db->query($sql)->row_array();
        $is_bought = $bought_num['bought'] > 0 ? 1 : 0;
        if ($is_bought == 1){
            $msg = "您已经购买，明天再来";
            return false;
        }
        // 判断是否有购买资格(当前周分享且已认证)
        $sql = "select count(*) as share_num from ttgy_share_num where active_tag = 'o2ogoddess4' and uid = {$uid} and time >= '{$week_start}' and time <= '{$week_end}'";
        $share_result = $this->ci->db->query($sql)->row_array();
        $is_share = $share_result['share_num'] > 0 ? 1 : 0;
        //$sql = "SELECT COUNT(*) as count_goddess FROM `ttgy_o2o_goddess_assess3` WHERE uid = '" . $uid . "' AND (assess = '气质女神' or assess = '女神') and ctime >= UNIX_TIMESTAMP('".$week_start."') and ctime <= UNIX_TIMESTAMP('".$week_end."')";
        //$count_result = $this->ci->db->query($sql)->row_array();
        //$is_goddess = $count_result['count_goddess'] > 0 ? 1 : 0;
        $can_buy = 0;
        if ($is_share == 1) {
            $can_buy = 1;
        } else {
            $msg = "您还未获得购买资格，请分享『做自己的百变女神』活动后购买";
            return false;
        }
        return true;
    }

    private function getmonsun(){
        $curtime=time();

        $curweekday = date('w');

         //为0是 就是 星期七
        $curweekday = $curweekday?$curweekday:7;


        $curmon = $curtime - ($curweekday-1)*86400;
        $cursun = $curtime + (7 - $curweekday)*86400;

        $cur['mon'] = $curmon;
        $cur['sun'] = $cursun;

        return $cur;
    }

    /**
     * 购物车提示(位于价格后面)
     * @return string
     */
    public function get_cart_alter(){
        $cart_info = $this->_cart_info;
        $this->ci->load->model('order_model');
        $postFee = $this->ci->order_model->getO2oPostFee();
        //购物车为空时
        if(empty($cart_info['total_amount'])){
            return '(满'.$postFee['limit'].'元包邮)';
        }


        $freight_check = $this->ci->order_model->check_cart_pro_status($cart_info);

        //检查购物车存在包邮商品 或 购物车金额满足包邮条件
        if ($freight_check['free_post']=='1' || $cart_info['total_amount'] >= $postFee['limit']) {
            return '(已包邮)';
        }

        //购物车金额不满足包邮条件
        return '(差' . bcsub($postFee['limit'], $cart_info['total_amount'], 2) . '元包邮)';
    }

    /**
     * 购物车运费
     * @return int
     */
    public function get_cart_freight()
    {
        $cart_info = $this->_cart_info;
        $this->ci->load->bll('o2o');
        return number_format($this->ci->bll_o2o->post_fee($cart_info, $cart_info['total_amount']), 2, '.', '');
    }

    /**
     * 检查购物车里是否有未结算商品
     * @param $latitude
     * @param $longitude
     * @return bool
     */
    public function checkCartExist($latitude,$longitude)
    {
        $uid = $this->get_userid();
        if (!$uid) {
            return false;
        }
        $s_ids = array();
        foreach ($this->_cart_item as $key => $value) {
            if($value['item_type'] == 'o2o' || $value['item_type'] == 'exch'){
                $s_ids[] = $value['store_id'];
            }
        }
        $s_ids = array_unique($s_ids);
        if(!$s_ids){
            return false;
        }
        $this->ci->load->bll('o2o');
        $storeBuildings = $this->ci->bll_o2o->getStoreBuildingIDs(0, $latitude, $longitude);
        $store_ids = $storeBuildings['store_ids'];
        return !array_diff($s_ids, $store_ids) ? true : false;
    }

    /**
     * 检查购物车里是否有未结算商品
     * @param $latitude
     * @param $longitude
     * @return bool
     */
    public function checkCartExist_v2($latitude,$longitude, $region_id=0)
    {
        $uid = $this->get_userid();
        if (!$uid) {
            return false;
        }
        $sid = 0;
        foreach ($this->_cart_item as $key => $value) {
            if($value['item_type'] == 'o2o' || $value['item_type'] == 'exch'){
                $sid = $value['store_id'];
                break;
            }
        }
        if(!$sid){
            return false;
        }
        $this->ci->load->bll('deliver');
        $deliver = $this->ci->bll_deliver->getO2oTmsCode(array(
            'lonlat' => "$longitude,$latitude",
            'region_id' => $region_id
        ));
        $store_id = $deliver['store_id'] ? $deliver['store_id'] : 0;
        return $sid == $store_id ? true : false;
    }

}
