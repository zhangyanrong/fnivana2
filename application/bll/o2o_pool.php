<?php
namespace bll;

/**
 * 请求订单池
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   bll
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: pool.php 1 2015-01-30 14:03:13Z pax $
 * @link      http://www.fruitday.com
 **/
class O2o_pool
{
    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * 发货
     *
     * @param Array $order =array(
     *                      'id' => '订单ID'
     *                      'operation_id' => '订单状态'
     *                      )
     * @return void
     * @author
     **/
    public function delivery($order, $shippedorder = array())
    {
        $this->ci->load->model('o2o_child_order_model');
        $result = array('result' => 1, 'msg' => '发货成功');

        if (!$order['id']) return array('result' => 0, 'msg' => '订单参数错误');

        if ($order['operation_id'] == '2') return $result;

        // 验证订单状态
        if ($order['operation_id'] != '0' && $order['operation_id'] != '1') {
            $operation = $this->ci->config->item('operation');
            return array('result' => 0, 'msg' => '订单' . $operation[$order['operation_id']]);
        }

        // 更新订单状态
        $rs = $this->ci->o2o_child_order_model->update(array('operation_id' => '2'), array('id' => $order['id']));

        if (!$rs) return array('result' => 0, 'msg' => '发货失败');

        $this->ci->load->model('order_model');
        $this->ci->order_model->update(array('operation_id' => 2,'last_modify' => time()),array('id'=>$order['p_order_id']));

        // 操作日志

        $this->order_log(array(
            'order_id' => $order['id'],
            'msg' => 'oms操作订单【已发货】',
        ));

        if ($shippedorder) {
            $this->ci->load->model('o2o_order_shipping_model');
            $shipping_info = array(
                'order_id' => $order['id'],
                'deliver_method' => $shippedorder['deliver'],
                'delivertime' => $shippedorder['delivertime'] ? $shippedorder['delivertime'] : date('Y-m-d H:i:s'),
                'logi_no' => $shippedorder['code'] ? $shippedorder['code'] : '',
                'deliver_name' => $shippedorder['dperson'] ? $shippedorder['dperson'] : '',
                'deliver_mobile' => $shippedorder['dphone'] ? $shippedorder['dphone'] : '',
            );

            $this->ci->o2o_order_shipping_model->insert($shipping_info);
        }

        return $result;
    }

    /**
     * 订单完成
     *
     * @return void
     * @author
     **/
    public function finish($order, $score = 0)
    {
        $this->ci->load->bll('order');
        $this->ci->load->model('user_model');
        $this->ci->load->model('o2o_child_order_model');
        $this->ci->load->model('order_op_model');

        $result = array('result' => 1, 'msg' => '成功');

        if (!$order['id']) return array('result' => 0, 'msg' => '订单参数错误');

        if ($order['operation_id'] == '3') return $result;

        if (in_array($order['operation_id'], array(3, 5))) {
            $operation = $this->ci->config->item('operation');
            return array('result' => 0, 'msg' => '订单' . $operation[$order['operation_id']]);
        }

        // 更新订单状态
        $rs = $this->ci->o2o_child_order_model->update(array('operation_id' => '3'), array('id' => $order['id']));

        if (!$rs) return array('result' => 0, 'msg' => '订单置完成失败');

        $orders = $this->ci->o2o_child_order_model->get_child_orders_by_parent_id($order['p_order_id']);
        $isUpdate = true;
        foreach($orders as $o){
            if($o['operation_id'] != 3 && $o['id'] != $order['id']){
                $isUpdate = false;
            }
        }
        if($isUpdate){
            $this->ci->load->model('order_model');
            $this->ci->order_model->update(array('operation_id' => '3'),array('id' => $order['p_order_id']));
        }

        // 操作日志
        $this->order_log(array(
            'order_id' => $order['id'],
            'msg' => 'oms操作订单【已完成】',
        ));

        // 企业回扣
        if ($order['is_enterprise'] && $score) {
            $enterprise = $this->ci->db->select('staff')->from('enterprise')->where('tag', $order['is_enterprise'])->get()->row_array();
            if ($enterprise) {
                $staffUser = $this->ci->db->select('id')->from('user')->where('mobile', $enterprise['staff'])->get()->row_array();
                if ($staffUser) {
                    $this->grant_score($staffUser['id'], $score, '完成企业订单赠送获取' . $score . '积分','企业回扣');
                }
            }
        }

        if ($order['uid']) {
            $user = $this->ci->db->select('*')
                ->from('user')
                ->where('id', $order['uid'])
                ->get()
                ->row_array();

            // 送积分
            if ($score > 0) {
                $reason = '完成订单' . $order['order_name'] . '获取' . $score . '积分';

                $this->ci->bll_order->grant_score($order['uid'], $score, $reason,'订单完成');
            }

            // 送卡卷
            $cards = $order['get_card_money_upto'] ? unserialize($order['get_card_money_upto']) : '';
            if ($cards) {
                $this->ci->load->bll('coupon', null, 'bll_coupon');

                foreach ($cards as $card) {
                    $coupon = array(
                        "uid" => $user['id'],
                        "mobile" => $user['mobile'],
                        "money" => $card,
                        "source" => 0,
                        "has_used" => 0,
                        "has_sent" => 1,
                        "notes" => "订单{$order['order_name']}满百活动赠送",
                        "created_at" => date("Y-m-d"),
                        "expired_at" => date("Y-m-d", strtotime("+ 30 day")),
                        "created_by" => "order complete by erp",
                        "secret" => "MZMWMYT TYMWMZM",
                    );
                    $this->ci->bll_coupon->send_coupon($coupon);
                }
            }

            $this->ci->user_model->upgrade_rank($user['id']);

            // 首次使用电子发票,额外赠送100积分
            $this->ci->bll_order->send_fp_jf($order['order_name'], $user['id']);

            // 邀请有礼
            // $this->ci->load->bll('invite',null,'bll_invite');
            // $this->ci->bll_invite->send_ecoupon($order['uid'],$order['order_name']);

            // 升级勋章
            // $this->ci->load->bll('user',null,'bll_user');
            // $this->ci->bll_user->upgrade_badge($order['uid'],$order['order_name']);

        }

        return $result;
    }


    /**
     * 同步订单状态
     *
     * @return void
     * @author
     **/
    public function status($order_name, $status)
    {
        $result = array('result' => 1, 'msg' => '成功');

        $this->ci->load->model('o2o_child_order_model');
        $order = $this->ci->o2o_child_order_model->dump(array('order_name' => $order_name));
        if (!$order) {
            return array('result' => 0, 'msg' => '订单不存在');
        }

        $operation = $this->ci->config->item('operation');

        $operation_id = null;
        switch ($status) {
            case '2': // 退货
                if (!in_array($order['operation_id'], array(2, 6, 9))) {
                    return array('result' => 0, 'msg' => '订单处于' . $operation[$order['operation_id']] . '状态，不能退货');
                }
                $msg = '退货中';
                $operation_id = 7;
                break;
            case '1': // 换货
                if (!in_array($order['operation_id'], array(2, 6, 9))) {
                    return array('result' => 0, 'msg' => '订单处于' . $operation[$order['operation_id']] . '状态，不能换货');
                }
                $operation_id = 8;
                $msg = '换货中';
                break;
        }

        // 更新订单状态
        if ($operation_id) {
            $rs = $this->ci->o2o_child_order_model->update(array('operation_id' => $operation_id), array('order_name' => $order_name));

            if (!$rs) {
                return array('result' => 0, 'msg' => '订单同步失败');
            }

            $this->ci->load->model('order_model');
            $this->ci->order_model->update(array('operation_id' => $operation_id),array('id'=>$order['p_order_id']));

            $this->order_log(array(
                'order_id' => $order['id'],
                'msg' => 'oms操作订单【' . $msg . '】',
            ));
        }

        return $result;
    }


    public function cancel($order_name, $type=1)
    {
        //type: 1为官网退金额 2为o2o系统走财务退金额（官网只退优惠券并且该订单状态为取消）

        $result = array('result' => 1, 'msg' => '成功');

        $this->ci->load->model('o2o_child_order_model');
        $order = $this->ci->o2o_child_order_model->dump(array('order_name' => $order_name));
        if (!$order) {
            return array('result' => 0, 'msg' => '订单不存在');
        }

        if ($order['operation_id'] == '5') return $result;

        if (in_array($order['operation_id'], array(3, 5))) {
            $operation = $this->ci->config->item('operation');
            return array('result' => 0, 'msg' => '订单' . $operation[$order['operation_id']]);
        }

        $affected_row = $this->ci->o2o_child_order_model->update(array('operation_id' => 5),array('id'=>$order['id']));
        if(!$affected_row) {return array('result' => 0, 'msg' => '订单取消失败');}


        $orders = $this->ci->o2o_child_order_model->get_child_orders_by_parent_id($order['p_order_id']);
        $isUpdate = true;
        foreach($orders as $o){
            if($o['operation_id'] != 5 && $o['id'] != $order['id']){
                $isUpdate = false;
            }
        }
        $this->ci->load->model('order_model');
        if($isUpdate){
            $this->ci->order_model->update(array('operation_id' => 5,'last_modify' => time()),array('id'=>$order['p_order_id']));
            $this->returnO2oGiftSend($order['p_order_id']);
        }

        $this->order_log(array(
            'order_id' => $order['id'],
            'msg' => 'oms操作订单【已取消】',
        ));

        $p_order = $this->ci->order_model->dump(array('order_name' => $order_name));

        if($type != 2){
            // 退预存款
            $money = $order['use_money_deduction'];

            if ($p_order['pay_parent_id'] == '5') {
                $money += $order['money'];
            }

            $this->ci->load->bll('user',null,'bll_user');
            if ($money > 0) {
                $this->ci->bll_user->deposit_recharge($order['uid'],$money, "订单{$order['order_name']}取消，退回帐户余额",$order['order_name']);
            }


            // 退回积分
            if ($order['jf_money']) {
                $score = $order['jf_money'] * 100;
                $this->ci->bll_user->return_score($order['uid'],$score,"订单{$order['order_name']}取消退回积分{$score}",'取消订单返还');
            }
        }

        // 注销优惠劵
        $this->ci->load->bll('order',null,'bll_order');
        if ($order['use_card']) {
            // $this->ci->bll_card->return_card($order['uid'],$order['use_card'],$order['order_name'],"订单{$order['order_name']}取消");
            $this->ci->bll_order->returnCard($order['use_card'],$order['order_name'],2);
        }
        return $result;
    }

    private function order_log($params){
        $data = array(
            'order_id' => $params['order_id'],
            'msg' => $params['msg'],
            'time'=>date("Y-m-d H:i:s"),
        );
        $this->ci->db->insert('o2o_child_order_log', $data);
    }

    function returnO2oGiftSend($order_id){
        $sql = "update ttgy_blow_gifts set is_used=0 where order_id=".$order_id;
        $this->ci->db->query($sql);
    }
}