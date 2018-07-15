<?php
// 用法:
// 
// 第一步：载入此模块：$this->ci->load->library('Promotion/promotion');
// 第二步：载入有效的优惠策略：$this->ci->promotion->loadStrategies($channel, $province, $member)   print_r($this->ci->promotion->strategies);
// 第三步：返回执行完优惠策略的购物车结构：$cart = this->ci->promotion->implementStrategies($this->_cart_info);
    
class promotion extends CI_Model {

	var $strategies = [];

	// 必要的购物车字段
	var $required_keys = [
		'name',
		'sku_id',
		'product_id',
		'product_no',
		'item_type',
		'status',
		'qty',
		'unit',
		'spec',
		'price',
		'amount',
		'photo',
	];

	// 可用的字段(不会被过滤)
	var $enabled_keys= [
		'name',
		'sku_id',
		'product_id',
		'product_no',
		'item_type',
		'status',
		'qty',
		'unit',
		'spec',
		'price',
		'sale_price',
		'amount',
		'photo',	
		'store_id',
		'pmt_id',
		'user_gift_id',  
		'gift_send_id',
		'gift_active_type'
	];

	function __construct() {
		require_once "cart/strategy.php";
		$this->load->model('strategy_model');
	}

	// 载入优惠策略
	// $channel 渠道
	// $platform 平台
	// $province 省市
	// $member 会员(等级)
	public function loadStrategies($channel, $platform, $province, $member) {

		// 获取id
		$this->strategies = [];
		$strategies = $this->strategy_model->getAll($channel, $platform, $province, $member);

		// 通过id获取字段
		foreach($strategies as $strategy) {

			// 策略模式
			// 根据strategy_name创建策略对象
			$strategy_name = $strategy['type'];
			require_once "cart/{$strategy_name}.php";
			$strategy = new $strategy_name($strategy['id'], $strategy['product'], $strategy['condition'], $strategy['solution']);			
			array_push($this->strategies, $strategy);
		}

		// print_r($this->strategies);die;
		return $this;
	}

	// 实现购物车
	// 返回 执行过各类优惠的购物车结构
	public function implementStrategies($raw_cart) {

		$cart = $this->verifyCart($raw_cart);

		if ($cart == false) {
			return false;
		}

		foreach ($this->strategies as $strategy) {
			$strategy->implement($cart);
		}

		// print_r($raw_cart);
		// print_r($cart);

		return $cart;
	}

	// 验证购物车字段是否完整
	// 返回 只包含需要的字段的购物车结构/false
	private function verifyCart($raw_cart) {

		$cart = [
			'items'     =>[],
			'pmt_alert' =>[]
		];	

		$cart['items'] = '';

		// 验证需要的字段
		foreach($raw_cart['items'] as $item) {
			foreach($this->required_keys as $key) {
				if ( !array_key_exists($key, $item) )
					return false;
			}
		}		

		// 获取有用的字段
		// foreach($raw_cart['items'] as $sku=>$item) {
		// 	foreach($this->enabled_keys as $key) {
		// 		$cart['items'][$sku][$key] = $item[$key];
		// 	}
		// }

		$cart['items'] = $raw_cart['items'];

		return $cart;

	}


}