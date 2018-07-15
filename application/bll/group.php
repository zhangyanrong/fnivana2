<?php
namespace bll;

class Group
{
	private $_orderinit = array();
	private $_error = '';
	private $_code = 300;
//	private $stock_limit = 3000;
	private $_config = array(
		'tag' => '20151120',
		'start_time' => '2015-11-14',
		'end_time' => '2015-11-28',
		'limit_group_num' => 100000,  //限制可以创建的团数
		'stock_limit' => 120000 //团购商品限购数量
	);

	private $_config1 = array(
		'tag' => '20151201',
		'start_time' => '2015-11-30',
		'end_time' => '2015-12-28',
		'limit_group_num' => 100000,  //限制可以创建的团数
		'stock_limit' => 120000 //团购商品限购数量
	);

	function __construct() {
		$this->ci = &get_instance();
		$this->ci->load->helper('public');

		$this->ci->load->model('group_model');
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
		$this->ci->load->model('group_model');

		//check
		$required_fields = array(
			'id' => array('required' => array('code' => '300', 'msg' => 'product id can not be null')),
			'active_id' => array('required' => array('code' => '300', 'msg' => 'active id can not be null')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}
		//get active info
//		$group_res = $this->ci->group_model->dump(array('id'=>$params['active_id']), 'product_id');
//		$group_product_ids = explode(",", $group_res['product_id']);
//		if(!in_array($params['id'], $group_product_ids)){
//			return array('code' => 300, 'msg' => '该活动不存在');
//		}
		if(!in_array($params['active_id'],array('20151120','20151201'))){
			return array('code' => 300, 'msg' => '该活动不存在');
		}

		if(!in_array($params['id'],array('6828','4348','7167'))){
			return array('code' => 300, 'msg' => '该活动不存在');
		}

		$group_id = array();

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
		$result = $this->ci->product_model->get_product_group($id, $channel, $params['source'],$cang_id);
		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['id'] . "_" . $channel;
			$this->ci->memcached->set($mem_key, $result, 1800);
		}
		return $result;
	}

	public function orderInit($group_id,$config_type,$address_id='')
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

		if($config_type==3){ //特殊类型的商品价格读group表里存的字段
			$this->ci->load->model('group_model');
			$tuan_price = $this->ci->group_model->get_tuan_price_by_groupid($group_id);
			if($tuan_price>0){
				$this->_orderinit['cart_info']['items'][0]['price']  = $tuan_price;
				$this->_orderinit['cart_info']['items'][0]['sale_price']  = $tuan_price;
				$this->_orderinit['cart_info']['items'][0]['amount']  = $tuan_price;
				$this->_orderinit['cart_info']['items'][0]['goods_cost']  = $tuan_price;

				$this->_orderinit['cart_info']['total_amount'] = $tuan_price;
				$this->_orderinit['cart_info']['goods_amount'] = $tuan_price;
				$this->_orderinit['cart_info']['goods_cost'] = $tuan_price;

				$this->_orderinit['goods_money']  = $tuan_price;
				$this->_orderinit['pmoney']       = $tuan_price;
			}
		}

		// 初始化订单金额
		$rs = $this->_order_total_init();
		if (!$rs) return false;
		$this->_orderinit['need_authen_code'] = 0;
		$this->_orderinit['shtime'] = '';
		$this->_orderinit['stime']  = '';

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
	public function createOrder($address_id, $record_info, $product_id, $price_id,$shtime,$stime,$pay_parent_id,$tag){
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

		$pay_parent_id2payname = array(
			1=>'支付宝',
			7=>'微信支付'
		);

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
			'pay_parent_id' => $pay_parent_id,
			'pay_id'        => 0,
			'pay_name'      => $pay_parent_id2payname[$pay_parent_id],
			'jf_money'      => $data['jf_money'],
			'use_jf'        => $data['use_jf'],
			'use_card'      => $data['use_card'],
			'card_money'    => $data['card_money'],
			'uid'           => $uid,
			'shtime'        => $shtime,
			'stime'         => $stime,
			'order_type'    => 7,
			'address_id'    => 0,
		);
		if ($ordermsg['msg']) {
			$order['msg'] = $ordermsg['msg'];
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
//		// 保存group订单
//		$rs = $this->orderAddgroup($order_id, $record_info);
//		if (!$rs) {
//			$this->ci->db->trans_rollback();
//			$this->_error = '出错啦,请重新提交4';
//			return false;
//		}
		//减库存
		$this->ci->product_model->reduce_stock($price_id,1);

		$this->ci->db->trans_commit();
		$this->ci->session->sess_write();
		$user_info = $this->ci->user_model->dump(array('id' =>$uid),'msgsetting,mobile,email');
		$this->_afterCreateOrder($order['id'],$user_info,$uid,$tag);

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
		$c = $this->ci->order_model->get_group_order_num($product_id);
		if($c >= $this->_config['stock_limit']){
			return array('code' => 300, 'msg' => '库存已售完');
		}
		//check  store
		$ck_res = $this->_ck_product_store($product_id);
		if($ck_res == false){
			return array('code' => 300, 'msg' => '商品已经售罄');
		}
		return array('code' => 200, 'msg' => 'succ');
	}

	private function _afterCreateOrder($order_id, $user_info, $uid, $tag){
		//变更团数据状态
		if(!empty($tag)){
			$this->_config = $this->_config1;
		}
		$this->ci->group_model->afterCreateOrder($order_id,$uid,$this->_config['tag']);
//		// 发短信 AND 邮件
//		$this->ci->load->driver('push_msg');
//		$this->ci->push_msg->set_msgsetting($user_info['msgsetting']);
//		$this->ci->push_msg->order_create->set_order($order_id);
//		$this->ci->push_msg->order_create->send_sms($user_info['mobile']); // 发短信
//		$this->ci->push_msg->order_create->send_email($user_info['email']); // 发邮件
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
	private function orderAddgroup($order_id, $record_info){
		$this->ci->load->model('group_order_model');
		$group_order_name = $this->srcncode.date("YmdHis").rand(10000,99999);
		$data = array(
			'order_id'=>$order_id,
			'group_order_name'=>$group_order_name,
			'sync_status'=>0,
			'name'=>$record_info['name'],
			'id_card_type'=>$record_info['id_card_type'],
			'id_card_number'=>$record_info['id_card_number'],
			'mobile'=>$record_info['mobile'],
			'email'=>$record_info['email']
		);
		$res = $this->ci->group_order_model->insert($data);
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

	private function _ck_product_store($product_id){
return true;
		$this->ci->load->model('product_model');
		$ck_res = true;
		//check  store
		$p = $this->ci->product_model->getProductSkus($product_id);
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
//		$this->ci->load->model('order_model');
//		$shtime_res = $this->ci->order_model->check_advsale_sendtime(0);
//		if($shtime_res!=false){
		$this->_orderinit['shtime'] =  date('Ymd',strtotime($shtime_res));
//		}
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
		$pay_parent_id = "";
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

	/*
	 * 是否还能继续购买
	 */
	function canBuy($params){

		$this->ci->load->model('order_model');
//		//check

		if($params['is_app']){
//			$required_fields = array(
//				'connect_id' => array('required' => array('code' => '300', 'msg' => 'connect_id can not be null')),
//			);
//			if ($alert_msg = check_required($params, $required_fields)) {
//				return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
//			}

			$this->ci->load->library('login');
			$this->ci->login->init($params['connect_id']);
			$uid = $this->ci->login->get_uid();

			if(empty($uid)){
				return array('code' => 303, 'msg' => '登录超时，请重新登录试试.');
			}
		}

		$product_id = '6828';   //活动产品id

		$tag  = $params['tag']?$params['tag']:'';
		if(!empty($tag)){
			$this->_config = $this->_config1;
			$product_id = '7167';
		}

//		$product_id = (int)$params['product_id'];
		$is_master = $params['is_master']?$params['is_master']:0;
		if($is_master){
			$g = $this->ci->group_model->check_group_num($this->_config['tag']);
			if($g>=$this->_config['limit_group_num']){
				return array('code' => 300, 'msg' => '成团上限已经达到');
			}
		}

		//check buy num
		$c = $this->ci->order_model->get_group_order_num($product_id);
		if($c >= $this->_config['stock_limit']){
			return array('code' => 300, 'msg' => '今天的限量库存已售完');
		}
		//check  store
		$ck_res = $this->_ck_product_store($product_id);
		if($ck_res == false){
			return array('code' => 300, 'msg' => '商品已经售罄');
		}

		$cancel = $this->ci->group_model->is_order_cancel($uid,$this->_config['tag']);
		if(!empty($cancel)){
			return array('code' => 302, 'msg' => $cancel['group_id']);
		}

		$can = $this->ci->group_model->is_createOrder($uid,$this->_config['tag'],$is_master);
		if(!empty($can)){
			return array('code' => 301, 'msg' => $can['group_id']);
		}
		return array('code' => 200, 'msg' => $is_master);
	}

	/*
	 *当前团状态
	 */
	function getGroupInfo($params){
//		$this->ci->load->library('login');
//		$this->ci->login->init($params['connect_id']);
//		$uid = $this->ci->login->get_uid();

//		$this->load->library('fdaylog');
//		$db_log = $this->load->database('db_log', TRUE);
//		$this->fdaylog->add($db_log,'group_connect_id2',$params['connect_id']);
		$session_id = isset($params['connect_id'])?$params['connect_id']:'';
		if($session_id){
			$this->ci->load->library('session',array('session_id'=>$session_id));
		}

		//获取session信息start
		$uid_result = $this->get_uid_by_connect_id($params['connect_id']);
		if($uid_result['code']!='200'){
//			return $uid_result;
		}else{
			$uid = $uid_result['msg'];
		}
		//获取session信息end

		$tag  = $params['tag']?$params['tag']:'';
		if(!empty($tag)){
			$this->_config = $this->_config1;
		}

		$group_id = $params['group_id'];
		$data = $this->ci->group_model->get_group_info($group_id,$this->_config['tag'],$uid);
		return $data;
	}

	/*
    *获取session
    */
	private function get_uid_by_connect_id($session_id,$lock_order=false){
		$session =   $this->ci->session->userdata;
		if(empty($session)){
			return array('code'=>'400','msg'=>'not this connect id ,maybe out of date');
		}

		$userdata = unserialize($session['user_data']);

		unset($userdata['user_data']);
		unset($userdata['connect_id']);

		if( !isset($userdata['id']) || $userdata['id'] == "" ){
			return array('code'=>'400','msg'=>'not this user,may be wrong connect id');
		}
		return array('code'=>'200','msg'=>$userdata['id']);
	}


	/*
	 * 获取已售数量
	 */
	function getSoutNum($pram){
		$this->ci->load->model('order_model');
		$product_id = '6828';   //活动产品id

		$tag  = $pram['tag']?$pram['tag']:'';
		if($tag=='20151201'){
			$this->_config = $this->_config1;
			$product_id = '7167';
		}
		$c = $this->ci->order_model->get_group_order_num($product_id);
		return $c;
	}

	/*
	 * 购买预操作
	 */
	function beforeOrder($params){
		$this->ci->load->model('order_model');
		$required_fields = array(
			'connect_id' => array('required' => array('code' => '300', 'msg' => 'connect_id can not be null')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}
		$is_master = $params['is_master']?$params['is_master']:0;

		$product_id = '6828';   //活动产品id

		$tag  = $params['tag']?$params['tag']:'';
		if($tag=='20151201'){
			$this->_config = $this->_config1;
			$product_id = '7167';
		}
		if($is_master){
			$g = $this->ci->group_model->check_group_num($this->_config['tag']);
			if($g>=$this->_config['limit_group_num']){
				return array('code' => 300, 'msg' => '成团上限已经达到');
			}
		}

		return array('code' => 300, 'msg' => '结束了');

		//check buy num
		$c = $this->ci->order_model->get_group_order_num($product_id);
		if($c >= $this->_config['stock_limit']){
			return array('code' => 300, 'msg' => '今天的限量库存已售完');
		}
		//check  store
		$ck_res = $this->_ck_product_store($product_id);
		if($ck_res == false){
			return array('code' => 300, 'msg' => '商品已经售罄');
		}

		$this->ci->load->library('login');
		$this->ci->login->init($params['connect_id']);
		$uid = $this->ci->login->get_uid();

		$cancel = $this->ci->group_model->is_order_cancel($uid,$this->_config['tag']);
		if(!empty($cancel)){
			return array('code' => 302, 'msg' => $cancel['group_id']);
		}

		$can = $this->ci->group_model->is_createOrder($uid,$this->_config['tag'],$is_master);
		if(!empty($can)){
			return array('code' => 301, 'msg' => $can['group_id']);
		}

		$now_time = time();
		$mobile = $this->ci->group_model->getMobileByUid($uid);
		if($is_master){  //团长
			$group_data = array(
				'uid'=>$uid,
				'time'=>$now_time,
				'status'=>0,
				'tag'=>$this->_config['tag']
			);
			$member_data = array(  //update
				'uid'=>$uid,
				'group_id'=>'',
				'time'=>$now_time,
				'status'=>0,
				'mobile' => $mobile,
				'tag'=>$this->_config['tag'],
				'openid'=>$params['openid'],
				'wx_name'=>addslashes($params['wx_name']),
				'wx_pic'=>$params['wx_pic'],
				'wx_name1'=>base64_encode($params['wx_name'])
			);
			$group_id = $this->ci->group_model->set_up($group_data,$member_data);
		}else{
			$group_id = $params['group_id'];
			$can_add = $this->ci->group_model->check_can_add($group_id);
			if(!empty($can_add)){
				return array('code'=>300,'msg'=>$can_add);
			}

			$member_data = array(  //update
				'uid'=>$uid,
				'group_id'=>$group_id,
				'time'=>$now_time,
				'status'=>0,
				'order_id'=>'0',
				'mobile' => $mobile,
				'tag'=>$this->_config['tag'],
				'openid'=>$params['openid'],
				'wx_name'=>addslashes($params['wx_name']),
				'wx_pic'=>$params['wx_pic'],
				'wx_name1'=>base64_encode($params['wx_name'])
			);
			$is_return = $this->ci->group_model->set_up_member($member_data);
			if(!empty($is_return)){
				$group_id = $is_return;
			}
		}
		if(empty($group_id)){
			return array('code'=>300,'msg'=>"操作失败，请重试.");
		}else{
			return array('code' => 200, 'msg' => $group_id);
		}
	}



	// upgrade v2 begin

	//团购配置信息
	function getGroupConfigData($tag){
		$groupInfo = $this->ci->group_model->getGroupConfigData($tag);
		if(empty($groupInfo)){
			return array('code'=>'300','msg'=>'该活动已经过期，看看其他活动吧.');
		}else{
			$groupInfo['config'] = unserialize($groupInfo['config']);
			return $groupInfo;
		}
	}
	//团长页面数据
	function master($params){
		$tag = $params['tag'];
		if(empty($tag)){
			return array('code'=>'300','msg'=>'该活动已经过期，看看其他活动吧.');
		}
		$result = $groupInfo = $this->ci->group_model->getGroupConfigData($tag);
		if(empty($result)){
			return array('code'=>'300','msg'=>'该活动已经过期，看看其他活动吧.');
		}else{
			$result['config'] = unserialize($result['config']);
		}

		if($result['code']==300){
			return array('code'=>'300','msg'=>'该活动已经过期，看看其他活动吧.');
		}

		//升值团处理
		if($result['config']['config_type'] == 3){
			//如果是类型是速度拼的团，价格得做处理.    开团数0~300 -》3折     301-800   -》4折      801-1000-》5折      拿原价标价处理，四舍五入一位小数
			$tuan_price = $this->ci->group_model->get_tuan_price($result['config']['promotion_pid'],$result['config']['config_type'],$tag);
			if($tuan_price == false){
				return array('code'=>300,'msg'=>'运营小哥正在飞速更新产品中');
			}

			if($tuan_price>0){
				$arr = (explode('元',$result['config']['promotion_desc']));
				$arr[0] = $tuan_price;
				$result['config']['promotion_desc'] = implode("元",$arr);
			}
		}

		//开团未成团展示
		$tuan_info = $this->ci->group_model->get_set_nearly_finish_group($tag,$result['config']);
		if(!empty($tuan_info)){
			foreach($tuan_info as $k => $v){
				$tuan_info[$k]['group_count'] = $result['config']['full_num'] - $this->ci->group_model->get_group_count($v['group_id'],$tag);
				$tuan_info[$k]['end_time'] = date('Y/m/d H:i:s',strtotime("+24 hours",$tuan_info[$k]['time']));
			}
		}

		$result['nearly_finish_group'] = $tuan_info;

		$this->ci->load->model('order_model');
		$c = $this->ci->order_model->get_group_order_num($result['config']['promotion_pid']);
		$result['soldNum'] = $c?$c:0;

//		$data = $this->ci->group_model->getProductInfo($result['config']['promotion_pid']);
//		$result['config']['description'] = $data['discription'];
		return $result;
	}
	
	//新年天团页面数据
	function master_newyear($params){
	    $tag = $params['tag'];
	    $connect_id = $params['connect_id'];
	    if(empty($tag)){
	        return array('code'=>'300','msg'=>'该活动已经过期，看看其他活动吧.');
	    }
	    
	    $this->ci->load->library('login');
		$this->ci->login->init($connect_id);
		$uid = $this->ci->login->get_uid();
		$result['uid'] = $uid;
		
		/* $cancel = $this->ci->group_model->is_order_cancel($uid,$tag);
		if(!empty($cancel)){
		    $group_id =  $cancel['group_id'];
		} else {
		    $can = $this->ci->group_model->is_createOrder($uid,$tag);
		    if (!empty($can)){
		        $group_id = $can['group_id'];
		    } else {
		        $mobile = $this->ci->group_model->getMobileByUid($uid);
		        //获取group_id
		        $group_data = array(
		            'uid'=>$uid,
		            'time'=>time(),
		            'tag'=>$tag
		        );
		        $member_data = array(  //update
		            'uid'=>$uid,
		            'group_id'=>'',
		            'time'=>time(),
		            'status'=>0,
		            'mobile' => $mobile,
		            'tag'=>$tag,
		        );
		        
		        $group_id = $this->ci->group_model->set_up($group_data,$member_data);
		    }
		} */
		//新年天团id
		$group_id = 38756;
		//$group_id = 3898;
		
		$group_info = $this->ci->group_model->get_group_info_v1($group_id,$uid);
		if ($group_info){
		    $upToNum = $group_info['uptoNum'];
		    $fullNum = $group_info['configData']['config']['full_num'];
		    $myGroupNum = $fullNum-$upToNum;
		    $result['myGroupNum'] = $myGroupNum;
		    $result['is_bought'] = $group_info['is_bought'];
		}
		
		
	    $this->ci->load->model('order_model');
	    $promotion_pid = 8068;
	    $c = $this->ci->order_model->get_group_order_num($promotion_pid);
	    $result['soldNum'] = $c?$c:0;
	    
	    //		$data = $this->ci->group_model->getProductInfo($result['config']['promotion_pid']);
	    //		$result['config']['description'] = $data['discription'];
	    return $result;
	}
	
	//新年天团页面数据
	function master_newyearmilk($params){
	    $tag = $params['tag'];
	    $connect_id = $params['connect_id'];
	    if(empty($tag)){
	        return array('code'=>'300','msg'=>'该活动已经过期，看看其他活动吧.');
	    }
	     
	    $this->ci->load->library('login');
	    $this->ci->login->init($connect_id);
	    $uid = $this->ci->login->get_uid();
	    $result['uid'] = $uid;
	    
	    if ($params['group_id']){
	        $group_id = $params['group_id'];
	    } else {
	        $cancel = $this->ci->group_model->is_order_cancel($uid,$tag);
	        if(!empty($cancel)){
	            $group_id =  $cancel['group_id'];
	        } else {
	            $can = $this->ci->group_model->is_createOrder($uid,$tag);
	            if (!empty($can)){
	                $group_id = $can['group_id'];
	            } else {
	                $mobile = $this->ci->group_model->getMobileByUid($uid);
	                //获取group_id
	                $group_data = array(
	                    'uid'=>$uid,
	                    'time'=>time(),
	                    'tag'=>$tag
	                );
	                $member_data = array(  //update
	                    'uid'=>$uid,
	                    'group_id'=>'',
	                    'time'=>time(),
	                    'status'=>0,
	                    'mobile' => $mobile,
	                    'tag'=>$tag,
	                );
	        
	                $group_id = $this->ci->group_model->set_up($group_data,$member_data);
	            }
	        }
	    }
	
	    $group_info = $this->ci->group_model->get_group_info_v1($group_id,$uid);
	    if ($group_info){
	        $upToNum = $group_info['uptoNum'];
	        $fullNum = $group_info['configData']['config']['full_num'];
	        $myGroupNum = $fullNum-$upToNum;
	        $result['myGroupNum'] = $myGroupNum;
	        $result['is_bought'] = $group_info['is_bought'];
	    }
	
	
	    $this->ci->load->model('order_model');
	    $promotion_pid = 8563;
	    $c = $this->ci->order_model->get_group_order_num($promotion_pid);
	    $result['soldNum'] = $c?$c:0;
	     
	    //		$data = $this->ci->group_model->getProductInfo($result['config']['promotion_pid']);
	    //		$result['config']['description'] = $data['discription'];
	    return $result;
	}
	
	//新年天团页面数据
	function master_newyear_test($params){
	    $tag = $params['tag'];
	    $connect_id = $params['connect_id'];
	    if(empty($tag)){
	        return array('code'=>'300','msg'=>'该活动已经过期，看看其他活动吧.');
	    }
	     
	    $this->ci->load->library('login');
                                	    $this->ci->login->init($connect_id);
	    $uid = $this->ci->login->get_uid();
	    $result['uid'] = $uid;
	
	     /* $cancel = $this->ci->group_model->is_order_cancel($uid,$tag);
	     if(!empty($cancel)){
	     $group_id =  $cancel['group_id'];
	     } else {
	     $can = $this->ci->group_model->is_createOrder($uid,$tag);
	     if (!empty($can)){
	     $group_id = $can['group_id'];
	     } else {
	     $mobile = $this->ci->group_model->getMobileByUid($uid);
	     //获取group_id
	     $group_data = array(
	     'uid'=>$uid,
	     'time'=>time(),
	     'tag'=>$tag
	     );
	     $member_data = array(  //update
	     'uid'=>$uid,
	     'group_id'=>'',
	     'time'=>time(),
	     'status'=>0,
	     'mobile' => $mobile,
	     'tag'=>$tag,
	     );
	
	     $group_id = $this->ci->group_model->set_up($group_data,$member_data);
	     }
	     }  */
	    //新年天团id
	    $group_id = 39620;
	    //$group_id = 3898;
	
	    $group_info = $this->ci->group_model->get_group_info_v1($group_id,$uid);
	    if ($group_info){
	        $upToNum = $group_info['uptoNum'];
	        $fullNum = $group_info['configData']['config']['full_num'];
	        $myGroupNum = $fullNum-$upToNum;
	        $result['myGroupNum'] = $myGroupNum;
	        $result['is_bought'] = $group_info['is_bought'];
	    }
	
	
	    $this->ci->load->model('order_model');
	    $promotion_pid = 8317;
	    $c = $this->ci->order_model->get_group_order_num($promotion_pid);
	    $result['soldNum'] = $c?$c:0;
	     
	    //		$data = $this->ci->group_model->getProductInfo($result['config']['promotion_pid']);
	    //		$result['config']['description'] = $data['discription'];
	    return $result;
	}
	

	//团购列表
	function groupList(){
		$groups = $this->ci->group_model->getGroupList();
		$arr = array();
		if(!empty($groups)){
			foreach($groups as $v){
				$v['config'] = unserialize($v['config']);
				$arr[] = $v;
			}
		}
		return $arr;
	}

	/*
	 * 购买预操作
	 */
	function beforeOrder_v1($params){
		$this->ci->load->model('order_model');
		$required_fields = array(
			'connect_id' => array('required' => array('code' => '300', 'msg' => 'connect_id can not be null')),
			'tag' => array('required' => array('code' => '300', 'msg' => 'tag can not be null')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}
		$is_master = $params['is_master']?$params['is_master']:0;

		$tag = $params['tag'];
		$groupInfo = $this->ci->group_model->getGroupConfigData($tag);
		$now = time();
		$is_over = false;
		if($now<$groupInfo['begin_time']||$now>$groupInfo['end_time']){
			$is_over = true;
		}
		if(empty($groupInfo)||($is_over&&$is_master)){
			return array('code'=>'300', 'msg'=>'结束啦。');
		}
		$config = unserialize($groupInfo['config']);

		if($is_master){
			if($config['config_type']==3){
				$g = $this->ci->group_model->check_group_num_flash($tag);
				if($g>=$config['limit_group_num']){
					return array('code' => 300, 'msg' => '成团上限已经达到');
				}
			}else{
				$g = $this->ci->group_model->check_group_num($tag);
				if($g>=$config['limit_group_num']){
					return array('code' => 300, 'msg' => '成团上限已经达到');
				}
			}
		}

//		//check buy num
//		$c = $this->ci->order_model->get_group_order_num($product_id);
//		if($c >= $this->_config['stock_limit']){
//			return array('code' => 300, 'msg' => '今天的限量库存已售完');
//		}
//		//check  store
//		$ck_res = $this->_ck_product_store($product_id);
//		if($ck_res == false){
//			return array('code' => 300, 'msg' => '商品已经售罄');
//		}

		$this->ci->load->library('login');
		$this->ci->login->init($params['connect_id']);
		$uid = $this->ci->login->get_uid();

		$cancel = $this->ci->group_model->is_order_cancel($uid,$tag);
		if(!empty($cancel)){
			return array('code' => 302, 'msg' => $cancel['group_id']);
		}

		$can = $this->ci->group_model->is_createOrder($uid,$tag,$is_master);
		if(!empty($can)){
			return array('code' => 301, 'msg' => $can['group_id']);
		}

		$now_time = time();
		$mobile = $this->ci->group_model->getMobileByUid($uid);
		if($is_master){  //团长
			//如果是类型是速度拼的团，价格得做处理.    开团数0~300 -》3折     301-800   -》4折      801-1000-》5折      拿原价标价处理，四舍五入一位小数
			$tuan_price = $this->ci->group_model->get_tuan_price($config['promotion_pid'],$config['config_type'],$tag);
			if($tuan_price == false){
				return array('code'=>300,'msg'=>'运营小哥正在飞速更新产品中');
			}

			//新客团
			$this->ci->load->library('login');
			$this->ci->login->init($params['connect_id']);
			$uid = $this->ci->login->get_uid();

			if($config['config_type'] == 4){
				$is_new = $this->ci->group_model->is_new($uid);
				if(!$is_new){
					return array('code'=>300,'msg'=>"特价仅限新客开启哦.");
				}
			}

			$group_data = array(
				'uid'=>$uid,
				'time'=>$now_time,
				'status'=>0,
				'tag'=>$tag,
				'tuan_price'=>$tuan_price
			);
			$member_data = array(  //update
				'uid'=>$uid,
				'group_id'=>'',
				'time'=>$now_time,
				'status'=>0,
				'mobile' => $mobile,
				'tag'=>$tag,
				'openid'=>$params['openid'],
				'wx_name'=>addslashes($params['wx_name']),
				'wx_pic'=>$params['wx_pic'],
				'wx_name1'=>base64_encode($params['wx_name'])
			);
			$group_id = $this->ci->group_model->set_up($group_data,$member_data);
		}else{
			$group_id = $params['group_id'];

			//是否拉新判断
			if($config['config_type']==2){
				$is_new = $this->ci->group_model->is_new($uid);
				if(!$is_new){
					return array('code'=>303,'msg'=>"特价团购老用户仅可当团长哦,快去开启团购吧.");
				}
			}

			$can_add = $this->ci->group_model->check_can_add($group_id);
			if(!empty($can_add)){
				return array('code'=>300,'msg'=>$can_add);
			}

			$member_data = array(  //update
				'uid'=>$uid,
				'group_id'=>$group_id,
				'time'=>$now_time,
				'status'=>0,
				'order_id'=>'0',
				'mobile' => $mobile,
				'tag'=>$tag,
				'openid'=>$params['openid'],
				'wx_name'=>addslashes($params['wx_name']),
				'wx_pic'=>$params['wx_pic'],
				'wx_name1'=>base64_encode($params['wx_name'])
			);
			$is_return = $this->ci->group_model->set_up_member($member_data);
			if(!empty($is_return)){
				$group_id = $is_return;
			}
		}
		if(empty($group_id)){
			return array('code'=>300,'msg'=>"操作失败，请重试.");
		}else{
			return array('code' => 200, 'msg' => $group_id);
		}
	}

	public function productInfo_v1($params){
		$this->ci->load->model('product_model');
		$this->ci->load->model('group_model');

		//check
		$required_fields = array(
			'tag' => array('required' => array('code' => '300', 'msg' => 'active id can not be null')),
		);
		if ($alert_msg = check_required($params, $required_fields)) {
			return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
		}
		//get active info
//		$group_res = $this->ci->group_model->dump(array('id'=>$params['active_id']), 'product_id');
//		$group_product_ids = explode(",", $group_res['product_id']);
//		if(!in_array($params['id'], $group_product_ids)){
//			return array('code' => 300, 'msg' => '该活动不存在');
//		}
		if(empty($params['tag'])){
			return array('code' => 300, 'msg' => '该活动不存在');
		}

		$groupInfo = $this->ci->group_model->getGroupConfigData($params['tag']);

		if(empty($groupInfo)){
			return array('code' => 300, 'msg' => '该活动不存在');
		}

		//get product info
		$config = unserialize($groupInfo['config']);
		$id = $config['promotion_pid'];
		$default_channle = $this->ci->config->item('default_channle');
		if (in_array($params['channel'], $default_channle)) {
			$params['channel'] = 'portal';
		}
		
		$channel = (isset($params['channel']) && !empty($params['channel'])) ? $params['channel'] : 'portal';
		$mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['tag'] . "_" . $channel;
		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['tag'] . "_" . $channel;
			$result = $this->ci->memcached->get($mem_key);
			if ($result) {
				return $result;
			}
		}
		$region_to_warehouse = $this->ci->config->item('region_to_cang'); 
        $cang_id = $region_to_warehouse[$params['region_id']];
		$result = $this->ci->product_model->get_product_group($id, $channel, $params['source'],$cang_id);
		if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
			if (!$this->ci->memcached) {
				$this->ci->load->library('memcached');
			}
			$mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $params['tag'] . "_" . $channel;
			$this->ci->memcached->set($mem_key, $result, 1800);
		}
		return $result;
	}

	public function createOrder_v1($address_id, $record_info, $product_id, $price_id,$shtime,$stime,$pay_parent_id,$tag,$group_id){
		$this->ci->load->library('login');
		$this->ci->load->model('product_model');
		$uid = $this->ci->login->get_uid();
		//ck product store
//		$ck_res = $this->_ck_product_store($product_id);
//		if($ck_res == false){
//			return array('code' => 300, 'msg' => '商品已经售罄');
//		}

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

		$this->ci->load->model('group_model');
		$groupInfo = $this->ci->group_model->getGroupConfigData($tag);
		if(empty($groupInfo)){
			return array('code'=>300,'msg'=>'错误的入参');
		}
		$config = unserialize($groupInfo['config']);
		$config_type = $config['config_type'];
		$data = $this->orderInit($group_id,$config_type,$address_id);
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

		$pay_parent_id2payname = array(
			1=>'支付宝',
			7=>'微信支付'
		);
        
		if ($tag == 'LBfhW1'){
		    $shtime = '20160117';
		}
		if ($tag == 'ddy5W1'){
		    $shtime = '20160117';
		}

		$ware_id ='';
		$this->ci->load->model('warehouse_model');
        if(!empty($address_id))
        {
            $this->ci->load->model('user_address_model');
            $user_add = $this->ci->user_address_model->dump(array('id' => $address_id));
            if(!empty($user_add) && !empty($user_add['tmscode']))
            {
                $arr_tmsCode = explode('-',$user_add['tmscode']);
                $tmsCode =$arr_tmsCode[0];
                $ware = $this->ci->warehouse_model->dump(array('tmscode' => $tmsCode));
                if(!empty($ware))
                {
                    $ware_id = $ware['id'];
                }
            }
        }

        if (!empty($ware_id)) {
            $warehouse_info = $this->ci->warehouse_model->getWarehouseByID($ware_id);
        }

        if (empty($warehouse_info)) {
            $warehouse_info = $this->ci->warehouse_model->get_warehouse_by_region($data['order_address']['area']['id']);
        }
        $cang_id = $warehouse_info['id'];

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
			'pay_parent_id' => $pay_parent_id,
			'pay_id'        => 0,
			'pay_name'      => $pay_parent_id2payname[$pay_parent_id],
			'jf_money'      => $data['jf_money'],
			'use_jf'        => $data['use_jf'],
			'use_card'      => $data['use_card'],
			'card_money'    => $data['card_money'],
			'uid'           => $uid,
			'shtime'        => $shtime,
			'stime'         => $stime,
			'order_type'    => 7,
			'address_id'    => 0,
		);
		if ($ordermsg['msg']) {
			$order['msg'] = $ordermsg['msg'];
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
//		// 保存group订单
//		$rs = $this->orderAddgroup($order_id, $record_info);
//		if (!$rs) {
//			$this->ci->db->trans_rollback();
//			$this->_error = '出错啦,请重新提交4';
//			return false;
//		}
		//减库存
		$this->ci->product_model->reduce_stock($price_id,1,$cang_id);
		$msg = $this->_afterCreateOrder_v1($order['id'],$uid,$tag);
		if(!empty($msg)){
			$this->ci->db->trans_rollback();
			$this->_error = $msg;
			return false;
		}

		$this->ci->db->trans_commit();
		$this->ci->session->sess_write();
//		$user_info = $this->ci->user_model->dump(array('id' =>$uid),'msgsetting,mobile,email');

		return array('code'=>200,'msg'=>$order['order_name'],'pay_parent_id'=>$order['pay_parent_id'],'money'=>$order['money']);
	}

	private function _afterCreateOrder_v1($order_id, $uid, $tag){
		//变更团数据状态
		return $this->ci->group_model->afterCreateOrder($order_id,$uid,$tag);
	}


	/*
	 *当前团状态
	 */
	function getGroupInfo_v1($params){
//		$this->ci->load->library('login');
//		$this->ci->login->init($params['connect_id']);
//		$uid = $this->ci->login->get_uid();

//		$this->load->library('fdaylog');
//		$db_log = $this->load->database('db_log', TRUE);
//		$this->fdaylog->add($db_log,'group_connect_id2',$params['connect_id']);
		$session_id = isset($params['connect_id'])?$params['connect_id']:'';
		if($session_id){
			$this->ci->load->library('session',array('session_id'=>$session_id));
		}

		//获取session信息start
		$uid_result = $this->get_uid_by_connect_id($params['connect_id']);
		if($uid_result['code']!='200'){
//			return $uid_result;
		}else{
			$uid = $uid_result['msg'];
		}
		//获取session信息end
		$group_id = $params['group_id'];

		if(empty($group_id)){
			return array('code' => 300, 'msg' => '入参有误');
		}

		if(!empty($params['cid'])){
			$uid_aa_result = $this->get_uid_by_connect_id($params['cid']);
			if($uid_aa_result['code']!='200'){
//			return $uid_result;
			}else{
				$uid_aa = $uid_aa_result['msg'];
			}
		}

		$data = $this->ci->group_model->get_group_info_v1($group_id,$uid,$uid_aa);
		return $data;
	}

	/*
	 * 支付中心调用接口，参团者的信息
	 * */
	function setMemberInfo($params){
		$order_name = $params['order_name'];
		$order_info = $this->ci->group_model->getOrderId($order_name);

		if($order_info['order_type'] == 7){
			$openid = $params['openid'];
//			$openid = 'obGvfjr_50gYVtM8XWkj2KppgzgQ';
			$this->ci->load->library("Weixin", ['sModuleName' => 'Menu']);
			$result = $this->ci->weixin->Menu->getGlobalAccessTokenDetail();
//			var_dump($result);
			list($token, $ttl) = $result;
//			var_dump($token);
//			$token = 'L7jBCyky1Yip-deEpo8f-gYDhPIkgXh4X-_2PpYR4bMq7RRWwT2XXs9dXMLhY15XWykvpNSxLOK1ZCpGlKOyQaeLQvXqLUTipKfTsuM29i8ODGaAGASHK';
			$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$token&openid=$openid&lang=zh_CN";
			$res = json_decode($this->httpGet($url));
			$member_data = array(
				'openid'=>$openid,
				'wx_pic'=>$res->headimgurl?$res->headimgurl:'',
				'wx_name1'=>$res->nickname?base64_encode($res->nickname):'',
			);
//			var_dump($member_data);exit;
			$groupInfo = $this->ci->group_model->getMemberInfo($order_info['id']);
			//判断牛奶团999后送冰糖橙
			if (isset($groupInfo['tag']) && $groupInfo['tag'] == 'CZmiS2'){
			    $this->ci->load->model('order_model');
			    $promotion_pid = 8563;
			    $c = $this->ci->order_model->get_group_order_num($promotion_pid);
			    $soldNum = $c?$c:0;
			    if ($soldNum > 999){
			        $this->ci->group_model->send_gift($groupInfo['uid'],'YRmaz1');
			    }
			}
			if(!empty($groupInfo['wx_name1'])){
				return true;
			}else{
				return $this->ci->group_model->setMemberInfoByPay($order_info['id'],$member_data);
			}
		}elseif( $order_info['channel'] == 99 ){//团购商品
            $this->ci->load->model('active_model');
            return $this->ci->active_model->join_tuan_by_order($order_name);
        }else{
			return false;
		}
	}

	//支付中心调用   afterPay
    function groupAfterPay($orderInfo){
        return $this->ci->group_model->checkGroupStatus($orderInfo);
    }

	function httpGet($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 500);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_URL, $url);

		$res = curl_exec($curl);
		curl_close($curl);

		return $res;
	}
}
