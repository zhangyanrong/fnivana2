<?php
// 满件优惠策略v1
// 蔡昀辰 2016
class Quantity_v1_model extends CI_model {
    
    public $quantity = 0;
    private $promotion;
    private $cart;
    private $list;
        
    // 载入优惠
    public function load($strategy, $promotion) {
        
        $quantity_strategy = new Quantity_v1_model();
        foreach($strategy as $key=>$value) {
            $quantity_strategy->$key = $value;
        }
        $quantity_strategy->promotion = $promotion;
        return $quantity_strategy;
        
    }
    
    // 执行优惠
    public function implement($list, &$cart) {

        $this->cart = $cart;
        $combos = [];

        // 获取组合
        while( $this->promotion->getCombo($list, $this, $combos) ) {
            $this->quantity++;
        }
        
        // 已经满足件数条件
        if ($this->quantity >= $this->condition->min && $this->quantity <= $this->condition->max) {	
            // 执行对应优惠
            $func = $this->solution->type . 'Exec';
            $this->$func(); 
            
			// 记录已经执行的优惠
			$this->record($combos, $list);
        }

       $cart = $this->cart;
        
    }
    

    // 执行减免
    private function discountExec() {
        
		// 重复优惠
		if($this->condition->repeat == 'true') 
			$repeat = $this->quantity / $this->condition->min;	
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

        // 换购提醒添加到购物车
		$this->cart['pmt_alert'][] = $alert;
	}	    
    
	// 赠品执行
	private function giftExec() {

        // 重复优惠
        if($this->condition->repeat == 'true') 
            $repeat = floor( $this->quantity / $this->condition->min );	
        else 
            $repeat = 1;

        // 赠品添加到购物车(新逻辑)
        if($this->promotion->cart) {
            $this->promotion->cart->addGift($this->solution->product_id, $repeat);
            return;
        }

        // 赠品添加到购物车(老逻辑)
        $gift = $this->promotion->getGift($this->solution->product_id, $repeat);
        $key  = array_keys($gift)[0];

        $this->cart['items'][$key] = $gift[$key];

	}	    

	// 记录已经执行到的优惠
	private function record($combos, $valid_list) {

		// 优惠明细 v1
		$this->cart['pmt_ids'][] = (object)[
			'id'       => $this->id,
			'name'     => $this->name,
			'type'     => $this->type,
			'solution' => $this->solution
		];     

        // 优惠明细 v2
        $list  = []; 
        $items = []; 
		$total_price = 0;

		if($this->condition->repeat == 'true') 
			$repeat = $this->quantity / $this->condition->min;	
		else 
			$repeat = 1;

        $total_reduce_money = $this->solution->reduce_money * floor($repeat);      

        // 去除不满足条件的item
        $remainder = $this->quantity % $this->condition->min;
        while($remainder) {
            array_pop($combos);
            $remainder--;
        }

        // 列举所有item [product_id, ...]
        foreach($combos as $combo) {
            foreach($combo as $item) {
                $list[] = $item;
            }
        }

        // 获取每个item的单价 [{product_id:price, ...}]
        foreach($list as $product_id) {
            foreach($valid_list as $item) {
                if($product_id == $item->product_id)
                     $items[] = [
						'product_name' => $item->name,
						'product_id'   => $item->product_id,
						'sku_id'       => $item->sku_id,                        
						'product_no'   => $item->product_no,
						'price'        => $item->price,                      
                    ];
            }
        }

		// 分摊减免金额到每个单件商品上
		if($this->solution->type == 'discount') {

            // 计算满足条件的item的原价总和
            foreach($items as $item) {
                $total_price += $item['price'];
            }

            // 计算出每个item的价格占比
            foreach($items as &$item) {
                $item['ratio'] = $item['price'] / $total_price;
            }       

            // 计算出每个item的优惠金额占比
            foreach($items as &$item) {
                foreach($item as $product_name=>&$price) {
                    $item['reduce_money'] = $item['ratio'] * $total_reduce_money;
                    $item['reduce_money'] = number_format($item['reduce_money'], 2, '.', '');
                }
            }

			// 修正百分比导致的余数
			$reduce_money_sum = 0;
			foreach($items as &$item) {
				$reduce_money_sum += $item['reduce_money'];
			}
			$fix_price = $total_reduce_money - $reduce_money_sum;

			if($fix_price) {
				$fix_price = number_format($fix_price, 2, '.', '');
				$items[0]['reduce_money'] = (string)($items[0]['reduce_money'] + $fix_price);
			}            

        }

        // 添加到购物车
        $pmt = [];

        if($this->solution->type != 'exchange')
            $pmt = [
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