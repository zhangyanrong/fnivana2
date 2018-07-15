<?php
namespace bll\wap;
include_once("wap.php");
/**
* 商品相关接口
*/
class Group extends wap{
	private $_config = array(
		'tag' => '20151120',
		'start_time' => '2015-11-14',
		'end_time' => '2015-11-28',
		'limit_group_num' => '2000',  //限制可以创建的团数
		'stock_limit' => '3000' //团购商品限购数量
	);

	private $_config1 = array(
		'tag' => '20151201',
		'start_time' => '2015-11-30',
		'end_time' => '2015-12-28',
		'limit_group_num' => 100000,  //限制可以创建的团数
		'stock_limit' => 120000 //团购商品限购数量
	);

	function __construct(){
		$this->ci = &get_instance();
		$this->_filtercol = array(
			'device_limit',
			'card_limit',
			'jf_limit',
			'group_limit',
			'pay_limit',
			'first_limit',
			'active_limit',
			'delivery_limit',
			'pay_discount_limit',
			'free',
			'offline',
			'type',
			'free_post',
			'free_post',
			'is_tuan',
			'use_store',
			'xsh',
			'xsh_limit',
			'ignore_order_money',
			'group_pro',
			'iscard',
			'pmt_pass',
		);
	}

	/**
	* 订单初始化
	*
	* @return void
	* @author
	**/
	public function orderInit($params)
	{
		$connect_id  = $params['connect_id'] ? $params['connect_id'] : '';
		$region_id   = $params['region_id'] ? $params['region_id'] : 0;
//		$is_app = $params['is_app'] ? true : false;
		$items       = $params['items'] ? @json_decode($params['items'],true) : '';
		$item = array_shift($items);

		//不支持积分和优惠券
		$jfmoney     = 0;
		$card_number = '';
		//仅支持支付宝
		$payway['pay_parent_id'] = 7;
		$payway['pay_id'] = 0;

		if (!$connect_id)   return array('code'=>300,'msg'=>'param `connect_id` is required');
		if (!$region_id)    return array('code'=>300,'msg'=>'param `region_id` is required');
		if (!$item)        return array('code'=>300,'msg'=>'请先选择您需要购买的商品');

		$this->ci->load->library('login');
		$this->ci->login->init($connect_id);
		if (!$this->ci->login->is_login()) {
			$this->ci->session->sess_destroy();
			return array('code' => 400,'msg'=>'登录过期，请重新登录');
		}

//		/*时间选择列表start*/
//		$this->ci->load->bll($params['source'].'/region');
//		$obj = 'bll_' . $params['source'] . '_region';
//		$region_id = $params['region_id'] ? $params['region_id'] : 0;
//		if(!empty($order_info['address_id']) && !empty($order_info['order_address']['area']['id'])){
//			$send_time_params = array(
//				'service'=>'region.getSendTime',
//				'area_id'=>$order_info['order_address']['area']['id'],
//				'region_id'=>$region_id,
//				'connect_id'=>$params['connect_id'],
//
//			);
//			$send_time_arr = $this->ci->$obj->getSendTime($send_time_params);
//			$order_info['send_times'] = $send_time_arr;
//		}
//		/*时间选择列表end*/

//		/*配送时间重置start*/
//		$init_sendtime = false;
//		if(isset($order_info['shtime']['after2to3days']) && !isset($order_info['send_times']['date_key']) && $order_info['send_times']['date_key']!='after2to3days'){
//			$init_sendtime = true;
//		}elseif( !isset($order_info['shtime']['after2to3days']) && isset($order_info['send_times']['date_key']) && $order_info['send_times']['date_key']=='after2to3days'){
//			$init_sendtime = true;
//		}
//		if($init_sendtime){
//			$this->ci->order_model->sendtime_init($order_id);
//			$order_info['shtime'] = '';
//			$order_info['stime'] = '';
//		}
//		/*配送时间重置end*/

		//一次仅允许一件商品
		$cart_items = array();
		$cart_items[] = array(
			'sku_id'     => $item['ppid'],
			'product_id' => $item['pid'],
			'qty'        => 1,
			'product_no' => $item['pno'],
			'item_type'  => 'group',
			'active_id'=>$item['active_id'],//本次团的tag
		);

		$this->ci->load->model('group_model');
		$groupInfo = $this->ci->group_model->getGroupConfigData($item['active_id']);
		if(empty($groupInfo)){
			return array('code'=>300,'msg'=>'错误的入参');
		}
		$config = unserialize($groupInfo['config']);
		$config_type = $config['config_type'];

		$this->ci->load->bll('cart');
		$this->ci->bll_cart->set_province($region_id);
		$res = $this->ci->bll_cart->setCart($cart_items);//something to do;
		$error = $this->ci->bll_cart->get_error();

		if ($error) {
			return array('code'=>300,'msg'=>implode(';',$error));
		}

		$this->ci->load->bll('group');
		$rs = $this->ci->bll_group->orderInit($params['group_id'],$config_type);

		if (!$rs) {
			$code = $this->ci->bll_group->get_code();
			$error = $this->ci->bll_group->get_error();

			return array('code'=>$code ? $code : 300,'msg' => $error);
		}

		foreach ((array) $rs['cart_info']['items'] as $key => $value) {
			foreach ($value as $k => $v) {
				if (in_array($k,$this->_filtercol)) {
					unset($rs['cart_info']['items'][$key][$k]);
				}
			}
		}
		unset($rs['cart_info']['pmt_alert']);

		self::str($rs);
		return $rs;
	}
	public function createOrder($params){
		$address_id = $params['address_id'] ? $params['address_id'] : 0;
		$connect_id  = $params['connect_id'] ? $params['connect_id'] : '';
		$region_id   = $params['region_id'] ? $params['region_id'] : 0;
		$items       = $params['items'] ? @json_decode($params['items'],true) : '';
		$pay_parent_id = 7;
		$shtime = $params['shtime'] ? $params['shtime'] : '';
		$stime = $params['stime'] ? $params['stime'] : '';
		$group_id = $params['group_id']?$params['group_id']:'';
		$item = array_shift($items);

		$now_time = date('Ymd',strtotime('+48 hour'));
		//新年天团除外
		if ($group_id != 43396){
		    if($shtime<$now_time){
		        return array('code'=>300,'msg'=>'shtime is wrong'); //
		    }
		}

		if(!in_array($stime,array('weekday','weekend','all'))){
			return array('code'=>300,'msg'=>'stime is wrong');
		}

		//不支持积分和优惠券
		$jfmoney     = 0;
		$card_number = '';
		//仅支持微信
//		if(!in_array($pay_parent_id,array(1,7))){
//			return array('code'=>300,'msg'=>'param `pay_parent_id` is wrong');
//		}
		$payway['pay_parent_id'] = $pay_parent_id;
		$payway['pay_id'] = 0;



		//
		if(!$group_id)		return array('code'=>300,'msg'=>'param `group_id` is required');
		if (!$connect_id)   return array('code'=>300,'msg'=>'param `connect_id` is required');
		if (!$region_id)    return array('code'=>300,'msg'=>'param `region_id` is required');
		if (!$address_id)    return array('code'=>300,'msg'=>'请选择收货地址');
		if (!$item || !$item['pid'] || !$item['active_id'])        return array('code'=>300,'msg'=>'请先选择您需要购买的商品');
		if (!$pay_parent_id)   return array('code'=>300,'msg'=>'请先选择支付方式');

		$this->ci->load->library('login');
		$this->ci->login->init($connect_id);
		if (!$this->ci->login->is_login()) {
			$this->ci->session->sess_destroy();
			return array('code' => 400,'msg'=>'登录过期，请重新登录');
		}

		//一次仅允许一件商品
		$cart_items = array();
		$cart_items[] = array(
			'sku_id'     => $item['ppid'],
			'product_id' => $item['pid'],
			'qty'        => 1,
			'product_no' => $item['pno'],
			'item_type'  => 'group',
			'active_id'=>$item['active_id'],
		);

		$this->ci->load->bll('cart');
		$this->ci->bll_cart->set_province($region_id);
		$this->ci->bll_cart->setCart($cart_items);//something to do
		$error = $this->ci->bll_cart->get_error();
		if ($error) {
			return array('code'=>300,'msg'=>implode(';',$error));
		}

		if(!empty($params['tag'])){
			$this->_config = $this->_config1;
		}
		$this->ci->load->library('login');
		$this->ci->load->model('group_model');
		$uid = $this->ci->login->get_uid();
		$is_already_payed = $this->ci->group_model->already_payed($uid,$this->_config['tag']);
		if($is_already_payed){
			return array('code'=>300,'msg'=>'您已经参与过这个团购活动了，请下载app参与其他活动');
		}

		$is_not_cancel_not_pay = $this->ci->group_model->is_notpay($uid,$this->_config['tag']);
		if($is_not_cancel_not_pay){
			return array('code'=>300,'msg'=>'您还有进行中的团购订单没有支付，请先查看处理');
		}

		$this->ci->load->bll('group');
		$record_info = '';
		$rs = $this->ci->bll_group->createOrder($address_id, $record_info, $item['pid'], $item['ppid'],$shtime,$stime,$pay_parent_id,$params['tag']);

		if (!$rs) {
			$code = $this->ci->bll_group->get_code();
			$error = $this->ci->bll_group->get_error();
			return array('code'=>$code ? $code : 300,'msg'=>$error);
		}

		self::str($rs);
		return $rs;
	}

	public static function str(&$array)
	{
		if (is_array($array)) {
			foreach ($array as &$value) {
				if (is_array($value)) {
					self::str($value);
				} else {
					$value = strval($value);
				}
			}
		} else {
			$array = strval($array);
		}
	}
	private function _ck_product_store($product_id,$cang_id=0){
		$this->ci->load->model('product_model');
		$ck_res = true;
		//check  store
		$p = $this->ci->product_model->getProductSkus($product_id,$cang_id);
		if($p['use_store']==1){
			$ck_res = false;
			foreach($p['skus'] as $val){
				if($val['store']>0){
					$ck_res = true;
					break;
				}
			}
		}
		return $ck_res;
	}
	private function is_mobile($mobile) {
		if(preg_match("/^1[0-9]{10}$/",$mobile))
			return TRUE;
		else
			return FALSE;
	}
	private function is_eMail($email) {
		if(preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix",$email))
			return TRUE;
		else
			return FALSE;
	}
	private function validation_filter_id_card($id_card)
	{
		if(strlen($id_card) == 18)
		{
			return $this->idcard_checksum18($id_card);
		}
		elseif((strlen($id_card) == 15))
		{
			return false;
			// $id_card = $this->idcard_15to18($id_card);
			// return $this->idcard_checksum18($id_card);
		}
		else
		{
			return false;
		}
	}
	// 计算身份证校验码，根据国家标准GB 11643-1999
	private function idcard_verify_number($idcard_base)
	{
		if(strlen($idcard_base) != 17)
		{
			return false;
		}
		//加权因子
		$factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
		//校验码对应值
		$verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
		$checksum = 0;
		for ($i = 0; $i < strlen($idcard_base); $i++)
		{
			$checksum += substr($idcard_base, $i, 1) * $factor[$i];
		}
		$mod = $checksum % 11;
		$verify_number = $verify_number_list[$mod];
		return $verify_number;
	}
	// 将15位身份证升级到18位
	private function idcard_15to18($idcard){
		if (strlen($idcard) != 15){
			return false;
		}else{
			// 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
			if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false){
				$idcard = substr($idcard, 0, 6) . '18'. substr($idcard, 6, 9);
			}else{
				$idcard = substr($idcard, 0, 6) . '19'. substr($idcard, 6, 9);
			}
		}
		$idcard = $idcard . $this->idcard_verify_number($idcard);
		return $idcard;
	}
	// 18位身份证校验码有效性检查
	private function idcard_checksum18($idcard){
	if (strlen($idcard) != 18){ return false; }
		$idcard_base = intval(substr($idcard, 0, 17));

		if ($this->idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))){
			return false;
		}else{
			return true;
		}
	}

	public function createOrder_v1($params){
		$address_id = $params['address_id'] ? $params['address_id'] : 0;
		$connect_id  = $params['connect_id'] ? $params['connect_id'] : '';
		$region_id   = $params['region_id'] ? $params['region_id'] : 0;
		$items       = $params['items'] ? @json_decode($params['items'],true) : '';
		$pay_parent_id = 7;
		$shtime = $params['shtime'] ? $params['shtime'] : '';
		$stime = $params['stime'] ? $params['stime'] : '';
		$group_id = $params['group_id']?$params['group_id']:'';
		$item = array_shift($items);

		$now_time = date('Ymd',strtotime('+48 hour'));
		if($shtime<$now_time){
			// return array('code'=>300,'msg'=>'shtime is wrong'); //
		}
		if(!in_array($stime,array('weekday','weekend','all'))){
			return array('code'=>300,'msg'=>'stime is wrong');
		}

		//不支持积分和优惠券
		$jfmoney     = 0;
		$card_number = '';
		//仅支持微信
//		if(!in_array($pay_parent_id,array(1,7))){
//			return array('code'=>300,'msg'=>'param `pay_parent_id` is wrong');
//		}
		$payway['pay_parent_id'] = $pay_parent_id;
		$payway['pay_id'] = 0;



		//
		if(!$group_id)		return array('code'=>300,'msg'=>'param `group_id` is required');
		if (!$connect_id)   return array('code'=>300,'msg'=>'param `connect_id` is required');
		if (!$region_id)    return array('code'=>300,'msg'=>'param `region_id` is required');
		if (!$address_id)    return array('code'=>300,'msg'=>'请选择收货地址');
		if (!$item || !$item['pid'] || !$item['active_id'])        return array('code'=>300,'msg'=>'请先选择您需要购买的商品');
		if (!$pay_parent_id)   return array('code'=>300,'msg'=>'请先选择支付方式');

		$this->ci->load->library('login');
		$this->ci->login->init($connect_id);
		if (!$this->ci->login->is_login()) {
			$this->ci->session->sess_destroy();
			return array('code' => 400,'msg'=>'登录过期，请重新登录');
		}
		//一次仅允许一件商品
		$cart_items = array();
		$cart_items[] = array(
			'sku_id'     => $item['ppid'],
			'product_id' => $item['pid'],
			'qty'        => 1,
			'product_no' => $item['pno'],
			'item_type'  => 'group',
			'active_id'=>$item['active_id'],
		);

		$this->ci->load->bll('cart');
		$this->ci->bll_cart->set_province($region_id);
		$this->ci->bll_cart->setCart($cart_items);//something to do
		$error = $this->ci->bll_cart->get_error();
		if ($error) {
			return array('code'=>300,'msg'=>implode(';',$error));
		}

		$this->ci->load->library('login');
		$this->ci->load->model('group_model');
		$uid = $this->ci->login->get_uid();
		$is_already_payed = $this->ci->group_model->already_payed($uid,$params['tag']);

		if($is_already_payed){
			return array('code'=>300,'msg'=>'您已经参与过这个团购活动了，请下载app参与其他活动');
		}

		$is_not_cancel_not_pay = $this->ci->group_model->is_notpay($uid,$params['tag']);
		if($is_not_cancel_not_pay){
			return array('code'=>300,'msg'=>'您还有进行中的团购订单没有支付，请先查看处理');
		}

		$this->ci->load->bll('group');
		$record_info = '';
		$rs = $this->ci->bll_group->createOrder_v1($address_id, $record_info, $item['pid'], $item['ppid'],$shtime,$stime,$pay_parent_id,$params['tag'],$group_id);

		if (!$rs) {
			$code = $this->ci->bll_group->get_code();
			$error = $this->ci->bll_group->get_error();
			return array('code'=>$code ? $code : 300,'msg'=>$error);
		}

		self::str($rs);
		return $rs;
	}
}