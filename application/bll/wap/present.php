<?php
namespace bll\wap;

// TODO
// 收礼打开验证
// 收礼接口增加地址验证
// 商品后台

// ini_set('display_errors',1);
// error_reporting(E_ERROR);

class Present {

    public function __construct() {
        $this->ci = &get_instance();
        $this->ci->load->model('present_model');
        $this->ci->load->model('order_model');
    }

    /**
    * @api {post} / 送礼商品列表
    * @apiGroup present
    * @apiName items
    *
    * @apiParam {Number} [page=1] 第几页
    * @apiParam {Number} [limit=10] 每页显示几条
    *
    * @apiSampleRequest /api/test?service=present.items&source=wap
    */
    public function items($params) {

        $limit  = $params['limit'] > 0 ? $params['limit'] : 10;
        $offset = ($params['page'] - 1 ) * $limit;

        // $items = [2069, 12269, 12515];
        $items = $this->ci->present_model->getProducts($limit, $offset);

        if(!is_array($items))
            return [
                'code'=>500,
                'msg'=>'获取送礼商品列表失败'
            ];

		$products = [];

        foreach($items as $item) {
            $product = $this->ci->present_model->getProduct($item->id);
            // if($product)
                array_push($products, $product);
        }

        return [
            'code' => 200,
            'msg'  => '获取送礼商品列表成功',
            'data' => $products
        ];


    }

    /**
    * @api {post} / 送礼订单创建
    * @apiGroup present
    * @apiName create
    *
    * @apiParam {Number} pid 商品id
    * @apiParam {String} connect_id 登录状态
    *
    * @apiSampleRequest /api/test?service=present.create&source=wap
    */
    public function create($params) {

        $pid = $params['pid'];
        $uid = $this->ci->present_model->getUserId($params['connect_id']);

        if(!$pid)
            return ['code'=>300, 'msg'=>'没有商品ID'];
        if(!$uid)
            return ['code' => 401,'msg'=>'登录过期，请重新登录'];

        // 获取商品
        $product = $this->ci->present_model->getProduct($pid);
        if(!$product)
            return ['code' => 500,'msg'=>'商品错误或失效'];

        // 创建订单
        $this->ci->db->trans_begin();

        // 插入order
        if( !$order = $this->ci->present_model->createOrder($uid, $product) )
            $this->ci->db->trans_rollback();

        // 插入order product
        if( !$order_product_id = $this->ci->present_model->addOrderProduct($uid, $order['id'], $product) )
            $this->ci->db->trans_rollback();

        // 插入 order address
        if( !$order_address_id = $this->ci->present_model->addOrderAddress($order['id']) )
            $this->ci->db->trans_rollback();

        // 更新order里的address_id
        $this->ci->present_model->updateOrder($order['id'], ['address_id'=>$order_address_id]);

        // check order
        $order = $this->ci->present_model->getOrder($order['order_name']);

        if ( $this->ci->db->trans_status() ) {

            $this->ci->db->trans_commit();
            return [
                'code' => 200,
                'msg'  => '创建订单成功',
                'data' => $order
            ];

        } else {

            $this->ci->db->trans_rollback();
            return [
                'code' => 500,
                'msg'  => '创建订单失败'
            ];

        }

    }

    /**
    * @api {post} / 送礼订单列表
    * @apiGroup present
    * @apiName orders
    *
    * @apiParam {String} connect_id
    * @apiParam {Number} [page=1] 第几页
    * @apiParam {Number} [limit=10] 每页显示几条登录状态
    *
    * @apiSampleRequest /api/test?service=present.orders&source=wap
    */
    public function orders($params) {

        $uid = $this->ci->present_model->getUserId($params['connect_id']);

        if(!$uid)
            return [
                'code' => 401,
                'msg'  => '登录过期，请重新登录'
            ];

        $limit  = $params['limit'] > 0 ? $params['limit'] : 10;
        $offset = ($params['page'] - 1 ) * $limit;

        $orders = $this->ci->present_model->getOrders($uid, $limit, $offset);

        return [
            'code' => 200,
            'msg'  => '送礼订单列表获取成功',
            'data' => $orders
        ];

    }

    /**
    * @api {post} / 送礼订单详细
    * @apiGroup present
    * @apiName order
    *
    * @apiParam {String} order_no 订单号
    * @apiParam {String} connect_id 登录状态
    *
    * @apiSampleRequest /api/test?service=present.order&source=wap
    */
    public function order($params) {

        $order_no = $params['order_no'];

        if(!$order_no)
            return [
                'code' => 300,
                'msg'  => '缺少order_no'
            ];

        $uid  = $this->ci->present_model->getUserId($params['connect_id']);

        if(!$uid)
            return [
                'code' => 401,
                'msg'  => '登录过期，请重新登录'
            ];

        $order = $this->ci->present_model->getOrder($order_no);

        return [
            'code' => 200,
            'msg'  => '送礼订单详细获取成功',
            'data' => $order
        ];

    }

    /**
    * @api {post} / 送礼订单状态
    * @apiGroup present
    * @apiName status
    *
    * @apiParam {String} order_no 订单号
    *
    * @apiSampleRequest /api/test?service=present.status&source=wap
    */
    public function status($params) {

        $order_no = $params['order_no'];

        if(!$order_no)
            return [
                'code' => 300,
                'msg'  => '缺少order_no'
            ];

        $order = $this->ci->present_model->getOrder($order_no);

        $status           = new \StdClass();
        $status->no       = $order->no;
        $status->type     = $order->type;
        $status->status   = $order->status;
        $status->received = $order->received;

        return [
            'code' => 200,
            'msg'  => '送礼订单详细获取成功',
            'data' => $status
        ];

    }

    /**
    * @api {post} / 收礼订单详细
    * @apiGroup present
    * @apiName detail
    *
    * @apiParam {String} order_no 订单号
    *
    * @apiSampleRequest /api/test?service=present.detail&source=wap
    */
    public function detail($params) {

        $order_no = $params['order_no'];

        if(!$order_no)
            return [
                'code' => 300,
                'msg'  => '缺少order_no'
            ];

        $order = $this->ci->present_model->getOrder($order_no);

        $simpleOrder           = new \StdClass();
        $simpleOrder->no       = $order->no;
        $simpleOrder->products = $order->products;

        foreach($simpleOrder->products as &$product) {
            $product = $this->ci->present_model->getProduct($product->id);
        }

        return [
            'code' => 200,
            'msg'  => '收礼订单详细获取成功',
            'data' => $simpleOrder
        ];

    }

    /**
    * @api {post} / 送礼订单领取
    * @apiGroup present
    * @apiName receive
    *
    * @apiParam {String} order_no 订单号
    * @apiParam {String} name='许佳珺' 名字
    * @apiParam {String} send_date='20161125' 配送日期(shtime)
    * @apiParam {String} send_time='0914' 配送时间(stime)
    * @apiParam {Number} mobile=13816954680 手机号
    * @apiParam {String} address='金沙江路753弄12号401室' 地址
    * @apiParam {Number} province=106092 省
    * @apiParam {Number} city=106093 市
    * @apiParam {Number} area=106105 区
    *
    * @apiSampleRequest /api/test?service=present.receive&source=wap
    */
    public function receive($params) {

        $order_no = $params['order_no'];
        if(!$order_no)
            return ['code'=>300,'msg'=>'缺少order_no'];

        $name = $params['name'];
        if(!$name)
            return ['code'=>300,'msg'=>'缺少name'];

        $send_date = $params['send_date'];
        if(!$send_date)
            return ['code'=>300,'msg'=>'缺少send_date'];

        $send_time = $params['send_time'];
        if(!$send_time)
            return ['code'=>300,'msg'=>'缺少send_time'];

        $mobile   = $params['mobile'];
        if(!$mobile)
            return ['code'=>300,'msg'=>'缺少mobile'];

        $address = $params['address'];
        if(!$address)
            return ['code'=>300,'msg'=>'缺少address'];

        $province = $params['province'];
        if(!$province)
            return ['code'=>300,'msg'=>'缺少province'];

        $city = $params['city'];
        if(!$city)
            return ['code'=>300,'msg'=>'缺少city'];

        $area = $params['area'];
        if(!$area)
            return ['code'=>300,'msg'=>'缺少area'];

        // 查询订单
        $order = $this->ci->present_model->getOrder($order_no);

        // 验证订单
        if($order->status == '待付款')
            return [ 'code'=>400, 'msg'=>'订单尚未支付'];
        if($order->status == '已取消')
            return [ 'code'=>400, 'msg'=>'订单已经取消'];
        if($order->status == '交易成功')
            return [ 'code'=>400, 'msg'=>'订单已经领取'];
        if($order->lyg == 9)
            return [ 'code'=>400, 'msg'=>'订单已经领取'];

        $this->ci->db->trans_begin();

        $address = [
            'name'     => $name,
            'mobile'   => $mobile,
            'address'  => $address,
            'province' => $province,
            'city'     => $city,
            'area'     => $area,
        ];

        $ok = $this->ci->order_model->check_addr($address);
        if($ok !== true)
            return $ok;

        if( !$this->ci->present_model->updateOrderAddress($order->id, $address) )
            $this->ci->db->trans_rollback();

        $values = [
            'lyg'    =>9,
            'shtime' => $send_date,
            'stime'  => $send_time
        ];

        if( !$this->ci->present_model->updateOrder($order->id, $values) )
            $this->ci->db->trans_rollback();

        if ( $this->ci->db->trans_status() ) {
            $this->ci->db->trans_commit();
            return [
                'code' => 200,
                'msg'  => '收礼成功',
                'data' => $order->no
            ];
        } else {
            return [
                'code' => 500,
                'msg'  => '收礼失败',
                'data' => $order->no
            ];
        }

    }

}
