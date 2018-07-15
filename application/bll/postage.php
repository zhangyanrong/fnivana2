<?php

namespace bll;

class Postage
{
    protected $uid = 0;
    protected $userInfo = [];

    public function __construct()
    {
        $this->ci = & get_instance();

        $this->ci->load->model('order_postage_model');
        $this->ci->load->model('user_jf_model');
        $this->ci->load->model('user_model');
    }

    /**
     * @api {post} / 创建用户邮费特权订单
     * @apiDescription 创建用户邮费特权订单
     * @apiGroup postage
     * @apiName createOrder
     *
     * @apiParam {String} connect_id 登录标识
     * @apiParam {String} exchange_type 兑换类型 1.1 25元/月 30次 | 2.1 2500积分/月 30次 | 1.2 9.9元/周 14次 | 2.2 990积分/周 14次 | 3.1 体验卡 1天 3次 | 3.2 体验卡 1年 36次
     * @apiParam {String} [active_tag] 活动标识
     *
     * @apiSampleRequest /api/test?service=postage.createOrder&source=app
     */
    public function createOrder($params)
    {
        $checkInfo = $this->checkConnect($params);
        if (!empty($checkInfo['code']) && $checkInfo['code'] != 200) {
            return $checkInfo;
        }

        $required = [
            'exchange_type' => ['required' => ['code' => '500', 'msg' => 'exchange_type can not be null']],
        ];
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return ['code' => $checkResult['code'], 'msg' => $checkResult['msg']];
        }

        $configArr = $this->ci->config->item('postage');
        $typeTemp = explode('.', $params['exchange_type']);
        if (count($typeTemp) != 2) {
            return ['code' => 500, 'msg' => 'exchange_type error'];
        } else {
            $exchangeType = $typeTemp[0];

            if (empty($configArr[$exchangeType][$typeTemp[1]])) {
                return ['code' => 500, 'msg' => 'exchange_type error'];
            } else {
                $config = $configArr[$exchangeType][$typeTemp[1]];
            }
        }
        $timestamp = !empty($params['timestamp']) ? $params['timestamp'] : $_SERVER['REQUEST_TIME'];
        $now = date('Y-m-d H:i:s', $timestamp);
        $activeTag = !empty($params['active_tag']) ? $params['active_tag'] : '';

        // 有有效的特权
        $exists = $this->ci->order_postage_model->dump([
            'uid' => $this->uid,
            'postage_status' => 1,
            'available_times >' => 0,
            'start_time <=' => $now,
            'end_time >=' => $now,
        ], 'id, order_name');
        if ($exists) {
            return ['code' => '302', 'msg' => '您的邮费特权服务暂未失效，无需购买'];
        }

        // 有未支付的订单
        $exists = $this->ci->order_postage_model->dump([
            'uid' => $this->uid,
            'pay_status' => 0,
            'exchange_type' => $exchangeType,
        ], 'id, order_name');
        if ($exists && '3' != $exchangeType) {
            $setData = [
                'last_modify_time' => $now,
                'start_time' => date('Y-m-d', $timestamp),
                'end_time' => date("Y-m-d", strtotime($config['time'], $timestamp)),
                'times' => $config['times'],
                'available_times' => $config['times'],
            ];

            // money
            if ('1' ==  $exchangeType) {
                // use_money_deduction + money + jf_money + card_money + ... = goods_money + method_menty + invoice_money
                $setData['money'] = $setData['goods_money'] = $config['money'];
                // 余额
                $setData['use_money_deduction'] = 0;
                $setData['pay_parent_id'] = 7;
                $setData['pay_id'] = 0;
                $setData['pay_name'] = '微信支付';
            }
            // jf
            elseif ('2' ==  $exchangeType) {
                $setData['money'] = 0;
                $setData['goods_money'] = $config['jf'] / 100;
                $setData['jf_money'] = $config['jf'] / 100;
                $setData['use_jf'] = $config['jf'];
            }

            $result = $this->ci->order_postage_model->update($setData, [
                'id' => $exists['id'],
                'order_name' => $exists['order_name'],
            ]);
            if ($result) {
                return ['code' => '200', 'msg' => '', 'data' => [
                    'order_name' => $exists['order_name'],
                ]];
            } else {
                return ['code' => '303', 'msg' => '订单创建失败'];
            }
        }

        $this->ci->db->trans_begin();

        $orderName = $this->makeOrder('Y');
        $orderData = [
            // 订单信息
            'order_name' => $orderName,
            'time' => $now,
            'channel' => 1,
            // 20 邮费特权订单
            'order_type' => 20,
            'sales_channel' => 1,
            'version' => 1,
            'last_modify_time' => $now,

            // 用户信息
            'uid' => $this->uid,

            // 1 金钱 | 2 积分 | 3 体验
            'exchange_type' => $exchangeType,

            // 支付信息
            'pay_name' => '微信支付',
            'pay_parent_id' => 7,
            'pay_id' => 0,

            // 状态信息
            'order_status' => 1,
            'operation_id' => 1,

            // 特权信息
            'postage_status' => 0, // 是有有效
            'start_time' => date('Y-m-d', $timestamp),
            'end_time' => date("Y-m-d", strtotime($config['time'], $timestamp)),
            'times' => $config['times'],
            'available_times' => $config['times'],

            // 活动标识
            'active_tag' => $activeTag,
        ];

        // money
        if ('1' == $exchangeType) {
            $orderData['money'] = $config['money'];
            $orderData['goods_money'] = $config['money'];
        }
        // jf
        elseif ('2' == $exchangeType) {
            $orderData['money'] = 0;
            $orderData['goods_money'] = $config['jf'] / 100;
            $orderData['jf_money'] = $config['jf'] / 100;
            $orderData['use_jf'] = $config['jf'];

            $userJf = $this->ci->user_model->dump(['id' => $this->uid], 'jf');
            if ($userJf['jf'] < $config['jf']) {
                return ['code' => '303', 'msg' => '订单创建失败，积分不足'];
            }
        }

        $orderId = $this->ci->order_postage_model->insert($orderData);
        if ($orderId) {
            if ('2' == $exchangeType) {
                $result = $this->ci->user_model->cut_uses_jf($this->uid, $config['jf'], $orderName);
                if ($result) {
                    $this->ci->order_postage_model->update([
                        'pay_status' => 1,
                        'postage_status' => 1,
                        'update_pay_time' => $now,
                    ], ['order_name' => $orderName]);
                }
            } elseif ('3' == $exchangeType) {
                $this->ci->order_postage_model->update([
                    'pay_status' => 1,
                    'postage_status' => 1,
                    'update_pay_time' => $now,
                ], ['order_name' => $orderName]);
            }
        }

        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();

            return ['code' => '303', 'msg' => '订单创建失败'];
        } else {
            $this->ci->db->trans_commit();

            return ['code' => '200', 'msg' => '', 'data' => [
                'order_name' => $orderName,
            ]];
        }
    }

    /**
     * @api {post} / 获取用户的邮费特权信息
     * @apiDescription 根据用户标识，获取用户的邮费特权信息
     * @apiGroup postage
     * @apiName getInfo
     *
     * @apiParam {String} connect_id 登录标识
     *
     * @apiSampleRequest /api/test?service=postage.getInfo&source=app
     */
    public function getInfo($params)
    {
        $checkInfo = $this->checkConnect($params);
        if (!empty($checkInfo['code']) && $checkInfo['code'] != 200) {
            return $checkInfo;
        }

        $timestamp = !empty($params['timestamp']) ? $params['timestamp'] : $_SERVER['REQUEST_TIME'];
        // 获取邮费特权信息
        $info = $this->ci->order_postage_model->getUserPostagePrivilegeInfo($this->uid, date("Y-m-d H:i:s", $timestamp));
        if ($info) {
            // Fix 结束时间 2017-11-13 00:00:00 显示为 2017-11-13 的问题，修改为显示 2017-11-12
            $info['end_time'] = date("Y-m-d H:i:s", strtotime("{$info['end_time']} -1 second"));

            return ['code' => '200', 'msg' => '', 'data' => ['postage' => $info, 'user' => $this->userInfo]];
        } else {
            return ['code' => '301', 'msg' => '未开通特权', 'data' => ['postage' => [], 'user' => $this->userInfo]];
        }
    }

    public function getOrderList($params)
    {
        $checkInfo = $this->checkConnect($params);
        if (!empty($checkInfo['code']) && $checkInfo['code'] != 200) {
            return $checkInfo;
        }

        $info = $this->ci->order_postage_model->getList('exchange_type, update_pay_time, money, use_jf', [
            'uid' => $this->uid,
            'pay_status' => 1,
            'exchange_type' => [1,2,4,5],
        ]);
        if ($info) {
            return ['code' => '200', 'msg' => '', 'data' => $info];
        } else {
            return ['code' => '304', 'msg' => '暂无购买记录'];
        }
    }

    public function getUseList($params)
    {
        $checkInfo = $this->checkConnect($params);
        if (!empty($checkInfo['code']) && $checkInfo['code'] != 200) {
            return $checkInfo;
        }

        $this->ci->load->model('postage_log_model');
        $info = $this->ci->postage_log_model->getList('order_name, time, remark', [
            'uid' => $this->uid,
        ], 0, -1, 'time DESC');
        if ($info) {
            return ['code' => '200', 'msg' => '', 'data' => $info];
        } else {
            return ['code' => '304', 'msg' => '暂无使用记录'];
        }
    }

    /**
     * 用户登录验证
     */
    private function checkConnect($params)
    {
        $required = [
            'connect_id' => ['required' => ['code' => '500', 'msg' => 'connect_id can not be null']],
//            'uid' => ['required' => ['code' => '500', 'msg' => 'uid can not be null']],
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
//        $this->uid = $params['uid'];
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
