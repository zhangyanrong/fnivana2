<?php
namespace bll;

class Paycenter {

    public function __construct($params = array()) {
        $this->ci = &get_instance();
        $this->ci->load->helper('public');
    }

    function orderRepair($params) {
        $this->ci->load->bll('order');
        $order_id = $params['order_id'];
        $res = $this->ci->bll_order->repair($order_id,$msg,1);
        if($res === false){
            return array('code'=>'300','msg'=>$msg);
        }
        return array('code'=>'200','msg'=>'恢复成功');
    }

    function orderCancel($params){
        $this->ci->load->bll('order');
        $order_id = $params['order_id'];
        $res = $this->ci->bll_order->cancel($order_id,$msg,false,array(),true);
        if($res === false){
            return array('code'=>'300','msg'=>$msg);
        }
        return array('code'=>'200','msg'=>'取消成功');
    }

    /*
    * 银联支付完成－等待到账
    */
    function orderPayed($params) {
        //必要参数验证start
        $required_fields = array(
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        $msg = array("code" => "200", "msg" => 'succ');
        $this->ci->load->model('order_model');

        $order = $this->ci->order_model->dump(array('order_name' => $params['order_name'], 'pay_status' => '0','order_status' => 1));
        if (empty($order)) {
            $msg = array('code'=>'300','msg'=>'订单不存在');
            return $msg;
        }

        $update_data = array(
            'pay_status' => '2'
        );
        $where = array("order_name" => $params['order_name'], 'pay_status' => '0', 'operation_id' => '0');

        //过滤充值
        $is_trade = substr($params['order_name'], 0, 1);
        if($is_trade != 'T')
        {
            $this->ci->order_model->update_order($update_data, $where);
        }

        $log = array();
        $log['data'] = $order;
        $log['msg'] = $msg;
        $this->ci->load->library('fdaylog');
        $db_log = $this->ci->load->database('db_log', TRUE);
        $this->ci->fdaylog->add($db_log,'orderPayed',json_encode($log));

        return $msg;
    }
}
