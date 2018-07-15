<?php

/**
 * 赠品套餐购买模型
 */
class Gifts_package_model extends MY_Model {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取表名
     */
    public function table_name() {
        return 'gifts_package';
    }

    /**
     * 获取套餐明细
     *
     * @params $id int 套餐ID
     */
    public function detail($id) {
        $wheres = [
            'id' => $id,
            'is_enabled' => 1,
            'is_deleted' => 0,
            'time_start <=' => date('Y-m-d H:i:s'),
            'time_end >=' => date('Y-m-d H:i:s')
        ];
        $result = $this->db->from($this->table_name())->where($wheres)->get()->row_array();
        return $result;
    }

    function createOrder($uid, $product) {
        $this->load->model('order_model');
        $money = 0.00;
        foreach ($product as $item) {
            $money += $item['price'] * $item['qty'];
        }
        $order = [
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
            'money'           => $money, // 订单金额
            'pmoney'          => $money, // 支付金额
            'goods_money'     => $money, // 商品金额
            'pay_status'      => 0, // 是否支付
            'score'           => null, // 订单积分
            'address_id'      => null, // 用户地址id
            'order_status'    => 1,
            'method_money'    => 0, // 运费
            'channel'         => 6, // 渠道
            'order_type'      => 10, // 10 购买赠品套餐订单
            'version'         => 1, // 版本
        ];
        $order_id = $this->order_model->generate_order('order', $order, 'F');
        if( !$order_id )
            return false;
        $order = $this->order_model->dump(['id' => $order_id]);
        return $order;
    }

    function addOrderProduct($order_id, $product) {
        $this->load->model('order_model');
        $result = true;
        foreach ($product as $item) {
            //$productDetail = $this->getProduct($item['product_id']);
            $order_product_data = [
                'order_id'     => $order_id,
                'product_name' => addslashes($item['product_name']),
                'product_id'   => $item['product_id'],
                'product_no'   => $item['product_no'],
                'gg_name'      => $item['volume'].'/'.$item['unit'],
                'price'        => $item['price'],
                'qty'          => $item['qty'],
                'score'        => null,
                'type'         => 1,
                'total_money'  => $item['price'] * $item['qty'],
            ];
            $insert_id = $this->order_model->addOrderProduct($order_product_data);
            if (!$insert_id) {
                $result = false;
                break;
            }
        }
        return $result;
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
		$product = $this->db->select('id, product_name')
            ->from('product')
            ->where('id', $pid)
            ->limit(1)->get()->row();

        if(!$product)
            return false;

        $sku = $this->db->select()
            ->from('product_price')
            ->where('product_id', $pid)
            ->limit(1)->get()->row();

        if(!$sku)
            return false;
        $sku_product = [
            'product_name' => $product->product_name,
            'product_no' => $sku->product_no,
            'volume' => $sku->volume,
            'unit' => $sku->unit
        ];
        return $sku_product;
    }

    function getOrderList($uid, $limit, $offset) {
		$orders = $this->db->select('id,order_name,money,time,pay_name,pay_status,operation_id,goods_money')
            ->from('order')
            ->where('uid', $uid)
            ->where('order_type', 10)
            ->where('show_status', 1)
            ->order_by('id desc')
            ->limit($limit, $offset)->get()->result_array();
        foreach($orders as $k => $order) {
            $goods = $this->getProductsByOrderId($order['id']);
            $goods_count = 0;
            if ($goods) {
                foreach ($goods as $v) {
                    $goods_count += $v['qty'];
                }
            }
            $orders[$k]['products'] = $goods;
            $orders[$k]['count'] = $goods_count;
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
        /*
        $operation_status = [
            0=>'待发货',
            1=>'待发货',
            4=>'待发货',
            2=>'待收货',
            3=>'交易成功',
            5=>'已取消',
        ];
        */
		$data = $this->db->select('*')
            ->from('order')
            ->where('order_name', $order_no)
            ->get()->row_array();
        /*
        if ($data) {
            $operation_id = $data['operation_id'];
            $data['operation_name'] = $operation_status[$operation_id];
        }
        */
        return $data;
    }

    function updateOrder($order_name, $values) {
        $this->db->where('order_name', $order_name);
        $ret = $this->db->update('order', $values);
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

    /**
     * 获取登录用户数据
     *
     * @param $connect_id
     * @return array
     */
    function getUserData($connect_id) {
        $this->load->library('login');
        $this->login->init($connect_id);
        if ( !$this->login->is_login() )
            return [];
        $user_data = $this->login->get_user();
        return $user_data;
    }

    function addGiftsPackageOrder($order_id, $pid, $order_name) {
        $data = [
            'order_id' => $order_id,
            'pid' => $pid,
            'order_name' => $order_name
        ];
        return $this->db->insert('gifts_package_order', $data);
    }

    public function getProductsByOrderId($order_id) {
        $this->db->select('product_name,product_id,price,qty,total_money,gg_name');
        $this->db->from('order_product');
        $this->db->where(array('order_id' => $order_id));
        $products = $this->db->get()->result_array();
        if ($products) {
            foreach ($products as $k => $v) {
                $photo = $this->getProductPhoto($v['product_id']);
                $products[$k]['photo'] = constant(CDN_URL.rand(1, 9)) . $photo['thum_min_photo'];
                $spec = $this->getProductSpec($v['product_id']);
                $products[$k]['unit_weight'] = $spec['unit_weight'];
            }
        }
        return $products;
    }

    // 获取商品图片,最新的一条
    function getProductPhoto($pid) {
        $photos = $this->db->select('photo,thum_photo,middle_photo,bphoto,thum_min_photo')
                ->from('product')
                ->where('id', $pid)
                ->limit(1)
                ->order_by('id DESC')
                ->get()->row_array();
        return $photos;
    }

    // 获取商品规格,最新的一条
    function getProductSpec($pid) {
        $spec = $this->db->select('*')
            ->from('product_price')
            ->where('product_id', $pid)
            ->limit(1)
            ->order_by('id DESC')
            ->get()->row_array();
        return $spec;
    }

    /**
     * 新增收货地址
     *
     * @param $order_id int 订单id
     * @param $mobile string 用户手机号
     *
     * @return mixed
     */
    function addOrderAddress($order_id, $mobile) {
        if (!$order_id) {
            return 0;
        }
        $order_address = [
            'order_id'  => $order_id,
            'position'  => '上海上海市浦东新区（外环线以内）',
            'address'   => '上海上海市浦东新区（外环线以内）祖冲之路887弄71-72号5楼',
            'name'      => $mobile,
            'mobile'    => $mobile,
            'province'  => 106092,
            'city'      => 106093,
            'area'      => 106094,
        ];
        $insert_id = $this->insertOrderAddress($order_address);
        return $insert_id;
    }

    /**
     * 新增订单地址
     */
    function insertOrderAddress($insert_data) {
        $this->db->insert("order_address", $insert_data);
        $id = $this->db->insert_id();
        return $id;
    }

    /**
     * 验证赠品是否领取完成
     */
    function get_user_gifts($uid = 0, $pid = 0) {
        if (!$uid || !$pid) {
            return 0;
        }
        $sql = "SELECT 
          COUNT(1) AS total 
        FROM
          ttgy_user_gifts 
        WHERE uid = $uid 
          AND pid = $pid 
          AND has_rec = 0 
          AND end_time >= DATE_FORMAT(NOW(), '%Y-%m-%d')";
        $row = $this->db->query($sql)->row_array();
        return $row['total'] > 0 ? 1 : 0;
    }

}
