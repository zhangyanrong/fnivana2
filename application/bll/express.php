<?php

namespace bll;

class Express
{
    protected $uid = 0;
    protected $userInfo = [];

    public function __construct()
    {
        $this->ci = & get_instance();

        $this->ci->load->model('order_express_model');
    }

    /**
     * @api {post} / 创建用户发票邮费订单
     * @apiDescription 创建用户发票邮费订单
     * @apiGroup express
     * @apiName createOrder
     *
     * @apiParam {String} [connect_id] 登录标识
     * @apiParam {String} [invoice_id] 发票订单id
     *
     * @apiSampleRequest /api/test?service=express.createOrder&source=app
     */
    public function createOrder($params)
    {
        $checkInfo = $this->checkConnect($params);
        if (!empty($checkInfo['code']) && $checkInfo['code'] != 200) {
            return $checkInfo;
        }

        $required = [
            'invoice_id' => ['required' => ['code' => '500', 'msg' => 'invoice_id can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        $now = date('Y-m-d H:i:s', $params['timestamp']);

        // 有未支付的订单
        $exists = $this->ci->order_express_model->dump([
            'uid' => $this->uid,
            'pay_status' => 0,
            'invoice_id'=>$params['invoice_id']
        ], 'id, order_name');

        if ($exists) {
            $setData = [
                'last_modify_time' => $now,
                'money'=>'5.00',
                'goods_money'=>'5.00',
            ];

            $this->ci->order_express_model->update($setData, [
                'id' => $exists['id'],
                'order_name' => $exists['order_name'],
            ]);

            return ['code' => '200', 'msg' => '', 'data' => [
                'order_name' => $exists['order_name'],
            ]];
        }

        //发票 1-非增值税发票 2-增值税发票
        if(isset($params['pay_invoice_type']) && $params['pay_invoice_type'] == 2)
        {
            $order_type = 22;
        }
        else
        {
            $order_type = 21;
        }

        $orderName = $this->makeOrder('M');
        $orderData = [
            // 订单信息
            'order_name' => $orderName,
            'time' => $now,
            'channel' => 1,
            'order_type' => $order_type,
            'sales_channel' => 1,
            'version' => 1,
            'last_modify_time' => $now,

            // 用户信息
            'uid' => $this->uid,

            //邮费信息
            'money'=>'5.00',
            'goods_money'=>'5.00',

            // 支付信息
            'pay_name' => '微信支付',
            'pay_parent_id' => 7,
            'pay_id' => 0,

            // 状态信息
            'order_status' => 1,
            'operation_id' => 1,

            //发票id
            'invoice_id' => $params['invoice_id'],
        ];

        $orderId = $this->ci->order_express_model->insert($orderData);
        if ($orderId) {
            return ['code' => '200', 'msg' => '', 'data' => [
                'order_name' => $orderName,
            ]];
        } else {
            return ['code' => '300', 'msg' => '订单创建失败'];
        }
    }

    /**
     * @api {post} / 获取用户的邮费特权信息
     * @apiDescription 根据用户标识，获取用户的邮费特权信息
     * @apiGroup express
     * @apiName getInfo
     *
     * @apiParam {String} [connect_id] 登录标识
     * @apiParam {String} [invoice_id] 发票订单id
     *
     * @apiSampleRequest /api/test?service=express.getInfo&source=app
     */
    public function getInfo($params)
    {
        $checkInfo = $this->checkConnect($params);
        if (!empty($checkInfo['code']) && $checkInfo['code'] != 200) {
            return $checkInfo;
        }

        $required = [
            'invoice_id' => ['required' => ['code' => '500', 'msg' => 'invoice_id can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        // 获取邮费订单
        $info = $this->ci->order_express_model->dump(array('uid'=> $this->uid,'invoice_id'=>$params['invoice_id']));
        $money = 5;
        if ($info) {
            $money = $info['money'];
            return $money;
        } else {
            return $money;
        }
    }


    /**
     * 用户登录验证
     */
    private function checkConnect($params)
    {
        $required = [
            'connect_id' => ['required' => ['code' => '500', 'msg' => 'connect_id can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }
        // 获取用户ID
        $this->ci->load->library('session', ['session_id' => $params['connect_id']]);
        $session = $this->ci->session->userdata;
        $userdata = isset($session['id']) ? $session : unserialize($session['user_data']);
        if (!$userdata['id']) {
            return ['code' => '300', 'msg' => '用户登录超时，请重新登录'];
        }
        $this->uid = $userdata['id'];
        $this->userInfo = $userdata;
    }

    /**
     * 创建子单
     */
    private function makeOrder($prefix)
    {
        $order_name = $prefix . date("ymdi") . rand_code(4);
        return $order_name;
    }
}
