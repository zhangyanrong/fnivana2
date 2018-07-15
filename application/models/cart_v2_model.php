<?php

class Cart_v2_model extends CI_model {

    public $cart_id;
    public $stores;
    public $small_stores;
    public $source;
    public $user;
    public $version;
    private $redis;

    public $items      = [];
    public $products   = [];
    public $promotions = [];
    public $errors     = [];
    public $alerts     = [];
    public $total      = null;
    public $count      = 0;

    /**
    * 购物车创建方法
    * @param  number $cart_id       登录用户就是user_id，非登陆用户就是device_id
    * @param  array $store_id_list  store数组
    * @param  string $source        app/pc/wap
    * @param  object $user          login->get_user()返回的user
    * @param  string $version       4.0.0
    * @return object $cart
    */
    function init($cart_id, $store_id_list, $user = null, $source = 'app', $version = null, $tms_region_type = 1) {

        $cart                  = new Cart_v2_model();
        $cart->cart_id         = $cart_id;
        $cart->stores          = $store_id_list;
        $cart->source          = $source;
        $cart->user            = (object) $user;
        $cart->tms_region_type = $tms_region_type;

        $this->load->library('phpredis');
        $cart->redis = $this->phpredis->getConn();

        //  小门店
        if($store_id_list)
            $small_stores = $this->db->select('id')
                ->from('ttgy_b2o_store')
                ->where_in('id', $store_id_list)
                ->where_in('type', [2,3])
                ->get()->result();

        foreach($small_stores as $small_store) {
            $cart->small_stores[] = $small_store->id;
        }
        // print_r($this->small_stores);die;
        //

        $cart->getItems();

        return $cart;
    }

    function addError($error) {
        $this->errors[] = $error;
        return $this;
    }

    function getUser($connect_id) {

        $this->load->library('login');
        $this->login->init($connect_id);

        if ( !$this->login->is_login() )
            return false;

        $user = $this->login->get_user();

        return (object) $user;

    }

    function createError($data) {
        $error = new StdClass();

        $error->code = 300;
        $error->msg  = '未知错误';
        $error->product_name;
        $error->item_id;
        $error->action;

        $error = (object) array_merge( (array) $error, $data );

        return $error;
    }

    private function itemKey($item) {
        $pid          = $item->pid;
        $sid          = $item->sid;
        $type         = $item->type;
        $pmt_id       = $item->pmt_id ? $item->pmt_id : 0;
        $gift_send_id = $item->gift_send_id ? $item->gift_send_id : 0;
        return "{$pid}_{$sid}_{$type}_{$pmt_id}_{$gift_send_id}";
    }

    function createItem($data, $cart_id) {

        $data = (object)$data;

        $item                   = new StdClass();
        $item->pid              = $data->pid;
        $item->sid              = $data->sid;
        $item->type             = $data->type; // normal/gift/exchange/user_gift
        $item->qty              = $data->qty ? $data->qty : 1;
        $item->selected         = $data->selected ? $data->selected : 1;
        $item->gift_send_id     = $data->gift_send_id ? $data->gift_send_id : 0;
        $item->gift_active_type = $data->gift_active_type ? $data->gift_active_type : null;
        $item->user_gift_id     = $data->user_gift_id ? $data->user_gift_id : null;
        $item->pmt_id           = $data->pmt_id ? $data->pmt_id : 0;

        return $item;
    }

    function getItems() {

        $items = $this->redis->get("cart_v2:{$this->cart_id}");

        $this->items = (array)json_decode($items);

        foreach ($this->items as &$item) {
            if(!$item->gift_send_id)
                unset($item->gift_send_id);
            if(!$item->gift_active_type)
                unset($item->gift_active_type);
            if(!$item->user_gift_id)
                unset($item->user_gift_id);
            if(!$item->pmt_id)
                unset($item->pmt_id);
        }

        return $this;

    }

    function addItem($item, $qty = 1) {

        $item = $this->createItem($item, $this->cart_id);

        $product = $this->getProduct($item);

        // 查看是否有老商品
        $item_id = $this->itemKey($item);
        $old_item = $this->items[$item_id];

        $product->qty = $product->qty + $old_item->qty;

        // 验证商品
        $this->load->model('validator_v2_model');
        $error = $this->validator_v2_model->check($product, $this);

        if($error !== false) {
            $this->addError($error);
            return $this;
        }

        $error = $this->validator_v2_model->checkMutex($product, $this);

        if($error !== false) {
            $this->addError($error);
            return $this;
        }

        // 增加数量
        if($old_item)
            $this->increaseItem($item_id, $qty);
        // 插入商品
        else
            $this->insertItem($item);

        return $this;

        // $this->db->_error_message();
        // $this->db->affected_rows() > 0
    }

    function removeItem($item_id) {

        unset($this->items[$item_id]);
        $ret = $this->redis->set("cart_v2:{$this->cart_id}", json_encode($this->items));

        return $this;

    }

    function removeItems() {

        $ret = $this->redis->set("cart_v2:{$this->cart_id}", json_encode([]));

        return $this;

    }

    function insertItem($item) {

        $this->items[$this->itemKey($item)] = $item;

        $ret = $this->redis->set("cart_v2:{$this->cart_id}", json_encode($this->items));

        return $this;

    }

    function increaseItem($item_id, $qty = 1) {

        $item = $this->items[$item_id];
        $item->qty += $qty;

        $ret = $this->redis->set("cart_v2:{$this->cart_id}", json_encode($this->items));

        return $this;

    }

    function decreaseItem($item_id, $qty = 1) {
        $item = $this->items[$item_id];
        if( $item->qty > 1 )
            $item->qty -= $qty;

        $ret = $this->redis->set("cart_v2:{$this->cart_id}", json_encode($this->items));

        return $this;

    }

    function selectItem($item_id) {

        $item = $this->items[$item_id];
        $item->selected = 1;

        $ret = $this->redis->set("cart_v2:{$this->cart_id}", json_encode($this->items));

        return $this;

    }

    function unselectItem($item_id) {

        $item = $this->items[$item_id];
        $item->selected = 0;

        $ret = $this->redis->set("cart_v2:{$this->cart_id}", json_encode($this->items));

        return $this;

    }

    function getProducts() {

        $this->products = [];

        foreach( $this->items as $item ) {
            $product = $this->getProduct($item);
            array_push($this->products, $product);
        }

        return $this;

    }

    // 获取商品详情
    function getProduct($item) {

        $store_product = $this->db->select('*')
            ->from('ttgy_b2o_store_product')
            ->where('product_id', $item->pid)
            ->where('store_id', $item->sid)
            ->limit(1)->get()->row();

        // free 企业团购商品(不用判断上下架) DEPRECATED
        // lack 商品缺货 DEPRECATED
        // use_store 是否启用库存 DEPRECATED
        // is_real_stock 是否启用实时库存 DEPRECATED
        $product = json_decode($this->redis->get("cart_v2:product:{$item->pid}"));
        if(!$product) {
            $product = $this->db
                // ->select('id, product_name, photo, cart_tag, is_pc_online, is_app_online, is_wap_online')
                ->select('*')
                ->from('product')
                ->where('id', $item->pid)
                ->limit(1)->get()->row();

            // 获取产品模板图片 TODO
            if ($product->template_id) {
                $this->load->model('b2o_product_template_image_model');
                $templateImages = $this->b2o_product_template_image_model->getTemplateImage($product->template_id, 'main');
                if (isset($templateImages['main'])) {
                    $product->photo = $templateImages['main']['image'];
                    $product->has_webp = $templateImages['main']['has_webp'];
                }
            }

            $this->redis->setex("cart_v2:product:{$item->pid}", 60, json_encode($product));
        }

        $sku = $this->db->select()
            ->from('product_price')
            ->where('product_id', $item->pid)
            ->limit(1)->get()->row();

        $template = $this->db->select()
            ->from('b2o_product_template')
            ->where('id', $product->template_id)
            ->limit(1)->get()->row();

        $delivery_template = $this->db->select()
            ->from('b2o_delivery_tpl')
            ->where('tpl_id', $store_product->delivery_template_id)
            ->limit(1)->get()->row();

        $this->load->model('gsend_model');
        $gsend = $this->gsend_model->dump(['id'=>$item->gift_send_id]);

        $full_product = new StdClass();

        // head
        $full_product->name             = $product->product_name;
        $full_product->item_id          = $this->itemKey($item);
        $full_product->product_id       = $item->pid;
        $full_product->product_no       = $sku->product_no;
        $full_product->sku_id           = $sku->id;
        $full_product->store_id         = $item->sid;

        // product
        // $full_product->photo              = constant(CDN_URL.rand(1, 9)).$product->photo;
        // $full_product->photo              = PIC_URL.$product->photo;
        // $full_product->photo              = 'http://stagingrbacdev.fruitday.com/'.$product->photo; //@TODO 上线后去除
        $full_product->photo              = constant(CDN_URL.($item->pid%9+1)).$product->photo;

        $full_product->has_webp           = $product->has_webp; // 是否启用webp
        $full_product->group_limit        = $product->group_limit; // 是否可以单独购买
        $full_product->pay_discount_limit = $product->pay_discount_limit; // 是否限制不参加支付折扣活动

        if( $now >= strtotime($store_product->promotion_tag_start) && $now <= strtotime($store_product->promotion_tag_end) )
            $full_product->cart_tag = $store_product->promotion_tag; // 新品，买一送一

        // sku
        $full_product->weight         = $sku->weight;
        $full_product->volume         = $sku->volume;
        $full_product->unit           = $sku->unit;

        // store_product
        $full_product->price            = in_array($item->type, ['gift','user_gift']) ? number_format(0, 2) : $store_product->price;

        $full_product->is_pc_online     = $store_product->is_pc_online;
        $full_product->is_app_online    = $store_product->is_app_online;
        $full_product->is_wap_online    = $store_product->is_wap_online;
        $full_product->is_store_sell    = $store_product->is_store_sell; // 是否在有小门店的时候显示
        $full_product->qty_limit        = $store_product->qty_limit; // 购物车最大添加数量
        $full_product->free_post        = $store_product->is_free_post; // 包邮
        $full_product->iscard           = $store_product->iscard; // 是否包含券卡
        $full_product->card_limit       = $store_product->card_limit; // 不能使用优惠券
        $full_product->jf_limit         = $store_product->jf_limit; // 不能使用积分
        $full_product->first_limit      = $store_product->first_limit; // 新客专享(首单限制)
        $full_product->active_limit     = $store_product->active_limit; // 不参加营销活动
        $full_product->send_region_type = $store_product->send_region_type; // 最大配送范围

        // exchange
        // 按加价金额显示商品价格
        if($item->type == 'exchange' && $item->pmt_id) {
            $this->load->model('promotion_v2_model');
            $strategy = $this->promotion_v2_model->getOneStrategy($item->pmt_id);
            $full_product->price = $strategy->solution_add_money;
        }

        $full_product->amount         = bcmul($full_product->price, $item->qty, 2); // 单品价格小计

        // template
        $full_product->class_id       = $template->class_id;

        // delivery_template
        $full_product->isTodayDeliver = $delivery_template->type == 1 ? 1 : 0;

        // userGift
        if($item->type == 'user_gift')
            $full_product->order_money_limit = $gsend['order_money_limit'];



        // item
        $full_product->qty            = $item->qty;
        $full_product->selected       = $item->selected == 1;
        $full_product->valid          = $item->valid;
        $full_product->type           = $item->type ? $item->type : 'normal';

        if($item->pmt_id)
            $full_product->pmt_id = $item->pmt_id;
        if($item->gift_send_id)
            $full_product->gift_send_id = $item->gift_send_id;
        if($item->gift_active_type)
            $full_product->gift_active_type = $item->gift_active_type;
        if($item->user_gift_id)
            $full_product->user_gift_id = $item->user_gift_id;

        // promotion
        $full_product->percentage = 0; // 价格占满足优惠的金额的百分比
        $full_product->discount   = 0; // 优惠金额(小计)

        // print_r($store_product->price);echo "<pre>";
        // echo json_encode($item);die;

        return $full_product;
    }

    // 凑单列表
    function moreProducts($stores, $products = null, $source, $outofmoney) {

        if($products)
            $order_products = $this->db
                ->select('product_id as pid, store_id as sid')
                ->from('ttgy_b2o_store_product')
                ->where('price >=', $outofmoney)
                ->where("is_{$source}_online", 1)
                ->where('is_hide', 0)
                ->where_in('store_id', $stores)
                ->where_in('product_id', $products)
                ->order_by('price asc')
                ->get()->result();
        else
            $order_products = $this->db
                ->select('product_id as pid, store_id as sid')
                ->from('ttgy_b2o_store_product')
                ->where('price >=', $outofmoney)
                ->where("is_{$source}_online", 1)
                ->where('is_hide', 0)
                ->where_in('store_id', $stores)
                ->order_by('price asc')
                ->limit(10)->get()->result();

        // print_r($order_products);die;

        $products = [];

        foreach($order_products as $order_product) {
            $products[] = $this->getProduct($order_product);
        }

        return $products;

    }

    // cart->products:
    // update selected
    // add valid
    function validate() {

        $this->load->model('validator_v2_model');

        foreach( $this->products as &$product ) {

            $product->valid = true;

            $error = $this->validator_v2_model->check($product, $this);

            // 如果返回错误
            if($error !== false) {
                $product->valid = false;
                $product->selected = false;
                $this->addError($error);
            }

        }

        return $this;

    }

    // implment promotions
    // add discounted_price to cart->products
    function promo() {
        $this->load->model('promotion_v2_model');

        // 可以参加优惠活动的商品列表
        $products = [];

        foreach( $this->products as $product ) {

            // 不参加促销活动
            if( $product->active_limit )
                continue;

            // 不是正常商品
            if( $product->type != 'normal' )
                continue;

            // 未勾选
            if( !$product->selected )
                continue;

            // 已失效
            if( !$product->valid )
                continue;

            $products[] = $product;
        }

        // 执行优惠
        $promotions = $this->promotion_v2_model
            ->loadStrategies($this->stores, $this->user->user_rank, $this->source, $this->version)
            ->implementStrategies($products, $this);

        // echo json_encode($product_ids);die;

        // 验证换购商品
        $this->load->model('validator_v2_model');

        foreach($this->products as &$product) {

            $error = $this->validator_v2_model->checkExchange($product, $this->promotions);
            // 如果返回错误
            if($error !== false) {
                $product->valid = false;
                $product->selected = false;
                $this->addError($error);
            }

        }

        return $this;
    }

    // 会员赠品
    function userGift() {

        $this->load->model('user_gifts_model');

        // 会员赠品
        $user_gifts = $this->user_gifts_model->get_valid_gifts($this->user->id, 106092, 0);

        // 获取充值赠品
        $trade_gifts = $this->user_gifts_model->get_trade_gifts($this->user->id, 106092, 1);

        if(!empty($trade_gifts))
            $user_gifts = array_merge($user_gifts, $trade_gifts);

        // print_r($user_gifts);die;

        // 赠品已经领取过的话不要再提醒了
        foreach($user_gifts as $key=>$user_gift) {
            if( $this->hasGift($user_gift['product_id']) )
                unset($user_gifts[$key]);
        }

        // 没有未领取的赠品不要提醒
        if(!$user_gifts)
            return $this;

        $alert       = new StdClass();
        $alert->name = '会员赠品提醒';
        $alert->msg  = '您还有赠品没有领取哦，快去看看吧';
        $alert->type = 'user_gift';
        $alert->tag  = '赠';
        $alert->url  = true;

        $this->alerts[] = $alert;

        return $this;
    }

    private function hasGift($product_id) {

        foreach ($this->items as $item) {
            if($item->type == 'user_gift')
                if($product_id == $item->pid)
                    return true;
        }

    }

    function total() {

        $total = new StdClass();

        $total->price            = 0;
        $total->discount         = 0;
        $total->discounted_price = 0;
        $total->weight           = 0;
        $total->selected         = 0;

        foreach($this->products as $product) {

            // 失效商品不计入总价不计入总重
            if(!$product->valid)
                continue;
            // 未勾选商品不计入总价不计入总重
            if(!$product->selected)
                continue;

            // 总数(勾选)
            $total->selected = bcadd($total->selected, $product->qty);

            // 赠品不计入总价不计入总重
            if($product->type == 'gift')
                continue;
            // 会员赠品不计入总价不计入总重
            if($product->type == 'user_gift')
                continue;

            // 总价
            $total->price            = bcadd($total->price, $product->amount, 2);
            $total->discount         = bcadd($total->discount, $product->discount, 2);
            $total->discounted_price = bcsub($total->price, $total->discount, 2);

            if($total->discounted_price < 0)
                $total->discounted_price = 0.00;

            // 总重
            $sub_total_weight        = bcmul($product->weight, $product->qty, 2);
            $total->weight           = bcadd($total->weight, $sub_total_weight, 2);

        }

        $this->total = $total;

        return $this;

    }

    // 统计所有商品 包括失效和未勾选的 不包括赠品
    function count() {

        foreach( $this->items as $item ) {
            $this->count = $this->count + $item->qty;
        }

        return $this;

    }

    // 结算
    // 返回有效且勾选的商品
    function checkOut() {

        // 过滤失效和未勾选商品
        $products = [];

        foreach($this->products as $product) {
            // 失效商品
            if(!$product->valid)
                continue;
            // 未勾选商品
            if(!$product->selected)
                continue;
            $products[] = $product;
        }

        return [
            'items'      => $this->items,
            'products'   => $products,
            'promotions' => $this->promotions,
            'total'      => (array)$this->total,
        ];

    }

}
