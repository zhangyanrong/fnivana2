<?php
// 满额
// 蔡昀辰 2015
class amount extends strategy implements strategyInterface {

	var $amount = 0; // 符合条件的总金额
	var $min    = 0;
	var $max    = 0;

	public function implement(&$cart) {

		$this->min = $this->condition->min ? $this->condition->min : 0;
		$this->max = $this->condition->max ? $this->condition->max : PHP_INT_MAX;		

		// 获取符合条件的总金额
		foreach($cart['items'] as $product) {
			if ( 
				$this->meet($product['product_id'], $this->product) && 
				($product['item_type'] == 'normal' || $product['item_type'] == 'o2o') &&
				$product['active_limit'] == 0
			) 
				$this->amount += $product['price'] * $product['qty'];

		}

		// (已经)满足的优惠执行
		if ($this->amount >= $this->min && $this->amount <= $this->max) {
			$func = $this->solution->type;
			$this->$func($cart); // 按照solution的type调用对应方法
			$cart['pmt_ids'][] = [$this->id];
		}		

		// (将要)满足的优惠提醒
		if ($this->condition->alert && $this->amount < $this->min) {
			$func = $this->solution->type.'Alert';
			$this->$func($cart); // 按照solution的type调用对应方法
		}
		
	}

	// 赠品提醒
	private function giftAlert(&$cart) {

		// 不是全场的话不要提醒
		if($this->product->all === 'false')
			return;

		if (!isset($cart['pmt_alert'])) {
			$cart['pmt_alert'] = [];
		}

		// 互斥判断
		// 如果pmt_alert里已经有过同类型的solution, 只显示min更小的哪一个
		if ($this->solution->mutex) {
			foreach($cart['pmt_alert'] as $key=>$current_alert) {
				if ($current_alert['solution']['type'] == 'gift' && $current_alert['solution']['mutex']) {

					if($current_alert['min'] <= $this->min)
						return;
					else
						array_splice($cart['pmt_alert'], $key, 1);

				}
			}			
		}


		$diff = number_format($this->min - $this->amount, 2, '.', '');
		$gift = $this->getProductName($this->solution->product_id);

		$alert = [
			'pmt_type' => $this->type,
			'solution' => [
				'title'  => "满{$this->min}元送{$gift}，还差{$diff}元，去凑单",
				'type'   => $this->solution->type,
				'tag'    => '促',
				'mutex'  => $this->solution->mutex,
				'url'    => true,
				'pmt_id' => $this->id,
				'outofmoney'  => $diff,
			],
			'min' => $this->min
		];

		$cart['pmt_alert'][] = $alert;		
	}

	// 减免提醒
	private function discountAlert(&$cart) {
		return;	
	}	

	// 换购提醒
	private function exchangeAlert(&$cart) {

		// 不是全场的话不要提醒
		if($this->product->all === 'false')
			return;


		if (!isset($cart['pmt_alert'])) {
			$cart['pmt_alert'] = [];
		}

		// 互斥判断
		// 如果pmt_alert里已经有过同类型的solution, 只显示min更小的哪一个
		if ($this->solution->mutex) {
			foreach($cart['pmt_alert'] as $key=>$current_alert) {
				if ($current_alert['solution']['type'] == 'exchange' && $current_alert['solution']['mutex']) {

					if($current_alert['min'] <= $this->min)
						return;
					else
						array_splice($cart['pmt_alert'], $key, 1);

				}
			}			
		}		

		$diff = number_format($this->min - $this->amount, 2, '.', '');
		$gift = $this->getProductName($this->solution->product_id);

		$alert = [
			'pmt_type' => $this->type,
			'solution' => [
				'title'  => "满{$this->min}元{$this->solution->add_money}元换购{$gift}，还差{$diff}元，去凑单",
				'type'   => $this->solution->type,
				'tag'    => '促',
				'mutex'  => $this->solution->mutex,
				'url'    => true,
				'pmt_id' => $this->id,
				'outofmoney'  => $diff,
			],
			'min'    => $this->min,
		];		

		$cart['pmt_alert'][] = $alert;		

	}		

	
	// 赠品执行
	private function gift(&$cart) {

		// 重复
		if($this->condition->repeat == "true") {
			$repeat = floor($this->amount / $this->min);	
		} else {
			$repeat = 1;
		}

		$gift = $this->getGift($this->solution->product_id, $repeat);
		$key  = array_keys($gift)[0];
		$cart['items'][$key] = $gift[$key];

	}

	// 换购提醒
	private function exchange(&$cart) {

		if (!isset($cart['pmt_alert'])) {
			$cart['pmt_alert'] = [];
		}

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

	// 减免执行
	private function discount(&$cart) {

		if (!isset($cart['pmt_total'])) {
			$cart['pmt_total'] = 0;
		}		

		// 重复
		if($this->condition->repeat == "true") {
			$repeat = $this->amount / $this->min;	
		} else {
			$repeat = 1;
		}
			
		$cart['pmt_total'] += $this->solution->reduce_money * floor($repeat);		

	}

}
