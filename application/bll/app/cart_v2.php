<?php
namespace bll\app;

class Cart_v2 {

    /**
    * @apiParam {String} [source=app] 客户端(Client)
    * @apiParam {Number} [timestamp] 时间戳
    * @apiParam {String} [version=4.3.0] 版本号
    * @apiParam {String} [channel=fruit_dev] 渠道？
    * @apiParam {String} [platform=ANDROID] 客户端操作系统
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 客户端设备号
    * @apiParam {String} [system_version=6.0.1] 客户端系统版本号
    * @apiParam {String} [device_model=Xiaomi_MI MAX] 客户端设备型号
    */
    public function __construct($params = []) {

        // $this->repost();
        // $ci = &get_instance();
        // $ci->load->model('promotion_v2_model');
        // $promotion = $ci->promotion_v2_model->getSingleStrategy(17563, 1);
        // print_r($promotion);die;

        // 调试
        // $ret = [
        //     'code'   =>300,
        //     'msg'    =>$params['store_id_list'],
        // ];
        // echo json_encode($ret);exit;

        $ci = &get_instance();
        $ci->load->model('cart_v2_model');
        $this->cart_v2_model = $ci->cart_v2_model;

        // 获取user
        if($params['connect_id']) {
            $this->user = $this->cart_v2_model->getUser($params['connect_id']);

            if( !$this->user ) {
                $ret = [
                    'code'=>300,
                    'msg'=>"登录超时，请重新登录",
                ];
                echo json_encode($ret);
                exit;
            }

        }

        // 获取cart_id
        if($this->user)
            $this->cart_id = $this->user->id;
        else
            $this->cart_id = $params['device_id'];

        if( !$this->cart_id ) {
            $ret = [
                'code'   =>300,
                'msg'    =>"购物车加载失败",
                'reason' =>"缺少cart_id",
            ];
            echo json_encode($ret);
            exit;
        }


        // 获取store_id_list
        if($params['store_id_list'])
            $this->store_id_list = explode(',', $params['store_id_list']);

        // 获取source
        if($params['source'])
            $this->source = $params['source'];

        // 获取version
        if($params['version'])
            $this->version = $params['version'];

        // 获取tms_region_type
        if($params['tms_region_type'])
            $this->tms_region_type = $params['tms_region_type'];
        else
            $this->tms_region_type = 1;
    }

    // 过滤商品详情
    private function filter($products, $params) {
        $simple_products = [];

        foreach($products as $product) {
            $simple_product                 = new \StdClass();
            $simple_product->name           = $product->name;
            $simple_product->type           = $product->type;
            $simple_product->price          = $product->price;
            $simple_product->qty            = $product->qty;
            $simple_product->selected       = $product->selected;
            $simple_product->valid          = $product->valid;

            $simple_product->photo          = $product->photo;

            if( $params['platform'] != 'IOS' && $product->has_webp)
                $simple_product->photo = str_replace('.jpg', '.webp', $simple_product->photo);

            $simple_product->item_id        = $product->item_id;
            $simple_product->product_id     = $product->product_id;
            $simple_product->product_no     = $product->product_no;
            $simple_product->sku_id         = $product->sku_id;
            $simple_product->store_id       = $product->store_id;


            $simple_product->cart_tag       = $product->cart_tag;
            $simple_product->class_id       = $product->class_id;
            $simple_product->weight         = $product->weight;
            $simple_product->volume         = $product->volume;
            $simple_product->unit           = $product->unit;
            $simple_product->qty_limit      = $product->qty_limit;
            $simple_product->isTodayDeliver = $product->isTodayDeliver;

            $simple_products[]              = $simple_product;
        }

        return array_reverse($simple_products); // 购物车倒序排列
    }

    private function repost() {

        if( php_uname('n') != 'ip-10-0-1-236' )
            return;

        $params = array_merge($_GET,$_POST);
        $data = http_build_query($params);
        $opts = array(
            'http'=>array(
                'method'=>"POST",
                'header'=>"Content-type: application/x-www-form-urlencoded\r\n".
                "Content-length:".strlen($data)."\r\n" .
                "Cookie: foo=bar\r\n" .
                "\r\n",
                'content' => $data,
            )
        );
        $cxContext = stream_context_create($opts);
        $result = file_get_contents('http://api.guantest.fruitday.com/api/test', false, $cxContext);

        print_r( $result );die;
    }

    /**
    * @api {post} / 获取购物车
    * @apiDescription 获取购物车里的所有item
    * @apiGroup cart
    * @apiName get
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiParam {String} store_id_list 门店列表，多个用逗号分隔
    *
    * @apiSampleRequest /api/test?service=cart_v2.get&source=app
    */
    // MIGRATED
    public function get($params) {

        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        $cart->getProducts()->validate()->promo()->userGift()->total()->count();

        return [
            'code'=>200,
            'msg'=>"购物车获取成功",
            'cart'=>[
                // 'items'      => $cart->items,
                'tag'        => md5(json_encode($cart->items)),
                // 'products'   => array_reverse($cart->products),
                'products'   => $this->filter($cart->products, $params),
                'promotions' => $cart->promotions,
                'errors'     => $cart->errors,
                'alerts'     => $cart->alerts,
                'total'      => $cart->total,
                'count'      => $cart->count,
            ],

        ];

    }
    //


    // MIGRATED
    public function count($params) {

        $cart = $this->cart_v2_model->init($this->cart_id);
        $cart->count();

        return [
            'code'  => 200,
            'msg'   => "购物车统计成功",
            'count' => $cart->count,
        ];

    }
    //

    /**
    * @api {post} / 添加商品
    * @apiDescription 添加一个/多个item
    * @apiGroup cart
    * @apiName add
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiParam {String} store_id_list 门店列表，多个用逗号分隔
    *
    * @apiParam {String} pid=13922 商品id(product_id)，多个用逗号分隔
    * @apiParam {String} sid=1 商店id，多个用逗号分隔
    * @apiParam {String} type=normal 商品类型(normal/gift/exchange/user_gift)，多个用逗号分隔
    * @apiParam {String} [gift_send_id] 加入会员赠品时必填
    * @apiParam {String} [gift_active_type] 加入会员赠品时必填
    * @apiParam {String} [user_gift_id] 加入会员赠品时必填
    * @apiParam {String} [pmt_id] 加入换购商品时必填
    *
    * @apiSampleRequest /api/test?service=cart_v2.add&source=app
    */
    // MIGRATED
    public function add($params) {

        // 验证字段
        if($params['type'] == 'exchange')
            if(!$params['pmt_id'])
                return [
                    'code'   => 300,
                    'msg'    => "缺少相关换购活动信息",
                    'reason' => "缺少pmt_id参数",
                ];

        if($params['type'] == 'user_gift')
            if(!$params['gift_send_id'] || !$params['gift_active_type'] || !$params['user_gift_id'])
                return [
                    'code'   => 300,
                    'msg'    => "缺少相关会员赠品信息",
                    'reason' => "缺少gift_send_id, gift_active_type, user_gift_id参数",
                ];


        // 美国有籽红提 13922 16939
        // 智利牛油果 13863 16905
        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        $items = [];
        $pids              = explode(',', $params['pid']);
        $sids              = explode(',', $params['sid']);
        $types             = explode(',', $params['type']);
        $pmt_ids           = explode(',', $params['pmt_id']);
        $gift_send_ids     = explode(',', $params['gift_send_id']);
        $gift_active_types = explode(',', $params['gift_active_type']);
        $user_gift_ids     = explode(',', $params['user_gift_id']);

        foreach( $pids as $key=>$pid ) {
            $items[] = [
                'pid'              => $pids[$key],
                'sid'              => $sids[$key],
                'type'             => $types[$key],
                'pmt_id'           => $pmt_ids[$key],
                'gift_send_id'     => $gift_send_ids[$key],
                'gift_active_type' => $gift_active_types[$key],
                'user_gift_id'     => $user_gift_ids[$key],
            ];
        }

        foreach($items as $item) {
            $cart->addItem($item);
        }

        if( count($cart->errors) > 0 )
            return (array)$cart->errors[0];

        $cart->getProducts()->validate()->promo()->userGift()->total()->count();


        return [
            'code' => 200,
            'msg'  => "购物车添加成功",
            'cart'=>[
                'tag'        => md5(json_encode($cart->items)),
                'products'   => $this->filter($cart->products),
                'promotions' => $cart->promotions,
                'errors'     => $cart->errors,
                'alerts'     => $cart->alerts,
                'total'      => $cart->total,
                'count'      => $cart->count,
            ],
        ];

    }
    //

    /**
    * @api {post} / 删除商品
    * @apiDescription 删除一某个/多个item
    * @apiGroup cart
    * @apiName remove
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiParam {String} item_id 需要删除的商品，多个用逗号分隔
    *
    * @apiSampleRequest /api/test?service=cart_v2.remove&source=app
    */
    // MIGRATED
    public function remove($params) {

        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        $item_array = explode(',', $params['item_id']);

        foreach( $item_array as $item_id ) {
            $cart->removeItem($item_id);
        }

        $cart->getProducts()->validate()->promo()->userGift()->total()->count();

        return [
            'code' => 200,
            'msg'  => "购物车删除成功",
            'cart'=>[
                'tag'        => md5(json_encode($cart->items)),
                'products'   => $this->filter($cart->products),
                'promotions' => $cart->promotions,
                'errors'     => $cart->errors,
                'alerts'     => $cart->alerts,
                'total'      => $cart->total,
                'count'      => $cart->count,
            ],
        ];

    }
    //

    /**
    * @api {post} / 清空购物车
    * @apiDescription 清空购物车(删除购物车所有items)
    * @apiGroup cart
    * @apiName clear
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiSampleRequest /api/test?service=cart_v2.clear&source=app
    */
    // MIGRATED
    public function clear($params) {

        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        $cart->removeItems();
        $cart->getItems();

        $cart->getProducts()->validate()->promo()->userGift()->total()->count();

        return [
            'code' => 200,
            'msg'  => "购物车清空成功",
            'cart'=>[
                'tag'        => md5(json_encode($cart->items)),
                'products'   => $this->filter($cart->products),
                'promotions' => $cart->promotions,
                'errors'     => $cart->errors,
                'alerts'     => $cart->alerts,
                'total'      => $cart->total,
                'count'      => $cart->count,
            ],
        ];

    }
    //

    /**
    * @api {post} / 增加商品数量
    * @apiDescription 增加某个item的数量
    * @apiGroup cart
    * @apiName increase
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiParam {Number} item_id Item ID
    *
    * @apiSampleRequest /api/test?service=cart_v2.increase&source=app
    */
    // MIGRATED
    public function increase($params) {

        $item_id = $params['item_id'];

        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        // 载入商品
        $item = $cart->items[$item_id];
        $product = $cart->getProduct($item);
        $product->qty = $product->qty + 1;

        // 验证商品
        $ci = &get_instance();
        $ci->load->model('validator_v2_model');
        $error = $ci->validator_v2_model->check($product, $cart);

        if($error)
            return (array)$error;

        $cart->increaseItem($item_id);

        $cart->getProducts()->validate()->promo()->userGift()->total()->count();

        return [
            'code' => 200,
            'msg'  => "购物车增加数量成功",
            'cart'=>[
                'tag'        => md5(json_encode($cart->items)),
                'products'   => $this->filter($cart->products),
                'promotions' => $cart->promotions,
                'errors'     => $cart->errors,
                'alerts'     => $cart->alerts,
                'total'      => $cart->total,
                'count'      => $cart->count,
            ],
        ];

    }
    //

    /**
    * @api {post} / 减少商品数量
    * @apiDescription 减少某个item的数量
    * @apiGroup cart
    * @apiName decrease
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiParam {Number} item_id Item ID
    *
    * @apiSampleRequest /api/test?service=cart_v2.decrease&source=app
    */
    // MIGRATED
    public function decrease($params) {
        $item_id = $params['item_id'];

        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        $cart->decreaseItem($item_id);

        $cart->getProducts()->validate()->promo()->userGift()->total()->count();

        return [
            'code' => 200,
            'msg'  => "购物车减少数量成功",
            'cart'=>[
                'tag'        => md5(json_encode($cart->items)),
                'products'   => $this->filter($cart->products),
                'promotions' => $cart->promotions,
                'errors'     => $cart->errors,
                'alerts'     => $cart->alerts,
                'total'      => $cart->total,
                'count'      => $cart->count,
            ],
        ];

    }
    //

    /**
    * @api {post} / 选中某个商品
    * @apiDescription 勾选某个item
    * @apiGroup cart
    * @apiName select
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiParam {Number} item_id Item ID
    *
    * @apiSampleRequest /api/test?service=cart_v2.select&source=app
    */
    // MIGRATED
    public function select($params) {

        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        $cart->selectItem($params['item_id']);

        $cart->getProducts()->validate()->promo()->userGift()->total()->count();

        return [
            'code' => 200,
            'msg'  => "购物车勾选成功",
            'cart'=>[
                'tag'        => md5(json_encode($cart->items)),
                'products'   => $this->filter($cart->products),
                'promotions' => $cart->promotions,
                'errors'     => $cart->errors,
                'alerts'     => $cart->alerts,
                'total'      => $cart->total,
                'count'      => $cart->count,
            ],
        ];

    }
    //

    /**
    * @api {post} / 反选某个商品
    * @apiDescription 反选某个item
    * @apiGroup cart
    * @apiName unselect
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiParam {Number} item_id Item ID
    *
    * @apiSampleRequest /api/test?service=cart_v2.unselect&source=app
    */
    // MIGRATED
    public function unselect($params) {

        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        $cart->unselectItem($params['item_id']);

        $cart->getProducts()->validate()->promo()->userGift()->total()->count();

        return [
            'code' => 200,
            'msg'  => "购物车反选成功",
            'cart'=>[
                'tag'        => md5(json_encode($cart->items)),
                'products'   => $this->filter($cart->products),
                'promotions' => $cart->promotions,
                'errors'     => $cart->errors,
                'alerts'     => $cart->alerts,
                'total'      => $cart->total,
                'count'      => $cart->count,
            ],
        ];

    }
    //

    /**
    * @api {post} / 勾选所有商品
    * @apiDescription 勾选所有item
    * @apiGroup cart
    * @apiName selectall
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiSampleRequest /api/test?service=cart_v2.selectall&source=app
    */
    // MIGRATED
    public function selectall($params) {

        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        foreach( $cart->items as $key=>$item ) {
            $cart->selectItem($key);
        }

        $cart->getItems();

        $cart->getProducts()->validate()->promo()->userGift()->total()->count();

        return [
            'code' => 200,
            'msg'  => "购物车全选成功",
            'cart'=>[
                'tag'        => md5(json_encode($cart->items)),
                'products'   => $this->filter($cart->products),
                'promotions' => $cart->promotions,
                'errors'     => $cart->errors,
                'alerts'     => $cart->alerts,
                'total'      => $cart->total,
                'count'      => $cart->count,
            ],
        ];

    }
    //

    /**
    * @api {post} / 反选所有商品
    * @apiDescription 反选所有item
    * @apiGroup cart
    * @apiName unselectall
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiSampleRequest /api/test?service=cart_v2.unselectall&source=app
    */
    // MIGRATED
    public function unselectall($params) {

        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        foreach( $cart->items as $key=>$item ) {
            $cart->unselectItem($key);
        }

        $cart->getItems();

        $cart->getProducts()->validate()->promo()->userGift()->total()->count();

        return [
            'code' => 200,
            'msg'  => "购物车全反选成功",
            'cart'=>[
                'tag'        => md5(json_encode($cart->items)),
                'products'   => $this->filter($cart->products),
                'promotions' => $cart->promotions,
                'errors'     => $cart->errors,
                'alerts'     => $cart->alerts,
                'total'      => $cart->total,
                'count'      => $cart->count,
            ],
        ];

    }
    //

    /**
    * @api {post} / 验证商品
    * @apiDescription 验证item
    * @apiGroup cart
    * @apiName validate
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiParam {String} store_id_list 门店列表，多个用逗号分隔
    *
    * @apiParam {String} item_id 需要提示失效的商品，多个用逗号分隔
    *
    * @apiSampleRequest /api/test?service=cart_v2.validate&source=app
    */
    public function validate($params) {

        if(!$params['item_id'])
            return [
                'code'   => 300,
                'msg'    => '缺少需要提示的商品参数',
                'reason' => '缺少item_id参数',
            ];

        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        $cart->getProducts()->validate()->promo()->userGift()->total()->count();

        // 需要报错的商品
        $items = explode(',', $params['item_id']);
        $error_array = [];

        // 聚合报错信息
        foreach($cart->errors as $error) {
            if( in_array($error->item_id, $items) )
                $error_array[] = "{$error->product_name}, {$error->msg}";
        }

        $error_string = implode($error_array, "\n");

        if($error_string)
            return [
                'code'   =>304,
                'msg'    =>$error_string,
                'action' => '返回购物车查看',
                'cart'   =>[
                    'tag'        => md5(json_encode($cart->items)),
                    'products'   => $this->filter($cart->products),
                    'promotions' => $cart->promotions,
                    'errors'     => $cart->errors,
                    'alerts'     => $cart->alerts,
                    'total'      => $cart->total,
                    'count'      => $cart->count,
                ]
            ];
        else
            return [
                'code'   =>200,
                'msg'    =>"校验商品成功",
                'action' => '跳转到结算页',
                'cart'   =>[
                    'tag'        => md5(json_encode($cart->items)),
                    'products'   => $this->filter($cart->products),
                    'promotions' => $cart->promotions,
                    'errors'     => $cart->errors,
                    'alerts'     => $cart->alerts,
                    'total'      => $cart->total,
                    'count'      => $cart->count,
                ]
            ];

    }
    //

    /**
    * @api {post} / 比较购物车
    * @apiDescription 检查购物车items是否和本地的不同
    * @apiGroup cart
    * @apiName compare
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiParam {String} tag 购物车tag字符串
    *
    * @apiSampleRequest /api/test?service=cart_v2.compare&source=app
    */
    // DEPRECATED
    public function compare($params) {

        if(!$params['tag'])
            return [
                'code'   => 300,
                'msg'    => '购物车商品比较失败',
                'reason' => '缺少tag参数',
            ];

        $cart = $this->cart_v2_model->init($this->cart_id);

        if( $params['tag'] == md5(json_encode($cart->items)) )
            return [
                'code'   => 200,
                'msg'    => '购物车商品没有变化',
                'action' => '本地不需要刷新购物车信息',
            ];
        else
            return [
                'code'   => 300,
                'msg'    => '购物车商品有变化',
                'action' => '本地需要刷新购物车信息',
            ];

    }
    //

    /**
    * @api {post} / 合并购物车
    * @apiDescription 合并临时购物车(基于device_id)和用户购物车(基于connect_id/user_id)，临时购物车会被清空
    * @apiGroup cart
    * @apiName merge
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiSampleRequest /api/test?service=cart_v2.merge&source=app
    */
    // MIGRATED
    public function merge($params) {

        $tmp_cart_id = $params['device_id'];
        $user_cart_id = $this->cart_v2_model->getUser($params['connect_id'])->id;

        $tmp_cart = $this->cart_v2_model->init($tmp_cart_id);
        $user_cart = $this->cart_v2_model->init($user_cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);

        // 合并
        foreach( $tmp_cart->items as $item ) {
            $user_cart->addItem($item, $item->qty);
        }

        $tmp_cart->removeItems();
        $user_cart->getItems();
        $user_cart->getProducts()->validate()->promo()->userGift()->total()->count();

        return [
            'code' => 200,
            'msg'  => "购物车合并成功",
            'cart'=>[
                'tag'        => md5(json_encode($cart->items)),
                'products'   => $user_cart->products,
                'promotions' => $user_cart->promotions,
                // 'errors'     => $user_cart->errors,
                'alerts'     => $user_cart->alerts,
                'total'      => $user_cart->total,
                'count'      => $user_cart->count,
            ],
        ];
    }
    //

    /**
    * @api {post} / 换购列表
    * @apiDescription 换购列表
    * @apiGroup cart
    * @apiName exchange
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    *
    * @apiParam {String} store_id_list 门店列表，多个用逗号分隔
    *
    * @apiParam {Number} pmt_id 优惠策略id
    * @apiParam {Number} store_id 商店id
    *
    * @apiSampleRequest /api/test?service=cart_v2.exchange&source=app
    */
    // MIGRATED
    public function exchange($params) {

        $pmt_id   = $params['pmt_id'];
        $store_id = $params['store_id'];

        if(!$store_id)
           return [
               'code' => 300,
               'msg'  => "缺少门店参数",
           ];

       if(!$pmt_id)
          return [
              'code' => 300,
              'msg'  => "缺少优惠策略参数",
          ];

        $cart = $this->cart_v2_model->init($this->cart_id, $this->store_id_list, $this->user, $this->source, $this->version, $this->tms_region_type);
        $cart->getProducts()->validate()->promo()->userGift()->total()->count();

        $valid = false;

        foreach($cart->promotions as $strategy) {
           if($strategy->id == $pmt_id)
                $valid = true;
        }

        if(!$valid)
           return [
               'code' => 300,
               'msg'  => "没有满足换购条件",
           ];

        // 获取优惠
        $ci = &get_instance();
        $ci->load->model('promotion_v2_model');
        $strategy = $ci->promotion_v2_model->getOneStrategy($pmt_id);

        if(!$strategy)
           return [
               'code' => 300,
               'msg'  => "换购活动已经结束",
           ];

        $products = [];

        foreach ($strategy->solution_products as $product_id) {
            $item = new \StdClass();
            $item->pid      = $product_id;
            $item->sid      = $store_id;
            $item->type     = 'exchange';
            $item->qty      = 1;

            $product        = $cart->getProduct($item);
            $product->price = $strategy->solution_add_money; // 按加价金额显示商品价格
            $products[]     = $product;
        }

        return [
           'code'      => 200,
           'msg'       => "换购列表加载成功",
           'pmt_id'    => $strategy->id,
           'add_money' => $strategy->solution_add_money,
           'products'  => $products,
        ];
    }
    // MIGRATED

    /**
    * @api {post} / 凑单列表
    * @apiDescription 凑单列表
    * @apiGroup cart
    * @apiName addmore
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    * @apiParam {String} store_id_list 商店id列表，多个用逗号分隔
    *
    * @apiParam {Number} pmt_id 优惠策略id
    * @apiParam {Number} outofmoney 还差多少钱
    *
    * @apiSampleRequest /api/test?service=cart_v2.addmore&source=app
    */
    // DEPRECATED
    public function addmore($params) {

        $pmt_id     = $params['pmt_id'];
        $stores     = explode(',', $params['store_id_list']) ;
        $source     = $params['source'];
        $outofmoney = $params['outofmoney'];

        // 获取优惠
        $ci = &get_instance();
        $ci->load->model('promotion_v2_model');
        $promotion = $ci->promotion_v2_model->getOneStrategy($pmt_id);

        $title = $promotion->name;

        $products = [];

        if($promotion->range_type == 'all')
            // 全场凑单
            $products = $this->cart_v2_model->moreProducts($stores, null, $source, $outofmoney);
        else
            // 商品白名单凑单
            $products = $this->cart_v2_model->moreProducts($stores, $promotion->range_products, $source, $outofmoney);

        return [
           'code'       => 200,
           'msg'        => "凑单列表加载成功",
           'title'      => $title,
           'outofmoney' => $outofmoney,
           'products'   => $products,
        ];

    }
    //

    /**
    * @api {post} / 收藏商品
    * @apiDescription 收藏单个/多个商品
    * @apiGroup cart
    * @apiName mark
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    * @apiParam {String} store_id_list 商店id列表，多个用逗号分隔
    *
    * @apiParam {Number} product_id 优惠策略id，多个用逗号分隔
    *
    * @apiSampleRequest /api/test?service=cart_v2.mark&source=app
    */
    public function mark($params) {

        $product_ids = explode(',', $params['product_id']);

        $ci = &get_instance();
        $ci->load->model('product_model');

        foreach($product_ids as $product_id) {
            $result = $ci->product_model->select_user_mark('id', ['uid'=>$this->user->id, 'product_id'=>$product_id]);

            $insert_data = [
                'uid'        => $this->user->id,
                'product_id' => $product_id,
                'mark_time'  => date("Y-m-d H:i:s"),
            ];

            if(empty($result))
                $ci->product_model->add_user_mark($insert_data);
        }

        return [
            'code'  => 200,
            'msg'   => "购物车商品关注成功",
        ];

    }
    //

}
