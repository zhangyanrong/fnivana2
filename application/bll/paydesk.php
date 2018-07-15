<?php
namespace bll;
/**
 *
 *@desc 收银台
 *@order_type  order | b2o | postage | trade | express
 *@author jackchen
 **/
class Paydesk
{

    public function __construct()
    {
        $this->ci = &get_instance();

        //order
        $this->ci->load->model('order_model');
        $this->ci->load->model('order_product_model');

        //b2o
        $this->ci->load->model('b2o_parent_order_model');
        $this->ci->load->model('b2o_parent_order_product_model');

        //postage
        $this->ci->load->model('order_postage_model');

        //express
        $this->ci->load->model('order_express_model');

        //trade
        $this->ci->load->model('trade_model');

        $this->ci->load->model('user_model');
        $this->ci->load->model('pay_discount_model');
        $this->ci->load->helper('public');
    }


    /*
     * 初始化
     */
    public function init($params)
    {
        //必要参数验证
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取用户信息
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();
        if(empty($uid))
        {
            return array('code' => 300, 'msg' => '用户登录异常，请重新登录');
        }

        //银行秒杀排队
        $bank_limit = $this->orderLimit();
        if($bank_limit['code'] != 200)
        {
            return array("code"=>$bank_limit['code'],"msg"=>$bank_limit['msg'],"data"=>$bank_limit['data']);
        }

        //事务 start
        $this->ci->db->trans_begin();

        //订单类型
        $type = $this->orderType($params['order_name']);

        //获取订单信息
        $order = $this->orderInfo($params['order_name'],$uid,$type);

        //check
        $check = $this->orderCheck($order,$type);
        if($check['code'] != 200)
        {
            return array('code' => $check['code'], 'msg' => $check['msg']);
        }

        //构建结构
        $order = $this->orderSet($order,$type);

        //支付
        $order = $this->orderPay($order,$type,$params);

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "请返回订单，重新支付");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => 'success', 'data' => $order);
    }


    /*
    * 使用余额
    */
    public function useBalance($params)
    {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();
        if (empty($uid)) {
            return array('code' => 300, 'msg' => '用户登录异常，请重新登录');
        }

        //事务 start
        $this->ci->db->trans_begin();

        //获取订单信息
        $type = $this->orderType($params['order_name']);
        $order = $this->orderInfo($params['order_name'],$uid,$type);

        //check
        $check = $this->orderCheck($order,$type);
        if($check['code'] != 200)
        {
            return array('code' => $check['code'], 'msg' => $check['msg']);
        }

        $user = $this->ci->user_model->getUser($uid);

        $user_money = $user['money'];
        $pay_bank_discount = $order['new_pay_discount'];
        $use_money_deduction = $order['use_money_deduction'];
        $order_money = $order['money'] + $pay_bank_discount + $use_money_deduction; //订单金额需要先加上支付抵扣金额

        $info = array();

        if ($user_money > 0) {

            if ($user_money >= $order_money)
            {
                //变更支付方式
                $pay_array = $this->ci->config->item("pay_array");
                $pay_parent_id = 5;
                $pay_id = 0;
                $pay_name = $pay_array[$pay_parent_id]['name'];
                $this->changePayment($pay_name, $pay_parent_id, $pay_id,$order['id'],$type);

                //取消发票
                $order_money = $order_money - $order['invoice_money'];
                $data = array(
                    'use_money_deduction' => 0.00,
                    'new_pay_discount' => 0.00,
                    'money' => $order_money,
                    'invoice_money' => 0.00
                );
                $where = array(
                    'order_name' => $params['order_name'],
                    'order_status' => 1,
                );
                $this->updateOrder($data,$where,$type);
                $invoice = array(
                    'is_valid' => 0
                );
                $invoice_where = array(
                    'order_id' => $order['id']
                );
                $this->updateOrderInvoice($invoice, $invoice_where,$type);

                //全额使用余额抵扣，初始化支付折扣
                $this->ci->pay_discount_model->initPayDiscount($order['id']);

                $info['order_name'] = $order['order_name'];
                $info['user_money'] = number_format((float)($user['money']), 2, '.', '');
                $info['user_can_money'] = number_format((float)($user['money'] - $order_money), 2, '.', '');
                $info['order_money'] = number_format((float)$order_money, 2, '.', '');
                $info['use_money_deduction'] = $use_money_deduction;
                $info['need_online_pay'] = number_format((float)$data['use_money_deduction'], 2, '.', '');;
                $info['is_pay_balance'] = 1;
                $info['pay_bank_discount'] = number_format(0, 2, '.', '');

                $info['id'] = $order['id'];
                $info['uid'] = $order['uid'];
                $info['pay_parent_id'] = $pay_parent_id;
                $info['address_id'] =  $order['address_id'];
                $info['need_send_code'] = $this->sendCheckMsg($info,$type);

                $info['selectPayments'] = array(
                    'pay_parent_id' => '5',
                    'pay_id' => '0',
                    'pay_name' => '',
                    'has_invoice' => 0,
                    'no_invoice_message' => '',
                    'icon' => '',
                    'discount_rule' => '',
                    'user_money' => '',
                    'prompt'=>''
                );
            }
            else if ($user_money < $order_money)
            {
                $data = array(
                    'use_money_deduction' => $user_money,
                    'money' => ($order_money - $pay_bank_discount) - $user_money
                );
                $where = array(
                    'order_name' => $order['order_name'],
                    'order_status' => 1,
                );
                $this->updateOrder($data,$where,$type);

                $info['order_name'] = $order['order_name'];
                $info['user_money'] = number_format((float)($user['money']), 2, '.', '');
                $info['user_can_money'] = number_format((float)($user['money'] - $data['use_money_deduction']), 2, '.', '');
                $info['order_money'] = number_format((float)$order_money, 2, '.', '');
                $info['use_money_deduction'] = $data['use_money_deduction'];
                $info['need_online_pay'] = number_format((float)($order_money - $data['use_money_deduction']), 2, '.', '');
                $info['is_pay_balance'] = 0;

                //银行支付折扣
                $order['money'] = number_format($info['need_online_pay'], 2, '.', '');
                $pay_bank_discount = $this->payDiscount($order,$type);
                $new_money = number_format((float)($info['need_online_pay'] - $pay_bank_discount), 2, '.', '');
                $new_data = array('money' => $new_money);
                $new_where = array('id' => $order['id']);
                $this->updateOrder($new_data,$new_where,$type);

                $info['need_online_pay'] = number_format((float)($info['need_online_pay'] - $pay_bank_discount), 2, '.', '');
                $info['pay_bank_discount'] = number_format($pay_bank_discount, 2, '.', '');

                //组合支付验证
                $info['need_send_code'] = $this->sendCheckMsg($order,$type);
                if($info['use_money_deduction'] >0 && $info['need_send_code'] == 1)
                {
                    $info['need_send_code'] = 1;
                }
                else
                {
                    $info['need_send_code'] = 0;
                }

                $prompt = $this->showBankText($order['pay_id'],$order['pay_parent_id']);
                $info['selectPayments'] = array(
                    'pay_parent_id' => $order['pay_parent_id'],
                    'pay_id' => $order['pay_id'],
                    'pay_name' => $order['pay_name'],
                    'has_invoice' => 0,
                    'no_invoice_message' => '',
                    'icon' => '',
                    'discount_rule' => '',
                    'user_money' => '',
                    'prompt'=>$prompt
                );
            }

            //积分
            $this->score($order,$type);
        }
        else
        {
            return array('code' => 300, 'msg' => '用户余额不足');
        }

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "使用余额失败，请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => 'success', 'data' => $info);
    }


    /*
     * 取消使用余额
     */
    public function cancelUseBalance($params) {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();
        if (empty($uid)) {
            return array('code' => 300, 'msg' => '用户登录异常，请重新登录');
        }

        //事务 start
        $this->ci->db->trans_begin();

        //获取订单信息
        $type = $this->orderType($params['order_name']);
        $order = $this->orderInfo($params['order_name'],$uid,$type);

        //check
        $check = $this->orderCheck($order,$type);
        if($check['code'] != 200)
        {
            return array('code' => $check['code'], 'msg' => $check['msg']);
        }

        $user = $this->ci->user_model->getUser($uid);

        $order_money = $order['money'];
        $use_money_deduction = $order['use_money_deduction'];
        $pay_bank_discount = $order['new_pay_discount'];
        $order_money = $order_money + $pay_bank_discount + $use_money_deduction;

        $info = array();

        if ($use_money_deduction > 0) {

            $data = array(
                'use_money_deduction' => 0.00,
                'new_pay_discount' => 0.00,
                'money' => $order['money'] + $use_money_deduction + $pay_bank_discount
            );
            $where = array(
                'order_name' => $order['order_name'],
                'order_status' => 1,
            );
            $this->updateOrder($data,$where,$type);

            $info['order_name'] = $order['order_name'];
            $info['user_money'] = number_format((float)$user['money'], 2, '.', '');
            $info['user_can_money'] = number_format((float)($user['money']), 2, '.', '');
            $info['order_money'] = $order_money;
            $info['use_money_deduction'] = number_format((float)$data['use_money_deduction'], 2, '.', '');
            $info['need_online_pay'] = number_format((float)($order_money - $data['use_money_deduction']), 2, '.', '');
            $info['is_pay_balance'] = 0;

            //银行支付折扣
            $order['money'] = number_format($info['need_online_pay'], 2, '.', '');
            $pay_bank_discount = $this->payDiscount($order,$type);
            $new_money = number_format((float)($info['need_online_pay'] - $pay_bank_discount), 2, '.', '');
            $new_data = array('money' => $new_money);
            $new_where = array('id' => $order['id']);
            $this->updateOrder($new_data,$new_where,$type);

            $info['order_money'] = number_format((float)$order_money, 2, '.', '');
            $info['need_online_pay'] = number_format((float)($info['need_online_pay'] - $pay_bank_discount), 2, '.', '');
            $info['need_send_code'] = 0;
            $info['pay_bank_discount'] = number_format($pay_bank_discount, 2, '.', '');

            $prompt = $this->showBankText($order['pay_id'],$order['pay_parent_id']);
            $info['selectPayments'] = array(
                'pay_parent_id' => $order['pay_parent_id'],
                'pay_id' => $order['pay_id'],
                'pay_name' => $order['pay_name'],
                'has_invoice' => 0,
                'no_invoice_message' => '',
                'icon' => '',
                'discount_rule' => '',
                'user_money' => '',
                'prompt'=>$prompt
            );

        }
        else if ($use_money_deduction == '0.00' && $order['pay_parent_id'] == 5)
        {
            //使用发票
            $invoice_info = $this->orderInvoice($order,$type);
            if (!empty($invoice_info)) {
                $order_money = $order_money + 5;
                $data = array(
                    'money' => $order_money,
                    'invoice_money' => 5.00
                );
            } else {
                $data = array(
                    'money' => $order_money,
                );
            }
            $where = array(
                'order_name' => $order['order_name'],
                'order_status' => 1,
            );
            $this->updateOrder($data,$where,$type);

            $invoice = array(
                'is_valid' => 1
            );
            $invoice_where = array(
                'order_id' => $order['id']
            );
            $this->updateOrderInvoice($invoice,$invoice_where,$type);

            $info['order_name'] = $order['order_name'];
            $info['user_money'] = number_format((float)($user['money']), 2, '.', '');
            $info['user_can_money'] = number_format((float)($user['money']), 2, '.', '');
            $info['order_money'] = number_format((float)($order_money), 2, '.', '');
            $info['use_money_deduction'] = number_format((float)($use_money_deduction), 2, '.', '');
            $info['need_online_pay'] = number_format((float)($order_money), 2, '.', '');
            $info['is_pay_balance'] = 1;
            $info['need_send_code'] = 0;
            $info['pay_bank_discount'] = number_format(0, 2, '.', '');

            $info['selectPayments'] = array(
                'pay_parent_id' => '-1',
                'pay_id' => '-1',
                'pay_name' => '',
                'has_invoice' => 0,
                'no_invoice_message' => '',
                'icon' => '',
                'discount_rule' => '',
                'user_money' => '',
                'prompt'=>''
            );
        }
        else
        {
            return array('code' => 300, 'msg' => '订单未使用余额');
        }

        //积分
        $this->score($order,$type);

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "取消使用余额失败，请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => 'success', 'data' => $info);
    }


    /*
     * 选择支付方式 － 支付
     */
    public function choseCostPayment($params)
    {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'pay_parent_id' => array('required' => array('code' => '300', 'msg' => '请选择支付方式')),
            'pay_id' => array('required' => array('code' => '300', 'msg' => '请选择支付方式')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);

        if (!$this->ci->login->is_login()) {
            return array('code' => 300, 'msg' => '登录超时');
        }

        $uid = $this->ci->login->get_uid();

        if ($params['pay_parent_id'] == '3' && $params['pay_id'] === '3') {
            $params['pay_id'] = '00003';
        }

        if ($params['ispc'] == 1) {
            $pay_array = $this->ci->config->item("pc_pay_array");
        } else {
            $pay_array = $this->ci->config->item("pay_array");
        }
        $pay_parent_id = $params['pay_parent_id'];
        $pay_id = $params['pay_id'];

        //支付方式合法性验证
        if (!isset($pay_array[$pay_parent_id])) {
            return array('code' => '300', 'msg' => '支付方式错误，请返回购物车重新操作');
        }
        $parent = $pay_array[$pay_parent_id]['name'];
        $son_name = '';

        if (!empty($pay_array[$pay_parent_id]['son'])) {
            $son = $pay_array[$pay_parent_id]['son'];
            if (!isset($son[$pay_id])) {
                return array('code' => '300', 'msg' => '支付方式错误，请返回购物车重新操作');
            }
            $son_name = $son[$pay_id];
        } else {
            $pay_id = '0';
        }

        if ($son_name == "") {
            $pay_name = $parent;
        } else {
            $pay_name = $parent . "-" . $son_name;
        }

        //事务 start
        $this->ci->db->trans_begin();

        //获取订单信息
        $type = $this->orderType($params['order_name']);
        $order = $this->orderInfo($params['order_name'],$uid,$type);

        //check
        $check = $this->orderCheck($order,$type);
        if($check['code'] != 200)
        {
            return array('code' => $check['code'], 'msg' => $check['msg']);
        }

        //变更支付方式
        $order_info = $this->changePayment($pay_name, $pay_parent_id, $pay_id,$order['id'],$type);

        //银行支付折扣
        $money = $order_info['money'];
        $old_money = $order_info['money'];
        if ($order_info['new_pay_discount'] > 0) {
            $money = bcadd($order_info['money'], $order_info['new_pay_discount'], 2);
            $order_info['money'] = $money;
        }
        $pay_bank_discount = $this->payDiscount($order_info,$type);
        $new_money = bcsub($money, $pay_bank_discount, 2);
        if (bccomp($new_money, $old_money, 2) != 0)
        {
            $data = array('money' => $new_money);
            $where = array('id' =>$order_info['id']);
            $this->updateOrder($data,$where,$type);
        }

        $info = array();
        $info['need_online_pay'] = number_format((float)($new_money), 2, '.', '');
        $info['pay_bank_discount'] = number_format($pay_bank_discount, 2, '.', '');

        //组合支付验证
        $info['need_send_code'] = $this->sendCheckMsg($order,$type);
        if($info['use_money_deduction'] >0 && $info['need_send_code'] == 1)
        {
            $info['need_send_code'] = 1;
        }
        else
        {
            $info['need_send_code'] = 0;
        }

        $info['order_money'] = number_format((float)($old_money + $order_info['use_money_deduction'] + $order_info['new_pay_discount']), 2, '.', '');

        //积分
        $this->score($order,$type);

        $prompt = $this->showBankText($pay_id,$pay_parent_id);
        $info['selectPayments'] = array(
            'pay_parent_id' => $pay_parent_id,
            'pay_id' => $pay_id,
            'pay_name' => $pay_name,
            'has_invoice' => 0,
            'no_invoice_message' => '',
            'icon' => '',
            'discount_rule' => '',
            'user_money' => '',
            'prompt'=>$prompt
        );

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "选择支付方式失败，请重新选择支付");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => 'success', 'data' => $info);
    }

    /*
     * 验证支付
     */
    public function checkPay($params) {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
            'need_online_pay' => array('required' => array('code' => '500', 'msg' => 'need_online_pay can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //精确捕获
        if(empty($params['platform']))
        {
            $params['platform'] = $params['source'];
        }
        else
        {
            $params['app_platform'] = $params['platform'];
            $params['platform'] = $params['platform'].'|'.$params['version'].'|'.$params['connect_id'];
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();

        if (empty($uid)) {
            $msg = array('code' => 300, 'msg' => '用户登录异常，请重新登录');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        //获取订单信息
        $type = $this->orderType($params['order_name']);
        $order = $this->orderInfo($params['order_name'],$uid,$type);

        if (empty($order)) {
            $msg = array('code' => 300, 'msg' => '用户订单不存在');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        $user = $this->ci->user_model->getUser($uid);

        if($type == 'trade')
        {
            if($order['money'] < 0 || $order['money'] != $params['need_online_pay'])
            {
                $msg = array('code' => 300, 'msg' => '充值订单金额异常');
                $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
                return $msg;
            }

            if($order['has_deal'] == 1)
            {
                $msg = array('code' => 300, 'msg' => '已充值，请勿充值');
                $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
                return $msg;
            }

            return array('code' => 200, 'msg' => 'succ');
        }

        //msg
        $if_send_code = $this->sendCheckMsg($order,$type);
        if ($if_send_code)
        {
            //account-safe
            $orderNum = $this->ci->user_model->showOrderNum($uid);
            if($orderNum > 0)
            {
                $this->ci->load->library("notifyv1");
                $send_params = [
                    "mobile"  => $user['mobile'],
                    "message" => "您的订单提交支付，订单号：".$params['order_name']."，为了确保账号安全，非本人操作或授权操作，请致电400-720-0770",
                ];
                $this->ci->notifyv1->send('sms','send',$send_params);
            }

            if($params['source'] == 'pc' || $params['source'] == 'wap')
            {
                $this->ci->load->library('phpredis');
                $redis = $this->ci->phpredis->getConn();
                $check_code = $redis->get('check_code_'.$params['order_name']);
                if($check_code != 1)
                {
                    $msg = array('code' => 300, 'msg' => '余额支付订单，需短信验证');
                    $this->checkLog($params['order_name'].'|'.$params['platform'].'|'.$uid, $msg);
                    return $msg;
                }
            }
        }

        //group
        if ($order['order_type'] == 7 && $order['pay_parent_id'] != 7) {
            $msg = array('code' => 300, 'msg' => '微信拼团订单，只支持微信支付');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        //times
        if ($order['operation_id'] == 5) {
            $msg = array('code' => 300, 'msg' => '支付订单已超时,请重新下单');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        //status
        if ($order['pay_status'] == 1) {

            if(strcmp($params['version'],'4.0.0') >= 0)
            {
                $msg = array('code' => 311, 'msg' => '订单已支付,请勿重复支付订单');
            }
            else
            {
                $msg = array('code' => 300, 'msg' => '订单已支付,请勿重复支付订单');
            }
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        if ($order['pay_status'] == 2) {

            if(strcmp($params['version'],'4.0.0') >= 0)
            {
                $msg = array('code' => 312, 'msg' => '支付订单到账确认中,请耐心等待');
            }
            else
            {
                $msg = array('code' => 300, 'msg' => '支付订单到账确认中,请耐心等待');
            }

            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        //payment
        if ($order['pay_parent_id'] == 4 || $order['pay_parent_id'] == 6) {
            $msg = array('code' => 300, 'msg' => '支付方式异常,请返回订单详情');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        if ($order['pay_parent_id'] == 11 && $params['app_platform'] != 'IOS') {
            $msg = array('code' => 300, 'msg' => '请重新选择支付方式');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        //check all
        if ($order['pay_parent_id'] == 5 && $order['use_money_deduction'] > 0) {
            $msg = array('code' => 300, 'msg' => '余额支付异常,请返回订单详情');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        if ($order['pay_parent_id'] == 5 && $order['money'] > $user['money']) {
            $msg = array('code' => 300, 'msg' => '支付余额不足,请返回订单详情');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        if ($order['pay_parent_id'] == 5 && $params['need_online_pay'] > 0) {
            $msg = array('code' => 300, 'msg' => '还需支付金额异常,请返回订单详情');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        //check section
        if ($order['pay_parent_id'] != 5 && $params['need_online_pay'] <= 0) {
            $msg = array('code' => 300, 'msg' => '支付抵扣金额异常,请返回订单详情');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        //price
        if ($order['pay_parent_id'] != 5 && ($params['need_online_pay'] + $order['new_pay_discount'] + $order['use_money_deduction']) != ($order['money'] + $order['new_pay_discount'] + $order['use_money_deduction'])) {
            $msg = array('code' => 300, 'msg' => '支付订单价格异常,请返回订单详情');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        if ($order['pay_parent_id'] != 5 && $order['use_money_deduction'] > 0 && ($params['need_online_pay'] + $order['new_pay_discount'] + $user['money']) != ($order['money'] + $order['new_pay_discount'] + $order['use_money_deduction'])) {
            $msg = array('code' => 300, 'msg' => '支付订单价格异常,请返回订单详情');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        if ($order['money'] <= 0) {
            $msg = array('code' => 300, 'msg' => '支付订单价格异常,请返回订单详情');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        //bank
        $bankMsg = $this->bankPro($order,$type);
        if($bankMsg['code'] != 200)
        {
            return $bankMsg;
        }

        return array('code' => 200, 'msg' => 'succ');
    }

    /*
     * 余额支付验证码 - 全额
     */
    public function checkBalanceCode($params) {

        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'verification_code' => array('required' => array('code' => '500', 'msg' => '请填写验证码')),
            'ver_code_connect_id' => array('required' => array('code' => '500', 'msg' => '请填写验证码')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        $session_id = $params['ver_code_connect_id'];
        $ver_code = $params['verification_code'];

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();

        if (empty($uid)) {
            return array('code' => 300, 'msg' => '用户登录异常，请重新登录');
        }

        $user = $this->ci->user_model->getUser($uid);
        $mobile = $user['mobile'];

        $this->ci->session->sess_id = $session_id;
        $this->ci->session->sess_read();
        $ver_code_session = $this->ci->session->userdata;

        $this->ci->load->model('ver_error_model');
        $this->ci->ver_error_model->setFilter($session_id, $mobile);
        $ver_error_res = $this->ci->ver_error_model->setVer();
        if ($ver_error_res == false) {
            return array('code' => '601', 'msg' => '短信验证码已过期，请重新发送获取验证码');
        }

        $userdata = unserialize($ver_code_session['user_data']);
        if (!isset($userdata['verification_code'])) {
            return array('code' => '601', 'msg' => '验证码已过期，请输入最新收到的验证码');
        }
        if ($userdata['verification_code'] != md5($mobile . $ver_code)) {
            return array('code' => '602', 'msg' => '验证码错误');
        } else {
            $this->ci->ver_error_model->setVer(1);
            if(!empty($params['order_name']))
            {
                $this->ci->load->library('phpredis');
                $redis = $this->ci->phpredis->getConn();

                $redis->set('check_code_'.$params['order_name'],1);
            }
            return array('code' => '200', 'msg' => '验证成功');
        }
    }


    /*
     * 订单发票
     */
    private function orderInvoice($info,$type)
    {
        if($type == 'order')
        {
            $invoice_info = $this->ci->order_model->get_order_invoice($info['id'], 0);
        }
        else if($type == 'postage')
        {
            $invoice_info = array();
        }
        else if($type == 'trade')
        {
            $invoice_info = array();
        }
        else if($type == 'express')
        {
            $invoice_info = array();
        }
        else
        {
            $invoice_info = $this->ci->b2o_parent_order_model->get_order_invoice($info['id'], 0);
        }

        return $invoice_info;
    }

    /*
     * 订单发票
     */
    private function updateOrderInvoice($data,$where,$type)
    {
        if($type == 'order')
        {
            $this->ci->order_model->update_order_invoice($data,$where);
        }
        else if($type == 'postage')
        {

        }
        else if($type == 'express')
        {

        }
        else if($type == 'trade')
        {

        }
        else
        {
            $this->ci->b2o_parent_order_model->update_order_invoice($data,$where);
        }
    }

    /*
     * 更新订单信息
     */
    private function updateOrder($data,$where,$type)
    {
        if($type == 'order')
        {
            $where['pay_status'] = 0;
            $this->ci->order_model->update($data,$where);
        }
        else if($type == 'postage')
        {
            $this->ci->order_postage_model->update_postage($data,$where);
        }
        else if($type == 'express')
        {
            $this->ci->order_express_model->update_express($data,$where);
        }
        else if($type == 'trade')
        {

        }
        else
        {
            $where['pay_status'] = 0;
            $this->ci->b2o_parent_order_model->update($data,$where);
        }
    }

    /*
     * 变更支付方式
     */
    private function changePayment($pay_name, $pay_parent_id, $pay_id, $order_id,$type)
    {
        if($type == 'order')
        {
            $this->ci->order_model->set_ordre_payment($pay_name, $pay_parent_id, $pay_id, $order_id);
            $order_info = $this->ci->order_model->getInfoById($order_id);
        }
        else if($type == 'postage')
        {
            $this->ci->order_postage_model->set_ordre_payment($pay_name, $pay_parent_id, $pay_id, $order_id);
            $order_info = $this->ci->order_postage_model->getInfoById($order_id);
        }
        else if($type == 'express')
        {
            $this->ci->order_express_model->set_ordre_payment($pay_name, $pay_parent_id, $pay_id, $order_id);
            $order_info = $this->ci->order_express_model->getInfoById($order_id);
        }
        else if($type == 'trade')
        {
            $order_info  = $this->ci->trade_model->dump(array('id' =>$order_id));
        }
        else
        {
            $this->ci->b2o_parent_order_model->set_ordre_payment($pay_name, $pay_parent_id, $pay_id, $order_id);
            $order_info = $this->ci->b2o_parent_order_model->getInfoById($order_id);
        }

        return $order_info;
    }

    /*
     * 银行合作卡券
     */
    private function bankPro($info,$type)
    {
        $msg = array('code'=>'200','msg'=>'succ');

        if($type == 'order')
        {
            if($info['pay_parent_id'] == 8 && date('w') == 6 && date('Y-m-d') <= '2017-10-28'){
                $res = $this->ci->order_model->checkCardtypeProd($info['id']);
                //存在券卡类商品
                if($res){
                    $msg = array('code' => 300, 'msg' => '券卡类商品，不支持建行活动，请选择其他支付方式');
                    $this->checkLog($info['order_name'], $msg);
                }
            }
        }
        else if($type == 'postage')
        {

        }
        else if($type == 'express')
        {

        }
        else if($type == 'trade')
        {

        }
        else
        {
            if($info['pay_parent_id'] == 8 && date('w') == 6 && date('Y-m-d') <= '2017-10-28'){
                $res = $this->ci->b2o_parent_order_model->checkCardtypeProd($info['id']);
                //存在券卡类商品
                if($res){
                    $msg = array('code' => 300, 'msg' => '券卡类商品，不支持建行活动，请选择其他支付方式');
                    $this->checkLog($info['order_name'], $msg);
                }
            }
        }
        return $msg;
    }

    /*
    * 支付
    */
    private function orderPay($info,$type,$params)
    {
        $user = $this->ci->user_model->getUser($info['uid']);
        if($user['money'] >= 0)
        {
            $info['user_money'] = $user['money'];
        }
        else
        {
            $info['user_money'] = '0.00';
        }
        $info['is_can_balance'] = $this->product($info,$type);
        $info['balance_text'] = '余额不支持购买充值卡';
        $info = $this->handel($info,$type);
        $info = $this->payments($info,$type,$params);
        $info['order_desc'] = $this->getDesc($info,$type);
        $this->score($info,$type);

        return $info;
    }

    /*
    * 构建收银台
    */
    private function handel($info,$type)
    {
        $user_money  = $info['user_money'];
        $order_money = $info['money'] + $info['use_money_deduction']+$info['new_pay_discount'];
        $pay_bank_discount = $info['new_pay_discount'];

        if ($user_money == '0.00')  //无余额
        {
            $info['money'] = number_format($order_money, 2, '.', '');
            $pay_bank_discount = $this->payDiscount($info,$type);
            $data = array(
                'money' => $order_money - $pay_bank_discount,
                'use_money_deduction' => $info['user_money']
            );
            $where = array(
                'order_name' => $info['order_name'],
                'order_status' => 1,
            );
            $this->updateOrder($data, $where,$type);

            $info['user_can_money'] = number_format('0', 2, '.', '');
            $info['need_online_pay'] = number_format($order_money - $pay_bank_discount, 2, '.', '');
            $info['is_pay_balance'] = 0;
            $info['need_send_code'] = 0;
            $info['pay_bank_discount'] = number_format($pay_bank_discount, 2, '.', '');
            $info['money'] = number_format($order_money, 2, '.', '');
        }
        else if($user_money > 0)  //用户余额 > 0
        {
            if($user_money >= $order_money)  //是否可以全额抵扣
            {
                if ($info['invoice_money'] > 0 && $info['pay_parent_id'] == 5)
                {
                    //取消发票
                    $order_money = $order_money - $info['invoice_money'];
                    $data = array(
                        'use_money_deduction' => 0.00,
                        'new_pay_discount' => 0.00,
                        'money' => $order_money,
                        'invoice_money' => 0.00
                    );
                    $invoice = array(
                        'is_valid' => 0
                    );
                    $invoice_where = array(
                        'order_id' => $info['id']
                    );
                    $this->updateOrderInvoice($invoice, $invoice_where,$type);
                }
                else
                {
                    $data = array(
                        'use_money_deduction' => 0.00,
                        'new_pay_discount' => 0.00,
                        'money' => $order_money
                    );
                }
                $where = array(
                    'order_name' => $info['order_name'],
                    'order_status' => 1,
                );
                $this->updateOrder($data, $where,$type);

                $info['user_can_money'] = number_format(($info['user_money'] - $info['money']), 2, '.', '');

                if ($info['pay_parent_id'] == 5)
                {
                    $info['need_online_pay'] = number_format(0, 2, '.', '');
                    $info['pay_bank_discount'] = number_format(0, 2, '.', '');
                    $info['need_send_code'] = $this->sendCheckMsg($info,$type);
                }
                else
                {
                    $info['money'] = number_format($order_money, 2, '.', '');
                    $pay_bank_discount = $this->payDiscount($info,$type);
                    $data = array(
                        'money' => $order_money - $pay_bank_discount
                    );
                    $where = array(
                        'order_name' => $info['order_name'],
                        'order_status' => 1,
                    );
                    $this->updateOrder($data, $where,$type);

                    $info['need_online_pay'] = number_format($order_money - $pay_bank_discount, 2, '.', '');
                    $info['need_send_code'] = 0;
                    $info['pay_bank_discount'] = number_format($pay_bank_discount, 2, '.', '');
                }
                $info['is_pay_balance'] = 1;
                $info['money'] = number_format($order_money, 2, '.', '');
            }
            else
            {
                $info['money'] = number_format($order_money-$info['user_money'], 2, '.', '');
                $pay_bank_discount = $this->payDiscount($info,$type);

                if ($info['is_can_balance'] == 1)
                {
                    $data = array(
                        'use_money_deduction' => $info['user_money'],
                        'money' => ($order_money - $pay_bank_discount) - $info['user_money']
                    );
                    $where = array(
                        'order_name' => $info['order_name'],
                        'order_status' => 1,
                    );
                    $this->updateOrder($data, $where,$type);

                    $info['use_money_deduction'] = number_format($info['user_money'], 2, '.', '');
                    $info['user_can_money'] = number_format('0', 2, '.', '');
                    $info['need_online_pay'] = number_format($order_money - $info['user_money'] - $pay_bank_discount, 2, '.', '');
                    $info['is_pay_balance'] = 0;

                    //组合支付验证
                    $info['need_send_code'] = $this->sendCheckMsg($info,$type);
                    if($info['use_money_deduction'] >0 && $info['need_send_code'] == 1)
                    {
                        $info['need_send_code'] = 1;
                    }
                    else
                    {
                        $info['need_send_code'] = 0;
                    }
                }
                else
                {
                    $info['use_money_deduction'] = number_format('0', 2, '.', '');
                    $info['user_can_money'] = number_format('0', 2, '.', '');
                    $info['need_online_pay'] = number_format($order_money - $pay_bank_discount, 2, '.', '');
                    $info['is_pay_balance'] = 0;
                    $info['need_send_code'] = 0;
                }
                $info['pay_bank_discount'] = number_format($pay_bank_discount, 2, '.', '');
                $info['money'] = number_format($order_money, 2, '.', '');
            }
        }

        return $info;
    }

    /*
    * 余额短信
    */
    private function sendCheckMsg($info,$type)
    {
        $need_send_code = 0;
        $cart_array = array();

        if($type == 'order')
        {
            $order_address_info = $this->ci->order_model->get_order_address($info['address_id']);
            $if_send_code = $this->ci->order_model->checkSendCode($cart_array, $info['uid'], $info['pay_parent_id'], $order_address_info, $info['id']);
            if ($if_send_code) {
                $need_send_code = 1;
            }
        }
        else if($type == 'postage')
        {
            $need_send_code = 0;
        }
        else if($type == 'express')
        {
            $need_send_code = 0;
        }
        else if($type == 'trade')
        {
            $need_send_code = 0;
        }
        else
        {
            $order_address_info = $this->ci->b2o_parent_order_model->get_order_address($info['address_id']);
            $if_send_code = $this->ci->b2o_parent_order_model->checkSendCode($cart_array, $info['uid'], $info['pay_parent_id'], $order_address_info, $info['id']);
            if ($if_send_code) {
                $need_send_code = 1;
            }
        }

        return $need_send_code;
    }

    /*
    * 支付折扣
    */
    private function payDiscount($info,$type)
    {
        $pay_bank_discount = 0;
        if($type == 'order')
        {
            $order_products = $this->ci->order_product_model->getProductsByOrderId($info['id']);
            $pids = array();
            foreach ($order_products as $key => $value) {
                if ($value['category'] == 1) $pids[] = $value['sku'];
            }
            switch ($info['channel']) {
                case '6':
                    $source = 'app';
                    break;
                case '2':
                    $source = 'wap';
                    break;
                case '1':
                    $source = 'pc';
                    break;
                default:
                    $source = 'pc';
                    break;
            }
            $o_area_info = $this->ci->order_model->getIorderArea($info['id']);
            $pay_bank_discount = $this->ci->pay_discount_model->set_order_pay_discount($info['pay_parent_id'], $info['pay_id'], $info['money'], $pids, $info['id'], $source, $o_area_info['province'], $info['uid'], $info['order_type']);
        }
        else if($type == 'postage')
        {
            $pay_bank_discount = 0;
        }
        else if($type == 'express')
        {
            $pay_bank_discount = 0;
        }
        else if($type == 'trade')
        {
            $pay_bank_discount = 0;
        }
        else
        {
            $order_products = $this->ci->b2o_parent_order_product_model->getProductsByOrderId($info['id']);
            $pids = array();
            foreach ($order_products as $key => $value) {
                if ($value['category'] == 1) $pids[] = $value['sku'];
            }
            switch ($info['channel']) {
                case '6':
                    $source = 'app';
                    break;
                case '2':
                    $source = 'wap';
                    break;
                case '1':
                    $source = 'pc';
                    break;
                default:
                    $source = 'pc';
                    break;
            }
            $o_area_info = $this->ci->b2o_parent_order_model->getIorderArea($info['id']);
            $pay_bank_discount = $this->ci->pay_discount_model->set_order_pay_discount($info['pay_parent_id'], $info['pay_id'], $info['money'], $pids,0, $source, $o_area_info['province'], $info['uid'], $info['order_type'],$info['id']);
        }

        return $pay_bank_discount;
    }

    /*
     * 支付方式
     */
    private function payments($info,$type,$params)
    {
        //payments
        $province = '106092';
        $this->ci->load->bll($params['source'] . '/region');
        $obj = 'bll_' . $params['source'] . '_region';
        $send_time_params = array(
                'service' => 'region.getPay',
                'province_id' => $province,
                'connect_id' => $params['connect_id'],
                'source' => $params['source'],
                'version' => $params['version'],
                'platform' => $params['platform'],
        );
        $pay_arr = $this->ci->$obj->getPay($send_time_params);
        unset($pay_arr['fday']);
        unset($pay_arr['offline']);

        if($type == 'trade' || $type == 'express')
        {
            foreach($pay_arr['online']['pays'] as $k=>$v)
            {
                if(!in_array($v['pay_parent_id'],array(1,7)))
                {
                    unset($pay_arr['online']['pays'][$k]);
                }
            }
        }

        //微信平台
        if($params['channel'] == 'wechat')
        {
            foreach($pay_arr['online']['pays'] as $k=>$v)
            {
                if(!in_array($v['pay_parent_id'],array(7)))
                {
                    unset($pay_arr['online']['pays'][$k]);
                }
            }
        }

        //支付宝平台
        if($params['channel'] == 'alipay')
        {
            foreach($pay_arr['online']['pays'] as $k=>$v)
            {
                if(!in_array($v['pay_parent_id'],array(1)))
                {
                    unset($pay_arr['online']['pays'][$k]);
                }
            }
        }

        //支付提货券
        if($info['pro_card_money'] >0)
        {
            $pro_card_number = $info['pro_card_number'];
            if(!empty($pro_card_number))
            {
                $this->ci->load->model('pro_card_model');
                $pro_card = $this->ci->pro_card_model->dump(array('card_number' =>$pro_card_number));
                $pay_method = json_decode($pro_card['pay_method'],true);
                if(!empty($pay_method))
                {
                    $pay_parent = array_column($pay_method,'pay_parent_id');
                    foreach($pay_arr['online']['pays'] as $k=>$v)
                    {
                        if(!in_array($v['pay_parent_id'],$pay_parent))
                        {
                            unset($pay_arr['online']['pays'][$k]);
                        }
                    }
                }
            }
            $pay_arr['online']['pays'] = array_merge($pay_arr['online']['pays']);
        }

        $info['payments'] = $pay_arr;

        //select
        if($info['pay_parent_id'] == 5 && $info['use_money_deduction'] > 0)
        {
            $info['selectPayments'] = array(
                'pay_parent_id' => '-1',
                'pay_id' => '-1',
                'pay_name' => '',
                'has_invoice' => 0,
                'no_invoice_message' => '',
                'icon' => '',
                'discount_rule' => '',
                'user_money' => '',
                'prompt'=>''
            );
        }
        else if($type == 'trade')
        {
            $info['selectPayments'] = array(
                'pay_parent_id' => '7',
                'pay_id' => '0',
                'pay_name' => '微信支付',
                'has_invoice' => 0,
                'no_invoice_message' => '',
                'icon' => '',
                'discount_rule' => '',
                'user_money' => '',
                'prompt'=>''
            );
        }
        else
        {
            $prompt = $this->showBankText($info['pay_id'],$info['pay_parent_id']);
            $info['selectPayments'] = array(
                'pay_parent_id' => $info['pay_parent_id'],
                'pay_id' => $info['pay_id'],
                'pay_name' => $info['pay_name'],
                'has_invoice' => 0,
                'no_invoice_message' => '',
                'icon' => '',
                'discount_rule' => '',
                'user_money' => '',
                'prompt'=>$prompt
            );
        }

        return $info;
    }

    /*
     * 积分
     */
    private function score($info,$type)
    {
        if($type == 'order')
        {
            $order = $this->ci->order_model->dump(array('order_name' =>$info['order_name'], 'uid' => $info['uid']));

            //fix 积分比例
            $pro_list = $this->ci->order_product_model->getOrderProductList($order['id'],'product.id,product.iscard,score');
            $order_score = 0;
            if(count($pro_list) >0)
            {
                foreach($pro_list as $k=>$v)
                {
                    $order_score += $v['score'];
                }
            }
            //$order_score = $this->ci->user_model->dill_score_new($order['money'] - $order['method_money'], $order['uid']);
            if ($order_score < 0) {
                $order_score = 0;
            }
            if ($order['pay_parent_id'] == 5) {
                //$order_score = 0;
            }
            $set = array(
                'score' => $order_score
            );
            $where = array(
                'order_name' => $order['order_name'],
                'order_status' => 1,
            );
            $this->ci->order_model->update_order($set,$where);
        }
        else if($type == 'postage')
        {

        }
        else if($type == 'express')
        {

        }
        else if($type == 'trade')
        {

        }
        else
        {
            $order = $this->ci->b2o_parent_order_model->dump(array('order_name' =>$info['order_name'], 'uid' => $info['uid']));

            //fix 积分比例
            $pro_list = $this->ci->b2o_parent_order_product_model->getOrderProductList($order['id'],'product.id,product.iscard,score');
            $order_score = 0;
            if(count($pro_list) >0)
            {
                foreach($pro_list as $k=>$v)
                {
                    $order_score += $v['score'];
                }
            }
            //$order_score = $this->ci->user_model->dill_score_new($order['money'] - $order['method_money'], $order['uid']);
            if ($order_score < 0) {
                $order_score = 0;
            }
            if ($order['pay_parent_id'] == 5) {
                //$order_score = 0;
            }
            $set = array(
                'score' => $order_score
            );
            $where = array(
                'order_name' => $order['order_name'],
                'order_status' => 1,
            );
            $this->ci->b2o_parent_order_model->update_order($set,$where);
        }
    }

    /*
     * 特殊商品 － 不支持余额
     */
    private function product($info,$type)
    {
        $is_can_balance = 1;

        if($type == 'order')
        {
            $pro_list = $this->ci->order_product_model->getOrderProductList($info['id']);
            if (!empty($pro_list)) {
                foreach ($pro_list as $key => $row) {
                    if ($row['iscard'] == 1) {
                        $is_can_balance = 0;
                    }
                }
            } else {
                $is_can_balance = 0;
            }
        }
        else if($type == 'postage')
        {
            $is_can_balance = 1;
        }
        else if($type == 'express')
        {
            $is_can_balance = 0;
        }
        else if($type == 'trade')
        {
            $is_can_balance = 0;
        }
        else
        {
            $pro_list = $this->ci->b2o_parent_order_product_model->getOrderProductList($info['id']);
            if (!empty($pro_list)) {
                foreach ($pro_list as $key => $row) {
                    if ($row['iscard'] == 1) {
                        $is_can_balance = 0;
                    }
                }
            } else {
                $is_can_balance = 0;
            }
        }

        //支付提货券
        if($info['pro_card_money'] >0)
        {
            $is_can_balance = 0;
        }

        return $is_can_balance;
    }


    /*
    * 订单信息
    */
    private function orderInfo($order_name,$uid,$type)
    {
        if($type == 'order')
        {
            $order = $this->ci->order_model->dump(array('order_name' =>$order_name, 'uid' => $uid));
        }
        else if($type == 'postage')
        {
            $order = $this->ci->order_postage_model->dump(array('order_name' =>$order_name, 'uid' => $uid));
        }
        else if($type == 'express')
        {
            $order = $this->ci->order_express_model->dump(array('order_name' =>$order_name, 'uid' => $uid));
        }
        else if($type == 'trade')
        {
            $order = $this->ci->trade_model->dump(array('trade_number' =>$order_name, 'uid' => $uid));
        }
        else
        {
            $order = $this->ci->b2o_parent_order_model->dump(array('order_name' =>$order_name, 'uid' => $uid));
        }

        return $order;
    }

    /*
    * 是否可以发起收银台
    */
    private function orderCheck($info,$type)
    {
        $res = array('code'=>200,'msg'=>'succ');
        if(empty($info))
        {
            $res['code'] = 300;
            $res['msg'] = '用户订单异常，请重新支付';
            return $res;
        }

        //0元支付订单
        if($info['money'] == '0.00' && $info['pay_status'] == 1)
        {
            $res['code'] = 317;
            $res['msg'] = '订单已支付，到账确认中，请稍后';
            return $res;
        }

        if($type == 'trade')
        {
            if($info['has_deal'] == 1)
            {
                $res['code'] = 300;
                $res['msg'] = '已经充值成功，请勿重复充值';
                return $res;
            }
        }
        else
        {
            if($info['pay_status'] == 1)
            {
                $res['code'] = 311;
                $res['msg'] = '订单已支付，请勿重复支付';
                return $res;
            }

            if($info['pay_status'] == 2)
            {
                $res['code'] = 312;
                $res['msg'] = '订单等待对账确认中，请稍后';
                return $res;
            }

            if($info['order_status'] != 1)
            {
                $res['code'] = 300;
                $res['msg'] = '订单状态异常，请重新支付';
                return $res;
            }

            if($info['pay_parent_id'] == 4 || $info['pay_parent_id'] == 6)
            {
                $res['code'] = 300;
                $res['msg'] = '订单支付方式错误，请重选支付方式';
                return $res;
            }

            $check_money = $this->ci->user_model->check_money_identical($info['uid']);
            if ($check_money === false) {
                $this->ci->user_model->freeze_user($info['uid']);
                $res['code'] = 300;
                $res['msg'] = '您的账号异常，已被冻结，请联系客服';
                return $res;
            }
        }

        return $res;
    }

    /*
    * 构建结构
    */
    private function orderSet($info,$type)
    {
        $info['order_id'] = $info['id'];
        if($type == 'trade')
        {
            $info['order_name'] = $info['trade_number'];
        }
        //$info['pay_status_key'] = $info['pay_status'];
        //$info['order_status_key'] = $info['order_status'];

        return $info;
    }

    /*
    * 支付日志
    */
    private function checkLog($order_name, $data) {
        $data['order_name'] = $order_name;
        $this->ci->load->library('fdaylog');
        $db_log = $this->ci->load->database('db_log', TRUE);
        $this->ci->fdaylog->add($db_log, 'paylog', json_encode($data));
    }

    /*
    * 支付方式－文案
    */
    private function showBankText($pay_id,$pay_parent_id)
    {
        $prompt = '';
        if($pay_id == '00108') //一网通
        {
            $prompt = '(支付优惠会在合作方页面扣除)';
        }

        if($pay_parent_id == 8)  //在线银联
        {
            $prompt = '(支付优惠会在合作方页面扣除)';
        }

        if($pay_id == '00105') //广发
        {
            $prompt = '(支付优惠以实际支付为准)';
        }

        if($pay_id == '00106') //花旗
        {
            $prompt = '(支付优惠以实际支付为准)';
        }

        return $prompt;
    }


    /*
     * 支持 - 订单类型
     */
    private function orderType($order_name)
    {
        $type = 'order';
        $bs = mb_substr($order_name,0,1);
        if($bs == 'P')
        {
            $type = '';
        }
        else if($bs == 'Y')
        {
            $type = 'postage';
        }
        else if($bs == 'T')
        {
            $type = 'trade';
        }
        else if($bs == 'M')
        {
            $type = 'express';
        }

        return $type;
    }

    /*
    * 支付成功 － 订单信息
    */
    public function orderSuccess($params)
    {
        //必要参数验证
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();
        if (empty($uid)) {
            return array('code' => 300, 'msg' => '用户登录异常，请重新登录');
        }

        //订单
        $type = $this->orderType($params['order_name']);
        $order = $this->orderInfo($params['order_name'],$uid,$type);

        if(empty($order))
        {
            return array('code'=>300,'msg'=>'支付订单异常，请返回重试');
        }

        if($order['pay_status'] == 0)
        {
            //return array('code'=>300,'msg'=>'订单未支付成功，请返回重试');
        }

        if($type == 'order')
        {
            $order_address_info = $this->ci->order_model->get_order_address($order['address_id']);
        }
        else
        {
            $order_address_info = $this->ci->b2o_parent_order_model->get_order_address($order['address_id']);
        }

        $info = array();
        $info['id'] = $order['id'];
        $info['uid'] = $order['uid'];
        $info['order_name'] = $order['order_name'];
        $info['money'] = $order['money'];
        $info['score'] = $order['score'];
        $info['name'] = $order_address_info['name'];
        $info['mobile'] = $order_address_info['mobile'];
        
        $info['province'] = $order_address_info['province']['name'];
        $info['city'] = $order_address_info['city']['name'];
        $info['area'] = $order_address_info['area']['name'];
        $info['address'] = $info['province']. $info['city'].$info['area'].$order_address_info['address'];

        //广告
        $this->ci->load->bll('ad');
        $ad = array_merge(array('position'=>7),$params);
        $adv_list = $this->ci->bll_ad->getPositionBanner($ad);
        if($adv_list['code'] == '200')
        {
            $info['adv_list'] = $adv_list['data'];
        }
        else
        {
            $info['adv_list'] = array();
        }

        //弹出窗
        $ad_pop = array_merge(array('position'=>8),$params);
        $adv_pop = $this->ci->bll_ad->getPositionBanner($ad_pop);
        if($adv_pop['code'] == '200')
        {
            $info['adv_pop'] = $adv_pop['data'];
        }
        else
        {
            $info['adv_pop'] = array();
        }

        return array('code'=>200,'msg'=>'succ','data'=>$info);
    }

    /*
     * app - 第三方支付平台
     */
    private function getDesc($info,$type)
    {
        if($type == 'order')
        {
            $products = $this->ci->order_product_model->get_products($info['id']);
            $p_name = $products[0]['product_name'];
            $p_count =0;
            foreach($products as $k=>$v)
            {
                $p_count += $v['qty'];
            }
            $desc = $p_name.'等'.$p_count.'个商品';
        }
        else if($type == 'postage')
        {
            $desc = '邮费特权卡';
        }
        else if($type == 'express')
        {
            $desc = '发票运费';
        }
        else if($type == 'trade')
        {
            $desc = '在线充值';
        }
        else
        {
            $products = $this->ci->b2o_parent_order_product_model->get_products($info['id']);
            $p_name = $products[0]['product_name'];
            $p_count =0;
            foreach($products as $k=>$v)
            {
                $p_count += $v['qty'];
            }
            $desc = $p_name.'等'.$p_count.'个商品';
        }

        return $desc;
    }

    /*
    * 银行秒杀排队
    */
    public function orderLimit()
    {
        $res = array('code'=>'200','msg'=>'succ');

        $bank_order_limit = $this->ci->config->item('bank_paydesk_limit');
        $is_open = $bank_order_limit['open'];
        $limit_count = $bank_order_limit['count'];
        $min = $bank_order_limit['min'];
        $max = $bank_order_limit['max'];
        $time = $bank_order_limit['time'];
        $rand = rand($min,$max);

        if($is_open == 1)
        {
            $this->ci->load->library('orderredis');
            $redis = $this->ci->orderredis->getConn();
            if($redis != false)
            {
                $ordercount = $redis->get('paydesk_limit_count');
                if($rand != 5 && $ordercount >= $limit_count && $is_open == 1)
                {
                    $res = array('code'=>'321','msg'=>'当前抢购人数太多啦，系统正在奋力处理中，请稍后重试~','data'=>array('time'=>$time));
                }
            }
        }

        return $res;
    }
}
