<?php
// 满件优惠策略v1
// 蔡昀辰 2016
class Amount_v1_model extends CI_model {
    
    public $amount = 0;
    private $promotion;
    private $cart;
    private $list;    
    
    // 载入优惠
    public function load($strategy, $promotion) {
        
        $amount_strategy = new Amount_v1_model();
        foreach($strategy as $key=>$value) {
            $amount_strategy->$key = $value;
        }
        $amount_strategy->promotion = $promotion;
        return $amount_strategy;
        
    }
    
    // 执行优惠
    public function implement($list, &$cart) {

        $this->cart = $cart;
		$items = [];

        // 获取组合
        foreach($list as $product) {
            if ( $this->promotion->meetList($product, $this) ) {
				$this->amount += $product->price * $product->qty;
				// 符合白名单的商品
				for($i=0;$i<$product->qty;$i++) {
					$items[] = clone $product;
				}
			}
        }
        
        // 已经满金额条件
        if ($this->amount >= $this->condition->min && $this->amount <= $this->condition->max) {	
			// 执行对应优惠
            $func = $this->solution->type . 'Exec';
            $this->$func(); 
			
			// 记录已经执行的优惠
			$this->record($items);
        }
        
		// 将要满足金额条件的优惠提醒
		if ($this->solution->alert == 'true' && $this->amount < $this->condition->min) {
			$func = $this->solution->type . 'Alert';
			$this->$func(); // 对应优惠提醒
		}
        
       $cart = $this->cart;
        
    }    
    
    // 执行减免
    private function discountExec() {

		// 重复优惠
		if($this->condition->repeat == 'true') 
			$repeat = $this->amount / $this->condition->min;	
		else 
			$repeat = 1;

        // 优惠金额添加到购物车
        $this->cart['pmt_total'] += $this->solution->reduce_money * floor($repeat);
		$this->cart['pmt_total'] = number_format($this->cart['pmt_total'], 2, '.', '');

    }
        
	// 换购执行(提醒)
	private function exchangeExec() {

		$alert = [
            'id'       => $this->id,
            'name'     => $this->name,
            'type'     => $this->type,
			'pmt_type' => $this->type, // todo: remove
			'solution' => [
				'title'  => "{$this->name}",
				'type'   => $this->solution->type,
				'tag'    => 换,
				'url'    => true,
				'pmt_id' => $this->id
			]
		];

        // 赠品添加到购物车
		$this->cart['pmt_alert'][] = $alert;
	}	    
    
	// 赠品执行
	private function giftExec() {

		// 重复优惠
		if($this->condition->repeat == 'true') 
			$repeat = floor( $this->amount / $this->condition->min );	
		else 
			$repeat = 1;

		$gift = $this->promotion->getGift($this->solution->product_id, $repeat);
		$key  = array_keys($gift)[0];
        
        // 赠品添加到购物车
		// todo: bugfix 已有此赠品要改qty
		$this->cart['items'][$key] = $gift[$key];

	}    
    
	// 减免提醒
	private function discountAlert() {
		return;	
	}	 
    
	// 换购提醒
	private function exchangeAlert() {

		// 非全场的话不要提醒
		if($this->product->all == 'false')
			return;

		// 互斥判断
		// 如果pmt_alert里已经有过同类型的solution, 只显示min更小的哪一个
        foreach($this->cart['pmt_alert'] as $key=>$current_alert) {
            if ($current_alert['solution']['type'] == 'exchange' && $current_alert['solution']['tag'] == '促') {
                if($current_alert['min'] <= $this->condition->min)
                    return;
                else
                    array_splice($this->cart['pmt_alert'], $key, 1);
            }
        }			

		$diff = number_format($this->condition->min - $this->amount, 2, '.', '');

		$alert = [
            'id'       => $this->id,
            'name'     => $this->name,
            'type'     => $this->type,
			'pmt_type' => $this->type, // todo: remove
			'solution' => [
				'title'      => "{$this->name}，还差{$diff}元，去凑单",
				'name'       => $this->name,
				'type'       => $this->solution->type,
				'tag'        => '促',
				'url'        => true,
				'pmt_id'     => $this->id,
				'outofmoney' => $diff,
			],
			'min'=> $this->condition->min,
		];		

        // 换购提醒添加到购物车
		$this->cart['pmt_alert'][] = $alert;		

	}
    
	// 赠品提醒
	private function giftAlert() {

		// 非全场的话不要提醒
		if($this->product->all == 'false')
			return;

		// 互斥判断
		// 如果pmt_alert里已经有过同类型的solution, 只显示min更小的哪一个
        foreach($this->cart['pmt_alert'] as $key=>$current_alert) {
            if ($current_alert['solution']['type'] == 'gift') {
                if($current_alert['min'] <= $this->condition->min)
                    return;
                else
                    array_splice($this->cart['pmt_alert'], $key, 1);
            }
        }			

		$diff = number_format($this->condition->min - $this->amount, 2, '.', '');

		$alert = [
            'id'       => $this->id,
            'name'     => $this->name,
            'type'     => $this->type,
			'pmt_type' => $this->type, // todo: remove
			'solution' => [
				'title'      => "{$this->name}，还差{$diff}元，去凑单",
				'name'       => $this->name,				
				'type'       => $this->solution->type,
				'tag'        => '促',
				'url'        => true,
				'pmt_id'     => $this->id,
				'outofmoney' => $diff,
			],
			'min' => $this->condition->min
		];

		// 赠品提醒添加到购物车
		$this->cart['pmt_alert'][] = $alert;		
	}    
    
	// 记录已经执行到的优惠
	private function record($items) {

		// 优惠明细 v1
		$this->cart['pmt_ids'][] = (object)[
			'id'       => $this->id,
			'name'     => $this->name,
			'type'     => $this->type,
			'solution' => $this->solution
		];

		// 优惠明细 v2
		$total_price = 0;

		if($this->condition->repeat == 'true') 
			$repeat = $this->amount / $this->condition->min;	
		else 
			$repeat = 1;

		$total_reduce_money = $this->solution->reduce_money * floor($repeat);	

		// 分摊减免金额到每个单件商品上
		if($this->solution->type == 'discount') {

			// 计算满足条件的item的原价总和
			foreach($items as $item) {
				$total_price += $item->price;
			}

			// 计算出每个item的价格占比
			foreach($items as &$item) {
				$item->ratio = $item->price / $total_price;
			}

			// 计算出每个item的优惠金额占比
			foreach($items as &$item) {
				$item->reduce_money = $item->ratio * $total_reduce_money;
				// bcmul($value['weight'],$value['qty'],2);
				$item->reduce_money = number_format($item->reduce_money, 2, '.', '');
			}

			// 修正百分比导致的余数
			$reduce_money_sum = 0;
			foreach($items as &$item) {
				$reduce_money_sum += $item->reduce_money;
			}
			$fix_price = $total_reduce_money - $reduce_money_sum;
			
			if($fix_price) {
				$fix_price = number_format($fix_price, 2, '.', '');
				$items[0]->reduce_money = (string)($items[0]->reduce_money + $fix_price);
			}

		}

		// 美化输出
		foreach($items as &$item) {
			unset($item->qty);
			unset($item->status);
			unset($item->item_type);
		}
		
		// 添加到购物车
		$pmt = [];

		if($this->solution->type != 'exchange')
			$pmt =  [
				'id'            => $this->id,
				'name'          => $this->name,
				'type'          => $this->type,
				'solution_type' => $this->solution->type,
				'items'         => $items
			];

		if($this->solution->type == 'discount') {
			$pmt['total_price']        = $total_price;
			$pmt['total_reduce_money'] = $total_reduce_money;
			$pmt['reduce_money_sum']   = $reduce_money_sum;
			$pmt['fix_price']          = $fix_price;
		}

		if($this->solution->type == 'gift') {
			$pmt['gift_id']        = $this->solution->product_id;          
		} 		
			
		if( count($pmt) > 0 )	
			$this->cart['pmt_detail'][] = $pmt;	

	}
    
}