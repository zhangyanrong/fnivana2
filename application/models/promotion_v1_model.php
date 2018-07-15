<?php
// 购物车优惠引擎v1
// 蔡昀辰 2016
class Promotion_v1_model extends CI_model {

    public $platform;               // b2c/o2o
    public $channel;                // 渠道(source/terminal) app/pc/wap
    public $province;               // 106092 上海
    public $user_rank;              // user array from memcache
    public $strategies = [];
    private $redis;

    public function __construct() {
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
        $this->load->model('quantity_v1_model');
        $this->load->model('amount_v1_model');
    }


    // 从redis载入在范围内的strategy
	// $channel     渠道
	// $platform    平台
	// $province    省市
	// $member      会员(等级)
    public function loadStrategies($channel = 'app', $platform = 'b2c', $region = '106092', $province = '106092', $warehouse = '1', $user_rank = 1, $cart = null) {

        // 初始化范围
        $this->channel   = $channel;
        $this->platform  = $platform;
        $this->province  = $province;
        $this->user_rank = $user_rank ? $user_rank: 1;
        $this->warehouse = $warehouse ? $warehouse : '1';
        $this->cart      = $cart; // cart_vt, to add gift

        if(  $this->config->item('promotion_warehouse') ) {
            // 通过redis集合索引获取redis key
            $range_ids = $this->redis->sInter(
                "promotion_v1:platform:{$this->platform}",
                "promotion_v1:channel:{$this->channel}",
                "promotion_v1:warehouse:{$this->warehouse}",
                "promotion_v1:member:{$this->user_rank}"
            );
        } else {
            // 通过redis集合索引获取redis key
            $range_ids = $this->redis->sInter(
                "promotion_v1:platform:{$this->platform}",
                "promotion_v1:channel:{$this->channel}",
                "promotion_v1:province:{$this->province}",
                "promotion_v1:member:{$this->user_rank}"
            );
        }

        $started_ids = $this->redis->zRangeByScore("promotion_v1:start", "-inf", time());
        $not_end_ids = $this->redis->zRangeByScore("promotion_v1:end", time(), "+inf");
        $ids         = array_intersect($range_ids, $started_ids, $not_end_ids); // 交集

        // 获取strategy数据
        foreach($ids as &$id) {
            $id = "promotion_v1:strategy:{$id}";
        }
        $this->strategies = $this->redis->mGet($ids); // fetch from redis

        // decode
        foreach($this->strategies as &$strategy) {
            $strategy = json_decode($strategy);
        }

        // 获取strategy对象
        foreach($this->strategies as &$strategy) {
            if($strategy->type == 'quantity')
                $strategy = $this->quantity_v1_model->load($strategy, $this);
            if($strategy->type == 'amount')
                $strategy = $this->amount_v1_model->load($strategy, $this);
        }

        return $this;

        die(json_encode($this->strategies)); // test
    }

    // 执行优惠
    // input：    购物车
    // output：   添加了优惠信息的购物车
    public function implementStrategies($cart) {

        $cart['pmt_total']       = 0;
        $cart['pmt_alert']       = [];
        $cart['pmt_ids']         = [];
        $cart['discount_detail'] = [];
        $cart['pmt_detail']      = [];

        foreach($this->strategies as $strategy) {
            $list = $this->createList($cart);
            if($list)
                $strategy->implement($list, $cart);
        }

        return $cart;
    }

    // 获取多个有效规则
    public function getStrategies() {
        return $this->strategies;
    }

    // 获取一条优惠规则
    public function getOneStrategy($pmt_id) {
        $strategy = $this->redis->get("promotion_v1:strategy:{$pmt_id}");
        return json_decode($strategy);
    }

    // 生成购物车item清单(去除不参加活动的商品)
    private function createList($cart) {

        $list = [];

        foreach($cart['items'] as $key=>$item) {

            //  不参加促销活动
            if($item['active_limit'] > 0)
                continue;

            if( !in_array($item['item_type'], ['normal','o2o']) )
                continue;

            // 未勾选
            if($item['selected'] != true)
                continue;

            // 已失效
            if($item['valid'] != true)
                continue;

            $product             = new stdClass();
            $product->name       = $item['name'];
            $product->product_id = $item['product_id'];
            $product->sku_id     = $item['sku_id'];
            $product->product_no = $item['product_no'];
            $product->qty        = $item['qty'];
            $product->item_type  = $item['item_type'];
            $product->status     = $item['status'];
            $product->price      = $item['price'];
            $list[] = $product;

        }

        return $list;
    }

    // 获取有效组合
    public function getCombo($list, $strategy, &$combos) {

        $combo = []; // 一个有效的组合, 包含product_id

        // 尝试获取组合
        foreach( $list as $product ) {
            if( $this->meetList($product, $strategy) ) {
                if( !in_array($product->product_id, $combo) ) // 如果满足白名单条件
                    $combo[] = $product->product_id;
                if ( $this->meetCombo($combo, $strategy) ) { // 如果满足组合条件
                    $this->reduceList($combo, $list); // 清单减去
                    $combos[] = $combo;
                    $combo = []; // 清空组合缓存
                    return true;
                }
            }
        }

        return false;

    }

    // 满足白名单条件(以及全场)
    public function meetList($product, $strategy) {

        if($strategy->product->all == 'true' && $product->qty > 0)
            return true;

        if ( in_array($product->product_id, $strategy->product->white) && $product->qty > 0 )
            return true;

    }

    // 满足组合(combo)条件
    private function meetCombo($combo, $strategy) {

        switch ($strategy->condition->combo) {
            case 'one':
                if( count($combo) >= 1)
                    return true;
                break;
            case 'all':
                if( count($combo) == count($strategy->product->white) )
                    return true;
                break;
            default:
                if( count($combo) == $strategy->condition->combo_num )
                    return true;
                break;
        }

    }

    // 减去已经满足的组合
    private function reduceList($combo, &$list) {
        foreach($combo as $product_id) {
            foreach($list as $product) {
                if($product_id == $product->product_id)
                    $product->qty--;
            }
        }
    }

    // 获取赠品
    // 无限库存
    // todo: cache
	public function getGift($product_id, $repeat = 1) {

		// product table
		$product = $this->db->select('id, product_name, bphoto, photo, middle_photo, thum_photo, thum_min_photo, template_id')
			->from('product')->where('id', $product_id)->limit(1)->get()->row();

        if(!$product)
            return;

        // 获取产品模板图片
        if ($product->template_id) {
            $this->load->model('b2o_product_template_image_model');
            $templateImages = $this->b2o_product_template_image_model->getTemplateImage($product->template_id, 'main');
            if (isset($templateImages['main'])) {
                $product->bphoto = $templateImages['main']['big_image'];
                $product->photo = $templateImages['main']['image'];
                $product->middle_photo = $templateImages['main']['middle_image'];
                $product->thum_photo = $templateImages['main']['thumb'];
                $product->thum_min_photo = $templateImages['main']['small_thumb'];
            }
        }

		// sku table
		$sku = $this->db->select()->from('product_price')->where('product_id', $product_id)->limit(1)->get()->row();

		$ret = [
			'gift_'.$sku->id => [
				'name'       => $product->product_name,
				'sku_id'     => $sku->id,
				'product_id' => $product->id,
				'product_no' => $sku->product_no,
				'item_type'  => 'gift',
				'status'     => 'active',
				'selected'   => true,
				'valid'      => true,
				'cart_tag'   => $product->cart_tag,
				'qty'        => $repeat,
				'unit'       => $sku->unit,
				'spec'       => $sku->volume,
				'weight'     => $sku->weight,
				// 'price'      => $sku->price, //todo:open
				'price'      => '0.00',
				'amount'     => '0',
                'photo' => [
					'huge'   => $product->bphoto ? PIC_URL.$product->bphoto : '',
					'big'    => $product->photo ? PIC_URL.$product->photo : '',
					'middle' => $product->middle_photo ? PIC_URL.$product->middle_photo : '',
					'small'  => $product->thum_photo ? PIC_URL.$product->thum_photo : '',
					'thum'   => $product->thum_min_photo ? PIC_URL.$product->thum_min_photo : '',
                ],
			]
		];

		return $ret;
	}

    // 获取商品名称 (未使用)
	public function getProductName($id) {
		$r = $this->db->select('product_name')->from('product')->where('id', $this->solution->product_id)->limit(1)->get()->row();
		return $r->product_name;
	}

}