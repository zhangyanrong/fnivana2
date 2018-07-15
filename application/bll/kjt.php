<?php
namespace bll;

class Kjt
{
	private $_orderinit = array();
	private $_error = '';
	private $_code = 300;
	private $stock_limit = 300;

	function __construct() {
		$this->ci = &get_instance();
		$this->ci->load->helper('public');
	}
	public function get_error()
	{
		return $this->_error;
	}
	public function get_code()
	{
		return $this->_code;
	}

	public function productInfo($params){
		$this->ci->load->model('product_model');
		$this->ci->load->model('kjt_model');

		//check
		$required_fields = array(
			'id' => array('required' => array('code' => '300', 'msg' => 'product id can not be null')),
		);
		$required_fields = array(
			'active_id' => array('required' => array('code' => '300', 'msg' => 'active id can not be null')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}
		//get active info
		$kjt_res = $this->ci->kjt_model->dump(array('id'=>$params['active_id']), 'product_id');
		$kjt_product_ids = explode(",", $kjt_res['product_id']);
		if(!in_array($params['id'], $kjt_product_ids)){
			return array('code' => 300, 'msg' => '该活动不存在');
		}
		//get product info
		$id = $params['id'];
		$default_channle = $this->ci->config->item('default_channle');
		if (in_array($params['channel'], $default_channle)) {
			$params['channel'] = 'portal';
		}
		$channel = (isset($params['channel']) && !empty($params['channel'])) ? $params['channel'] : 'portal';
		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $channel;
			$result = $this->ci->memcached->get($mem_key);
			if ($result) {
				return $result;
			}
		}
		$region_to_warehouse = $this->ci->config->item('region_to_cang'); 
        $cang_id = $region_to_warehouse[$params['region_id']];
		$result = $this->ci->product_model->get_product_kjt($id, $channel, $params['source'],$cang_id);
		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $channel;
			$this->ci->memcached->set($mem_key, $result, 1800);
		}
		return $result;
	}

	public function orderInit($address_id='')
	{
		// 平台
		$this->ci->load->library('terminal');
		$source = $this->ci->terminal->get_source();

		$this->_orderinit = array();

		$this->ci->load->bll('cart');
		$cart = $this->ci->bll_cart->get_cart_info();
		if (!$cart['items']) {
			$error = $this->ci->bll_cart->get_error();
			$this->_error = implode(';',$error);
			return false;
		}

		$cart['items'] = array_values($cart['items']);
		$this->_orderinit['cart_info'] = $cart; 

		$this->ci->load->library('login');
		$this->_orderinit['uid'] = $this->ci->login->get_uid();

		// 初始化价格
		$this->_orderinit['goods_money']  = $cart['total_amount'];
		$this->_orderinit['method_money'] = 0;
		$this->_orderinit['pay_discount'] = 0;
		$this->_orderinit['jf_money']     = 0;
		$this->_orderinit['card_money']   = 0;
		$this->_orderinit['pmoney']       = $cart['goods_cost'];
		$this->_orderinit['msg']          = false;

		// 初始化订单金额
		$rs = $this->_order_total_init();
		if (!$rs) return false;

		$this->_orderinit['need_authen_code'] = 0;
		$this->_orderinit['shtime'] = 'after2to3days';
		$this->_orderinit['stime']            = '';

		// 积分/优惠券限制
		$this->ci->load->model('order_model');
		$uselimit = $this->ci->order_model->check_cart_pro_status($cart);
		$this->_orderinit['can_use_card'] = $uselimit['card_limit'] == '1' ? 0 : 1;
		$this->_orderinit['can_use_jf']   = $uselimit['jf_limit'] == '1' ? 0 : 1;
		$this->_orderinit['jf_limit_pro'] = $uselimit['jf_limit_pro'];

		// 初始化发货时间
		$rs = $this->_order_shtime_init();
		if (!$rs) return false;

		// 初始化会员
		$rs = $this->_user_init();
		if (!$rs) return false;

		// 初始化收货地址
		$rs = $this->_order_address_init($address_id);
		if (!$rs) return false;

		// 初始化支付方式
		$rs = $this->_order_pay_init();
		if (!$rs) return false;

		// 初始化积分
		$rs = $this->_order_jfmoney_init();
		if (!$rs) return false;

		// 初始化卡券
		$rs = $this->_order_card_init();
		if (!$rs) return false;

		$rs = $this->_order_total_init();
		if (!$rs) return false;

		$this->_orderinit['card_number'] = '';
		return $this->_orderinit;
	}
	public function createOrder($address_id, $record_info, $product_id, $price_id){
		$this->ci->load->library('login');
		$this->ci->load->model('product_model');
		$uid = $this->ci->login->get_uid();

		//ck product store
		$ck_res = $this->_ck_product_store($product_id);
		if($ck_res == false){
			return array('code' => 300, 'msg' => '商品已经售罄');
		}

		//黑名单验证
		$this->ci->load->model('user_model');
		// if($user_black = $this->ci->user_model->check_user_black($uid)){
		// 	if($user_black['type']==1){
		// 		$this->_error = '果园君发现您的账号为无效手机号，为保证您的购物体验请用有效手机号注册，敬请谅解。';  
		// 	}else{
		// 		$this->_error = '您的帐号可能存在安全隐患，暂时冻结，请联系客服处理';  
		// 	}
		// 	return false;
		// }

		$data = $this->orderInit($address_id);
		if (!$data) return false;
		
		// 收货地址验证
		if (!$data['order_address']['name']) {
			$this->_error = '请完善收货人信息';
			return false;
		}
		if (!$data['order_address']['address']) {
			$this->_error = '请完善收货人地址';
			return false;
		}
		if (!$data['order_address']['mobile']) {
			$this->_error = '请完善收货人手机信息';
			return false;
		}
		if (!is_numeric($data['order_address']['mobile']) || strlen(strval($data['order_address']['mobile'])) != 11) {
			$this->_error = '请正确填写手机信息';
			return false;
		}
		//region result
		$sendRegionFilterResult = $this->sendRegionFilter($address_id,$data['cart_info']);
		if($sendRegionFilterResult!==true){
			$region_filter_result_pro_name = '';
			foreach($sendRegionFilterResult as $filter_v){
				$region_filter_result_pro_name .= $filter_v.',';
			}
			$this->_error = "您购买的商品：“".trim($region_filter_result_pro_name,',')."”无法配送到您的收货地址";  
			return false;
		}

		$this->ci->db->trans_begin();
		$score = $this->_order_score($data); 
		$this->ci->load->library('terminal');

		$order = array(
			'order_name'    => '',
			'pay_status'    => $data['money'] == 0 ? 1 : 0,
			'money'         => $data['money'],
			'pmoney'        => $data['goods_money'],
			'goods_money'   => $data['goods_money'],
			'score'         => $score,
			// 'msg'           => '',
			// 'hk'            => '',
			'method_money'  => $data['method_money'],
			'order_status'  => '1',
			'time'          => date('Y-m-d H:i:s'),
			// 'order_region'  => $data['order_region'],
			'channel'       => $this->ci->terminal->get_channel(),
			// 'is_enterprise' => $enter_tag,
			// 'sales_channel' => $data['sales_channel'],
			'pay_discount'  => $data['pay_discount'],
			'version'       => 1,
			'pay_parent_id' => $data['pay_parent_id'],
			'pay_id'        => $data['pay_id'],
			'pay_name'      => $data['pay_name'],
			'jf_money'      => $data['jf_money'],
			'use_jf'        => $data['use_jf'],
			'use_card'      => $data['use_card'],
			'card_money'    => $data['card_money'],
			'uid'           => $uid,
			'shtime'        => $data['shtime'],
			'stime'         => '',
			'order_type'    => 6,
			'address_id'    => 0,
		);
		if ($ordermsg['msg']) {
			$order['msg'] = $ordermsg['msg'];
		}
		if($order['pay_status'] == 1){
	        $order['update_pay_time'] = date('Y-m-d H:i:s');
	        $order['pay_time'] = date('Y-m-d H:i:s');
      	}
		$this->ci->load->model('order_model');
		$order_id = $this->ci->order_model->generate_order('order',$order);
		if (!$order_id) {
			$this->ci->db->trans_rollback();
			$this->_error = '出错啦,请重新提交1';
			return false;
		}
		$order = $this->ci->order_model->dump(array('id' => $order_id ));
		
		// 生成明细
		$rs = $this->orderAddPro($uid,$order_id,$data['cart_info']);
		if (!$rs) {
			$this->ci->db->trans_rollback();
			$this->_error = '出错啦,请重新提交2';
			return false;
		}
		// 生成地址
		$rs = $this->orderAddAddr($order_id,$data['order_address']);
		if (!$rs) {
			$this->ci->db->trans_rollback();
			$this->_error = '出错啦,请重新提交3';
			return false;
		}
		// 保存kjt订单
		$rs = $this->orderAddKjt($order_id, $record_info);
		if (!$rs) {
			$this->ci->db->trans_rollback();
			$this->_error = '出错啦,请重新提交4';
			return false;
		}
		//减库存
		$this->ci->product_model->reduce_stock($price_id,1);

		$this->ci->db->trans_commit();
		$this->ci->session->sess_write();
		$user_info = $this->ci->user_model->dump(array('id' =>$uid),'msgsetting,mobile,email');
		$this->_afterCreateOrder($order['id'],$user_info);

		return array('code'=>200,'msg'=>$order['order_name'],'pay_parent_id'=>$order['pay_parent_id'],'money'=>$order['money']);		
	}
	public function checkStore($params){
		$this->ci->load->model('order_model');

		//check
		$required_fields = array(
			'product_id' => array('required' => array('code' => '300', 'msg' => 'product id can not be null')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}

		$product_id = (int)$params['product_id'];
		//check buy num
		$c = $this->ci->order_model->get_kjt_order_num($product_id);
		if($c >= $this->stock_limit){
			return array('code' => 300, 'msg' => '今天的限量库存已售完');
		}
		//check  store
		$ck_res = $this->_ck_product_store($product_id);
		if($ck_res == false){
			return array('code' => 300, 'msg' => '商品已经售罄');
		}
		return array('code' => 200, 'msg' => 'succ');
	}

	private function _afterCreateOrder($order_id, $user_info){
		// 发短信 AND 邮件
		// $this->ci->load->driver('push_msg');
		// $this->ci->push_msg->set_msgsetting($user_info['msgsetting']);
		// $this->ci->push_msg->order_create->set_order($order_id);
		// $this->ci->push_msg->order_create->send_sms($user_info['mobile']); // 发短信
		// $this->ci->push_msg->order_create->send_email($user_info['email']); // 发邮件
	}
	private function _order_score($order)
	{
		$score = 0;
		foreach ($order['cart_info']['items'] as $key => $item) {
			$is_gift = in_array($item['item_type'],array('gift','mb_gift','user_gift','coupon_gift')) ? true : false;
			if (!$is_gift) {
				$score +=   $this->ci->order_model->get_order_product_score($uid,$item);
			}
		}

		if($order['goods_money']==0) {
			$substract = 1;
		}else{
			$substract = 1-($order['card_money'] + $order['jf_money'] + $order['pay_discount'])/$order['goods_money'];
		}
		$order_score = ceil($score*$substract);
		if($order_score<0){
			$order_score = 0;
		}
		if($order['pay_parent_id'] == 5){
			$order_score = 0; 
		}
		if($order['pay_parent_id'] == 4 && ($order['pay_id']==7 || $order['pay_id']==8 || $order['pay_id']==9)){
			$order_score = 0;
		}

		return $order_score;
	}
	/*
	*订单跨进通插入
	*/
	private function orderAddKjt($order_id, $record_info){
		$this->ci->load->model('kjt_order_model');
		$kjt_order_name = $this->srcncode.date("YmdHis").rand(10000,99999);
		$data = array(
			'order_id'=>$order_id,
			'kjt_order_name'=>$kjt_order_name,
			'sync_status'=>0,
			'name'=>$record_info['name'],
			'id_card_type'=>$record_info['id_card_type'],
			'id_card_number'=>$record_info['id_card_number'],
			'mobile'=>$record_info['mobile'],
			'email'=>$record_info['email']
		);
		$res = $this->ci->kjt_order_model->insert($data);
		return $res;
	}
	/*
	*订单商品插入
	*/
	private function orderAddPro($uid,$order_id,$cart_info){
		$this->ci->load->model('product_model');
		$this->ci->load->model('order_model');
		$opt = $this->ci->config->item('order_product_type');

		$insert = true;
		foreach ($cart_info['items'] as $key => $item) {
			$order_product_type = $opt[$item['item_type']] ? $opt[$item['item_type']] : '1';

			$order_pro_type = 1;
			if ($item['amount'] == 0) $order_pro_type = 3;

			// 判断是否为赠品
			$is_gift = in_array($item['item_type'],array('gift','mb_gift','user_gift','coupon_gift')) ? true : false;
			$score  = $is_gift ? 0 : $this->ci->order_model->get_order_product_score($uid,$item);

			if ($item['group_pro'] && $group_pro = explode(',',$item['group_pro'])) { // 组合商品
				$rows = $this->ci->product_model->getGroupProducts($group_pro);

				foreach ($rows as $row) {
					$order_product_data = array(
						'order_id'     => $order_id,
						'product_name' => addslashes($row['product_name']),
						'product_id'   => $row['id'],
						'product_no'   => $row['product_no'],
						'gg_name'      => $row['volume'] . '/' . $row['unit'],
						'price'        => $is_gift ? 0 : $row['price'],
						'qty'          => $item['qty'],
						'score'        => $score,
						'type'         => $order_pro_type,
						'total_money'  => $is_gift ? 0 : $item['qty'] * $row['price'],
						'group_pro_id' => $item['product_id'],
					);

					$insert = $this->ci->order_model->addOrderProduct($order_product_data);
					if (!$insert) return false;
				}
			} else {
				$order_product_data = array(
					'order_id'     => $order_id,
					'product_name' => addslashes($item['name']),
					'product_id'   => $item['product_id'],
					'product_no'   => $item['product_no'],
					'gg_name'      => $item['spec'].'/'.$item['unit'],
					'price'        => $item['price'],
					'qty'          => $item['qty'],
					'score'        => $score,
					'type'         => $order_pro_type,
					'total_money'  => $item['amount'],
				);
				$insert = $this->ci->order_model->addOrderProduct($order_product_data);
				if (!$insert) return false;
			}

			// 赠品置状态
			if ($is_gift && $item['user_gift_id']) $this->ci->order_model->receive_user_gift($uid,$order_id,$item['user_gift_id']);
		}

		return $insert;
	}

	private function orderAddAddr($order_id,$order_address){
		$this->ci->load->model("region_model");
		$this->ci->load->model("order_model");
		$address = $order_address['province']['name'].$order_address['city']['name'].$order_address['area']['name'].$order_address['address'];
		$email = addslashes(strip_tags($order_address['email']));
		$telephone = addslashes(strip_tags($order_address['telephone']));
		$mobile = addslashes(strip_tags($order_address['mobile']));
		$name = addslashes(strip_tags($order_address['name']));
		$region = $order_address['province']['name'].$order_address['city']['name'].$order_address['area']['name'];
		$order_address = array(
			'order_id'  => $order_id,
			'position'  => $region,
			'address'   => $address,
			'name'      => $name,
			'email'     => $email,
			'telephone' => $telephone,
			'mobile'    => $mobile,
			'province'  => $order_address['province']['id'],
			'city'      => $order_address['city']['id'],
			'area'      => $order_address['area']['id'],
		);
		$insert = $this->ci->order_model->addOrderAddr($order_address);   
		return $insert;
	}

	private function _ck_product_store($product_id,$cang_id=0){
		$this->ci->load->model('product_model');
		$ck_res = true;
		//check  store
		$p = $this->ci->product_model->getProductSkus($product_id,$cang_id);
		if($p['use_store']==1){
			$ck_res = false;
			foreach($p['skus'] as $val){
				if($val['stock']>0){
					$ck_res = true;
					break;
				}
			}
		}
		return $ck_res;
	}

	private function _order_shtime_init(){
		$this->ci->load->model('order_model');
		$shtime_res = $this->ci->order_model->check_advsale_sendtime(0);
		if($shtime_res!=false){
			$this->_orderinit['shtime'] =  date('Ymd',strtotime($shtime_res));
		}
		return true;
	}

	/**
	* 初始化订单总价
	*
	* @return void
	* @author 
	**/
	private function _order_address_init($address_id)
	{
		$this->ci->load->model('order_model');
		if(!empty($address_id)){
			$order_address = $this->ci->order_model->get_order_address($address_id);
	                	$this->_orderinit['order_address'] = $order_address;
                	}
                	return true;
	}
	/**
	* 初始化订单总价
	*
	* @return void
	* @author 
	**/
	private function _order_pay_init()
	{
		$pay_parent_id = 1;
		$pay_id = 0;
		$pay_array  =  $this->ci->config->item("pay_array");
		$pay_name = $pay_array[$pay_parent_id]['name'];

		$this->_orderinit['pay_parent_id'] = $pay_parent_id;
		$this->_orderinit['pay_id']        = $pay_id;     
		$this->_orderinit['pay_name']      = $pay_name;
		$this->_orderinit['icon'] = constant(CDN_URL.rand(1, 9)).'assets/images/bank/app/'.$pay_parent_id.'_'.$pay_id.'.png';

		if ($this->_orderinit['pay_parent_id'] == 5) {
			$this->_orderinit['need_authen_code'] = 1;
		}
		return true;
	}
	/**
	* 初始化订单总价
	*
	* @return void
	* @author 
	**/
	private function _order_total_init()
	{
		$this->_orderinit['money'] = $this->_orderinit['goods_money'] 
		+ $this->_orderinit['method_money']
		- $this->_orderinit['jf_money']
		- $this->_orderinit['card_money']
		- $this->_orderinit['pay_discount'];

		if ($this->_orderinit['money'] < 0) {
			$this->_error = '订单金额异常，请重新挑选商品试试';
			return false;
		}

		return true;
	}
	/**
	* 初始化会员
	*
	* @return void
	* @author 
	**/
	private function _user_init()
	{
		$this->ci->load->library('login');
		$uid = $this->ci->login->get_uid();

		// $this->ci->load->model('user_model');
		// $user = $this->ci->user_model->getUser($uid);
		if (!$uid) {
			$this->_error = '帐号异常，请重新登录试试';
			return false;
		}
		// $this->_orderinit['user_mobile'] = $user['mobile'] ? $user['mobile'] : '';
		// $this->_orderinit['user_money']  = $user['money'] ? $user['money'] : '0';
		// $this->_orderinit['user_coupon_num'] = $this->ci->user_model->getCouponNum($uid);
		return true;
	}
	/**
	* 积分初始化
	*
	* @return void
	* @author 
	**/
	private function _order_jfmoney_init()
	{
		$this->_orderinit['jf_money'] = 0;
		$this->_orderinit['use_jf'] = 0;
		return true;
	}
	/**
	* 优惠券初始化
	*
	* @return void
	* @author 
	**/
	private function _order_card_init()
	{
		$this->_orderinit['use_card'] = '';
		$this->_orderinit['card_money'] = '';

		return true;
	}

	/*
	*商品是否可配送判断
	*/
	protected function sendRegionFilter($address_id='',$cart_info){
		$cart_array = $cart_info['items'];
		$this->ci->load->model('region_model');
		$area_result = $this->ci->region_model->get_province_id($address_id);
		$area_id = $area_result['area'];
		$province_id = $area_result['province'];

		if(mb_strlen($area_result['name'])>15){
			return array('code'=>'300','msg'=>'您填写收货人姓名过长，请控制在15个字以内');
		}

		if(count($cart_array)>0){
			$ids = array();
			foreach ($cart_array as $key => $value) {
				$ids[] = $value['product_id'];
			}

			$where_in[] = array('key'=>'id','value'=>array_unique($ids));
			$send_region_arr = $this->ci->product_model->selectProducts('id,send_region','',$where_in);
			$can_not_send_pro_arr = array();
			foreach ($send_region_arr as $key => $value) {
				$can_send_region = unserialize($value['send_region']);
				if(is_array($can_send_region)){
					if(!in_array($province_id, $can_send_region)){
					    $can_not_send_pro_arr[$value['id']] = implode($this->ci->region_model->get_send_region($can_send_region,'order'),'，');
					}
				}else{
					continue;
				}
			}

			$can_not_send_sku_arr = array();
			foreach ($cart_array as $key => $value) {
				$is_lack = false;

				if(isset($can_not_send_pro_arr[$value['product_id']]) || $is_lack){
					$can_not_send_sku_arr[] = $value['name'];
				}
			}

			if(count($can_not_send_sku_arr)>0){
				return $can_not_send_sku_arr;
			}else{
				return true;
			}
		}else{
			return true;
		}
	}
}
