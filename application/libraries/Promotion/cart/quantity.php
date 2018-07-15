<?php
// 满件
// 蔡昀辰 2015
class quantity extends strategy implements strategyInterface {

	private $quantity = 0; // 满足条件的次数
	private $min      = 0;
	private $max      = 0;

	public function implement(&$cart) {

		$this->min = $this->condition->min ? $this->condition->min : 1;
		$this->max = $this->condition->max ? $this->condition->max : PHP_INT_MAX;	

		// 获取满足条件的次数$this->quantity
		$this->meetCombo($cart);

		// (已经)满足的优惠执行
		if ($this->quantity >= $this->min && $this->quantity <= $this->max) {	
			$this->{$this->solution->type}($cart); // 按照solution的type调用对应方法
			$cart['pmt_ids'][] = [$this->id];
		}		

	}

	// 获取满足多少次 放入$this->quantity
	function meetCombo($cart) {
		// 有一件商品在白名单里算满足一次
		if ($this->condition->combo == 'one') {
			foreach($cart['items'] as $product) {
				if ( 
					$this->meet($product['product_id'], $this->product) && 
					($product['item_type'] == 'normal' || $product['item_type'] == 'o2o') &&
					$product['active_limit'] == 0
				) 
					$this->quantity += $product['qty'];
				
			}	
		// 有n件商品在白名单里算满足一次	
		} else {
			$combo_count = 0;
			while ( $this->getCombo($cart) ) {
				$combo_count++;
			}
			$this->quantity = $combo_count;
		}

	}

	// 获取符合条件的组合
	private function getCombo(&$cart) {

		// 一个有效的组合 包含cart items和白名单交集里对应product_id的sku_id
		$combo = []; 

		// 尝试获取组合
		foreach($this->product->white as $product_id) {
			foreach ($cart['items'] as $product) {
				if ($product['product_id'] == $product_id && $product['qty'] > 0 && ($product['item_type'] == 'normal' || $product['item_type'] == 'o2o') ) {
					array_push($combo, $product['sku_id']);
				}
			}
		}

		// 如果组合完全满足预设满足件数，那么从购物车里减去组合并返回成功
		if ($this->condition->combo == 'some')
			if ( count($combo) >= $this->condition->combo_num ) {
				foreach ($combo as $sku_id) {
					$cart['items']['normal_'.$sku_id]['qty']--;
				}
				return true;
			}		
		
		// 如果组合完全满足白名单，那么从购物车里减去组合并返回成功
		if ($this->condition->combo == 'all')	
			if ( count($combo) == count($this->product->white) ) {
				foreach ($combo as $sku_id) {
					$cart['items']['normal_'.$sku_id]['qty']--;
				}
				return true;
			}
	}

	// 换购提醒
	private function exchange(&$cart) {

		if (!isset($cart['pmt_alert'])) {
			$cart['pmt_alert'] = [];
		}	

		// 已经换购过的不用重复提醒
		// foreach ($cart['items'] as $product) {
		// 	if ($product['product_id'] == $this->solution->product_id) {
		// 		return;
		// 	}
		// }

		$gift = $this->getProductName($this->solution->product_id);

		$alert = [
			'pmt_type' => $this->type,
			'solution' => [
				'title'  => "{$this->solution->add_money}元换购{$gift}，点击查看",
				'type'   => $this->solution->type,
				'tag'    => 换,
				'url'    => true,
				'pmt_id' => $this->id
			]
		];

		array_push($cart['pmt_alert'], $alert);
	}	

	// 赠品执行
	private function gift(&$cart) {

		// 重复
		if($this->condition->repeat == "true") {
			$repeat = floor($this->quantity / $this->min);	
		} else {
			$repeat = 1;
		}		

		$gift = $this->getGift($this->solution->product_id, $repeat);
		$key  = array_keys($gift)[0];
		$cart['items'][$key] = $gift[$key];

	}	

	// 减免执行
	private function discount(&$cart) {

		if (!isset($cart['pmt_total'])) {
			$cart['pmt_total'] = 0;
		}		

		// 重复
		if($this->condition->repeat == "true") {
			$repeat = $this->quantity / $this->min;	
		} else {
			$repeat = 1;
		}

		$cart['pmt_total'] += $this->solution->reduce_money * floor($repeat);		

	}	

}
