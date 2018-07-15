<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class CronOrderCancel extends CI_Controller {
    function __construct(){
        parent::__construct ();
        $this->load->helper('public');
    }

    public function cancelParentOrder(){
        $filter = array();
        $filter['time >='] = date('Y-m-d H:i:s',strtotime('-10 hour'));
        $filter['time <'] = date('Y-m-d H:i:s',strtotime('-15 min'));
        $filter['order_status'] = 1;
        $filter['pay_status'] = 0;
        $filter['operation_id'] = 0;
        $filter['pay_parent_id !='] = 4;
        $filter['p_order_id !='] = 1;
        $this->load->model('b2o_parent_order_model');
        $parent_orders = $this->b2o_parent_order_model->getList('id',$filter);
        if(empty($parent_orders)) return;
        $this->parentOrderCancel($parent_orders);
    }

    private function parentOrderCancel($parent_orders) {
        $this->load->model('b2o_parent_order_model');
        $this->load->model('b2o_parent_order_product_model');
        $this->load->model('b2o_store_product_model');
        $this->load->model('order_op_model');
        $this->load->model('user_gifts_model');
        $this->load->bll('user', null, 'bll_user');
        $this->load->bll('card', null, 'bll_card');
        foreach ($parent_orders as $key => $value) {
            $this->db->trans_begin();
            $order = $this->b2o_parent_order_model->dump(array('id' => $value['id']));
            if($order['operation_id'] != '0'){
                continue;
            }
            if($order['operation_id'] != '0'){
                continue;
            }
            if($order['p_order_id'] != '0'){
                continue;
            }
            $this->b2o_parent_order_model->update(array('operation_id' => 5, 'last_modify' => time()), array('id' => $order['id']));
            // 退赠品
            $this->user_gifts_model->return_b2o_user_gift($order['id'], $order['uid']);
            // 退回积分
            if ($order['jf_money']) {
                $score = $order['jf_money'] * 100;
                $this->bll_user->return_score($order['uid'], $score, "订单{$order['order_name']}取消退回积分{$score}",'取消订单返还');
            }
            // 注销优惠劵
            if ($order['use_card']) {
                $this->bll_card->return_card($order['uid'], $order['use_card'], $order['order_name'], "订单{$order['order_name']}取消");
            }
            // 重置在线提货劵
            if($order['pay_parent_id'] == '6'){
                $this->bll_card->return_pro_card($order['uid'], $order['order_name']);
            }
            //红包状态改为无效
            //$this->change_parent_packet_status($order['id']);
            //退库存
            $sku_arr = $this->b2o_parent_order_product_model->getOrderSkuList($order['id']);
            foreach ($sku_arr as $key => $val) {
                $this->b2o_store_product_model->return_product_stock($val['product_id'],$val['sid'],$val['qty']);
            }
            $order_op = array(
                "manage" => $order['uid'],
                "pay_msg" => "",
                "operation_msg" => "支付超时订单取消",
                "time" => date("Y-m-d H:i:s"),
                "p_order_id" => $order['id'],
            );
            $this->order_op_model->insert($order_op);
            $this->order_op_model->addCancelDetail($order['id'],3,0,0);
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                continue;
            } else {
                $this->db->trans_commit();
            }
        }
    }

    private function change_parent_packet_status($p_order_id) {
        $this->db->where(array("p_order_id" => $p_order_id));
        $this->db->update('red_packets', array(
            "status" => 0
        ));
    }
}
