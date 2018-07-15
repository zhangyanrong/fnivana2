<?php

class Amount_v2_model extends CI_model {

    var $amount = 0;

    public function init($data) {

        $strategy = new Amount_v2_model();

        foreach($data as $key=>$value) {
            $strategy->$key = $value;
        }

        return $strategy;

    }

    public function simplify() {
        // TODO 同步到cart v3
        $promotion = new StdClass();
        $promotion->id                  = $this->id;
        $promotion->name                = $this->name;
        $promotion->range_type          = $this->range_type;
        $promotion->range_products      = $this->range_products;
        $promotion->condition_type      = $this->condition_type;
        $promotion->solution_type       = $this->solution_type;
        $promotion->condition_min       = $this->condition_min;
        $promotion->condition_max       = $this->condition_max;
        $promotion->condition_qty_limit = $this->condition_qty_limit;
        $promotion->start               = $this->start;
        $promotion->end                 = $this->end;

        $promotion->amount = $this->amount;

        if($promotion->solution_type == 'discount') {
            $promotion->solution_reduce_money = $this->solution_reduce_money;
            $promotion->discount_total = $this->discount_total;
        }

        if($promotion->solution_type == 'gift')
            $promotion->solution_products = $this->solution_products;
        if($promotion->solution_type == 'exchange')
            $promotion->solution_products = $this->solution_products;

        return $promotion;
    }

    // 多品
    public function group($products, $cart) {

        // 筛选符合条件的商品
        $valid_products = [];

        foreach($products as $product) {
            if( in_array($product->product_id, $this->range_products) )
                if( in_array($product->store_id, $this->range_stores) )
                    $valid_products[] = $product;
        }

        // 满足的金额
        foreach($valid_products as $product) {
            $amount = bcmul($product->price, $product->qty, 2);
            $this->amount = bcadd($this->amount, $amount, 2);
        }

        // 已经满金额条件
        if ($this->amount >= $this->condition_min && $this->amount <= $this->condition_max) {
			// 执行对应优惠
			if( $this->solution_type == 'discount' )
                $this->discount($valid_products, $cart);
			if( $this->solution_type == 'gift' )
                $this->gift($valid_products, $cart);
			if( $this->solution_type == 'exchange' )
                $this->exchange($valid_products, $cart);

            // 记录满足条件的(执行到的)优惠
            $cart->promotions[] = $this->simplify();
        }

        // 将要满足金额条件的优惠提醒
        if($this->amount > 0)
    		if ($this->solution_alert && $this->amount < $this->condition_min)
    			$this->alert($valid_products, $cart);

    }

    // 单品
    public function single($products, $cart) {
        // 筛选符合条件的商品
        $valid_products = [];

        foreach($products as $product) {
            if( in_array($product->product_id, $this->range_products) )
                if( in_array($product->store_id, $this->range_stores) )
                    $valid_products[] = $product;
        }

        // 满足的金额
        foreach($valid_products as $product) {
            $amount = bcmul($product->price, $product->qty, 2);
            $this->amount = bcadd($this->amount, $amount, 2);
        }

        // 已经满金额条件
        if ($this->amount >= $this->condition_min && $this->amount <= $this->condition_max) {
			// 执行对应优惠
			if( $this->solution_type == 'discount' )
                $this->discount($valid_products, $cart);

            // 记录满足条件的(执行到的)优惠
            $cart->promotions[] = $this->simplify();
        }

        // 将要满足金额条件的优惠提醒
        if($this->amount > 0)
    		if ($this->solution_alert && $this->amount < $this->condition_min)
    			$this->alert($valid_products, $cart);
    }

    // 全场
    public function all($products, $cart) {

        $valid_products = $products;

        foreach($valid_products as $product) {
            if( in_array($product->store_id, $this->range_stores) )
                $this->amount = bcadd($this->amount, $product->amount, 2);
        }

        // 已经满金额条件
        if ($this->amount >= $this->condition_min && $this->amount <= $this->condition_max) {
			// 执行对应优惠
			if( $this->solution_type == 'discount' )
                $this->discount($valid_products, $cart);
            if( $this->solution_type == 'gift' )
                $this->gift($valid_products, $cart);
			if( $this->solution_type == 'exchange' )
                $this->exchange($valid_products, $cart);

            // 记录满足条件的(执行到的)优惠
            $cart->promotions[] = $this->simplify();
        }

        // 将要满足金额条件的优惠提醒
        if($this->amount > 0)
    		if ($this->solution_alert && $this->amount < $this->condition_min)
    			$this->alert($valid_products, $cart);

    }

    // 减免
    // add percentage, discount to cart->products
    private function discount($valid_products, $cart) {

        // 重复优惠
		if($this->condition_repeat)
			$repeat = floor( bcdiv($this->amount, $this->condition_min, 2) );
		else
			$repeat = 1;

        // 总优惠金额
        $this->discount_total = bcmul($this->solution_reduce_money, $repeat, 2);

        // 满足优惠的商品原价总计
        $valid_total_price = 0;

        foreach($valid_products as $product) {
            $valid_total_price = bcadd($valid_total_price, $product->amount, 2);
        }

        // 参加活动商品的价格比例
        foreach($valid_products as &$product) {
            $product->percentage = bcdiv($product->amount, $valid_total_price, 4);
        }

        // 分摊优惠金额(累计)
        $discounted = 0;

        foreach($valid_products as &$product) {
            $discount = bcmul($product->percentage, $this->discount_total, 2);
            $product->discount = bcadd($product->discount, $discount, 2);
            $discounted = bcadd($discounted, $discount, 2);
        }

        // 补差额
        $diff = bcsub($this->discount_total, $discounted, 2);
        $valid_products[0]->discount = bcadd($valid_products[0]->discount, $diff, 2);

    }

    // 赠品
    private function gift($valid_products, $cart) {

        // 重复优惠
		if($this->condition_repeat)
			$repeat = floor( bcdiv($this->amount, $this->condition_min, 2) );
		else
			$repeat = 1;

        $item           = new StdClass();
        $item->pid      = $this->solution_products[0];
        $item->sid      = $valid_products[0]->store_id;
        $item->type     = 'gift';
        $item->qty      = $repeat;
        $item->selected = '1';

        $product = $cart->getProduct($item);
        $product->valid = true;

        // 验证商品
        $this->load->model('validator_v2_model');
        $error = $this->validator_v2_model->check($product, $cart);

        // 如果返回错误
        if($error !== false) {
            $product->valid = false;
            $product->selected = false;
            $cart->addError($error);
        }

        // 添加赠品
        $cart->products[] = $product;

    }

    // 换购(提醒)
    private function exchange($valid_products, $cart) {

        // 已经换购过的不用重复提醒
        foreach ($cart->products as $product) {
        	if ( in_array($product->product_id, $this->solution_products) )
                if($product->type == 'exchange')
        		      return;
        }

        $alert       = new StdClass();
        $alert->name = $this->name;
        $alert->msg  = $this->name;
        $alert->type = 'exchange';
        $alert->tag  = '换';
        $alert->url  = true;
        $alert->range_type     = $this->range_type;
        $alert->condition_type = $this->condition_type;
        $alert->solution_type  = $this->solution_type;
        $alert->pmt_id         = $this->id;
        $alert->store_id       = $valid_products[0]->store_id;

        $cart->alerts[] = $alert;

    }

    // 凑单提醒
    private function alert($valid_products, $cart) {

        // 凑单只显示min更小的哪一个
        foreach($cart->alerts as $key=>$current_alert) {
            if ($current_alert->type == 'addmore' && $current_alert->solution_type == $this->solution_type) {
                if($current_alert->condition_min <= $this->condition_min)
                    return;
                else
                    array_splice($cart->alerts, $key, 1);
            }
        }

        $diff = bcsub($this->condition_min, $this->amount, 2);

        $alert       = new StdClass();
        $alert->name = $this->name;
        // $alert->msg  = "{$this->name}，还差{$diff}元，去凑单";
        $alert->msg  = "{$this->name}，还差{$diff}元";
        $alert->type = 'addmore';
        $alert->tag  = '促';
        // $alert->url  = true;
        $alert->url  = false;
        $alert->range_type     = $this->range_type;
        $alert->condition_type = $this->condition_type;
        $alert->solution_type  = $this->solution_type;
        $alert->pmt_id         = $this->id;
        $alert->outofmoney     = $diff;
        $alert->condition_min  = $this->condition_min;

        $cart->alerts[] = $alert;
    }

}
