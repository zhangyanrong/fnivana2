<?php
namespace bll\wap;

// ini_set('display_errors',1);
// error_reporting(E_ERROR);

/**
 * 购买赠品套餐
 * @package bll\wap
 */
class Giftsbuy {

    public function __construct() {
        $this->ci = &get_instance();
        $this->ci->load->model('gifts_package_model');
        $this->ci->load->model('gifts_goods_model');
    }

    /**
    * @api {post} / 订单创建
    * @apiGroup giftsbuy
    * @apiName create
    *
    * @apiParam {Number} pid 套餐ID
    * @apiParam {String} connect_id 登录状态
    *
    * @apiSampleRequest /api/test?service=giftsbuy.create&source=wap
    */
    public function create($params) {

        $pid = intval($params['pid']);
        $userData = $this->ci->gifts_package_model->getUserData($params['connect_id']);

        if(!$pid)
            return ['code'=>300, 'msg' => '没有套餐ID'];
        if(!$userData['id'])
            return ['code' => 401,'msg' => '登录过期，请重新登录'];
        $uid = $userData['id'];
        $mobile = $userData['mobile'];

        $checkCount = $this->ci->gifts_package_model->get_user_gifts($uid, $pid);
        if ($checkCount) {
            return ['code' => 400, 'msg' => '您的套餐还未领完，请领取后购买!'];
        }

        // 验证套餐
        $packageDetail = $this->ci->gifts_package_model->detail($pid);
        if (!$packageDetail) {
            return [
                'code' => 500,
                'msg' => '套餐已过期'
            ];
        }

        // 获取商品
        $goods = $this->ci->gifts_goods_model->get_goods($pid);
        if(!$goods)
            return ['code' => 500,'msg'=>'套餐无效'];

        // 创建订单
        $this->ci->db->trans_begin();

        // 插入order
        $order = $this->ci->gifts_package_model->createOrder($uid, $goods);
        if (!$order)
            $this->ci->db->trans_rollback();

        // 插入order product
        $order_product_id = $this->ci->gifts_package_model->addOrderProduct($order['id'], $goods);
        if (!$order_product_id)
            $this->ci->db->trans_rollback();

        // 插入 order address
        $order_address_id = $this->ci->gifts_package_model->addOrderAddress($order['id'], $mobile);
        if (!$order_address_id)
            $this->ci->db->trans_rollback();

        // 更新order里的address_id
        $this->ci->gifts_package_model->updateOrder($order['order_name'], ['address_id' => $order_address_id]);

        // 插入订单套餐绑定表
        if( !$result = $this->ci->gifts_package_model->addGiftsPackageOrder($order['id'], $pid, $order['order_name']) )
            $this->ci->db->trans_rollback();

        // check order
        //$order = $this->ci->gifts_package_model->getOrder($order['order_name']);

        if ( $this->ci->db->trans_status() ) {

            $this->ci->db->trans_commit();
            return [
                'code' => 200,
                'msg'  => '创建订单成功',
                'data' => ['order_name' => $order['order_name']]
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
    * @api {post} / 订单列表
    * @apiGroup giftsbuy
    * @apiName orderList
    *
    * @apiParam {String} connect_id
    * @apiParam {Number} [page=1] 第几页
    * @apiParam {Number} [limit=10] 每页显示几条登录状态
    *
    * @apiSampleRequest /api/test?service=giftsbuy.orderList&source=wap
    */
    public function orderList($params) {
        $uid = $this->ci->gifts_package_model->getUserId($params['connect_id']);
        if(!$uid)
            return [
                'code' => 401,
                'msg'  => '登录过期，请重新登录'
            ];

        $limit  = $params['limit'] > 0 ? $params['limit'] : 10;
        $offset = ($params['page'] - 1 ) * $limit;

        $orders = $this->ci->gifts_package_model->getOrderList($uid, $limit, $offset);

        return [
            'code' => 200,
            'msg'  => '购买赠品套餐订单列表获取成功',
            'data' => $orders
        ];

    }

    /**
    * @api {post} / 确认订单
    * @apiGroup giftsbuy
    * @apiName order
    *
    * @apiParam {int} pid 套餐ID
    * @apiParam {String} connect_id 登录状态
    *
    * @apiSampleRequest /api/test?service=giftsbuy.order&source=wap
    */
    public function order($params) {
        $pid = intval($params['pid']);
        if (!$pid) {
            return [
                'code' => 300,
                'msg' => '参数错误'
            ];
        }

        $uid  = $this->ci->gifts_package_model->getUserId($params['connect_id']);
        if (!$uid) {
            return [
                'code' => 401,
                'msg' => '登录过期，请重新登录'
            ];
        }

        $packageDetail = $this->ci->gifts_package_model->detail($pid);
        if (!$packageDetail) {
            return [
                'code' => 500,
                'msg' => '套餐已无效'
            ];
        }

        $goods = $this->ci->gifts_goods_model->get_goods($pid);
        if (!$goods) {
            return [
                'code' => 500,
                'msg' => '套餐已无效'
            ];
        }

        // 统计商品总数和总重量/kg
        $goods_count = 0;
        $goods_weight = 0.00;
        $goods_money = 0.00;
        foreach ($goods as $k => $v) {
            $goods_count += $v['qty'];
            $goods_weight += $v['qty'] * $v['unit_weight'];
            $goods_money += $v['qty'] * $v['price'];
        }

        return [
            'code' => 200,
            'msg'  => '赠品获取成功',
            'data' => [
                'goods' => $goods,
                'money' => $goods_money,
                'count' => $goods_count,
                'weight' => $goods_weight
            ]
        ];
    }

    /**
    * @api {post} / 订单详细
    * @apiGroup giftsbuy
    * @apiName detail
    *
    * @apiParam {String} order_name 订单号
    *
    * @apiSampleRequest /api/test?service=giftsbuy.detail&source=wap
    */
    public function detail($params) {
        $uid = $this->ci->gifts_package_model->getUserId($params['connect_id']);
        if(!$uid)
            return [
                'code' => 401,
                'msg'  => '登录过期，请重新登录'
            ];

        $order_name = $params['order_name'];
        if(!$order_name)
            return [
                'code' => 300,
                'msg'  => '缺少参数'
            ];
        $order = $this->ci->gifts_package_model->getOrder($order_name);
        if (!$order) {
            return [
                'code' => 300,
                'msg'  => '订单不存在'
            ];
        }
        $data = [
            'order_name' => $order['order_name'],
            'time' => $order['time'],
            'money' => $order['money'],
            'pay_name' => $order['pay_name'],
            'pay_status' => $order['pay_status'],
            'operation_id' => $order['operation_id'],
            'goods_money' => $order['goods_money'],
            'products' => $this->ci->gifts_package_model->getProductsByOrderId($order['id'])
        ];

        // 统计商品总数和总重量/kg
        $goods_count = 0;
        $goods_weight = 0.00;
        foreach ($data['products'] as $k => $v) {
            $goods_count += $v['qty'];
            $goods_weight += $v['qty'] * $v['unit_weight'];
        }
        $data['count'] = $goods_count;
        $data['weight'] = $goods_weight;
        return [
            'code' => 200,
            'msg'  => '购买赠品套餐订单详细获取成功',
            'data' => $data
        ];
    }

    /**
     * @api {post} / 取消订单
     * @apiGroup giftsbuy
     * @apiName cancel
     *
     * @apiParam {String} order_name 订单号
     *
     * @apiSampleRequest /api/test?service=giftsbuy.cancel&source=wap
     */
    public function cancel($params) {
        $uid = $this->ci->gifts_package_model->getUserId($params['connect_id']);
        if(!$uid)
            return [
                'code' => 401,
                'msg'  => '登录过期，请重新登录'
            ];

        $order_name = $params['order_name'];
        if(!$order_name)
            return [
                'code' => 300,
                'msg'  => '缺少参数'
            ];
        $order = $this->ci->gifts_package_model->getOrder($order_name);
        if (!$order) {
            return [
                'code' => 300,
                'msg'  => '订单不存在'
            ];
        }
        $result = $this->ci->gifts_package_model->updateOrder($order_name, ['operation_id' => 5]);
        if ($result) {
            return [
                'code' => 200,
                'msg' => '取消订单成功'
            ];
        } else {
            return [
                'code' => 500,
                'msg' => '取消订单失败'
            ];
        }
    }

    /**
     * 订单支付完成后，插入赠品
     *
     * @param order_name string 订单编号
     */
    public function give($params) {
        $order_name = $params['order_name'];
        if (!$order_name) {
            die(0);
        }

        // 验证订单有效性
        $this->ci->load->model('order_model');
        $orderInfo = $this->ci->order_model->dump(
            ['order_name' => $order_name, 'order_type' => 10, 'order_status' => 1, 'pay_status' => 1, 'operation_id <>' => 5]
        );
        if (!$orderInfo) {
            die(0);
        }

        $activeInfo = $this->ci->gifts_goods_model->get_gift_active($order_name);
        if ($activeInfo) {
            $batch_data = [];
            foreach ($activeInfo as $item) {
                if ($item['gift_valid_day'] && $item['gift_valid_day'] > 0) {
                    $gift_start_time = date('Y-m-d');
                    $gift_end_time = date('Y-m-d',strtotime('+'.(intval($item['gift_valid_day'])-1).' day'));
                } elseif ($item['gift_start_time'] && $item['gift_end_time'] && $item['gift_start_time'] != '0000-00-00' && $item['gift_end_time'] != '0000-00-00') {
                    $gift_start_time = $item['gift_start_time'];
                    $gift_end_time = $item['gift_end_time'];
                } else {
                    $gift_start_time = $item['start'];
                    $gift_end_time = $item['end'];
                }
                $batch_data[] = [
                    'uid' => $orderInfo['uid'],
                    'active_id' => $item['active_id'],
                    'active_type' => 2,
                    'has_rec' => 0,
                    'start_time' => $gift_start_time,
                    'end_time' => $gift_end_time,
                    'pid' => $item['pid']
                ];
            }
            $this->ci->db->insert_batch('ttgy_user_gifts', $batch_data);
        }
    }

}
