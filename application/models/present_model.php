<?php

class Present_model extends CI_model {

    var $debug = false;

    function __construct($debug) {
        if($debug)
            $this->debug = $debug;
    }

    function createOrder($uid, $product) {

        $this->load->model('order_model');

        $score = $this->order_model->get_order_product_score($uid, (array)$product);

    		$order                = [
                'uid'             => $uid, // 用户id
    			'order_name'      => null, // 订单号
                'trade_no'        => null, // 外部交易号
                'billno'          => null, // 冗余字段
                'time'            => date('Y-m-d H:i:s'), // 下单时间
                'pay_time'        => null, // 支付时间
                'update_pay_time' => null, // 冗余字段
                'pay_name'        => '支付宝', // 支付方式名称
                'pay_parent_id'   => 1, // 支付方式(1:支付宝)
                'pay_id'          => 0,
                'shtime'          => date('Ymd'), // 收货日期
                'stime'           => null, // 收货时间(0914)
                'send_date'       => null, // 发货时间
                'msg'             => '', // 客户留言
                'money'           => $product->price, // 订单金额
                'pmoney'          => $product->price, // 支付金额
                'goods_money'     => $product->price, // 商品金额
                'pay_status'      => 0, // 是否支付
    		    'score'           => $score, // 订单积分
    		    'address_id'      => null, // 用户地址id
                'order_status'    => 1,
                'method_money'    => 0, // 运费
                'channel'         => 6, // 渠道
    			'order_type'      => 9, // 9送礼订单
    		    'version'         => 1, // 版本
    		];


        $order_id = $this->order_model->generate_order('order', $order);

        if( !$order_id )
            return;

        $order = $this->order_model->dump(['id' => $order_id]);
        return $order;

    }

    function addOrderProduct($uid, $order_id, $product) {

        $score = $this->order_model->get_order_product_score($uid, (array)$product);

        $order_product_data = [
            'order_id'     => $order_id,
            'product_name' => addslashes($product->name),
            'product_id'   => $product->product_id,
            'product_no'   => $product->product_no,
            'gg_name'      => $product->volume.'/'.$product->unit,
            'price'        => $product->price,
            'qty'          => $product->qty,
            'score'        => $score,
            'type'         => 1,
            'total_money'  => $product->price,
        ];

        $insert_id = $this->order_model->addOrderProduct($order_product_data);

        return $insert_id;

    }

    function addOrderAddress($order_id) {
		$order_address = [
			'order_id'  => $order_id,
			'position'  => 0,
			'address'   => '收礼地址待定',
			'name'      => '收礼人待定',
			'email'     => 0,
			'telephone' => 0,
			'mobile'    => '收礼人手机待定',
			'province'  => 106092,
			'city'      => 0,
			'area'      => 0,
		];

		$insert_id = $this->order_model->addOrderAddr($order_address);

        return $insert_id;
    }

    // 获取送礼商品列表
    function getProducts($limit = 10, $offset = 0) {

		$product_ids = $this->db->select('id')
            ->from('product')
            ->where('is_present', 1)
            ->limit($limit, $offset) // limit, offset
            ->get()->result();

        return $product_ids;
    }

    // 获取商品 只取第一个sku
    function getProduct($pid) {

		$product = $this->db->select('id, product_name, photo, product_desc, summary, template_id, send_region')
            ->from('product')
            ->where('id', $pid)
            ->limit(1)->get()->row();

        if(!$product)
            return false;

        // 获取产品模板图片
        if ($product->template_id) {
            $this->load->model('b2o_product_template_image_model');
            $templateImages = $this->b2o_product_template_image_model->getTemplateImage($product->template_id, 'main');
            if (isset($templateImages['main'])) {
                $product->photo = $templateImages['main']['image'];
            }
        }

        $sku = $this->db->select()
            ->from('product_price')
            ->where('product_id', $pid)
            ->limit(1)->get()->row();

        if(!$sku)
            return false;

        $sku_product = new StdClass();

        $sku_product->product_id  = $product->id;
        $sku_product->name        = $product->product_name;
        $sku_product->price       = $sku->price;
        $sku_product->photo       = constant(CDN_URL.rand(1, 9)).$product->photo;
        $sku_product->product_no  = $sku->product_no;
        $sku_product->desc        = $product->product_desc;
        $sku_product->summary     = $product->summary;
        $sku_product->sku_id      = $sku->id;
        $sku_product->weight      = $sku->weight;
        $sku_product->volume      = $sku->volume;
        $sku_product->unit        = $sku->unit;
        $sku_product->qty         = 1;
        $sku_product->send_region = unserialize($product->send_region);

        return $sku_product;
    }

    function getOrders($uid, $limit, $offset) {

		$orders = $this->db->select('order_name')
            ->from('order')
            ->where('uid', $uid)
            ->where('order_type', 9)
            ->where('show_status', 1)
            ->order_by('id desc')
            ->limit($limit, $offset)->get()->result();

        foreach($orders as &$order) {
            $order = $this->getOrder($order->order_name);
        }

        return $orders;
    }

    // operation
    // 0待审核
    // 1已审核
    // 2已发货
    // 3已完成
    // 4拣货中
    // 5已取消
    // 6配送完成
    // 7退货中
    // 8换货中
    // 9已收货

    // pay
    // 0还未付款
    // 1已经付款
    // 2到帐确认中

    // pay parent
    // 4 线下支付
    function getOrder($order_no) {

        $operation_status = [
            0=>'待发货',
            1=>'待发货',
            4=>'待发货',
            2=>'待收货',
            3=>'交易成功',
            5=>'已取消',
        ];

		$data = $this->db->select('id, order_name, order_type, money, method_money, time, shtime, stime, uid, operation_id, pay_status, lyg')
            ->from('order')
            ->where('order_name', $order_no)
            ->get()->row();

        $order = new StdClass();

        $order->id       = $data->id;
        $order->no       = $data->order_name;
        $order->type     = $data->order_type  == 9 ? '送礼' : '其它';
        $order->status   = $operation_status[$data->operation_id];
        $order->status   = ($data->pay_status == 0 && $data->operation_id == 0) ? '待付款': $order->status;
        $order->status   = ($data->pay_status == 2 && $data->operation_id == 0) ? '确认中': $order->status;
        $order->money    = $data->money;
        $order->freight  = $data->method_money;
        $order->time     = $data->time;
        $order->shtime   = $data->shtime;
        $order->stime    = $data->stime;
        $order->received = $data->lyg         == 9 ? '已领取'                :  '未领取';
        $order->products = $this->getOrderProduct($data->id);
        $order->address  = $this->getOrderAddress($data->id);

        if($this->debug)
            $order->debug = $data;

        return $order;
    }

    function getOrderProduct($order_id) {
		$data = $this->db->select('*')
            ->from('order_product')
            ->where('order_id', $order_id)
            ->get()->result();

        $products = [];

        foreach($data as $data) {
            $product        = new StdClass();

            $product->id    = $data->product_id;
            $product->name  = $data->product_name;
            $product->price = $data->price;
            $product->qty   = $data->qty;
            $product->spec  = $data->gg_name;

            $product->photo       = $this->getProduct($data->product_id)->photo;
            $product->send_region = $this->getProduct($data->product_id)->send_region;

            if($this->debug)
                $product->debug = $data;

            array_push($products, $product);
        }

        return $products;
    }

    function getOrderAddress($order_id) {
		$data = $this->db->select('*')
            ->from('order_address')
            ->where('order_id', $order_id)
            ->get()->row();

        $address = new StdClass();

        $address->name     = $data->name;
        $address->position = $data->position;
        $address->address  = $data->address;
        $address->mobile   = $data->mobile;
        $address->province = $data->province;
        $address->city     = $data->city;
        $address->area     = $data->area;

        return $address;
    }

    function updateOrder($order_id, $values) {

        $this->db->where('id', $order_id);
        $ret = $this->db->update('order', $values);

        return $ret;
    }


    function updateOrderAddress($order_id, $values) {

        $this->db->where('order_id', $order_id);
        $ret = $this->db->update('order_address', $values);

        return $ret;
    }

    // 获取用户id
    function getUserId($connect_id) {

        $this->load->library('login');
        $this->login->init($connect_id);

        if ( !$this->login->is_login() )
            return false;

        $uid = $this->login->get_uid();

        return $uid;

    }

}
