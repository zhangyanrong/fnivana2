<?php
namespace bll;

/**
 * 订单退款相关接口
 */
class Refund
{
    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
        $this->ci->load->model('order_model');
    }

    /**
    * @api {post} / 取消订单(退款)
    * @apiGroup refund
    * @apiName send
    * 
    * @apiParam {String} order_name 订单号
    * @apiParam {String} connect_id 登录状态
    *
    * @apiSampleRequest /api/test?service=refund.send
    */ 
    public function send($params){
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null'))
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //用户
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();
        if (empty($uid)) {
            return array('code' => 300, 'msg' => '用户登录异常，请重新登录');
        }

        //订单
        $order = $this->ci->order_model->dump(
            array(
                'order_name' => $params['order_name'],
                'uid' => $uid,
                'order_type' =>9,
            )
        );
        if(empty($order)) {
            return array('code' => 300, 'msg' => '订单类型不支持退款');
        }

        //未支付
        if($order['pay_status'] == 0)
        {
            //return array('code' => 300, 'msg' => '未支付，未到账订单不支持退款');
            $this->ci->load->bll('order');
            $rs = $this->ci->bll_order->orderCancel($params);
            return $rs;
        }

        //到账中
        if($order['pay_status'] == 2)
        {
            return array('code' => 300, 'msg' => '等待到账确认中，请稍后取消订单');
        }

        //已取消
        if ($order['operation_id'] == 5) {
            return array('code' => 300, 'msg' => '已取消订单不支持退款');
        }

        //已出库
        if (in_array($order['operation_id'],array(2,3,4,6,7,8,9))) {
            return array('code' => 300, 'msg' => '订单正在配送不支持退款');
        }

        //金额
        if ($order['money'] <= 0) {
            return array('code' => 300, 'msg' => '订单不支持退款');
        }

        //领取状态
        $rs = array('code' => '200', 'msg' =>'succ');
        if($order['lyg'] == 9)  //已领取
        {
            $this->ci->load->bll('order');
            $rs = $this->ci->bll_order->orderCancel($params);
        }
        else
        {
            $orderNo = $order['order_name'];
            $custId = $order['uid'];
            $pay_id = $order['pay_id'];
            $pay_parent_id = $order['pay_parent_id'];
            $transId = $order['trade_no'];
            $feeAmount = $order['money'];
            $tolAmount = number_format((float)($order['money'] + $order['use_money_deduction']),2,'.','');
            $online_pay = $this->ci->config->item("oms_online_pay");
            $payPf = $online_pay[$pay_parent_id]['children_platform_id'][$pay_id] ? $online_pay[$pay_parent_id]['children_platform_id'][$pay_id] : $online_pay[$pay_parent_id]['platform_id'];
            $payWay = $online_pay[$pay_parent_id]['way_id'];

            $this->ci->load->model('user_model');
            $user = $this->ci->user_model->dump(array('id' =>$custId));
            $cellPhone = empty($user['mobile']) ? $user['email'] : $user['mobile'];
            $custName = $user['username'];

            $data[] = array(
                'orderNo'=>$orderNo,    //订单号
                'feeAmount'=>$feeAmount,  //退款金额
                'payPf'=>$payPf,      //付款平台
                'payWay'=>$payWay,     //付款方式
                'rPayPf'=>$payPf,     //退款平台
                'rPayWay'=>$payWay,    //退款付款方式
                'transId'=>$transId,    //第三方支付平台的流水号
                'cellPhone'=>$cellPhone,  //联系方式
                'custName'=>$custName,   //联系人
                'renId'=>'',      //退款id
                'tolAmount'=>$feeAmount,  //订单总金额
                'refSource'=>'12',  //12-无单退款
                'custId'=>$custId,     //用户id
                'sapCode'=>''     //o2o门店编码
            );

            if($order['use_money_deduction'] >0){
                $data[] = array(
                    'orderNo'=>$orderNo,    //订单号
                    'feeAmount'=>$order['use_money_deduction'],  //退款金额
                    'payPf'=>'',      //付款平台
                    'payWay'=>9,     //付款方式
                    'rPayPf'=>'',     //退款平台
                    'rPayWay'=>9,    //退款付款方式
                    'transId'=>'',    //第三方支付平台的流水号
                    'cellPhone'=>$cellPhone,  //联系方式
                    'custName'=>$custName,   //联系人
                    'renId'=>'',      //退款id
                    'tolAmount'=>$order['use_money_deduction'],  //订单总金额
                    'refSource'=>'12',  //12-无单退款
                    'custId'=>$custId,     //用户id
                    'sapCode'=>''     //o2o门店编码
                );
            }

            $re_push = $this->push($data);

            if($re_push['result'] == '1')
            {
                //退余额
                $this->ci->db->trans_begin();
                // if($order['use_money_deduction'] >0)
                // {
                //     //部分余额支付
                //     $this->ci->load->bll('user', null, 'bll_user');
                //     $this->ci->bll_user->deposit_recharge($order['uid'], $order['use_money_deduction'], "订单{$order['order_name']}取消，退回帐户余额", $order['order_name']);

                //     //消息中心
                //     $this->ci->load->bll('msg');
                //     $this->ci->bll_msg->addMsgRefund(array('order_name'=>$order['order_name'],'content'=>"订单{$order['order_name']}取消,退回帐户余额"));
                // }

                //取消订单
                $this->ci->order_model->update(array('operation_id' => 5, 'last_modify' => time()), array('id' => $order['id']));

                if ($this->ci->db->trans_status() === FALSE) {
                    $this->ci->db->trans_rollback();
                    $rs = array("code" => "300", "msg" => "退款失败");
                } else {
                    $this->ci->db->trans_commit();
                }
            }
            else
            {
                $rs = array("code" => "300", "msg" => "退款订单失败");
            }
        }
        return $rs;
    }


    /*
     * oms - 推送退款订单
     */
    private function push($data)
    {
        $this->ci->load->bll('rpc/request');
        $log = array(
            'rpc_desc' => '推送退款订单',
            'obj_type' => 'push_order_refund',
            'obj_name' => $data['orderNo'],
        );
        $this->ci->bll_rpc_request->set_rpc_log($log);
        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_ORDER_REFUND_URL,$data,'POST',6,base64_decode(REFUND_AES_SECRET),REFUND_SHA_SECRET);

        return $rs;
    }

}
