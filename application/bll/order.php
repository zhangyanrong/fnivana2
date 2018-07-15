<?php
namespace bll;

class Order {
    private $can_comment_period = "3 months";
    private $card_channel = array('1' => '官网', '2' => 'APP', '3' => 'WAP');
    private $is_enterprise = 0;
    private $can_use_card = '1';
    private $can_use_jf = '1';
    private $card_info = array();
    private $order_score = 0;
    private $terminal_arr = array('pc' => 1, 'app' => 2, 'wap' => 3);
    //upload
    private $photopath = "images/";
    private $thumb_size = "320";
    private $photolimit = 6;
    //comment jf
    private $score_num = 5;
    private $score_mult = 2;
    private $use29Min = true;

    var $cart_info = array();

    private $active_arr = array();

    /*数据库积分信息延迟问题start*/
    var $use_jf_obj = false;
    var $use_jf = 0;
    var $jf_money = 0;
    /*数据库积分信息延迟问题end*/


    /*数据库积分信息延迟问题start*/
    var $use_card_obj = false;
    var $use_card = '';
    var $card_money = 0;
    /*数据库积分信息延迟问题end*/

    /*数据库积点信息延迟问题start*/
    var $use_jd_obj = false;
    var $jd_discount = 0;
    /*数据库积点信息延迟问题end*/

    var $device_product_id = array();

    var $obj_cart;

    public function __construct($params = array()) {
        $this->ci = &get_instance();
        $session_id = isset($params['connect_id']) ? $params['connect_id'] : '';
        if ($session_id) {
            $this->ci->load->library('session', array('session_id' => $session_id));
        }
        $this->ci->load->model('order_model');
        $this->ci->load->model('user_model');
        $this->ci->load->model('cart_model');
        $this->ci->load->model('order_product_model');
        $this->ci->load->model('pay_discount_model');
        $this->ci->load->model('comment_model');
        $this->ci->load->model('product_model');
        $this->ci->load->model('quality_complaints_model');
        $this->ci->load->model('trade_invoice_model');
        $this->ci->load->model('warehouse_model');
        $this->ci->load->model('evaluation_model');
        $this->ci->load->model('subscription_order_model');  //周期购
        $this->ci->load->model('subscription_order_address_model');

        //b2o
        $this->ci->load->model('b2o_parent_order_model');
        $this->ci->load->model('b2o_parent_order_product_model');
        $this->ci->load->model('b2o_parent_order_address_model');
        $this->ci->load->model('b2o_parent_order_invoice_model');
        $this->ci->load->model('b2o_store_product_model');
        $this->ci->load->model('package_model');
        $this->ci->load->model('cart_v2_model');
        $this->ci->load->model('card_model');
        $this->ci->load->model('o2o_model');
        $this->ci->load->model('order_einvoices_model');
        $this->ci->load->model('b2o_store_model');
        $this->ci->load->model('b2o_product_group_model');
        $this->ci->load->model('order_postage_model');
        $this->ci->load->model('postage_log_model');

        if(!empty($params['connect_id']) && !empty($params['store_id_list'])) {
            $this->ci->load->library('login');
            $this->ci->login->init($params['connect_id']);
            $uid = $this->ci->login->get_uid();
            $user = $this->ci->login->get_user();

            $cart_bll_params['cart_id'] = $uid;
            $cart_bll_params['store_id_list'] = $params['store_id_list'];
            $cart_bll_params['user'] = $user;
            $cart_bll_params['source'] = $params['source'];
            $cart_bll_params['version'] = $params['version'];
            $cart_bll_params['tms_region_type'] = $params['tms_region_type'];

            $this->ci->order_model->cart_bll_params = $cart_bll_params;

            //cart -v3
            $this->ci->load->bll('apicart');
            $api_cart = array();
            $api_cart['cart_id'] = $uid;
            $api_cart['store_id_list'] = $params['store_id_list'];
            $api_cart['user'] = $uid;
            $api_cart['source'] = $params['source'];
            $api_cart['version'] = $params['version'];
            $api_cart['tms_region_type'] = $params['tms_region_type'];
            $this->cart_info = $this->ci->bll_apicart->get($api_cart);

            //cart -v2
            //$store_id_list = explode(',',$params['store_id_list']);
            //$cart = $this->ci->cart_v2_model->init($uid,$store_id_list,$user,$params['source'],$params['version'],$params['tms_region_type']);
            //$this->obj_cart = $cart;
            //obj => arr
            //$cart_obj = $cart->getProducts()->validate()->promo()->total()->count()->checkout();
            //$json_cart = json_encode($cart_obj);
            //$this->cart_info = json_decode($json_cart,true);
        }

        $this->ci->load->helper('public');
        $this->_version = $params['version'];
        $this->platform = $params['platform'];
        $this->photopath = $this->photopath . date("Y-m-d");

    }

    /**
     * 取消订单
     *
     * @return void
     * @author
     **/
    public function cancel($order_id, &$msg, $is_oms = false, $params = array(),$is_pay_center = false) {

        $order = $this->ci->order_model->dump(array('id' => $order_id));

        if (!$order) {
            $msg = '订单不存在';
            return false;
        }

        if ($order['sync_status'] == 2 && !$is_oms) {
            $msg = '订单确认中，请稍后操作';
            return false;
        }

        if ($order['operation_id'] != '0' && $order['operation_id'] != '1') {
            $operation = $this->ci->config->item('operation');

            $msg = '订单已经' . $operation[$order['operation_id']];

            return false;
        }

        if (in_array($order['order_type'], array(3, 4))) {
            if ($order['sync_status'] != 0 && !$is_oms) {
                $msg = '订单已经审核不能取消';
                return false;
            }
        }

        if($order['order_type'] == 14 && in_array($order['pay_status'], array(1,2))){
            $msg = '提货券兑换积点订单不能取消';
            return false;
        }

        $type = $is_oms?2:1;
        $is_pay_center and $type = 3;
        $per_op_id = $order['operation_id'];
        $per_pay_status = $order['pay_status'];

        // if (!in_array($order['pay_parent_id'],array('4','5','6'))) {
        //     $msg = '请先退款';

        //     return false;
        // }

        $affected_row = $this->ci->order_model->update(array('operation_id' => 5, 'last_modify' => time()), array('id' => $order['id']));
        if (!$affected_row) {
            $msg = '订单取消失败';
            return false;
        }
        $this->ci->order_model->setOmsRefund($order['id']);
        if($is_pay_center){  //支付中心过来的取消，没有支付余额成功
            $this->ci->order_model->setOmsNoRefund($order['id']);
        }

        // 退赠品
        $this->ci->load->model('user_gifts_model');
        $this->ci->user_gifts_model->return_user_gift($order['id'], $order['uid']);

        // if($order['p_order_id']){
        //     $counts_s_order = $this->ci->order_model->getList('id',array('p_order_id'=>$order['p_order_id']));
        //     if(count($counts_s_order) == 1){
        //         $this->ci->user_gifts_model->return_b2o_user_gift($order['p_order_id'], $order['uid']);
        //     }
        // }

        // 退预存款
        //if (strcmp($params['version'], '3.4.0') >= 0) {
            if ($order['pay_status'] == 1) {
                $money = $order['use_money_deduction'];

                if ($order['pay_parent_id'] == '5') {
                    $money += $order['money'];
                }
            }
        // } else {
        //     $money = $order['use_money_deduction'];

        //     if ($order['pay_parent_id'] == '5' && $order['pay_status'] == 1) {
        //         $money += $order['money'];
        //     }
        // }

        $this->ci->load->bll('user', null, 'bll_user');
        // if ($money > 0) {
        //     $this->ci->bll_user->deposit_recharge($order['uid'], $money, "订单{$order['order_name']}取消，退回帐户余额", $order['order_name']);

        //     //消息中心
        //     $this->ci->load->bll('msg');
        //     $this->ci->bll_msg->addMsgRefund(array('order_name'=>$order['order_name'],'content'=>"订单{$order['order_name']}取消,退回帐户余额"));
        // }


        // 退回积分
        if ($order['jf_money']) {
            $score = $order['jf_money'] * 100;
            $this->ci->bll_user->return_score($order['uid'], $score, "订单{$order['order_name']}取消退回积分{$score}",'取消订单返还');
        }

        // 注销优惠劵
        $this->ci->load->bll('card', null, 'bll_card');
        if ($order['use_card']) {
            $this->returnCard($order['use_card'],$order['order_name'],2);
        }

        // 重置在线提货劵
        if ($order['pay_parent_id'] == '6'){
            $this->ci->bll_card->return_pro_card($order['uid'], $order['order_name']);
        }elseif($order['pro_card_money'] > 0  && $order['pro_card_number']){
            $this->ci->bll_card->return_pro_card_new($order['pro_card_number'],$order['order_name']);
        }

        // 红包退回
        // if ($order['uid'])
        //     $this->ci->bll_card->return_hongbao($order['id'],$order['uid']);

        //订单取消,库存退回
        if ($order['order_type'] == '3' || $order['order_type'] == '4') {//o2o订单
            $this->ci->load->model('o2o_order_extra_model');
            $this->ci->load->model('o2o_store_goods_model');
            if ($c_orders = $this->checkChildOrder($order_id)) {
                $this->ci->load->model('o2o_child_order_model');
                foreach ($c_orders as $c_order) {
                    $this->ci->o2o_child_order_model->update(array('operation_id' => 5), array('id' => $c_order['id']));
                }
                $c_order_info = $this->getChildOrderInfo($order_id);
                foreach ($c_order_info as $c_order) {
                    $this->ci->o2o_store_goods_model->returnStock($c_order['store_id'], $c_order['product_id'], $c_order['qty']);
                }
            } else {
                $fields = 'order_product.product_id,order_product.qty,order_product.product_no';
                $order_product_where = array('order_product.order_id' => $order_id);
                $join[] = array('table' => 'product', 'field' => 'product.id=order_product.product_id', 'type' => 'left');
                $order_products_res = $this->ci->order_model->selectOrderProducts($fields, $order_product_where, '', '', $join);
                $store_id = $this->ci->o2o_order_extra_model->getStoreByOrderId($order_id);
                foreach ($order_products_res as $p_info) {
                    $this->ci->o2o_store_goods_model->returnStock($store_id, $p_info['product_id'], $p_info['qty']);
                }
            }
            $this->returnO2oGiftSend($order_id);
        } else {//b2c订单
            $this->return_product_qty($order_id);
        }
        $this->returnPostage($order['order_name'],2);

        //单品促销删除
        $this->delete_sales_rule_limit_log($order['uid'], $order_id);

        //红包状态改为无效
        $this->change_packet_status($order_id);

        //b2c刮一刮优惠券处理
        $this->unsent_blow_card($order_id);

        // 操作日志
        $this->ci->load->model('order_op_model');
        if ($is_oms) {
            $order_op = array(
                "manage" => "erp系统",
                "pay_msg" => "",
                "operation_msg" => "订单取消",
                "time" => date("Y-m-d H:i:s"),
                "order_id" => $order['id'],
            );
        } else {
            $order_op = array(
                "manage" => $order['uid'],
                "pay_msg" => "",
                "operation_msg" => "订单取消",
                "time" => date("Y-m-d H:i:s"),
                "order_id" => $order['id'],
            );
        }
        $this->ci->order_op_model->insert($order_op);
        $this->ci->order_op_model->addCancelDetail($order['id'],$type,$per_op_id,$per_pay_status);

        //gift price
        $orderPro = $this->ci->order_product_model->getList('product_id,gift_price',array('order_id'=>$order['id']));
        $is_gift_price = 0;
        foreach($orderPro as $row=>$val)
        {
            if($val['gift_price'] >0)
            {
                $is_gift_price = 1;
            }
        }
        if($is_gift_price == 1)
        {
            $this->ci->load->model('gift_package_log_model');
            $this->ci->gift_package_log_model->add($order['order_name'],0);
        }

        //返还秒杀
        if($order['p_order_id'] >0)
        {
            $b2o = $this->ci->b2o_parent_order_model->dump(array('id' => $order['p_order_id']));
            $b2o_pro = $this->ci->order_product_model->getList('id,product_id',array('order_id'=>$order['id']));

            $p_order_name = $b2o['order_name'];
            $this->ci->load->model('ms_log_v2_model');
            $log = $this->ci->ms_log_v2_model->getList('id,product_id',array('order_name' => $p_order_name,'is_del'=>0));

            $log_pros = array_column($log,'product_id');
            $p_pros = array_column($b2o_pro,'product_id');

            $ms = array();
            foreach($p_pros as $k=>$v)
            {
                if(in_array($v,$log_pros))
                {
                    array_push($ms,$v);
                }
            }

            if(count($ms) >0)
            {
                foreach($ms as $key=>$val)
                {
                    $this->ci->ms_log_v2_model->update_order_del($p_order_name,$val);
                }
            }
        }

        return true;
    }

    private function return_product_qty($order_id) {
        $sku_arr = $this->ci->b2o_parent_order_product_model->getOrderSkuList($order_id);
        foreach ($sku_arr as $key => $val) {
            $this->ci->b2o_store_product_model->return_product_stock($val['product_id'],$val['sid'],$val['qty']);
        }
    }

    //红包状态改为无效
    private function change_packet_status($order_id) {
        $this->ci->db->where(array("order_id" => $order_id));
        $this->ci->db->update('red_packets', array(
            "status" => 0
        ));
    }

    private function delete_sales_rule_limit_log($uid, $order_id) {
        $this->ci->db->where(array("order_id" => $order_id, 'uid' => $uid));
        $this->ci->db->delete('active_limit');
    }

    private function unsent_blow_card($order_id) {
        $blow_card = $this->ci->db->select('card_number')->from('blow_log')->where(array("order_id" => $order_id))->get()->row_array();
        if (!empty($blow_card)) {
            $this->ci->db->where(array("card_number" => $blow_card['card_number'], 'is_used' => 0));
            $this->ci->db->delete('card');
        }
    }

    /**
     * 送积分
     *
     * @return void
     * @author
     **/
    public function grant_score($uid, $score, $reason = '',$type='') {
        $data = array(
            'jf' => $score,
            'reason' => $reason,
            'time' => date("Y-m-d H:i:s"),
            'uid' => $uid,
        );
        $data['type'] = $type?$type:'活动';
        $this->ci->load->model('user_jf_model');
        $insert_id = $this->ci->user_jf_model->insert($data);
        $this->ci->user_model->updateJf($uid,$score,1);
        return $insert_id ? $insert_id : false;
    }

    /**
     * 发货
     *
     * @param Array $order  =array(
     *                      'id' => '订单ID'
     *                      'operation_id' => '订单状态'
     *                      )
     *
     * @return void
     * @author
     **/
    public function delivery($order, $shippedorder = array()) {
//        print_r($order);exit;

//echo 3;exit;
//        return 1;
        $result = array('rs' => 'succ', 'msg' => '');

        if (!$order['id']) return array('rs' => 'error', 'msg' => '订单参数错误');


        if ($order['operation_id'] == '2') {
            if ($shippedorder) {
                $this->ci->load->model('order_shipping_model');
                $shipping_info = array(
                    'deliver_method' => $shippedorder['deliver'],
                    'pkgtime' => $shippedorder['pkgtime'] ? strtotime($shippedorder['pkgtime']) : time(),
                    'delivertime' => $shippedorder['delivertime'] ? strtotime($shippedorder['delivertime']) : time(),
                    'logi_no' => $shippedorder['code'] ? $shippedorder['code'] : '',
                    'deliver_name' => $shippedorder['dperson'] ? $shippedorder['dperson'] : '',
                    'deliver_mobile' => $shippedorder['dphone'] ? $shippedorder['dphone'] : '',
                );
                $where = array(
                    'order_id' => $order['id'],
                );
                $this->ci->order_shipping_model->update($shipping_info, $where);
            }
            return $result;
        }

        // 验证订单状态
        if ($order['operation_id'] != '0' && $order['operation_id'] != '1' && $order['operation_id'] != '4') {
            $operation = $this->ci->config->item('operation');

            return array('rs' => 'error', 'msg' => '订单' . $operation[$order['operation_id']]);
        }

        // 更新订单状态
        $rs = $this->ci->order_model->update(array('operation_id' => '2', 'send_date' => date("Y-m-d", strtotime($shippedorder['delivertime'])),'sync_status'=>'1'), array('id' => $order['id']));

        if (!$rs) return array('rs' => 'error', 'msg' => '发货失败');

        //分享 拉新 发赠品 start
        if(preg_match("/[0-9]+\-s\-/i", $order['use_card'], $match)){
            $check_user_card = $this->ci->db->select('*')->from('active_card_user')->where('card_number', $order['use_card'])->get()->row_array();
            if($check_user_card){
                $own_user_count = $this->ci->db->select('*')->from('active_card_user')->where(array('uid'=>$check_user_card['uid'], 'active_id'=>$check_user_card['active_id'], 'is_used'=>1))->get()->num_rows();
                $own_user_count ++;
                $this->ci->db->update('active_card_user', array('is_used' => '1'), array('card_number' => $order['use_card']));
                $tag_array = array(
                    1=>array('tag'=>'bvrOU2', 'id'=>'3696'),
                    2=>array('tag'=>'sAskQ1', 'id'=>'3699'),
                    3=>array('tag'=>'qtbQa1', 'id'=>'3700'),
                    4=>array('tag'=>'uWm7h',  'id'=>'3701'),
                    5=>array('tag'=>'F7zHb1', 'id'=>'3702')
                );
                $is_can_send = $own_user_count%2;//每下单2次 送一个赠品
                $tag_key = $own_user_count/2;

                if($own_user_count>0 && $is_can_send==0 && isset($tag_array[$tag_key])){
                    $result = $this->ci->db->from("user_gifts")->where( array("uid" => $check_user_card['uid'], "active_id" => $tag_array[$tag_key]['id']) )->get()->row_array();
                    if (empty($result)) {
                        $gift_send = $this->ci->db->select('*')->from('gift_send')->where('id', $tag_array[$tag_key]['id'])->get()->row_array();
                        if($gift_send['gift_valid_day'] && $gift_send['gift_valid_day']>0){
                            $gift_start_time = date('Y-m-d');
                            $gift_end_time = date('Y-m-d',strtotime('+'.(intval($gift_send['gift_valid_day'])-1).' day'));
                        }elseif($gift_send['gift_start_time'] && $gift_send['gift_end_time'] && $gift_send['gift_start_time'] != '0000-00-00' && $gift_send['gift_end_time'] != '0000-00-00'){
                            $gift_start_time = $gift_send['gift_start_time'];
                            $gift_end_time = $gift_send['gift_end_time'];
                        }else{
                            $gift_start_time = $gift_send['start'];
                            $gift_end_time = $gift_send['end'];
                        }
                        $user_gift_data = array(
                            'uid' => $check_user_card['uid'],
                            'active_id' => $tag_array[$tag_key]['id'],
                            'active_type' => '2',
                            'has_rec' => '0',
                            'start_time'=>$gift_start_time,
                            'end_time'=>$gift_end_time,
                        );
                        $this->ci->db->insert('user_gifts', $user_gift_data);
                    }
                }
            }
        }
        //分享 拉新 发赠品 end

        //大转盘1012 start
        //先查看当前订单是否有使用赠品
        $use_active_ids = $this->ci->db->from("user_gifts")->where( array("bonus_order" => $order['id'], "has_rec" => 1) )->get()->result_array();
        if ($use_active_ids){
            foreach($use_active_ids as $use_active_id){
                //获取赠品的tag码
                $active_id = $use_active_id['active_id'];
                $now_uid = $order['uid'];
                //获取当前人的手机号
                $now_mobile_row = $this->ci->db->select('*')->from('user')->where('id', $now_uid)->get()->row_array();
                $now_mobile = $now_mobile_row['mobile'];
                $gift_row = $this->ci->db->select('*')->from('gift_send')->where('id', $active_id)->get()->row_array();
                if ($gift_row){
                    $gift_tag = $gift_row['tag'];
                    //查看这个tag是否是转盘1012领取的
                    if ($gift_tag){
                        $rotor1012_record = $this->ci->db->select('*')->from('rotor1012')->where(array('prize_num'=>$gift_tag,'mobile'=>$now_mobile))->get()->row_array();
                        //是通过邀请获取
                        if ($rotor1012_record){
                            $record_id = $rotor1012_record['id'];
                            //哪个人分享的转盘链接
                            $rotor_id = $rotor1012_record['rotor_id'];
                            //哪个大转盘
                            $config_id = $rotor1012_record['config_id'];
                            //将状态变为1
                            $this->ci->db->where(array("id" => $record_id));
                            $this->ci->db->update('rotor1012', array(
                                "is_order" => 1
                            ));
                            //查看是否满3人使用赠品 满3人送牛奶
                            $parent_row = $this->ci->db->select('*')->from('rotor1012')->where('id', $rotor_id)->get()->row_array();
                            $send_uid = $parent_row['uid'];
                            //所有分享出去的记录id
                            $all_share = $this->ci->db->select('*')->from('rotor1012')->where(array('uid'=>$send_uid,'config_id'=>$config_id))->get()->result_array();
                            $all_share_ids = array();
                            foreach($all_share as $each_share){
                                $all_share_ids[] = $each_share['id'];
                            }
                            $use_gift_count = $this->ci->db->select('*')->from('rotor1012')->where_in('rotor_id',$all_share_ids)->where(array('is_order'=>1))->get()->num_rows();
                            if ($use_gift_count == 3){
                                $send_active_id = 4228;
                            } elseif ($use_gift_count == 6){
                                $send_active_id = 4260;
                            } elseif ($use_gift_count == 9){
                                $send_active_id = 4261;
                            } elseif ($use_gift_count == 12){
                                $send_active_id = 4262;
                            }
                            if ($send_active_id){
                                $gift_send = $this->ci->db->select('*')->from('gift_send')->where('id', $send_active_id)->get()->row_array();
                                if(empty($gift_send)) continue;
                                if($gift_send['gift_valid_day'] && $gift_send['gift_valid_day']>0){
                                    $gift_start_time = date('Y-m-d');
                                    $gift_end_time = date('Y-m-d',strtotime('+'.(intval($gift_send['gift_valid_day'])-1).' day'));
                                }elseif($gift_send['gift_start_time'] && $gift_send['gift_end_time'] && $gift_send['gift_start_time'] != '0000-00-00' && $gift_send['gift_end_time'] != '0000-00-00'){
                                    $gift_start_time = $gift_send['gift_start_time'];
                                    $gift_end_time = $gift_send['gift_end_time'];
                                }else{
                                    $gift_start_time = $gift_send['start'];
                                    $gift_end_time = $gift_send['end'];
                                }
                                $user_gift_data = array(
                                    'uid' => $send_uid,
                                    'active_id' => $send_active_id,
                                    'active_type' => '2',
                                    'has_rec' => '0',
                                    'start_time'=>$gift_start_time,
                                    'end_time'=>$gift_end_time,
                                );
                                $this->ci->db->insert('user_gifts', $user_gift_data);
                            }
                            /* $use_gift_count = $this->ci->db->select('*')->from('rotor1012')->where(array('rotor_id'=>$rotor_id, 'is_order'=>1))->get()->num_rows();
                            if ($use_gift_count && $use_gift_count == 3){
                                //获取邀请人id
                                $parent_row = $this->ci->db->select('*')->from('rotor1012')->where('id', $rotor_id)->get()->row_array();
                                $send_uid = $parent_row['uid'];
                                $send_active_id = 4228; //牛奶的tag pIhZG2
                                $user_gift_data = array(
                                    'uid' => $send_uid,
                                    'active_id' => $send_active_id,
                                    'active_type' => '2',
                                    'has_rec' => '0'
                                );
                                $this->ci->db->insert('user_gifts', $user_gift_data);
                            } */
                        }
                    }
                }
            }
        }
        //大转盘1012 end

        //香梨分享 13301 start
        $today = date('Ymd');
        if($today>'20161007' && $today<='20161030'){
            $this->ci->load->model('active_model');
            $this->ci->active_model->share_gift_tuan($order);//柚子团发牛奶
            $this->ci->active_model->share_gift_tuan($order, 13691, array(13691, 13699), 4031, 2);//香蕉团发牛奶
        }
        //香梨分享 end

//        500元赠品参团start
        if(($today>'20161112' && $today<='20161125')){
            $this->ci->load->model('active_model');
            $this->ci->active_model->five_gift_tuan($order);
        }
//        500元赠品参团end

//        if(empty($this->active_arr))
//            $this->active_arr = include_once('application/config/user_pri_arr.php');
//        $config = $this->active_arr;
//        $this->ci->load->library('fdaylog');
//        $db_log = $this->ci->load->database('db_log', TRUE);
//        $this->ci->fdaylog->add($db_log,'gsdr2',json_encode($config['cherry'][0]));
        $list = $this->ci->user_model->stockAll();
        $this->ci->order_model->bh_active($list, $order);

        $this->ci->order_model->rocketTree1111($order); //2016双11 小火箭能量树

        if ($order['uid']) {
            $user = $this->ci->db->select('*')
            ->from('user')
            ->where('id', $order['uid'])
            ->get()
            ->row_array();
            // 邀请有礼
            $this->ci->load->bll('userinvite',null,'bll_userinvite');
            //$invite_result = $this->ci->bll_userinvite->checkIfInvite2($user['id'],$user['mobile']);
            $invite_result = $this->ci->bll_userinvite->checkIfInvitePrune($user['id'],$user['mobile']);
            $invite_result = false;

            //给邀请人加1000分 $order['pay_parent_id']=4
            if ($invite_result) {
                //查看订单是否含有西梅
                $is_gift_order_sql = "select id from ttgy_user_gifts where bonus_order = '".$order['id']."' and active_id >= 3577 and active_id <= 3602";

                $is_gift_order_res = $this->ci->db->query($is_gift_order_sql)->row_array();
                if ($is_gift_order_res){
                $invite_by = $invite_result['invite_by'];
                    $invite_time = $invite_result['ctime'];

                    $order_money = $this->ci->db->select('money')->from('order')->where('id', $order['id'])->get()->row_array();
                    //订单金额为X时讲邀请状态变为1
                    $limit_money = 0;


                    if ($order_money) {
                        if ($order_money['money'] >= $limit_money) {
                            //将邀请记录的is_finish_order变为1 下次不再加积分
                            $this->ci->bll_userinvite->updateFinishOrderPrune($user['id'], $user['mobile']);
                            $this->ci->bll_userinvite->sendInviteGiftPrune($invite_by);
                        }
                    }
                }


            }
        }
//        if(strpos($config['cherry'],','.$order['uid'].',')>0||strpos($config['kiwi'],','.$order['uid'].',')>0||strpos($config['blueberry'],','.$order['uid'].',')>0){
////能量树确定时间后配置再开
//            //能量树
//            $tag = '20160714';
//            $powertree_endtime = date('2016-08-01 23:59:59');     //延后一段时间
//            $powertree_endtime = strtotime($powertree_endtime);
//            $powertree_order_starttime = date('2016-07-14 00:00:00');  //开始时间
//            $powertree_order_endtime = date('2016-07-27 23:59:59');    //订单结束时间
//            $time  = time();
//            //当前时间小小于6月30日
//            if ($time < $powertree_endtime && $time>strtotime($powertree_order_starttime)) {//&& $time>$powertree_order_starttime
//                //判断是否开启能量树
//                $is_powertree = $this->ci->db->select('id')->from('powertree')->where(array('uid'=>$order['uid'],'tag'=>$tag))->get()->row_array();
//                if (empty($is_powertree)) {
//                    $powertree  = array(
//                        'uid'=>$order['uid'],
//                        'power_value'=>0,
//                        'created_time'=>$time,
//                        'tag'=>$tag
//                    );
//                    $this->ci->db->insert('powertree', $powertree);
//                    $tree_id = $this->ci->db->insert_id();
//                }
//                $ordertime = $this->ci->db->select('time,order_type,money,pay_parent_id')->from('order')->where('id', $order['id'])->get()->row_array();
//                //订单时间在范围内 且是B2C订单
//                if ($ordertime['time'] >= $powertree_order_starttime && $ordertime['time'] <= $powertree_order_endtime && $ordertime['order_type'] == 1 && $ordertime['pay_parent_id']!=5) {
//                    $powertree_record = array(
//                        'uid' => $order['uid'],
//                        'power_value' => $ordertime['money'],
//                        'order_id' => $order['id'],
//                        'created_time' => $time,
//                        'tag' => $tag
//                    );
//                    $this->ci->db->insert('powertree_record', $powertree_record);
//
//                    //更新能量主表
//                    if($tree_id){
//                        $this->ci->db->where(array("id" => $tree_id));
//                    }else{
//                        $this->ci->db->where(array("uid" => $order['uid'],'tag'=>$tag));
//                    }
//                    $this->ci->db->set('power_value', 'power_value + ' . $ordertime['money'], FALSE);
//                    $this->ci->db->update('powertree');
//                }
//            }
//        }

        // 操作日志
        $this->ci->load->model('order_op_model');
        $order_op = array(
            "manage" => "erp系统",
            "pay_msg" => "已经付款",
            "operation_msg" => "订单发货",
            "time" => date("Y-m-d H:i:s"),
            "order_id" => $order['id'],
        );
        $this->ci->order_op_model->insert($order_op);

        if ($shippedorder) {
            $this->ci->load->model('order_shipping_model');
            $shipping_info = array(
                'order_id' => $order['id'],
                'deliver_method' => $shippedorder['deliver'],
                'pkgtime' => $shippedorder['pkgtime'] ? strtotime($shippedorder['pkgtime']) : time(),
                'delivertime' => $shippedorder['delivertime'] ? strtotime($shippedorder['delivertime']) : time(),
                'logi_no' => $shippedorder['code'] ? $shippedorder['code'] : '',
                'deliver_name' => $shippedorder['dperson'] ? $shippedorder['dperson'] : '',
                'deliver_mobile' => $shippedorder['dphone'] ? $shippedorder['dphone'] : '',
            );

            $this->ci->order_shipping_model->insert($shipping_info);
        }

        return $result;
    }

    /**
     * 订单完成
     *
     * @return void
     * @author
     **/
    public function finish($order, $score = 0,$final_money = NULL) {
        $result = array('rs' => 'succ', 'msg' => '');

        if (!$order['id']) return array('rs' => 'error', 'msg' => '订单参数错误');

        if ($order['operation_id'] == '3') return $result;

        if (in_array($order['operation_id'], array(3, 5, 7, 8))) {
            $operation = $this->ci->config->item('operation');

            return array('rs' => 'error', 'msg' => '订单' . $operation[$order['operation_id']]);
        }

        // 更新订单状态
        $rs = $this->ci->order_model->update(array('operation_id' => '3'), array('id' => $order['id']));

        if (!$rs) return array('rs' => 'error', 'msg' => '订单置完成失败');

        // 操作日志
        $this->ci->load->model('order_op_model');
        $order_op = array(
            "manage" => "erp系统",
            "pay_msg" => "已经付款",
            "operation_msg" => "订单已完成",
            "time" => date("Y-m-d H:i:s"),
            "order_id" => $order['id'],
        );
        $this->ci->order_op_model->insert($order_op);
        if(!is_null($final_money)){
            $this->ci->order_model->add_final_money($order['id'],$final_money);
        }
        if($order['uid']){
            $this->ci->user_model->upgrade_rank($order['uid']);
        }
        // 企业回扣
        if ($order['is_enterprise'] && $score > 0) {
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

                $this->grant_score($order['uid'], $score, $reason,'订单完成');

                if(bccomp($score, $order['score'],2) == 1){
                    $this->ci->load->library('fdaylog');
                    $db_log = $this->ci->load->database('db_log', TRUE);
                    $log_data = array();
                    $log_data['order_name'] = $order['order_name'];
                    $log_data['order_score'] = $order['score'];
                    $log_data['give_score'] = $score;
                    $this->ci->fdaylog->add($db_log,'finish_jf',$log_data);
                }
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

            // 首次使用电子发票,额外赠送100积分
            $this->send_fp_jf($order['order_name'], $user['id']);

            /*能量树确定时间后配置再开
            //能量树
            $tag = '20160601';
            $powertree_endtime = date('2016-07-07 23:59:59');     //延后一段时间
            $powertree_endtime = strtotime($powertree_endtime);
            $powertree_order_starttime = date('2016-06-20 00:00:00');  //开始时间
            $powertree_order_endtime = date('2016-06-30 23:59:59');    //订单结束时间
            $time  = time();
            //当前时间小小于6月30日
            if ($time < $powertree_endtime && $time>strtotime($powertree_order_starttime)) {//&& $time>$powertree_order_starttime
                //判断是否开启能量树
                $is_powertree = $this->ci->db->select('id')->from('powertree')->where(array('uid'=>$order['uid'],'tag'=>$tag))->get()->row_array();
                if (empty($is_powertree)) {
                    $powertree  = array(
                        'uid'=>$order['uid'],
                        'power_value'=>0,
                        'created_time'=>$time,
                        'tag'=>$tag
                    );
                    $this->ci->db->insert('powertree', $powertree);
                    $tree_id = $this->ci->db->insert_id();
                }
                    $ordertime = $this->ci->db->select('time,order_type,money')->from('order')->where('id', $order['id'])->get()->row_array();
                    //订单时间在范围内 且是B2C订单
                    if ($ordertime['time'] >= $powertree_order_starttime && $ordertime['time'] <= $powertree_order_endtime && $ordertime['order_type'] == 1) {
                        $powertree_record = array(
                            'uid' => $order['uid'],
                            'power_value' => $ordertime['money'],
                            'order_id' => $order['id'],
                            'created_time' => $time,
                            'tag' => $tag
                        );
                        $this->ci->db->insert('powertree_record', $powertree_record);

                        //更新能量主表
                        if($tree_id){
                            $this->ci->db->where(array("id" => $tree_id));
                        }else{
                            $this->ci->db->where(array("uid" => $order['uid'],'tag'=>$tag));
                        }
                        $this->ci->db->set('power_value', 'power_value + ' . $ordertime['money'], FALSE);
                        $this->ci->db->update('powertree');
                    }
            }
            */

            // 邀请有礼
            $this->ci->load->bll('userinvite',null,'bll_userinvite');
            //$invite_result = $this->ci->bll_userinvite->checkIfInvite2($user['id'],$user['mobile']);
            $invite_result = $this->ci->bll_userinvite->checkIfInvitePrune($user['id'],$user['mobile']);
            //$invite_result = false;

            //给邀请人加1000分 $order['pay_parent_id']=4
            if ($invite_result) {
                //查看订单是否含有西梅
                $is_gift_order_sql = "select id from ttgy_user_gifts where bonus_order = '".$order['id']."' and active_id >= 3577 and active_id <= 3602";

                $is_gift_order_res = $this->ci->db->query($is_gift_order_sql)->row_array();
                if ($is_gift_order_res){
                    $invite_by = $invite_result['invite_by'];
                    $invite_time = $invite_result['ctime'];

                    $order_money = $this->ci->db->select('money')->from('order')->where('id', $order['id'])->get()->row_array();
                    //订单金额为X时讲邀请状态变为1
                    $limit_money = 0;


                    if ($order_money) {
                        if ($order_money['money'] >= $limit_money) {
                            //将邀请记录的is_finish_order变为1 下次不再加积分
                            $this->ci->bll_userinvite->updateFinishOrderPrune($user['id'], $user['mobile']);
                            $this->ci->bll_userinvite->sendInviteGiftPrune($invite_by);
                        }
                    }
                }


            }

            // 邀请有礼
            // $this->ci->load->bll('invite',null,'bll_invite');
            // $this->ci->bll_invite->send_ecoupon($order['uid'],$order['order_name']);

            // 升级勋章
            // $this->ci->load->bll('user',null,'bll_user');
            // $this->ci->bll_user->upgrade_badge($order['uid'],$order['order_name']);

        }

        return $result;
    }

    /*
    *前台订单
    */


    /*
    *获取session
    */
    private function get_uid_by_connect_id($session_id, $lock_order = false) {
        // return array('code' => '200', 'msg' => 17551); // @todo for test

        // $this->ci->load->model("session_model");
        // $session = $this->ci->session_model->get_session($session_id);
        $session = $this->ci->session->userdata;

        if (empty($session)) {
            return array('code' => '400', 'msg' => 'not this connect id ,maybe out of date');
        }

        $userdata = unserialize($session['user_data']);
        // if($lock_order){
        //     $now_time = time();
        //     if(isset($userdata['lock_order']) && ($now_time-$userdata['lock_order']<=3)){
        //         return array('code'=>'300','msg'=>'请勿重复提交订单,稍后请重试');
        //     }
        //     $userdata['lock_order'] = $now_time;
        //     $this->ci->session->set_userdata($userdata);
        //     // $this->session_model->update_session_userdata($session_id, $userdata);
        // }

        unset($userdata['user_data']);
        unset($userdata['connect_id']);

        if (!isset($userdata['id']) || $userdata['id'] == "") {
            return array('code' => '400', 'msg' => 'not this user,may be wrong connect id');
        }
        return array('code' => '200', 'msg' => $userdata['id']);
    }


    /*
     *生成初始化订单
     */
    private function geneOrder($uid, $source = 'pc',$address_id ='') {
        $order_info = $this->ci->b2o_parent_order_model->preOrderInfo($uid);
        if ($order_info == null) {
            $pay_name = '微信支付';
            $pay_parent_id = 7;
            $pay_id = 0;

            return $this->ci->b2o_parent_order_model->generate_order("b2o_parent_order", array("order_name" => "", 'pay_name' => $pay_name, 'pay_parent_id' => $pay_parent_id, 'pay_id' => $pay_id, "send_date" => '0000-00-00', "uid" => $uid, "time" => date("Y-m-d H:i:s"),"address_id"=>$address_id),'P');
        }
        else
        {
            /*默认支付方式start*/
            if ($source == 'pc') {
                $pay_array = $this->ci->config->item("pc_pay_array");
            } else {
                $pay_array = $this->ci->config->item("pay_array");
            }
            $pay_parent_id = $order_info['pay_parent_id'];
            $pay_id = $order_info['pay_id'];
            $pay_name = $pay_array[$pay_parent_id]['name'];

            $pay_arr = array(4,5,6,11);
            if (in_array($pay_parent_id,$pay_arr))
            {
                $pay_name = '微信支付';
                $pay_parent_id = 7;
                $pay_id = 0;
            }
            else if($pay_parent_id == '' || $pay_parent_id == 0)
            {
                $pay_name = '微信支付';
                $pay_parent_id = 7;
                $pay_id = 0;
            }
            /*默认支付方式end*/

            $order_id = $this->ci->b2o_parent_order_model->generate_order("b2o_parent_order", array(
                'order_name' => '',
                "uid" => $uid,
                'send_date' => '0000-00-00',
                'shtime' => '',
                'stime' => '',
                'pay_name' => $pay_name,
                'pay_parent_id' => $pay_parent_id,
                'pay_id' => $pay_id,
                'address_id' =>$address_id,
                "time" => date("Y-m-d H:i:s"),
                "channel" => '6'
            ),'P');

            return $order_id;
        }
    }

    function getDefaultSendTime($send_result){
        $shtime = '';
        $stime = '';
        foreach ($send_result as $key => $value) {
            foreach ($value['time'] as $k => $v) {
                if($v['disable'] == 'true'){
                    $shtime = $send_result[$key]['date_key'];
                    $stime = $send_result[$key]['time'][$k]['time_key'];
                    if($v['time_key'] == '0914' || $v['time_key'] == '1418' ){
                        $stime = '0918';
                    }
                    if($v['time_key'] == '1822'){
                        continue;
                    }
                    break 2;
                }
            }
        }
        return array('shtime'=>$shtime,'stime'=>$stime);
    }

    /*
     *获取配送时间
     */
    private function getSendTime($area_id, $uid) {

        //预售商品配送时间
        if ($adv_send_date = $this->ci->order_model->check_advsale_sendtime($uid)) {
            $new_send_date = date('Ymd', strtotime($adv_send_date));
            $first_date = $new_send_date > date('Ymd') ? $new_send_date : '';
            return array('first_date' => $first_date, 'first_time' => '');
        }

        $cart_arr = $this->cart_info;
        if (!empty($cart_arr["items"])) {//有特殊物品必须2-3天发货
            $is_after2to3days = 0;
            foreach ($cart_arr["items"] as $val) {
                if ($val["delivery_limit"] == 1) {
                    $is_after2to3days = true;
                    break;
                }
            }
        }

        if ($is_after2to3days) {
            return array('first_date' => 'after2to3days', 'first_time' => '');
        }

        $this->ci->load->model("region_model");
        $area_info = $this->ci->region_model->get_area_info($area_id);
        if (empty($area_info)) {
            return array('first_date' => '', 'first_time' => '');
        }

        $send_time = $this->ci->order_model->defaultSendTime($area_info);
        return $send_time;
    }

    /*
    *积分优惠券初始化
    */
    private function card_jf_init($order_id, $uid, $source, $version, $card_limit,$total_method_money=0) {

        $cart_info = $this->cart_info;
        $goods_money = $cart_info['total']['discounted_price'];
        $pay_discount = 0;
        $method_money = $total_method_money;

        $result = $this->ci->b2o_parent_order_model->get_card_jf($order_id);

        $this->ci->load->model('card_model');
        $card_info = $this->ci->card_model->get_card_info($result['use_card']);
        $other_discount = $pay_discount;

        //优惠码使用 -fix
        if(isset($card_info['uid']) && $card_info['uid'] != 0 || empty($card_info))
        {
            $card_info = $this->ci->card_model->get_orderinit_card($uid, $goods_money, $source, $result['jf_money'], $other_discount, 0, $cart_info, $card_limit, $method_money);
        }

        if ($card_info) {
            $data = array(
                'use_card' => $card_info['card_number'],
                'card_money' => $card_info['card_money'],
            );
            $where = array(
                'id' => $order_id,
                'order_status' => 0,
            );
            $this->ci->b2o_parent_order_model->update_order($data, $where);
        } else {
            $this->ci->b2o_parent_order_model->init_order_card($order_id);
        }

        $result = $this->ci->b2o_parent_order_model->get_card_jf($order_id);
        $can_use_card_number = $this->ci->card_model->b2o_can_use_card_number($uid, $goods_money, $source, $result['jf_money'], $other_discount, 0, $cart_info, $card_limit,$cart_info['products']);
        $user_jf = $this->ci->user_model->getUserScore($uid);
        if (count($result) > 0) {
            if ($result['use_jf'] > $user_jf) {
                $this->ci->b2o_parent_order_model->init_order_jf($order_id);
                $result['jf_money'] = 0;
            }
            if (!empty($card_info)) {

                $can_use = $this->ci->card_model->card_can_use($card_info, $uid, $goods_money, $source, $result['jf_money'], $other_discount);
                if ($can_use[0] == 0) {
                    $this->ci->b2o_parent_order_model->init_order_card($order_id);
                    $result['card_money'] = 0;
                }

                if (!empty($card_info['product_id'])) {
                    $card_product_id = explode(',', $card_info['product_id']);
                    $card_product_can_use = false;
                    foreach ($cart_info['products'] as $key => $value) {
                        if (in_array($value['product_id'], $card_product_id)) {
                            $card_product_can_use = true;
                            break;
                        }
                    }
                    if (!$card_product_can_use) {
                        $this->ci->b2o_parent_order_model->init_order_card($order_id);
                        $result['card_money'] = 0;
                    }
                }
            } else {
                $this->ci->b2o_parent_order_model->init_order_card($order_id);
            }
            if ($pay_discount > ($goods_money - $result['card_money'])) {
                $this->ci->b2o_parent_order_model->init_pay_discount($order_id);
            }
        }
        return $can_use_card_number;
    }

    /*
    *获取所有进行中的促销规则
    */
    function getProSaleRules($num) {
        $this->ci->load->model("promotion_model");
        $pro_sale_first = $this->ci->promotion_model->get_single_promotion($num);
        return $pro_sale_first;
    }

    /*
    *单品促销活动
    */
    function checkProSale($cart_info, $uid, $source, $device_code) {
        $cut_money = 0;
        $active_rules = array();
        $pro_sale_first = $this->getProSaleRules(4);
        if (empty($pro_sale_first)) {
            return false;
        }

        $product_ids = array();
        foreach ($cart_info['products'] as $key => $value) {
            $product_ids[$value['product_id']] = $value['product_id'];
        }
        foreach ($pro_sale_first as $key => $value) {
            $product_arr = explode(',', $value['product_id']);
            foreach ($product_arr as $k => $v) {
                if (isset($product_ids[$v])) {
                    $rule = unserialize($value['content']);
                    if ($value['device_limit'] == '1' && $source == 'app') {
                        $device_code = isset($device_code) ? $device_code : '';
                        $this->ci->db->from('active_limit');
                        $this->ci->db->where(array('uid' => $uid, 'device_code' => $device_code, 'active_tag' => $value['active_tag']));
                        if ($this->ci->db->get()->num_rows() == 0) {
                            if ($value['account_limit'] == '1') {
                                $this->ci->db->from('active_limit');
                                $this->ci->db->where(array('uid' => $uid, 'active_tag' => $value['active_tag']));
                                if ($this->ci->db->get()->num_rows() == 0) {
                                    $cut_money += $rule['cut_money'];
                                    $active_rules[$key]['account_limit'] = $value['account_limit'];
                                    $active_rules[$key]['device_limit'] = $value['device_limit'];
                                    $active_rules[$key]['active_tag'] = $value['active_tag'];
                                }
                            } else {
                                $cut_money += $rule['cut_money'];
                                $active_rules[$key]['account_limit'] = $value['account_limit'];
                                $active_rules[$key]['device_limit'] = $value['device_limit'];
                                $active_rules[$key]['active_tag'] = $value['active_tag'];
                            }
                        }
                    } else {
                        if ($value['account_limit'] == '1') {
                            $this->ci->db->from('active_limit');
                            $this->ci->db->where(array('uid' => $uid, 'active_tag' => $value['active_tag']));
                            if ($this->ci->db->get()->num_rows() == 0) {
                                $cut_money += $rule['cut_money'];
                                $active_rules[$key]['account_limit'] = $value['account_limit'];
                                $active_rules[$key]['device_limit'] = $value['device_limit'];
                                $active_rules[$key]['active_tag'] = $value['active_tag'];
                            }
                        } else {
                            $cut_money += $rule['cut_money'];
                            $active_rules[$key]['account_limit'] = $value['account_limit'];
                            $active_rules[$key]['device_limit'] = $value['device_limit'];
                            $active_rules[$key]['active_tag'] = $value['active_tag'];
                        }
                    }

                }
            }
        }
        return array('cut_money' => $cut_money, 'active_rules' => $active_rules);
    }

    function get_active_limit($uid, $tag) {
        $this->ci->db->from('active_limit');
        $this->ci->db->where(array('uid' => $uid, 'active_tag' => $tag));
        $query = $this->ci->db->get();
        $rows = $query->num_rows();
        return $rows;
    }

    function insert_sales_rule_limit_log($uid, $order_id, $tag) {
        $active_limit_data = array(
            'uid' => $uid,
            'active_tag' => $tag,
            'order_id' => $order_id
        );
        $this->ci->db->insert('active_limit', $active_limit_data);
    }

    /*
    *新满额减
    */
    function checkProSale_upto($cart_info, $uid, $source) {
        $source2number = array("pc" => 1, "app" => 2, "wap" => 3);
        $source = $source2number[$source];
        $pro_sale_upto = $this->getProSaleRules(5);
        if (empty($pro_sale_upto)) {
            return false;
        }
        $enough_sales_money = 0;
        $affect_rule_tags = array();
        foreach ($pro_sale_upto as $k => $v) {
            $content = unserialize($v['content']);
            $channel = unserialize($content['channel']);
            $rule_product_id = explode(',', $v['product_id']);
            $in_rules_money = 0;
            if (in_array($source, $channel)) {
                if (!$content['isrepeat']) {
                    $rows = $this->get_active_limit($uid, $v['active_tag']);
                    if ($rows == 0) {
                        foreach ($cart_info['products'] as $key => $value) {
                            if (in_array($value['product_id'], $rule_product_id)) {
                                $in_rules_money += $value['amount'];
                            }
                        }
                        if ($in_rules_money >= $content['pro_upto_money']) {
                            $enough_sales_money += $content['cut_money'];
                            $affect_rule_tags[] = $v['active_tag'];
                        }
                    }
                } else {
                    foreach ($cart_info['products'] as $key => $value) {
                        if (in_array($value['product_id'], $rule_product_id)) {
                            $in_rules_money += $value['amount'];
                        }
                    }
                    if ($in_rules_money >= $content['pro_upto_money']) {
                        $enough_sales_money += $content['cut_money'];
//                                $affect_rule_tags[] = $v['active_tag'];
                    }
                }
//                        echo $enough_sales_money;
//                        var_dump($affect_rule_tags);
            }
        }
        $cut_money = $enough_sales_money;
        return array('cut_money' => $cut_money, 'affect_rule_tags' => $affect_rule_tags);
    }

    /*
    *初始化订单
    */
    function orderInit($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'address_id' => array('required' => array('code' => '500', 'msg' => 'address id can not be null')),
            'area_adcode' => array('required' => array('code' => '500', 'msg' => 'area_adcode can not be null')),
            'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
            'delivery_code' => array('required' => array('code' => '500', 'msg' => 'delivery_code can not be null')),
            'tms_region_type' => array('required' => array('code' => '500', 'msg' => 'tms_region_type can not be null')),
            //'tms_region_time' => array('required' => array('code' => '500', 'msg' => 'tms_region_time can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //银行秒杀排队
        //$bank_limit = $this->orderLimit();
        //if($bank_limit['code'] != 200)
        //{
        //    return array("code"=>$bank_limit['code'],"msg"=>$bank_limit['msg']);
        //}

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
            $uid = (string)$uid;
        }
        //获取session信息end

        $cart_info = $this->cart_info;
        if(empty($cart_info['products']))
        {
            return array("code" => "400", "msg" => "登录失效，请重新登录账号");
        }
        if (!$cart_info) {
            return array("code" => "300", "msg" => "您的购物车是空的，请返回添加商品");
        }


        //检查用户地址
        $user_addr = $this->ci->b2o_parent_order_model->get_user_addr($uid,$params['address_id']);
        if(empty($user_addr))
        {
            return array("code" => "300", "msg" => "您选择的下单地址错误，请重新选择");
        }

        //检查用户gps
        $gps_res  = $this->getTmsStore($user_addr['lonlat'],$user_addr['area_adcode']);
        if(empty($gps_res['data']['store_id_list']) || empty($gps_res['data']['delivery_code']) || $gps_res['data']['delivery_code'] != $params['delivery_code'])
        {
            return array("code" => "300", "msg" => "您选择的下单地址定位已变更，请重新选择收货地址");
        }

        //获取订单初始化数据start
        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        if (!$order_id) {
            $order_id = $this->geneOrder($uid, $params['source'],$params['address_id']);
        }

        //检查门店是否营业
        $store_ids = array();
        $is_close = 0;
        $store = $this->ci->b2o_store_model->storeIsOpen($params['store_id_list']);
        foreach($store as $k=>$v)
        {
            if($v['is_open'] == 1)
            {
                array_push($store_ids,$v['id']);
            }
            else
            {
                $is_close = 1;
            }
        }
        if($is_close == 1)
        {
            $sid_list = implode(',',$store_ids);
            $re_data = array('store_id_list'=>$sid_list,'delivery_code'=>$params['delivery_code']);
            return array('code'=>'314','msg'=>'已购部分商品已下架','data'=>$re_data);
        }

        //事务 start
        $this->ci->db->trans_begin();

        //更改用户地址
        $data = array(
            'address_id' =>$params['address_id']
        );
        $where = array(
            'id' => $order_id
        );
        $this->ci->b2o_parent_order_model->update_order($data, $where);
        
        //积分、余额、优惠券初始化start
        $check_result = $this->ci->b2o_parent_order_model->check_cart_pro_status($cart_info);
        if ($check_result['card_limit'] == '1') {
            //$this->can_use_card = 0;
            //$this->ci->b2o_parent_order_model->init_order_card($order_id);
        }
        if ($check_result['jf_limit'] == '1') {
            $this->can_use_jf = 0;
            $this->ci->b2o_parent_order_model->init_order_jf($order_id);
        }

        //包裹
        $open_flash_send = $params['open_package'] == 1 ? true : false;
        $package =  $this->package($cart_info,$params['area_adcode'],$params['delivery_code'],$open_flash_send,$params['tms_region_type'],$params['is_day_night'],$gps_res['data']['delivery_end_time']);
        if(isset($package['code']))
        {
            return array('code'=>$package['code'],'msg'=>$package['msg']);
        }
        $total_method_money = 0;
        $package_invoice = array();
        foreach($package as $key=>$val)
        {
            $total_method_money += $val['method_money'];
            array_push($package_invoice,$val['package_type']);
        }
        $can_use_card_number = $this->card_jf_init($order_id, $uid, $params['source'], $params['version'], $check_result['card_limit'],$total_method_money);

        $this->ci->b2o_parent_order_model->deduction_init($order_id);
        //积分、余额、优惠券初始化end

        $order_info = $this->ci->b2o_parent_order_model->init_order($order_id,$cart_info,$params['address_id']);
        $order_info['package'] = $package;
        $order_info['package_count'] = count($package);

        /*是否可以使用积分优惠券start*/
        $order_info['can_use_card'] = $this->can_use_card;
        $order_info['can_use_jf'] = $this->can_use_jf;
        /*是否可以使用积分优惠券end*/
        $order_info['can_use_card_number'] = $can_use_card_number;

        //默认不使用优惠券
        $this->ci->b2o_parent_order_model->init_order_card($order_id);

        //默认不使用闪鲜卡
        $this->ci->b2o_parent_order_model->init_order_fresh($order_id);

        //默认不使用积点
        $this->ci->b2o_parent_order_model->init_order_jd($order_id);

        //购物车
        $order_info['carts_info'] = $cart_info;

        //发票
        $pk_invoice_type = 1;
        if(in_array($pk_invoice_type,$package_invoice))
        {
            $order_info['invoice_show'] = 2;
        }
        else if(!in_array($pk_invoice_type,$package_invoice) && $order_info['support_einvoice'] == 0)
        {
            $order_info['invoice_show'] = 1;
        }
        else
        {
            $order_info['invoice_show'] = 0;
        }

        //重置发票
        if($order_info['invoice_show'] == 2)
        {
            $invoice_info = $this->ci->b2o_parent_order_model->get_invoice_info($order_id);
            if(!empty($invoice_info))
            {
                $this->ci->b2o_parent_order_model->delete_order_invoice($order_id);
                $invoice_data = array(
                    'fp' =>'',
                    'fp_dz'=>'',
                    'invoice_money'=>'0.00'
                );
                $invoice_where = array(
                    'id' => $order_id
                );
                $this->ci->b2o_parent_order_model->update_order($invoice_data,$invoice_where);
            }
        }

        //会员赠品验证
        $err = $this->check_gift_money_limit($cart_info, $cart_info['total']['discounted_price']);
        if($err)
            return ["code" =>"300", "msg"=>$err];

        //发票详情
        $b2o_invoice = $this->b2oInvoiceInfo($params);
        if($order_info['invoice_show'] == 2)
        {
            $b2o_invoice['data']['invoice_name'] = '';
            $order_info['fp'] = '';
        }
        $order_info['invoice_info']  = $b2o_invoice['data'];

        //发票类目筛选 -staging:85
        $is_food = 0;
        $food_arr = array('5','7','8');
        foreach($cart_info['products'] as $k=>$v)
        {
            $class_id = $this->ci->product_model->getProductTopBackendClass($v['product_id']);
            if(in_array($class_id,$food_arr))
            {
                $is_food = 1;
            }
        }

        //重置食品发票
        if($is_food == 0 && $order_info['invoice_info']['kp_type'] == 2)
        {
            $this->ci->b2o_parent_order_model->delete_order_invoice($order_id);
            $invoice_data = array(
                'fp' =>'',
                'fp_dz'=>'',
                'invoice_money'=>'0.00'
            );
            $invoice_where = array(
                'id' => $order_id
            );
            $this->ci->b2o_parent_order_model->update_order($invoice_data,$invoice_where);
        }

        $order_info['is_invoice_food'] = $is_food;

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array('code' => 300, 'msg' => '创建失败，请重新创建订单');
        } else {
            $this->ci->db->trans_commit();
        }

        //隐藏价格
        if(in_array(1,$package_invoice))
        {
            $order_info['isSupportHidePrice'] = 0;
        }
        else
        {
            $order_info['isSupportHidePrice'] = 1;
        }

        //用户积点
        $order_info['jd'] = $this->getJd($uid);

        return $order_info;
    }

    /**
    * @api {post} / 获取收货地址列表
    * @apiDescription 获取收货地址列表
    * @apiGroup order
    * @apiName getAddrList
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    * @apiParam {String} [use_case] order大客户订单
    *
    * @apiSampleRequest /api/test?service=order.getAddrList&source=app
    */
    function getAddrList($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }

        //获取session信息end

        $use_case = isset($params['use_case']) ? $params['use_case'] : '';
        $addr_list = $this->ci->user_model->geta_user_address($uid, '', $use_case, $params['source']);

        //倒序
        $addr_list_rev = array_reverse($addr_list);

        $top = array();
        foreach($addr_list_rev as $key=>$val)
        {
            if($val['isDefault'] == 1)
            {
                array_push($top,$addr_list_rev[$key]);
                unset($addr_list_rev[$key]);
            }
        }
        
        $list = array_merge($top,$addr_list_rev);

        $res = $this->returnMsg($list);
        return $res;
    }

    /**
    * @api {post} / 添加收货地址
    * @apiDescription 添加收货地址
    * @apiGroup order
    * @apiName addAddr
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    * @apiParam {String} [name] 收货人姓名
    * @apiParam {String} [mobile] 收货人手机
    * @apiParam {String} [lonlat] 收货地址坐标
    * @apiParam {String} [area_adcode] 区行政编码
    * @apiParam {String} [province_name] 省名称
    * @apiParam {String} [city_name] 市名称
    * @apiParam {String} [area_name] 区名称
    * @apiParam {String} [address_name] 地址名称
    * @apiParam {String} [address] 详细地址名称
    * @apiParam {String} [telepnone] 电话
    * @apiParam {String} [flag] 标记
    * @apiParam {String} [default] 是否默认
    *
    * @apiSampleRequest /api/test?service=order.addAddr&source=app
    */
    function addAddr($params) {

        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'name' => array('required' => array('code' => '300', 'msg' => '请输入收货人姓名'), 'length' => array('length' => 90, 'code' => '300', 'msg' => '收货人姓名过长')),
            'lonlat' => array('required' => array('code' => '300', 'msg' => '无定位坐标')),
            'area_adcode' => array('required' => array('code' => '300', 'msg' => '请选择完整的地区')),
            'address_name' => array('required' => array('code' => '300', 'msg' => '请输入收货地址'), 'length' => array('length' => 200, 'code' => '300', 'msg' => '收货人地址过长')),
            'mobile' => array('required' => array('code' => '300', 'msg' => '请输入收货人手机')),
            'province_name' => array('required' => array('code' => '300', 'msg' => '请选择完整的地区')),
            //'city_name' => array('required' => array('code' => '300', 'msg' => '请选择完整的地区')),
            // 'area_name' => array('required' => array('code' => '300', 'msg' => '请选择完整的地区')),
            'address' => array('required' => array('code' => '300', 'msg' => '请选择完整的地址')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        if (mb_strlen($params['name']) > 15) {
            return array('code' => '300', 'msg' => '收货人姓名过长，请控制在15个字以内');
        }

        if (!is_mobile($params['mobile'])) {
            return array('code' => '300', 'msg' => '手机号码格式错误');
        }

        if (mb_strlen($params['address']) > 80) {
            return array('code' => '300', 'msg' => '收货人地址过长，请控制在80个字以内');
        }

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end
        $params['name'] = preg_replace_callback('/[\xf0-\xf7].{3}/', function ($r) {
            return '';
        }, $params['name']);
        if (empty($params['name'])) {
            return array('code' => '300', 'msg' => '请正确填写您的收货人姓名');
        }
        $this->ci->load->model('area_model');
        $params['province_adcode'] = mb_substr($params['area_adcode'],0,2).'0000';
        $params['city_adcode'] = mb_substr($params['area_adcode'],0,4).'00';
        $area = $this->ci->area_model->dump(array('adcode'=>$params['area_adcode'],'active'=>1,'tree_lvl'=>3));
        $city = $this->ci->area_model->dump(array('adcode'=>$params['city_adcode'],'active'=>1,'tree_lvl'=>2));
        $province = $this->ci->area_model->dump(array('adcode'=>$params['province_adcode'],'active'=>1,'tree_lvl'=>1));

        //配送地址匹配
        $tms = $this->getTmsStore($params['lonlat'],$params['area_adcode']);
        if($tms['code'] == 200)
        {
            if(empty($tms['data']['store_id_list']) || empty($tms['data']['delivery_code']))
            {
                return array('code' => '300', 'msg' => '该地址不支持配送');
            }
        }
        else
        {
            return array('code' => '300', 'msg' => '定位区域错误');
        }

        $data = array(
            'uid' => $uid,
            'name' => $this->ci->security->xss_clean($params['name']),
            'province' => $province['id']?$province['id']:0,
            'city' => $city['id']?$city['id']:0,
            'area' => $area['id']?$area['id']:0,
            'address' => $params['address'],
            'address_name' => $this->ci->security->xss_clean($params['address_name']),
            'telephone' => $params['telephone'] ? strip_tags($params['telephone']) : '',
            'mobile' => strip_tags($params['mobile']),
            'flag' => isset($params['flag']) ? strip_tags($params['flag']) : '',
            'is_default' => isset($params['default']) ? $params['default'] : '0',
            'lonlat' => $params['lonlat'],
            'province_adcode' => $params['province_adcode'],
            'city_adcode' => $params['city_adcode'],
            'area_adcode' => $params['area_adcode'],
            'province_name' => $params['province_name'],
            'city_name' => $params['city_name']?$params['city_name']:$city['name'],
            'area_name' => $params['area_name']?$params['area_name']:'',
        );
        $data['id'] = $this->ci->order_model->add_user_address($data);

        //构建
        $data['province'] = array('id'=>$data['province'],'name'=>$data['province_name']);
        $data['city'] = array('id'=>$data['city'],'name'=>$data['city_name']);
        $data['area'] = array('id'=>$data['area'],'name'=>$data['area_name']);
        $data['isDefault'] = $data['is_default'];

        $res = $this->returnMsg($data);
        return $res;
    }

    /**
    * @api {post} / 修改收货地址
    * @apiDescription 修改收货地址
    * @apiGroup order
    * @apiName updateAddr
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    * @apiParam {String} [address_id] 收货地址ID
    * @apiParam {String} [name] 收货人姓名
    * @apiParam {String} [mobile] 收货人手机
    * @apiParam {String} [lonlat] 收货地址坐标
    * @apiParam {String} [area_adcode] 区行政编码
    * @apiParam {String} [province_name] 省名称
    * @apiParam {String} [city_name] 市名称
    * @apiParam {String} [area_name] 区名称
    * @apiParam {String} [address_name] 地址名称
    * @apiParam {String} [address] 详细地址名称
    * @apiParam {String} [telepnone] 电话
    * @apiParam {String} [flag] 标记
    * @apiParam {String} [default] 是否默认
    *
    * @apiSampleRequest /api/test?service=order.updateAddr&source=app
    */
    function updateAddr($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'address_id' => array('required' => array('code' => '500', 'msg' => 'address id can not be null')),
            'name' => array('required' => array('code' => '300', 'msg' => '请输入收货人姓名'), 'length' => array('length' => 90, 'code' => '300', 'msg' => '收货人姓名过长')),
            'lonlat' => array('required' => array('code' => '300', 'msg' => '无定位坐标')),
            'area_adcode' => array('required' => array('code' => '300', 'msg' => '请选择完整的地区')),
            'address_name' => array('required' => array('code' => '300', 'msg' => '请输入收货地址'), 'length' => array('length' => 200, 'code' => '300', 'msg' => '收货人地址过长')),
            'mobile' => array('required' => array('code' => '300', 'msg' => '请输入收货人手机')),
            'province_name' => array('required' => array('code' => '300', 'msg' => '请选择完整的地区')),
            //'city_name' => array('required' => array('code' => '300', 'msg' => '请选择完整的地区')),
            //'area_name' => array('required' => array('code' => '300', 'msg' => '请选择完整的地区')),
            'address' => array('required' => array('code' => '300', 'msg' => '请选择完整的地址')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end
        if (mb_strlen($params['name']) > 15) {
            return array('code' => '300', 'msg' => '收货人姓名过长，请控制在15个字以内');
        }
        if (!is_mobile($params['mobile'])) {
            return array('code' => '300', 'msg' => '手机号码格式错误');
        }

        if (mb_strlen($params['address']) > 80) {
            return array('code' => '300', 'msg' => '收货人地址过长，请控制在80个字以内');
        }

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        $params['name'] = preg_replace_callback('/[\xf0-\xf7].{3}/', function ($r) {
            return '';
        }, $params['name']);
        if (empty($params['name'])) {
            return array('code' => '300', 'msg' => '请正确填写您的收货人姓名');
        }
        $this->ci->load->model('area_model');
        $params['province_adcode'] = mb_substr($params['area_adcode'],0,2).'0000';
        $params['city_adcode'] = mb_substr($params['area_adcode'],0,4).'00';
        $area = $this->ci->area_model->dump(array('adcode'=>$params['area_adcode'],'active'=>1,'tree_lvl'=>3));
        $city = $this->ci->area_model->dump(array('adcode'=>$params['city_adcode'],'active'=>1,'tree_lvl'=>2));
        $province = $this->ci->area_model->dump(array('adcode'=>$params['province_adcode'],'active'=>1,'tree_lvl'=>1));

        //配送地址匹配
        $tms = $this->getTmsStore($params['lonlat'],$params['area_adcode']);
        if($tms['code'] == 200)
        {
            if(empty($tms['data']['store_id_list']) || empty($tms['data']['delivery_code']))
            {
                return array('code' => '300', 'msg' => '该地址不支持配送');
            }
        }
        else
        {
            return array('code' => '300', 'msg' => '定位区域错误');
        }

        $data = array(
            'name' => $this->ci->security->xss_clean($params['name']),
            'province' => $province['id']?$province['id']:0,
            'city' => $city['id']?$city['id']:0,
            'area' => $area['id']?$area['id']:0,
            'address' => $params['address'],
            'address_name' => $this->ci->security->xss_clean($params['address_name']),
            'telephone' => strip_tags($params['telephone']),
            'mobile' => strip_tags($params['mobile']),
            'flag' => isset($params['flag']) ? strip_tags($params['flag']) : '',
            'is_default' => isset($params['default']) ? $params['default'] : '0',
            'lonlat' => $params['lonlat'],
            'province_adcode' => $params['province_adcode'],
            'city_adcode' => $params['city_adcode'],
            'area_adcode' => $params['area_adcode'],
            'province_name' => $params['province_name'],
            'city_name' => $params['city_name']?$params['city_name']:$city['name'],
            'area_name' => $params['area_name']?$params['area_name']:'',
        );
        $where = array('id' => $params['address_id'], 'uid' => $uid);
        $this->ci->order_model->update_user_address($data, $where);
        $data['uid'] = $uid;
        $data['id'] = $params['address_id'];

        //构建
        $data['province'] = array('id'=>$data['province'],'name'=>$data['province_name']);
        $data['city'] = array('id'=>$data['city'],'name'=>$data['city_name']);
        $data['area'] = array('id'=>$data['area'],'name'=>$data['area_name']);
        $data['isDefault'] = $data['is_default'];

        $res = $this->returnMsg($data);
        return $res;
    }

    /**
    * @api {post} / 删除收货地址
    * @apiDescription 删除收货地址
    * @apiGroup order
    * @apiName deleteAddr
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    * @apiParam {String} [address_id] 收货地址ID
    *
    * @apiSampleRequest /api/test?service=order.deleteAddr&source=app
    */
    function deleteAddr($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'address_id' => array('required' => array('code' => '500', 'msg' => 'address id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end
        $this->ci->order_model->delete_user_address(array('address_id' => $params['address_id'], 'uid' => $uid));
        $data['id'] = $params['address_id'];
        return array('code' => '200', 'msg' => 'succ','data'=>$data);
    }

    /**
    * @api {post} / get
    * @apiDescription 选择收货地址
    * @apiGroup order
    * @apiName deleteAddr
    *
    * @apiParam {String} [connect_id] 登录Token
    * @apiParam {String} [device_id=5aee7f491ffe2a6edc44bbf94a4cb8cc] 登录Token
    * @apiParam {String} [address_id] 收货地址ID
    *
    * @apiSampleRequest /api/test?service=order.addAddr&source=app
    */
    function choseAddr($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'address_id' => array('required' => array('code' => '500', 'msg' => 'address id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        $address_id = $params['address_id'];


        return array('code' => '200', 'msg' => $details);

    }

    /*
    *商品是否可配送判断
    */
    function sendRegionFilter($address_id = '', $cart_info) {
        $cart_array = $cart_info['products'];
        $this->ci->load->model('region_model');
        $area_result = $this->ci->region_model->get_province_id($address_id);
        $area_id = $area_result['area'];
        $province_id = $area_result['province'];

        if (mb_strlen($area_result['name']) > 15) {
            return array('code' => '300', 'msg' => '您填写收货人姓名过长，请控制在15个字以内');
        }

        if (count($cart_array) > 0) {
            $ids = array();
            foreach ($cart_array as $key => $value) {
                $ids[] = $value['product_id'];
            }

            $where_in[] = array('key' => 'id', 'value' => array_unique($ids));
            $send_region_arr = $this->ci->product_model->selectProducts('id,send_region', '', $where_in);
            $can_not_send_pro_arr = array();
            foreach ($send_region_arr as $key => $value) {
                $can_send_region = unserialize($value['send_region']);
                if (is_array($can_send_region)) {
                    if (!in_array($province_id, $can_send_region)) {
                        $can_not_send_pro_arr[$value['id']] = implode($this->ci->region_model->get_send_region($can_send_region, 'order'), '，');
                    }
                } else {
                    continue;
                }
            }

            $can_not_send_sku_arr = array();
            foreach ($cart_array as $key => $value) {
                $is_lack = false;

                if (isset($can_not_send_pro_arr[$value['product_id']]) || $is_lack) {
                    $can_not_send_sku_arr[] = $value['name'];
                }
            }

            if (count($can_not_send_sku_arr) > 0) {

                return $can_not_send_sku_arr;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }


    /*
    *查询收货地址
    */
    function getAddr($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'address_id' => array('required' => array('code' => '500', 'msg' => 'address id can not be null')),
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end
        $address_id = $params['address_id'];
        $use_case = isset($params['use_case']) ? $params['use_case'] : '';
        $addr_list = $this->ci->user_model->geta_user_address($uid, $address_id, $use_case, $params['source']);

        if (empty($addr_list)) {
            $return_result = array('code' => '300', 'msg' => '请添加收货地址');
            return $return_result;
        } else {
            return $addr_list[0];
        }
    }

    /*
    *选择收货时间
    */
    function choseSendtime($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'send_date' => array('required' => array('code' => '300', 'msg' => '请选择配送时间')),
            'send_time' => array('required' => array('code' => '300', 'msg' => '请选择配送时间')),
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end
        $send_date = $params['send_date'];
        $send_time = $params['send_time'];
        $order_id = $this->ci->order_model->get_order_id($uid);

        //预售商品配送时间
        if ($send_date == 'after2to3days' && $adv_send_date = $this->ci->order_model->check_advsale_sendtime($uid)) {
            $new_send_date = date('Ymd', strtotime($adv_send_date));
            if ($new_send_date > date('Ymd')) $send_date = $new_send_date;
        }

        $data = array(
            'shtime' => $send_date,
            'stime' => $send_time
        );
        $where = array(
            'id' => $order_id
        );
        $this->ci->order_model->update_order($data,$where);
        $details = $this->orderDetails($uid,$params['source'],$params['device_id']);
        if($details['shtime'] != $send_date) $details['shtime'] = $send_date;
        if($details['stime'] != $send_time) $details['stime'] = $send_time;

        if (strcmp($this->_version, '3.7.0') >= 0) {
            $formateDate = $this->ci->order_model->format_send_date($details['shtime'],$details['stime']);
            $details['shtime'] = $formateDate['shtime'];
            $details['stime'] = $formateDate['stime'];
        }

        //特殊处理
        if($params['source'] == 'app')
        {
            $details['goods_money'] = $details['total_amount_money'];
            $details['pay_discount'] = '0.00';
        }

        return array('code'=>'200','msg'=>$details);
    }

    /*
    *选择支付方式
    */
    function chosePayment($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'pay_parent_id' => array('required' => array('code' => '300', 'msg' => '请选择支付方式')),
            'pay_id' => array('required' => array('code' => '300', 'msg' => '请选择支付方式')),
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end
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


        $order_id = $this->ci->order_model->get_order_id($uid);
        //事务开始
        $this->ci->db->trans_begin();
        // $data = array(
        //     'pay_parent_id'=>$pay_parent_id,
        //     'pay_id'=>$pay_id,
        //     'pay_name'=>$pay_name
        // );
        // $where = array(
        //     'id'=>$order_id
        // );
        $this->ci->order_model->set_ordre_payment($pay_name, $pay_parent_id, $pay_id, $order_id);

        /*余额支付帐号手机绑定验证start*/
        $order_info = $this->orderDetails($uid, $params['source'], $params['device_id']);
        if ($pay_parent_id == 5) {
            $result = $this->ci->user_model->selectUser('money,mobile', array('id' => $uid));
            if (empty($result['mobile'])) {
                $this->ci->db->trans_rollback();
                return array('code' => '700', 'msg' => '帐户余额支付需要先绑定手机,是否立即绑定?');
            }
            if ($order_info['money'] > $result['money']) {
                $this->ci->db->trans_rollback();
                return array('code' => '600', 'msg' => '您的帐户余额不足，是否立即充值?');
            }
        }
        $this->ci->db->trans_commit();
        /*余额支付帐号手机绑定验证end*/
        $has_invoice = $this->ci->order_model->has_invoice($pay_parent_id, $pay_id);
        $no_invoice_message = '';
        if (!$has_invoice) {
            $no_invoice_message = '您选择的支付方式不支持开发票';
        }
        $order_info['has_invoice'] = $has_invoice;
        $order_info['no_invoice_message'] = $no_invoice_message;


        /* 提交订单短信验证  start */
        $cart_info = $this->cart_info;
        $cart_array = $cart_info['products'];
        $order_address_info = $this->ci->order_model->get_order_address($order_info['address_id']);
        $order_info['need_send_code'] = 0;
        $need_send_code = $this->ci->order_model->checkSendCode($cart_array, $uid, $pay_parent_id, $order_address_info);
        if ($need_send_code) {
            $order_info['need_send_code'] = 1;
        }
        /* 提交订单短信验证  start */

        /*余额改造 start*/
        //$order_info['need_online_pay'] = $order_info['money'] - $order_info['use_money_deduction'];
        /*余额改造 end*/

        //特殊处理
        if($params['source'] == 'app')
        {
            $order_info['goods_money'] = $order_info['total_amount_money'];
            $order_info['pay_discount'] = '0.00';
        }

        return array('code' => '200', 'msg' => $order_info);
    }

    /*
    *验证用户余额
    */
    function checkUserMoney($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end
        $details = $this->orderDetails($uid, $params['source'], $params['device_id']);

        $result = $this->ci->user_model->selectUser('money', array('id' => $uid));
        if (bccomp($result['money'], $details['money']) < 0) {
            return array('code' => '600', 'msg' => '你的帐户余额不足或充值金额还未到账，请充值或稍后再试');
        }
        return array('code' => '200', 'msg' => 'succ');
    }

    /*
    *订单详情获取通用方法
    * $uid           用户id
    * $source        请求来源
    * $device_code   设备号
    */
    function orderDetails($uid, $source = 'app', $device_code = '') {
        /*订单基本信息start*/
        $order_id = $this->ci->order_model->get_order_id($uid);
        $order_info = $this->ci->order_model->selectOrder("id,uid,order_name,shtime,stime,send_date,pay_name,pay_parent_id,pay_id,pay_status,operation_id,use_money_deduction,money,goods_money,jf_money,card_money,hk,msg,address_id,use_card,use_jf,order_status,invoice_money,pay_discount,fp,fp_dz,version", array("id" => $order_id));

        if ($this->use_card_obj) {
            $cardmoney = $this->card_money;
        } else {
            $cardmoney = $order_info['card_money'];
        }

        if ($this->use_jf_obj) {
            $jfmoney = $this->jf_money;
        } else {
            $jfmoney = $order_info['jf_money'];
        }
        $shtime = $order_info['shtime'];
        $stime = $order_info['stime'];
        $invoice_money = $order_info['invoice_money'];


        if ($stime != "") {
            $date = $shtime . "-" . $stime;
        } else {
            $date = $shtime;
        }
        /*订单基本信息end*/

        /*购物车信息start*/
        $cart_info = $this->cart_info;

        $goods_money = $cart_info['goods_amount'];
        $total_amount_money = $cart_info['total_amount'];

        /*购物车信息end*/


        /*运费计算start*/
        $method_info = $this->post_fee($cart_info, $total_amount_money, $order_info, $date);
        $method_money = $method_info['method_money'];
        /*运费计算end*/

        //支付折扣
        $o_area_info = $this->ci->order_model->getIorderArea($order_id);
        $s_from = 0;
        $pids = $this->ci->bll_cart->getCartProductID($cart_info);
        $pids = $pids['normal'];

        $promotion_discount = $cart_info['pmt_total'];
        $money = $goods_money + $invoice_money + $method_money - $cardmoney - $promotion_discount;

        /*积分限制start*/
        $order_jf_limit = $this->check_order_jf_limit($uid, $order_id, $money - $method_money - $invoice_money, $jfmoney);
        /*积分限制end*/
        $pay_discount_upto = 0;

        $money = $money - $jfmoney;

        $new_pay_discount = 0;
        if (strcmp($this->_version, '3.4.0') < 0) {
            $new_pay_discount = $this->ci->pay_discount_model->get_pay_discount($order_info['pay_parent_id'], $order_info['pay_id'], $money, $pids, $order_id, $source, $o_area_info['province'], $uid);
        }
        $money = $money - $new_pay_discount;

        $details['id'] = $order_info['id'];                             //订单id
        $details['uid'] = $order_info['uid'];                           //用户id
        $details['order_name'] = $order_info['order_name'];             //订单号
        $details['shtime'] = $shtime;                                   //配送日期
        $details['stime'] = $stime;                                     //配送时间
        $details['send_date'] = $order_info['send_date'];               //实际配送时间
        $details['pay_name'] = $order_info['pay_name'];                 //支付方式名称
        $details['pay_parent_id'] = $order_info['pay_parent_id'];       //支付父id
        $details['pay_id'] = $order_info['pay_id'];                     //支付id
        $details['pay_status'] = $order_info['pay_status'];             //支付状态
        $details['operation_id'] = $order_info['operation_id'];         //订单状态
        $details['use_money_deduction'] = $order_info['use_money_deduction'];     //账户余额抵扣金额
        $details['money'] = empty($money) ? 0 : $money;                     //订单金额
        $details['goods_money'] = $goods_money + $pay_discount_upto;      //商品金额
        $details['jf_money'] = empty($jfmoney) ? 0 : $jfmoney;              //积分抵扣金额
        $details['card_money'] = $cardmoney ? $cardmoney : 0;               //优惠券抵扣金额
        $details['hk'] = $order_info['hk'];                             //贺卡内容
        $details['msg'] = $order_info['msg'];                           //备注内容
        $details['address_id'] = $order_info['address_id'];             //配送地址id
        $details['use_card'] = $order_info['use_card'];                 //使用的优惠券
        $details['use_jf'] = $order_info['use_jf'];                     //使用的积分
        $details['order_status'] = $order_info['order_status'];         //订单生成状态,0:预生成状态;1:有效订单
        $details['invoice_money'] = $invoice_money;                     //发票配送费用
        $details['new_pay_discount'] = $new_pay_discount;               //支付折扣
        $details['promotion_discount'] = $promotion_discount;           //满减等活动抵扣金额
        $details['pay_discount'] = $promotion_discount + $new_pay_discount;     //其他折扣
        $details['fp'] = $order_info['fp'];                             //发票抬头
        $details['fp_dz'] = $order_info['fp_dz'];                       //发票配送地址
        $details['method_money'] = $method_money + $invoice_money;        //运费
        $details['order_method_money'] = $method_money;                 //订单运费
        $details['order_jf_limit'] = $order_jf_limit;                   //最大使用积分
        $details['cmoney'] = $method_info['cmoney'];                    //券卡类商品金额
        $details['order_limit'] = $method_info['order_limit'];          //最小提交订单金额
        $details['version'] = $order_info['version'];                   //订单版本
        $details['pay_discout_upto'] = $pay_discount_upto;              //新满减逻辑补差价字段

        //特殊处理
        $details['total_amount_money'] = $total_amount_money;   //购物车优惠价格

        /*余额改造 srart*/
        $user = $this->ci->user_model->getUser($uid);
        $details['user_money'] = $user['money'];

        if ($details['user_money'] >= $money) {
            $details['user_can_money'] = $details['user_money'] - $money;
        } else {
            $details['user_can_money'] = $details['user_money'] - $details['use_money_deduction'];
        }

        //返回选中地址
        $this->ci->load->model('region_model');
        $user_address_info = $this->ci->region_model->get_province_id($details['address_id']);
        $details['province'] = $user_address_info['province'];

        //支持开票
        $has_invoice = $this->ci->order_model->has_invoice($details['pay_parent_id'], $details['pay_id']);
        $no_invoice_message = '';
        if (!$has_invoice) {
            $no_invoice_message = '您选择的支付方式不支持开发票';
        }
        $details['has_invoice'] = $has_invoice;
        $details['no_invoice_message'] = $no_invoice_message;

        /*发票验证start*/
        if ($details['pay_parent_id'] == '5' && (!empty($details['fp']) || !empty($details['fp_dz']))) {
            $this->init_invoice($details['id']);
        }
        if ($details['pay_parent_id'] == '2' && (!empty($details['fp']) || !empty($details['fp_dz']))) {
            $this->init_invoice($details['id']);
        }
        if ($details['pay_parent_id'] == '4' && ($details['pay_id'] == 3 || $details['pay_id'] == 7 || $details['pay_id'] == 8 || $details['pay_id'] == 9 || $details['pay_id'] == 10 || $details['pay_id'] == 11) && (!empty($details['fp']) || !empty($details['fp_dz']))) {
            $this->init_invoice($details['id']);
        }

        if ($details['has_invoice'] == 0) {
            $details['method_money'] = $method_money;
            $details['invoice_money'] = 0.00;
            $details['money'] = $details['money'] - $invoice_money;
        }
        //根据收货地区判断是否支持电子发票,仅支持江、浙、沪、皖
        if (in_array($details['province'], array(1, 54351, 106340, 106092))) {
            $details['support_einvoice'] = 1;
//            $details['support_einvoice'] = 0;//暂时都改成不支持电子发票
        } else {
            $details['support_einvoice'] = 0;
        }
        $stime_value = '';
        switch ($details['stime']) {
            case '0918':
                $stime_value = '09:00-18:00';
                break;
            case '0914':
                $stime_value = '09:00-14:00';
                break;
            case '1418':
                $stime_value = '14:00-18:00';
                break;
            case '1822':
                $stime_value = '18:00-22:00';
                break;
            case 'weekday':
                $stime_value = '仅在工作日配送';
                break;
            case 'weekend':
                $stime_value = '仅在双休日、假日配送';
                break;
            case 'all':
                $stime_value = '工作日、双休日与假日均可配送';
                break;
            default:
                $stime_value = $details['stime'];
                break;
        }
        $details['stime_value'] = $stime_value;
        /*发票验证end*/

        /*余额改造 end*/

        return $details;
    }


    /*
   *订单详情获取通用方法
   * $uid           用户id
   * $source        请求来源
   * $device_code   设备号
   */
    function b2oOrderDetails($uid, $source = 'app', $device_code = '',$params) {
        /*订单基本信息start*/
        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        $order_info = $this->ci->b2o_parent_order_model->selectOrder("id,uid,order_name,shtime,stime,send_date,pay_name,pay_parent_id,pay_id,pay_status,operation_id,use_money_deduction,money,goods_money,jf_money,card_money,hk,msg,address_id,use_card,use_jf,order_status,invoice_money,pay_discount,fp,fp_dz,version,fresh_discount,fresh_no,jd_discount", array("id" => $order_id));

        if ($this->use_card_obj) {
            $cardmoney = $this->card_money;
        } else {
            $cardmoney = $order_info['card_money'];
        }

        if ($this->use_jf_obj) {
            $jfmoney = $this->jf_money;
        } else {
            $jfmoney = $order_info['jf_money'];
        }

        if ($this->use_jd_obj) {
            $jd_discount = $this->jd_discount;
        } else {
            $jd_discount = $order_info['jd_discount'];
        }

        $shtime = $order_info['shtime'];
        $stime = $order_info['stime'];
        $invoice_money = $order_info['invoice_money'];

        if ($stime != "") {
            $date = $shtime . "-" . $stime;
        } else {
            $date = $shtime;
        }
        /*订单基本信息end*/

        /*购物车信息start*/
        $cart_info = $this->cart_info;
        $goods_money = $cart_info['total']['price'];
        $total_amount_money = $cart_info['total']['discounted_price'];
        /*购物车信息end*/


        /*运费计算start*/
        $open_flash_send = $params['open_package'] == 1 ? true : false;
        $gps_res =  $this->getTmsStore($params['lonlat'],$params['area_adcode']);
        $package =  $this->package($cart_info,$params['area_adcode'],$params['delivery_code'],$open_flash_send,$params['tms_region_type'],$params['is_day_night'],$gps_res['data']['delivery_end_time']);
        $total_method_money = 0;
        $total_package_count = 0;
        $total_package_weight = 0;
        foreach($package as $key=>$val)
        {
            $total_method_money += $val['method_money'];
            foreach($val['item'] as $k=>$v)
            {
                $total_package_count += $v['qty'];
            }
            $total_package_weight += floatval($val['weight']);
        }

        $this->ci->load->bll('apimethod');
        $api_method = array();
        $api_method['cart_info'] = json_encode($cart_info);
        $api_method['area_code'] = $params['area_adcode'];
        $api_method['total_method_money'] = $total_method_money;
        $api_method['version'] = $params['version'];
        $api_method['uid'] = $order_info['uid'];
        $api_method['store_id_list'] = $params['store_id_list'];
        $method_money = $this->ci->bll_apimethod->get($api_method);
        //$method_money = $total_method_money;

        //自提
        if(isset($params['self_pick']) && !empty($params['self_pick']) && $method_money >= 5)
        {
            if(!isset($params['no_self_pick']))
            {
                $method_money = $method_money - 5;
            }
        }

        /*运费计算end*/

        $promotion_discount = $cart_info['total']['discount'];
        $money = $goods_money + $invoice_money + $method_money - $cardmoney - $promotion_discount;

        /*积分限制start*/
        $order_jf_limit = $this->check_b2o_order_jf_limit($uid, $order_id, $money - $method_money - $invoice_money, $jfmoney);
        /*积分限制end*/
        $pay_discount_upto = 0;
        $money = $money - $jfmoney;
        $new_pay_discount = 0;
        $money = $money - $new_pay_discount;

        //邮费特权
        $post_info = $this->ci->order_postage_model->getUserPostagePrivilegeInfo($order_info['uid'], date("Y-m-d H:i:s", $params['timestamp']),1);
        $post_config = $this->ci->config->item('postage_discount');
        $details['post_url'] = $post_config['url'];
        if($post_info && $method_money >= $post_config['money'])
        {
            $details['post_discount']  =  $post_config['money'];
        }
        else
        {
            $details['post_discount'] =  '0.00';
        }
        if(!empty($post_info))
        {
            $details['is_open_post'] = 1;
        }
        else
        {
            $details['is_open_post'] = 0;
        }
        $money = $money - $details['post_discount'];

        //闪鲜卡
        $money = $money - $order_info['fresh_discount'];

        //积点
        $money = bcsub($money,$jd_discount,2);
        //$money = $money - $order_info['jd_discount'];

        $details['id'] = $order_info['id'];                             //订单id
        $details['uid'] = $order_info['uid'];                           //用户id
        $details['order_name'] = $order_info['order_name'];             //订单号
        $details['shtime'] = $shtime;                                   //配送日期
        $details['stime'] = $stime;                                     //配送时间
        $details['send_date'] = $order_info['send_date'];               //实际配送时间
        $details['pay_name'] = $order_info['pay_name'];                 //支付方式名称
        $details['pay_parent_id'] = $order_info['pay_parent_id'];       //支付父id
        $details['pay_id'] = $order_info['pay_id'];                     //支付id
        $details['pay_status'] = $order_info['pay_status'];             //支付状态
        $details['operation_id'] = $order_info['operation_id'];         //订单状态
        $details['use_money_deduction'] = $order_info['use_money_deduction'];     //账户余额抵扣金额
        $details['money'] = empty($money) ? 0 : $money;                     //订单金额
        $details['goods_money'] = $goods_money + $pay_discount_upto;      //商品金额
        $details['jf_money'] = empty($jfmoney) ? 0 : $jfmoney;              //积分抵扣金额
        $details['card_money'] = $cardmoney ? $cardmoney : 0;               //优惠券抵扣金额
        $details['hk'] = $order_info['hk'];                             //贺卡内容
        $details['msg'] = $order_info['msg'];                           //备注内容
        $details['address_id'] = $order_info['address_id'];             //配送地址id
        $details['use_card'] = $order_info['use_card'];                 //使用的优惠券
        $details['use_jf'] = $order_info['use_jf'];                     //使用的积分
        $details['order_status'] = $order_info['order_status'];         //订单生成状态,0:预生成状态;1:有效订单
        $details['invoice_money'] = $invoice_money;                     //发票配送费用
        $details['new_pay_discount'] = $new_pay_discount;               //支付折扣
        $details['promotion_discount'] = $promotion_discount;           //满减等活动抵扣金额
        $details['pay_discount'] = $promotion_discount + $new_pay_discount;     //其他折扣
        $details['fp'] = $order_info['fp'];                             //发票抬头
        $details['fp_dz'] = $order_info['fp_dz'];                       //发票配送地址
        $details['method_money'] = $method_money + $invoice_money;        //运费
        $details['order_method_money'] = $method_money;                 //订单运费
        $details['order_jf_limit'] = $order_jf_limit;                   //最大使用积分
        //$details['cmoney'] = $method_info['cmoney'];                    //券卡类商品金额
        //$details['order_limit'] = $method_info['order_limit'];          //最小提交订单金额
        $details['version'] = $order_info['version'];                   //订单版本
        $details['pay_discout_upto'] = $pay_discount_upto;              //新满减逻辑补差价字段

        //特殊处理
        $details['total_amount_money'] = $total_amount_money;   //购物车优惠价格

        //5.2.0
        $details['total_package_count'] = $total_package_count;   //包裹商品总数量
        $details['total_package_weight'] = $total_package_weight; //包裹商品总重量

        //闪鲜卡
        $details['fresh_discount'] = $order_info['fresh_discount'];
        $details['fresh_no'] = $order_info['fresh_no'];

        //积点
        $details['jd_discount'] = $jd_discount;

        //支持开票
        $has_invoice = $this->ci->b2o_parent_order_model->has_invoice($details['pay_parent_id'], $details['pay_id']);
        $no_invoice_message = '';
        if (!$has_invoice) {
            $no_invoice_message = '您选择的支付方式不支持开发票';
        }
        $details['has_invoice'] = $has_invoice;
        $details['no_invoice_message'] = $no_invoice_message;

        /*发票验证start*/
        if ($details['pay_parent_id'] == '5' && (!empty($details['fp']) || !empty($details['fp_dz']))) {
            $this->init_b2oinvoice($details['id']);
        }
        if ($details['pay_parent_id'] == '2' && (!empty($details['fp']) || !empty($details['fp_dz']))) {
            $this->init_b2oinvoice($details['id']);
        }
        if ($details['pay_parent_id'] == '4' && ($details['pay_id'] == 3 || $details['pay_id'] == 7 || $details['pay_id'] == 8 || $details['pay_id'] == 9 || $details['pay_id'] == 10 || $details['pay_id'] == 11) && (!empty($details['fp']) || !empty($details['fp_dz']))) {
            $this->init_b2oinvoice($details['id']);
        }

        if ($details['has_invoice'] == 0) {
            $details['method_money'] = $method_money;
            $details['invoice_money'] = 0.00;
            $details['money'] = $details['money'] - $invoice_money;
        }

        //根据收货地区判断是否支持电子发票,仅支持江、浙、沪、皖
        $this->ci->load->model('region_model');
        $user_address_info = $this->ci->region_model->get_province_id($details['address_id']);
        $details['province'] = $user_address_info['province'];
        if (in_array($details['province'], array(1, 54351, 106340, 106092))) {
            $details['support_einvoice'] = 1;
        } else {
            $details['support_einvoice'] = 0;
        }

        return $details;
    }

    /*积分使用规则*/
    function check_order_jf_limit($uid, $order_id, $money, &$jfmoney) {
        $pay_money = $money;
        $jf_limit = floor($pay_money / 5);

        /*用户积分start*/
        $user_score = $this->ci->user_model->getUserScore($uid);
        $user_jf_money = number_format(floor($user_score['jf'] / 100), 0, '', '');
        if ($user_jf_money < 0) {
            $user_jf_money = 0;
        }
        /*用户积分end*/

        /*积分使用计算start*/
        if ($user_jf_money < $jf_limit) {
            $order_jf_limit = $user_jf_money;
        } else {
            $order_jf_limit = $jf_limit;
        }
        /*积分使用计算end*/

        /*积分重置start*/
        if ($jfmoney > $order_jf_limit && $jfmoney>0) {
            if ($order_jf_limit < 0) {
                $order_jf_limit = 0;
            }
            $data = array(
                'use_jf' => $order_jf_limit * 100,
                'jf_money' => $order_jf_limit
            );
            $where = array(
                'id' => $order_id,
                'order_status' => 0,
            );
            $this->ci->order_model->update_order($data, $where);
            $jfmoney = $order_jf_limit;
        }
        /*积分重置end*/
        return $order_jf_limit;
    }

    /*积分使用规则*/
    function check_b2o_order_jf_limit($uid, $order_id, $money, &$jfmoney) {
        $pay_money = $money;
        $jf_limit = floor($pay_money / 10);

        /*用户积分start*/
        $user_score = $this->ci->user_model->getUserScore($uid);
        $user_jf_money = number_format(floor($user_score['jf'] / 100), 0, '', '');
        if ($user_jf_money < 0) {
            $user_jf_money = 0;
        }
        /*用户积分end*/

        /*积分使用计算start*/
        if ($user_jf_money < $jf_limit) {
            $order_jf_limit = $user_jf_money;
        } else {
            $order_jf_limit = $jf_limit;
        }
        /*积分使用计算end*/

        /*积分重置start*/
        if ($jfmoney > $order_jf_limit && $jfmoney>0) {
            if ($order_jf_limit < 0) {
                $order_jf_limit = 0;
            }
            $data = array(
                'use_jf' => $order_jf_limit * 100,
                'jf_money' => $order_jf_limit
            );
            $where = array(
                'id' => $order_id,
                'order_status' => 0,
            );
            $this->ci->b2o_parent_order_model->update_order($data, $where);
            $jfmoney = $order_jf_limit;
        }
        /*积分重置end*/
        return $order_jf_limit;
    }
    /*
    *运费计算
    */
    function post_fee($cart_info, $goods_money, $order_info, $date) {
        $check_result = $this->ci->order_model->check_cart_pro_status($cart_info);
        if ($check_result['free_post'] == '1') {
            $method_info = array("method_money" => 0, "order_limit" => 0);
        } else {
            $this->ci->load->model("region_model");
            if (empty($order_info['address_id'])) {
                $method_info = array("method_money" => 0, "order_limit" => 0);
            } else {
                $area_info = $this->ci->region_model->get_province_id($order_info['address_id']);
                // if (strcmp($this->_version, '3.6.0') != 0) {
                //     $send_free = $this->ci->region_model->is_send_wd($area_info['province']);
                //     if($send_free === true){
                //         $method_info = $this->cacu_cost($cart_info,$area_info['city'],$goods_money);
                //     }else{
                //         $method_info = $this->freight($date,$cart_info,$area_info,$goods_money,$check_result);
                //     }
                // }else{
                    $method_info = $this->get_post_fee($cart_info,$area_info['city'],$goods_money);
                // }
            }
        }
        $method_info['cmoney'] = $check_result['cmoney'];
        return $method_info;
    }


    /*
    *运费计算规则new
    */
    function get_post_fee($cart_info,$city,$goods_money) {
        $area_freight_info = $this->ci->region_model->get_area_info($city);
        $weight = $this->cacu_weight($cart_info);

        if(empty($area_freight_info['send_role'])) {
            $send_role = unserialize('a:4:{i:0;a:5:{s:3:"low";i:0;s:5:"hight";s:5:"49.99";s:12:"first_weight";i:9999;s:18:"first_weight_money";i:20;s:19:"follow_weight_money";i:0;}i:1;a:5:{s:3:"low";i:50;s:5:"hight";s:5:"99.99";s:12:"first_weight";i:9999;s:18:"first_weight_money";i:10;s:19:"follow_weight_money";i:0;}i:2;a:5:{s:3:"low";i:100;s:5:"hight";s:6:"199.99";s:12:"first_weight";i:8;s:18:"first_weight_money";i:0;s:19:"follow_weight_money";i:2;}i:3;a:5:{s:3:"low";i:200;s:5:"hight";i:9999;s:12:"first_weight";i:9999;s:18:"first_weight_money";i:0;s:19:"follow_weight_money";i:0;}}');
        }else{
            $send_role = unserialize($area_freight_info['send_role']);
        }

        foreach ($send_role as $key => $value) {
            if($value['hight']==9999){//运费规则上限,每多x元，首重增加ykg
                // $first_weight_tmp = $value['first_weight'] + floor(($goods_money-$value['low'])/$value['increase'])*$value['add_first_weight'];
                // if($weight <= $first_weight_tmp) {
                //     $method_money = $value['first_weight_money'];
                // }else {
                //     $method_money = $value['first_weight_money'] + ceil(($weight - $first_weight_tmp))*$value['follow_weight_money'];
                // }
                // return array("method_money"=>$method_money,"order_limit"=>0);
                return array("method_money"=>0,"order_limit"=>0);
            }else if($goods_money >= $value['low'] && $goods_money <= $value['hight']){

                if($weight <= $value['first_weight']) {
                    $method_money = $value['first_weight_money'];
                }else {
                    $method_money = $value['first_weight_money'] + ceil(($weight - $value['first_weight']))*$value['follow_weight_money'];
                }
                return array("method_money"=>$method_money,"order_limit"=>0);
            }
        }
        return array("method_money"=>0,"order_limit"=>0);
    }

    /*
    *按照运费模版计算运费
    */
    function cacu_cost($cart_info, $city, $goods_money) {
        $area_freight_info = $this->ci->region_model->get_area_info($city);
        $weight = $this->cacu_weight($cart_info);

        if (empty($area_freight_info['first_weight']) || empty($area_freight_info['first_weight_money'])) {
            return array("method_money" => 0, "order_limit" => 0);
        } else {
            // $wd_region_free_post_money_limit = $this->ci->config->item("wd_region_free_post_money_limit");
            $free_post_money_limit = $area_freight_info['free_post_money_limit'];//isset($wd_region_free_post_money_limit[$city])?$wd_region_free_post_money_limit[$city]:300;
            if ($goods_money < $free_post_money_limit) {
                if ($weight <= $area_freight_info['first_weight']) {
                    $method_money = $area_freight_info['first_weight_money'];
                } else {
                    $method_money = $area_freight_info['first_weight_money'] + ceil(($weight - $area_freight_info['first_weight'])) * $area_freight_info['follow_weight_money'];
                }
                return array("method_money" => $method_money, "order_limit" => 0);
            } else {
                return array("method_money" => 0, "order_limit" => 0);
            }
        }
    }

    /*
    *按照配送时间收取运费
    */
    function freight($d, $cart_info, $area, $f, $check_result) {
        $has_ignore_order_money_limit_pro = false;
        if ($check_result['ignore_order_money']) {
            $has_ignore_order_money_limit_pro = true;
        }
        if (strpos($d, "-") > 0) {
            $arr = explode("-", $d);
            $d = $arr[0];
            $t = $arr[1];
            $tFrom = substr($t, 0, 2);
            $tTo = substr($t, -2);
        } else {
            $d = $d;
        }
        $area_info = $this->ci->region_model->get_area_info($area['area']);
        $cut_off_time = $area_info['cut_off_time'];

        if ($f >= $area_info['free_post_money_limit']) {
            return array("method_money" => 0, "order_limit" => 0);
        } else {
            $weight = $this->cacu_weight($cart_info);
            if ($weight <= $area_info['first_weight']) {
                return array("method_money" => $area_info['first_weight_money'], "order_limit" => 0);
            } else {
                $method_money = $area_info['first_weight_money'] + ceil(($weight - $area_info['first_weight'])) * $area_info['follow_weight_money'];
                return array("method_money" => $method_money, "order_limit" => 0);
            }
        }

        // $a=$this->getArea($area_info['identify_code']);
        // $a=$a[0];
        // if(strlen($a)!=2){
        //     $a=$a[0];
        // }else{
        //     $a=substr($a,-1);
        // }

        // $no_order_limit_send_arr = $this->ci->config->item("no_order_limit_send_arr");

        // if(in_array($area['province'], $no_order_limit_send_arr)){
        //   $no_order_limit_send = true;
        // }else{
        //     $no_order_limit_send = false;
        // }
        // if($a==1){
        //     if($no_order_limit_send){
        //       if($d>date("Ymd") && $f>=60 && $f<100){
        //        return array("method_money"=>5,"order_limit"=>0);
        //       }else if($d>date("Ymd") && $f>=100){
        //          return array("method_money"=>0,"order_limit"=>0);
        //       }else if($f<60){
        //          return array("method_money"=>5,"order_limit"=>0);
        //       }
        //     }


        //     // if(date("H")>=$cut_off_time && $d==date('Ymd',strtotime("+1 day"))  && $f>=60 && $f<200){
        //     //   return array("method_money"=>20,"order_limit"=>0);
        //     // }elseif(date("H")>=$cut_off_time && $d==date('Ymd',strtotime("+1 day")) && $f>=200){
        //     //   return array("method_money"=>20,"order_limit"=>0);
        //     // }elseif($d==date("Ymd") && $f>=60 && $f<200 ){
        //     //    return array("method_money"=>20,"order_limit"=>0);
        //     // }elseif($d==date("Ymd") && $f>=200 ){
        //     //    return array("method_money"=>20,"order_limit"=>0);
        //     // }elseif($d>date("Ymd") && $f>=60 && $f<100){
        //     //    return array("method_money"=>5,"order_limit"=>0);
        //     // }else if($d>date("Ymd") && $f<60){
        //     //     if($has_ignore_order_money_limit_pro){
        //     //       return array("method_money"=>5,"order_limit"=>0);
        //     //    }else{
        //     //       return array("method_money"=>0,"order_limit"=>60);
        //     //    }
        //     // }else if($d>date("Ymd") && $f>=100){
        //     //    return array("method_money"=>0,"order_limit"=>0);
        //     // }else if($f<60){
        //     //    if($has_ignore_order_money_limit_pro){
        //     //       return array("method_money"=>5,"order_limit"=>0);
        //     //    }else{
        //     //       return array("method_money"=>0,"order_limit"=>60);
        //     //    }
        //     // }else{
        //     //    return array("method_money"=>0,"order_limit"=>0);
        //     // }
        //     if($f>=60 && $f<100){
        //       return array("method_money"=>5,"order_limit"=>0);
        //     }else if($f>=100){
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }else if($f<60){
        //        if($has_ignore_order_money_limit_pro){
        //           return array("method_money"=>5,"order_limit"=>0);
        //        }else{
        //           return array("method_money"=>0,"order_limit"=>60);
        //        }
        //     }else{
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }
        // }else if($a==2){
        //     if($f>=60 && $f<100){
        //       return array("method_money"=>5,"order_limit"=>0);
        //     }else if($f>=100){
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }else if($f<60){
        //        if($has_ignore_order_money_limit_pro){
        //           return array("method_money"=>5,"order_limit"=>0);
        //        }else{
        //           return array("method_money"=>0,"order_limit"=>60);
        //        }
        //     }else{
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }
        // }else if($a==3){
        //     if($f>300 && $f<500){
        //        return array("method_money"=>20,"order_limit"=>0);
        //     }else if($f>=500){
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }else if($f<300){
        //         if($has_ignore_order_money_limit_pro){
        //           return array("method_money"=>20,"order_limit"=>0);
        //        }else{
        //           return array("method_money"=>0,"order_limit"=>300);
        //        }
        //     }else{
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }
        // }elseif($a==5){
        //     if($this->no_order_limit_send){
        //       if($d>date("Ymd") && $f>=60 && $f<100){
        //         return array("method_money"=>5,"order_limit"=>0);
        //       }else if($d>date("Ymd") && $f>=100){
        //          return array("method_money"=>0,"order_limit"=>0);
        //       }else if($f<60){
        //          return array("method_money"=>5,"order_limit"=>0);
        //       }
        //     }

        //     if(date("H")>=$cut_off_time && $d==date('Ymd',strtotime("+1 day"))  && $f>=60 && $f<100){
        //       return array("method_money"=>5,"order_limit"=>0);
        //     }elseif(date("H")>=$cut_off_time && $d==date('Ymd',strtotime("+1 day")) && $f>=100){
        //       return array("method_money"=>0,"order_limit"=>0);
        //     }elseif($d==date("Ymd") && $f>=60 && $f<100 ){
        //        return array("method_money"=>5,"order_limit"=>0);
        //     }elseif($d==date("Ymd") && $f>=100 ){
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }elseif($d>date("Ymd") && $f>=60 && $f<100){
        //        return array("method_money"=>5,"order_limit"=>0);
        //     }else if($d>date("Ymd") && $f<60){
        //       if($has_ignore_order_money_limit_pro){
        //          return array("method_money"=>5,"order_limit"=>0);
        //        }else{
        //          return array("method_money"=>0,"order_limit"=>60);
        //        }
        //     }else if($d>date("Ymd") && $f>=100){
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }else if($f<60){
        //       if($has_ignore_order_money_limit_pro){
        //          return array("method_money"=>5,"order_limit"=>0);
        //        }else{
        //          return array("method_money"=>0,"order_limit"=>60);
        //        }
        //     }
        // }else if($a==8){
        //     if($f>=200){
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }else if($f<200){
        //         if($has_ignore_order_money_limit_pro){
        //           return array("method_money"=>10,"order_limit"=>0);
        //        }else{
        //           return array("method_money"=>0,"order_limit"=>200);
        //        }

        //     }else{
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }
        // }else if($a==9){
        //     if($f>=300 && $f<500){
        //        return array("method_money"=>20,"order_limit"=>0);
        //     }else if($f>=500){
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }else if($f<300){
        //         if($has_ignore_order_money_limit_pro){
        //           return array("method_money"=>20,"order_limit"=>0);
        //        }else{
        //           return array("method_money"=>0,"order_limit"=>300);
        //        }
        //     }else{
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }
        // }else if($a==4){
        //     if($f>=300){
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }else if($f<300){
        //        return array("method_money"=>12,"order_limit"=>0);
        //     }else{
        //        return array("method_money"=>0,"order_limit"=>0);
        //     }
        // }
    }

    /*
    *获取地区标示
    */
    function getArea($identify_code) {
        $m = "";
        preg_match("/\d+/", $identify_code, $m);
        return $m;
    }

    /*
    *购物车商品总重量
    */
    function cacu_weight($cart_info) {
        if(isset($cart_info['pro_weight'])){
            return $cart_info['pro_weight'];
        }else{
            $weight = 0;
            if (!empty($cart_info['products'])) {
                foreach ($cart_info['products'] as $val) {
                    $weight += $val['weight'] * $val['qty'];
                }
            }
            return $weight;
        }
    }

    /*
    *积分使用
    */
    function usejf($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'jf' => array('required' => array('code' => '300', 'msg' => '请输入需要使用的积分')),
            'area_adcode' => array('required' => array('code' => '500', 'msg' => 'area_adcode can not be null')),
            'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
            'delivery_code' => array('required' => array('code' => '500', 'msg' => 'delivery_code can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        //事务 start
        $this->ci->db->trans_begin();

        $jf_money = $params['jf'];
        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);

        //正整数验证
        if (!filter_var($jf_money, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)))) {
            return array('code' => '300', 'msg' => '抵扣金额必须为正整数');
        }

        //不能使用积分验证
        $cart_info = $this->cart_info;
        $check_result = $this->ci->b2o_parent_order_model->check_cart_pro_status($cart_info);
        if ($check_result['jf_limit'] == '1') {
            return array('code' => '300', 'msg' => '您购买的' . $check_result['jf_limit_pro'] . '是特价商品，不能使用积分');
        }

        //使用金额验证
        $orderDetail = $this->b2oOrderDetails($uid, $params['source'], $params['device_id'],$params);

        $goods_money = $orderDetail['goods_money'];
        $card_money = $orderDetail['card_money'];
        $method_money = $orderDetail['method_money'];
        $pay_discount = $orderDetail['pay_discount'];

        if ($jf_money > ($goods_money - $card_money - $pay_discount)) {
            return array('code' => '300', 'msg' => '积分抵扣金额超过商品金额，无法使用积分');
        }

        if ($goods_money + $method_money - $pay_discount - $card_money - $jf_money - $orderDetail['fresh_discount'] - $orderDetail['jd_discount'] - $orderDetail['post_discount'] < 0) {
            return array('code' => '300', 'msg' => '抵扣之后的订单金额必须大于0元');
        }

        $user_jf = $this->ci->user_model->getUserScore($uid);
        $use_jf = $jf_money * 100;

        if ($user_jf['jf'] < $use_jf) {
            return array('code' => '300', 'msg' => '您的积分不足');
        }

        if ($jf_money > $orderDetail['order_jf_limit']) {
            return array('code' => '300', 'msg' => '您的积分最多只能使用' . $orderDetail['order_jf_limit'] . '元');
        }

        $data = array(
            'use_jf' => $use_jf,
            'jf_money' => $jf_money
        );
        $where = array(
            'id' => $order_id,
            'order_status' => 0
        );
        $this->ci->b2o_parent_order_model->update_order($data, $where);
        $this->use_jf_obj = true;
        $this->use_jf = $use_jf;
        $this->jf_money = $jf_money;

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "使用积分失败,请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => "succ", 'uid' => $uid);
    }

    /*
    *取消积分使用
    */
    function cancelUsejf($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'area_adcode' => array('required' => array('code' => '500', 'msg' => 'area_adcode can not be null')),
            'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
            'delivery_code' => array('required' => array('code' => '500', 'msg' => 'delivery_code can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        //事务 start
        $this->ci->db->trans_begin();

        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        $data = array(
            'use_jf' => 0,
            'jf_money' => 0
        );
        $where = array(
            'id' => $order_id,
            'order_status' => 0
        );
        $this->ci->b2o_parent_order_model->update_order($data, $where);
        $this->use_jf_obj = true;
        $this->use_jf = 0;
        $this->jf_money = 0;

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "取消使用积分失败,请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array("code" => "200", "msg" => "succ", "uid" => $uid);
    }

    /*
    *使用优惠券
    */
    function useCard($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'card' => array('required' => array('code' => '300', 'msg' => '请输入或选择您要使用的优惠券')),
            'area_adcode' => array('required' => array('code' => '500', 'msg' => 'area_adcode can not be null')),
            'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
            'delivery_code' => array('required' => array('code' => '500', 'msg' => 'delivery_code can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        //事务 start
        $this->ci->db->trans_begin();

        //验证优惠券状态start
        $card_number = $params['card'];
        $this->ci->load->model('card_model');
        $card_info = $this->ci->card_model->get_card_info($card_number);
        $cart_info = $this->cart_info;

        $details = $this->b2oOrderDetails($uid, $params['source'], $params['device_id'],$params);

        $goods_money = $details['goods_money'];
        $jf_money = $details['jf_money'];
        $method_money = $details['method_money'];
        $pay_discount = $details['pay_discount'];
        $can_use = $this->ci->card_model->card_can_use($card_info, $uid, $goods_money, $params['source'], $jf_money, $pay_discount,0,$cart_info['products']);
        if ($can_use[0] == 0) {
            return array('code' => '300', 'msg' => $can_use[1]);
        }

        //不能使用积分验证
        $check_result = $this->ci->b2o_parent_order_model->check_cart_pro_status($cart_info);
        // if ($check_result['card_limit'] == '1') {
        //     $card_pros = explode(',',$card_info['product_id']);
        //     if(count($card_pros) > 0)
        //     {
        //         $is_card_limit = 0;
        //         foreach($cart_info['products'] as $k=>$v)
        //         {
        //             if(in_array($v['product_id'],$card_pros))
        //             {
        //                 $is_card_limit = 1;
        //             }
        //         }
        //         if($is_card_limit == 0)
        //         {
        //             return array('code' => '300', 'msg' => '您购买的商品不能使用优惠券');
        //         }
        //     }
        //     else
        //     {
        //         return array('code' => '300', 'msg' => '您购买的商品部分是特价商品，不能使用全场通用优惠券');
        //     }
        //     //return array('code' => '300', 'msg' => '您购买的商品全部都是特价商品，不能使用优惠券');
        // }

        //金额判断
        if ($goods_money + $method_money - $card_info['card_money'] - $jf_money - $details['fresh_discount'] - $details['jd_discount'] - $details['post_discount'] < 0) {
            return array('code' => '300', 'msg' => '抵扣之后的订单金额必须大于0元');
        }
        //验证优惠券状态end

        $product_list = explode(",", $card_info['product_id']);
        $productnum = 0;
        $salenum = 0;

        /*抵扣码改造，使用抵扣码后价格更改 by lusc*/
        $card_sales_money = 0;//减免金额
        if ($card_info['product_id']) {
            foreach ($cart_info['products'] as $val) {
                for ($i = 0; $i < count($product_list); $i++) {
                    if (trim($product_list[$i]) == $val['product_id']) {
                        if ($card_info['can_use_onemore_time'] == 'true') {//多次劵
                            if ($card_info['maketing'] == '5') {//商品减免
                                if ($card_info['can_sales_more_times'] == 'true') {//多买多减
                                    $card_sales_money += $card_info['card_money'] * $val['qty'];//商品种类*每种商品的数量*抵扣金额
                                } else {
                                    $card_sales_money = $card_info['card_money'];
                                }
                            } elseif ($card_info['maketing'] == '6') {//商品打折
                                if ($card_info['can_sales_more_times'] == 'true') {//多买多减
                                    $card_sales_money += round((1 - $card_info['card_discount']) * $val['qty'] * $val['price'], 2);
                                } else {
                                    $card_sales_money += round((1 - $card_info['card_discount']) * $val['price'], 2);
                                }
                            }
                        } else {//单次劵
                            if ($card_info['can_sales_more_times'] == 'true') {//TODO
                                $card_sales_money += $card_info['card_money'] * $val['qty'];//商品种类*每种商品的数量*抵扣金额
                            } else {
                                $card_sales_money = $card_info['card_money'];
                            }
                        }
                        $salenum = $salenum + 1;
                    }
                }
                $productnum = $productnum + 1;
            }
        } else {
            $productnum = count($cart_info['products']);
            if ($card_info['can_use_onemore_time'] == 'true') {//多次劵
                if ($card_info['maketing'] == '5') {//商品减免
                    $card_sales_money = $card_info['card_money'];
                }
            } else {//单次劵
                $card_sales_money = $card_info['card_money'];
            }
        }

        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        if ($salenum == $productnum && $card_info['restr_good'] == 0) {
            return array('code' => '300', 'msg' => '购物篮中都是活动商品不能抵扣，你可以添加非特价商品');
        } else if ($salenum == 0 && $card_info['restr_good'] == 1) {
            return array('code' => '300', 'msg' => '购物车中没有可以使用抵扣码的产品。');
        } else if ($order_id != "") {
            $data = array(
                'use_card' => $card_number,
                'card_money' => $card_sales_money,
            );
            $where = array(
                'id' => $order_id,
                'order_status' => 0,
            );
            $this->ci->b2o_parent_order_model->update_order($data, $where);


            $this->use_card_obj = true;
            $this->use_card = $card_number;
            $this->card_money = $card_sales_money;

            //事务 end
            if ($this->ci->db->trans_status() === FALSE) {
                $this->ci->db->trans_rollback();
                return array("code" => "300", "msg" => "使用优惠券失败,请重试");
            } else {
                $this->ci->db->trans_commit();
            }

            return array('code' => '200', 'msg' => $card_sales_money, 'uid' => $uid);
        } else if ($order_id == "") {
            return array('code' => '300', 'msg' => '请先登录您的帐号');
        }
    }

    /*
    *取消使用优惠券
    */
    function cancelUseCard($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'area_adcode' => array('required' => array('code' => '500', 'msg' => 'area_adcode can not be null')),
            'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
            'delivery_code' => array('required' => array('code' => '500', 'msg' => 'delivery_code can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        //事务 start
        $this->ci->db->trans_begin();

        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        $data = array(
            'use_card' => '',
            'card_money' => 0
        );
        $where = array(
            'id' => $order_id,
            'order_status' => 0,
        );
        $this->ci->b2o_parent_order_model->update_order($data, $where);
        $this->use_card_obj = true;
        $this->use_card = '';
        $this->card_money = 0;

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "取消使用优惠券失败,请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array("code" => "200", "msg" => "succ", "uid" => $uid);
    }

    /*
    *运费计算
    */
    function postFree($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end
        /*订单基本信息start*/
        $order_id = $this->ci->order_model->get_order_id($uid);
        $order_info = $this->ci->order_model->selectOrder("shtime,stime,address_id,", array("id" => $order_id));
        $shtime = $order_info['shtime'];
        $stime = $order_info['stime'];

        if ($stime != "") {
            $date = $shtime . "-" . $stime;
        } else {
            $date = $shtime;
        }
        /*订单基本信息end*/

        /*购物车信息start*/
        $cart_info = $this->cart_info;

        $goods_money = $cart_info['goods_amount'];
        $total_amount_money = $cart_info['total_amount'];
        /*购物车信息end*/

        /*运费计算start*/
        $method_info = $this->post_fee($cart_info, $total_amount_money, $order_info, $date);

        $order_detail = $this->ci->bll_order->orderDetails($uid, $params['source'], $params['device_id']);
        $order_info['money'] = number_format($order_detail['money'], 2, '.', '');
        $order_info['goods_money'] = number_format($order_detail['goods_money'], 2, '.', '');
        $order_info['method_money'] = number_format($order_detail['method_money'], 2, '.', '');
        $order_info['order_limit'] = number_format($method_info['order_limit'], 2, '.', '');
        $order_info['jf_money'] = number_format($order_detail['jf_money'], 2, '.', '');
        $order_info['card_money'] = number_format($order_detail['card_money'], 2, '.', '');
        $order_info['pay_discount'] = number_format($order_detail['pay_discount'], 2, '.', '');
        $order_info['order_jf_limit'] = number_format($order_detail['order_jf_limit'], 2, '.', '');
        /*余额改造 srart*/
        $order_info['user_money'] = number_format($order_detail['user_money'], 2, '.', '');
        $order_info['user_can_money'] = number_format($order_detail['user_can_money'], 2, '.', '');
        $order_info['use_money_deduction'] = number_format($order_detail['use_money_deduction'], 2, '.', '');
        /*余额改造 end*/

        /*运费计算end*/
        return $order_info;
    }

    /*
    *余额支付验证码
    */
    private function check_ver_code($session_id = '', $ver_code = '', $mobile) {
        if ($session_id == '') {
            return array('code' => '601', 'msg' => '验证码验证失败，请重新输入验证码');
        }
        if ($ver_code == '') {
            return array('code' => '602', 'msg' => '验证码不能为空');
        }
        $this->ci->session->sess_id = $session_id;
        $this->ci->session->sess_read();
        $ver_code_session = $this->ci->session->userdata;

        $userdata = unserialize($ver_code_session['user_data']);
        if (!isset($userdata['verification_code'])) {
            return array('code' => '601', 'msg' => '验证码已过期，请输入最新收到的验证码');
        }
        if ($userdata['verification_code'] != md5($mobile . $ver_code)) {
            return array('code' => '602', 'msg' => '验证码错误');
        } else {
            return array('code' => '200', 'msg' => '验证成功');
        }
    }


    /*
    *订单数据验证
    *  $uid             用户id
    *  $cart_info       购物车详情
    *  $params          接口入参
    *  $goods_money     商品金额
    *  $check_result    商品验证信息
    *  $user_info       用户信息
    *  $details         订单详情
    *  $use_case        用来标记验证操作点，create:订单创建;init:订单初始化;cart:购物车
    */
    function check_order_data($uid,$cart_info,$params,$goods_money,$check_result,$user_info,$details,$use_case='create'){


        /*
         ***********************************
         *以下是订单初始化、创建时需要验证信息start*
         ***********************************
         */

        if($use_case=='create' || $use_case=='init'){

            /*设备验证start*/
            if($params['source']=='app'){
                $this->ci->load->model('ms_log_v2_model');

                $product2Qty = array();

                foreach($cart_info['products'] as $productInfo){
                    $product2Qty[$productInfo['product_id']] = $productInfo['qty'];
                }

                foreach($cart_info['products'] as $product){
                    if (isset($product['dsc_id'])) {
                        $hasBoughtQty = $this->ci->ms_log_v2_model->dump(array('promotion_id'=>$product['dsc_id'], 'product_id'=>$product['product_id'], 'store_id'=>$product['store_id'],'is_del'=>0), 'sum(qty) as totleHasBought');

                        if ($product['order_qty_limit'] != 0 && ($hasBoughtQty['totleHasBought'] + $product['qty'] > $product['order_qty_limit'])){
                            return array("code" => "300", "msg" => '秒杀商品已售完');
                        }

                        $hasBoughtQtyByUser = $this->ci->ms_log_v2_model->dump(array('uid'=>$uid, 'promotion_id'=>$product['dsc_id'], 'product_id'=>$product['product_id'], 'store_id'=>$product['store_id'],'is_del'=>0), 'sum(qty) as totleHasBought');
                        if ($product['user_qty_limit'] != 0 && ($hasBoughtQtyByUser['totleHasBought'] + $product['qty'] > $product['user_qty_limit'])){
                            return array("code" => "300", "msg" => '秒杀商品超过单人限制购买次数');
                        }
                    }
                }

            }
            /*设备验证end*/

            /*特惠商品验证start*/
            if($check_result['group_limit']=='1'){
                return array('code'=>'300','msg'=>'您购买的商品全部都是活动商品，必须购买其他商品才能下单');
            }
            /*特惠商品验证end*/

            /*单独购买验证start*/
            if($check_result['expect']=='1'){
                return array('code'=>'300','msg'=>'您购买的商品中包含预售商品，预售商品必须单独下单');
            }
            /*单独购买验证end*/

            if($details['order_limit']){
                return array("code"=>"300","msg"=>"您购买的商品需要达到".$details["order_limit"]."元才能配送");
            }

            /*互斥判断start*/
            $is_fan = $this->ci->b2o_parent_order_model->fan($cart_info['products'],$uid);
            if(is_array($is_fan))
                return $is_fan;
            /*互斥判断end*/

            //$this->ci->load->library('fdaylog');
            //$db_log = $this->ci->load->database('db_log', TRUE);
            //$this->ci->fdaylog->add($db_log, 'ms', json_encode($cart_info));

        }

        /*
         **********************************
         *以上是订单初始化、创建时需要验证信息end*
         **********************************
         */




        /*
         *****************************
         *以下是订单创建时需要验证信息start*
         *****************************
         */
        if($use_case=='create'){

            /*赠品订单金额限制判断start*/
            //$xsh_check_result = $this->check_gift_money_limit($cart_info,$details['money']-$details['method_money']-$details['cmoney']);
            //特殊处理
            $xsh_check_result = $this->check_gift_money_limit($cart_info,$details['total_amount_money']);

            if($xsh_check_result!==false){
                return array("code"=>"300","msg"=>$xsh_check_result);
            }
            /*赠品订单金额限制判断end*/

            /*支付方式验证start*/
            if($params['ispc'] == 1)
            {
                $pay_array  =  $this->ci->config->item("pc_pay_array");
            }
            else{
                $pay_array  =  $this->ci->config->item("pay_array");
            }
            $pay_parent_id=$details['pay_parent_id'];
            $pay_id=$details['pay_id'];
            //支付方式合法性验证
            $init_pay = false;
            if(!isset($pay_array[$pay_parent_id])){
                $init_pay = true;
            }
            if(!empty($pay_array[$pay_parent_id]['son'])){
                $son=$pay_array[$pay_parent_id]['son'];
                if(!isset($son[$pay_id])){
                    $init_pay = true;
                }
            }else{
                if($pay_id!='0'){
                    $init_pay = true;
                }
            }

            if($init_pay){
                $this->ci->b2o_parent_order_model->init_pay($details['id']);
            }

            //仅限线上支付验证
            if($check_result['pay_limit']=='1' && $pay_parent_id=='4' && $params['source'] != 'app'){
                return array('code'=>'300','msg'=>'您购买的'.$check_result['pay_limit_pro'].'仅限线上支付，请重新选择支付方式');
            }
            //券卡商品余额支付限制
            if($check_result['iscard']=='1' && $pay_parent_id=='4' && $pay_id>6){
                return array('code'=>'300','msg'=>'您购买的'.$check_result['iscard_pro'].'是券卡商品，不能使用券卡支付');
            }
            //余额支付验证码验证
            if($pay_parent_id == 5) {
                //券卡商品余额支付限制
                if($check_result['iscard']=='1'){
                    return array('code'=>'300','msg'=>'您购买的'.$check_result['iscard_pro'].'是券卡商品，不能使用余额支付');
                }
            }
            /*支付方式验证end*/

            /*收货地址验证start*/
            if(empty($details['address_id'])){
                return array("code"=>"300","msg"=>"请选择收货地址");
            }
            /*收货地址验证end*/

            /*优惠券验证start*/
            if(!empty($details['use_card'])){
                // if($check_result['card_limit']=='1')
                // {
                //     $this->ci->load->model('card_model');
                //     $card_info = $this->ci->card_model->get_card_info($details['use_card']);
                //     $card_pros = explode(',',$card_info['product_id']);
                //     if(count($card_pros) > 0)
                //     {
                //         $is_card_limit = 0;
                //         foreach($cart_info['products'] as $k=>$v)
                //         {
                //             if(in_array($v['product_id'],$card_pros))
                //             {
                //                 $is_card_limit = 1;
                //             }
                //         }
                //         if($is_card_limit == 0)
                //         {
                //             return array('code' => '300', 'msg' => '您购买的商品不能使用优惠券');
                //         }
                //     }
                //     else
                //     {
                //         return array('code' => '300', 'msg' => '您购买的商品部分是特价商品，不能使用全场通用优惠券');
                //     }
                //     //return array('code'=>'300','msg'=>'您购买的商品全部都是活动商品，不能使用优惠券，请取消使用');
                // }
                $this->ci->load->model('card_model');
                $this->card_info = $this->ci->card_model->get_card_info($details['use_card']);
                $can_use = $this->ci->card_model->card_can_use($this->card_info,$uid,$details['goods_money'],$params['source'],$details['jf_money'],$details['pay_discount'],0,$cart_info['products']);
                if ($can_use[0] == 0){
                    return array('code'=>'300','msg'=>$can_use[1]);
                }
            }
            /*优惠券验证end*/

            /*积分验证start*/
            if(!empty($details['use_jf'])){
                if($check_result['jf_limit']=='1'){
                    return array('code'=>'300','msg'=>'您购买的'.$check_result['jf_limit_pro'].'是活动商品，不能使用积分，请取消使用');
                }
            }
            /*积分验证end*/

            /*发票验证start*/
            if($details['pay_parent_id']=='5' && (!empty($details['fp']) || !empty($details['fp_dz']))){
                $this->init_b2oinvoice($details['id']);
            }
            if($details['pay_parent_id']=='2' && (!empty($details['fp']) || !empty($details['fp_dz']))){
                $this->init_b2oinvoice($details['id']);
            }
            if($details['pay_parent_id']=='4' && ($details['pay_id']==3 || $details['pay_id']==7 || $details['pay_id']==8 || $details['pay_id']==9 || $details['pay_id']==10 || $details['pay_id']==11) && (!empty($details['fp']) || !empty($details['fp_dz']))){
                $this->init_b2oinvoice($details['id']);
            }
            /*发票验证end*/

            //用户余额判断
            if($details['pay_parent_id']=='5'){
                if(bccomp($user_info['money'],$details['money']) < 0){
                    return array('code'=>'600','msg'=>'帐户余额不足，当前余额为¥'.$user_info['money'].'，请充值');
                }
            }

            //用户积分判断
            if($details['use_jf']>0){
                $user_jf = $this->ci->user_model->getUserScore($uid);
                if($user_jf['jf']<$details['use_jf']){
                    return array('code'=>'300','msg'=>'帐户积分不足，请返回取消使用');
                }
            }

            if($details['money'] < 0){
                return array("code"=>"300","msg"=>"使用积分和抵扣码后订单金额必须大于0");
            }

            //邮费特权
            $post_info = $this->ci->order_postage_model->getUserPostagePrivilegeInfo($details['uid'], date("Y-m-d H:i:s", $params['timestamp']),1);
            $post_config = $this->ci->config->item('postage_discount');
            if($post_info  && $details['order_method_money'] >= $post_config['money'])
            {
                $check_money = $goods_money+$details['method_money']-$details['jf_money']-$details['card_money']-$details['post_discount'];
            }
            else
            {
                $check_money = $goods_money+$details['method_money']-$details['jf_money']-$details['card_money'];
            }

            //闪鲜卡
            if($details['fresh_discount'] >0)
            {
                $check_money = $check_money - $details['fresh_discount'];
            }

            //积点
            if($details['jd_discount'] >0)
            {
                $check_money = $check_money - $details['jd_discount'];
            }

            if(bccomp($check_money, $details['money'],3)!=0){
                return array("code"=>"300","msg"=>"订单提交失败，请稍后再重试");
            }
            if($details['money'] < 0){
                return array("code"=>"300","msg"=>"抵扣之后的订单金额必须大于0元");
            }
            // 暂时关闭by lusc
            // if($goods_money == 0 && $details['method_money']>0){
            //     return array("code"=>"300","msg"=>"订单金额不能为0,请重新操作");
            // }

            //不能自提商品判断
            if(isset($params['self_pick']) && !empty($params['self_pick'])){
                $no_self_products = $this->config->item('no_self_products');
                foreach($cart_info['products'] as $pv){
                    if(in_array($pv['product_id'], $no_self_products)){
                        return array("code"=>"300","msg"=>"您购买的".$pv['name']."不支持自提");
                    }
                }
            }
        }

        /*
     ***************************
     *以上是订单创建时需要验证信息end*
     ***************************
     */
    }

    function init_invoice($order_id) {
        $data = array(
            'fp' => '',
            'fp_dz' => '',
            'invoice_money' => 0,
        );
        $where = array(
            'id' => $order_id
        );
        $this->ci->order_model->update_order($data, $where);
        $this->ci->order_model->delete_order_invoice($order_id);
    }

    function init_b2oinvoice($order_id) {
        $data = array(
            'fp' => '',
            'fp_dz' => '',
            'invoice_money' => 0,
        );
        $where = array(
            'id' => $order_id
        );
        $this->ci->b2o_parent_order_model->update_order($data, $where);
        $this->ci->b2o_parent_order_model->delete_order_invoice($order_id);
    }

    /*
    *单品促销验证
    */
    private function checkUserProSale($uid, $pro_sale_result, $order_id, $device_code) {
        if (!empty($pro_sale_result) && isset($pro_sale_result['active_rules'])) {
            $device_code = $device_code ? $device_code : '';
            foreach ($pro_sale_result['active_rules'] as $key => $value) {
                $insert_data = array(
                    'uid' => $uid,
                    'device_code' => $device_code,
                    'active_tag' => $value['active_tag'],
                    'order_id' => $order_id
                );
                $this->ci->db->insert('active_limit', $insert_data);
            }
        }
        return false;
    }

    /*订单生成*/
    function createOrder($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'area_adcode' => array('required' => array('code' => '500', 'msg' => 'area_adcode can not be null')),
            'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
            'delivery_code' => array('required' => array('code' => '500', 'msg' => 'delivery_code can not be null')),
            'package_send_times' => array('required' => array('code' => '500', 'msg' => 'package_send_times can not be null')),
            'tms_region_type' => array('required' => array('code' => '500', 'msg' => 'tms_region_type can not be null')),
            //'tms_region_time' => array('required' => array('code' => '500', 'msg' => 'tms_region_time can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        $user_info = $this->ci->user_model->selectUser('id,msgsetting,mobile,email,enter_id,money', array('id' => $uid));
        $time = date("Y-m-d H:i:s");
        $this->ci->load->model("region_model");

        //订单数据验证start
        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);

        if (!$order_id) {
            return array("code" => "500", "msg" => "传参错误");
        }
        $cart_info = $this->cart_info;
        if (!$cart_info) {
            return array("code" => "300", "msg" => "您的购物车是空的，请返回添加商品");
        }

        //检查购物车 - b2o
        $cart_err = $this->check_cart_data($cart_info,$params);
        if($cart_err['code'] != 200)
        {
            return array("code" => $cart_err['code'], "msg" => $cart_err['msg']);
        }

        $goods_money = (float)$cart_info['total']['discounted_price'];

        if ($params['source'] == 'app' && isset($params['device_id'])) {
            $params['device_code'] = $params['device_id'];
        }

        $check_result = $this->ci->b2o_parent_order_model->check_cart_pro_status($cart_info);//重新组织商品属性判断
        $details = $this->b2oOrderDetails($uid, $params['source'], $params['device_code'],$params);

        //闪鲜卡 － 全额短信
        if($details['fresh_discount'] >0 && $details['money'] == 0)
        {
            $order_address_info = $this->ci->b2o_parent_order_model->get_order_address($details['address_id']);
            $if_send_code = $this->ci->b2o_parent_order_model->checkSendCode(array(),$uid,'5', $order_address_info);
            if ($if_send_code)
            {
                if(!empty($params['verification_code']) && !empty($params['ver_code_connect_id']))
                {
                    $send = array();
                    $send['connect_id'] = $params['connect_id'];
                    $send['verification_code'] = $params['verification_code'];
                    $send['ver_code_connect_id'] = $params['ver_code_connect_id'];
                    $send_res  = $this->checkBalanceCode($send);
                    if($send_res['code'] != 200)
                    {
                        return array('code'=>$send_res['code'],'msg'=>$send_res['msg']);
                    }
                }
                else
                {
                    return array('code'=>320,'msg'=>'支付需要短信验证');
                }
            }
        }

        //检查用户地址
        $user_addr = $this->ci->b2o_parent_order_model->get_user_addr($uid,$details['address_id']);
        if(empty($user_addr))
        {
            return array("code" => "300", "msg" => "您选择的下单地址错误，请重新选择");
        }

        $check_order_result = $this->check_order_data($uid, $cart_info, $params, $goods_money, $check_result, $user_info, $details, 'create');
        if ($check_order_result) {
            return array("code" => $check_order_result['code'], "msg" => $check_order_result['msg']);
        }
        //订单数据验证end

        //订单区分耘易、天天果园
        $sales_channel = $check_result['type'];
        switch ($params['source']) {
            case 'wap':
                $order_channel = 2;
                break;
            case 'app':
                $order_channel = 6;
                break;
            default:
                $order_channel = 1;
                break;
        }

        //新版本m站
        if($params['channel'] == 'wap')
        {
            $order_channel = 2;
        }elseif ($params['channel'] == 'wechatapp') {
            $order_channel = 11;
        }

        /*订单提交信息start*/
        $msg = isset($params['msg']) ? addslashes(strip_tags($params["msg"])) : '';
        $hk = isset($params['hk']) ? addslashes(strip_tags($params["hk"])) : '';
        if ($hk && mb_strlen($hk) > 50) {
            return array("code" => "300", "msg" => "您填写的贺卡内容过长，请控制在50个字以内。");
        }
        /*订单提交信息end*/

        //订单详情
        $pay_status = 0;

        /**********************************事务开始************************************/
        $this->ci->db->trans_begin();

        //插入地址
        $recInfo = $this->orderAddAddr($uid, $details['address_id'], $order_id, $this->is_enterprise);
        if (isset($recInfo['code'])) {
            return $recInfo;
        }

        //处理抵扣码
        if ($details['use_card'] != "") {
            $content = "订单" . $details["order_name"] . "抵扣" . $details['card_money'];
            $card_number = $details['use_card'];
            if ($this->card_info['can_use_onemore_time'] == 'false') {
                $card_data = array(
                    'is_used' => '1',
                    'content' => $content
                );
                if (!$this->ci->card_model->update_card($card_data, array('card_number' => $card_number))) {
                    return array("code" => "300", "msg" => "优惠券使用错误，请取消使用重新提交");
                }
            }
        }

        //扣积分
        if ($details['jf_money'] > 0) {
            $use_jf = $details['jf_money'] * 100;
            if (!$this->ci->user_model->cut_uses_jf($uid, $use_jf, $details['order_name'])) {
                return array("code" => "300", "msg" => "积分扣除失败，请重新提交订单");
            }
        }

        //订单积分

        //fix 积分比例
        $order_score = 0;
        foreach($cart_info['products'] as $cart_key => $item)
        {
            if($item['type'] == 'normal')
            {
                $pro_score = $this->ci->b2o_parent_order_model->get_order_product_score($uid, $item);
                $order_score += $pro_score;
            }
        }
        //$order_score = $this->ci->user_model->dill_score_new($details['money'] - $details['method_money'], $uid);
        if ($order_score < 0) {
            $order_score = 0;
        }
        if ($details['pay_parent_id'] == 5) {
            $order_score = 0;
        }
        if ($details['pay_parent_id'] == 4 && ($details['pay_id'] == 7 || $details['pay_id'] == 8 || $details['pay_id'] == 9)) {
            $order_score = 0;
        }

        //订单地区
        $order_region = $this->get_order_region($details['address_id']);

        /*设备验证记录start*/
        if ($params['source'] == 'app') {
            $device_code = isset($params['device_code']) ? $params['device_code'] : '';
            if (isset($this->device_product_id) && !empty($this->device_product_id)) {
                $this->ci->user_model->add_device_limit($this->device_product_id, $device_code, $order_id);
            }
        }
        /*设备验证记录end*/

        //订单类型判断
        $order_type = 1;
        $detail_goods_money = $details['goods_money'];
        //订单数据更新

        //仓储
        $this->ci->load->model("warehouse_model");
        $ware = $this->ci->warehouse_model->dump(array('id'=>$params['delivery_code']));
        if(empty($ware))
        {
            return array("code" => "300", "msg" => "");
        }
        else
        {
            $cang_id = $ware['id'];
            $deliver_type = $ware['send_type'];
        }

        //包裹
        $open_flash_send = $params['open_package'] == 1 ? true : false;
        $package_send_times = json_decode($params['package_send_times'],true);
        $gps_res =  $this->getTmsStore($user_addr['lonlat'],$params['area_adcode']);
        $package =  $this->package($cart_info,$params['area_adcode'],$params['delivery_code'],$open_flash_send,$params['tms_region_type'],$params['is_day_night'],$gps_res['data']['delivery_end_time']);
        if(isset($package['code']))
        {
            return array('code'=>$package['code'],'msg'=>$package['msg']);
        }
        $is_reset_sendtime = 0;
        $reset_package = array();
        foreach($package as $key=>$val)
        {
            $shtime = $package_send_times[$key]['shtime'];
            $stime = $package_send_times[$key]['stime'];
            $is_flash = $package_send_times[$key]['is_flash'];

            $is_zt =0;
            if(!empty($params['self_pick']))
            {
                $par_self_pick = explode(',',$params['self_pick']);
                if(in_array($val['tag'],$par_self_pick))
                {
                    $is_zt =1;
                }
            }
            $checkPackage = $this->checkPackageSendTime($package[$key],$shtime,$stime,$is_flash,$is_zt);
            if(empty($checkPackage))
            {
                $is_reset_sendtime = 1;
                $p_num = $key+1;
                array_push($reset_package,$p_num);
            }
            else
            {
                $package[$key]['chose_sendtime'] = array(
                    'tag'=>$package[$key]['tag'],
                    'shtime'=>$checkPackage['shtime'],
                    'stime'=>$checkPackage['stime'],
                );
            }
        }
        //重载orderInit
        if($is_reset_sendtime == 1)
        {
            $str_reset_package = implode(',',$reset_package);
            return array("code" => "303", "msg" => "您选择配送时间包裹".$str_reset_package."已过期,请重新选择配送时间");
        }

        //自提
        $pickup_store_id = '';
        $pickup_code = '';
        if(isset($params['self_pick']) && !empty($params['self_pick']))
        {
            //code
            $randStr = str_shuffle('1234567890');
            $pickup_code = substr($randStr,0,6);

            $par_self_pick = explode(',',$params['self_pick']);
            $store_ids = explode(',',$params['store_id_list']);
            $sid = $store_ids[1];

            foreach($package as $key=>$val)
            {
                $self_pick = array(
                    'is_can'=>0,
                    'is_select'=>0,
                    'store_id'=>'',
                    'store_name'=>'',
                    'store_address'=>'',
                );

                if(in_array($val['tag'],$par_self_pick) && !empty($sid))
                {
                    $self_pick['is_can'] = 1;
                    $self_pick['is_select'] = 1;
                    $self_pick['store_id'] = $sid;

                    $this->ci->load->model('b2o_store_model');
                    $store_data = $this->ci->b2o_store_model->dump(array('id' =>$sid));
                    $self_pick['store_name'] = $store_data['name'];
                    $self_pick['store_address'] = '上海市'.$store_data['address'];

                    $pickup_store_id = $sid;
                }
                $package[$key]['store'] = $self_pick;
            }
        }

        $this->ci->b2o_parent_order_model->add_order_package($details['order_name'],$package);

        //商品插入数据库
        $order_pro_check = $this->orderAddPro($uid, $order_id, $cart_info['products'],0,$this->card_info);
        if (isset($order_pro_check['code'])) {
            return $order_pro_check;
        }

        //隐藏价格
        $sheet_show_price = isset($params['sheet_show_price']) ? $params['sheet_show_price'] : 1;

        //缺货商品
        $no_stock = isset($params['no_stock']) ? $params['no_stock'] : 1;

        $store_list = explode(',',$params['store_id_list']);
        if(count($store_list) > 1)
        {
            $str_id = $store_list[1];
        }
        else
        {
            $str_id = $store_list[0];
        }

        $order_data = array(
            'pay_status' => $pay_status,
            'money' => $details['money'],
            'pmoney' => $detail_goods_money,
            'score' => $order_score,
            'msg' => $msg,
            'hk' => $hk,
            'goods_money' => $detail_goods_money,
            'method_money' => $details['order_method_money'],
            // 'fp'=>$fp,
            // 'fp_dz'=>$fp_dz,
            'order_status' => '1',
            'time' => $time,
            'order_region' => $order_region,
            'channel' => $order_channel,
            'sync_erp' => 0,
            'is_enterprise' =>0,
            'sales_channel' => $sales_channel,
            'pay_discount' => $details['promotion_discount'],
            'new_pay_discount' => $details['new_pay_discount'],
            'version' => $details['version'] + 1,
            'order_type' => $order_type,
            'cang_id' => $cang_id,
            'deliver_type' => $deliver_type,
            'sheet_show_price' => $sheet_show_price,
            'no_stock'=>$no_stock,
            'pickup_store_id'=>$pickup_store_id,
            'pickup_code'=>$pickup_code,
            'store_id'=>$str_id,
        );

        //更新channel
        $session = $this->ci->session->userdata;
        $userdata = unserialize($session['user_data']);
        if (isset($userdata['fl_channel_id']) && $userdata['fl_channel_id'] == "xiaomi") {
            $order_data['channel'] = 9;
        }

        //特殊处理-处理果实卡
        if($params['source'] == 'app' && $details['pay_parent_id'] == 4)
        {
            $order_data['pay_parent_id'] = 7;
            $order_data['pay_id'] = 0;
            $order_data['pay_name'] = '微信支付';
        }
        $order_where = array(
            'id' => $order_id,
            'version' => $details['version'],
            'order_status' => 0
        );
        if (!$this->ci->b2o_parent_order_model->update_order($order_data, $order_where)) {
            return array("code" => "300", "msg" => "订单提交失败，请稍后重试");
        }

        $this->ci->b2o_parent_order_model->add_order_cart($details['order_name'],$cart_info);

        //0元订单
        $is_all_gift = 1;
        foreach($cart_info['products'] as $k=>$v)
        {
            if($v['type'] != 'user_gift' || $v['type'] != 'gift')
            {
                //$is_all_gift = 0;
            }
        }

        if($details['money'] == 0  && $is_all_gift == 1)
        {
            if($details['fresh_discount'] >0)
            {
                $data_split = array(
                    'pay_status' => 2,
                    'pay_time'=>date('Y-m-d H:i:s'),
                    'update_pay_time'=>date('Y-m-d H:i:s'),
                    'pay_id'=>'0',
                    'pay_parent_id'=>'16',
                    'pay_name'=>'闪鲜卡支付',
                    'money'=>$details['fresh_discount'],
                    'fresh_discount'=>'0',
                );
                $data_split_where = array(
                    'id' => $order_id
                );
                if (!$this->ci->b2o_parent_order_model->update_order($data_split, $data_split_where)) {
                    return array("code" => "300", "msg" => "订单提交失败，请稍后重试");
                }
            }
            else if($details['jd_discount'] >0)
            {
                $data_split = array(
                    'pay_status' => 2,
                    'pay_time'=>date('Y-m-d H:i:s'),
                    'update_pay_time'=>date('Y-m-d H:i:s'),
                    'pay_id'=>'0',
                    'pay_parent_id'=>'17',
                    'pay_name'=>'积点支付',
                    'money'=>$details['jd_discount'],
                    'jd_discount'=>'0',
                );
                $data_split_where = array(
                    'id' => $order_id
                );
                if (!$this->ci->b2o_parent_order_model->update_order($data_split, $data_split_where)) {
                    return array("code" => "300", "msg" => "订单提交失败，请稍后重试");
                }
            }
            else
            {
                $data_split = array(
                    'pay_status' => 1,
                    'pay_time'=>date('Y-m-d H:i:s'),
                    'update_pay_time'=>date('Y-m-d H:i:s'),
                );
                $data_split_where = array(
                    'id' => $order_id
                );
                if (!$this->ci->b2o_parent_order_model->update_order($data_split, $data_split_where)) {
                    return array("code" => "300", "msg" => "订单提交失败，请稍后重试");
                }
            }
        }

        //邮费特权
        $post_info = $this->ci->order_postage_model->getUserPostagePrivilegeInfo($details['uid'], date("Y-m-d H:i:s", $params['timestamp']),1);
        $post_config = $this->ci->config->item('postage_discount');
        if($post_info  && $details['order_method_money'] >= $post_config['money'])
        {
            $data_post = array(
                'post_discount' => $post_config['money'],
            );
            $data_post_where = array(
                'id' => $order_id
            );
            if (!$this->ci->b2o_parent_order_model->update_order($data_post, $data_post_where)) {
                return array("code" => "300", "msg" => "订单提交失败，请稍后重试");
            }

            //更新次数
            $available_times = intval($post_info['available_times'] - 1);
            $data_order_postage = array(
                'available_times' => $available_times,
            );
            $data_order_postage_where = array(
                'id' => $post_info['id']
            );
            if($available_times == 0)
            {
                $data_order_postage['postage_status'] = 0;
            }
            $this->ci->order_postage_model->update_postage($data_order_postage, $data_order_postage_where);

            //使用记录
            $post_log = array(
                'uid'=>$details['uid'],
                'order_name'=>$details["order_name"],
                'time'=>date('Y-m-d H:i:s'),
                'remark'=>'订单号' .$details["order_name"].'已使用邮费特权,减免'.$post_config['money'].'元',
                'start_time'=>$post_info['start_time'],
                'end_time'=>$post_info['end_time'],
                'times'=>$post_info['times'],
                'available_times'=>$available_times,
            );
            if($available_times == 0)
            {
                $post_log['remark'] = '邮费特权已使用完毕，无剩余次数';
            }
            $this->ci->postage_log_model->addPostLog($post_log);
        }


        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "订单提交失败，请重新提交订单");
        } else {
            $this->ci->db->trans_commit();
        }

        //0元拆单
        if($details['money'] == 0 && $is_all_gift == 1)
        {
            if($details['fresh_discount'] >0)
            {
                $fresh = array();
                $fresh['user_id'] = $uid;
                $fresh['card_no'] = $details['fresh_no'];
                $fresh['money'] = $details['fresh_discount'];
                $fresh['order_id'] = $details['order_name'];
                $this->ci->load->bll('apisd');
                $res = $this->ci->bll_apisd->doPay($fresh);
                if($res['code'] != 200)
                {
                    $this->checkCardLog($details['order_name'],$res);
                }
            }
            else if($details['jd_discount'] >0)
            {
                $jd = array();
                $jd['uid'] = $uid;
                $jd['amount'] = $details['jd_discount'];
                $jd['order_name'] = $details['order_name'];
                $this->ci->load->bll('apijd');
                $res = $this->ci->bll_apijd->doPay($jd);
                if($res['code'] != 200)
                {
                    $this->checkCardLog($details['order_name'],$res);
                }
                else
                {
                $this->ci->load->model('order_jd_model');
                    $res_jd = $res['data'];
                    foreach($res_jd as $k=>$v)
                    {
                        $jd_arr = array();
                        $jd_arr['order_name'] = $details['order_name'];
                        $jd_arr['oms_no'] = $v['oms_no'];
                        $jd_arr['amount'] = $v['amount'];
                        $jd_arr['trade_type'] = 'F';
                        $this->ci->order_jd_model->add($jd_arr);
                    }
                }
            }
            else
            {
                $par = array('order_name' => $details['order_name']);
                $this->orderSplit($par);
            }
        }

        $this->_afterCreateOrder($order_id, $user_info, $params,$cart_info);
        $res = array('order_name'=>$details["order_name"],"pay_parent_id" => $details['pay_parent_id'], "money" => $details['money']);

        $this->ci->load->model('ms_log_v2_model');
        $product2Qty = array();
        foreach($cart_info['products'] as $productInfo){
            $product2Qty[$productInfo['product_id']] = $productInfo['qty'];
        }

        $now = time();
        $msLogData = array();
        foreach($cart_info['products'] as $product){
            if (isset($product['dsc_id'])) {
                $hasBoughtQty = $this->ci->ms_log_v2_model->dump(array('promotion_id'=>$product['dsc_id'], 'product_id'=>$product['product_id'],'store_id'=>$product['store_id'],'is_del'=>0), 'sum(qty) as totleHasBought');
                if ($product['order_qty_limit'] != 0 && ($hasBoughtQty['totleHasBought'] + $product['qty'] > $product['order_qty_limit'])){
                    return array("code" => "300", "msg" => '秒杀商品已售完');
                }

                $hasBoughtQtyByUser = $this->ci->ms_log_v2_model->dump(array('uid'=>$uid, 'promotion_id'=>$product['dsc_id'], 'product_id'=>$product['product_id'],'store_id'=>$product['store_id'],'is_del'=>0), 'sum(qty) as totleHasBought');
                if ($product['user_qty_limit'] != 0 && ($hasBoughtQtyByUser['totleHasBought'] + $product['qty'] > $product['user_qty_limit'])){
                    return array("code" => "300", "msg" => '秒杀商品超过单人限制购买次数');
                }
                $msLogData[] = array('uid' => $uid, 'order_name' => $details['order_name'], 'promotion_id' => $product['dsc_id'], 'product_id' => $product['product_id'], 'store_id' => $product['store_id'], 'qty' => $product['qty'], 'time' => $now);
            }
        }

        //插入日志ms_log
        if(!empty($msLogData)){
            foreach($msLogData as $msLog){
                $this->ci->ms_log_v2_model->insert($msLog);
            }
        }

        //更新用户信息
        $this->setUserInfo($uid);

        return array("code" => "200", "msg" =>'succ','data'=>$res);
    }

    function check_cang_product($cart_info) {
        $cang_product = $this->ci->config->item('cang_product');
        foreach ($cart_info['products'] as $key => $value) {
            if (isset($cang_product[$value['product_id']])) {
                return $cang_product[$value['product_id']];
            }
        }
        return false;
    }

    function check_warehouse_product($cart_info) {
        $warehouse_info = array();
        foreach ($cart_info['products'] as $key => $value) {
            $warehouse_id = $this->ci->warehouse_model->getProductAppointWarehouse($value['sku_id']);
            if ($warehouse_id) {
                $warehouse_info = $this->ci->warehouse_model->getWarehouseByID($warehouse_id);
                if ($warehouse_info) {
                    return $warehouse_info;
                }
            }
        }
        return false;
    }

    private function _afterCreateOrder($order_id, $user_info, $params,$cart_info) {
        //下单成功后，增加返利信息
        //$this->_setOrderFanli($order_id, $params);

        //清空购物车

        //cart -v3
        $this->ci->load->bll('apicart');
        $api_cart = array();
        $api_cart['cart_id'] = $user_info['id'];
        $api_cart['store_id_list'] = $params['store_id_list'];
        $api_cart['user'] = $user_info['id'];
        $api_cart['source'] = $params['source'];
        $api_cart['version'] = $params['version'];
        $api_cart['tms_region_type'] = $params['tms_region_type'];
        $api_cart['cart_products'] = json_encode($cart_info['products']);
        $this->ci->bll_apicart->del($api_cart);

        //cart -v2
        //foreach($cart_info['products'] as $key =>$val) {
        //    $this->obj_cart->removeItem($val['item_id']);
        //}
    }

    private function _setOrderFanli($order_id, $userdata) {
        $this->ci->load->model('order_fanli_model');
        $this->ci->order_fanli_model->insFanliOrder($order_id, $userdata);
    }

    /*
    *订单商品插入
    */
    private function orderAddPro($uid, $order_id, $cart_info,$cang_id=0,$card_info = array()) {
        $cart_array = $cart_info;

        $card_product_info = array();
        if($card_info && $card_info['product_id']){
            $card_product_info = explode(',', $card_info['product_id']);
        }

        if ($cart_array != null) {
            $price = 0;
            $giftTypeList = $this->_setProductType();
            $order_pro_type_list = $this->ci->config->item('order_product_type');

            //组合商品
            $groups = $this->ci->b2o_product_group_model->getGroupList();
            $this->ci->load->model('b2o_store_model');
            $stores = $this->ci->b2o_store_model->getList();
            $store_type = array();
            foreach ($stores as $key => $value) {
                $store_type[$value['id']] = ($value['type'] == 1) ? 2 : 1; //2大门店  1 小门店
            }

            foreach ($cart_array as $cart_key => $item) {

                $can_use_card = 1;
                //记录不可使用优惠券的商品
                if($item['card_limit'] == 1){
                    $can_use_card = 0;
                }
                if($card_product_info && !in_array($item['product_id'], $card_product_info)){
                    $can_use_card = 0;
                }

                $store_id = $item['store_id'];
                //商品类型
                $order_pro_type = isset($order_pro_type_list[$item['item_type']]) ? $order_pro_type_list[$item['item_type']] : '1';
                if ($item['price'] == 0 || $item['amount'] == 0) {
                    $order_pro_type = 3;
                }
                //商品积分处理，赠品没有积分
                $is_gift = false;
                if (in_array($order_pro_type, $giftTypeList)) {
                    $is_gift = true;
                    $score = 0;
                } else {
                    $score = $this->ci->b2o_parent_order_model->get_order_product_score($uid, $item);
                }

                $product_name = addslashes($item['name']);
                $total_money = $item ['amount'];
                $gg_name = $item['volume'] . '/' . $item['unit'];

                //组合商品
                $g_arr = array();
                if(count($groups) >0)
                {
                    foreach($groups as $k=>$v)
                    {
                        if($v['product_id'] == $item['product_id'])
                        {
                            if(! isset($store_type[$item['store_id']])){
                                $store_type[$item['store_id']] = 1;
                            }
                            if(isset($store_type[$item['store_id']]) && $store_type[$item['store_id']] == $v['channel']){
                                array_push($g_arr,$v);
                            }
                        }
                    }
                }

                if(count($g_arr) >0)
                {
                    $group_products = json_encode($g_arr);
                }
                else
                {
                    $group_products = '';
                }

                //赠品订单
                $gift_price = '0.00';
                $user_gift_id = 0;
                if($is_gift)
                {
                    $this->ci->load->model('user_gifts_model');
                    $this->ci->load->model('gifts_goods_model');

                    $user_gifts = $this->ci->user_gifts_model->dump(array('id'=>$item['user_gift_id']));
                    if($user_gifts['pid'] >0)
                    {
                        $gifts_arr = $this->ci->gifts_goods_model->dump(array('pid'=>$user_gifts['pid'],'active_id'=>$user_gifts['active_id']));
                        $gift_price = $gifts_arr['price'] * $item['qty'];
                    }
                    $user_gift_id = $item['user_gift_id'];
                }

                //异常处理
                if($item['price'] < 0)
                {
                    return array("code" => "300", "msg" => '商品异常，请返回购物车');
                }

                $order_product_data = array(
                    'order_id' => $order_id,
                    'product_name' => $product_name,
                    'product_id' => $item['product_id'],
                    'product_no' => $item['product_no'],
                    'gg_name' => $gg_name,
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'score' => $score,
                    'type' => $order_pro_type,
                    'total_money' => $total_money,
                    'sid'=>$item['store_id'],
                    'group_products'=>$group_products,
                    'gift_price'=>$gift_price,
                    'user_gift_id'=>$user_gift_id,
                    'is_oversale'=>isset($item['is_oversale']) ? $item['is_oversale'] : 0,
                    'can_use_card'=>$can_use_card,
                );
                $this->ci->b2o_parent_order_model->addOrderProduct($order_product_data);

                //商品库存
                $this->ci->b2o_store_product_model->reduce_product_stock($item['product_id'],$item['store_id'],$item['qty']);

                //赠品领取 - 蔡韵辰
                if ($is_gift) {
                    $ug = $this->ci->b2o_parent_order_model->receive_user_gift($uid, $order_id, $item['user_gift_id']);
                    if($ug['code']=='300' && $item['type'] == 'user_gift')
                    {
                        return array("code" => "300", "msg" => '赠品购买失败');
                    }
                }
            }
        } else {
            return array("code" => "300", "msg" => '购物车中没有产品，请添加产品');
        }
    }

    /*
    *订单地址插入
    */
    private function orderAddAddr($uid, $address_id, $order_id, $is_enterprise) {
        $result = $this->ci->user_model->address_info($uid, $address_id, $is_enterprise);
        if (isset($result['code'])) {
            return $result;
        } else {
            if (is_numeric($result['area'])) {
                $this->ci->region_model->region = '';
                $region = $this->ci->region_model->get_region($result['area']);
                $address = $region . $result['address_name'] . $result['address'];
            } else {
                $address = $result['area'] . $result['address_name'] . $result['address'];
            }
            $email = addslashes(strip_tags($result['email']));
            $telephone = addslashes(strip_tags($result['telephone']));
            $mobile = addslashes(strip_tags($result['mobile']));
            $name = addslashes(strip_tags($result['name']));
            if (!preg_match('/^1\d{10}$/', $mobile)) {
                return array("code" => "300", "msg" => '收货人手机号格式错误，请输入11位数字');
            }
            $order_address = array(
                'order_id' => $order_id,
                'position' => $region,
                'address' => $address,
                'name' => $name,
                'email' => $email,
                'telephone' => $telephone,
                'mobile' => $mobile,
                'province' => $result['province'],
                'city' => $result['city'],
                'area' => $result['area'],
                'lonlat'=>$result['lonlat'],
                'address_name'=>$result['address'],
            );
            $this->ci->b2o_parent_order_model->addOrderAddr($order_address);
            $data = array($address, $name, $email, $telephone, $mobile);
            return $data;
        }
    }

    /*
    *获取订单地区
    */
    function get_order_region($address_id) {
        $result = $this->ci->region_model->get_user_address_info('province', $address_id);
        $area_refelect = $this->ci->config->item("area_refelect");
        $order_region = 1;
        foreach ($area_refelect as $key => $value) {
            if (in_array($result['province'], $value)) {
                $order_region = $key;
                break;
            }
        }
        return $order_region;
    }

    /*
    *用户订单列表
    */
    function orderList($params) {
        //必要参数验证
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }

        //get order list
        $page = !empty($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = ($page - 1) * $limit;
        $order_where_in = array();

        $order_fields = 'id,send_date,uid,order_name,time,pay_name,shtime,money,pay_status,operation_id,pay_parent_id,had_comment,has_bask,order_type,sync_status,use_money_deduction,new_pay_discount,sheet_show_price';
        $order_where = array(
            'uid' => $uid,
            'order_status' => '1',
            //'order_type !=' => 8
        );
        if ($params['ctime']) {
            $order_where['time <'] = date("Y-m-d H:i:s", strtotime("-3 months"));
        }
        $order_status_filter = $this->_setOperation($params['order_status']);
        if (!empty($order_status_filter)) {
            $order_where_in[] = array(
                'key' => 'operation_id',
                'value' => $order_status_filter
            );
        }

        //fix 过滤订单类型
        $order_where_in[] = array(
            'key' => 'order_type',
            'value' =>array('1','2','3','4','5','7','13')
        );
        $order_orderby = 'time desc';
        $result = $this->ci->order_model->selectOrderList($order_fields, $order_where, $order_where_in, $order_orderby, $limit, $offset);
        $result = $this->_initOrderList($result, $params);
        $total = $this->ci->order_model->countOrderList("id", $order_where, $order_where_in);
        return array('list' => $result, 'countOrder' => $total);
    }

    /**
     * 已付款之后的订单状态逻辑。
     * @param int $operation_id
     * @param string $time
     * @param string $had_comment
     * @return string
     */
    private function getOrderStatusAfterPay($operation_id, $time, $had_comment)
    {
        if (in_array($operation_id, [0, 1, 4])) {
            return '待发货';
        }

        if ($operation_id == 2) {
            return '待收货';
        }

        if (in_array($operation_id, [3, 6, 9])
            && strtotime($time)  > strtotime(date("Y-m-d", strtotime('-' . $this->can_comment_period)))
            && $had_comment === '0') {
            return '待评价';
        }

        return '交易成功';
    }

    /**
     * 获得订单状态的文字表达。
     * @param  int $operation_id
     * @param  int $pay_parent_id
     * @param  int $pay_status
     * @return string
     */
    private function getOrderStatusName($operation_id, $pay_parent_id, $pay_status, $time, $had_comment)
    {
        $delivering_status_list = [0,1,2,4,6,9];
        $result = '';

        if ($pay_status == 0 && $pay_parent_id != 4 && $operation_id == 0) {
            $result = '待付款';
        } else if ($pay_status == 1 && $pay_parent_id != 4 && in_array($operation_id, $delivering_status_list)) {
            $result = $this->getOrderStatusAfterPay($operation_id, $time, $had_comment);
        } else if ($pay_parent_id == 4 && in_array($operation_id, $delivering_status_list)) {
            $result = '货到付款';
        } else if ($operation_id == 3) {
            $result = '交易成功';
        } else if ($operation_id == 5) {
            $result = '已取消';
        }

        return $result;
    }
    /*
    *订单详情
    */
    function orderInfo($params) {
        //必要参数验证
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );

        //ios bug
        if (empty($params['order_name']) || empty($params['connect_id'])) {
            //$this->checkLog($params['order_name'].'|'.$params['connect_id'].'|'.$params['source'],'connect_id is null');
            return array('code' => '300', 'msg' => '支付订单异常，请到我的订单，再支付订单');
        }

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }

        //b2o
        $bs = mb_substr($params['order_name'],0,1);
        if($bs == 'P')
        {
            $par = array('order_name'=>$params['order_name'],'uid'=>$uid);
            return $this->b2oOrderInfo($par);
        }


        //获取订单信息
        $order_name = $params['order_name'];
        $where = array(
            'order.order_name' => $order_name,
            'order.uid' => $uid,
        );

        $field = 'order.id,order.send_date,order.pay_parent_id,order.pay_id,order.uid,order.order_name,order.time,order.pay_name,order.shtime,order.stime,order.money,order.pay_status,order.operation_id,order.fp,order.goods_money,order.method_money,order.jf_money,order.card_money,order.had_comment,order.has_bask,order.pay_discount,order.new_pay_discount,order.oauth_discount,order_address.address,order_address.name,msg,use_money_deduction,operation_id,had_comment,order_address.telephone,order_address.mobile,order.order_region,order.order_type,order.sync_status,hk,order_address.id as address_id,order_address.province as province,invoice_money,order.sheet_show_price,order.channel,order.order_status,order.address_id,order.p_order_id,order.post_discount,order.refund_money,order.fresh_discount,order.jd_discount,order.change_addr_status,order.no_stock,order.fp_id_no,order.kp_type as order_kp_type,order.pickup_store_id,order.pickup_code';
        $join[] = array(
            'table' => 'order_address',
            'type' => 'left',
            'field' => 'order.id=order_address.order_id',
        );
        $join[] = array(
            'table' => 'order_invoice',
            'type' => 'left',
            'field' => 'order.id=order_invoice.order_id'
        );
        $field .= ',order_invoice.username as invoice_username,order_invoice.mobile as invoice_mobile,order_invoice.address as invoice_address, order_invoice.province as invoice_province, order_invoice.city as invoice_city, order_invoice.area as invoice_city, order_invoice.kp_type';
        $result = $this->ci->order_model->selectOrder($field, $where, $join);
        if (empty($result)) {
            return array('code' => '500', 'msg' => 'connect id error');
        }

        $time = $result['time'];
        $had_comment = $result['had_comment'];
        $pay_status = $result['pay_status'];
        $operation_id = $result['operation_id'];
        $pay_parent_id = $result['pay_parent_id'];

        //初始化订单信息
        $result_array = $this->_initOrderList(array($result), $params);
        $result = $result_array[0];

        // APP订单详情的订单状态文案修改。
        $result['order_status'] = $this->getOrderStatusName($operation_id, $pay_parent_id, $pay_status, $time, $had_comment);

        $result['order_id'] = $result['id'];
        if (in_array($result['order_type'], array(3))) {
            $this->ci->load->model('o2o_order_extra_model');
            $store_id = $this->ci->o2o_order_extra_model->getStoreByOrderId($result['order_id']);
            $result['store_id'] = $store_id;

            //倒计时
            $result['start_time'] = date('Y-m-d H:i:s');
            if($result['pay_status_key'] == 1 && in_array($result['operation_id'], array(0,1,2,4)))
            {
                $result['end_time'] = $this->showTimes($result['shtime'],$result['stime']);
            }
            $result['show_time_state'] = 1;
        }
        else
        {
            $result['start_time'] = date('Y-m-d H:i:s');
            $result['end_time'] = '';
            $result['show_time_state'] = 0;
        }

        //商品
        $giftTypeList = $this->_setProductType();
        foreach ($result['item'] as $key => $value) {
            $result['item'][$key]['product_type'] = in_array($value['order_product_type'], $giftTypeList) ? 3 : 1;
            unset($result['item'][$key]['order_product_type']);
        }

        //设置下单时候和钱
        $stime = '';
        if ($result['stime'] == '1822') {
            $stime = '18:00-22:00';
        } elseif ($result['stime'] == '0918') {
            $stime = '09:00-18:00';
        } elseif ($result['stime'] == '0914') {
            $stime = '09:00-14:00';
        } elseif ($result['stime'] == '1418') {
            $stime = '14:00-18:00';
        } elseif ($result['stime'] == '0914') {
            $stime = '09:00-14:00';
        } elseif ($result['stime'] == '1418') {
            $stime = '14:00-18:00';
        }

        if ($result['shtime'] == 'after2to3days') {
            $result['shtime'] = '下单后3天内送达';
            switch ($result['stime']) {
                case 'weekday':
                    $result['shtime'] .= "(工作日配送)";
                    break;
                case 'weekend':
                    $result['shtime'] .= "(周末配送)";
                    break;
                default:
                    # code...
                    break;
            }
        } elseif ($result['shtime'] == 'after1to2days') {
            $result['shtime'] = '下单后2天内送达';
            switch ($result['stime']) {
                case 'weekday':
                    $result['shtime'] .= "(工作日配送)";
                    break;
                case 'weekend':
                    $result['shtime'] .= "(周末配送)";
                    break;
                default:
                    # code...
                    break;
            }
        } elseif ($result['stime'] == '2hours') {
            $result['shtime'] = '两小时之内配送';
        } elseif ($result['stime'] == '1hour') {
            $result['shtime'] = '1小时之内配送';
        } elseif ($result['stime'] == 'selfTake') {
            $result['shtime'] = '活动期间自提';
        } elseif ($result['stime'] == 'am') {
            $result['shtime'] = trim(date("Y-m-d", strtotime($result['shtime'])) . ' 上午');
        } elseif ($result['stime'] == 'pm') {
            $result['shtime'] = trim(date("Y-m-d", strtotime($result['shtime'])) . ' 下午');
        } elseif (in_array($result['stime'], array('weekday', 'weekend', 'all'))) {
            $result['shtime'] = trim(date("Y-m-d", strtotime($result['shtime']))) . '发货';
            switch ($result['stime']) {
                case 'weekday':
                    $result['shtime'] .= "(工作日配送)";
                    break;
                case 'weekend':
                    $result['shtime'] .= "(周末配送)";
                    break;
                default:
                    # code...
                    break;
            }

        } else {
            $aWeekName = ['日', '一', '二', '三', '四', '五', '六'];
            $iUnixTime = strtotime($result['shtime']);
            $result['shtime'] = trim(date("m-d 周", $iUnixTime) . $aWeekName[date('w', $iUnixTime)] . ' ' . $stime);
        }

        // $result['shtime'] = ($result['shtime']=='after2to3days')?'下单后3天内送达':trim(date("Y-m-d",strtotime($result['shtime'])).' '.$stime);

        $result['pay_discount_money'] = $result['pay_discount'] + $result['new_pay_discount'] + $result['oauth_discount'];
        $result['mail_money'] = $result['method_money'];

        $method_money = $result['method_money'];

        $new_pay_discount = $result['new_pay_discount'];
        unset($result['method_money'], $result['pay_discount'], $result['new_pay_discount'], $result['oauth_discount']);

        if (empty($result['fp'])) {
            $result['fp'] = '';
        }
        $result['score_desc'] = '发表评论审核通过增加10积分，附加晒单图片再送10积分';

        /*调查问卷start*/
        $survey_url = '';
        if ($result['order_region'] == 1 && in_array($result['operation_id'], array(3, 9))) {
            if ($this->ci->order_model->ckDistSurvey($order_name)) {
                $survey_unique = $this->getSurveyUnique($uid, $order_name);
                $survey_url = 'http://www.fruitday.com/survey/distribute/' . $survey_unique;
            }
        }
        $result['survey_url'] = $survey_url;
        /*调查问卷end*/
        /*调查问卷srart*/
        if ($result['order_type'] == 3) {
            $result['store_contact'] = $this->ci->order_model->getStoreTel($result['id']);
        } else {
            $result['store_contact'] = '';
        }
        /*门店联系电话end*/
        /*物流查询srart*/
        // $logistic_info = $this->ci->order_model->getLogisticInfo($order_name, $uid);
        // if (!empty($logistic_info)) {
        //     $result['can_check_logistic'] = 1;
        // } else {
        //     $result['can_check_logistic'] = 0;
        // }
        if($result['sync_status'] == 1 && in_array($result['operation_id'], array(1,2,3,4,6,9))){
            $result['can_check_logistic'] = 1;
        }else{
            $result['can_check_logistic'] = 0;
        }
        /*物流查询end*/
        $result['card_money'] = number_format($result['card_money'], 2, '.', '');

        $invoice_show = $this->ci->order_model->get_order_invoice($result['id'], 0);
        if (!empty($invoice_show) && $result['pay_parent_id'] == 5)
        {
            $result['show_money'] = number_format((float)($result['money'] + $new_pay_discount + 5), 2, '.', '');
        }
        else
        {
            $result['show_money'] = number_format((float)($result['money'] + $new_pay_discount), 2, '.', '');
        }

        /*余额改造 srart*/
        if ($params['scene'] == 'pay' && $result['pay_status_key'] == 0 && $result['pay_parent_id'] != 4 && $result['pay_parent_id'] != 6 && $result['order_status_key'] == 1) {
            //冻结
            $check = $this->ci->user_model->check_money_identical($uid);
            if ($check === false) {
                $this->ci->user_model->freeze_user($uid);
                return array("code" => "300", "msg" => "您的账号异常，已被冻结，请联系客服");
            }

            $this->ci->db->trans_begin();

            $user = $this->ci->user_model->getUser($uid);
            $result['user_money'] = $user['money'];
            $order_money = $result['money'] + $new_pay_discount;

            //充值卡(券卡)
            $is_can_balance = 1;
            $pro_list = $this->ci->order_product_model->getOrderProductList($result['id']);
            if (!empty($pro_list)) {
                foreach ($pro_list as $key => $row) {
                    if ($row['iscard'] == 1) {
                        $is_can_balance = 0;
                    }
                }
            } else {
                $is_can_balance = 0;
            }
            $result['is_can_balance'] = $is_can_balance;

            if ($result['user_money'] == '0.00') {
                //支付折扣优惠
                $order_products = $this->ci->order_product_model->getProductsByOrderId($result['id']);
                $pids = array();
                foreach ($order_products as $key => $value) {
                    if ($value['category'] == 1) $pids[] = $value['sku'];
                }
                switch ($result['channel']) {
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
                $o_area_info = $this->ci->order_model->getIorderArea($result['id']);
                $new_pay_discount_zhifu = $this->ci->pay_discount_model->set_order_pay_discount($result['pay_parent_id'], $result['pay_id'], $order_money, $pids, $result['id'], $source, $o_area_info['province'], $uid, $result['order_type']);

                $data = array(
                    'money' => $order_money - $new_pay_discount_zhifu,
                    'use_money_deduction' => $result['user_money']
                );
                $where = array(
                    'order_name' => $params['order_name'],
                    'order_status' => 1,
                );
                $this->ci->order_model->update_order($data, $where);

                $result['user_can_money'] = number_format('0', 2, '.', '');
                $result['need_online_pay'] = number_format($order_money - $new_pay_discount_zhifu, 2, '.', '');
                $result['is_pay_balance'] = 0;
                $result['need_send_code'] = 0;
                $result['pay_bank_discount'] = number_format($new_pay_discount_zhifu, 2, '.', '');
            } else if ($result['user_money'] > 0) {
                //是否可以全额抵扣
                if($result['user_money'] >= ($result['money']+$new_pay_discount))
                {
                    //开启默认使用余额
                    if($result['is_can_balance'] == 1)
                    {
//                        $pay_array  =  $this->ci->config->item("pay_array");
//                        $pay_parent_id = 5;
//                        $pay_id = 0;
//                        $pay_name = $pay_array[$pay_parent_id]['name'];
//                        $this->ci->order_model->set_ordre_payment($pay_name,$pay_parent_id,$pay_id,$result['id']);
//                        $result['pay_parent_id'] = 5;
//                        $result['pay_id'] = 0;
//                        $result['pay_name'] = '账户余额支付';
                    }

                    if ($result['invoice_money'] > 0 && $result['pay_parent_id'] == 5) {
                        $order_money = $order_money - $result['invoice_money'];
                        $data = array(
                            'use_money_deduction' => 0.00,
                            'new_pay_discount' => 0.00,
                            'money' => $order_money,
                            'invoice_money' => 0.00
                        );

                        //取消发票
                        $invoice = array(
                            'is_valid' => 0
                        );
                        $invoice_where = array(
                            'order_id' => $result['id']
                        );
                        $this->ci->order_model->update_order_invoice($invoice, $invoice_where);
                    } else {
                        $data = array(
                            'use_money_deduction' => 0.00,
                            'new_pay_discount' => 0.00,
                            'money' => $order_money
                        );
                    }
                    $where = array(
                        'order_name' => $params['order_name'],
                        'order_status' => 1,
                    );
                    $this->ci->order_model->update_order($data, $where);

                    $result['user_can_money'] = number_format(($result['user_money'] - $result['money']), 2, '.', '');
                    if ($result['pay_parent_id'] == 5) {
                        $result['need_online_pay'] = number_format(0, 2, '.', '');
                        $result['pay_bank_discount'] = number_format(0, 2, '.', '');
                        //余额支付短信验证
                        $cart_array = array();
                        $order_address_info = $this->ci->order_model->get_order_address($result['address_id']);
                        $result['need_send_code'] = 0;
                        $if_send_code = $this->ci->order_model->checkSendCode($cart_array, $uid, $result['pay_parent_id'], $order_address_info, $result['id']);
                        if ($if_send_code) {
                            $result['need_send_code'] = 1;
                        }
                    } else {
                        //支付折扣优惠
                        $order_products = $this->ci->order_product_model->getProductsByOrderId($result['id']);
                        $pids = array();
                        foreach ($order_products as $key => $value) {
                            if ($value['category'] == 1) $pids[] = $value['sku'];
                        }
                        switch ($result['channel']) {
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
                        $o_area_info = $this->ci->order_model->getIorderArea($result['id']);
                        //以线上支付金额计算支付折扣
                        $new_pay_discount_zhifu = $this->ci->pay_discount_model->set_order_pay_discount($result['pay_parent_id'], $result['pay_id'], $order_money, $pids, $result['id'], $source, $o_area_info['province'], $uid, $result['order_type']);

                        $data = array(
                            'money' => $order_money - $new_pay_discount_zhifu
                        );
                        $where = array(
                            'order_name' => $params['order_name'],
                            'order_status' => 1,
                        );
                        $this->ci->order_model->update_order($data, $where);

                        $result['need_online_pay'] = number_format($order_money - $new_pay_discount_zhifu, 2, '.', '');
                        $result['need_send_code'] = 0;
                        $result['pay_bank_discount'] = number_format($new_pay_discount_zhifu, 2, '.', '');
                    }
                    $result['is_pay_balance'] = 1;

                    //同步o2o
                    if ($result['order_type'] == 3 || $result['order_type'] == 4) {
                        $this->order_o2o_split($uid, $result['id'], 1);
                    }
                } else {
                    //支付折扣优惠
                    $order_products = $this->ci->order_product_model->getProductsByOrderId($result['id']);
                    $pids = array();
                    foreach ($order_products as $key => $value) {
                        if ($value['category'] == 1) $pids[] = $value['sku'];
                    }
                    switch ($result['channel']) {
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
                    $o_area_info = $this->ci->order_model->getIorderArea($result['id']);
                    $new_money = $order_money - $result['user_money'];
                    //以线上支付金额计算支付折扣
                    $new_pay_discount_zhifu = $this->ci->pay_discount_model->set_order_pay_discount($result['pay_parent_id'], $result['pay_id'], $new_money, $pids, $result['id'], $source, $o_area_info['province'], $uid, $result['order_type']);

                    if ($result['is_can_balance'] == 1) {
                        $data = array(
                            'use_money_deduction' => $result['user_money'],
                            'money' => ($order_money - $new_pay_discount_zhifu) - $result['user_money']
                        );
                        $where = array(
                            'order_name' => $params['order_name'],
                            'order_status' => 1,
                        );
                        $this->ci->order_model->update_order($data, $where);

                        $result['use_money_deduction'] = number_format($data['use_money_deduction'], 2, '.', '');
                        $result['user_can_money'] = number_format('0', 2, '.', '');
                        $result['need_online_pay'] = number_format($order_money - $result['user_money'] - $new_pay_discount_zhifu, 2, '.', '');
                        $result['is_pay_balance'] = 0;

                        //余额支付短信验证
                        $cart_array = array();
                        $order_address_info = $this->ci->order_model->get_order_address($result['address_id']);
                        $if_send_code = $this->ci->order_model->checkSendCode($cart_array, $uid,$result['pay_parent_id'], $order_address_info, $result['id']);

                        //组合支付
                        if($result['use_money_deduction'] >0 && $if_send_code)
                        {
                            $result['need_send_code'] = 1;
                        }
                        else
                        {
                            $result['need_send_code'] = 0;
                        }
                    } else {
                        $result['use_money_deduction'] = number_format('0', 2, '.', '');
                        $result['user_can_money'] = number_format('0', 2, '.', '');
                        $result['need_online_pay'] = number_format($order_money - $new_pay_discount_zhifu, 2, '.', '');
                        $result['is_pay_balance'] = 0;
                        $result['need_send_code'] = 0;
                    }

                    $result['pay_bank_discount'] = number_format($new_pay_discount_zhifu, 2, '.', '');
                    //同步o2o
                    if ($result['order_type'] == 3 || $result['order_type'] == 4) {
                        $this->order_o2o_split($uid, $result['id'], 1);
                    }
                }
            }

            //支付方式
            $this->ci->load->bll($params['source'] . '/region');
            $obj = 'bll_' . $params['source'] . '_region';
            if (!empty($result['province'])) {
                $send_time_params = array(
                    'service' => 'region.getPay',
                    'province_id' => $result['province'],
                    'connect_id' => $params['connect_id'],
                    'source' => $params['source'],
                    'version' => $params['version'],
                    'platform' => $params['platform'],
                );
                $pay_arr = $this->ci->$obj->getPay($send_time_params);
                unset($pay_arr['fday']);
                unset($pay_arr['offline']);
                $result['payments'] = $pay_arr;
            }

            if ($result['pay_parent_id'] == 5 && $result['use_money_deduction'] > 0) {
                $result['selectPayments'] = array(
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
            } else {
                $prompt = $this->showBankText($result['pay_id'],$result['pay_parent_id']);
                $result['selectPayments'] = array(
                    'pay_parent_id' => $result['pay_parent_id'],
                    'pay_id' => $result['pay_id'],
                    'pay_name' => $result['pay_name'],
                    'has_invoice' => 0,
                    'no_invoice_message' => '',
                    'icon' => '',
                    'discount_rule' => '',
                    'user_money' => '',
                    'prompt'=>$prompt
                );
            }

            //重新计算－订单积分
            $order_new = $this->ci->order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid, 'order_status' => 1));
            $order_score = $this->ci->user_model->dill_score_new($order_new['money'] - $order_new['method_money'], $uid);
            if ($order_score < 0) {
                $order_score = 0;
            }
            if ($order_new['pay_parent_id'] == 5) {
                $order_score = 0;
            }
            $ds_score = array(
                'score' => $order_score
            );
            $where_score = array(
                'order_name' => $params['order_name'],
                'order_status' => 1,
            );
            $this->ci->order_model->update_order($ds_score, $where_score);

            $result['money'] = number_format($order_money, 2, '.', '');

            $result['balance_text'] = '余额不支持购买充值卡';

            if ($this->ci->db->trans_status() === FALSE) {
                $this->ci->db->trans_rollback();
                return array("code" => "300", "msg" => "请返回订单，重新支付");
            } else {
                $this->ci->db->trans_commit();
            }
        }

        $result['mail_money'] = $method_money + $result['invoice_money'];

        //返回发票类型
        $this->ci->load->model('order_einvoices_model');
        $einvoice = $this->ci->order_einvoices_model->count(array('order_name'=>$params['order_name']));
        if($einvoice == 0){
            $result['invoice_type'] = 0;
        }else{
            $result['invoice_type'] = 1;
        }
        if ($result['pay_status_key'] == 1) {
            $result['show_use_money_deduction'] = $result['use_money_deduction'];
        } else {
            $result['show_use_money_deduction'] = '0.00';
        }

        /*余额改造 end*/

        //物流查询
        if($params['source'] == 'app')
        {
            $this->ci->load->bll('pool/order',null,'bll_pool_order');
            $order_route = $this->ci->bll_pool_order->getLogisticTrace($params['order_name']);
            if(count($order_route) >0)
            {
                foreach($order_route as $key=>$val)
                {
                    $order_route[$key]['createDate'] = date('Y-m-d H:i:s',$val['createDate']/1000);
                    unset($order_route[$key]['opStateName']);
                    unset($order_route[$key]['opName']);
                }
            }
            $result['route_list'] = $order_route[0];
            if($params['version'] == '4.0.0' && $params['platform'] == 'ANDROID'){
                $result['route_list'] = NULL;
            }

            //地址flag
            if(!empty($result['address_id']))
            {
                $this->ci->load->model('user_address_model');
                $user_add = $this->ci->user_address_model->dump(array('id' => $result['address_id']));
                if(!empty($user_add))
                {
                    $result['flag'] = $user_add['flag'];
                }
            }
        }

        //包裹
        $order_items = $result['item'];
        $items = array();
        $order_pro_type_list = $this->ci->config->item('b2o_order_product_type');
        foreach($order_items as $key=>$val)
        {
            $order_items[$key]['name'] = $val['product_name'];
            $order_items[$key]['photo'] = $val['thum_photo'];
            $order_items[$key]['item_id'] = '';
            $order_items[$key]['sku_id'] = '';
            $order_items[$key]['store_id'] = '';
            $order_items[$key]['weight'] = '';
            $order_items[$key]['volume'] = $val['gg_name'];
            $order_items[$key]['unit'] = '';
            $order_items[$key]['amount'] = '';
            $order_items[$key]['is_pc_online'] = '';
            $order_items[$key]['is_app_online'] = '';
            $order_items[$key]['is_wap_online'] = '';
            $order_items[$key]['selected'] = '';
            $order_items[$key]['valid'] = '';
            $order_items[$key]['type'] = $order_pro_type_list[$val['product_type']];
            $order_items[$key]['percentage'] = '';
            $order_items[$key]['discount'] = '';

            unset($order_items[$key]['product_name']);
            unset($order_items[$key]['thum_photo']);
            unset($order_items[$key]['order_id']);
            unset($order_items[$key]['order_product_id']);

            $items[] = $order_items[$key];
        }

        //自提
        $self_pick = array(
            'is_can'=>0,
            'is_select'=>0,
            'store_id'=>'',
            'store_name'=>'',
            'store_address'=>'',
        );
        $this->ci->load->model('b2o_store_model');
        $store_data = $this->ci->b2o_store_model->dump(array('id' =>$result['pickup_store_id']));
        if($store_data['self_pick'] == 1)
        {
            $self_pick['is_can'] = 1;
            $self_pick['is_select'] = 1;
            $self_pick['store_id'] = $result['pickup_store_id'];
            $self_pick['store_name'] = $store_data['name'];
            $self_pick['store_address'] = '上海市'.$store_data['address'];
        }

        $package = array(
            0=>array(
                'item'=>$items,
                'package_type'=>'',
                'tag'=>'',
                'amount'=>'',
                'weight'=>'',
                'count'=>'',
                'disamount'=>'',
                'method_money'=>'',
                'send_time'=>(object)array(),
                'chose_sendtime'=>array(
                    'tag'=>'',
                    'shtime'=>$result['shtime'],
                    'stime'=>$result['stime'],
                ),
                'store'=>$self_pick,
            )
        );
        $result['package'] = $package;

        //兼容订单
        if($result['p_order_id'] == 0)
        {
            $result['stime']='';
        }

        //已补开发票
        $this->ci->load->model('subsequent_invoice_order_model');
        $supply_invoice = $this->ci->subsequent_invoice_order_model->dump(array('order_name' => $params['order_name']));
        if(!empty($supply_invoice))
        {
            $stime = strtotime($result['time']);
            $etime = strtotime(date('Y-m-d'));
            $days = ($etime - $stime)/86400;
            if($days >= 180)
            {
                $result['is_supply_invoice'] = 0;
            }
            else
            {
                $result['is_supply_invoice'] = 1;
            }
        }
        else
        {
            $result['is_supply_invoice'] = 0;
        }

        return $result;
    }


    /*
    *订单取消
    */
    function orderCancel($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        //b2o
        $bs = mb_substr($params['order_name'],0,1);
        if($bs == 'P')
        {
            $par = array('order_name'=>$params['order_name'],'uid'=>$uid);
            return $this->b2oOrderCancel($par);
        }

        $result = $this->ci->order_model->selectOrder('id,order_name,channel,sync_status,is_enterprise,order_type,p_order_id,money,pay_status', array('order_name' => $params['order_name'], 'uid' => $uid));
        if (empty($result)) {
            return array("code" => "300", "msg" => '操作失败');
        }

        if ( ($result['order_type'] == 3 || $result['order_type'] == 4) && $result['pay_status']==1 && $result['p_order_id']>0 )
        {
            if(version_compare($params['version'], '5.7.0') >= 0){
                $canl = $this->o2oCancel($params['order_name']);
                if($canl == 1)
                {
                    return array('code' => '200', 'msg' =>'succ');
                }
                else
                {
                    return array('code' => '300', 'msg' => '订单已经审核不能取消');
                }
            }
            else
            {
                return array('code' => '300', 'msg' => '订单已经审核不能取消');
                $this->ci->load->model('o2o_child_order_model');
                $childOrders = $this->ci->o2o_child_order_model->get_child_orders_by_parent_id($result['id']);
                foreach ($childOrders as $value) {
                    if ($value['sync_status'] != 0) {
                        return array('code' => '300', 'msg' => '订单已经审核不能取消');
                    }
                }
            }
        }

        //事物开启
        $this->ci->db->trans_begin();

        //取消子订单
        if($result['p_order_id'] != 0 && $result['money'] >0)
        {
            $res_p_order = $this->ci->order_model->get_gift_order($result['p_order_id']);
            foreach($res_p_order as $key=>$val)
            {
                $is_gift = $this->ci->order_model->get_gift_product($val['id'],$val['uid'],$val['p_order_id']);
                if($is_gift == 0)
                {
                    $msg = 'succ';
                    $params['order_name'] = $val['order_name'];
                    $res_cancel = $this->cancel($val['id'], $msg, false, $params);
                    if ($res_cancel) {
                        if ($val['order_type'] == 3 || $val['order_type'] == 4) {

                        }
                        else
                        {
                            //oms todo
                            $this->ci->load->bll('pool');
                            $poolrs = $this->ci->bll_pool->pool_order_cancel($val['order_name'], $val['channel'], $val['sync_status'], $val['is_enterprise'], $val['order_type']);
                            if ($poolrs['status'] != 'succ') {
                                return array('code' => 300, 'msg' => $poolrs['msg']);
                            }
                        }
                    }
                }
            }
        }

        $msg = 'succ';
        $cancel_result = $this->cancel($result['id'], $msg, false, $params);
        if (!$cancel_result) {
            $this->ci->db->trans_rollback();
            return array('code' => '300', 'msg' => $msg);
        } else {
            if ($result['order_type'] == 3 || $result['order_type'] == 4) {
                /*$this->ci->load->bll('pool/o2o/order',null,'bll_pool_o2o_order');
                $this->ci->load->model('o2o_child_order_model');
                $childOrders = $this->ci->o2o_child_order_model->get_child_orders_by_parent_id($result['id']);
                foreach($childOrders as $value){
                    $poolrs = $this->ci->bll_pool_o2o_order->pool_cancel($value['order_name'],$value['sync_status']);
                    if ($poolrs['result'] != '1') return array('code'=>300,'msg'=>$poolrs['msg']);
                }*/

            } else {
                //oms todo
                $this->ci->load->bll('pool');
                $poolrs = $this->ci->bll_pool->pool_order_cancel($result['order_name'], $result['channel'], $result['sync_status'], $result['is_enterprise'], $result['order_type']);
                if ($poolrs['status'] != 'succ') {
                    $this->ci->db->trans_rollback();
                    return array('code' => 300, 'msg' => $poolrs['msg']);
                }
            }

            if ($this->ci->db->trans_status() === FALSE) {
                $this->ci->db->trans_rollback();
                return array('code' => '300', 'msg' => '订单取消失败');
            } else {
                $this->ci->db->trans_commit();
            }
            return array('code' => '200', 'msg' => $msg);
        }
    }

    /*
    *订单确认收货
    */
    function confirmReceive($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end


        $result = $this->ci->order_model->selectOrder('id,operation_id,order_name,channel,sync_status,is_enterprise,order_region,order_type', array('order_name' => $params['order_name'], 'uid' => $uid));
        if (empty($result)) {
            return array("code" => "300", "msg" => '操作失败');
        }
        $order_id = $result['id'];
        $operation_id = $result['operation_id'];

        if ($operation_id != 2 && $operation_id != 6) {
            return array("code" => "300", "msg" => '您的订单状态不是已发货或已妥投，不能确认收货');
        }

        //事物开启
        $this->ci->db->trans_begin();

        $op_log['operation_msg'] = "确认收货";
        $op_log['uid'] = $uid;
        $op_log['order_id'] = $order_id;
        $update_data = array(
            'operation_id' => '9',
            'last_modify' => time()
        );
        $where = array('id' => $order_id);
        if (!$this->ci->order_model->update_order($update_data, $where, $op_log)) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => '订单确认收货失败，请重新操作');
        }

        //同步OMS
        if ($result['order_type'] == 3 || $result['order_type'] == 4) {
            $this->ci->load->bll('pool/o2o/order', null, 'bll_pool_o2o_order');
            $this->ci->load->model('o2o_child_order_model');
            $childOrders = $this->ci->o2o_child_order_model->get_child_orders_by_parent_id($result['id']);
            foreach ($childOrders as $value) {
                $this->ci->o2o_child_order_model->update(array('operation_id' => 9), array('id' => $value['id']));
                $poolrs = $this->ci->bll_pool_o2o_order->pool_confirm($value['order_name'], $value['sync_status']);
                if ($poolrs['result'] != '1') {
                    $this->ci->db->trans_rollback();
                    return array('code' => 300, 'msg' => $poolrs['msg']);
                }
            }

        } else {
            //oms todo
            $this->ci->load->bll('pool');
            $poolrs = $this->ci->bll_pool->pool_order_confirm($result['order_name'], $result['channel'], $result['sync_status'], $result['is_enterprise'], $result['order_type']);
            if ($poolrs['status'] != 'succ') {
                $this->ci->db->trans_rollback();
                return array('code' => 300, 'msg' => $poolrs['msg']);
            }
        }

        $survey_url = '';
        if ($result['order_region'] == 1) {
            if ($this->ci->order_model->ckDistSurvey($params['order_name'])) {
                //$survey_unique = $this->getSurveyUnique($uid, $params['order_name']);
                //$survey_url = 'http://www.fruitday.com/survey/distribute/' . $survey_unique;
            }
        }
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array('code' => '300', 'msg' => '订单取消失败');
        } else {
            $this->ci->db->trans_commit();
        }
        return array("code" => "200", "msg" => 'succ', 'survey_url' => $survey_url);
    }

    function getSurveyUnique($uid, $ordername) {
        $uid = base_convert($uid, 10, 36);
        $ordername = base_convert($ordername, 10, 36);
        return urlencode(base64_encode($uid . "#" . $ordername));
    }


    /*
    *订单支付完成
    */
    function orderPayed($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end


//         $result = $this->ci->order_model->selectOrder('id,pay_status,money',array('order_name'=>$params['order_name']));
//         if($result['pay_status']!='0'){
//             $this->ifhuchi_msg($uid,$result['id']);
// //            $cherry_result = $this->cherry_active($uid,$result['id'],$result['money']);
// //             if($cherry_result['code']!="200"){//cherry_active
// //                 return $cherry_result;
// //             }else{
// //                return array("code"=>"200","msg"=>'succ');//todo 200
// //             }

//             $cherry_result1 = $this->cherry_active1($uid,$result['id']);
//             if($cherry_result1['code']!="200"){//cherry_active1
//                 return $cherry_result1;
//             }else{
//                 return array("code"=>"200","msg"=>'succ');
//             }
//         }

        $update_data = array(
            'pay_status' => '2'
        );
        $where = array("order_name" => $params['order_name'], 'pay_status' => '0', 'operation_id' => '0');

        //充值
        $is_trade = substr($params['order_name'], 0, 1);
        if($is_trade != 'T')
        {
            $this->ci->order_model->update_order($update_data, $where);
        }

        /*余额改造 start*/
//        $this->ci->load->model('trade_model');
//        $trade = $this->ci->trade_model->dump(array('order_name' => $params['order_name'],'type'=>'outlay'));
//        if(empty($trade))
//        {
//            $order = $this->ci->order_model->dump(array('order_name' => $params['order_name']));
//            $use_money_deduction = $order['use_money_deduction'];
//            if($use_money_deduction >0)
//            {
//                $this->ci->load->bll('user',null,'bll_user');
//                $this->ci->bll_user->deposit_charge($order['uid'],$use_money_deduction, "支出涉及订单号{$order['order_name']}",$order['order_name']);
//            }
//        }
        /*余额改造 end*/

        return array("code" => "200", "msg" => 'succ');

//         else{
//             $this->ifhuchi_msg($uid,$result['id']);
// //            $cherry_result = $this->cherry_active($uid,$result['id'],$result['money']);
// //             if($cherry_result['code']!="200"){
// //                 return $cherry_result;
// //             }else{
// //                return array("code"=>"200","msg"=>'succ');//todo 200
// //             }
//             $cherry_result1 = $this->cherry_active1($uid,$result['id']);
//             if($cherry_result1['code']!="200"){
//                 return $cherry_result1;
//             }else{
//                 return array("code"=>"200","msg"=>'succ');
//             }
//         }
    }

    //设置赠品类型
    private function _setProductType() {
        $order_pro_type_list = $this->ci->config->item('order_product_type');
        $giftTypeList = array();
        foreach ($order_pro_type_list as $key => $value) {
            if (false === strrpos($key, 'gift')) {
                continue;
            }
            $giftTypeList[] = $value;
        }
        return $giftTypeList;
    }

    private function _setOperation($input_order_status) {
        $order_status_filter = array();
        switch ($input_order_status) {
            case '0':
                # code...
                break;
            case '1':
                $order_status_filter = array('0', '1', '2', '4', '6', '7', '8', '9');
                break;
            case '2':
                $order_status_filter = array('3');
                break;
            case '3':
                $order_status_filter = array('5');
                break;
            case '4':
                $order_status_filter = array('3', '9');
            default:
                # code...
                break;
        }
        return $order_status_filter;
    }

    private function _initOrderList($result, $params = array()) {
        $pay = $this->ci->config->item('pay');
        $operation = $this->ci->config->item('operation');
        $operation[0] = '待发货';
        $operation[1] = '待发货';
        $operation[4] = '待发货';

        if (empty($result)) {
            return array();
        }
        $orderids = array_column($result, 'id');
        $fields = 'product.thum_photo, product.template_id,
                        order_product.id as order_product_id,order_product.product_name,order_product.product_id,
                        order_product.gg_name,order_product.price,order_product.qty,order_product.type order_product_type,
                        order_product.product_no,order_product.order_id';
        $where_in[] = array(
            'key' => 'order_product.order_id',
            'value' => $orderids,
        );
        $join[] = array('table' => 'product', 'field' => 'product.id=order_product.product_id', 'type' => 'left');
        $order_products_res = $this->ci->order_model->selectOrderProducts($fields, '', $where_in, '', $join);

        $order_product_data = array();
        $giftTypeList = $this->_setProductType();
        foreach ($result as $rk => $rv) {
            $show_ids[$rv['id']] = $rv;
        }
        foreach ($order_products_res as &$val) {
            // 获取产品模板图片
            if ($val['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($val['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $val['thum_photo'] = $templateImages['main']['thumb'];
                }
            }

            $qua = $this->ci->quality_complaints_model->selectQualitys('*', array('ordername' => $show_ids[$val['order_id']]['order_name'], 'product_id' => $val['product_id']));

            if (count($qua[0])) {
                $val['complain'] = $qua[0]['description'];
            }
            $val['thum_photo'] = empty($val['thum_photo']) ? '' : PIC_URL . $val['thum_photo'];
            $val['product_type'] = in_array($val['order_product_type'], $giftTypeList) ? 3 : 1;

            $pcomment_where = array('product_id' => $val['product_id'], 'order_id' => $val['order_id']);
            $pcomment = $this->ci->comment_model->selectComments('id', $pcomment_where);
            if (count($pcomment[0])) {
                $val['gcomment'] = $pcomment[0]['id'];
            }
            $order_product_data[$val['order_id']][] = $val;
        }

        $order_ids = array();
        foreach ($result as $key => $value) {
            if (!isset($order_product_data[$value['id']])) {
                unset($result[$key]);
                continue;
            }

            $result[$key]['pay_status'] = $pay[$value['pay_status']];
            $result[$key]['order_status'] = $operation[$value['operation_id']];
            $result[$key]['pay_status_key'] = $value['pay_status'];
            $result[$key]['order_status_key'] = $value['order_status'];  //订单状态

            if(!empty($value['fp']) && empty($value['invoice_province']))
            {
                $result[$key]['invoice_address'] = $value['address'];
                $result[$key]['invoice_mobile'] = substr_replace($value['mobile'], '****', 3, 4);
                $result[$key]['invoice_username'] = $value['name'];
            }
            else
            {
                $result[$key]['invoice_address'] = $value['invoice_province'] . $value['invoice_city'] . $value['invoice_area'] . $value['invoice_address'];
                $result[$key]['invoice_mobile'] = substr_replace($value['invoice_mobile'], '****', 3, 4);
                $result[$key]['invoice_username'] = $value['invoice_username'];
            }
            //$result[$key]['invoice_address'] = $value['invoice_province'] . $value['invoice_city'] . $value['invoice_area'] . $value['invoice_address'];
            //$result[$key]['kp_type'] = $value['kp_type'] == 1 ? '水果' : '食品';
            if($value['kp_type'] == 1 || $value['order_kp_type'] == 1)
            {
                $result[$key]['kp_type'] = '明细';
            }
            else if($value['kp_type'] == 2 || $value['order_kp_type'] == 2)
            {
                $result[$key]['kp_type'] = '食品';
            }
            else if($value['kp_type'] == 3 || $value['order_kp_type'] == 3)
            {
                $result[$key]['kp_type'] = '明细';
            }
            else if($value['kp_type'] == 4 || $value['order_kp_type'] == 4)
            {
                $result[$key]['kp_type'] = '商品大类';
            }
            else
            {
                $result[$key]['kp_type'] = '明细';
            }

            //comment
            $evl = $this->ci->evaluation_model->get_info($value['uid'], $value['order_name']);
            if (($value['operation_id'] != 3 && $value['operation_id'] != 9) || $value['had_comment'] == '1' || ($value['time'] < date("Y-m-d", strtotime('-' . $this->can_comment_period)))) {
                if (empty($evl) && ($value['operation_id'] == 3 || $value['operation_id'] == 6 || $value['operation_id'] == 9) && strcmp($params['version'], '3.4.0') >= 0 && $value['time'] > date("Y-m-d", strtotime('-' . $this->can_comment_period))) {
                    $result[$key]['can_comment'] = true;
                } else {
                    $result[$key]['can_comment'] = false;
                }
            } else {
                if(count($order_product_data[$value['id']]) == 1 && $order_product_data[$value['id']][0]['product_type'] == 3)
                {
                    $result[$key]['can_comment'] = true;
                }
                else
                {
                    $result[$key]['can_comment'] = true;
                }
            }
            //confirm 收货
            if ($value['operation_id'] == 2) {
                $result[$key]['can_confirm_receive'] = 'true';
            } else {
                $result[$key]['can_confirm_receive'] = 'false';
            }

            //can pay
            if( isset($params['version']) && strcmp($params['version'], '3.4.0') >= 0 ){
                $online_pay = array(1, 2, 3, 5, 7, 8, 9, 11,12,13,15);  //add 余额支付，apply pay
            }
            else
            {
                $online_pay = array(1, 2, 3, 7, 8, 9 ,12,13,15);
            }

            if ($value['pay_status'] == '0' && in_array($value['pay_parent_id'], $online_pay) && $value['operation_id'] == '0') {
                $result[$key]['can_pay'] = 'true';
            } else {
                $result[$key]['can_pay'] = 'false';
            }
            //can cancle
            //天天到家订单逻辑不变
            if (in_array($value['order_type'], array(3, 4))) {
                if ($value['operation_id'] == '0' || $value['operation_id'] == '1') {
                    //if ($value['pay_parent_id'] == '4' || $value['pay_parent_id'] == '5' || $value['pay_status'] == '0') {
                        if ($value['sync_status'] != '0') {
                            if(version_compare($params['version'], '5.7.0') >= 0)
                            {
                                $result[$key]['can_cancel'] = 'true';
                            }
                            else
                            {
                                $result[$key]['can_cancel'] = 'false';
                            }
                        }
                        else
                        {
                            $result[$key]['can_cancel'] = 'true';
                        }
//                    } else {
//                        $result[$key]['can_cancel'] = 'false';
//                    }
                } else {
                    $result[$key]['can_cancel'] = 'false';
                }
            } else {
                if ($value['operation_id'] == '0' || $value['operation_id'] == '1') {
                    if ($value['time'] >= date('Y-m-d', strtotime('-2 months'))) {
                        $result[$key]['can_cancel'] = 'true';
                    } else {
                        $result[$key]['can_cancel'] = 'false';
                    }
                } else {
                    $result[$key]['can_cancel'] = 'false';
                }
                // if($value['operation_id']=='0'){
                //     if($value['pay_parent_id']=='4' || $value['pay_parent_id']=='5' || $value['pay_status']=='0'){
                //         if($value['sync_status']!='0'){
                //             $result[$key]['can_cancel'] = 'false';
                //         }else{
                //             $result[$key]['can_cancel'] = 'true';
                //         }
                //     }else{
                //         $result[$key]['can_cancel'] = 'false';
                //     }
                // }else{
                //     $result[$key]['can_cancel'] = 'false';
                // }
            }

            $result[$key]['money'] = number_format((float)($value['money'] + $value['use_money_deduction']),2,'.','');

            //隐藏价格
            if ($value['sheet_show_price'] != '1' && $value['pay_parent_id'] == '6') {
                foreach ($order_product_data[$value['id']] as $tk => $tv) {
                    $order_product_data[$value['id']][$tk]['price'] = "0.00";
                }
                $result[$key]['money'] = "0.00";
                $result[$key]['goods_money'] = "0.00";
            }

            //set product
            $result[$key]['item'] = $order_product_data[$value['id']];

            //特殊处理-app
            foreach($result[$key]['item'] as $k=>$v)
            {
                if(strpos($v['gg_name'],'/') !== false)
                {
                    $g_name = explode('/',$v['gg_name']);
                    $result[$key]['item'][$k]['gg_name'] = $g_name[0];
                }
            }
            $order_ids[] = $value['id'];
            $result[$key]['can_check_logistic'] = 0;

            $has_refund = $this->ci->db->select('has_refund')->from('ttgy_order_refund')->where(array('order_id'=>$value['id']))->get()->row_array();
            $result[$key]['has_refund'] = $has_refund['has_refund']?1:0;

            if($result[$key]['order_type'] == 1 && $result[$key]['pay_status_key'] == 1 )
            {
                $result[$key]['can_replace'] = 0;
            }
            else
            {
                $result[$key]['can_replace'] = 0;
            }
            if($result[$key]['sync_status'] == 1 && in_array($result[$key]['operation_id'], array(1,2,3,4,6,9))){
                $result[$key]['can_check_logistic'] = 1;
            }else{
                $result[$key]['can_check_logistic'] = 0;
            }

        }
        /*物流查询srart*/
        // $logistic_info = $this->ci->order_model->getLogisticInfoList($order_ids);
        // $logistic_id_arr = array();
        // foreach ($logistic_info as $key => $value) {
        //     $logistic_id_arr[$value['id']] = $value['id'];
        // }
        // foreach ($result as $key => $value) {
        //     if (isset($logistic_id_arr[$value['id']])) {
        //         $result[$key]['can_check_logistic'] = 1;
        //     }
        // }
        /*物流查询end*/
        return $result;
    }

    /*赠品订单金额限制验证*/
    private function check_gift_money_limit($cart_info, $order_money) {
        foreach ($cart_info['products'] as $key => $value) {
            if (($value['product_id'] == 9955 || $value['product_id'] == 9787) && $order_money < $value['order_money_limit']) {
                return "订单实付金额必须满" . $value['order_money_limit'] . "元才能使用包邮卡哦么么哒";
            }
            if ($value['type'] == 'user_gift' && $value['order_money_limit'] > 0 && $order_money < $value['order_money_limit']) {
                return "购物金额须满" . $value['order_money_limit'] . "才能带走您的赠品";
            }
        }
        return false;
    }


    /**
     * 合并开票
     * @return array
     */
    function useUnitedInvoice($params){
        $this->ci->load->bll('rpc/request');
        $this->ci->load->model('subsequent_invoice_model');
        $this->ci->load->model('subsequent_invoice_order_model');
        $this->ci->load->model('user_model');
        //必要参数验证start
        if (isset($params['fp_dz']) && !empty($params['fp_dz'])) {
            $required_fields = array(
                'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
                'order_name_group'=> array('required' => array('code' => '300', 'msg' => '订单编号不能为空')),
                'fp' => array('required' => array('code' => '300', 'msg' => '发票抬头不能为空')),
                'fp_dz' => array('required' => array('code' => '300', 'msg' => '地址不能为空')),
                'invoice_username' => array('required' => array('code' => '300', 'msg' => '收件人不能为空')),
                'invoice_mobile' => array('required' => array('code' => '300', 'msg' => '手机不能为空')),
                'invoice_province' => array('required' => array('code' => '300', 'msg' => '地区不能为空')),
                'invoice_city' => array('required' => array('code' => '300', 'msg' => '地区不能为空')),
                'invoice_area' => array('required' => array('code' => '300', 'msg' => '地区不能为空')),
                'area_adcode' => array('required' => array('code' => '500', 'msg' => 'area_adcode can not be null')),
//                'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
//                'delivery_code' => array('required' => array('code' => '500', 'msg' => 'delivery_code can not be null')),
                'kp_type' => array('required' => array('code' => '500', 'msg' => 'kp_type can not be null')),
            );
        } else {
            $required_fields = array(
                'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
                'order_name_group'=> array('required' => array('code' => '300', 'msg' => '订单编号不能为空')),
                'fp' => array('required' => array('code' => '300', 'msg' => '发票抬头不能为空')),
            );
        }
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $orderNameGroup = json_decode($params['order_name_group'], true);

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        //事务开始

        $pay_info = $this->ci->b2o_parent_order_model->selectOrder('pay_parent_id,pay_id', array('id' => $order_id));
        $has_invoice = $this->ci->b2o_parent_order_model->has_invoice($pay_info['pay_parent_id'], $pay_info['pay_id']);
        if ($has_invoice == 0) {
            return array("code" => "300", "msg" => "您选择的支付方式不支持开发票");
        }

        if ($params['fp_dz'] == '使用收货地址') {
            $params['fp_dz'] = '';
        }

        $fp = trim($params['fp']);
        if(empty($fp)){
            return array('code'=>'300', 'msg'=>'抬头不能为空');
        }

        if(preg_match("/[\'.,:;*?~`!@#$%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$_GET['word'])){  //不允许特殊字符
            return array('code'=>'300', 'msg'=>'抬头不能包含符号');
        }

        $fp = isset($params['fp']) ? addslashes($params["fp"]) : '';
        if ($fp && mb_strlen($fp,'utf8') > 50) {
            return array("code" => "300", "msg" => "发票抬头请不要大于50个字");
        }

        $fp_id_no = trim(addslashes($params['fp_id_no']));

        if($fp != '个人' && $fp_id_no !=='0'){
            if(empty($fp_id_no)){
                return array('code' => '300', 'msg' => '公司发票必须填写纳税人识别号');
            }

            if(preg_match('/^[A-Za-z0-9]{15}$|^[A-Za-z0-9]{18}$|^[A-Za-z0-9]{20}$/', $fp_id_no) == 0){
                return array("code"=>'300', 'msg'=>"请输入合法的纳税人识别号");
            }
        }

        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_INVOICE_DETAIL_URL, array('nos' => $orderNameGroup), 'POST', 20);
        $invoiceMoney = 0;
        foreach($rs['list'] as $info){
            if($info['enable'] == 0){
                return array('code'=>300, 'msg' => '订单:'.$info['orderNo'].'无法开票,'.$info['msg']);
            }
            $invoiceMoney += $info['amt'];
        }

        $this->ci->db->trans_begin();
        $now = date('Y-m-d H:i:s', time());
        if($params['invoice_type'] == 1){
            //电子发票
            $einvoice_mobile = $params['einvoice_mobile'];
            if(empty($einvoice_mobile)){
                $info = $this->ci->user_model->dump(array('id'=>$uid), 'mobile');
                $einvoice_mobile = $info['einvoice_mobile'];
            }

            $invoiceId = $this->ci->subsequent_invoice_model->insert(array('uid' => $uid, 'money' => $invoiceMoney, 'fp' => $fp, 'fp_id_no' => $fp_id_no, 'mobile' => $einvoice_mobile, 'type' => 1, 'kp_type' => $params['kp_type'], 'create_time' => $now, 'pay_status' => $invoiceMoney >= 500 ? 1 : 0));
            $this->ci->load->model('order_einvoices_model');
            foreach($orderNameGroup as $order_name){
                $this->ci->subsequent_invoice_order_model->insert(array('invoice_id' => $invoiceId, 'order_name' => $order_name));
            }
        } else {
            if (isset($params['fp_dz']) && !empty($params['fp_dz'])) {
                $fp_dz = isset($params['fp_dz']) ? addslashes($params["fp_dz"]) : '';
                $fp_name = isset($params['invoice_username']) ? addslashes($params["invoice_username"]) : '';
                $fp_mobile = isset($params['invoice_mobile']) ? addslashes($params["invoice_mobile"]) : '';
                $fp_province = isset($params['invoice_province']) ? addslashes($params["invoice_province"]) : '';
                $fp_city = isset($params['invoice_city']) ? addslashes($params["invoice_city"]) : '';
                $fp_area = isset($params['invoice_area']) ? addslashes($params["invoice_area"]) : '';
                $fp_kp = $params['kp_type'];
                if (!is_mobile($fp_mobile)) {
                    return array('code' => '300', 'msg' => '手机号码格式错误');
                }

                $invoiceId = $this->ci->subsequent_invoice_model->insert(array('uid' => $uid, 'money' => $invoiceMoney, 'username' => $fp_name, 'mobile' => $params['invoice_mobile'], 'fp' => $fp, 'fp_id_no' => $fp_id_no, 'fp_dz' => $fp_dz, 'province' => $fp_province, 'city' => $fp_city, 'area' => $fp_area, 'kp_type' => $fp_kp, 'type' => 2, 'create_time' => $now, 'pay_status' => $invoiceMoney >= 500 ? 1 : 0));

                $this->ci->load->model('order_invoice_model');
                foreach($orderNameGroup as $order_name){
                    $this->ci->subsequent_invoice_order_model->insert(array('invoice_id' => $invoiceId, 'order_name' => $order_name));
                }

            }
        }

        //$this->ci->db->trans_commit();
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array('code' => 300, 'msg' => '提交失败，请重新提交!');
        } else {
            $this->ci->db->trans_commit();
        }

        if(empty($params['area_adcode'])){
            $params['area_adcode'] = 310110;
        }

        $data['invoiceId'] = $invoiceId;
        return array('code' => '200', 'msg' => '', 'data' => $data);
    }


    function cancelUnitInvoice($params){
        $this->ci->load->model('order_express_model');
        $this->ci->load->model('subsequent_invoice_model');

        $required_fields = array(
            'invoice_id' => array('required' => array('code' => '500', 'msg' => 'invoice_id can not be null')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $invoiceInfo = $this->ci->subsequent_invoice_model->dump(array('id'=>$params['invoice_id']));
        if($invoiceInfo['sync_status'] != 0 ){
            return array('code' => 300, 'msg' => '已同步发票无法取消');
        }

        $result = $this->ci->order_express_model->dump(array('invoice_id' => $params['invoice_id'], 'order_type' => 21), 'id,order_name,channel,sync_status,order_type');
        if(!empty($result) && $result['sync_status'] == 1){
            $this->ci->load->bll('pool');
            $poolrs = $this->ci->bll_pool->pool_order_cancel($result['order_name'], $result['channel'], $result['sync_status'], 0, $result['order_type']);
            if ($poolrs['status'] != 'succ') {
                return array('code' => 300, 'msg' =>$poolrs['msg']);
            } else {
                $this->ci->subsequent_invoice_model->update(array('is_canceled'=>1), array('id'=>$params['invoice_id']));
                $this->ci->order_express_model->update(array('operation_id'=>5), array('id'=>$result['id']));
                return array('code'=>200 , 'msg'=>'','data'=>array());
            }
        } else {
            $this->ci->subsequent_invoice_model->update(array('is_canceled'=>1), array('id'=>$params['invoice_id']));
            $this->ci->order_express_model->update(array('operation_id'=>5), array('id'=>$result['id']));
            return array('code'=>200 , 'msg'=>'','data'=>array());
        }
    }


    function cancelVatInvoice($params){
        $this->ci->load->model('order_express_model');
        $this->ci->load->model('vat_invoice_model');

        $required_fields = array(
            'invoiceId' => array('required' => array('code' => '500', 'msg' => 'invoiceId can not be null')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        $invoiceInfo = $this->ci->vat_invoice_model->dump(array('id'=>$params['invoiceId']));
        if($invoiceInfo['sync_status'] != 0 ){
            return array('code' => 300, 'msg' => '已同步发票无法取消');
        }

        $result = $this->ci->order_express_model->dump(array('invoice_id' => $params['invoiceId'], 'order_type' => 22), 'id,order_name,channel,sync_status,order_type');
        if(!empty($result) && $result['sync_status'] == 1){
            $this->ci->load->bll('pool');
            $poolrs = $this->ci->bll_pool->pool_order_cancel($result['order_name'], $result['channel'], $result['sync_status'], 0, $result['order_type']);
            if ($poolrs['status'] != 'succ') {
                return array('code' => 300, 'msg' =>$poolrs['msg']);
            } else {
                $this->ci->vat_invoice_model->update(array('is_canceled'=>1), array('id'=>$params['invoiceId']));
                $this->ci->order_express_model->update(array('operation_id'=>5), array('id'=>$result['id']));
                return array('code'=>200 , 'msg'=>'','data'=>array());
            }
        } else {
            $this->ci->vat_invoice_model->update(array('is_canceled'=>1), array('id'=>$params['invoiceId']));
            $this->ci->order_express_model->update(array('operation_id'=>5), array('id'=>$result['id']));
            return array('code'=>200 , 'msg'=>'','data'=>array());
        }
    }



    /*
    *索取发票
    */
    function useInvoice($params) {
        //必要参数验证start
        if (isset($params['fp_dz']) && !empty($params['fp_dz'])) {
            $required_fields = array(
                'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
                'fp' => array('required' => array('code' => '300', 'msg' => '发票抬头不能为空')),
                'fp_dz' => array('required' => array('code' => '300', 'msg' => '地址不能为空')),
                'invoice_username' => array('required' => array('code' => '300', 'msg' => '收件人不能为空')),
                'invoice_mobile' => array('required' => array('code' => '300', 'msg' => '手机不能为空')),
                'invoice_province' => array('required' => array('code' => '300', 'msg' => '地区不能为空')),
                'invoice_city' => array('required' => array('code' => '300', 'msg' => '地区不能为空')),
                'invoice_area' => array('required' => array('code' => '300', 'msg' => '地区不能为空')),
                'area_adcode' => array('required' => array('code' => '500', 'msg' => 'area_adcode can not be null')),
                'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
                'delivery_code' => array('required' => array('code' => '500', 'msg' => 'delivery_code can not be null')),
                'kp_type' => array('required' => array('code' => '500', 'msg' => 'kp_type can not be null')),
            );
        } else {
            $required_fields = array(
                'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
                'fp' => array('required' => array('code' => '300', 'msg' => '发票抬头不能为空')),
            );
        }
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        //事务开始

        $pay_info = $this->ci->b2o_parent_order_model->selectOrder('pay_parent_id,pay_id', array('id' => $order_id));
        $has_invoice = $this->ci->b2o_parent_order_model->has_invoice($pay_info['pay_parent_id'], $pay_info['pay_id']);
        if ($has_invoice == 0) {
            return array("code" => "300", "msg" => "您选择的支付方式不支持开发票");
        }

        if ($params['fp_dz'] == '使用收货地址') {
            $params['fp_dz'] = '';
        }

        $fp = isset($params['fp']) ? addslashes($params["fp"]) : '';
        if ($fp && mb_strlen($fp,'utf8') > 50) {
            return array("code" => "300", "msg" => "发票抬头请不要大于50个字。");
        }
        $fp_id_no = isset($params['fp_id_no']) ? addslashes($params['fp_id_no']) : '';
        $fp_id_no = trim($fp_id_no);

        if($fp != '个人' && $fp_id_no !=='0'){
            if(version_compare($params['version'], '5.3.0') <= 0){
                return array('code'=>'900', 'msg'=>'国家规定2017年7月1日起，发票抬头为公司须填写纳税人识别号，请更新升级app');
            }

            if(empty($fp_id_no)){
                return array('code' => '300', 'msg' => '公司发票必须填写纳税人识别号');
            }

            if(preg_match('/^[A-Za-z0-9]{15}$|^[A-Za-z0-9]{18}$|^[A-Za-z0-9]{20}$/', $fp_id_no) == 0){
                return array("code"=>'300', 'msg'=>"请输入合法的纳税人识别号");
            }
        }

        $order_info = $this->b2oOrderDetails($uid, $params['source'], $params['device_id'],$params);
        $this->ci->db->trans_begin();
        if($params['invoice_type'] == 1)
        {
            $fp_kp = $params['kp_type'];
            //电子发票
            $data = array(
                'fp' => $fp,
                'fp_id_no' => $fp_id_no,
                'kp_type'=>$fp_kp,
                'fp_dz' => '',
                'invoice_money' => 0,
            );
            $this->ci->b2o_parent_order_model->delete_order_invoice($order_id);
            $this->ci->b2o_parent_order_model->update_order($data, array('id' => $order_id));

            $this->ci->load->model('order_einvoices_model');
            $this->ci->order_einvoices_model->insert(array('dfp' => $fp, 'fp_id_no' => $fp_id_no, 'order_name' => $order_info['order_name'], 'mobile' => $params['invoice_mobile']));
        } else {
            if (isset($params['fp_dz']) && !empty($params['fp_dz'])) {
                $fp_dz = isset($params['fp_dz']) ? addslashes($params["fp_dz"]) : '';
                $fp_name = isset($params['invoice_username']) ? addslashes($params["invoice_username"]) : '';
                $fp_mobile = isset($params['invoice_mobile']) ? addslashes($params["invoice_mobile"]) : '';
                $fp_province = isset($params['invoice_province']) ? addslashes($params["invoice_province"]) : '';
                $fp_city = isset($params['invoice_city']) ? addslashes($params["invoice_city"]) : '';
                $fp_area = isset($params['invoice_area']) ? addslashes($params["invoice_area"]) : '';
                $fp_kp = $params['kp_type'];
                if (!is_mobile($fp_mobile)) {
                    return array('code' => '300', 'msg' => '手机号码格式错误');
                }

                $data = array(
                    'fp' => $fp,
                    'fp_id_no' => $fp_id_no,
                    'fp_dz' => $fp_dz,
                    'invoice_money' => 5,
                );
                $this->ci->b2o_parent_order_model->update_order($data, array('id' => $order_id));

                $fp_info['fp'] = $fp;
                $fp_info['fp_id_no'] = $fp_id_no;
                $fp_info['fp_dz'] = $fp_dz;
                $fp_info['fp_name'] = $fp_name;
                $fp_info['fp_mobile'] = $fp_mobile;
                $fp_info['fp_province'] = $fp_province;
                $fp_info['fp_city'] = $fp_city;
                $fp_info['fp_area'] = $fp_area;
                $fp_info['fp_kp'] = $fp_kp;
                $this->ci->b2o_parent_order_model->add_order_invoice($order_id, $fp_info);
            } else {
                $fp_kp = $params['kp_type'];
                $data = array(
                    'fp' => $fp,
                    'fp_id_no' => $fp_id_no,
                    'kp_type'=>$fp_kp,
                    'fp_dz' => '',
                    'invoice_money' => 0,
                );
                $this->ci->b2o_parent_order_model->update_order($data, array('id' => $order_id));
                $this->ci->b2o_parent_order_model->delete_order_invoice($order_id);
            }
        }

        //$this->ci->db->trans_commit();
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array('code' => 300, 'msg' => '提交失败，请重新提交!');
        } else {
            $this->ci->db->trans_commit();
        }

        $order_info = $this->b2oOrderDetails($uid, $params['source'], $params['device_id'],$params);
        $order_info['invoice_type'] = $params['invoice_type'];

        //特殊处理
//        if($params['source'] == 'app')
//        {
//            $order_info['goods_money'] = $order_info['total_amount_money'];
//            $order_info['pay_discount'] = '0.00';
//        }

        return array('code' => '200', 'msg' => 'succ','data'=>$order_info);
    }

    /*
    *索取充值单发票
    */
    function useTradeInvoice($params) {
        //必要参数验证start
        if (isset($params['fp_dz']) && !empty($params['fp_dz'])) {
            $required_fields = array(
                'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
                'fp' => array('required' => array('code' => '300', 'msg' => '发票抬头不能为空')),
                'fp_dz' => array('required' => array('code' => '300', 'msg' => '地址不能为空')),
                'invoice_username' => array('required' => array('code' => '300', 'msg' => '收件人不能为空')),
                'invoice_mobile' => array('required' => array('code' => '300', 'msg' => '手机不能为空')),
                'invoice_province' => array('required' => array('code' => '300', 'msg' => '地区不能为空')),
                'invoice_city' => array('required' => array('code' => '300', 'msg' => '地区不能为空')),
                'invoice_area' => array('required' => array('code' => '300', 'msg' => '地区不能为空')),
                //'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
                'trade_number' => array('required' => array('code' => '500', 'msg' => 'trade_number can not be null')),
                'kp_type' => array('required' => array('code' => '500', 'msg' => 'kp_type can not be null')),
            );
        } else {
            $required_fields = array(
                'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
                'fp' => array('required' => array('code' => '300', 'msg' => '发票抬头不能为空')),
            );
        }
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end


        if ($params['fp_dz'] == '使用收货地址') {
            $params['fp_dz'] = '';
        }

        $fp = isset($params['fp']) ? addslashes($params["fp"]) : '';
        if ($fp && mb_strlen($fp,'utf8') > 20) {
            return array("code" => "300", "msg" => "发票抬头请不要大于20个字。");
        }

        if (strpos($params['trade_number'], ',')) {
            $trade_number = explode(',', $params['trade_number']);
        }

        $this->ci->db->trans_begin();
        if (isset($params['fp_dz']) && !empty($params['fp_dz'])) {
            if (is_array($trade_number) && count($trade_number)) {
                $pdata['invoice']['trades'] = $trade_number;
            } else {
                $pdata['invoice']['trades'][0] = $params['trade_number'];
            }
            $smoney = $this->ci->trade_invoice_model->checkTotalMoney($pdata['invoice']['trades']);
            if ($smoney < 10) {
                return array("code" => "300", "msg" => "总金额小于10元 。");
            }


            if($fp != '个人' && $params['fp_id_no'] !=='0'){
                if(version_compare($params['version'], '5.3.0') <= 0){
                    return array('code'=>'900', 'msg'=>'国家规定2017年7月1日起，发票抬头为公司须填写纳税人识别号，请更新升级app');
                }

                if(empty($params['fp_id_no'])){
                    return array('code' => '300', 'msg' => '公司发票必须填写纳税人识别号');
                }

                if(preg_match('/^[A-Za-z0-9]{15}$|^[A-Za-z0-9]{18}$|^[A-Za-z0-9]{20}$/', $params['fp_id_no']) == 0){
                    return array("code"=>'300', 'msg'=>"请输入合法的纳税人识别号");
                }
            }

            $pdata['invoice']['name'] = $params['fp'];
            $pdata['invoice']['fp_id_no'] = $params['fp_id_no'];
            $pdata['invoice']['username'] = $params['invoice_username'];
            $pdata['invoice']['mobile'] = $params['invoice_mobile'];
            $pdata['invoice']['address'] = $params['fp_dz'];
            $pdata['province'] = $params['invoice_province'];
            $pdata['city'] = $params['invoice_city'];
            $pdata['area'] = $params['invoice_area'];
            $invoice = $pdata['invoice'];

            $setted = $this->ci->trade_invoice_model->setTradeAndAmount(
                $this->ci->trade_invoice_model->getTransactionByTrade(
                    isset($invoice['trades']) ?
                        $invoice['trades'] : ""
                )
            );

            $succ = $this->ci->trade_invoice_model->save(
                $uid,
                $pdata,
                $params
            );

            $this->ci->trade_invoice_model->updateTransaction(
                isset($invoice['trades']) ?
                    $invoice['trades'] : "",
                array(
                    "invoice" => isset($succ['invoice']) ? $succ['invoice'] : ""
                )
            );
        }

        //$this->ci->db->trans_commit();
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array('code' => 300, 'msg' => '提交失败，请重新提交!');
        } else {
            $this->ci->db->trans_commit();
        }

        if ($succ) {
            return array('code' => '200', 'msg' => "发票申请成功");
        }
        return array('code' => '200', 'msg' => "发票申请失败");
    }

    /*
    *取消索取发票
    */
    function cancelInvoice($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'area_adcode' => array('required' => array('code' => '500', 'msg' => 'area_adcode can not be null')),
            'store_id_list' => array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
            'delivery_code' => array('required' => array('code' => '500', 'msg' => 'delivery_code can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end
        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        $data = array(
            'fp' => '',
            'fp_dz' => '',
            'invoice_money' => 0,
        );
        $where = array(
            'id' => $order_id
        );
        $this->ci->b2o_parent_order_model->update_order($data, $where);
        $this->ci->b2o_parent_order_model->delete_order_invoice($order_id);
        $order_info = $this->b2oOrderDetails($uid, $params['source'], $params['device_id'],$params);

        //删除电子发票
        $rs = $this->ci->b2o_parent_order_model->selectOrder("order_name", array("id" => $order_id));
        $order_name = $rs['order_name'];

        $res = $this->ci->b2o_parent_order_model->getDzFp($order_name);
        if (!empty($res)) {
            $this->ci->b2o_parent_order_model->delete_DzFp($order_name);
        }

        //特殊处理
//        if($params['source'] == 'app')
//        {
//            $order_info['goods_money'] = $order_info['total_amount_money'];
//            $order_info['pay_discount'] = '0.00';
//        }

        return array('code' => '200', 'msg' =>'succ','data'=>$order_info);
    }


    /*
    *发票信息
    */
    function invoiceInfo($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }

        //b2o
        $bs = 'P';
        if($bs == 'P')
        {
            $par = array('connect_id'=>$params['connect_id']);
            return $this->b2oInvoiceInfo($par);
        }

        //获取session信息end
        $order_id = $this->ci->order_model->get_order_id($uid);
        $invoice_info = $this->ci->order_model->get_invoice_info($order_id);

        //添加电子发票
        $order_info = $this->ci->order_model->selectOrder("order_name", array("id" => $order_id));
        $order_name = $order_info['order_name'];
        $dz = $this->ci->order_model->getDzFp($order_name);
        $invoice_info['dz'] = $dz;

        $res = $this->returnMsg($invoice_info);
        return $res;
    }

    /*
    *发票信息
    */
    function b2oInvoiceInfo($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }

        //获取session信息end
        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        $invoice_info = $this->ci->b2o_parent_order_model->get_invoice_info($order_id);

        //添加电子发票
        $order_info = $this->ci->b2o_parent_order_model->selectOrder("order_name", array("id" => $order_id));
        $order_name = $order_info['order_name'];
        if(!empty($order_name))
        {
            $dz = $this->ci->b2o_parent_order_model->getDzFp($order_name);
            $invoice_info['dz'] = $dz;
        }
        else
        {
            $invoice_info['dz'] = array();
        }

        //v5.6.0
        //if($invoice_info['kpTypeName'] == '明细')
        //{
        //    $invoice_info['kp_type'] = 3;
        //}

        $invoice_info['freight'] = '5';   //运费

        $res = $this->returnMsg($invoice_info);
        return $res;
    }

    /*
    *发票抬头列表
    */
    function invoiceTitleList($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end
        $invoice_info = $this->ci->b2o_parent_order_model->get_invoice_title_list($uid);
        $invoice_result = array();
        if (!empty($invoice_info)) {
            foreach ($invoice_info as $key => $value) {
                if ($value['fp'] != '') {
                    $invoice_result[] = $value['fp'];
                }
            }
        }
        return array('code' => '200', 'msg' => 'succ', 'data' => $invoice_result);
    }

    //申诉订单      add by dengjm 2015-11-09
    function complaintsList($params) {
        //检查用户
        $ck_user = $this->get_uid_by_connect_id($params['connect_id']);
        if ($ck_user['code'] != '200') {
            return $ck_user;
        } else {
            $uid = $ck_user['msg'];
        }
        //$uid = 4809831;
        $return_result = $this->ci->order_model->billComplaintsList($params, $uid);
        return $return_result;
    }

    function complaintsListNew($params) {
        //检查用户
        $ck_user = $this->get_uid_by_connect_id($params['connect_id']);
        if ($ck_user['code'] != '200') {
            return $ck_user;
        } else {
            $uid = $ck_user['msg'];
        }
        $return_result = $this->ci->order_model->billComplaintsListNew($params, $uid);
        return $return_result;
    }

    //申诉详情
    function complaintsDetail($params) {
        $ck_user = $this->get_uid_by_connect_id($params['connect_id']);
        if ($ck_user['code'] != '200') {
            return $ck_user;
        } else {
            $uid = $ck_user['msg'];
        }
        $id = $params['id'];
        $return_result = $this->ci->order_model->billComplaintsDetail($id);
        $order_id = $this->ci->order_model->getOrderIdByOrderName($return_result['data']['order_name']);
        $has_refund = $this->ci->db->select('has_refund')->from('ttgy_order_refund')->where(array('order_id'=>$order_id))->get()->row_array();
        if (!empty($has_refund['has_refund'])) {
            $return_result['data']['can_has_refund'] = 1;
        } else {
            $return_result['data']['can_has_refund'] = 0;
        }

        return $return_result;
    }

    //客户评价申诉        2015-12-23 by dengjm
    function complaintsFeedback($params) {
        $ck_user = $this->get_uid_by_connect_id($params['connect_id']);
        if ($ck_user['code'] != '200') {
            return $ck_user;
        } else {
            $uid = $ck_user['msg'];
        }

        if (!is_numeric($params['qcid']) || !is_numeric($params['stars']) || ($params['stars'] == 0)) {
            return array('code' => 300, 'msg' => '传参错误');
        }

        $ffsql = "select oa.mobile from ttgy_order_address as oa inner join ttgy_order as o on o.id=oa.order_id
                  inner join ttgy_quality_complaints as qc on qc.ordername=o.order_name where qc.id=" . $params['qcid'];

        $ffres = $this->ci->db->query($ffsql)->row_array();
        $mobile = $ffres['mobile'];

        $data = array(
            'quality_complaints_id' => $params['qcid'],
            'stars' => $params['stars'],
            'time' => date('Y-m-d H:i:s', time()),
            'mobile' => $mobile,   //收货人的电话
            'user_id' => $uid
        );

        //事务提交数据start
        $this->ci->db->trans_begin();
        $this->ci->db->insert('complaints_feedback', $data);

        $aasql = "update ttgy_quality_complaints set service_status=2 where id=" . $params['qcid'];
        $aares = $this->ci->db->query($aasql);

        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array('code' => 300, 'msg' => '服务器忙忙，请重新提交!');
        } else {
            $this->ci->db->trans_commit();
        }
        //事务提交数据end

        return array('code' => 200, 'msg' => '评价成功');

    }

    function appealList($params) {
        //检查用户
        $ck_user = $this->get_uid_by_connect_id($params['connect_id']);
        if ($ck_user['code'] != '200') {
            return $ck_user;
        } else {
            $uid = $ck_user['msg'];
        }

        $where['uid'] = $uid;
        $where_in[] = array(
            'key' => 'operation_id',
            'value' => array(2, 3, 6, 9),
        );
        $result = $this->ci->order_model->selectOrderList('id,order_name,id,time,shtime,order_type,send_date', $where, $where_in, '', 9999);
        $curr_time = time();
        $appeal_orders = array();
        if (!empty($result)) {
            //剔除无效订单
            foreach ($result as $key => $val) {
                if (in_array($val['order_type'], array(2, 3, 4))) {
                    unset($result[$key]);
                    continue;
                }
                //申诉的条件应该是送货时间往后顺延48小时以内，2到3天的订单取第3天
                // if($val['shtime']=='after2to3days'){
                //     $send_time = date("Y-m-d",strtotime($val['time']));
                //     $can_report_issue_time = date("Y-m-d",strtotime("+6 days" ,strtotime($send_time)));
                // }elseif ($val['shtime'] == 'after1to2days') {
                //     $send_time = date("Y-m-d",strtotime($val['time']));
                //     $can_report_issue_time = date("Y-m-d",strtotime("+5 days" ,strtotime($send_time)));
                // } elseif ($val['stime'] == '2hours') {
                //      $send_time = date("Y-m-d",strtotime($val['time']));
                //     $can_report_issue_time = date("Y-m-d",strtotime("+3 days" ,strtotime($send_time)));
                // }else{
                //      $send_time = date("Y-m-d",strtotime($val['shtime']));
                //      $can_report_issue_time = date("Y-m-d",strtotime("+3 days" ,strtotime($send_time)));
                // }

                $send_time = date("Y-m-d", strtotime($val['send_date']));
                $can_report_issue_time = date("Y-m-d", strtotime("+3 days", strtotime($send_time)));

                if ($curr_time > strtotime($can_report_issue_time)) {
                    unset($result[$key]);
                    continue;
                }
            }
            if (empty($result)) {
                return $appeal_orders;
            }
            $orders = array_column($result, null, 'id');
            $order_names = array_column($result, 'order_name');
            $order_ids = array_column($result, 'id');
            //获取已申诉商品
            $qualitys = array();
            $qualitys_where_in[] = array(
                'key' => 'ordername',
                'value' => $order_names,
            );
            $qualitys_result = $this->ci->quality_complaints_model->selectQualitys('ordername,product_id', '', $qualitys_where_in);
            if (!empty($qualitys_result)) {
                foreach ($qualitys_result as $val) {
                    $qualitys[$val['ordername']][] = $val['product_id'];
                }
            }
            //获取订单商品
            $order_product_where_in[] = array(
                'key' => 'order_id',
                'value' => $order_ids,
            );
            $order_product = $this->ci->order_model->selectOrderProducts('id,order_id,product_id,product_no,product_name,gg_name,price,qty,score,type,total_money', '', $order_product_where_in);
            $giftTypeList = $this->_setProductType();
            foreach ($order_product as &$val) {
                $ordername = $orders[$val['order_id']]['order_name'];
                if (in_array($val['product_id'], $qualitys[$ordername]) || in_array($val['type'], $giftTypeList)) {
                    continue;
                }
                if ($val['type'] == 2) {
                    $gift_product_nos[] = $val['product_no'];
                } else {
                    $product_ids[] = $val['product_id'];
                }
                $val['order_name'] = $ordername;
                if (isset($appeal_orders[$ordername])) {
                    $appeal_orders[$ordername]['product'][] = $val;
                } else {
                    $appeal_orders[$ordername]['order_name'] = $ordername;
                    $appeal_orders[$ordername]['order_time'] = $orders[$val['order_id']]['time'];;
                    $appeal_orders[$ordername]['product'][] = $val;
                }
            }
            if (!empty($appeal_orders)) {
                //获取图片
                $product_photo = array();
                $product_gift_photo = array();
                if (!empty($gift_product_nos)) {
                    $product_gift_where_in = array('key' => 'gno', 'value' => $gift_product_nos);
                    $product_gift_photo_res = $this->ci->product_model->selectProductGift('gno,gift_photo', '', $product_gift_where_in);
                    $product_gift_photo = array_column($product_gift_photo_res, null, 'gno');
                }
                if (!empty($product_ids)) {
                    $product_where_in = array(array('key' => 'id', 'value' => $product_ids));
                    $product_photo_res = $this->ci->product_model->selectProducts('id,thum_photo,template_id', '', $product_where_in);
                    foreach ($product_photo_res as &$item) {
                        // 获取产品模板图片
                        if ($item['template_id']) {
                            $this->ci->load->model('b2o_product_template_image_model');
                            $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($item['template_id'], 'main');
                            if (isset($templateImages['main'])) {
                                $item['thum_photo'] = $templateImages['main']['thumb'];
                            }
                        }
                    }
                    $product_photo = array_column($product_photo_res, null, 'id');
                }
                $appeal_orders = array_values($appeal_orders);
                foreach ($appeal_orders as &$appeal_order) {
                    foreach ($appeal_order['product'] as &$val) {
                        if ($val['type'] == 2) {
                            $val['photo'] = !empty($product_gift_photo[$val['product_no']]) ? PIC_URL . $product_gift_photo[$val['product_no']]['gift_photo'] : '';
                        } else {
                            $val['photo'] = !empty($product_photo[$val['product_id']]) ? PIC_URL . $product_photo[$val['product_id']]['thum_photo'] : '';
                        }
                    }
                }
            }
        }
        return $appeal_orders;
    }

    function doAppeal($params) {
        //检查用户
        $ck_user = $this->get_uid_by_connect_id($params['connect_id']);
        if ($ck_user['code'] != '200') {
            return $ck_user;
        } else {
            $uid = $ck_user['msg'];
        }

        //必要参数验证
        $required_fields = array(
            'product_no' => array('required' => array('code' => '500', 'msg' => '传参错误'))
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        // $img_arr = $this->savePhoto();

        // 申诉图片上传到七牛
        // 蔡昀辰 2015
        if ($_FILES && count($_FILES) <= $this->photolimit) {
            $img_arr = [
                "images" => []
            ];

            // 载入配置和lib
            $this->ci->config->load("qiniu", true, true);
            $this->ci->load->library('Qiniu/qiniu', $this->ci->config->item('qiniu'));

            // 获取图片
            foreach ($_FILES as $photo) {
                $path = $photo['tmp_name'];
                $name = $photo['name'];
                $date = date("ymd", time());
                $prefix = 'img/comment';
                $hash = str_replace('/tmp/php', '', $path);
                $key = "{$prefix}/{$date}/{$hash}/{$name}";
                // 上传
                $ret = $this->ci->qiniu->put($key, $path);
                if ($ret) {
                    $img_arr["images"][] = str_replace('img/', '', $key);
                }
            }
        }


        $data['information'] = $params['information'];
        $data['description'] = strip_tags(trim($params['description']));
        $data['photo'] = implode(",", $img_arr["images"]);
        $data['status'] = 0;
        $data['time'] = date("Y-m-d H:i:s");
        $data['uid'] = $uid;
        $data['ordername'] = $params['ordername'];
        $data['product_id'] = isset($params['product_id']) ? $params['product_id'] : 0;
        $data['product_no'] = $params['product_no'] ? $params['product_no'] : '';
        $data['productname'] = isset($params['productname']) ? trim($params['productname']) : '';
        $data['is_pic_tmp'] = 1;

        $ck_res = $this->_ckAllowAppeal($data);
        if ($ck_res['code'] != 200) {
            return $ck_res;
        }
        if (empty($data['information'])) {
            return array('code' => '300', 'msg' => '请填写手机号');
        }

        if (empty($data['description'])) {
            return array('code' => '300', 'msg' => '请填写问题描述');
        }

        if (empty($data['photo'])) {
            return array('code' => '300', 'msg' => '请上传图片');
        }

        //fix 问题比例
        if(version_compare($params['version'], '5.6.0') >= 0) {
            $data['quest_ratio'] = $params['quest_ratio'] ? $params['quest_ratio'] : 0;
        }

        $id = $this->ci->quality_complaints_model->insQualitys($data);
        if ($id) {
            return array('code' => '200', 'msg' => '提交成功', 'data'=> array('id'=> $id));
        } else {
            return array('code' => '300', 'msg' => '提交失败,请稍后再试');
        }
    }

    /*
     * 订单商品评价列表  －New
     */
    function commentNewList($params) {
        $this->ci->load->model('order_model');
        $this->ci->load->model('evaluation_model');
        //检查用户
        $ck_user = $this->get_uid_by_connect_id($params['connect_id']);
        if ($ck_user['code'] != '200') {
            return $ck_user;
        } else {
            $uid = $ck_user['msg'];
        }
        $order_name = (int)$params['order_name'];

        if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app') {
            //获取可评论订单
            $where = array(
                'uid' => $uid,
                'time >=' => date("Y-m-d", strtotime('-' . $this->can_comment_period)) . " 00:00:00"
            );
        } else {
            //获取可评论订单
            $where = array(
                'uid' => $uid,
                'had_comment' => 0,
                'time >=' => date("Y-m-d", strtotime('-' . $this->can_comment_period)) . " 00:00:00"
            );
        }
        $where_in[] = array(
            'key' => 'operation_id',
            'value' => array(3,6,9),
        );
        if (!empty($order_name)) {
            $where['order_name'] = $order_name;
        }
        $result = $this->ci->order_model->selectOrderList('id,order_name,time,shtime,order_type,send_date,had_comment,has_bask,operation_id', $where, $where_in, '', 9999);

        $curr_time = time();
        $comment_orders = array();
        if (!empty($result)) {

            $evl_info = $this->ci->evaluation_model->get_info($uid, $order_name);
            if (!empty($order_name) && !empty($evl_info) && $result[0]['had_comment'] == 1 && $result[0]['has_bask'] == 1) {
                $rs['code'] ='200';
                $rs['msg'] ='';
                $rs['products'] = array();
                $rs['order_name'] = $order_name;
                $rs['order_time'] = $result[0]['time'];
                if (!empty($evl_info))
                {
                    $rs['canLogisticsEvaluate'] = 1;
                }
                else
                {
                    $rs['canLogisticsEvaluate'] = 0;
                }
                return $rs;
            }

            if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app' && empty($order_name)) {
                //物流评价 － 特殊处理
                foreach ($result as $key => $val) {
                    $evl = $this->ci->evaluation_model->get_info($uid, $val['order_name']);
                    if ($val['had_comment'] == 1 && !empty($evl) && $val['has_bask'] == 1) {
                        unset($result[$key]);
                    }
                }
            }

            if (empty($result)) {
                return array();
            }
            $orders = array_column($result, null, 'id');
            $order_ids = array_column($result, 'id');

            //获取订单商品评论
            if (empty($order_ids)) {
                $comments_res = array();
            } else {
                $comments_res = $this->ci->comment_model->selectComments("product_id,order_id", '', array(array('key' => 'order_id', 'value' => $order_ids)));
            }
            if (!empty($comments_res)) {
                foreach ($comments_res as $val) {
                    $comments[$val['order_id']][] = $val['product_id'];
                }
            }

            //获取订单商品
            $order_product_where_in[] = array(
                'key' => 'order_id',
                'value' => $order_ids,
            );
            $order_products = $this->ci->order_model->selectOrderProducts('id,order_id,product_id,product_no,product_name,gg_name,price,qty,score,type,total_money', '', $order_product_where_in, 'order_id desc');

            $giftTypeList = $this->_setProductType();

            //商品申诉start dengjm
            foreach ($order_products as $key => $value) {
                $order_type = $orders[$value['order_id']]['order_type'];
                $product_type = $order_products[$key]['type'];
                $operation_id = $orders[$value['order_id']]['operation_id'];

                $fbbsql = "select bbqc.id from ttgy_quality_complaints as bbqc left join ttgy_order as bbo on bbqc.ordername=bbo.order_name
                        where bbo.id=" . $value['order_id'] . " and bbqc.product_id=" . $value['product_id'] . " and bbqc.product_no='" . $value['product_no'] . "'";
                $fbbres = $this->ci->db->query($fbbsql)->row_array();

                if (!in_array($product_type, $giftTypeList) && ($result[0]['operation_id'] ==6 ||$result[0]['operation_id']==9) && empty($fbbres) ) {
                    $order_products[$key]['can_report_issue'] = true;
                } else {
                    $order_products[$key]['can_report_issue'] = false;
                }

                //申诉
                if($fbbres)
                {
                    $order_products[$key]['quality_complaints_id'] = $fbbres['id'];
                    $order_products[$key]['has_report_issue'] = 1;
                }
                else
                {
                    $order_products[$key]['quality_complaints_id'] = '';
                    $order_products[$key]['has_report_issue'] = 0;
                }

            }
            //商品申诉end  dengjm

            foreach ($order_products as &$val) {
                $ordername = $orders[$val['order_id']]['order_name'];

                if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app') {
                    if (in_array($val['type'], $giftTypeList)) {
                        //continue;
                    }
                } else {
                    if (in_array($val['product_id'], $comments[$val['order_id']]) || in_array($val['type'], $giftTypeList)) {
                        continue;
                    }
                }

                $product_ids[] = $val['product_id'];
                $val['order_name'] = $ordername;
                $val['score_desc'] = '它的口感如何，颜值还满意吗，说说你喜欢的理由，就可获得5积分哦';
                $val['photo_desc'] = '发照片，可获得5积分';
                $val['report_desc'] = '对它不满意吗？吐吐槽让我们改进，还能赚5积分哦';
                if (isset($comment_orders[$ordername])) {
                    $comment_orders[$ordername]['product'][] = $val;
                } else {
                    $comment_orders[$ordername]['order_name'] = $ordername;
                    $comment_orders[$ordername]['order_time'] = $orders[$val['order_id']]['time'];
                    $comment_orders[$ordername]['product'][] = $val;
                }
            }
            if (!empty($comment_orders)) {
                //获取图片
                $product_photo = array();
                if (!empty($product_ids)) {
                    $product_where_in = array(array('key' => 'id', 'value' => $product_ids));
                    $product_photo_res = $this->ci->product_model->selectProducts('id,thum_photo,photo,template_id', '', $product_where_in);
                    foreach ($product_photo_res as &$item) {
                        // 获取产品模板图片
                        if ($item['template_id']) {
                            $this->ci->load->model('b2o_product_template_image_model');
                            $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($item['template_id'], 'main');
                            if (isset($templateImages['main'])) {
                                $item['photo'] = $templateImages['main']['image'];
                                $item['thum_photo'] = $templateImages['main']['thumb'];
                            }
                        }
                    }
                    $product_photo = array_column($product_photo_res, null, 'id');
                }
                $comment_orders = array_values($comment_orders);

                foreach ($comment_orders as &$comment_order) {
                    foreach ($comment_order['product'] as $key => &$val) {
                        $val['photo'] = !empty($product_photo[$val['product_id']]) ? PIC_URL . $product_photo[$val['product_id']]['thum_photo'] : '';
                        $val['big_photo'] = !empty($product_photo[$val['product_id']]) ? PIC_URL . $product_photo[$val['product_id']]['photo'] : '';
                        if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app') {
                            if (!in_array($val['product_id'], $comments[$val['order_id']])) {
                                $comment_order['products'][$val['product_id']] = $val;
                            }
                        }
                    }

                    if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app') {
                        unset($comment_order['product']);
                    }

                    if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app') {
                        //物流评价 － 特殊处理
                        if ($orders[$val['order_id']]['had_comment'] == 1 && $orders[$val['order_id']]['has_bask'] == 1) {
                            $comment_order['product'] = array();
                        }
                        $evl = $this->ci->evaluation_model->get_info($uid, $comment_order['order_name']);
                        if (!empty($evl)) {
                            $comment_order['canLogisticsEvaluate'] = 1;
                        } else {
                            $comment_order['canLogisticsEvaluate'] = 0;
                        }
                    }
                }
            }
        }

        $rs = $comment_orders[0];
        $rs['code'] ='200';
        $rs['msg'] ='';
        $rs['eva_desc'] = '对配送员哥哥的服务还满意吗？说说你的评价，就可赚5积分哦';
        $rs['evabad_desc'] = '对配送员哥哥的服务不满意吗？吐吐槽让我们改进，还能赚5积分哦';
        $rs['star2word'] = array(1=>array('态度恶劣','衣着不整','包裹丢失','包裹私放','商品融化','不提前电联','包装破损','货物不符','商品凌乱','严重压伤','提前配送'),
            2=>array('态度一般','着装随便','超时配送','不送上门','商品融化','不提前电联','包装破损','货物不符','商品凌乱','送错地址'),
            3=>array('态度一般','着装随便','超时配送','不送上门','商品融化','不提前电联','包装破损','货物不符','商品凌乱','送错地址'),
            4=>array('态度一般','着装随便','准时送达','不送上门','商品融化','不提前电联','包装破损','货物不符','商品凌乱'),
            5=>array('态度热情','衣着整洁','配送神速','亲手送达','没有化冻','提前电联','包裹完好','开箱验货')
        );

        $rs['package_star2word'] = array(1 => array('包装破损', '商品凌乱', '商品融化', '货物不符', '严重压伤'),
                                    2 => array('包装破损', '商品凌乱', '商品融化', '货物不符'),
                                    3 => array('包装破损', '商品凌乱', '商品融化', '货物不符'),
                                    4 => array('包装破损', '商品凌乱', '商品融化', '货物不符'),
                                    5 => array('包裹完好', '商品完好',	'没有化冻')
                                );
        $rs['express_star2word'] = array(1 => array('态度恶劣', '衣着不整', '包裹丢失', '包裹私放', '不提前电联', '提前配送'),
                                    2 => array('态度一般', '着装随便', '超时配送', '不送上门', '不提前电联', '送错地址'),
                                    3 => array('态度一般', '着装随便', '超时配送', '不送上门', '不提前电联', '送错地址'),
                                    4 => array('态度一般', '着装随便', '配送延迟', '不送上门', '不提前电联', '提前配送'),
                                    5 => array('态度热情', '衣着整洁', '准时送达', '亲手送达', '提前电联', '开箱验货')
        );

        //特殊处理－同一个商品不同规格
        $pro = array();
        $rs['products'] = array_merge($pro,$rs['products']);

        return $rs;
    }


    function commentList($params) {
        $this->ci->load->model('order_model');
        $this->ci->load->model('evaluation_model');
        //检查用户
        $ck_user = $this->get_uid_by_connect_id($params['connect_id']);
        if ($ck_user['code'] != '200') {
            return $ck_user;
        } else {
            $uid = $ck_user['msg'];
        }
        $order_name = (int)$params['order_name'];

        if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app') {
            //获取可评论订单
            $where = array(
                'uid' => $uid,
                'time >=' => date("Y-m-d", strtotime('-' . $this->can_comment_period)) . " 00:00:00"
            );
        } else {
            //获取可评论订单
            $where = array(
                'uid' => $uid,
                'had_comment' => 0,
                'time >=' => date("Y-m-d", strtotime('-' . $this->can_comment_period)) . " 00:00:00"
            );
        }
        $where_in[] = array(
            'key' => 'operation_id',
            'value' => array(3,6,9),
        );
        if (!empty($order_name)) {
            $where['order_name'] = $order_name;
        }
        $result = $this->ci->order_model->selectOrderList('id,order_name,time,shtime,order_type,send_date,had_comment,has_bask', $where, $where_in, '', 9999);

        $curr_time = time();
        $comment_orders = array();
        if (!empty($result)) {

            $evl_info = $this->ci->evaluation_model->get_info($uid, $order_name);
            if (!empty($order_name) && !empty($evl_info) && $result[0]['had_comment'] == 1 && $result[0]['has_bask'] == 1) {
                return $comment_orders;
            }

            if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app' && empty($order_name)) {
                //物流评价 － 特殊处理
                foreach ($result as $key => $val) {
                    $evl = $this->ci->evaluation_model->get_info($uid, $val['order_name']);
                    if ($val['had_comment'] == 1 && !empty($evl) && $val['has_bask'] == 1) {
                        unset($result[$key]);
                    }
                }
            }

            if (empty($result)) {
                return array();
            }
            $orders = array_column($result, null, 'id');
            $order_ids = array_column($result, 'id');

            //获取订单商品评论
            if (empty($order_ids)) {
                $comments_res = array();
            } else {
                $comments_res = $this->ci->comment_model->selectComments("product_id,order_id", '', array(array('key' => 'order_id', 'value' => $order_ids)));
            }
            if (!empty($comments_res)) {
                foreach ($comments_res as $val) {
                    $comments[$val['order_id']][] = $val['product_id'];
                }
            }

            //获取订单商品
            $order_product_where_in[] = array(
                'key' => 'order_id',
                'value' => $order_ids,
            );
            $order_products = $this->ci->order_model->selectOrderProducts('id,order_id,product_id,product_no,product_name,gg_name,price,qty,score,type,total_money', '', $order_product_where_in, 'order_id desc');

            $giftTypeList = $this->_setProductType();

            //商品申诉start dengjm
            foreach ($order_products as $key => $value) {
                $order_type = $orders[$value['order_id']]['order_type'];
                $product_type = $order_products[$key]['type'];
                $send_time = $orders[$value['order_id']]['send_date'];
                $curr_time = time();
                $can_report_issue_time = date("Y-m-d", strtotime("+4 days", strtotime($send_time)));

                $fbbsql = "select bbqc.id from ttgy_quality_complaints as bbqc left join ttgy_order as bbo on bbqc.ordername=bbo.order_name
                        where bbo.id=" . $value['order_id'] . " and bbqc.product_id=" . $value['product_id'] . " and bbqc.product_no='" . $value['product_no'] . "'";
                $fbbres = $this->ci->db->query($fbbsql)->row_array();

                if (in_array($order_type, array(2)) || in_array($product_type, $giftTypeList) || (intval($curr_time) >= intval(strtotime($can_report_issue_time))) || $fbbres) {
                    $order_products[$key]['can_report_issue'] = false;
                } else {
                    $order_products[$key]['can_report_issue'] = true;
                }
            }
            //商品申诉end  dengjm

            foreach ($order_products as &$val) {
                $ordername = $orders[$val['order_id']]['order_name'];

                if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app') {
                    if (in_array($val['type'], $giftTypeList)) {
                        continue;
                    }
                } else {
                    if (in_array($val['product_id'], $comments[$val['order_id']]) || in_array($val['type'], $giftTypeList)) {
                        continue;
                    }
                }

                $product_ids[] = $val['product_id'];
                $val['order_name'] = $ordername;
                $val['score_desc'] = '发表评论审核通过增加10积分，附加晒单图片再送10积分';
                if (isset($comment_orders[$ordername])) {
                    $comment_orders[$ordername]['product'][] = $val;
                } else {
                    $comment_orders[$ordername]['order_name'] = $ordername;
                    $comment_orders[$ordername]['order_time'] = $orders[$val['order_id']]['time'];
                    $comment_orders[$ordername]['product'][] = $val;
                }
            }
            if (!empty($comment_orders)) {
                //获取图片
                $product_photo = array();
                if (!empty($product_ids)) {
                    $product_where_in = array(array('key' => 'id', 'value' => $product_ids));
                    $product_photo_res = $this->ci->product_model->selectProducts('id,thum_photo,photo,template_id', '', $product_where_in);
                    foreach ($product_photo_res as &$item) {
                        // 获取产品模板图片
                        if ($item['template_id']) {
                            $this->ci->load->model('b2o_product_template_image_model');
                            $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($item['template_id'], 'main');
                            if (isset($templateImages['main'])) {
                                $item['photo'] = $templateImages['main']['image'];
                                $item['thum_photo'] = $templateImages['main']['thumb'];
                            }
                        }
                    }
                    $product_photo = array_column($product_photo_res, null, 'id');
                }
                $comment_orders = array_values($comment_orders);

                foreach ($comment_orders as &$comment_order) {
                    foreach ($comment_order['product'] as $key => &$val) {
                        $val['photo'] = !empty($product_photo[$val['product_id']]) ? PIC_URL . $product_photo[$val['product_id']]['thum_photo'] : '';
                        $val['big_photo'] = !empty($product_photo[$val['product_id']]) ? PIC_URL . $product_photo[$val['product_id']]['photo'] : '';
                        if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app') {
                            if (!in_array($val['product_id'], $comments[$val['order_id']])) {
                                $comment_order['products'][] = $val;
                            }
                        }
                    }

                    if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app') {
                        unset($comment_order['product']);
                    }

                    if (strcmp($params['version'], '3.4.0') >= 0 && $params['source'] == 'app') {
                        //物流评价 － 特殊处理
                        if ($orders[$val['order_id']]['had_comment'] == 1 && $orders[$val['order_id']]['has_bask'] == 1) {
                            $comment_order['product'] = array();
                        }
                        $evl = $this->ci->evaluation_model->get_info($uid, $comment_order['order_name']);
                        if (!empty($evl)) {
                            $comment_order['canLogisticsEvaluate'] = 1;
                        } else {
                            $comment_order['canLogisticsEvaluate'] = 0;
                        }
                    }
                }
            }
        }

        return $comment_orders;
    }

    //订单商品评论 －New
    function doNewComment($params) {

        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'star_eat' => array('required' => array('code' => '300', 'msg' => '评分不能为空')),
            'star_show' => array('required' => array('code' => '300', 'msg' => '评分不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //检查用户
        $ck_user = $this->get_uid_by_connect_id($params['connect_id']);
        if ($ck_user['code'] != '200') {
            return $ck_user;
        } else {
            $uid = $ck_user['msg'];
        }
        //upload
        // $img_arr = $this->savePhoto();

        // 评论图片上传到七牛
        // 蔡昀辰 2015
        if ($_FILES && count($_FILES) <= $this->photolimit) {
            $img_arr = [
                "images" => [],
                "thumbs" => []
            ];

            // 载入配置和lib
            $this->ci->config->load("qiniu", true, true);
            $this->ci->load->library('Qiniu/qiniu', $this->ci->config->item('qiniu'));

            // 获取图片
            foreach ($_FILES as $photo) {
                $path = $photo['tmp_name'];
                $name = $photo['name'];
                $date = date("ymd", time());
                $prefix = 'img/comment';
                $hash = str_replace('/tmp/php', '', $path);
                $key = "{$prefix}/{$date}/{$hash}/{$name}";
                // 上传
                $ret = $this->ci->qiniu->put($key, $path);
                if ($ret) {
                    $img_arr["images"][] = str_replace('img/', '', $key);
                    $img_arr["thumbs"][] = str_replace('img/', '', $key) . '-thumb';
                }
            }
        }

        //评论内容处理
        if(empty($params['content']))
        {
            $content = '此人没有写文字评论哦~';
        }
        else
        {
            $content = $params['content'];
        }

        if(!empty($params['star_eat']) && !empty($params['star_show'])) {
            $all = (int)$params['star_eat'] + (int)$params['star_show'];
            $all = round($all / 2);
            $star = intval($all);
        }
        else
        {
            $star = 5;
        }

        $data = array(
            'uid' => $uid,
            'star' => $star,
            'star_eat' => $params['star_eat'],     //口感
            'star_show' => $params['star_show'],   //颜值
            'product_id' => $params['product_id'],
            'order_id' => $params['order_id'],
            'content' => str_replace("'", "", $this->ci->db->escape(strip_tags(trim($content)))),
            'time' => date('Y-m-d H:i:s'),
            'images' => implode(",", $img_arr["images"]),  // images/2015-05-15/58c2c0a0279776f1916df8288d48fc14.jpg
            'thumbs' => implode(",", $img_arr["thumbs"]),  // images/2015-05-15/58c2c0a0279776f1916df8288d48fc14_thumb.jpg
            'type' => empty($img_arr["thumbs"]) ? 0 : 1,
            'is_pic_tmp' => 1,
        );
        //获取订单信息
        $order_where = array('id' => $data['order_id'], 'uid' => $data['uid']);
        $order = $this->ci->order_model->selectOrder('id,time,operation_id,had_comment', $order_where);
        //获取订单商品信息
        $order_product_where = array('order_id' => $data['order_id']);
        $order_product = $this->ci->order_model->selectOrderProducts('product_name,product_id,type', $order_product_where);
        $giftTypeList = $this->_setProductType();
        foreach ($order_product as $key => $val) {
            if (in_array($val['type'], $giftTypeList)) {
                unset($order_product[$key]);
            }
        }
        //获取订单评论信息
        $comment_where = array('uid' => $data['uid'], 'order_id' => $data['order_id']);
        $comment = $this->ci->comment_model->selectComments('product_id', $comment_where);
        //验证是否可评
        $isallow = $this->_ckAllowComment($data, $order, $order_product, $comment);
        if (!$isallow['status']) {
            return array('code' => '300', 'msg' => $isallow['msg']);
        }

        //自动审核
        $review_star = array(9,10);
        $all_start = (int)$params['star_eat'] + (int)$params['star_show'];
        if(in_array($all_start,$review_star))
        {
            $data['is_review'] = 1;
        }

        //新增评论
        $insert_id = $this->ci->comment_model->insComment($data);
        if (empty ($insert_id)) {
            return array('code' => '300', 'msg' => '评论失败');
        }

        //获取该商品评论信息
        $pcomment_where = array('product_id' => $data['product_id']);
        $pcomment = $this->ci->comment_model->selectComments('id', $pcomment_where);

        //赠送积分
        if(!empty($img_arr["images"]) && !empty($params['content']))
        {
            $score = 20;
        }
        else if(!empty($img_arr['images']))
        {
            $score = 15;
        }
        else if(!empty($params['content']))
        {
            $score = 5;
        }
        else
        {
            $score = 0;
        }

        if (count($pcomment) < $this->score_num) {
            $score = $score * $this->score_mult;
        }
        $product_names = array_column($order_product, null, 'product_id');

        //fix 产品逻辑问题
        if($score >0)
        {
            $this->grant_score($data['uid'], $score, '评论' . $product_names[$data['product_id']]['product_name'] . '商品获得' . $score . '积分','评论商品');
        }

        //判断订单评论是否完成
        if (count($product_names) == count($comment) + 1) {
            $this->ci->order_model->update_order(array('had_comment' => 1, 'has_bask' => 1), $order_where);
        }

        return array('code' => '200', 'msg' => '评论成功');
    }

    //订单商品评论
    function doComment($params) {

        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'content' => array('required' => array('code' => '300', 'msg' => '评论不能为空')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //检查用户
        $ck_user = $this->get_uid_by_connect_id($params['connect_id']);
        if ($ck_user['code'] != '200') {
            return $ck_user;
        } else {
            $uid = $ck_user['msg'];
        }
        //upload
        // $img_arr = $this->savePhoto();

        // 评论图片上传到七牛
        // 蔡昀辰 2015
        if ($_FILES && count($_FILES) <= $this->photolimit) {
            $img_arr = [
                "images" => [],
                "thumbs" => []
            ];

            // 载入配置和lib
            $this->ci->config->load("qiniu", true, true);
            $this->ci->load->library('Qiniu/qiniu', $this->ci->config->item('qiniu'));

            // 获取图片
            foreach ($_FILES as $photo) {
                $path = $photo['tmp_name'];
                $name = $photo['name'];
                $date = date("ymd", time());
                $prefix = 'img/comment';
                $hash = str_replace('/tmp/php', '', $path);
                $key = "{$prefix}/{$date}/{$hash}/{$name}";
                // 上传
                $ret = $this->ci->qiniu->put($key, $path);
                if ($ret) {
                    $img_arr["images"][] = str_replace('img/', '', $key);
                    $img_arr["thumbs"][] = str_replace('img/', '', $key) . '-thumb';
                }
            }
        }

        $data = array(
            'uid' => $uid,
            'star' => $params['star'],
            'product_id' => $params['product_id'],
            'order_id' => $params['order_id'],
            'content' => str_replace("'", "", $this->ci->db->escape(strip_tags(trim($params['content'])))),
            'time' => date('Y-m-d H:i:s'),
            'images' => implode(",", $img_arr["images"]),  // images/2015-05-15/58c2c0a0279776f1916df8288d48fc14.jpg
            'thumbs' => implode(",", $img_arr["thumbs"]),  // images/2015-05-15/58c2c0a0279776f1916df8288d48fc14_thumb.jpg
            'type' => empty($img_arr["thumbs"]) ? 0 : 1,
            'is_pic_tmp' => 1,
        );
        //获取订单信息
        $order_where = array('id' => $data['order_id'], 'uid' => $data['uid']);
        $order = $this->ci->order_model->selectOrder('id,time,operation_id,had_comment', $order_where);
        //获取订单商品信息
        $order_product_where = array('order_id' => $data['order_id']);
        $order_product = $this->ci->order_model->selectOrderProducts('product_name,product_id,type', $order_product_where);
        $giftTypeList = $this->_setProductType();
        foreach ($order_product as $key => $val) {
            if (in_array($val['type'], $giftTypeList)) {
                unset($order_product[$key]);
            }
        }
        //获取订单评论信息
        $comment_where = array('uid' => $data['uid'], 'order_id' => $data['order_id']);
        $comment = $this->ci->comment_model->selectComments('product_id', $comment_where);
        //验证是否可评
        $isallow = $this->_ckAllowComment($data, $order, $order_product, $comment);
        if (!$isallow['status']) {
            return array('code' => '300', 'msg' => $isallow['msg']);
        }

        //自动审核
        $star = array(3,4,5);
        if(in_array($data['star'],$star))
        {
            $data['is_review'] = 1;
        }

        //新增评论
        $insert_id = $this->ci->comment_model->insComment($data);
        if (empty ($insert_id)) {
            return array('code' => '300', 'msg' => '评论失败');
        }

        //获取该商品评论信息
        $pcomment_where = array('product_id' => $data['product_id']);
        $pcomment = $this->ci->comment_model->selectComments('id', $pcomment_where);
        //赠送积分
        $score = empty($img_arr["images"]) ? 10 : 20;
        if (count($pcomment) < $this->score_num) {
            $score = $score * $this->score_mult;
        }
        $product_names = array_column($order_product, null, 'product_id');
        $this->grant_score($data['uid'], $score, '评论' . $product_names[$data['product_id']]['product_name'] . '商品获得' . $score . '积分','评论商品');

        //判断订单评论是否完成
        if (count($order_product) == count($comment) + 1) {
            $this->ci->order_model->update_order(array('had_comment' => 1, 'has_bask' => 1), $order_where);
        }

        return array('code' => '200', 'msg' => '评论成功');
    }

    private function _ckAllowAppeal($data) {
        if (empty($data['information']) || strlen($data['information']) > 20) {
            return array('code' => '300', 'msg' => '输入的联系方式有误');
        }
        if (empty($data['description'])) {
            return array('code' => '300', 'msg' => '输入的问题描述有误');
        }

        $order = $this->ci->order_model->selectOrder("operation_id,shtime,time", array("order_name" => $data['ordername']));
        if (empty($order) || !in_array($order['operation_id'], array(6, 9))) {
            return array('code' => '300', 'msg' => '请在收货后再提交质量申诉');
        }

        //申诉的条件应该是送货时间往后顺延48小时以内，2到3天的订单取第3天
//        $curr_time = time();
//        if ($order['shtime'] == 'after2to3days') {
//            $send_time = date("Y-m-d", strtotime($order['time']));
//            $can_report_issue_time = date("Y-m-d", strtotime("+7 days", strtotime($send_time)));
//        } elseif ($order['shtime'] == 'after1to2days') {
//            $send_time = date("Y-m-d", strtotime($order['time']));
//            $can_report_issue_time = date("Y-m-d", strtotime("+6 days", strtotime($send_time)));
//        } elseif ($order['stime'] == '2hours') {
//            $send_time = date("Y-m-d", strtotime($order['time']));
//            $can_report_issue_time = date("Y-m-d", strtotime("+4 days", strtotime($send_time)));
//        } else {
//            $send_time = date("Y-m-d", strtotime($order['shtime']));
//            $can_report_issue_time = date("Y-m-d", strtotime("+4 days", strtotime($send_time)));
//        }
//        if ($curr_time > strtotime($can_report_issue_time)) {
//            return array('code' => '300', 'msg' => '时间太久了，无法申诉');
//        }

        $qualitys = $this->ci->quality_complaints_model->selectQualitys('id', array('ordername' => $data['ordername'], 'product_id' => $data['product_id']));
        if (!empty($qualitys)) {
            return array('code' => '300', 'msg' => '该商品已经提交过质量申诉，请勿重复提交');
        }
        return array('code' => '200', 'msg' => '');
    }

    private function _ckAllowComment($data, $order, $order_product, $comment) {
        $status = true;
        $msg = '';

        //验证参数是否完整
        if (empty($data['order_id']) || empty($data['product_id']) || empty($data['uid']) || empty($order)) {
            $status = false;
            $msg = '非法请求！';
            return array('status' => $status, 'msg' => $msg);
        }
        if (empty ($data['star'])) {
            $status = false;
            $msg = '您好，请选择评级。';
            return array('status' => $status, 'msg' => $msg);
        }
        if (empty($data['content'])) {
            $status = false;
            $msg = '您好，请输入评论。';
            return array('status' => $status, 'msg' => $msg);
        }
        if (strlen($data['content']) < 1) {
            $status = false;
            $msg = '您好，评论内容过低。';
            return array('status' => $status, 'msg' => $msg);
        }

        $order_product_arr = array_column($order_product, null, 'product_id');
        if (($order['operation_id'] != 3 && $order['operation_id'] != 6 && $order['operation_id'] != 9))
        {
            $status = false;
            $msg = '订单未完成，暂时无法商品评论。';
            return array('status' => $status, 'msg' => $msg);
        }

        if(!isset($order_product_arr[$data['product_id']]))
        {
            $status = false;
            $msg = '该商品不能评价。';
            return array('status' => $status, 'msg' => $msg);
        }

        if($order['had_comment'] == '1')
        {
            $status = false;
            $msg = '订单商品已评价完成，请勿重复提交。';
            return array('status' => $status, 'msg' => $msg);
        }

        if($order['time'] < date("Y-m-d", strtotime('-' . $this->can_comment_period)))
        {
            $status = false;
            $msg = '订单商品评论已过商品评论有效期';
            return array('status' => $status, 'msg' => $msg);
        }

        $comment_product_ids = array_column($comment, 'product_id');
        if (in_array($data['product_id'], $comment_product_ids)) {
            $status = false;
            $msg = '请勿重复评论';
            return array('status' => $status, 'msg' => $msg);
        }

        return array('status' => $status, 'msg' => $msg);
    }

    //保存图片
    // 计划修改成不在本地保存 不进行GD图片压缩 直接上传到七牛并配置thumb 320大小    by蔡昀辰 2015
    private function savePhoto() {
        $img_name_arr = array();
        $photo_arr = array();
        $thumbs_arr = array();
        if (!empty($_FILES)) {
            $config['upload_path'] = $this->ci->config->item('photo_base_path') . $this->photopath;
            $config['allowed_types'] = 'gif|jpg|png';
            $config['encrypt_name'] = true;
            $this->ci->load->library('upload', $config); // 蔡昀辰：这里不用上传 改成qiniu->put()
            for ($i = 0; $i < $this->photolimit; $i++) {
                $key = "photo" . $i;
                if (empty($_FILES[$key]['size'])) {
                    continue;
                }
                if (!$this->ci->upload->do_upload($key)) {  // 蔡昀辰：这里不用上传 改成qiniu->put()
                    return array('code' => '300', 'msg' => '上传失败');
                }
                $image_data[] = $this->ci->upload->data(); // 蔡昀辰：这里不用上传 改成qiniu->put()
            }
            if (!empty($image_data)) {
                $this->ci->load->library('image_lib');
                foreach ($image_data as $val) {

                    // 蔡昀辰：这里不用压缩 直接加-commentThumb就行
                    $curr_image_info = pathinfo($val['file_name']);
                    $thumb_image_info = $curr_image_info['filename'] . "_thumb";
                    $thumb_photo = $thumb_image_info . "." . $curr_image_info['extension'];
                    $thumb_config['image_library'] = 'gd2';
                    $thumb_config['source_image'] = $config['upload_path'] . "/" . $val['file_name'];
                    $thumb_config['create_thumb'] = TRUE;
                    $thumb_config['maintain_ratio'] = TRUE;
                    $thumb_config['width'] = $this->thumb_size;
                    $thumb_config['height'] = $this->thumb_size;
                    $this->ci->image_lib->initialize($thumb_config);
                    if (!$this->ci->image_lib->resize()) {
                        return array('code' => '300', 'msg' => '上传失败');
                    }
                    $photo_arr[] = $this->photopath . "/" . $val['file_name'];
                    $thumbs_arr[] = $this->photopath . "/" . $thumb_photo;
                    // 蔡昀辰：这里不用压缩 直接加-commentThumb就行
                }
            }
        }
        $img_name_arr["images"] = $photo_arr;
        $img_name_arr["thumbs"] = $thumbs_arr;
        return $img_name_arr;
    }


    /**
     * 同步订单状态
     *
     * @return void
     * @author
     **/
    public function status($order_name, $status, $sendCompleteTime) {
        $result = array('rs' => 'succ', 'msg' => '');

        $this->ci->load->model('order_model');
        $order = $this->ci->order_model->dump(array('order_name' => $order_name));
        if (!$order) {
            return array('rs' => 'fail', 'msg' => '订单不存在');
        }

        $operation = $this->ci->config->item('operation');

        $operation_id = null;
        switch ($status) {
            case 'return': // 退货
                if (!in_array($order['operation_id'], array(2, 6, 9))) {
                    return array('rs' => 'fail', 'msg' => '订单处于' . $operation[$order['operation_id']] . '状态，不能退货');
                }

                $operation_id = 7;
                break;
            case 'reship': // 换货
                if (!in_array($order['operation_id'], array(2, 6, 9))) {
                    return array('rs' => 'fail', 'msg' => '订单处于' . $operation[$order['operation_id']] . '状态，不能换货');
                }
                $operation_id = 8;
                break;
            case 'check':
                if ($order['operation_id'] != 0 && $order['operation_id'] !=4) {
                    return array('rs' => 'fail', 'msg' => '订单处于' . $operation[$order['operation_id']] . '状态，不能再次审核');
                }

                $operation_id = 1;
                break;
            case 'picking':
                if ($order['operation_id'] != 1) {
                    //return array('rs'=>'fail','msg'=>'订单处于'.$operation[$order['operation_id']].'状态，不能拣货');
                    return $result;
                }
                $operation_id = 4;
                break;
            case 'sendComplete':
                if($order['operation_id'] == 2){
                    $operation_id = 6;
                }elseif(!in_array($order['operation_id'], array(3,9))){
                    return array('rs'=>'fail','msg'=>'订单处于'.$operation[$order['operation_id']].'状态，不能操作配送完成');
                }else{
                    return $result;
                }
                break;
        }

        // 更新订单状态
        if ($operation_id) {
            $updata = array('operation_id' => $operation_id);
            if($operation_id == 6){
                $updata['sendCompleteTime'] = $sendCompleteTime;
            }
            $rs = $this->ci->order_model->update($updata, array('order_name' => $order_name));
            if (!$rs) {
                return array('rs' => 'fail', 'msg' => '订单同步失败');
            }
        }

        return $result;
    }


    public function pay_status($order_name) {
        $result = array('rs' => 'succ', 'msg' => '');

        $this->ci->load->model('order_model');
        $order = $this->ci->order_model->dump(array('order_name' => $order_name));
        if (!$order) {
            return array('rs' => 'fail', 'msg' => '订单不存在');
        }

        if ($order['pay_status'] == 1) {
            return array('rs' => 'fail', 'msg' => '订单已支付');
        }

        if (in_array($order['operation_id'], array(5, 7, 8))) {
            return array('rs' => 'fail', 'msg' => '取消/退货/换货订单不能操作');
        }

        $rs = $this->ci->order_model->update(array('pay_status' => '1'), array('order_name' => $order_name));

        if (!$rs) {
            return array('rs' => 'fail', 'msg' => '订单支付失败');
        }

        // 操作日志
        $this->ci->load->model('order_op_model');
        $order_op = array(
            "manage" => "erp系统",
            "pay_msg" => "订单付款",
            "operation_msg" => "",
            "time" => date("Y-m-d H:i:s"),
            "order_id" => $order['id'],
        );
        $this->ci->order_op_model->insert($order_op);

        return $result;
    }

    public function logisticTrace($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'ordername  can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true && defined('REFRESH_MEMCACHE') && REFRESH_MEMCACHE != $params['service']) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $uid . "_" . $params['order_name'];
            $result = $this->ci->memcached->get($mem_key);
            if ($result) {
                return $result;
            }
        }
        $logistic_info = $this->ci->order_model->getLogisticInfo($params['order_name'], $uid);
        $this->ci->load->bll('pool/order',null,'bll_pool_order');
        $order_route = $this->ci->bll_pool_order->getLogisticTrace($params['order_name']);
        $route = array();
        if($order_route){
            foreach ($order_route as $key => $value) {
                $one_route = array();
                $one_route['trace_desc'] = $value['opNote'];
                $one_route['trace_time'] = strlen($value['createDate']>10)?date("Y-m-d H:i:s", $value['createDate']/1000):date("Y-m-d H:i:s", $value['createDate']);
                $route[] = $one_route;
            }
        }
        $result = array();
        if ($logistic_info && in_array($logistic_info['deliver_method'], array('京东快递', '顺丰', '南京晟邦','蜂鸟配送'))) {
            switch ($logistic_info['deliver_method']) {
                case '京东快递':
                    $logistic_logo = 'http://cdn.fruitday.com/assets/logistic/ic_jd@2x.png';
                    $logistic_tag = "jd";
                    break;
                case '顺丰':
                    $logistic_logo = 'http://cdn.fruitday.com/assets/logistic/ic_sf@2x.png';
                    $logistic_tag = "sf";
                    break;
                case '南京晟邦':
                    $logistic_logo = 'http://cdn.fruitday.com/assets/logistic/ic_fruitday@2x.png';
                    $logistic_tag = "njsb";
                    break;
                case '蜂鸟配送':
                    $logistic_logo = 'http://awshuodong.fruitday.com/sale/ic_fn@2x.png';
                    $logistic_tag = "fn";
                    break;
                default:
                    $logistic_logo = 'http://cdn.fruitday.com/assets/logistic/ic_fruitday@2x.png';
                    break;
            }

            $result['type'] = 1;
            $result['driver_name'] = '';
            $result['driver_phone'] = '';
            $result['logistic_company'] = $logistic_info['deliver_method'];
            $result['logistic_logo'] = $logistic_logo;
            $result['logistic_order'] = $logistic_info['logi_no'];

            if(empty($route)){
                $apiUrl = "http://trac.fday.co/api/baoguo/luyou/".$logistic_tag."/".$logistic_info['logi_no'];
                $apiUrl = POOL_LOGISTICTRACE_URL . "?code=" . $logistic_info['logi_no'] . "&accs_key=d5473645-e614-4d0f-bc0c-38b0b3ccbfc8";
                $ch = curl_init($apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Content-Type: application/x-www-form-urlencoded'
                    )
                );
                $api_result = curl_exec($ch);
                $result_json = urldecode($api_result);
                $return = json_decode($result_json, 1);
                if ($return['result'] != 1) {
                    $logistic_trace[] = array(
                        'trace_desc' => '物流公司已收件',
                        'trace_time' => date("Y-m-d H:i:s", $logistic_info['delivertime'])
                    );
                } else {
                    if (empty($return['list'])) {
                        $logistic_trace[] = array(
                            'trace_desc' => '物流公司已收件',
                            'trace_time' => date("Y-m-d H:i:s", $logistic_info['delivertime'])
                        );
                    } else {
                        foreach ($return['list'] as $key => $value) {
                            $logistic_trace[$key]['trace_desc'] = $value['remark'] ? $value['remark'] : '';
                            $logistic_trace[$key]['trace_time'] = $value['acceptTime'];
                        }
                        krsort($logistic_trace);
                        $logistic_trace_tmp = array();
                        foreach ($logistic_trace as $lk => $lv) {
                            $logistic_trace_tmp[] = $lv;
                        }
                        $logistic_trace = $logistic_trace_tmp;
                    }
                }
            }
        } else {
            $result['type'] = 0;
            $result['driver_name'] = $logistic_info['deliver_name']?$logistic_info['deliver_name']:'暂无';
            $result['driver_phone'] = $logistic_info['deliver_mobile']?$logistic_info['deliver_mobile']:'';
            $result['logistic_company'] = $logistic_info['deliver_method']?$logistic_info['deliver_method']:'天天果园';
            $result['logistic_logo'] = 'http://cdn.fruitday.com/assets/logistic/ic_fruitday@2x.png';
            // $result['logistic_company'] = $logistic_info['deliver_method']?$logistic_info['deliver_method']:'';
            // $result['logistic_logo'] = $logistic_info['deliver_method']?'http://cdn.fruitday.com/assets/logistic/ic_fruitday@2x.png':'';
            $result['logistic_order'] = $logistic_info['logi_no']?$logistic_info['logi_no']:'';
            $logistic_info and $logistic_trace = array(
                array(
                    'trace_desc' => '【已发货】送货员：' . $logistic_info['deliver_name'] . '，手机：' . $logistic_info['deliver_mobile'],
                    'trace_time' => date("Y-m-d H:i:s", $logistic_info['delivertime'])
                )
            );
        }
        $logistic_trace = $route?$route:$logistic_trace;
        $result['logistic_trace'] = $logistic_trace?$logistic_trace:array();
        if (defined('OPEN_MEMCACHE') && OPEN_MEMCACHE == true) {
            if (!$this->ci->memcached) {
                $this->ci->load->library('memcached');
            }
            $mem_key = $params['service'] . "_" . $params['source'] . "_" . $params['version'] . "_" . $uid . "_" . $params['order_name'];
            $this->ci->memcached->set($mem_key, $result, 600);
        }
        return $result;
    }

    function send_fp_jf($order_name, $uid) {
        if (empty($uid)) return;
        $score = 100;
        $reason = "首次使用电子发票,赠送" . $score . "积分";
        $einvoices = $this->ci->db->select('id')->from('order_einvoices')->where('order_name', $order_name)->get()->row_array();
        if ($einvoices) {
            $user_jf = $this->ci->db->select('id')->from('user_jf')->where(array('uid' => $uid, 'reason' => $reason))->get()->row_array();
            if (empty($user_jf)) {
                $this->grant_score($uid, $score, $reason,'电子发票');
            }
        }
    }

    function returnO2oGiftSend($order_id) {
        $sql = "update ttgy_blow_gifts set is_used=0 where order_id=" . $order_id;
        $this->ci->db->query($sql);
    }

    function checkChildOrder($p_order_id) {
        $sql = "SELECT * FROM ttgy_o2o_child_order where p_order_id=" . $p_order_id;
        $res = $this->ci->db->query($sql)->result_array();
        if ($res) {
            return $res;
        }
        return false;
    }

    function getChildOrderInfo($p_order_id) {
        $sql = "select e.store_id,p.product_id,p.qty from ttgy_o2o_child_order c join ttgy_o2o_order_product_extra e on e.c_order_id=c.id join ttgy_order_product p on p.id=e.order_product_id where c.p_order_id={$p_order_id}";
        $rows = $this->ci->db->query($sql)->result_array();
        return $rows;
    }

    /*
   * 选择支付方式 － pc
   */
    function chosePcPayment($params) {

    }

    function getOrderProducts($params) {
        $result = $this->ci->order_product_model->getProductsByOrderId($params['orderId']);
        return $result;
    }

    function repair($order_id, &$msg ,$fpay=0) {
        $this->ci->db->trans_begin();
        $order = $this->ci->order_model->dump(array('id' => $order_id));
        $uid = $order['uid'];
        $order_name = $order['order_name'];

        if (!$order) {
            $msg = '订单不存在';
            return false;
        }

        if ($order['operation_id'] != '5') {
            $msg = '订单不是取消状态，不能恢复';
            return false;
        }

        // 在线提货劵
        if ($order['pay_parent_id'] == '6') {
            $this->ci->db->trans_rollback();
            $msg = '提货券订单不能恢复';
            return false;
        }

        $money = 0;
        if ($order['pay_parent_id'] == 5) {
            $money += $order['money'];
        }
        if ($order['use_money_deduction'] > 0) {
            $money += $order['use_money_deduction'];
        }
        //余额
        $user_money = $this->ci->user_model->selectUser('money', array('id' => $uid));
        if ($user_money['money'] - $money < 0) {
            $this->ci->db->trans_rollback();
            $msg = '余额不足，不能恢复';
            return false;
        }

        if ($money > 0) {
            $check = $this->ci->user_model->check_money_identical($uid);
            if ($check === false) {
                $this->ci->db->trans_rollback();
                $this->ci->user_model->freeze_user($uid);
                $msg = '帐号余额异常，恢复失败';
                return false;
            }
            if($fpay == 0 && $order['pay_status'] == 1){
                $result = $this->ci->user_model->cut_user_money($uid, $money, $order['order_name'], true);
                if (!$result) {
                    $this->ci->db->trans_rollback();
                    $msg = '余额抵扣失败，恢复失败';
                    return false;
                }
            }
        }

        //赠品
        $this->ci->load->model('user_gifts_model');
        $result = $this->ci->user_gifts_model->repairOrderUserGift($order['id'], $order['uid']);
        if (!$result) {
            $this->ci->db->trans_rollback();
            $msg = '赠品使用失败，恢复失败';
            return false;
        }

        // 积分
        if ($order['jf_money'] > 0) {
            $use_jf = $order['jf_money'] * 100;
            $result = $this->ci->user_model->cut_uses_jf($uid, $use_jf, $order['order_name'], true);
            if (!$result) {
                $this->ci->db->trans_rollback();
                $msg = '积分使用失败，恢复失败';
                return false;
            }
        }
        // 优惠劵
        if ($order['use_card']) {
            $this->ci->load->model('card_model');
            $card_info = $this->ci->card_model->get_card_info($order['use_card']);
            if ($card_info['is_used'] == 1) {
                $this->ci->db->trans_rollback();
                $msg = '优惠券已使用，恢复失败';
                return false;
            }
            $content = "订单" . $order["order_name"] . "抵扣" . $order['card_money'] . "(订单恢复)";

            $card_data = array(
                'is_used' => '1',
                'content' => $content
            );
            if (!$this->ci->card_model->update_card($card_data, array('card_number' => $order['use_card']))) {
                $this->ci->db->trans_rollback();
                $msg = '优惠券抵扣失败，恢复失败';
                return false;
            }
        }

        //红包状态
        $this->repair_packet_status($order['id']);

        $update_data = array();
        $update_data = array('operation_id' => 1, 'last_modify' => time());
        if($fpay == 1){
            $update_data['operation_id'] = 0;
            if($order['channel'] == 7){
                $update_data['operation_id'] = 2;
            }
        }
        $affected_row = $this->ci->order_model->update($update_data, array('id' => $order['id']));
        if (!$affected_row) {
            $this->ci->db->trans_rollback();
            $msg = '订单恢复失败';
            return false;
        }

        // 操作日志
        $this->ci->load->model('order_op_model');
        $order_op = array(
            "manage" => "erp系统",
            "pay_msg" => "",
            "operation_msg" => "订单恢复",
            "time" => date("Y-m-d H:i:s"),
            "order_id" => $order['id'],
        );
        if($fpay == 1){
            $order_op['manage'] = '支付中心';
        }
        $this->ci->order_op_model->insert($order_op);
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            $msg = '订单恢复失败';
            return false;
        } else {
            $this->ci->db->trans_commit();
        }
        $msg = '订单恢复成功';
        return true;
    }

    private function repair_packet_status($order_id) {
        $this->ci->db->where(array("order_id" => $order_id));
        $this->ci->db->update('red_packets', array(
            "status" => 1
        ));
    }

    /*
     * 订单物流评价 － new
     */
    function orderEval($params) {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
            //'score' => array('required' => array('code' => '500', 'msg' => 'score can not be null')),
            //'remark'=>array('required' => array('code' => '500', 'msg' => 'remark can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $connect_id = $params['connect_id'] ? $params['connect_id'] : '';
        $order_name = $params['order_name'] ? $params['order_name'] : '';

        $this->ci->load->library('login');
        $this->ci->login->init($connect_id);
        $uid = $this->ci->login->get_uid();
        if (empty($uid)) {
            return array('code' => 300, 'msg' => '用户登录超时，请重新登录');
        }

        $order = $this->ci->order_model->dump(array('order_name' => $order_name));
        if (empty($order)) {
            return array('code' => 300, 'msg' => '用户订单不存在');
        }

        return $order;
    }

    /*
     * 使用余额支付订单
     */
    function useBalance($params) {
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

        $order = $this->ci->order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid, 'order_status' => 1));
        if (empty($order)) {
            return array('code' => 300, 'msg' => '用户订单不存在');
        }

        if ($order['pay_status'] != 0) {
            return array('code' => 300, 'msg' => '请稍后，等待订单支付确认');
        }

        if ($order['pay_parent_id'] == 6) {
            return array('code' => 300, 'msg' => '在线提货券,无需使用余额');
        }

        if ($order['order_status'] != 1) {
            return array('code' => 300, 'msg' => '请提交订单后，使用余额');
        }

        $user = $this->ci->user_model->getUser($uid);
        $user_money = $user['money'];

        $new_pay_discount = $order['new_pay_discount'];
        $order_money = $order['money'] + $new_pay_discount + $order['use_money_deduction']; //订单金额需要先加上支付抵扣金额
        $use_money_deduction = $order['use_money_deduction'];
        $pay_parent_id = $order['pay_parent_id'];
        $pay_id = $order['pay_id'];
        $channel = $order['channel'];

        $order_id = $order['id'];

        $info = array();

        $this->ci->db->trans_begin();

        if ($user_money > 0) {
            $check = $this->ci->user_model->check_money_identical($uid);
            if ($check === false) {
                $this->ci->user_model->freeze_user($uid);
                return array("code" => "300", "msg" => "您的账号异常，已被冻结，请联系客服");
            }

            if ($user_money >= $order_money) {
                $pay_array = $this->ci->config->item("pay_array");
                $pay_parent_id = 5;
                $pay_id = 0;
                $pay_name = $pay_array[$pay_parent_id]['name'];
                $this->ci->order_model->set_ordre_payment($pay_name, $pay_parent_id, $pay_id, $order_id);

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
                $this->ci->order_model->update_order($data, $where);

                //取消发票
                $invoice = array(
                    'is_valid' => 0
                );
                $invoice_where = array(
                    'order_id' => $order['id']
                );
                $this->ci->order_model->update_order_invoice($invoice, $invoice_where);

                $this->ci->pay_discount_model->initPayDiscount($order_id); //全额使用余额抵扣，初始化支付折扣

                $info['order_name'] = $params['order_name'];
                $info['user_money'] = number_format((float)($user['money']), 2, '.', '');
                $info['user_can_money'] = number_format((float)($user['money'] - $order_money), 2, '.', '');
                $info['order_money'] = number_format((float)$order_money, 2, '.', '');
                $info['use_money_deduction'] = $use_money_deduction;
                $info['need_online_pay'] = number_format((float)$data['use_money_deduction'], 2, '.', '');;
                $info['is_pay_balance'] = 1;
                $info['pay_bank_discount'] = number_format(0, 2, '.', '');

                //余额支付短信验证
                $cart_array = array();
                $order_address_info = $this->ci->order_model->get_order_address($order['address_id']);
                $info['need_send_code'] = 0;
                $if_send_code = $this->ci->order_model->checkSendCode($cart_array, $uid, $pay_parent_id, $order_address_info, $order['id']);
                if ($if_send_code) {
                    $info['need_send_code'] = 1;
                }

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
            } else if ($user_money < $order_money) {
                $data = array(
                    'use_money_deduction' => $user_money,
                    'money' => ($order_money - $new_pay_discount) - $user_money
                );
                $where = array(
                    'order_name' => $params['order_name'],
                    'order_status' => 1,
                );
                $this->ci->order_model->update_order($data, $where);

                $info['order_name'] = $params['order_name'];
                $info['user_money'] = number_format((float)($user['money']), 2, '.', '');
                $info['user_can_money'] = number_format((float)($user['money'] - $data['use_money_deduction']), 2, '.', '');
                $info['order_money'] = number_format((float)$order_money, 2, '.', '');
                $info['use_money_deduction'] = $data['use_money_deduction'];
                $info['need_online_pay'] = number_format((float)($order_money - $data['use_money_deduction']), 2, '.', '');
                $info['is_pay_balance'] = 0;

                //支付折扣优惠
                $order_products = $this->ci->order_product_model->getProductsByOrderId($order_id);
                $pids = array();
                foreach ($order_products as $key => $value) {
                    if ($value['category'] == 1) $pids[] = $value['sku'];
                }
                switch ($channel) {
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
                $o_area_info = $this->ci->order_model->getIorderArea($order_id);
                //以线上支付金额计算支付折扣
                $new_pay_discount = $this->ci->pay_discount_model->set_order_pay_discount($pay_parent_id, $pay_id, $info['need_online_pay'], $pids, $order_id, $source, $o_area_info['province'], $uid, $order['order_type']);
                //订单金额减去支付折扣
                $new_money = number_format((float)($info['need_online_pay'] - $new_pay_discount), 2, '.', '');
                $this->ci->order_model->update_order(array('money' => $new_money), array('id' => $order_id));
                //$info['order_money'] = number_format((float)($order_money - $new_pay_discount), 2, '.', '');
                $info['order_money'] = number_format((float)($order_money), 2, '.', '');
                $info['need_online_pay'] = number_format((float)($info['need_online_pay'] - $new_pay_discount), 2, '.', '');
                $info['pay_bank_discount'] = number_format($new_pay_discount, 2, '.', '');

                //余额支付短信验证
                $cart_array = array();
                $order_address_info = $this->ci->order_model->get_order_address($order['address_id']);
                $if_send_code = $this->ci->order_model->checkSendCode($cart_array, $uid, $pay_parent_id, $order_address_info, $order['id']);

                //组合支付
                if($info['use_money_deduction'] >0 && $if_send_code)
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

                //同步o2o
                if ($order['order_type'] == 3 || $result['order_type'] == 4) {
                    $this->order_o2o_split($uid, $order_id, 1);
                }
            }

            //重新计算－订单积分
            $order_new = $this->ci->order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid, 'order_status' => 1));
            $order_score = $this->ci->user_model->dill_score_new($order_new['money'] - $order_new['method_money'], $uid);
            if ($order_score < 0) {
                $order_score = 0;
            }
            if ($order_new['pay_parent_id'] == 5) {
                $order_score = 0;
            }
            $ds_score = array(
                'score' => $order_score
            );
            $where_score = array(
                'order_name' => $params['order_name'],
                'order_status' => 1,
            );
            $this->ci->order_model->update_order($ds_score, $where_score);
        } else {
            return array('code' => 300, 'msg' => '用户余额不足');
        }

        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "使用余额失败，请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => 'success', 'info' => $info);
    }


    /*
     * 取消使用余额支付订单
     */
    function cancelUseBalance($params) {
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

        $order = $this->ci->order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid, 'order_status' => 1));
        if (empty($order)) {
            return array('code' => 300, 'msg' => '用户订单不存在');
        }

        if ($order['pay_status'] != 0) {
            return array('code' => 300, 'msg' => '请稍后，等待订单支付确认');
        }

        if ($order['pay_parent_id'] == 6) {
            return array('code' => 300, 'msg' => '在线提货券,无需使用余额');
        }

        if ($order['order_status'] != 1) {
            return array('code' => 300, 'msg' => '请提交订单后，使用余额');
        }

        $user = $this->ci->user_model->getUser($uid);
        $user_money = $user['money'];
        $order_money = $order['money'];
        $use_money_deduction = $order['use_money_deduction'];
        $new_pay_discount = $order['new_pay_discount'];
        $pay_parent_id = $order['pay_parent_id'];
        $pay_id = $order['pay_id'];
        $channel = $order['channel'];
        $order_money = $order_money + $new_pay_discount + $use_money_deduction;
        $order_id = $order['id'];

        $info = array();

        $this->ci->db->trans_begin();

        if ($use_money_deduction > 0) {
            $check = $this->ci->user_model->check_money_identical($uid);
            if ($check === false) {
                $this->ci->user_model->freeze_user($uid);
                return array("code" => "300", "msg" => "您的账号异常，已被冻结，请联系客服");
            }

            $data = array(
                'use_money_deduction' => 0.00,
                'new_pay_discount' => 0.00,
                'money' => $order['money'] + $use_money_deduction + $new_pay_discount
            );
            $where = array(
                'order_name' => $params['order_name'],
                'order_status' => 1,
            );
            $this->ci->order_model->update_order($data, $where);

            $info['order_name'] = $params['order_name'];
            $info['user_money'] = number_format((float)$user['money'], 2, '.', '');
            $info['user_can_money'] = number_format((float)($user['money']), 2, '.', '');
            $info['order_money'] = $order_money;
            $info['use_money_deduction'] = number_format((float)$data['use_money_deduction'], 2, '.', '');
            $info['need_online_pay'] = number_format((float)($order_money - $data['use_money_deduction']), 2, '.', '');
            $info['is_pay_balance'] = 0;

            //支付折扣优惠
            $order_products = $this->ci->order_product_model->getProductsByOrderId($order_id);
            $pids = array();
            foreach ($order_products as $key => $value) {
                if ($value['category'] == 1) $pids[] = $value['sku'];
            }
            switch ($channel) {
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
            $o_area_info = $this->ci->order_model->getIorderArea($order_id);
            //以线上支付金额计算支付折扣
            $new_pay_discount = $this->ci->pay_discount_model->set_order_pay_discount($pay_parent_id, $pay_id, $info['need_online_pay'], $pids, $order_id, $source, $o_area_info['province'], $uid, $order['order_type']);
            //订单金额减去支付折扣
            $new_money = number_format((float)($info['need_online_pay'] - $new_pay_discount), 2, '.', '');
            $this->ci->order_model->update_order(array('money' => $new_money), array('id' => $order_id));
            //$info['order_money'] = number_format((float)($order_money - $new_pay_discount), 2, '.', '');
            $info['order_money'] = number_format((float)$order_money, 2, '.', '');
            $info['need_online_pay'] = number_format((float)($info['need_online_pay'] - $new_pay_discount), 2, '.', '');
            $info['need_send_code'] = 0;
            $info['pay_bank_discount'] = number_format($new_pay_discount, 2, '.', '');

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

            //同步o2o
            if ($order['order_type'] == 3 || $result['order_type'] == 4) {
                $this->order_o2o_split($uid, $order_id, 0);
            }
        } else if ($use_money_deduction == '0.00' && $order['pay_parent_id'] == 5) {
            $invoice_info = $this->ci->order_model->get_order_invoice($order['id'], 0);
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
                'order_name' => $params['order_name'],
                'order_status' => 1,
            );
            $this->ci->order_model->update_order($data, $where);

            //使用发票
            $invoice = array(
                'is_valid' => 1
            );
            $invoice_where = array(
                'order_id' => $order['id']
            );
            $this->ci->order_model->update_order_invoice($invoice, $invoice_where);

            $info['order_name'] = $params['order_name'];
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
        } else {
            return array('code' => 300, 'msg' => '订单未使用余额');
        }

        //重新计算－订单积分
        $order_new = $this->ci->order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid, 'order_status' => 1));
        $order_score = $this->ci->user_model->dill_score_new($order_new['money'] - $order_new['method_money'], $uid);
        if ($order_score < 0) {
            $order_score = 0;
        }
        if ($order_new['pay_parent_id'] == 5) {
            $order_score = 0;
        }
        $ds_score = array(
            'score' => $order_score
        );
        $where_score = array(
            'order_name' => $params['order_name'],
            'order_status' => 1,
        );
        $this->ci->order_model->update_order($ds_score, $where_score);

        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "取消使用余额失败，请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => 'success', 'info' => $info);
    }

    /*
    * 选择支付方式 － 支付
    */
    function choseCostPayment($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'pay_parent_id' => array('required' => array('code' => '300', 'msg' => '请选择支付方式')),
            'pay_id' => array('required' => array('code' => '300', 'msg' => '请选择支付方式')),
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
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

        //处理已创建的订单，变更支付方式
        $order_name = $params['order_name'];
        $order = $this->ci->order_model->dump(array('order_name' => $order_name, 'uid' => $uid));
        if (empty($order)) {
            return array('code' => 300, 'msg' => '用户订单不存在');
        }

        if ($order['pay_status'] != 0) {
            return array('code' => 300, 'msg' => '请稍后，等待订单支付确认');
        }

        if ($order['pay_parent_id'] == 6) {
            return array('code' => 300, 'msg' => '在线提货券,无需使用余额');
        }

        if ($order['order_status'] != 1) {
            return array('code' => 300, 'msg' => '请提交订单后，使用余额');
        }

        //事务开始
        $this->ci->db->trans_begin();
        $this->ci->order_model->set_ordre_payment($pay_name, $pay_parent_id, $pay_id, $order['id']);
        //$this->ci->db->trans_commit();
        $order_id = $order['id'];
        $order_products = $this->ci->order_product_model->getProductsByOrderId($order_id);
        $pids = array();
        foreach ($order_products as $key => $value) {
            if ($value['category'] == 1) $pids[] = $value['sku'];
        }
        $order_info = $this->ci->order_model->getInfoById($order_id);
        switch ($order_info['channel']) {
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
        $o_area_info = $this->ci->order_model->getIorderArea($order_id);
        $money = $order_info['money'];
        if ($order_info['new_pay_discount'] > 0) {
            $money = bcadd($order_info['money'], $order_info['new_pay_discount'], 2);
        }
        $new_pay_discount = $this->ci->pay_discount_model->set_order_pay_discount($pay_parent_id, $pay_id, $money, $pids, $order_id, $source, $o_area_info['province'], $uid, $order['order_type']);
        $new_money = bcsub($money, $new_pay_discount, 2);

        if (bccomp($new_money, $order_info['money'], 2) != 0) {
            $affected_row = $this->ci->order_model->update(array('money' => $new_money), array('id' => $order_id));
            if (!$affected_row) {
                $this->ci->db->trans_rollback();
                return array("code" => "300", "msg" => "选择失败，请重试");
            }
        }

        //重新计算－订单积分
        $order_new = $this->ci->order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid, 'order_status' => 1));
        $order_score = $this->ci->user_model->dill_score_new($order_new['money'] - $order_new['method_money'], $uid);
        if ($order_score < 0) {
            $order_score = 0;
        }
        if ($order_new['pay_parent_id'] == 5) {
            $order_score = 0;
        }
        $ds_score = array(
            'score' => $order_score
        );
        $where_score = array(
            'order_name' => $params['order_name'],
            'order_status' => 1,
        );
        $this->ci->order_model->update_order($ds_score, $where_score);


        $info = array();
        $info['need_online_pay'] = number_format((float)($new_money), 2, '.', '');
        $info['pay_bank_discount'] = number_format($new_pay_discount, 2, '.', '');

        //余额支付短信验证
        $cart_array = array();
        $order_address_info = $this->ci->order_model->get_order_address($order['address_id']);
        $if_send_code = $this->ci->order_model->checkSendCode($cart_array, $uid, $pay_parent_id, $order_address_info, $order['id']);

        //组合支付
        if($order_info['use_money_deduction'] >0 && $if_send_code)
        {
            $info['need_send_code'] = 1;
        }
        else
        {
            $info['need_send_code'] = 0;
        }

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
        $info['order_money'] = number_format((float)($order_info['money'] + $order_info['use_money_deduction'] + $order_info['new_pay_discount']), 2, '.', '');

        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "选择支付方式失败，请重新选择支付");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => 'success', 'info' => $info);
    }

    /**
     * 同步返利订单
     */
    public function syncFanliOrder($params) {
        // 检查参数
        $required = array(
            'channel_id' => array('required' => array('code' => '500', 'msg' => 'channel_id can not be null')),
        );
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
        }

        $this->ci->load->model('order_fanli_model');
        $where = array(
            'channel_id' => $params['channel_id'],
            'order_status !=' => 2, // 没有同步成功
        );
        $fanliOrders = $this->ci->order_fanli_model->getList('*', $where);

        if (count($fanliOrders) > 0) {
//            $fanliOrdersStatusPairs = array_column($fanliOrders, 'status', 'order_id');

            $orderIds = array_column($fanliOrders, 'order_id');
            $orders = $this->ci->order_model->getList('*', array('id' => $orderIds));
            foreach ($orders as $order) {
                $orderId = $order['id'];
                $fanliOrderStatus = $this->getfanliOrderStatus($order);

                // 已完成、已取消
                if (in_array($fanliOrderStatus, array(5, 6, 7))) {
                    $syncStatus = 2;
                } else {
                    $syncStatus = 1;
                }

                $data = array(
                    'status' => $fanliOrderStatus,
                    'order_status' => $syncStatus,
                    'time' => empty($order['time']) ? 0 : strtotime($order['time']),
                    'pay_time' => empty($order['pay_time']) ? 0 : strtotime($order['pay_time']),
                    'order_name' => $order['order_name'],
                );

                /**
                 * 如果返利订单状态发生改变，则再次推送，接普通返利通的不需要重新推送
                 * 公司结算都是线下结算，现在推送的记录都是预订单，没有接快返的，不用重新推送
                 */
//                if ($fanliOrdersStatusPairs[$orderId] != $fanliOrderStatus) {
//                    $data['push_order'] = '0';
//                }

                $this->ci->order_fanli_model->update($data, array('order_id' => $orderId));
            }
        }
    }

    /**
     * 获取返利订单
     */
    public function getFanliOrders($params) {
        // 检查参数
        $required = array(
            'channel_id' => array('required' => array('code' => '500', 'msg' => 'channel_id can not be null')),
        );
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
        }

        $this->ci->load->model('order_fanli_model');
        $where = array(
            'channel_id' => $params['channel_id'],
            'order_status !=' => 0, // 同步过的返利订单
        );
        if (isset($params['begin']) && !empty($params['begin'])) {
            $where['time >='] = $params['begin'];
        }
        if (isset($params['end']) && !empty($params['end'])) {
            $where['time <='] = $params['end'];
        }
        if (isset($params['status'])) {
            $where['status'] = $params['status'];
        }
        if (isset($params['order_name'])) {
            $where['order_name'] = $params['order_name'];
        }
        if (isset($params['push_status'])) {
            $where['push_status'] = $params['push_status'];
        }
        $fanliOrders = $this->ci->order_fanli_model->getList('*', $where);
        if (count($fanliOrders)) {
            $this->ci->load->model('order_product_model');
//            $this->ci->load->model('cat_model');

            foreach ($fanliOrders as &$order) {
                $discountInfo = $this->ci->order_model->getOrderProductDiscount($order['order_id']);
                // 实际计算返利的金额（除去余额的支付金额 - 运费）
                $fanliMoney = $discountInfo['money'] - $discountInfo['method_money'] - $discountInfo['invoice_money'] - $discountInfo['today_method_money'];

                $order['products'] = $this->ci->order_product_model->getProductsByOrderId($order['order_id']);
                if (count($order['products'])) {
                    $orderProductTotalMoney = array_sum(array_column($order['products'], 'total_money'));

                    foreach ($order['products'] as &$product) {
                        $percent = bcdiv($product['total_money'], $orderProductTotalMoney, 7);
                        $product['total_money'] = $fanliMoney > 0 ? bcmul($fanliMoney, $percent, 3) : 0;
                    }
                }
            }
        }

        return array('code' => 200, 'orders' => $fanliOrders);
    }

    /**
     * 设置返利订单推送信息
     */
    public function setFanliOrderPush($params) {
        // 检查参数
        $required = array(
            'code' => array('required' => array('code' => '500', 'msg' => 'code can not be null')),
            'description' => array('required' => array('code' => '500', 'msg' => 'description can not be null')),
        );
        $checkResult = check_required($params, $required);
        if ($checkResult) {
            return array('code' => $checkResult['code'], 'msg' => $checkResult['msg']);
        }

        $this->ci->load->model('order_fanli_model');
        $order = $this->ci->order_fanli_model->dump(array(
            'channel_id' => '51fanli',
            'order_name' => $params['description'],
        ));

        if ($order) {
            // 保存日志
            $this->ci->load->model('fanli_log_model');
            $this->ci->fanli_log_model->insert(array(
                'code' => $params['code'],
                'msg' => $params['description'],
                'type' => $params['type'],
                'ctime' => $params['ctime'],
            ));

            // 0 repeat | 1 success
            if ($params['code'] == 0 || $params['code'] == 1) {
                $pushStatus = in_array($order['status'], array(5, 6)) ? 2 : 1;
                $this->ci->order_fanli_model->update(array('push_status' => $pushStatus), array(
                    'channel_id' => '51fanli',
                    'order_name' => $params['description'],
                ));
            }
        }
    }

    /**
     * 根据订单状态获取对外订单
     */
    private function getfanliOrderStatus($order) {
        $fanliStatus = 0;
        switch ($order['operation_id']) {
            case 0: // 未审核
                $fanliStatus = 0; // 新下订单
                break;
//            case 1: // 已审核
//                if ($order['pay_status'] == 1) {
//                    $fanliStatus = 1; // 订单已付款
//                } else {
//                    $fanliStatus = 2; // 线下付款
//                }
//                break;
            case 2: // 已发货
                $fanliStatus = 3;
                break;
            case 6: // 已发货
                $fanliStatus = 3;
                break;
            case 9: // 已收货
                $fanliStatus = 4;
                break;
            case 3: // 已完成
                // 5 账户余额支付 | 6 券卡支付
                if (in_array($order['pay_parent_id'], array(5, 6))) {
                    $fanliStatus = 7; // 券卡、余额支付不返利
                } // 4 线下支付 [ 1 货到付现金 | 2 货到刷银行卡 | 7 红色储值卡支付 | 8 金色储值卡支付 | 9 果实卡支付 | 11 通用券/代金券支付 ]
                elseif ($order['pay_parent_id'] == 4 && !in_array($order['pay_id'], array(1, 2))) {
                    $fanliStatus = 7;
                } else {
                    $fanliStatus = 5;
                }
                break;
            case 5: // 已取消
                $fanliStatus = 6;
                break;
        }

        return $fanliStatus;
    }

    /*
    *余额支付验证码 - 全额
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
    * o2o 订单拆分余额更新
    */
    private function order_o2o_split($uid, $orderId, $isBalance) {
        $if_split = true;


        //获取订单信息
        $order = $this->ci->order_model->dump(array('id' => $orderId, 'uid' => $uid));
        $money = $order['money'];
        $use_money_deduction = $order['use_money_deduction'];

        //获取o2o子订单
        $where = array(
            'p_order_id' => $orderId,
            'uid' => $uid
        );
        $this->ci->load->model('o2o_child_order_model');
        $order_o2o = $this->ci->o2o_child_order_model->getList('*', $where);

        if ($isBalance == 1)   //使用余额
        {
            if (count($order_o2o) <= 0) {
                $if_split = false;
            } else {
                if (count($order_o2o) == 1) {
                    $data = array(
                        'money' => $money,
                        'use_money_deduction' => $use_money_deduction
                    );
                    $this->ci->o2o_child_order_model->update($data, array('p_order_id' => $orderId, 'uid' => $uid));
                } else {
                    $total = $money + $use_money_deduction;
                    $c_nums = count($order_o2o);
                    $i = 1;
                    $last_money = 0;
                    $last_user_money = 0;

                    foreach ($order_o2o as $key => $val) {
                        $o2o_base_money = ($val['goods_money'] + $val['method_money']) - $val['card_money'] - $val['jf_money'] - $val['pay_discount'];
                        $zb = floatval($o2o_base_money / $total);

                        if ($i == $c_nums) {
                            $o2o_money = $money - $last_money;
                            $o2o_use_money_deduction = $use_money_deduction - $last_user_money;
                        } else {
                            $o2o_money = $money * (float)$zb;
                            $o2o_use_money_deduction = $use_money_deduction * (float)$zb;

                            $last_money += $o2o_money;
                            $last_user_money += $o2o_use_money_deduction;
                        }

                        $data = array(
                            'money' => $o2o_money,
                            'use_money_deduction' => $o2o_use_money_deduction
                        );
                        $this->ci->o2o_child_order_model->update($data, array('id' => $val['id']));

                        $i++;
                    }
                }
                $if_split = true;
            }
        } else if ($isBalance == 0)  //取消使用余额
        {
            if (count($order_o2o) <= 0) {
                $if_split = false;
            } else {
                if (count($order_o2o) == 1) {
                    $data = array(
                        'money' => $money,
                        'use_money_deduction' => $use_money_deduction
                    );
                    $this->ci->o2o_child_order_model->update($data, array('p_order_id' => $orderId, 'uid' => $uid));
                } else {
                    foreach ($order_o2o as $key => $val) {
                        $o2o_money = $val['money'] + $val['use_money_deduction'];

                        $data = array(
                            'money' => $o2o_money,
                            'use_money_deduction' => 0.00
                        );
                        $this->ci->o2o_child_order_model->update($data, array('id' => $val['id']));
                    }
                }
                $if_split = true;
            }
        }

        return $if_split;
    }

    /*
     * 验证订单支付
     */
    function checkPay($params) {
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

        $order = $this->ci->order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid, 'order_status' => 1));
        if (empty($order)) {
            $msg = array('code' => 300, 'msg' => '用户订单不存在');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        $user = $this->ci->user_model->getUser($uid);

        //余额支付短信验证
        $cart_array = array();
        $order_address_info = $this->ci->order_model->get_order_address($order['address_id']);
        $if_send_code = $this->ci->order_model->checkSendCode($cart_array, $uid, $order['pay_parent_id'], $order_address_info, $order['id']);
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

        if($order['pay_parent_id'] == 8 && date('w') == 6 && date('H')>=10 && date('H')<12 && date('Y-m-d') <= '2017-01-14'){
            $res = $this->ci->order_model->checkCardtypeProd($order['id']);
            //存在券卡类商品
            if($res){
                $msg = array('code' => 300, 'msg' => '券卡类商品，不支持建行活动，请选择其他支付方式');
                $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
                return $msg;
            }
        }

        return array('code' => 200, 'msg' => 'succ');
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
     * 支付闪鲜卡日志
     */
    private function checkCardLog($order_name, $data) {
        $data['order_name'] = $order_name;
        $this->ci->load->library('fdaylog');
        $db_log = $this->ci->load->database('db_log', TRUE);
        $this->ci->fdaylog->add($db_log, 'cardpaylog', json_encode($data));
    }

    /**
     * 删除电子发票
     */
    private function _deleteEinvoice($order_name){
        $this->ci->load->model('order_einvoices_model');
        $this->ci->order_einvoices_model->delete(array('order_name'=>$order_name));
    }

    public function refundInfo($params){
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'order_name'=>array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );
        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();
        if(empty($uid))
        {
            $msg = array('code'=>300,'msg'=>'用户登录异常，请重新登录');
            return $msg;
        }

        $order = $this->ci->order_model->dump(array('order_name' => $params['order_name'],'uid'=>$uid,'order_status'=>1));
        if(empty($order))
        {
            $msg = array('code'=>300,'msg'=>'用户订单不存在');
            return $msg;
        }

        $this->ci->load->bll('pool/order',null,'bll_pool_order');
        $result = $this->ci->bll_pool_order->getRefundLog($params['order_name']);
        if($order['id'] == '1315469810'){
            $result = array('RefundProcess'=>array(array('log'=>'您的退款申请成功,待客服审核中','act_user'=>'系统','time'=>'2016-04-14 14:10:02')),'RefundDetails'=>array(array('money'=>'10.90','channel'=>'支付宝')));
        }

        foreach ($result['RefundProcess'] as $k => $v){
            $sortRefundProcess[$v['time']] = $v;
        }
        krsort($sortRefundProcess);
        if($result){
            $return['code'] = 200;
            $return['RefundProcess'] = array_values($sortRefundProcess);
            $return['RefundDetails'] = $result['RefundDetails'];

            return $return;
        }else{
            $msg = array('code'=>300,'msg'=>'暂无退款信息');
            return $msg;
        }
    }

    /*
     * 重新下单
     */
    function replace($params)
    {
        $require_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect_id can not be null')),
            'order_name'=>array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
            'store_id_list'=>array('required' => array('code' => '500', 'msg' => 'store_id_list can not be null')),
        );

        if ($alert_msg = check_required($params, $require_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();

        if(empty($uid))
        {
            return array('code'=>300,'msg'=>'用户登录异常，请重新登录');
        }

        $bs = mb_substr($params['order_name'],0,1);
        if($bs == 'P')
        {
            $order = $this->ci->b2o_parent_order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid));
            if (empty($order)) {
                return array('code' => 300, 'msg' => '用户订单不存在');
            }

            $orderPro = $this->ci->b2o_parent_order_product_model->get_products($order['id']);
            if (empty($orderPro)) {
                return array('code' => 300, 'msg' => '抱歉，订单中的商品都卖光了，去看看其他的吧');
            }
        }
        else
        {
            $order = $this->ci->order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid));
            if (empty($order)) {
                return array('code' => 300, 'msg' => '用户订单不存在');
            }

            if ($order['order_type'] != 1) {
                //return array('code' => 300, 'msg' => '该订单不支持重新下单');
            }

            $orderPro = $this->ci->order_product_model->get_products($order['id']);
            if (empty($orderPro)) {
                return array('code' => 300, 'msg' => '抱歉，订单中的商品都卖光了，去看看其他的吧');
            }
        }

        $productIds = array();
        $this->ci->load->bll('apicart');
        foreach($orderPro as $key=>$val)
        {
            $data = array(
                'uid'=>$uid,
                'pid'=>$val['product_id'],
                'qty'=>$val['qty'],
                'stores'=>$params['store_id_list'],
            );

            //加入购物车
            $res = $this->ci->bll_apicart->add($data);
            if($res != 200 && $res != 201)
            {
                array_push($productIds,$val['product_id']);
            }
        }

        $pro_count = count($orderPro);
        $proids_count = count($productIds);
        if($proids_count >0)
        {
            if($pro_count == $proids_count)
            {
                return array('code' => 300, 'msg' => '抱歉，订单中的商品都卖光了，去看看其他的吧');
            }
            else
            {
                $strIds = implode(',',$productIds);
                return array('code'=>200,'msg'=>'以下商品卖光了，其余商品已加入购物车','data'=>$strIds);
            }
        }

        return array('code'=>200,'msg'=>'加购物车成功','data'=>'');
    }

    /*
     * 活动标记
     */
    private function _activeWind($order_id){

        $this->ci->load->model('order_product_model');
        $this->ci->load->model('active_order_model');
        $this->ci->load->model('active_wind_model');

        $wind = $this->ci->active_wind_model->get_list();
        $products = $this->ci->order_product_model->get_all_product($order_id);

        if(count($wind) >0 && count($products) >0)
        {
            $arr_wind = array();
            foreach($wind as $key=>$val)
            {
                $arr_wind[$val['active_code']] = $val['product_id'];
            }

            foreach($products as $keys=>$vals)
            {
                $pid = $vals['product_id'];
                $orderId = $vals['order_id'];

                foreach($arr_wind as $ks=>$rs)
                {
                    $arr_pids = explode(',',$rs);

                    if(in_array($pid,$arr_pids))
                    {
                        $ds = array(
                            'order_id'=>$orderId,
                            'active_code'=>$ks
                        );
                        $this->ci->active_order_model->add($ds);
                    }
                }

            }
        }
    }

    /**
     * 对接第三方APP时，提供订单列表。
     */
    public function getOrderListForThirdParty($params) {
        $uid = (int)$params['uid'];
        $start_time = (int)$params['start_time'];
        $end_time = (int)$params['end_time'];
        $client_id = $params['client_id'];
        $offset = $params['offset'] ? : 0;
        $limit = $params['limit'] ? : 30;

        $require_fields = [
            'client_id' => ['required' => ['code' => '500', 'msg' => 'client_id can not be null']],
        ];

        if ($alert_msg = check_required($params, $require_fields)) {
            return ['code' => $alert_msg['code'], 'msg' => $alert_msg['msg']];
        }

        $fields = 'o.id, o.uid, o.order_name, o.time, o.money, o.use_money_deduction';
        $where = 'o.pay_status = 1 and o.operation_id <> 5 and u.invite_code = "oauth" and u.reg_from = "' . $client_id . '"';

        if ($uid > 0) {
            $where .= ' and o.uid = ' . $uid;
        }

        if ($start_time > 0 and $end_time > $start_time) {
            $where .= ' and o.time >= "' . date('Y-m-d H:i:s', $start_time) . '"';
            $where .= ' and o.time <= "' . date('Y-m-d H:i:s', $end_time) . '"';
        }

        $sql = 'select ' . $fields . ' from ttgy_order as o left join ttgy_user as u on o.uid = u.id where ' . $where . ' order by o.id limit ' . $offset . ',' . $limit;

        $data = $this->ci->db->query($sql)->result_array();
        $result = $this->_setOrderStructForAPP($data, $params);

        return array('list' => $result);
    }

    /*
    * 用户订单列表 -- 构造
    */
    private function _setOrderStructForAPP($result, $params = array()) {
        $pay = $this->ci->config->item('pay');

        if (empty($result)) {
            return [];
        }

        //组合订单商品
        $orderids = array_column($result, 'id');
        $fields = 'order_product.id as order_product_id,order_product.product_name,order_product.product_id,order_product.order_id';

        $where_in[] = array(
            'key' => 'order_product.order_id',
            'value' => $orderids,
        );

        $join[] = array('table' => 'product', 'field' => 'product.id=order_product.product_id', 'type' => 'left');
        $order_products_res = $this->ci->order_model->selectOrderProducts($fields, '', $where_in, '', $join);
        $order_product_data = array();
        $giftTypeList = $this->_setProductType();

        foreach ($result as $rk => $rv) {
            $show_ids[$rv['id']] = $rv;
        }

        foreach ($order_products_res as &$val) {
            $v = [
                'product_id' => $val['product_id'],
                'product_name' => $val['product_name']
            ];
            $order_product_data[$val['order_id']][] = $v;
        }

        //构建数据结构
        foreach ($result as $key => $value)
        {
            //过滤订单商品
            if (!isset($order_product_data[$value['id']])) {
                unset($result[$key]);
                continue;
            }

            $result[$key]['money'] = number_format((float)($value['money'] + $value['use_money_deduction']),2,'.','');
            unset($result[$key]['use_money_deduction'], $result[$key]['id']);

            //设置订单商品
            $result[$key]['products'] = $order_product_data[$value['id']];
        }

        $rs = array();
        $rs = array_merge($rs,$result);

        return $rs;
    }

    /*
    *用户订单列表 -- 重构
    */
    public function orderNewList($params) {
        //必要参数验证
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_status'=> array('required' => array('code' => '500', 'msg' => 'order_status can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }

        //获取订单列表
        $page = !empty($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = ($page - 1) * $limit;

        //构建查询
        $filter = $this->_setOrderFilter($params,$uid);

        //返回
        if($params['order_status'] == 2)
        {
            $result = $this->ci->order_model->waitOrderList($uid,$limit, $offset);
            $result = $this->_setOrderStruct($result, $params);
            $total = $this->ci->order_model->waitOrderCount($uid);
        }
        else
        {
            $result = $this->ci->order_model->parentOrderList($filter['fields'], $filter['where'], $filter['where_in'], $filter['order_by'], $limit, $offset);
            $result = $this->_setOrderStruct($result, $params);
            $total = $this->ci->order_model->parentCountOrderList("id", $filter['where'],  $filter['where_in']);
        }

        $data = array('code'=>'200','msg'=>'','list' => $result, 'countOrder' => $total);

        //订购订单提示
        $this->ci->load->model('subscription_model');
        $sAlert = $this->ci->subscription_model->sAlert($uid, $params['connect_id']);
        if(!empty($sAlert)){
            $data['sAlert'] = $sAlert;
        }

        return $data;
    }


    /*
    * 用户订单列表 -- 筛选
    */
    private function _setOrderFilter($params,$uid)
    {
        $data = array();

        $data['fields'] = 'id,send_date,uid,order_name,time,pay_name,shtime,money,pay_status,operation_id,pay_parent_id,had_comment,has_bask,order_type,sync_status,use_money_deduction,new_pay_discount,sheet_show_price,order_status, method_money,p_order_id,stime,lyg';

        if ($params['ctime']) {
            $data['where']['time <'] = date("Y-m-d H:i:s", strtotime("-3 months"));
        }

        $data['where'] = array(
            'uid' => $uid,
            'order_status' => '1',
            //'order_type !=' => 8,
            'show_status' => '1'
        );

        $arr_operation = array();
        switch ($params['order_status']) {
            case '0':
                # code...
                break;
            //待付款
            case '1':
                $data['where']['pay_status'] = '0';
                $data['where']['pay_parent_id !='] = '4';
                $arr_operation = array('0');
                break;
            //待发货
            case '2':
                $arr_operation = array('0','1','4');
                break;
            //待收货
            case '3':
                $data['where']['pay_status'] = '1';
                $arr_operation = array('2');
                break;
            //待评价
            case '4':
                $data['where']['pay_status']  = '1';
                $data['where']['had_comment'] = '0';
                $data['where']['time >'] = date("Y-m-d", strtotime('-' . $this->can_comment_period));
                $arr_operation = array('3','6', '9');
                break;
            default:
                # code...
                break;
        }

        if (!empty($arr_operation)) {
            $data['where_in'][] = array(
                'key' => 'operation_id',
                'value' => $arr_operation
            );
        }

        //fix 过滤订单类型
        if(version_compare($params['version'], '5.2.0') > 0)
        {
            if($params['order_status'] == 4)
            {
                $data['where_in'][] = array(
                    'key' => 'order_type',
                    'value' =>array('1','2','3','4','5','7','13','14')
                );
            }
            else
            {
                if(version_compare($params['version'], '5.4.0') > 0)
                {
                    $data['where_in'][] = array(
                        'key' => 'order_type',
                        'value' =>array('1','2','3','4','5','7','13','9','10','14')
                    );
                }
                else
                {
                    $data['where_in'][] = array(
                        'key' => 'order_type',
                        'value' =>array('1','2','3','4','5','7','13','9','14')
                    );
                }
            }
        }
        else
        {
            $data['where_in'][] = array(
                'key' => 'order_type',
                'value' =>array('1','2','3','4','5','7','13','14')
            );
        }

        $data['order_by'] = 'time desc';

        return $data;
    }

    /*
    * 用户订单列表 -- 筛选
    */
    private function _setOrderFilterSearch($params,$uid)
    {
        $data = array();

        $data['fields'] = 'id,send_date,uid,order_name,time,pay_name,shtime,money,pay_status,operation_id,pay_parent_id,had_comment,has_bask,order_type,sync_status,use_money_deduction,new_pay_discount,sheet_show_price,order_status,method_money,p_order_id,stime,lyg';

        if ($params['ctime']) {
            $data['where']['time <'] = date("Y-m-d H:i:s", strtotime("-3 months"));
        }

        $data['where'] = array(
            'uid' => $uid,
            'order_status' => '1',
            //'order_type !=' => 8,
            'show_status' => '1'
        );

        $arr_operation = array();
        switch ($params['order_status']) {
            case '0':
                # code...
                break;
            //待付款
            case '1':
                $data['where']['pay_status'] = '0';
                $data['where']['pay_parent_id !='] = '4';
                $arr_operation = array('0');
                break;
            //待发货
            case '2':
                $arr_operation = array('0','1','4');
                break;
            //待收货
            case '3':
                $data['where']['pay_status'] = '1';
                $arr_operation = array('2');
                break;
            //待评价
            case '4':
                $data['where']['pay_status']  = '1';
                $data['where']['had_comment'] = '0';
                $data['where']['time >'] = date("Y-m-d", strtotime('-' . $this->can_comment_period));
                $arr_operation = array('3','6', '9');
                break;
            default:
                # code...
                break;
        }

        if (!empty($arr_operation)) {
            $data['where_in'][] = array(
                'key' => 'operation_id',
                'value' => $arr_operation
            );
        }

        //fix 过滤订单类型
        if(version_compare($params['version'], '5.2.0') > 0)
        {
            if($params['order_status'] == 4)
            {
                $data['where_in'][] = array(
                    'key' => 'order_type',
                    'value' =>array('1','2','3','4','5','7','13','14')
                );
            }
            else
            {
                if(version_compare($params['version'], '5.4.0') > 0)
                {
                    $data['where_in'][] = array(
                        'key' => 'order_type',
                        'value' =>array('1','2','3','4','5','7','13','9','10','14')
                    );
                }
                else
                {
                    $data['where_in'][] = array(
                        'key' => 'order_type',
                        'value' =>array('1','2','3','4','5','7','13','9','14')
                    );
                }
            }
        }
        else
        {
            $data['where_in'][] = array(
                'key' => 'order_type',
                'value' =>array('1','2','3','4','5','7','13','14')
            );
        }

        $data['order_by'] = 'time desc';

        return $data;
    }

    /*
    * 用户订单列表 -- 构造
    */
    private function _setOrderStruct($result, $params = array()) {
        $pay = $this->ci->config->item('pay');

        if (empty($result))
        {
            return array();
        }

        //组合订单商品
        $order_products_res = $this->orderProductItems($result);

        $order_product_data = array();
        $giftTypeList = $this->_setProductType();
        foreach ($order_products_res as &$val) {
            $val['thum_photo'] = empty($val['thum_photo']) ? '' : PIC_URL . $val['thum_photo'];
            $val['product_type'] = in_array($val['order_product_type'], $giftTypeList) ? 3 : 1;
            $order_product_data[$val['order_name']][] = $val;
        }

        //构建数据结构
        foreach ($result as $key => $value)
        {
            //过滤订单商品
            if (!isset($order_product_data[$value['order_name']])) {
                unset($result[$key]);
                continue;
            }

            //过滤－待发货
            if($params['order_status'] == 2 && $value['pay_status'] == 0 && !in_array($value['pay_parent_id'], array(4)))
            {
                unset($result[$key]);
                continue;
            }

            $result[$key]['pay_status'] = $pay[$value['pay_status']];
            $result[$key]['pay_status_key'] = $value['pay_status'];
            $result[$key]['order_status_key'] = $value['order_status'];
            $result[$key]['money'] = number_format((float)($value['money'] + $value['use_money_deduction']),2,'.','');


            //提货券 － 隐藏价格
            if ($value['sheet_show_price'] != '1' && $value['pay_parent_id'] == '6') {
                foreach ($order_product_data[$value['order_name']] as $tk => $tv) {
                    $order_product_data[$value['order_name']][$tk]['price'] = "0.00";
                }
                $result[$key]['money'] = "0.00";
                $result[$key]['goods_money'] = "0.00";
            }

            //设置订单商品
            $result[$key]['item'] = $order_product_data[$value['order_name']];

            //订单状态
            //if($params['order_status'] == 0)
            //{
                if($value['pay_status'] == 0 && $result[$key]['pay_parent_id'] != 4 && $value['operation_id'] == 0)
                {
                    $result[$key]['order_status'] = '待付款';
                }
                else if($value['pay_status'] == 1 && $result[$key]['pay_parent_id'] != 4 && in_array($value['operation_id'], array(0,1,2,4,6,9)))
                {
                    $result[$key]['order_status'] = $this->getOrderStatusAfterPay($value['operation_id'], $value['time'], $value['had_comment']);
                    if($value['order_type'] == 4 && $result[$key]['order_status'] == '待发货')
                    {
                        $result[$key]['order_status'] = '待自提';
                    }

                    if($value['order_type'] == 9 && $result[$key]['order_status'] == '待发货')
                    {
                        $result[$key]['order_status'] = '已付款';
                    }
                }
                else if($result[$key]['pay_parent_id'] == 4 && in_array($value['operation_id'], array(0,1,2,4,6,9)))
                {
                    $result[$key]['order_status'] = '货到付款';
                }
                else if($value['operation_id'] == 3)
                {
                    $result[$key]['order_status'] = '交易成功';
                }
                else if($value['operation_id'] == 5)
                {
                    $result[$key]['order_status'] = '已取消';
                }
                else
                {
                    $result[$key]['order_status'] = '';  // fix by dymyw
                }
            //}
            //else
            //{
                //$result[$key]['order_status'] = '';
            //}

            if($value['order_type'] == 9 && $value['operation_id'] != 5 && $result[$key]['lyg'] == 9 && $result[$key]['order_status']=='待评价')
            {
                $result[$key]['order_status'] = '交易成功';
            }

            //订单操作
            $result[$key]['btn']  = array();

            //can pay
            if (isset($params['version']) && strcmp($params['version'], '3.5.0') >= 0 ) {
                $online_pay = array(1, 2, 3, 5, 7, 8, 9, 11, 12,13,15);  //add 余额支付
            }
            else
            {
                $online_pay = array(1, 2, 3, 7, 8, 9, 11, 12,13,15);
            }
            if ($value['pay_status'] == '0' && in_array($value['pay_parent_id'], $online_pay) && $value['operation_id'] == '0') {
                $result[$key]['btn']['can_pay'] = 1;
            } else {
                $result[$key]['btn']['can_pay'] = 0;
            }

            //取消订单
            if (in_array($value['order_type'], array(3, 4)))
            {
                if ($value['operation_id'] == '0' || $value['operation_id'] == '1') {
                    //if ($value['pay_parent_id'] == '4' || $value['pay_parent_id'] == '5' || $value['pay_status'] == '0') {
                        if ($value['sync_status'] != '0') {
                            if(version_compare($params['version'], '5.7.0') >= 0)
                            {
                                $result[$key]['btn']['can_cancel'] = 1;
                            }
                            else
                            {
                                $result[$key]['btn']['can_cancel'] = 0;
                            }
                        } else {
                            $result[$key]['btn']['can_cancel'] = 1;
                        }
                    // } else {
                    //     $result[$key]['btn']['can_cancel'] = 0;
                    // }
                } else {
                    $result[$key]['btn']['can_cancel'] = 0;
                }
            }
            else
            {
                if ($value['operation_id'] == '0' || $value['operation_id'] == '1') {
                    if ($value['time'] >= date('Y-m-d', strtotime('-2 months'))) {
                        $result[$key]['btn']['can_cancel'] = 1;
                    } else {
                        $result[$key]['btn']['can_cancel'] = 0;
                    }
                } else {
                    $result[$key]['btn']['can_cancel'] = 0;
                }
            }

            //再次购买
            if($result[$key]['order_type'] == 1 && $value['operation_id'] == 3)
            {
                //$result[$key]['btn']['can_replace'] = 1;
                $result[$key]['btn']['can_replace'] = 0;
            }
            else
            {
                $result[$key]['btn']['can_replace'] = 0;
            }

            //查询物流
            if($result[$key]['sync_status'] == 1 && in_array($result[$key]['operation_id'], array(1,2,3,4,6,9)))
            {
                if ($value['time'] <= date('Y-m-d', strtotime('-1 months')))
                {
                    $result[$key]['btn']['can_check_logistic'] = 0;
                }
                else
                {
                    $result[$key]['btn']['can_check_logistic'] = 1;
                }
            }
            else
            {
                $result[$key]['btn']['can_check_logistic'] = 0;
            }

            //评价商品
            $evl = $this->ci->evaluation_model->get_info($value['uid'], $value['order_name']);
            // fix by dymyw，评价过物流也可以继续评价商品
            if(!in_array($result[$key]['operation_id'], array(3,6,9)) || $value['had_comment'] == '1'
                || ($value['time'] < date("Y-m-d", strtotime('-' . $this->can_comment_period))))
            {
                $result[$key]['btn']['can_comment'] = 0;
            }
            else
            {
                if(count($order_product_data[$value['order_name']]) == 1 && $order_product_data[$value['order_name']][0]['product_type'] == 3)
                {
                    $result[$key]['btn']['can_comment'] = 1;
                }
                else
                {
                    $result[$key]['btn']['can_comment'] = 1;
                }
            }

            //确认收货
            if (in_array($result[$key]['operation_id'], array(2)))
            {
                $result[$key]['btn']['can_confirm_receive'] = 1;
            }
            else
            {
                $result[$key]['btn']['can_confirm_receive'] = 0;
            }

            //查询退款
            $has_refund = $this->ci->db->select('has_refund')->from('ttgy_order_refund')->where(array('order_id'=>$value['id']))->get()->row_array();
            if($value['operation_id'] == 5 && $value['pay_status'] == '1')
            {
                $result[$key]['btn']['can_has_refund'] = 1;
            }
            else
            {
                $result[$key]['btn']['can_has_refund'] = 0;
            }

            //申请售后
            if (in_array($result[$key]['operation_id'], array(6,9)) && in_array($result[$key]['order_type'], array(1,2,6,13)))
            {
                $result[$key]['btn']['can_report_issue'] = 0;
            }
            else
            {
                $result[$key]['btn']['can_report_issue'] = 0;
            }

            //联系客服
            $bs = mb_substr($result[$key]['order_name'],0,1);
            if($bs == 'P')
            {
                if(in_array($result[$key]['operation_id'], array(3,6,9)))
                {
                    $result[$key]['btn']['can_customer_service'] = 0;
                }
                else if($result[$key]['operation_id'] == 5)
                {
                    if($value['pay_status'] == 1)
                    {
                        $result[$key]['btn']['can_customer_service'] = 1;
                    }
                    else
                    {
                        $result[$key]['btn']['can_customer_service'] = 0;
                    }
                }
                else
                {
                    $result[$key]['btn']['can_customer_service'] = 1;
                }
            }
            else
            {
                $result[$key]['btn']['can_customer_service'] = 0;
            }

            //删除订单
            if ($value['operation_id'] == 3 || $value['operation_id'] == 5)
            {
                $result[$key]['btn']['can_del'] = 1;
            }
            else
            {
                $result[$key]['btn']['can_del'] = 0;
            }

            //倒计时
            if(in_array($value['order_type'], array(3)) && $value['pay_status'] == 1 && in_array($value['operation_id'], array(0,1,2,4)))
            {
                $result[$key]['start_time'] = date('Y-m-d H:i:s');
                $result[$key]['end_time'] = $this->showTimes($value['shtime'],$value['stime']);
                $result[$key]['show_time_state'] = 1;
            }
            else
            {
                $result[$key]['start_time'] = date('Y-m-d H:i:s');
                $result[$key]['end_time'] = '';
                $result[$key]['show_time_state'] = 0;
            }

            if($value['order_type'] == 9)
            {
                //$result[$key]['gift_url'] = 'http://m.fruitday.com/statics/gifts/order-detail.html?order_no='.$value['order_name'];
                $result[$key]['gift_url'] = 'https://spa.fruitday.com/sendGift/orderDetail?no='.$value['order_name'];

                $result[$key]['gift_btn_detail'] = 1;
                $result[$key]['gift_btn_send'] = 0;
                $result[$key]['gift_btn_del'] = 0;
                $result[$key]['gift_btn_cancel'] = 0;

                if($result[$key]['pay_status_key'] == 0)
                {
                    $result[$key]['gift_btn_detail'] = 0;
                    $result[$key]['gift_btn_send'] = 0;
                    $result[$key]['gift_btn_cancel'] = 1;
                }
                
                if($result[$key]['pay_status_key'] == 1)
                {
                    $result[$key]['gift_btn_detail'] = 0;
                    $result[$key]['gift_btn_send'] = 1;
                }

                if($result[$key]['lyg'] == 9)
                {
                    $result[$key]['gift_btn_detail'] = 1;
                    $result[$key]['gift_btn_send'] = 0;
                }

                if($result[$key]['operation_id'] == 5)
                {
                    $result[$key]['gift_btn_detail'] = 0;
                    $result[$key]['gift_btn_send'] = 0;
                    $result[$key]['gift_btn_del'] = 1;
                    $result[$key]['gift_btn_cancel'] = 0;
                }
            }

            if($value['order_type'] == 10)
            {
                $result[$key]['giveaway_url'] = 'http://m.fruitday.com/statics/giveaway/order-detail.html?orderName='.$value['order_name'];

                $result[$key]['giveaway_btn_detail'] = 0;
                $result[$key]['giveaway_btn_pay'] = 0;
                $result[$key]['giveaway_btn_cancel'] = 0;
                $result[$key]['giveaway_btn_customer'] = 0;
                $result[$key]['giveaway_btn_del'] = 0;

                if($result[$key]['pay_status_key'] == 0)
                {
                    $result[$key]['giveaway_btn_pay'] = 1;
                    $result[$key]['giveaway_btn_cancel'] = 1;
                }

                if($result[$key]['pay_status_key'] == 1)
                {
                    $result[$key]['giveaway_btn_customer'] = 1;
                    $result[$key]['giveaway_btn_del'] = 1;
                }

                if($result[$key]['operation_id'] == 5)
                {
                    $result[$key]['giveaway_btn_detail'] = 1;
                    $result[$key]['giveaway_btn_del'] = 1;
                    $result[$key]['giveaway_btn_pay'] = 0;
                }
            }
        }

        $rs = array();
        $rs = array_merge($rs,$result);

        return $rs;
    }


    /*
    * 隐藏订单
    */
    public function orderHide($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        $update_data = array(
            'show_status' => '0'
        );
        $where = array('order_name' => $params['order_name'], 'operation_id' => array('3', '5'), 'uid' => $uid);
        $result = $this->ci->order_model->update_order($update_data, $where);

        if ($result) {
            return array("code" => "200", "msg" => 'succ');
        } else {
            return array("code" => "300", "msg" => 'failed');
        }
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
    * 收银台－周期购
    */
    public function payCenter($params)
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
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }

        //获取订单信息
        $order_name = $params['order_name'];
        $order = $this->ci->subscription_order_model->dump(array('order_name' => $order_name, 'uid' => $uid));
        if (empty($order)) {
            $msg = array('code' => 300, 'msg' => '用户订单不存在');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        if ($order['status'] == 1 || $order['pay_status'] == 2) {
            $msg = array('code' => 300, 'msg' => '订单已支付，等待确认订单中');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        $this->ci->db->trans_begin();

        //默认支付方式
        if($order['pay_id'] == 0 && $order['pay_parent_id'] == 0)
        {
            $up_pays = array(
                'pay_id'=>0,
                'pay_parent_id'=>1,
                'pay_name'=>'支付宝'
            );
            $this->ci->subscription_order_model->update($order['id'],$up_pays);

            $order['pay_id'] = 0;
            $order['pay_parent_id'] = 1;
        }

        $allow_pay = array(1,5,7,8);
        if(!in_array($order['pay_parent_id'],$allow_pay))
        {
            return array("code" => "300", "msg" => "您选择的支付方式无法支付,请重新选择支付方式");
        }

        //构建结构
        //check - 冻结
        $check = $this->ci->user_model->check_money_identical($uid);
        if ($check === false) {
            $this->ci->user_model->freeze_user($uid);
            return array("code" => "300", "msg" => "您的账号异常，已被冻结，请联系客服");
        }

        $user = $this->ci->user_model->getUser($uid);
        $order['user_money'] = $user['money'];
        $order_money = number_format((float)($order['money'] + $order['use_money_deduction']),2,'.','');
        $order['is_can_balance'] = 1; //默认可以使用余额

        if ($order['user_money'] == '0.00')
        {
            $data = array(
                'money' => $order_money,
                'use_money_deduction' => $order['user_money']
            );
            $where = array(
                'order_name' => $params['order_name']
            );
            $this->ci->subscription_order_model->update_order($data, $where);

            $order['user_can_money'] = number_format('0', 2, '.', '');
            $order['need_online_pay'] = number_format($order_money, 2, '.', '');
            $order['is_pay_balance'] = 0;
            $order['need_send_code'] = 0;
        }
        else if ($order['user_money'] > 0)
        {
            //是否可以全额抵扣
            if($order['user_money'] >= $order_money)
            {
                $data = array(
                        'use_money_deduction' => 0.00,
                        'new_pay_discount' => 0.00,
                        'money' => $order_money
                );

                $where = array(
                    'order_name' => $params['order_name']
                );
                $this->ci->subscription_order_model->update_order($data, $where);

                $order['user_can_money'] = number_format(($order['user_money'] - $order['money'] - $order['use_money_deduction']), 2, '.', '');

                if ($order['pay_parent_id'] == 5)
                {
                    $order['use_money_deduction'] = number_format($data['use_money_deduction'], 2, '.', '');
                    $order['need_online_pay'] = number_format(0, 2, '.', '');
                    $order['need_send_code'] = 1;
                }
                else
                {
                    $order['use_money_deduction'] = number_format($data['use_money_deduction'], 2, '.', '');
                    $order['need_online_pay'] = number_format($order_money, 2, '.', '');
                    $order['need_send_code'] = 0;
                }
                $order['is_pay_balance'] = 1;
            }
            else
            {
                if ($order['is_can_balance'] == 1)
                {
                    $data = array(
                        'use_money_deduction' => $order['user_money'],
                        'money' => $order_money -  $order['user_money']
                    );

                    $where = array(
                        'order_name' => $params['order_name']
                    );
                    $this->ci->subscription_order_model->update_order($data, $where);

                    $order['use_money_deduction'] = number_format($data['use_money_deduction'], 2, '.', '');
                    $order['user_can_money'] = number_format('0', 2, '.', '');
                    $order['need_online_pay'] = number_format($order_money - $order['user_money'], 2, '.', '');
                    $order['is_pay_balance'] = 0;
                    $order['need_send_code'] = 1;
                }
                else
                {
                    $order['use_money_deduction'] = number_format('0', 2, '.', '');
                    $order['user_can_money'] = number_format('0', 2, '.', '');
                    $order['need_online_pay'] = number_format($order_money, 2, '.', '');
                    $order['is_pay_balance'] = 0;
                    $order['need_send_code'] = 0;
                }
            }
        }

        //支付方式
        $this->ci->load->bll($params['source'] . '/region');
        $obj = 'bll_' . $params['source'] . '_region';

        $order_address = $this->ci->subscription_order_address_model->dump(array('order_id' => $order['id']));
        $province = $order_address['province'];

        if (!empty($province)) {
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
            unset($pay_arr['bank']);
            if($params['platform'] == 'android' && $params['app_version'] != '4.2.2'){
                foreach ($pay_arr['online']['pays'] as $key => $value) {
                    if($value['pay_parent_id']=='7'){
                        unset($pay_arr['online']['pays'][$key]);
                    }
                }
            }
            $order['payments'] = $pay_arr;
        }

        //订单金额
        $order['money'] = number_format($order_money, 2, '.', '');

        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "支付失败,请重新支付");
        } else {
            $this->ci->db->trans_commit();
        }

        return $order;
    }

    /*
   * 收银台－周期购(选择支付)
   */
    function payChosePayment($params) {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'pay_parent_id' => array('required' => array('code' => '300', 'msg' => '请选择支付方式')),
            'pay_id' => array('required' => array('code' => '300', 'msg' => '请选择支付方式')),
            'region_id' => array('required' => array('code' => '500', 'msg' => 'region id can not be null')),
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

        $pay_array = $this->ci->config->item("pay_array");
        $pay_parent_id = $params['pay_parent_id'];
        $pay_id = $params['pay_id'];

        //支付方式合法性验证
        if (!isset($pay_array[$pay_parent_id])) {
            return array('code' => '300', 'msg' => '支付方式错误，请返回重新操作');
        }
        $parent = $pay_array[$pay_parent_id]['name'];
        $son_name = '';

        if (!empty($pay_array[$pay_parent_id]['son'])) {
            $son = $pay_array[$pay_parent_id]['son'];
            if (!isset($son[$pay_id])) {
                return array('code' => '300', 'msg' => '支付方式错误，请返回重新操作');
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

        //处理已创建的订单，变更支付方式
        $order_name = $params['order_name'];
        $order = $this->ci->subscription_order_model->dump(array('order_name' => $order_name, 'uid' => $uid));
        if (empty($order)) {
            return array('code' => 300, 'msg' => '用户订单不存在');
        }

        //已支付
        if ($order['status'] == 1 || $order['pay_status'] == 2) {
            return array('code' => 300, 'msg' => '请稍后，等待订单支付确认');
        }

        //事务开始
        $this->ci->db->trans_begin();

        $up_pays = array(
            'pay_id'=>$pay_id,
            'pay_parent_id'=>$pay_parent_id,
            'pay_name'=>$pay_name
        );
        $this->ci->subscription_order_model->update($order['id'],$up_pays);

        $info = array();
        $info['need_online_pay'] = number_format((float)($order['money']), 2, '.', '');
        $info['order_money'] = number_format((float)($order['money'] + $order['use_money_deduction'] + $order['new_pay_discount']), 2, '.', '');

        if($order['use_money_deduction'] >0)
        {
            $info['need_send_code'] = 1;
        }
        else
        {
            $info['need_send_code'] = 0;
        }

        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "选择支付方式失败，请重新选择支付");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => 'success', 'info' => $info);
    }

    /*
   * 收银台－周期购(使用余额)
   */
    function payUseBalance($params) {
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

        $order = $this->ci->subscription_order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid));
        if (empty($order)) {
            return array('code' => 300, 'msg' => '用户订单不存在');
        }

        if ($order['status'] == 1 || $order['pay_status'] == 2) {
            return array('code' => 300, 'msg' => '请稍后，等待订单支付确认');
        }

        $user = $this->ci->user_model->getUser($uid);
        $user_money = $user['money'];

        $new_pay_discount = $order['new_pay_discount'];
        $order_money = $order['money'] + $new_pay_discount + $order['use_money_deduction']; //订单金额需要先加上支付抵扣金额
        $use_money_deduction = $order['use_money_deduction'];

        $this->ci->db->trans_begin();

        $info = array();
        if ($user_money > 0)
        {
            $check = $this->ci->user_model->check_money_identical($uid);
            if ($check === false) {
                $this->ci->user_model->freeze_user($uid);
                return array("code" => "300", "msg" => "您的账号异常，已被冻结，请联系客服");
            }

            if ($user_money >= $order_money)
            {
                $pay_array = $this->ci->config->item("pay_array");
                $pay_parent_id = 5;
                $pay_id = 0;
                $pay_name = $pay_array[$pay_parent_id]['name'];

                $up_pays = array(
                    'pay_id'=>$pay_id,
                    'pay_parent_id'=>$pay_parent_id,
                    'pay_name'=>$pay_name,
                );
                $this->ci->subscription_order_model->update($order['id'],$up_pays);

                $data = array(
                    'use_money_deduction' => 0.00,
                    'new_pay_discount' => 0.00,
                    'money' => $order_money,
                );
                $where = array(
                    'order_name' => $params['order_name']
                );
                $this->ci->subscription_order_model->update_order($data, $where);

                $info['order_name'] = $params['order_name'];
                $info['user_money'] = number_format((float)($user['money']), 2, '.', '');
                $info['user_can_money'] = number_format((float)($user['money'] - $order_money), 2, '.', '');
                $info['order_money'] = number_format((float)$order_money, 2, '.', '');
                $info['use_money_deduction'] = $use_money_deduction;
                $info['need_online_pay'] = number_format((float)$data['use_money_deduction'], 2, '.', '');;
                $info['is_pay_balance'] = 1;
                $info['need_send_code'] = 1;

                $info['selectPayments'] = array(
                    'pay_parent_id' => '5',
                    'pay_id' => '0',
                    'pay_name' => '账户余额支付',
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
                    'money' => ($order_money - $new_pay_discount) - $user_money
                );
                $where = array(
                    'order_name' => $params['order_name']
                );
                $this->ci->subscription_order_model->update_order($data, $where);

                $info['order_name'] = $params['order_name'];
                $info['user_money'] = number_format((float)($user['money']), 2, '.', '');
                $info['user_can_money'] = number_format((float)($user['money'] - $data['use_money_deduction']), 2, '.', '');
                $info['order_money'] = number_format((float)$order_money, 2, '.', '');
                $info['use_money_deduction'] = $data['use_money_deduction'];
                $info['need_online_pay'] = number_format((float)($order_money - $data['use_money_deduction']), 2, '.', '');
                $info['is_pay_balance'] = 0;
                $info['need_send_code'] = 1;

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
        }
        else
        {
            return array('code' => 300, 'msg' => '用户余额不足');
        }

        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "使用余额失败，请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => 'success', 'info' => $info);
    }


    /*
     * 收银台－周期购(取消使用余额)
     */
    function payCancelUseBalance($params) {
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

        $order = $this->ci->subscription_order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid));
        if (empty($order)) {
            return array('code' => 300, 'msg' => '用户订单不存在');
        }

        if ($order['status'] == 1 || $order['pay_status'] == 2) {
            return array('code' => 300, 'msg' => '请稍后，等待订单支付确认');
        }

        $user = $this->ci->user_model->getUser($uid);
        $order_money = $order['money'];
        $use_money_deduction = $order['use_money_deduction'];
        $new_pay_discount = $order['new_pay_discount'];
        $order_money = $order_money + $new_pay_discount + $use_money_deduction;

        $this->ci->db->trans_begin();

        $info = array();
        if ($use_money_deduction > 0) {
            $check = $this->ci->user_model->check_money_identical($uid);
            if ($check === false) {
                $this->ci->user_model->freeze_user($uid);
                return array("code" => "300", "msg" => "您的账号异常，已被冻结，请联系客服");
            }

            $data = array(
                'use_money_deduction' => 0.00,
                'new_pay_discount' => 0.00,
                'money' => $order['money'] + $use_money_deduction + $new_pay_discount
            );
            $where = array(
                'order_name' => $params['order_name']
            );
            $this->ci->subscription_order_model->update_order($data, $where);

            $info['order_name'] = $params['order_name'];
            $info['user_money'] = number_format((float)$user['money'], 2, '.', '');
            $info['user_can_money'] = number_format((float)($user['money']), 2, '.', '');
            $info['use_money_deduction'] = number_format((float)$data['use_money_deduction'], 2, '.', '');
            $info['need_online_pay'] = number_format((float)($order_money - $data['use_money_deduction']), 2, '.', '');
            $info['is_pay_balance'] = 0;
            $info['order_money'] = number_format((float)$order_money, 2, '.', '');
            $info['need_send_code'] = 0;

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
            $data = array(
                'money' => $order_money,
            );
            $where = array(
                'order_name' => $params['order_name']
            );
            $this->ci->subscription_order_model->update_order($data, $where);

            $info['order_name'] = $params['order_name'];
            $info['user_money'] = number_format((float)($user['money']), 2, '.', '');
            $info['user_can_money'] = number_format((float)($user['money']), 2, '.', '');
            $info['order_money'] = number_format((float)($order_money), 2, '.', '');
            $info['use_money_deduction'] = number_format((float)($use_money_deduction), 2, '.', '');
            $info['need_online_pay'] = number_format((float)($order_money), 2, '.', '');
            $info['is_pay_balance'] = 1;
            $info['need_send_code'] = 0;

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

        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "取消使用余额失败，请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => 'success', 'info' => $info);
    }

    /*
     * 收银台 － 周期购(验证支付)
     */
    function payCheckOrder($params)
    {
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

        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();
        if (empty($uid)) {
            $msg = array('code' => 300, 'msg' => '用户登录异常，请重新登录');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        $order = $this->ci->subscription_order_model->dump(array('order_name' => $params['order_name'], 'uid' => $uid));
        if (empty($order)) {
            $msg = array('code' => 300, 'msg' => '用户订单不存在');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        $user = $this->ci->user_model->getUser($uid);

        //余额支付短信验证
        if($order['pay_parent_id'] == 5 || $order['use_money_deduction'] > 0)
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

        //times
        if ($order['status'] == 5) {
            $msg = array('code' => 300, 'msg' => '订单已取消,请重新下单');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        if ($order['status'] == 2) {
            $msg = array('code' => 300, 'msg' => '订单已暂停,请重新下单');
            $this->checkLog($params['order_name'].'|'.$params['platform'], $msg);
            return $msg;
        }

        //status
        if ($order['status'] == 1 || $order['pay_status'] == 2) {
            $msg = array('code' => 300, 'msg' => '订单已支付,请勿重复支付订单');
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

        return array('code' => 200, 'msg' => 'succ');
    }

    /**
     * 获得订单状态的文字描述。
     * @param  int $operation_id
     * @param  int $pay_parent_id
     * @param  int $pay_status
     * @return string
     */
    public function getOrderStatusText($operation_id, $pay_parent_id, $pay_status, $time, $had_comment)
    {
        $delivering_status_list = [0,1,2,4,6,9];
        $result = '';

        if ($pay_status == 0 && $pay_parent_id != 4 && $operation_id == 0) {
            $result = '待付款';
        } else if ($pay_status == 1 && $pay_parent_id != 4 && in_array($operation_id, $delivering_status_list)) {
            $result = $this->getOrderStatusAfterPay($operation_id, $time, $had_comment);
        } else if ($pay_parent_id == 4 && in_array($operation_id, $delivering_status_list)) {
            $result = '货到付款';
        } else if ($operation_id == 3) {
            $result = '交易成功';
        } else if ($operation_id == 5) {
            $result = '已取消';
        }

        return $result;
    }

    public function editSendTime($order_id,$area_code,$send_ware_id,$open_flash_send = false,$tms_region_type=1,$is_day_night=0,$delivery_end_time){
        $store_id = $this->ci->order_model->dump(array('id'=>$order_id),'store_id');
        $store_id = $store_id['store_id'];
        if(empty($store_id)) return false;
        $order_products = $this->ci->order_product_model->getList('*',array('order_id'=>$order_id));
        $cart_product = array();
        foreach ($order_products as $key => $value) {
            $product_one = array();
            $product_one['product_id'] = $value['product_id'];
            $product_one['store_id'] = $store_id;
            $product_one['name'] = $value['product_name'];
            $product_one['is_oversale'] = $value['is_oversale'];
            $cart_product[] = $product_one;
        }
        $tmp_cart_info = array();
        $tmp_cart_info['products'] = $cart_product;
        return $this->package($tmp_cart_info,$area_code,$send_ware_id,$open_flash_send,$tms_region_type,$is_day_night,$delivery_end_time);
    }

    //拆包裹
    public function package($cart_info,$area_code,$send_ware_id,$open_flash_send = false,$tms_region_type=1,$is_day_night=0,$delivery_end_time){
        //$is_day_night = 1;
        $this->ci->load->model('b2o_store_product_model');
        $this->ci->load->model('b2o_store_model');
        $p_data = array();
        $packages = array();
        $products = $cart_info['products'];
        $store_infos = array();
        $no_send_product = array();
        $send_time_datas = array();
        $store_ids = array();
        foreach ($products as $product) {
            $store_ids[] = $product['store_id'];
        }
        $store_ids = array_unique($store_ids);
        $sid2cangid = $this->ci->b2o_store_model->getCangByStore($store_ids,$area_code);

        foreach ($products as $product) {
            if(isset($sid2cangid[$product['store_id']]) && $sid2cangid[$product['store_id']]){
                $send_ware_id = $sid2cangid[$product['store_id']];
            }
            $send_limit = $this->getSendLimit($send_ware_id,$ware_open_flash_send);
            $filter = array();
            $filter['product_id'] = $product['product_id'];
            $store_id = $filter['store_id'] = $product['store_id'];
            $join = array();
            $join[] = array(
                'name'=>'b2o_delivery_tpl',
                'cond'=>'b2o_delivery_tpl.tpl_id=b2o_store_product.delivery_template_id',
                //'type'=>'left',
                );
            $result = $this->ci->b2o_store_product_model->getAllList('b2o_delivery_tpl.tpl_id,b2o_delivery_tpl.type,b2o_delivery_tpl.rule',$filter,$join);
            $result = $result[0];
            if(empty($result)){
                return array('code'=>300,'msg'=>'因配送原因，['.$product['name'].'] 暂时无法购买');
            }
            $rule = json_decode($result['rule'],true);
            if(empty($rule['c_type'])) $rule['c_type'] = 0;
            if($result['type'] == 1 && $rule['time'] > 1 && ! $product['is_oversale']){
                $product['delivery_desc'] = $rule['time']."小时起送";
            }
            $package_data = array();
            $package_data['package_type'] = $result['type'];
            $package_data['product'] = $product;
            $package_data['rule'] = $rule;
            $store_infos[$store_id] or $store_infos[$store_id] = $this->ci->b2o_store_model->dump(array('id'=>$store_id));
            $store_info = $store_infos[$store_id];
            $store_info['ware_open_flash_send'] = $ware_open_flash_send;
            $package_data = $this->getItemSendTimes($package_data,$send_limit,$store_info,$tms_region_type,$is_day_night,$delivery_end_time);
            if(empty($package_data['send_time'])){
                $no_send_product[] = $product['name'];
            }

            if($result['type'] == 1){
                if($open_flash_send){
                    $packages[$package_data['send_day'].'-'.$result['type'].'-'.$rule['time']]['item'][] = $package_data;
                    $packages[$package_data['send_day'].'-'.$result['type'].'-'.$rule['time']]['package_type'] = $result['type'];
                    $packages[$package_data['send_day'].'-'.$result['type'].'-'.$rule['time']]['cang_id'] = $send_ware_id;
                    $packages[$package_data['send_day'].'-'.$result['type'].'-'.$rule['time']]['tag'] = $package_data['send_day'].'-'.$result['type'].'-'.$rule['time'];
                }else{
                    $send_time_datas = $this->tag_send_time($package_data['send_time'],$send_time_datas);
                    $tag = $send_time_datas['check_tag'];
                    $packages[$package_data['send_day'].'-'.$result['type'].'-'.$tag]['item'][] = $package_data;
                    $packages[$package_data['send_day'].'-'.$result['type'].'-'.$tag]['package_type'] = $result['type'];
                    $packages[$package_data['send_day'].'-'.$result['type'].'-'.$tag]['cang_id'] = $send_ware_id;
                    $packages[$package_data['send_day'].'-'.$result['type'].'-'.$tag]['tag'] = $package_data['send_day'].'-'.$result['type'].'-'.$tag;
                }
            }elseif($result['type'] == 2){
                $tag = $result['type'].'-'.$rule['c_type'].'-'.$rule['time_mode'];
                $packages[$tag]['item'][] = $package_data;
                $packages[$tag]['package_type'] = $result['type'];
                $packages[$tag]['cang_id'] = $send_ware_id;
                $packages[$tag]['tag'] = $tag;
            }else{
                $packages[$result['tpl_id']]['item'][] = $package_data;
                $packages[$result['tpl_id']]['package_type'] = $result['type'];
                $packages[$result['tpl_id']]['cang_id'] = $send_ware_id;
                $packages[$result['tpl_id']]['tag'] = $result['tpl_id'];
            }
        }
        if($no_send_product){
            return array('code'=>300,'msg'=>"您挑选的商品".implode(',', $no_send_product)."由于运能原因暂时无法配送，请选择其他商品购买");
        }
        $packages = $this->getPackageMethodMoney($packages,$area_code,$tms_region_type);
        $packages = $this->getPackageSendTimes($packages);
        $packages = array_values($packages);
        $sort_arr = array();
        foreach($packages as $key=>$val)
        {
            if(empty($val['send_time']))
            {
                //$packages[$key]['send_time'] = (object)array();
                return array('code'=>300,'msg'=>'由于运能原因，您购买的商品暂时无法配送');
            }
            $sort_arr[] = $val['package_type'];
        }
        array_multisort($sort_arr, SORT_ASC,$packages);
        return $packages;
    }

    private function getPackageMethodMoney($packages,$area_code,$tms_region_type=1){
        $this->ci->load->model('area_model');
        $provinceInfo = $this->ci->area_model->getProadcodeByAdcode($area_code);
        $provinceCode = $provinceInfo['adcode'];
        foreach ($packages as $key => $package) {
            $package_money = 0;
            $free_method = false;
            $weight = 0;
            $disamount = 0;
            foreach ($package['item'] as $package_key => $package_data) {
                $product = $package_data['product'];
                $package_money += $product['amount'];
                if($product['free_post'] == 1){
                    $free_method = true;
                }
                $weight += $product['weight'] * $product['qty'];
                $disamount = bcadd($disamount, $product['discount'],2);
                $packages[$key]['item'][$package_key]['product']['weight'] = '';//'约'.$product['weight'];
            }
            $packages[$key]['amount'] = $package_money;
            $packages[$key]['weight'] = $weight.'kg';
            $packages[$key]['count'] = count($package['item']);
            $packages[$key]['disamount'] = $disamount;
            if($free_method == true){
                $packages[$key]['method_money'] = 0;
            }elseif($package['package_type'] == 1 && $tms_region_type == 1){  //T+0 闪电送
                if($package_money < 69){
                    $packages[$key]['method_money'] = 5;
                }else{
                    $packages[$key]['method_money'] = 0;
                }
            }else{   //T+1  预售  当日达
                if($provinceCode == 310000 && ($area_code != 310230 && $area_code != 310151)){  //上海除崇明
                    if($package_money < 100){
                        $packages[$key]['method_money'] = 10;
                    }else{
                        $packages[$key]['method_money'] = 0;
                    }
                }else{
                    $packages[$key]['method_money'] = $this->get_post_fee_new($weight,$area_code,$package_money);
                }
            }
            $packages[$key]['total_amount'] = bcsub(bcadd($packages[$key]['amount'], $packages[$key]['method_money'],2),$packages[$key]['disamount'],2);
        }
        return $packages;
    }

    private function get_post_fee_new($weight,$area_code,$package_money) {
        $this->ci->load->model('area_model');
        $area_freight_info = $this->ci->area_model->getCityByAreacode($area_code);
        if(empty($area_freight_info['send_role'])) {
            $send_role = unserialize('a:4:{i:0;a:5:{s:3:"low";i:0;s:5:"hight";s:5:"49.99";s:12:"first_weight";i:9999;s:18:"first_weight_money";i:20;s:19:"follow_weight_money";i:0;}i:1;a:5:{s:3:"low";i:50;s:5:"hight";s:5:"99.99";s:12:"first_weight";i:9999;s:18:"first_weight_money";i:10;s:19:"follow_weight_money";i:0;}i:2;a:5:{s:3:"low";i:100;s:5:"hight";s:6:"199.99";s:12:"first_weight";i:8;s:18:"first_weight_money";i:0;s:19:"follow_weight_money";i:2;}i:3;a:5:{s:3:"low";i:200;s:5:"hight";i:9999;s:12:"first_weight";i:9999;s:18:"first_weight_money";i:0;s:19:"follow_weight_money";i:0;}}');
        }else{
            $send_role = unserialize($area_freight_info['send_role']);
        }

        foreach ($send_role as $key => $value) {
            if($value['hight']==9999){//运费规则上限,每多x元，首重增加ykg
                $method_money = 0;
                break;
            }else if($package_money >= $value['low'] && $package_money <= $value['hight']){

                if($weight <= $value['first_weight']) {
                    $method_money = $value['first_weight_money'];
                    break;
                }else {
                    $method_money = $value['first_weight_money'] + ceil(($weight - $value['first_weight']))*$value['follow_weight_money'];
                    break;
                }
            }
        }
        return $method_money;
    }

    public function getPackageSendTimes($packages){
        foreach ($packages as &$package) {
            $send_time = array();
            $default_send_time = array();
            foreach ($package['item'] as $key => &$package_data) {
                //if($package['package_type'] == 1){
                    $all_times = array();
                    if(empty($send_time)){
                        $send_time = $package_data['send_time'];
                    }else{
                        $send_time = $this->mult_array_intersect_assoc($send_time,$package_data['send_time']);
                    }
                    foreach ($package_data['send_time'] as $sh_time => $value) {
                        foreach ($value as $s_time => $v) {
                            $all_times[] = $sh_time.'|'.$s_time;
                        }
                    }
                    foreach ($all_times as $value) {
                        $sh_s_time = explode('|', $value);
                        if(!isset($send_time[$sh_s_time[0]])){

                        }elseif(!isset($send_time[$sh_s_time[0]][$sh_s_time[1]])){
                            $send_time[$sh_s_time[0]][$sh_s_time[1]]['disable'] = 'disable';
                        }
                    }

                    if(empty($default_send_time)){
                        $default_send_time = $package_data['default_send_time'];
                    }else{
                        $default_send_time = array_intersect_assoc($default_send_time,$package_data['default_send_time']);
                    }
                    if(empty($default_send_time)){
                        $default_send_time = array('shtime'=>'','stime'=>'');
                    }
                    if($package['package_type'] == 1){
                        $default_send_time = array('shtime'=>'','stime'=>'');
                    }
                    $package['send_time'] = $send_time;
                    $package['default_send_time'] = $default_send_time;
                // }else{
                //     $package['send_time'] = $package_data['send_time'];
                // }
                $package['item'][$key] = $package_data['product'];
                unset($package_data['send_time'],$package_data['package_type'],$package_data['rule'],$package_data['send_day'],$package_data['product'],$package_data['default_send_time']);
            }
            $package['send_time'] = array_slice($package['send_time'], 0, 5, true);
            if($package['package_type'] == 1){
                $package['zt_send_time'] = $package['send_time'];
                foreach ($package['zt_send_time'] as $zt_shtime => $zt_times) {
                    foreach ($zt_times as $zt_stime => $zt_status) {
                        $package['zt_send_time'][$zt_shtime][$zt_stime]['disable'] = 'undisable';
                        if(isset($zt_status['is_flash']) && $zt_status['is_flash'] == 'true'){
                            unset($package['zt_send_time'][$zt_shtime][$zt_stime]);
                        }
                    }
                }
            }else{
                $package['zt_send_time'] = array();
            }
        }
        return $packages;
    }

    function mult_array_intersect_assoc($arr1=array(),$arr2=array(),&$intersect = array()){
        $mm = array();
        if(is_array($arr1) && is_array($arr2)){
            $mm = array_intersect_assoc($arr1,$arr2);
        }
        foreach($mm as $key => $v){
            if(is_array($v)){
               $intersect[$key] = $this->mult_array_intersect_assoc($arr1[$key],$arr2[$key],$intersect[$key]);
            }else{
               $intersect[$key] = $v;
            }
        }
        is_array($intersect) and $intersect = array_filter($intersect);
        return $intersect;
    }

    function tag_send_time($send_time,$send_time_datas = array()){
        $i = 1;
        $send_time_data = array();
        if(empty($send_time_datas)){
            $send_time_data = array();
            $send_time_data['tag'] = 'tag'.$i;
            $send_time_data['send_time'] = $send_time;
            $send_time_datas['sends'][] = $send_time_data;
            $send_time_datas['check_tag'] = $send_time_data['tag'];
        }else{
            foreach ($send_time_datas['sends'] as $key => $send_time_data) {
                $new_send_time = $this->mult_array_intersect_assoc($send_time,$send_time_data['send_time']);
                if($new_send_time){
                    $send_time_datas['sends'][$key]['send_time'] = $new_send_time;
                    $send_time_datas['check_tag'] = $send_time_data['tag'];
                    break;
                }
                $i++;
            }
        }
        $send_time_data = array();
        if(empty($new_send_time)){
            $send_time_data['tag'] = 'tag'.$i;
            $send_time_data['send_time'] = $send_time;
            $send_time_datas['sends'][] = $send_time_data;
            $send_time_datas['check_tag'] = $send_time_data['tag'];
        }
        return $send_time_datas;
    }

    private function getItemSendTimes($package_data,$send_limit,$store_info,$tms_region_type=1,$is_day_night=0,$delivery_end_time=''){
        // if($tms_region_type == 9){
        //     $tms_region_type = 0;
        // }
        //$package_data_send_time = $this->getOnePackageSendTime($package_data);
        $store_id = $package_data['product']['store_id'];
        $product_id = $package_data['product']['product_id'];
        $exp_date = $this->ci->b2o_store_product_model->getExpiration($product_id,$store_id);
        if($package_data['package_type'] == 3){ //预售商品保质期无限
            $exp_date = '9999-12-31';
        }
        $is_oversale = isset($package_data['product']['is_oversale'])?$package_data['product']['is_oversale']:0;
        $rule = $package_data['rule'];
        $open_time = intval($store_info['open_time']);
        $close_time = intval($store_info['close_time']);
        if($delivery_end_time){
            $close_time = intval($delivery_end_time);
        }
        $am_cutoff_time = intval($store_info['am_cutoff_time']);
        $pm_cutoff_time = intval($store_info['pm_cutoff_time']);
        $ware_open_flash_send = $store_info['ware_open_flash_send']?$store_info['ware_open_flash_send']:0;
        $delivery_date = $rule['delivery_date']?$rule['delivery_date']:date('Ymd');
        $delay_days = $rule['delay_days']?$rule['delay_days']:0;
        $send_day_limit = $rule['send_day_limit']?intval($rule['send_day_limit']):0;
        $diff_day = max(((strtotime($delivery_date)-strtotime(date('Ymd')))/86400),$delay_days,0);
        $exp_day = 30;
        if(strtotime($exp_date)){ //Unix Millennium bug
            $exp_day = bcdiv((strtotime($exp_date)-strtotime(date('Ymd'))), 86400,0);
        }
        
        $days = $rule['fresh_days']?$rule['fresh_days']:30;
        $days = $days - 1;
        if($rule['delivery_date'] && $rule['fresh_days']){
            $days = $diff_day + $days;
        }
        $days = min($exp_day,$days); 

        $send_region_add_time = 0;
        //偏远区域增加时间
        // if($tms_region_time >0){
        //     $send_region_add_time = $tms_region_time - 1;
        // }else{
        //     $send_region_add_time = 0;
        // }
        $rule['time'] = $rule['time'] + $send_region_add_time;
        $delay_hour = $rule['delay_hour']?$rule['delay_hour']:0;

        $s_time = array();
        // if($rule['time'] === 0){   //半小时送达
        //     $send_time_arr[] = date('H:i').'-'.date('H:i',strtotime('+ 30 min'));
        // }
        $send_time_arr = array();
        $default_send_time = array('shtime'=>'','stime'=>'');
        $format_arr = array(
                '09:00-14:00' => '0914',
                '14:00-18:00' => '1418',
                '09:00-18:00' => '0918',
                '18:00-22:00' => '1822',
                '18:00-21:00' => '1822',
                '11:00-18:00' => '0918',
                );
        if($package_data['package_type'] == 1){ //T+0
            if($tms_region_type > 1){    //3公里外
                $send_time_arr = array(
                    //'09:00-14:00',
                    '11:00-18:00',
                    '18:00-21:00',
                    );
            }else{
                $step_time = array(
                    'step' => 1,
                    'unit' => 'hour',
                    );
                $e_time = '';
                $s_time = date('H:i',strtotime($open_time.":00")+3600+3600*$delay_hour);
                $f_time = date('H:i',strtotime($close_time.":00"));
                while ($e_time != $f_time) {
                    $step = bcmul($step_time['step'],1,0);
                    $e_time = date('H:i',strtotime('+'.$step.' '.$step_time['unit'],strtotime($s_time)));
                    $send_time_arr[] = $s_time.'-'.$e_time;
                    $s_time = $e_time;
                }
            }
        }else{
            $send_array = array(
                '1' => array(
                        '1'=>'09:00-14:00',
                        '2'=>'14:00-18:00',
                        '3'=>'18:00-22:00',
                    ),
                '2' => array(
                        '1'=>'09:00-18:00',
                        '2'=>'18:00-22:00',
                    ),
                );
            foreach ($rule['mode_list'] as $mode) {
                if(isset($send_array[$rule['time_mode']][$mode])){
                    if($is_day_night == 0 && $send_array[$rule['time_mode']][$mode] == '18:00-22:00'){
                        continue;
                    }
                    $send_time_arr[] = $send_array[$rule['time_mode']][$mode];
                }
            }
            // if(empty($send_time_arr)){
            //     $send_time_arr[] = '0918';
            // }
        }

        $send_times = array();
        $h = date('H');
        if($package_data['package_type'] == 1){  //T+0
            if($is_oversale && $diff_day == 0){    //T+0超售 第二天起送
                $diff_day = 1;
            }
        }
        $s_time_key = '';
        for($i=$diff_day;$i<=$days;$i++) {
            $send_day = date('Ymd',strtotime('+'.$i.' day'));
            foreach ($send_time_arr as $s_time) {
                $send_times[$send_day][$s_time]['disable'] = 'undisable';
                if($package_data['package_type'] == 1){  //T+0
                    if($tms_region_type > 1){    //3公里外
                        $now = date('H:i');
                        if($now >= '09:30'){
                            if($send_day == date('Ymd') && $s_time != '18:00-21:00'){
                                $send_times[$send_day][$s_time]['disable'] = 'disable';
                            }
                        }
                        if($h >= 15){
                            if($send_day == date('Ymd')){
                                $send_times[$send_day][$s_time]['disable'] = 'disable';
                            }
                        }
                    }else{
                        //增加捡货时间5min 只对T+0闪电送有效
                        // if(date('Ymd') == date('Ymd',strtotime('+5 min'))){
                        //     $h = date('H',strtotime('+5 min'));
                        // }
                        $s_time_arr = explode('-', $s_time);
                        //捡货时间截至为截单前1小时
                        $last_pick_time = bcsub($close_time, 1);
                        if($rule['time'] == 0){
                            // if($this->use29Min === true){
                            //     $last_hour = date('H',strtotime('-0 hour',strtotime($s_time_arr[1])));
                            // }else{
                                $last_hour = date('H',strtotime('-1 hour',strtotime($s_time_arr[1])));
                            // }
                        }else{
                            $last_hour = date('H',strtotime('-'.$rule['time'].' hour',strtotime($s_time_arr[1])));
                        }



                        if($ware_open_flash_send && $rule['time'] == 0 && $send_day == date('Ymd') && $h == date('H',strtotime($s_time_arr[0]))){
                            if($this->use29Min === true){
                                $a_f_time = date('H:i');
                                $a_l_time = date('H:i',strtotime('+30 min'));
                            }else{
                                $a_f_time = date('H:i',strtotime('+30 min'));
                                $a_l_time = date('H:i',strtotime('+1 hour'));
                            }
                            
                            $s_time_key = $a_f_time.'-'.$a_l_time;
                            $a_send_times = array();
                            $a_send_times[$s_time_key]['disable'] = 'undisable';
                            $a_send_times[$s_time_key]['is_flash'] = 'true';
                            if($this->use29Min === true){
                                $a_send_times[$s_time_key]['name'] = '29分钟即时达';
                            }else{
                                $a_send_times[$s_time_key]['name'] = '尽快送达';
                            }
                            if($this->use29Min === true){
                                if($tms_region_type > 0){
                                    $a_send_times[$s_time_key]['disable'] = 'disable';
                                }
                            }

                            $flash_s_time = date('H:00',strtotime($a_f_time)+((strtotime($a_l_time)-strtotime($a_f_time))/2)); //落单时间段
                            $flash_e_time = date('H:00',strtotime($flash_s_time) + 3600);
                            $flash_limit_key = $flash_s_time."-".$flash_e_time."_flash";
                            if($send_limit[$send_day][$flash_limit_key]){
                                if($send_limit[$send_day][$flash_limit_key] == 'limit'){
                                    $a_send_times[$s_time_key]['disable'] = 'disable';
                                }
                            }elseif($send_limit['all'][$flash_limit_key] == 'limit'){
                                $a_send_times[$s_time_key]['disable'] = 'disable';
                            }

                            if($a_send_times && $a_send_times[$s_time_key]['disable'] == 'disable'){
                                if($this->use29Min === true){
                                    if($this->platform == 'IOS' && $this->_version < '5.9.1'){
                                        $a_send_times = array();
                                    }
                                }else{
                                    $a_send_times = array();
                                }
                            }

                            if(date('H',strtotime($a_l_time)) >= $close_time){
                                $a_send_times = array();
                            }
                            if(date('H',strtotime($a_f_time)) >= $last_pick_time){
                                $a_send_times = array();
                            }
                            $a_send_times and $send_times[$send_day] = $a_send_times;
                        }
                        
                        if($h >= $last_hour && $send_day == date('Ymd')){
                            $send_times[$send_day][$s_time]['disable'] = 'disable';
                        }
                        if($open_time >= $last_hour){
                            $send_times[$send_day][$s_time]['disable'] = 'disable';
                        }
                        if($last_hour >= $close_time){
                            $send_times[$send_day][$s_time]['disable'] = 'disable';
                        }
                    }
                }else{
                    if($h >= $close_time){
                        if($send_day <= date('Ymd')){
                            $send_times[$send_day][$s_time]['disable'] = 'disable';
                        }elseif($send_day == date('Ymd',strtotime('+1 day'))){
                            if($s_time != '18:00-22:00'){
                                $send_times[$send_day][$s_time]['disable'] = 'disable';
                            }
                        }
                    }
                    if($rule['time_mode'] == 1 && $h <= $am_cutoff_time){
                        if($send_day < date('Ymd')){
                            $send_times[$send_day][$s_time]['disable'] = 'disable';
                        }elseif($send_day == date('Ymd')){
                            if($s_time != '18:00-22:00'){
                                $send_times[$send_day][$s_time]['disable'] = 'disable';
                            }
                        }
                    }
                    if($rule['time_mode'] == 1 && $h >= $am_cutoff_time && $h <= $pm_cutoff_time){
                       if($send_day < date('Ymd')){
                            $send_times[$send_day][$s_time]['disable'] = 'disable';
                        }elseif($send_day == date('Ymd')){
                            if($s_time != '18:00-22:00'){
                                $send_times[$send_day][$s_time]['disable'] = 'disable';
                            }
                        }
                    }
                    if($rule['time_mode'] == 1 && $h >= $pm_cutoff_time && $h <= $close_time){
                        if($send_day <= date('Ymd')){
                            $send_times[$send_day][$s_time]['disable'] = 'disable';
                        }
                    }
                    if($rule['time_mode'] == 2 && $h <= $close_time){
                        if($send_day <= date('Ymd')){
                            $send_times[$send_day][$s_time]['disable'] = 'disable';
                        }
                    }
                    if($rule['time_mode'] == 2 && $h >= $close_time){
                        if($send_day <= date('Ymd')){
                            $send_times[$send_day][$s_time]['disable'] = 'disable';
                        }elseif($send_day == date('Ymd',strtotime('+1 day'))){
                            if($s_time != '18:00-22:00'){
                                $send_times[$send_day][$s_time]['disable'] = 'disable';
                            }
                        }
                    }
                    if($is_day_night == 0 && $s_time == '18:00-22:00'){
                        $send_times[$send_day][$s_time]['disable'] = 'disable';
                    }

                }
                $limit_s_time = $s_time;
                if(isset($format_arr[$s_time])){
                    $limit_s_time = $format_arr[$s_time];
                }

                if($send_limit[$send_day][$limit_s_time]){
                    if($send_limit[$send_day][$limit_s_time] == 'limit'){
                        $send_times[$send_day][$s_time]['disable'] = 'disable';
                    }elseif($send_limit[$send_day][$limit_s_time] == 'half'){
                        if($send_times[$send_day][$s_time]['disable'] != 'disable'){
                            $send_times[$send_day][$s_time]['disable'] = 'half';
                        }
                    }
                }elseif($send_limit['all'][$limit_s_time] == 'limit'){
                    $send_times[$send_day][$s_time]['disable'] = 'disable';
                }
                //if($package_data['package_type'] == 1 && $send_times[$send_day][$s_time]['disable'] == 'disable'){
                if(version_compare($this->_version, '5.7.0') < 0){
                    if($send_times[$send_day][$s_time]['disable'] == 'disable'){
                        unset($send_times[$send_day][$s_time]);
                    }
                }else{
                    if($package_data['package_type'] == 1){
                        if($h && $last_hour){
                            if($h >= $last_hour && $send_day == date('Ymd')){
                                unset($send_times[$send_day][$s_time]);
                            }
                        }
                    }
                }
            }
        }
        foreach ($send_times as $key => $value) {
            $is_delete = true;
            foreach ($value as $k => $v) {
                if($v['disable'] !== 'disable'){
                    $is_delete = false;
                    if(empty($package_data['send_day'])) $package_data['send_day'] = $key;
                }
                if($v['disable'] !== 'disable' && $v['disable'] !== 'half'){
                    if(empty($default_send_time['shtime']) || empty($default_send_time['stime'])){
                        $default_send_time['shtime'] = $key;
                        $default_send_time['stime'] = $k;
                    }
                }
                if($v['disable'] == 'half'){
                    $send_times[$key][$k]['disable'] = 'undisable';
                }
            }
            if($is_delete === true){
                unset($send_times[$key]);
            }
        }
        // if($package_data['package_type'] == 1){  //T+0
        //     if($tms_region_type > 1){  //3公里外

        //     }elseif($ware_open_flash_send){
        //         //$send_times = array_slice($send_times, 0 , 1 ,true);
        //         foreach ($send_times as $sh_time => $val) {
        //             $s_time = array_keys($val)[0];
        //             $s_time_arr = explode('-', $s_time);
        //             if(empty($package_data['send_day'])) $package_data['send_day'] = $sh_time;
        //             if($rule['time'] == 0 && $send_times[$sh_time][$s_time]['disable'] == 'undisable'){
        //                 $first_hour = date('H',strtotime('-1 hour',strtotime($s_time_arr[0])));
        //                 if($sh_time == date('Ymd') && date('H') == $first_hour){

        //                     if($this->use29Min === true){
        //                         $a_f_time = date('H:i');
        //                         $a_l_time = date('H:i',strtotime('+30 min'));
        //                     }else{
        //                         $a_f_time = date('H:i',strtotime('+30 min'));
        //                         $a_l_time = date('H:i',strtotime('+1 hour'));
        //                     }
                            
        //                     $s_time_key = $a_f_time.'-'.$a_l_time;
        //                     $a_send_times = array();
        //                     $a_send_times[$s_time_key]['disable'] = 'undisable';
        //                     $a_send_times[$s_time_key]['is_flash'] = 'true';
                            
        //                     if($this->use29Min === true){
        //                         $a_send_times[$s_time_key]['name'] = '29分钟即时达';
        //                     }else{
        //                         $a_send_times[$s_time_key]['name'] = '尽快送达';
        //                     }
        //                     $send_times[$sh_time] = array_merge($a_send_times,$send_times[$sh_time]);
        //                     break;
        //                 }
        //             }
        //         }
        //     }
        // }else{
        //     //$send_times = array_slice($send_times, 0 , 5 ,true);
        // }
        if($send_day_limit > 0){
            $send_times = array_slice($send_times, 0 , $send_day_limit ,true);
        }
        $package_data['send_time'] = $send_times;
        $package_data['default_send_time'] = $default_send_time;
        return $package_data;
    }

    private function getSendTimeList($send_type=1,$start_time=0,$end_time=0){



        return $sendHours;
    }

    private function getOnePackageSendTime($item){

    }

    //获取仓储运能限量
    private function getSendLimit($send_ware_id,&$open_flash_send){
        $send_limit = array();
        $this->ci->load->model('warehouse_model');
        $warehouse_info = $this->ci->warehouse_model->getWarehouseByID($send_ware_id);
        $day_limit = $warehouse_info['day_limit'];
        $am_limit = $warehouse_info['am_limit'];
        $pm_limit = $warehouse_info['pm_limit'];
        $night_limit = $warehouse_info['night_limit'];
        $hours_limit = unserialize($warehouse_info['hours_limit']);
        $flash_hours_limit = unserialize($warehouse_info['flash_hours_limit']);
        $open_flash_send = $warehouse_info['open_flash_send'];
        foreach ($hours_limit as $key => $value) {
            if($value == 0){
                $send_limit['all'][$key] = 'limit';
            }
        }
        foreach ($flash_hours_limit as $key => $value) {
            if($value == 0){
                $send_limit['all'][$key."_flash"] = 'limit';
            }
        }
        if($day_limit == 0){
            $send_limit['all']['0918'] = 'limit';
        }
        if($am_limit == 0){
            $send_limit['all']['0914'] = 'limit';
        }
        if($pm_limit == 0){
            $send_limit['all']['1418'] = 'limit';
        }
        if($night_limit == 0){
            $send_limit['all']['1822'] = 'limit';
        }
        $special_limit = unserialize($warehouse_info['special']);
        $order_time_limit_result = $this->ci->warehouse_model->getWarehouseSendCount($send_ware_id);
        foreach ($order_time_limit_result as $sh_stime => $nums) {
            $time_key = explode('_', $sh_stime);
            $sh_time = $time_key[0];
            $s_time = $time_key[1];
            $flash = $time_key[2];
            if(isset($hours_limit[$s_time]) || isset($flash_hours_limit[$s_time])){
                if($flash){
                    if($nums >= $flash_hours_limit[$s_time]){
                        $send_limit[$sh_time][$s_time."_flash"] = 'limit';
                    }elseif(bccomp(bcdiv($nums, $flash_hours_limit[$s_time] , 2),0.5,2) == 1){
                        $send_limit[$sh_time][$s_time."_flash"] = 'half';
                    }else{
                        $send_limit[$sh_time][$s_time."_flash"] = 'unlimit';
                    }
                }else{
                    if($nums >= $hours_limit[$s_time]){
                        $send_limit[$sh_time][$s_time] = 'limit';
                    }elseif(bccomp(bcdiv($nums, $hours_limit[$s_time] , 2),0.5,2) == 1){
                        $send_limit[$sh_time][$s_time] = 'half';
                    }else{
                        $send_limit[$sh_time][$s_time] = 'unlimit';
                    }
                }
            }elseif($s_time == '0918'){
                if($nums >= $day_limit){
                    $send_limit[$sh_time][$s_time] = 'limit';
                }else{
                    $send_limit[$sh_time][$s_time] = 'unlimit';
                }
            }elseif($s_time == '0914'){
                if($nums >= $am_limit){
                    $send_limit[$sh_time][$s_time] = 'limit';
                }else{
                    $send_limit[$sh_time][$s_time] = 'unlimit';
                }
            }elseif($s_time == '1418'){
                if($nums >= $pm_limit){
                    $send_limit[$sh_time][$s_time] = 'limit';
                }else{
                    $send_limit[$sh_time][$s_time] = 'unlimit';
                }
            }elseif($s_time == '1822'){
                if($nums >= $night_limit){
                    $send_limit[$sh_time][$s_time] = 'limit';
                }else{
                    $send_limit[$sh_time][$s_time] = 'unlimit';
                }
            }
        }
        foreach ($special_limit as $special) {
            $s_start_time = date('Ymd',strtotime($special['s_start_time']));
            $s_end_time = date('Ymd',strtotime($special['s_end_time']));
            $sh_time = date('Ymd',strtotime($s_start_time));
            while ($sh_time <= $s_end_time && $sh_time>=$s_start_time) {
                if($special['s_day_limit'] == 0){
                    $send_limit[$sh_time]['0918'] = 'limit';
                }
                if($special['s_am_limit'] == 0){
                    $send_limit[$sh_time]['0914'] = 'limit';
                }
                if($special['s_pm_limit'] == 0){
                    $send_limit[$sh_time]['1418'] = 'limit';
                }
                if($special['s_night_limit'] == 0){
                    $send_limit[$sh_time]['1822'] = 'limit';
                }
                foreach ($special['s_hours_limit'] as $s_time => $s_hours_limit) {
                    if($s_hours_limit == 0){
                        $send_limit[$sh_time][$s_time] = 'limit';
                    }
                }
                foreach ($special['s_flash_hours_limit'] as $s_time => $s_flash_hours_limit) {
                    if($s_flash_hours_limit == 0){
                        $send_limit[$sh_time][$s_time."_flash"] = 'limit';
                    }
                }
                $sh_time = date('Ymd',strtotime($sh_time)+86400);
            }
        }
        foreach ($order_time_limit_result as $sh_stime => $nums) {
            $time_key = explode('_', $sh_stime);
            $sh_time = $time_key[0];
            $s_time = $time_key[1];
            $flash = $time_key[2];
            foreach ($special_limit as $special) {
                $s_start_time = date('Ymd',strtotime($special['s_start_time']));
                $s_end_time = date('Ymd',strtotime($special['s_end_time']));
                if($sh_time <= $s_end_time && $sh_time>=$s_start_time){
                    $s_hours_limit = $special['s_hours_limit'];
                    $s_flash_hours_limit = $special['s_flash_hours_limit'];
                    $s_day_limit = $special['s_day_limit'];
                    $s_am_limit = $special['s_am_limit'];
                    $s_pm_limit = $special['s_pm_limit'];
                    $s_night_limit = $special['s_night_limit'];
                    if(isset($s_hours_limit[$s_time]) || isset($s_flash_hours_limit[$s_time])){
                        if($flash){
                            if($nums >= $s_flash_hours_limit[$s_time]){
                                $send_limit[$sh_time][$s_time."_flash"] = 'limit';
                            }elseif(bccomp(bcdiv($nums, $s_flash_hours_limit[$s_time] , 2),0.5,2) == 1){
                                $send_limit[$sh_time][$s_time."_flash"] = 'half';
                            }else{
                                $send_limit[$sh_time][$s_time."_flash"] = 'unlimit';
                            }
                        }else{
                            if($nums >= $s_hours_limit[$s_time]){
                                $send_limit[$sh_time][$s_time] = 'limit';
                            }elseif(bccomp(bcdiv($nums, $s_hours_limit[$s_time] , 2),0.5,2) == 1){
                                $send_limit[$sh_time][$s_time] = 'half';
                            }else{
                                $send_limit[$sh_time][$s_time] = 'unlimit';
                            }
                        }
                    }elseif($s_time == '0918'){
                        if($nums >= $s_day_limit){
                            $send_limit[$sh_time][$s_time] = 'limit';
                        }else{
                            $send_limit[$sh_time][$s_time] = 'unlimit';
                        }
                    }elseif($s_time == '0914'){
                        if($nums >= $s_am_limit){
                            $send_limit[$sh_time][$s_time] = 'limit';
                        }else{
                            $send_limit[$sh_time][$s_time] = 'unlimit';
                        }
                    }elseif($s_time == '1418'){
                        if($nums >= $s_pm_limit){
                            $send_limit[$sh_time][$s_time] = 'limit';
                        }else{
                            $send_limit[$sh_time][$s_time] = 'unlimit';
                        }
                    }elseif($s_time == '1822'){
                        if($nums >= $s_night_limit){
                            $send_limit[$sh_time][$s_time] = 'limit';
                        }else{
                            $send_limit[$sh_time][$s_time] = 'unlimit';
                        }
                    }
                }
            }
        }
        return $send_limit;
    }

    //check创建订单时由于购物车或者时效改变，导致包裹改变
    private function checkDiffPackages($p_order_id,$new_packages){
        $packages = $this->ci->package_model->dump(array('p_order_id'=>$p_order_id));
        $old_struc = array();
        $new_struc = array();
        foreach ($packages as $key => $value) {
            $old_struc[] = $value['tag'];
        }
        foreach ($new_packages as $key => $value) {
            $new_struc[] = $key;
        }
        $l_arr = array_diff($old_struc, $new_struc);
        $r_arr = array_diff($new_struc, $old_struc);
        if(empty($l_arr) && empty($r_arr)){
            return true;
        }else{
            return false;
        }
    }

    public function checkPackageSendTime($package,$shtime,$stime,$is_flash,$is_zt = 0){
        $package_send_time = $package['send_time'];
        if($is_zt == 1 && version_compare($this->_version, '5.9.2') >= 0){
            $package_send_time = $package['zt_send_time'];
            $is_flash = 0;
        }
        $send_time = array();
        if(isset($package_send_time[$shtime][$stime]) && $package_send_time[$shtime][$stime]['disable'] == 'undisable'){
            $send_time['shtime'] = $shtime;
            $send_time['stime'] = $stime;
        }elseif($is_flash && $package['package_type'] == 1 && isset($package_send_time[$shtime])){  //t+0半小时达
            $s_time_arr = explode('-', $stime);
            $c_f_time = $s_time_arr[0];
            $c_l_time = $s_time_arr[1];
            foreach ($package_send_time[$shtime] as $p_s_time => $value) {
                if($value['disable'] == 'undisable'){
                    $s_time_arr = explode('-', $p_s_time);
                    $p_f_time = $s_time_arr[0];
                    $p_l_time = $s_time_arr[1];
                    //极速达  选择时间 - 新算的包裹时间小于5分钟
                    if(strtotime($p_f_time) - strtotime($c_f_time) <= 300  && strtotime($p_l_time) - strtotime($c_l_time) <= 300  ){
                        $send_time['shtime'] = $shtime;
                        $send_time['stime'] = $p_s_time;
                    }
                }
            }
        }
        return $send_time;
        //$packages = $this->ci->package_model->dump(array('p_order_id'=>$p_order_id));
    }

    public function setPackageSendTime($package,$shtime,$stime){
        //$check = $this->checkPackageSendTime($package,$shtime,$stime);
        //if($check === true){
        //}
    }

    /*
    * 订单列表New － 商品展示
    */
    private  function orderProductItems($list)
    {
        $order_ids = array();
        $b2o_ids = array();

        $order_name = array();
        $b2o_name = array();

        foreach($list as $key=>$val)
        {
            $bs = mb_substr($val['order_name'],0,1);
            if($bs == 'P')
            {
                array_push($b2o_ids,$val['id']);
                $data = array(
                    'id'=>$val['id'],
                    'order_name'=>$val['order_name']
                );
                array_push($b2o_name,$data);
            }
            else
            {
                array_push($order_ids,$val['id']);
                $data = array(
                    'id'=>$val['id'],
                    'order_name'=>$val['order_name']
                );
                array_push($order_name,$data);
            }
        }

        //order pro
        if(count($order_ids) >0)
        {
            $fields = 'product.thum_photo, product.template_id,
                order_product.id as order_product_id,order_product.product_name,order_product.product_id,
                order_product.gg_name,order_product.price,order_product.qty,order_product.type as order_product_type,
                order_product.product_no,order_product.order_id';
            $where_in[] = array(
                'key' => 'order_product.order_id',
                'value' => $order_ids,
            );
            $join[] = array('table' => 'product', 'field' => 'product.id=order_product.product_id', 'type' => 'left');
            $order_products_res = $this->ci->order_model->selectOrderProducts($fields, '', $where_in, '', $join);

            foreach($order_products_res as $key=>$val)
            {
                // 获取产品模板图片
                if ($val['template_id']) {
                    $this->ci->load->model('b2o_product_template_image_model');
                    $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($val['template_id'], 'main');
                    if (isset($templateImages['main'])) {
                        $order_products_res[$key]['thum_photo'] = $templateImages['main']['thumb'];
                    }
                }

                foreach($order_name as $k=>$v)
                {
                    if($v['id'] == $val['order_id'])
                    {
                        $order_products_res[$key]['order_name'] = $v['order_name'];
                    }
                }
            }
        }
        else
        {
            $order_products_res = array();
        }

        //b2oorder - pro
        if(count($b2o_ids) >0)
        {
            $fields = 'product.thum_photo, product.template_id,
                b2o_parent_order_product.id as order_product_id,b2o_parent_order_product.product_name,b2o_parent_order_product.product_id,
                b2o_parent_order_product.gg_name,b2o_parent_order_product.price,b2o_parent_order_product.qty,b2o_parent_order_product.type as order_product_type,
                b2o_parent_order_product.product_no,b2o_parent_order_product.order_id';
            $b2o_where_in[] = array(
                'key' => 'b2o_parent_order_product.order_id',
                'value' => $b2o_ids,
            );
            $b2o_join[] = array('table' => 'product', 'field' => 'product.id=b2o_parent_order_product.product_id', 'type' => 'left');
            $b2o_order_products_res = $this->ci->b2o_parent_order_model->selectOrderProducts($fields, '', $b2o_where_in, '', $b2o_join);

            foreach($b2o_order_products_res as $key=>$val)
            {
                // 获取产品模板图片
                if ($val['template_id']) {
                    $this->ci->load->model('b2o_product_template_image_model');
                    $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($val['template_id'], 'main');
                    if (isset($templateImages['main'])) {
                        $b2o_order_products_res[$key]['thum_photo'] = $templateImages['main']['thumb'];
                    }
                }

                foreach($b2o_name as $k=>$v)
                {
                    if($v['id'] == $val['order_id'])
                    {
                        $b2o_order_products_res[$key]['order_name'] = $v['order_name'];
                    }
                }
            }
        }
        else
        {
            $b2o_order_products_res = array();
        }

        $res = array_merge($order_products_res,$b2o_order_products_res);

        return $res;
    }


    /*
    * 订单详情 －b2o
    */
    private function b2oOrderInfo($params)
    {
        //fix 订单缓存
        $cach_key = 'cache_'.$params['order_name'];
        $this->ci->load->library('orderredis');
        $redis = $this->ci->orderredis->getConn();
        if($redis != false)
        {
            $orderData = $redis->get($cach_key);
            if(!empty($orderData))
            {
                //return json_decode($orderData,true);
            }
        }

        //获取订单信息
        $result = $this->ci->b2o_parent_order_model->dump(array('order_name' =>$params['order_name'], 'uid' =>$params['uid']));
        $orderId = $result['id'];
        if (empty($result)) {
            return array('code' => '500', 'msg' => 'connect id error');
        }

        //订单地址
        $order_address_info = $this->ci->b2o_parent_order_address_model->dump(array('order_id' =>$result['id']));

        //初始化订单信息
        $result_array = $this->_initB2oOrderList(array($result), $params);
        $result = $result_array[0];

        // APP订单详情的订单状态文案修改。
        $time = $result['time'];
        $had_comment = $result['had_comment'];
        $pay_status = $result['pay_status'];
        $operation_id = $result['operation_id'];
        $pay_parent_id = $result['pay_parent_id'];
        $result['order_status'] = $this->getOrderStatusName($operation_id, $pay_parent_id, $pay_status, $time, $had_comment);

        $result['order_id'] = $result['id'];
        //商品
        $giftTypeList = $this->_setProductType();
        foreach ($result['item'] as $key => $value) {
            $result['item'][$key]['product_type'] = in_array($value['order_product_type'], $giftTypeList) ? 3 : 1;
            unset($result['item'][$key]['order_product_type']);
        }

        $result['pay_discount_money'] = $result['pay_discount'] + $result['new_pay_discount'] + $result['oauth_discount'];
        $method_money = $result['method_money'];
        unset($result['method_money'], $result['pay_discount'], $result['new_pay_discount'], $result['oauth_discount']);

        if (empty($result['fp']))
        {
            $result['fp'] = '';
        }
        $result['score_desc'] = '发表评论审核通过增加10积分，附加晒单图片再送10积分';
        $result['survey_url'] = '';
        $result['can_check_logistic'] = 0;
        $result['card_money'] = number_format($result['card_money'], 2, '.', '');
        $result['mail_money'] = $method_money + $result['invoice_money'];
        $result['method_money'] = $method_money + $result['invoice_money'];

        //发票
        $this->ci->load->model('b2o_parent_order_invoice_model');
        $invoiceInfo = $this->ci->b2o_parent_order_invoice_model->dump(array('order_id' => $orderId, 'is_valid' => 1),'username,mobile,address,province,city,area,kp_type');

        if(!empty($result['fp']) && empty($invoiceInfo)){
            $result['invoice_username'] =  $order_address_info['name'];
            $result['invoice_mobile'] =  substr_replace($order_address_info['mobile'], '****', 3, 4);
            $result['invoice_address'] =  $order_address_info['address'];
            //$result['kp_type'] = '明细';
            if($result['kp_type'] == 1)
            {
                $result['kp_type'] = '明细';
            }
            else if($result['kp_type'] == 2)
            {
                $result['kp_type'] = '食品';
            }
            else if($result['kp_type'] == 3)
            {
                $result['kp_type'] = '明细';
            }
            else if($result['kp_type'] == 4)
            {
                $result['kp_type'] = '商品大类';
            }
        } else {
            $result['invoice_username'] = $invoiceInfo['username'];
            $result['invoice_mobile'] = substr_replace($invoiceInfo['mobile'], '****', 3, 4);
            $result['invoice_address'] = $invoiceInfo['province'] . $invoiceInfo['city'] . $invoiceInfo['area'] . $invoiceInfo['address'];
            //$result['kp_type'] = $invoiceInfo['kp_type'] == 1 ? '水果' : '食品';
            if($invoiceInfo['kp_type'] == 1)
            {
                $result['kp_type'] = '水果';
            }
            else if($invoiceInfo['kp_type'] == 2)
            {
                $result['kp_type'] = '食品';
            }
            else if($invoiceInfo['kp_type'] == 3)
            {
                $result['kp_type'] = '明细';
            }
            else if($invoiceInfo['kp_type'] == 4)
            {
                $result['kp_type'] = '商品大类';
            }
        }

        $this->ci->load->model('order_einvoices_model');
        $einvoice = $this->ci->order_einvoices_model->count(array('order_name'=>$params['order_name']));
        if($einvoice == 0){
            $result['invoice_type'] = 0;
        }else{
            $result['invoice_type'] = 1;
        }

        if ($result['pay_status_key'] == 1) {
            $result['show_use_money_deduction'] = $result['use_money_deduction'];
        } else {
            $result['show_use_money_deduction'] = '0.00';
        }

        //物流查询
        if($params['source'] == 'app')
        {
            $result['route_list'] = NULL;
            //地址flag
            if(!empty($result['address_id']))
            {
                $this->ci->load->model('user_address_model');
                $user_add = $this->ci->user_address_model->dump(array('id' => $result['address_id']));
                if(!empty($user_add))
                {
                    $result['flag'] = $user_add['flag'];
                }
            }
        }

        //address
        $result['name'] =  $order_address_info['name'];
        $result['mobile'] =  $order_address_info['mobile'];
        $result['address'] =  $order_address_info['address'];

        //package
        $order_package = $this->ci->b2o_parent_order_model->get_order_package($params['order_name']);
        $package = json_decode($order_package['content'],true);
        $mail_money = 0;
        foreach($package as $key=>$val)
        {
            $package[$key]['send_time'] = (object)array();
            $mail_money += $package[$key]['method_money'];
        }
        $result['package'] = $package;
        $result['mail_money'] = $method_money + $result['invoice_money'];
        //$result['mail_money'] = $mail_money + $result['invoice_money'];

        //倒计时
        $result['start_time'] = date('Y-m-d H:i:s');
        $result['end_time'] = '';
        $result['show_time_state'] = 0;

        //称重商品补差金额
        $result['refund_money'] = '0.00';

        //已补开发票
        $result['is_supply_invoice'] = 0;

        //fix 订单缓存
        if($redis != false)
        {
            $redis->set($cach_key, json_encode($result));
            $redis->expire($cach_key, 15);
        }

        $result['pickup_code'] = '';

        return $result;
    }

    /*
     * b2o init
     */
    private function _initB2oOrderList($result, $params = array()) {
        $pay = $this->ci->config->item('pay');
        $operation = $this->ci->config->item('operation');
        $operation[0] = '待发货';
        $operation[1] = '待发货';
        $operation[4] = '待发货';

        if (empty($result)) {
            return array();
        }
        $orderids = array_column($result, 'id');
        $fields = 'product.thum_photo, product.template_id,
                b2o_parent_order_product.id as order_product_id,b2o_parent_order_product.product_name,b2o_parent_order_product.product_id,
                b2o_parent_order_product.gg_name,b2o_parent_order_product.price,b2o_parent_order_product.qty,b2o_parent_order_product.type as order_product_type,
                b2o_parent_order_product.product_no,b2o_parent_order_product.order_id';
        $where_in[] = array(
            'key' => 'b2o_parent_order_product.order_id',
            'value' => $orderids,
        );
        $join[] = array('table' => 'product', 'field' => 'product.id=b2o_parent_order_product.product_id', 'type' => 'left');
        $order_products_res = $this->ci->b2o_parent_order_model->selectOrderProducts($fields, '', $where_in, '', $join);

        $order_product_data = array();
        $giftTypeList = $this->_setProductType();
        foreach ($order_products_res as &$val) {
            // 获取产品模板图片
            if ($val['template_id']) {
                $this->ci->load->model('b2o_product_template_image_model');
                $templateImages = $this->ci->b2o_product_template_image_model->getTemplateImage($val['template_id'], 'main');
                if (isset($templateImages['main'])) {
                    $val['thum_photo'] = $templateImages['main']['thumb'];
                }
            }

            $val['thum_photo'] = empty($val['thum_photo']) ? '' : PIC_URL . $val['thum_photo'];
            $val['product_type'] = in_array($val['order_product_type'], $giftTypeList) ? 3 : 1;
            $order_product_data[$val['order_id']][] = $val;
        }

        foreach ($result as $key => $value) {
            if (!isset($order_product_data[$value['id']])) {
                unset($result[$key]);
                continue;
            }

            $result[$key]['pay_status'] = $pay[$value['pay_status']];
            $result[$key]['order_status'] = $operation[$value['operation_id']];
            $result[$key]['pay_status_key'] = $value['pay_status'];
            $result[$key]['order_status_key'] = $value['order_status'];  //订单状态

            //btn
            $result[$key]['can_comment'] = false;
            $result[$key]['can_confirm_receive'] = 'false';

            $online_pay = array(1, 2, 3, 5, 7, 8, 9, 11,12,13,15);
            if ($value['pay_status'] == '0' && in_array($value['pay_parent_id'], $online_pay) && $value['operation_id'] == '0') {
                $result[$key]['can_pay'] = 'true';
            } else {
                $result[$key]['can_pay'] = 'false';
            }
            if ($value['operation_id'] == '0' || $value['operation_id'] == '1') {
                if ($value['time'] >= date('Y-m-d', strtotime('-2 months'))) {
                    $result[$key]['can_cancel'] = 'true';
                } else {
                    $result[$key]['can_cancel'] = 'false';
                }
            } else {
                $result[$key]['can_cancel'] = 'false';
            }
            $result[$key]['can_check_logistic'] = 0;
            $result[$key]['has_refund'] = 0;
            $result[$key]['can_replace'] = 0;
            if($result[$key]['sync_status'] == 1 && in_array($result[$key]['operation_id'], array(1,2,3,4,6,9))){
                $result[$key]['can_check_logistic'] = 1;
            }else{
                $result[$key]['can_check_logistic'] = 0;
            }

            //隐藏价格
            if ($value['sheet_show_price'] != '1' && $value['pay_parent_id'] == '6') {
                foreach ($order_product_data[$value['id']] as $tk => $tv) {
                    $order_product_data[$value['id']][$tk]['price'] = "0.00";
                }
                $result[$key]['money'] = "0.00";
                $result[$key]['goods_money'] = "0.00";
            }

            //set product
            $result[$key]['item'] = $order_product_data[$value['id']];

            //特殊处理-app
            foreach($result[$key]['item'] as $k=>$v)
            {
                if(strpos($v['gg_name'],'/') !== false)
                {
                    $g_name = explode('/',$v['gg_name']);
                    $result[$key]['item'][$k]['gg_name'] = $g_name[0];
                }
            }

            $result[$key]['money'] = number_format((float)($value['money'] + $value['use_money_deduction']),2,'.','');
        }

        return $result;
    }


    /*
    * b2o - 订单取消
    */
    private function b2oOrderCancel($params)
    {
        $result = $this->ci->b2o_parent_order_model->selectOrder('id,order_name,channel,sync_status,is_enterprise,order_type', array('order_name' => $params['order_name'], 'uid' => $params['uid']));
        if (empty($result)) {
            return array("code" => "300", "msg" => '操作失败');
        }

        //事务开启
        $this->ci->db->trans_begin();

        $msg = 'succ';
        $cancel_result = $this->b2oCancel($result['id'], $msg, false, $params);
        if (!$cancel_result) {
            $this->ci->db->trans_rollback();
            return array('code' => '300', 'msg' => $msg);
        } else {
            if ($this->ci->db->trans_status() === FALSE) {
                $this->ci->db->trans_rollback();
                return array('code' => '300', 'msg' => '订单取消失败');
            } else {
                $this->ci->db->trans_commit();
            }
            return array('code' => '200', 'msg' => $msg);
        }
    }

    /**
     * b2o - 取消订单
     *
     * @return void
     * @author
     **/
    public function b2oCancel($order_id, &$msg, $is_oms = false, $params = array(),$is_pay_center = false)
    {
        $order = $this->ci->b2o_parent_order_model->dump(array('id' => $order_id));

        if (!$order) {
            $msg = '订单不存在';
            return false;
        }

        if ($order['sync_status'] == 2 && !$is_oms) {
            $msg = '订单确认中，请稍后操作';
            return false;
        }

        if ($order['operation_id'] != '0' && $order['operation_id'] != '1') {
            $operation = $this->ci->config->item('operation');
            $msg = '订单已经' . $operation[$order['operation_id']];
            return false;
        }

        if ($order['pay_status'] == 1 || $order['pay_status'] == 2) {
            $msg = '订单已支付或等待到账中,操作失败';
            return false;
        }

        $type = $is_oms?2:1;
        $is_pay_center and $type = 3;
        $per_op_id = $order['operation_id'];
        $per_pay_status = $order['pay_status'];

        $affected_row = $this->ci->b2o_parent_order_model->update(array('operation_id' => 5, 'last_modify' => time()), array('id' => $order['id']));
        if (!$affected_row) {
            $msg = '订单取消失败';
            return false;
        }

        // 退赠品
        $this->ci->load->model('user_gifts_model');
        $this->ci->user_gifts_model->return_b2o_user_gift($order['id'], $order['uid']);


        // 退回积分
        if ($order['jf_money']) {
            $score = $order['jf_money'] * 100;
            $this->ci->load->bll('user',null,'bll_user');
            $this->ci->bll_user->return_score($order['uid'], $score, "订单{$order['order_name']}取消退回积分{$score}",'取消订单返还');
        }

        // 注销优惠劵
        $this->ci->load->bll('card', null, 'bll_card');
        if ($order['use_card']) {
            $this->returnCard($order['use_card'],$order['order_name'],1);
        }

        $this->returnPostage($order['order_name'],1);

        //订单取消,库存退回
        $this->return_product_qty($order_id);

        //返还秒杀
        $this->ci->load->model('ms_log_v2_model');
        $this->ci->ms_log_v2_model->update_del($order['order_name']);

        // 操作日志
        $this->ci->load->model('order_op_model');
        if ($is_oms) {
            $order_op = array(
                "manage" => "erp系统",
                "pay_msg" => "",
                "operation_msg" => "订单取消",
                "time" => date("Y-m-d H:i:s"),
                "order_id" => $order['id'],
            );
        } else {
            $order_op = array(
                "manage" => $order['uid'],
                "pay_msg" => "",
                "operation_msg" => "订单取消",
                "time" => date("Y-m-d H:i:s"),
                "order_id" => $order['id'],
            );
        }
        $this->ci->order_op_model->insert($order_op);
        $this->ci->order_op_model->addCancelDetail($order['id'],$type,$per_op_id,$per_pay_status);
        return true;
    }

    /*
     * 格式化输出 - app
     */
    private function returnMsg($data)
    {
        return array('code'=>200,'msg'=>'succ','data'=>$data);
    }

    /*
     * 拆单 － b2o => order
     */
    public function orderSplit($params)
    {
        //必要参数验证start
        $required_fields = array(
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        $gift = $this->giftSplit($params['order_name']);
        if($gift['code'] == '200')
        {
            return array('code'=>'200','msg'=>'succ');
        }

        //事务 start
        $this->ci->db->trans_begin();

        $order = $this->ci->b2o_parent_order_model->dump(array('order_name' => $params['order_name'],'pay_status'=>'1','order_status' =>'1','p_order_id'=>'0'));
        if (empty($order)) {
            $msg = array('code'=>'300','msg'=>'订单不存在');
            return $msg;
        }

        if($order['operation_id'] == 5)
        {
            $msg = array('code'=>'300','msg'=>'已取消订单');
            return $msg;
        }

        $order_address = $this->ci->b2o_parent_order_address_model->dump(array('order_id' =>$order['id']));
        if (empty($order_address)) {
            $msg = array('code'=>'300','msg'=>'订单地址异常');
            return $msg;
        }

        $order_invoice = $this->ci->b2o_parent_order_invoice_model->dump(array('order_id' =>$order['id']));

        $order_package = $this->ci->b2o_parent_order_model->get_order_package($params['order_name']);
        if (empty($order_package)) {
            $msg = array('code'=>'300','msg'=>'订单包裹异常');
            return $msg;
        }

        $order_einvoice = $this->ci->order_einvoices_model->dump(array('order_name' =>$params['order_name']));


        $package = json_decode($order_package['content'],true);

        $p_order_id = $order['id'];
        $p_money = $order['money'];
        $p_use_money_deduction = $order['use_money_deduction'];
        $p_goods_money = $order['goods_money'];
        $p_jf_money = $order['jf_money'];
        $p_card_money = $order['card_money'];
        $p_method_money = $order['method_money'];
        $p_invoice_money = $order['invoice_money'];
        $p_new_pay_discount = $order['new_pay_discount'];
        $p_score = $order['score'];
        $p_bank_discount = $order['bank_discount'];
        $p_use_jf= $order['use_jf'];
        $p_post_discount = $order['post_discount'];
        $p_pay_discount = $order['pay_discount'];
        $p_fresh_discount = $order['fresh_discount'];
        $p_jd_discount = $order['jd_discount'];

        //自提拆单
        $p_pickup_store_id = $order['pickup_store_id'];
        $p_pickup_code = $order['pickup_code'];

        $uid = $order['uid'];
        $fp = $order['fp'];
        $fp_dz = $order['fp_dz'];
        $fp_id_no = $order['fp_id_no'];
        $address_id = $order['address_id'];
        $trade_no = $order['trade_no'];
        $pay_name = $order['pay_name'];
        $pay_id = $order['pay_id'];
        $pay_parent_id = $order['pay_parent_id'];
        $billno = $order['billno'];
        $pay_time = $order['pay_time'];
        $update_pay_time = $order['update_pay_time'];
        $pay_status = $order['pay_status'];
        $time = $order['time'];
        $operation_id = $order['operation_id'];
        $use_card = $order['use_card'];
        $order_status = $order['order_status'];
        $channel = $order['channel'];
        $sales_channel = $order['sales_channel'];
        $version = $order['version'];
        $sync_status = $order['sync_status'];
        $last_modify_time = $order['last_modify_time'];
        $cang_id = $order['cang_id'];
        $deliver_type = $order['deliver_type'];
        $sheet_show_price = $order['sheet_show_price'];
        $show_status = $order['show_status'];
        $fresh_no = $order['fresh_no'];
        $no_stock = $order['no_stock'];
        $kp_type = $order['kp_type'];
        $balance_payed = $order['balance_payed'];

        //单品券
        $is_sign_card = 0;
        $p_card_arr = array();
        if(!empty($use_card) && $p_card_money >0)
        {
            $card = $this->ci->card_model->dump(array('card_number' =>$use_card));
            if(!empty($card) && !empty($card['product_id']))
            {
                $is_sign_card = 1;
                $p_card_arr = explode(',',$card['product_id']);
            }
        }

        $pro = array();
        //构建商品
        foreach($package as $k=>$v)
        {
            $shtime = $v['chose_sendtime']['shtime'];
            $stime = $v['chose_sendtime']['stime'];
            foreach($v['item'] as $key=>$val)
            {
                $val['package_type'] = $v['package_type'];
                $val['tag'] = $v['tag'];
                $val['sendtime'] = $shtime.'|'.$stime;
                //自提拆单
                if(isset($v['store']))
                {
                    $val['store'] = $v['store'];
                }
                $val['cang_id'] = $v['cang_id'];
                array_push($pro,$val);
            }
        }

        $store_ids = array_column($pro,'store_id','');
        $store_ids = array_unique($store_ids);
        $product = array();
        foreach($store_ids as $k=>$v)
        {
            foreach($pro as $key=>$val)
            {
                if($v == $val['store_id'])
                {
                    $s_p_key = $val['store_id'].'|'.$val['tag'];
                    $product[$s_p_key]['product'][] = $val;
                }
            }
        }

        //构建订单
        foreach($product as $k=>$v)
        {
            $goods_money = 0;
            $discount = 0;
            $shtime = '';
            $stime = '';
            $order_type = 1;
            $store_id = 0;
            $p_pack_products = array();

            foreach($v['product'] as $key=>$val)
            {
                $goods_money += $val['price']*$val['qty'];
                $discount += $val['discount'];
                $sendtime = explode('|',$val['sendtime']);
                $shtime = $sendtime[0];
                $stime = $sendtime[1];

                if($val['package_type'] == 1)
                {
                    $order_type = 3;
                    //自提拆单
                    if(isset($val['store']))
                    {
                        if($val['store']['is_select'] == '1')
                        {
                            $order_type = 4;
                        }
                    }
                }
                else if($val['package_type'] == 3)
                {
                    $order_type = 5;
                }
                $store_id = $val['store_id'];
                array_push($p_pack_products,$val['product_id']);
            }

            //分拆
            $bl = round($goods_money/$p_goods_money,3);
            
            if($is_sign_card == 1 && count($p_card_arr) >0)
            {
                $inter = array_intersect($p_pack_products,$p_card_arr);
                if(count($inter) >0)
                {
                    $bl = round(($goods_money -$p_card_money)/($p_goods_money - $p_card_money),3);
                }
                else
                {
                    $bl = round($goods_money/($p_goods_money - $p_card_money),3);
                }
            }

            if($p_pay_discount >0)
            {
                if($discount >0)
                {
                    $bl = round(($goods_money - $discount)/($p_goods_money - $p_pay_discount),4);
                }
                else
                {
                    $bl = round($goods_money/($p_goods_money - $p_pay_discount),4);
                }
            }

            if($is_sign_card == 1 && count($p_card_arr) >0 && $p_pay_discount >0)
            {
                $inter = array_intersect($p_pack_products,$p_card_arr);
                if(count($inter) >0)
                {
                    $bl = round(($goods_money -$p_card_money -$discount)/($p_goods_money - $p_card_money - $p_pay_discount),3);
                }
                else
                {
                    $bl = round(($goods_money - $discount)/($p_goods_money - $p_card_money - $p_pay_discount),3);
                }
            }

            if($bl > 1)
            {
                $bl = 1;
            }
            $product[$k]['order']['bl'] = $bl;

            $product[$k]['order']['uid'] = $uid;
            $product[$k]['order']['order_name'] = $this->makeOrder();
            $product[$k]['order']['trade_no'] = $trade_no;
            $product[$k]['order']['billno'] = $billno;
            $product[$k]['order']['time'] = $time;
            $product[$k]['order']['pay_time'] = $pay_time;
            $product[$k]['order']['update_pay_time'] = $update_pay_time;
            $product[$k]['order']['pay_name'] = $pay_name;
            $product[$k]['order']['pay_parent_id'] = $pay_parent_id;
            $product[$k]['order']['pay_id'] = $pay_id;
            $product[$k]['order']['shtime'] = $shtime;
            $product[$k]['order']['stime'] = $stime;
            $product[$k]['order']['goods_money'] = $goods_money;
            $product[$k]['order']['jf_money'] = number_format($this->calBl($p_jf_money,$bl),2,'.','');
            $product[$k]['order']['method_money'] = number_format($this->calBl($p_method_money,$bl),2,'.','');
            $product[$k]['order']['post_discount'] = number_format($this->calBl($p_post_discount,$bl),2,'.','');
            $product[$k]['order']['card_money'] = number_format($this->calBl($p_card_money, $bl),2,'.','');
            $product[$k]['order']['use_card'] = '';
            $product[$k]['order']['pmoney'] = $goods_money;
            $product[$k]['order']['pay_status'] = $pay_status;
            $product[$k]['order']['bank_discount'] = number_format($this->calBl($p_bank_discount,$bl),2,'.','');
            $product[$k]['order']['fp'] = $fp;
            $product[$k]['order']['fp_dz'] = $fp_dz;
            $product[$k]['order']['operation_id'] = $operation_id;
            $product[$k]['order']['score'] = number_format($this->calBl($p_score,$bl),2,'.','');
            $product[$k]['order']['address_id'] = $address_id;
            $product[$k]['order']['use_jf'] = number_format($this->calBl($p_use_jf,$bl),2,'.','');
            $product[$k]['order']['order_status'] = $order_status;
            $product[$k]['order']['invoice_money'] = number_format($this->calBl($p_invoice_money,$bl),2,'.','');
            $product[$k]['order']['channel'] = $channel;
            $product[$k]['order']['use_money_deduction'] = number_format($this->calBl($p_use_money_deduction,$bl),2,'.','');
            $product[$k]['order']['order_type'] = $order_type;
            $product[$k]['order']['sales_channel'] = $sales_channel;
            $product[$k]['order']['pay_discount'] = $discount;
            $product[$k]['order']['new_pay_discount'] = number_format($this->calBl($p_new_pay_discount,$bl),2,'.','');
            $product[$k]['order']['fresh_discount'] = number_format($this->calBl($p_fresh_discount,$bl),2,'.','');
            $product[$k]['order']['jd_discount'] = number_format($this->calBl($p_jd_discount,$bl),2,'.','');
            $product[$k]['order']['version'] = $version;
            $product[$k]['order']['sync_status'] = $sync_status;
            $product[$k]['order']['last_modify_time'] = $last_modify_time;
            //$product[$k]['order']['cang_id'] = $cang_id;
            if(!empty($val['cang_id']))
            {
                $product[$k]['order']['cang_id'] = $val['cang_id'];
            }
            else
            {
                $product[$k]['order']['cang_id'] = $cang_id;
            }
            $product[$k]['order']['deliver_type'] = $deliver_type;
            $product[$k]['order']['sheet_show_price'] = $sheet_show_price;
            $product[$k]['order']['show_status'] = $show_status;
            $product[$k]['order']['store_id'] = $store_id;
            $product[$k]['order']['fresh_no'] = $fresh_no;
            $product[$k]['order']['fp_id_no'] = $fp_id_no;
            $product[$k]['order']['no_stock'] = $no_stock;
            $product[$k]['order']['kp_type'] = $kp_type;
            $product[$k]['order']['balance_payed'] = $balance_payed;

            //money
            $product[$k]['order']['money'] = number_format($this->calMoney($product[$k]['order']),2,'.','');

            $product[$k]['order']['address'] = $order_address;
            $product[$k]['order']['invoice'] = $order_invoice;
            $product[$k]['order']['product'] = $product[$k]['product'];
            //unset($product[$k]['order']['bl']);
            unset($product[$k]['product']);
        }

        //重新构建
        $is_cd = 0;

        //KEY
        $product_key = '';
        foreach($product as $k =>$v)
        {
            $product_key = $k;
        }

        $s_card_money = 0;
        $s_jf_money = 0;
        $s_method_money = 0;
        $s_bank_discount = 0;
        $s_score = 0;
        $s_use_jf = 0;
        $s_invoice_money = 0;
        $s_use_money_deduction = 0;
        $s_new_pay_discount = 0;
        $s_post_discount = 0;
        $s_fresh_discount = 0;
        $s_jd_discount = 0;

        $pack_count = count($product);
        foreach($product as $k =>$v)
        {
            foreach($v['order']['product'] as $key=>$val)
            {
                //单品
                if($is_sign_card == 1)
                {
                    if(in_array($val['product_id'],$p_card_arr) && $is_cd == 0)
                    {
                        $product[$k]['order']['card_money'] = $p_card_money;
                        $product[$k]['order']['use_card'] = $use_card;
                        $is_cd =1;
                    }
                    elseif(empty($product[$k]['order']['use_card']))
                    {
                        $product[$k]['order']['card_money'] = 0;
                    }
                }
                else
                {
                    //全场
                    if($pack_count == 1)
                    {
                        $product[$k]['order']['use_card'] = $use_card;
                    }
                    else
                    {
                        $product[$k]['order']['use_card'] = '';
                    }
                }
            }

            if($k != $product_key)
            {
                $s_card_money += $product[$k]['order']['card_money'];
                $s_jf_money += $product[$k]['order']['jf_money'];
                $s_method_money += $product[$k]['order']['method_money'];
                $s_post_discount += $product[$k]['order']['post_discount'];
                $s_bank_discount += $product[$k]['order']['bank_discount'];
                $s_score += $product[$k]['order']['score'];
                $s_use_jf += $product[$k]['order']['use_jf'];
                $s_invoice_money += $product[$k]['order']['invoice_money'];
                $s_use_money_deduction += $product[$k]['order']['use_money_deduction'];
                $s_new_pay_discount += $product[$k]['order']['new_pay_discount'];
                $s_fresh_discount += $product[$k]['order']['fresh_discount'];
                $s_jd_discount += $product[$k]['order']['jd_discount'];
            }

            $product[$k]['order']['money'] = number_format($this->calMoney($product[$k]['order']),2,'.','');
        }

        //检验
        $product[$product_key]['order']['card_money'] = $p_card_money - $s_card_money;
        $product[$product_key]['order']['jf_money'] = $p_jf_money - $s_jf_money;
        $product[$product_key]['order']['method_money'] = $p_method_money- $s_method_money;
        $product[$product_key]['order']['post_discount'] = $p_post_discount- $s_post_discount;
        $product[$product_key]['order']['bank_discount'] = $p_bank_discount - $s_bank_discount;
        $product[$product_key]['order']['score'] = $p_score - $s_score;
        $product[$product_key]['order']['use_jf'] = $p_use_jf - $s_use_jf;
        $product[$product_key]['order']['invoice_money'] = $p_invoice_money - $s_invoice_money;
        $product[$product_key]['order']['use_money_deduction'] = $p_use_money_deduction - $s_use_money_deduction;
        $product[$product_key]['order']['new_pay_discount'] = $p_new_pay_discount - $s_new_pay_discount;
        $product[$product_key]['order']['fresh_discount'] = $p_fresh_discount - $s_fresh_discount;
        $product[$product_key]['order']['jd_discount'] = $p_jd_discount - $s_jd_discount;
        foreach($product as $k =>$v)
        {
            $product[$k]['order']['money'] = number_format($this->calMoney($product[$k]['order']),2,'.','');
        }

        //验证
        $son_money = 0;
        $is_false = 0;
        foreach($product as $k =>$v)
        {
            $son_money += $v['order']['money'];
            if($v['order']['money'] < 0)
            {
                $is_false = 1;
            }
        }
        $son_money = number_format($son_money,2,'.','');

        //积点
        $son_jd = array();

        //写入数据
        if($p_money != $son_money || $is_false == 1)
        {
            $this->splitLog($params['order_name'],$product);
            $this->ci->load->library("notifyv1");
            $send_params = [
                "mobile"  => '15216691217',
                "message" => "拆单金额错误,订单号:".$params['order_name'].',触发时间:'.date('Y-m-d H:i:s'),
            ];
            $this->ci->notifyv1->send('sms','send',$send_params);
            return array('code'=>300,'msg'=>'拆单金额错误');
        }
        else
        {
            foreach($product as $k=>$v)
            {
                $check_order_name = $this->ci->order_model->dump(array('order_name' =>$v['order']['order_name']));
                if(empty($check_order_name))
                {
                    $son_order_name = $v['order']['order_name'];
                }
                else
                {
                    $son_order_name = $this->makeOrder();
                }

                $son_order = array(
                    'uid'=>$v['order']['uid'],
                    'order_name'=>$son_order_name,
                    'trade_no'=>$v['order']['trade_no'],
                    'billno'=>$v['order']['billno'],
                    'time'=>$v['order']['time'],
                    'pay_time'=>$v['order']['pay_time'],
                    'update_pay_time'=>$v['order']['update_pay_time'],
                    'pay_name'=>$v['order']['pay_name'],
                    'pay_parent_id'=>$v['order']['pay_parent_id'],
                    'pay_id'=>$v['order']['pay_id'],
                    'shtime'=>$v['order']['shtime'],
                    'stime'=>$v['order']['stime'],
                    'goods_money'=>$v['order']['goods_money'],
                    'jf_money'=>$v['order']['jf_money'],
                    'method_money'=>$v['order']['method_money'],
                    'post_discount'=>$v['order']['post_discount'],
                    'card_money'=>$v['order']['card_money'],
                    'use_card'=>$v['order']['use_card'],
                    'pmoney'=>$v['order']['pmoney'],
                    'pay_status'=>$v['order']['pay_status'],
                    'bank_discount'=>$v['order']['bank_discount'],
                    'fp'=>$v['order']['fp'],
                    'fp_dz'=>$v['order']['fp_dz'],
                    'operation_id'=>$v['order']['operation_id'],
                    'score'=>$v['order']['score'],
                    'address_id'=>$v['order']['address_id'],
                    'use_jf'=>$v['order']['use_jf'],
                    'order_status'=>$v['order']['order_status'],
                    'invoice_money'=>$v['order']['invoice_money'],
                    'channel'=>$v['order']['channel'],
                    'use_money_deduction'=>$v['order']['use_money_deduction'],
                    'order_type'=>$v['order']['order_type'],
                    'sales_channel'=>$v['order']['sales_channel'],
                    'pay_discount'=>$v['order']['pay_discount'],
                    'new_pay_discount'=>$v['order']['new_pay_discount'],
                    'fresh_discount'=>$v['order']['fresh_discount'],
                    'jd_discount'=>$v['order']['jd_discount'],
                    'version'=>$v['order']['version'],
                    'sync_status'=>$v['order']['sync_status'],
                    'last_modify_time'=>$v['order']['last_modify_time'],
                    'cang_id'=>$v['order']['cang_id'],
                    'deliver_type'=>$v['order']['deliver_type'],
                    'sheet_show_price'=>$v['order']['sheet_show_price'],
                    'show_status'=>$v['order']['show_status'],
                    'money'=>$v['order']['money'],
                    'p_order_id'=>$p_order_id,
                    'store_id'=>$v['order']['store_id'],
                    'fresh_no'=>$v['order']['fresh_no'],
                    'fp_id_no'=>$v['order']['fp_id_no'],
                    'no_stock'=>$v['order']['no_stock'],
                    'kp_type'=>$v['order']['kp_type'],
                    'balance_payed'=>$v['order']['balance_payed'],
                );

                //极速达
                if($v['order']['order_type'] == 3 || $v['order']['order_type'] == 4)
                {
                    $time_arr = explode('-',$son_order['stime']);
                    $s_time = strtotime($time_arr[0]);
                    $e_time = strtotime($time_arr[1]);
                    $m_time = $e_time - $s_time;

                    if($m_time >0 && $m_time < 3600)
                    {
                        if($this->use29Min === true){
                            $now_time = date('H:i');
                            $end_time = date('H:i',time()+1800);
                            $son_order['stime'] = $now_time.'-'.$end_time;
                        }else{
                            $now_time = date('H:i',time()+1800);
                            $end_time = date('H:i',time()+3600);
                            $son_order['stime'] = $now_time.'-'.$end_time;
                        }
                    }
                }

                //自提拆单
                if($v['order']['order_type'] == 4)
                {
                    $son_order['pickup_store_id'] = $p_pickup_store_id;
                    $son_order['pickup_code'] = $p_pickup_code;
                }

                $order_id = $this->ci->order_model->addOrder($son_order);

                //o2o
                $o2o_id = 0;
                $order_type = $v['order']['order_type'];
                if($order_type == 3 || $order_type == 4)
                {
                    $o2o_order = array(
                        'p_order_id' => $order_id,
                        'uid' => $son_order['uid'],
                        'order_name' => $son_order['order_name'],
                        'store_id' => 0,//老版实体门店,忽设置
                        'money' => $son_order['money'],
                        'goods_money' => $son_order['goods_money'],
                        'jf_money' => $son_order['jf_money'],
                        'method_money' => $son_order['method_money'],
                        'post_discount' => $son_order['post_discount'],
                        'card_money' => $son_order['card_money'],
                        'pmoney' => $son_order['pmoney'],
                        'pay_status' => $son_order['pay_status'],
                        'operation_id' => $son_order['operation_id'],
                        'score' => $son_order['score'],
                        'use_card' => $son_order['use_card'],
                        'sync_status' => $son_order['sync_status'],
                        'address' => '',
                        'send_type' => 1,
                        'pay_discount' => $son_order['pay_discount'],
                        'use_money_deduction' => $son_order['use_money_deduction'],
                        'fresh_discount' => $son_order['fresh_discount'],
                        'jd_discount' => $son_order['jd_discount'],
                        'fresh_no' => $son_order['fresh_no'],
                        'no_stock'=>$son_order['no_stock'],
                    );
                    $o2o_id = $this->ci->o2o_model->createChildOrder($o2o_order);
                }

                //地址
                $son_address = $v['order']['address'];
                $son_address['order_id'] = $order_id;
                unset($son_address['id']);
                $this->ci->order_model->addOrderAddr($son_address);

                //发票
                if(!empty($v['order']['invoice']))
                {
                    $son_invoice = $v['order']['invoice'];
                    $son_invoice['order_id'] = $order_id;
                    unset($son_invoice['id']);
                    $this->ci->order_model->addOrderInvoice($son_invoice);
                }

                //电子发票
                if(!empty($order_einvoice))
                {
                    if(!empty($order_einvoice['mobile']))
                    {
                        $mobile = $order_einvoice['mobile'];
                    }
                    else
                    {
                        $mobile = $order_address['mobile'];
                    }
                    $this->ci->order_model->addDfp($son_order['order_name'],$mobile,$order_einvoice['dfp']);
                }

                //商品
                $son_product = $v['order']['product'];
                $this->orderInsertPro($v['order']['uid'],$order_id,$son_product,$order_type,$o2o_id,$p_order_id,$son_order['order_name']);

                //积点
                if($son_order['jd_discount'] > 0)
                {
                    $jd = array();
                    $jd['order_name'] = $son_order['order_name'];
                    $jd['amount'] = $son_order['jd_discount'];
                    $jd['bl'] = $v['order']['bl'];
                    array_push($son_jd,$jd);
                }
                else if($son_order['pay_parent_id'] == '17')
                {
                    $jd = array();
                    $jd['order_name'] = $son_order['order_name'];
                    $jd['amount'] = $son_order['money'];
                    $jd['bl'] = $v['order']['bl'];
                    array_push($son_jd,$jd);
                }
            }

            //积点
            if(count($son_jd) >0)
            {
                $this->ci->load->model('order_jd_model');
                $order_jd = $this->ci->order_jd_model->getList('id,order_name,oms_no,amount,trade_type',array('order_name'=>$params['order_name']));

                if(empty($order_jd))
                {
                    $this->ci->load->library("notifyv1");
                    $send_params = [
                        "mobile"  => '15216691217',
                        "message" => "积点金额错误,订单号:".$params['order_name'].',触发时间:'.date('Y-m-d H:i:s'),
                    ];
                    $this->ci->notifyv1->send('sms','send',$send_params);
                    return array('code'=>300,'msg'=>'积点金额错误');
                }

                if(count($order_jd) == 1)
                {
                    foreach($son_jd as $k=>$v)
                    {
                        $jd_arr = array();
                        $jd_arr['order_name'] = $v['order_name'];
                        $jd_arr['oms_no'] = $order_jd[0]['oms_no'];
                        $jd_arr['amount'] = $v['amount'];
                        $jd_arr['trade_type'] = $order_jd[0]['trade_type'];
                        $jd_arr['b2o_order_name'] = $params['order_name'];
                        $this->ci->order_jd_model->add($jd_arr);
                    }
                }
                else if(count($order_jd) > 1)
                {
                    if(count($son_jd) == 1)
                    {
                        $son_key = count($son_jd);
                        foreach($order_jd as $k=>$v)
                        {
                            $oms_no = $v['oms_no'];
                            $amount = $v['amount'];
                            $trade_type = $v['trade_type'];
                            $order_name = $params['order_name'];
                            $s_o_amount = 0;
                            foreach($son_jd as $key=>$val)
                            {
                                $num_amount = number_format($amount * $val['bl'],2,'.','');
                                $jd_arr = array();
                                $jd_arr['order_name'] = $val['order_name'];
                                $jd_arr['oms_no'] = $oms_no;
                                $jd_arr['amount'] = $num_amount;
                                $jd_arr['trade_type'] = $trade_type;
                                $jd_arr['b2o_order_name'] = $order_name;
                                if(($key+1) != $son_key)
                                {
                                    $s_o_amount += (float)$num_amount;
                                }
                                if(($key+1) == $son_key)
                                {
                                    $jd_arr['amount'] = $amount - $s_o_amount;
                                }
                                $this->ci->order_jd_model->add($jd_arr);
                            }
                        }
                    }
                    else
                    {
                        $jd_all = array();
                        $son_key = count($son_jd);
                        foreach($order_jd as $k=>$v)
                        {
                            $oms_no = $v['oms_no'];
                            $amount = $v['amount'];
                            $trade_type = $v['trade_type'];
                            $order_name = $params['order_name'];
                            $s_o_amount = 0;
                            foreach($son_jd as $key=>$val)
                            {
                                $num_amount = number_format($amount * $val['bl'],2,'.','');
                                $jd_arr = array();
                                $jd_arr['order_name'] = $val['order_name'];
                                $jd_arr['oms_no'] = $oms_no;
                                $jd_arr['amount'] = $num_amount;
                                $jd_arr['trade_type'] = $trade_type;
                                $jd_arr['b2o_order_name'] = $order_name;
                                if(($key+1) != $son_key)
                                {
                                    $s_o_amount += (float)$num_amount;
                                }
                                if(($key+1) == $son_key)
                                {
                                    $jd_arr['amount'] = $amount - $s_o_amount;
                                }
                                $jd_arr['order_amount'] = $val['amount'];
                                array_push($jd_all,$jd_arr);
                            }
                        }

                        foreach($son_jd as $key=>$val)
                        {
                            $total_amount = 0;
                            $jd_key = 0;
                            foreach($jd_all as $k=>$v)
                            {
                                if($val['order_name'] == $v['order_name'])
                                {
                                    $total_amount += $v['amount'];
                                    $jd_key = $k;
                                }
                            }
                            $jd_all[$jd_key]['total_amount'] = $total_amount;
                        }

                        foreach($jd_all as $k=>$v)
                        {
                            if(isset($v['total_amount']))
                            {
                                if($v['total_amount'] != $v['order_amount'])
                                {
                                    if($v['total_amount'] > $v['order_amount'])
                                    {
                                        $diff = $v['total_amount'] - $v['order_amount'];
                                        $jd_all[$k]['amount'] = $v['amount'] - $diff;
                                    }
                                    else if($v['total_amount'] < $v['order_amount'])
                                    {
                                        $diff = $v['order_amount'] - $v['total_amount'];
                                        $jd_all[$k]['amount'] = $v['amount'] + $diff;
                                    }
                                }
                            }
                        }

                        foreach($jd_all as $key=>$val)
                        {
                            $add = array();
                            $add['order_name'] = $val['order_name'];
                            $add['oms_no'] = $val['oms_no'];
                            $add['amount'] = $val['amount'];
                            $add['trade_type'] = $val['trade_type'];
                            $add['b2o_order_name'] = $val['b2o_order_name'];

                            $this->ci->order_jd_model->add($add);
                        }
                    }
                }
            }

            //拆单状态
            $data = array(
                'p_order_id' =>'1'
            );
            $where = array(
                'order_name' => $params['order_name'],
                'p_order_id'=>'0',
            );
            $up_rows = $this->ci->b2o_parent_order_model->update_order($data, $where);
            if($up_rows == false)
            {
                $this->ci->db->trans_rollback();
                $this->splitLog($params['order_name'],$product);
                return array("code" => "300", "msg" => "拆单失败");
            }
        }

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();

            $this->splitLog($params['order_name'],$product);
            return array("code" => "300", "msg" => "拆单失败");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code'=>200,'msg'=>'succ');
    }

    /*
     * 创建子单
     */
    private function makeOrder()
    {
        $order_name = date("ymdi").rand_code(4);
        return $order_name;
    }

    /*
   *  计算订单金额
   */
    private function calMoney($order)
    {
        $goods_money = $order['goods_money'];
        $invoice_money = $order['invoice_money'];
        $method_money = $order['method_money'];
        $jf_money = $order['jf_money'];
        $card_money = $order['card_money'];
        $pay_discount = $order['pay_discount'];
        $new_pay_discount = $order['new_pay_discount'];
        $use_money_deduction = $order['use_money_deduction'];
        $post_discount = $order['post_discount'];
        $fresh_discount = $order['fresh_discount'];
        $jd_discount = $order['jd_discount'];
        $total = $goods_money + $invoice_money + $method_money;
        $money = $total - $jf_money - $card_money - $pay_discount - $new_pay_discount - $use_money_deduction - $post_discount - $fresh_discount - $jd_discount ;

        return $money;
    }

    /*
    *  计算比例
    */
    private function calBl($money,$bl)
    {
        $cost = round($money * $bl,2);
        return $cost;
    }

    /*
     * 拆单日志
     */
    private function splitLog($order_name, $data) {
        $data['order_name'] = $order_name;
        $this->ci->load->library('fdaylog');
        $db_log = $this->ci->load->database('db_log', TRUE);
        $this->ci->fdaylog->add($db_log, 'split_error', json_encode($data));
    }

    /*
    * 订单商品插入
    */
    private function orderInsertPro($uid, $order_id, $products,$order_type=1,$o2o_id = 0,$p_order_id,$order_name=0) {
        $cart_array = $products;
        if ($cart_array != null)
        {
            $giftTypeList = $this->_setProductType();
            $order_pro_type_list = $this->ci->config->item('order_product_type');

            $is_gift_price = 0;
            foreach ($cart_array as $cart_key => $item) {
                //商品类型
                $order_pro_type = isset($order_pro_type_list[$item['item_type']]) ? $order_pro_type_list[$item['item_type']] : '1';
                if ($item['price'] == 0 || $item['amount'] == 0) {
                    $order_pro_type = 3;
                }
                //商品积分处理，赠品没有积分
                $is_gift = false;
                if (in_array($order_pro_type, $giftTypeList)) {
                    $is_gift = true;
                    $score = 0;
                } else {
                    $score = $this->ci->b2o_parent_order_model->get_order_product_score($uid, $item);
                }
                $product_name = addslashes($item['name']);
                $total_money = $item ['amount'];
                $gg_name = $item['volume'] . '/' . $item['unit'];

                //组合商品
                $b2o_pro = $this->ci->b2o_parent_order_product_model->dump(array('order_id'=>$p_order_id,'product_id'=>$item['product_id'],'type'=>$order_pro_type));

                if(!empty($b2o_pro['group_products']))
                {
                    $group_products = $b2o_pro['group_products'];
                }
                else
                {
                    $group_products = '';
                }

                $order_product_data = array(
                    'order_id' => $order_id,
                    'product_name' => $product_name,
                    'product_id' => $item['product_id'],
                    'product_no' => $item['product_no'],
                    'gg_name' => $gg_name,
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'score' => $score,
                    'type' => $order_pro_type,
                    'total_money' => $total_money,
                    'discount'=> $item['discount'],
                    'group_products'=>$group_products,
                    'gift_price'=>$b2o_pro['gift_price'],
                    'user_gift_id'=>$b2o_pro['user_gift_id'],
                    'is_oversale'=>isset($item['is_oversale']) ? $item['is_oversale'] : 0,
                    'can_use_card'=>isset($b2o_pro['can_use_card'])?$b2o_pro['can_use_card']:1,
                );
                $order_pro_id = $this->ci->order_model->addOrderProduct($order_product_data);

                if($order_type == 3 || $order_type == 4)
                {
                    $son = array(
                        'order_product_id' => $order_pro_id,
                        'store_id' => 0,//老版虚拟门店，新版忽设置
                        'c_order_id' => $o2o_id
                    );
                    $this->ci->o2o_model->createChildOrderProduct($son);
                }

                //gift
                if(isset($item['user_gift_id']) && !empty($item['user_gift_id']))
                {
                    $this->ci->load->model('user_gifts_model');
                    $this->ci->user_gifts_model->set_user_gift($item['user_gift_id'],$order_id);
                }

                //gift price log
                if($b2o_pro['gift_price'] >0)
                {
                    $is_gift_price = 1;
                }
            }

            if($is_gift_price == 1)
            {
                $this->ci->load->model('gift_package_log_model');
                $this->ci->gift_package_log_model->add($order_name,1);
            }
        }
        else
        {
            return array("code" => "300", "msg" => '拆单商品错误');
        }
    }

    /**
     * 根据 GPS 获取 storeId、deliverId
     *
     * @param string $lonlat 经纬度
     * @param string $districtCode 区域代码
     * @return array
     */
    private function getTmsStore($lonlat, $districtCode)
    {
        $this->ci->load->bll('deliver');
        return $this->ci->bll_deliver->getTmsStore([
            'lonlat' => $lonlat,
            'districtCode' => $districtCode,
        ]);
    }


    /*
     * 检查购物车
     */
    private function check_cart_data($cart_info,$params)
    {
        //用户
        $this->ci->load->library('login');
        $this->ci->login->init($params['connect_id']);
        $uid = $this->ci->login->get_uid();
        $user = $this->ci->login->get_user();

        //cart -v3
        $this->ci->load->bll('apicart');
        $api_cart = array();
        $api_cart['cart_id'] = $uid;
        $api_cart['store_id_list'] = $params['store_id_list'];
        $api_cart['user'] = $uid;
        $api_cart['source'] = $params['source'];
        $api_cart['version'] = $params['version'];
        $api_cart['tms_region_type'] = $params['tms_region_type'];
        $now_cart_info = $this->ci->bll_apicart->get($api_cart);

        //cart -v2
        //$store_id_list = explode(',',$params['store_id_list']);
        //$cart = $this->ci->cart_v2_model->init($uid,$store_id_list,$user,$params['source'],$params['version'],$params['tms_region_type']);
        //$now_cart = $cart->getProducts()->validate()->promo()->total()->count()->checkout();
        //$json_cart = json_encode($now_cart);
        //$now_cart_info = json_decode($json_cart,true);

        //购物车为空
        if(empty($now_cart_info['products']))
        {
            return array('code'=>'302','msg'=>'购买商品异常，请返回购物车确认');
        }

        //购物车数量
        $now_count = count($now_cart_info['products']);
        $cart_count = count($cart_info['products']);
        if($now_count != $cart_count)
        {
            return array('code'=>'302','msg'=>'购买商品异常，请返回购物车确认');
        }

        $res = array('code'=>200,'msg'=>'succ');

        $is_pro_err = 0;
        $is_store_err = 0;
        $is_deliver_err = 0;
        $is_price_err = 0;
        $is_qty_err = 0;

        foreach($cart_info['products'] as $key=>$val)
        {
            if($val['product_id'] != $now_cart_info['products'][$key]['product_id'])
            {
                $is_pro_err =1;
            }

            if($val['store_id'] != $now_cart_info['products'][$key]['store_id'])
            {
                $is_store_err =1;
            }

            if($val['isTodayDeliver'] != $now_cart_info['products'][$key]['isTodayDeliver'])
            {
                $is_deliver_err =1;
            }

            if($val['price'] != $now_cart_info['products'][$key]['price'])
            {
                $is_price_err =1;
            }

            if($val['qty'] != $now_cart_info['products'][$key]['qty'])
            {
                $is_qty_err =1;
            }
        }

        if($is_pro_err == 1 || $is_store_err == 1 || $is_deliver_err == 1 || $is_price_err == 1 || $is_qty_err == 1)
        {
            $res['code'] = '302';
            $res['msg'] = '购买商品异常，请返回购物车确认';
        }

        return $res;
    }


    /*
     * 订单倒计时
     */
    private function showTimes($shtime,$stime)
    {
        $end_time = '';

        if(!empty($shtime) && !empty($stime))
        {
            $now_time = date('Ymd',time());
            $y_time = date('Y-m-d',strtotime($shtime));
            if($shtime == $now_time)
            {
                $k_time = explode('-',$stime);
                $s_time = $k_time[1];
                if(!empty($y_time) && !empty($s_time))
                {
                    $end_time = $y_time.' '.$s_time.':00';
                }
            }
        }
        return $end_time;
    }

    /*
     * 拆单 － rbac拆单
     */
    public function orderSplitRbac($params)
    {
        //必要参数验证start
        $required_fields = array(
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        $gift = $this->giftSplit($params['order_name']);
        if($gift['code'] == '200')
        {
            return array('code'=>'200','msg'=>'succ');
        }

        
        //事务 start
        $this->ci->db->trans_begin();

        $order = $this->ci->b2o_parent_order_model->dump(array('order_name' => $params['order_name'],'pay_status'=>'1','order_status' =>'1','p_order_id'=>'0'));
        if (empty($order)) {
            $msg = array('code'=>'300','msg'=>'订单不存在');
            return $msg;
        }

        if($order['operation_id'] == 5)
        {
            //$msg = array('code'=>'300','msg'=>'已取消订单');
            //return $msg;
        }

        $order_address = $this->ci->b2o_parent_order_address_model->dump(array('order_id' =>$order['id']));
        if (empty($order_address)) {
            $msg = array('code'=>'300','msg'=>'订单地址异常');
            return $msg;
        }

        $order_invoice = $this->ci->b2o_parent_order_invoice_model->dump(array('order_id' =>$order['id']));

        $order_package = $this->ci->b2o_parent_order_model->get_order_package($params['order_name']);
        if (empty($order_package)) {
            $msg = array('code'=>'300','msg'=>'订单包裹异常');
            return $msg;
        }

        $order_einvoice = $this->ci->order_einvoices_model->dump(array('order_name' =>$params['order_name']));


        $package = json_decode($order_package['content'],true);

        $p_order_id = $order['id'];
        $p_money = $order['money'];
        $p_use_money_deduction = $order['use_money_deduction'];
        $p_goods_money = $order['goods_money'];
        $p_jf_money = $order['jf_money'];
        $p_card_money = $order['card_money'];
        $p_method_money = $order['method_money'];
        $p_invoice_money = $order['invoice_money'];
        $p_new_pay_discount = $order['new_pay_discount'];
        $p_score = $order['score'];
        $p_bank_discount = $order['bank_discount'];
        $p_use_jf= $order['use_jf'];
        $p_post_discount = $order['post_discount'];
        $p_pay_discount = $order['pay_discount'];
        $p_fresh_discount = $order['fresh_discount'];
        $p_jd_discount = $order['jd_discount'];

        //自提拆单
        $p_pickup_store_id = $order['pickup_store_id'];
        $p_pickup_code = $order['pickup_code'];

        $uid = $order['uid'];
        $fp = $order['fp'];
        $fp_dz = $order['fp_dz'];
        $fp_id_no = $order['fp_id_no'];
        $address_id = $order['address_id'];
        $trade_no = $order['trade_no'];
        $pay_name = $order['pay_name'];
        $pay_id = $order['pay_id'];
        $pay_parent_id = $order['pay_parent_id'];
        $billno = $order['billno'];
        $pay_time = $order['pay_time'];
        $update_pay_time = $order['update_pay_time'];
        $pay_status = $order['pay_status'];
        $time = $order['time'];
        $operation_id = $order['operation_id'];
        $use_card = $order['use_card'];
        $order_status = $order['order_status'];
        $channel = $order['channel'];
        $sales_channel = $order['sales_channel'];
        $version = $order['version'];
        $sync_status = $order['sync_status'];
        $last_modify_time = $order['last_modify_time'];
        $cang_id = $order['cang_id'];
        $deliver_type = $order['deliver_type'];
        $sheet_show_price = $order['sheet_show_price'];
        $show_status = $order['show_status'];
        $fresh_no = $order['fresh_no'];
        $no_stock = $order['no_stock'];
        $kp_type = $order['kp_type'];
        $balance_payed = $order['balance_payed'];

        //单品券
        $is_sign_card = 0;
        $p_card_arr = array();
        if(!empty($use_card) && $p_card_money >0)
        {
            $card = $this->ci->card_model->dump(array('card_number' =>$use_card));
            if(!empty($card) && !empty($card['product_id']))
            {
                $is_sign_card = 1;
                $p_card_arr = explode(',',$card['product_id']);
            }
        }

        $pro = array();
        //构建商品
        foreach($package as $k=>$v)
        {
            $shtime = $v['chose_sendtime']['shtime'];
            $stime = $v['chose_sendtime']['stime'];
            foreach($v['item'] as $key=>$val)
            {
                $val['package_type'] = $v['package_type'];
                $val['tag'] = $v['tag'];
                $val['sendtime'] = $shtime.'|'.$stime;
                //自提拆单
                if(isset($v['store']))
                {
                    $val['store'] = $v['store'];
                }
                $val['cang_id'] = $v['cang_id'];
                array_push($pro,$val);
            }
        }

        $store_ids = array_column($pro,'store_id','');
        $store_ids = array_unique($store_ids);
        $product = array();
        foreach($store_ids as $k=>$v)
        {
            foreach($pro as $key=>$val)
            {
                if($v == $val['store_id'])
                {
                    $s_p_key = $val['store_id'].'|'.$val['tag'];
                    $product[$s_p_key]['product'][] = $val;
                }
            }
        }

        //构建订单
        foreach($product as $k=>$v)
        {
            $goods_money = 0;
            $discount = 0;
            $shtime = '';
            $stime = '';
            $order_type = 1;
            $store_id = 0;
            $p_pack_products = array();

            foreach($v['product'] as $key=>$val)
            {
                $goods_money += $val['price']*$val['qty'];
                $discount += $val['discount'];
                $sendtime = explode('|',$val['sendtime']);
                $shtime = $sendtime[0];
                $stime = $sendtime[1];

                if($val['package_type'] == 1)
                {
                    $order_type = 3;
                    //自提拆单
                    if(isset($val['store']))
                    {
                        if($val['store']['is_select'] == '1')
                        {
                            $order_type = 4;
                        }
                    }
                }
                else if($val['package_type'] == 3)
                {
                    $order_type = 5;
                }
                $store_id = $val['store_id'];
                array_push($p_pack_products,$val['product_id']);
            }

            //分拆
            $bl = round($goods_money/$p_goods_money,4);

            if($is_sign_card == 1 && count($p_card_arr) >0)
            {
                $inter = array_intersect($p_pack_products,$p_card_arr);
                if(count($inter) >0)
                {
                    $bl = round(($goods_money -$p_card_money)/($p_goods_money - $p_card_money),4);
                }
                else
                {
                    $bl = round($goods_money/($p_goods_money - $p_card_money),4);
                }
            }

            if($p_pay_discount >0)
            {
                if($discount >0)
                {
                    $bl = round(($goods_money - $discount)/($p_goods_money - $p_pay_discount),4);
                }
                else
                {
                    $bl = round($goods_money/($p_goods_money - $p_pay_discount),4);
                }
            }

            if($is_sign_card == 1 && count($p_card_arr) >0 && $p_pay_discount >0)
            {
                $inter = array_intersect($p_pack_products,$p_card_arr);
                if(count($inter) >0)
                {
                    $bl = round(($goods_money -$p_card_money -$discount)/($p_goods_money - $p_card_money - $p_pay_discount),4);
                }
                else
                {
                    $bl = round(($goods_money - $discount)/($p_goods_money - $p_card_money - $p_pay_discount),4);
                }
            }

            if($bl > 1)
            {
                $bl = 1;
            }

            //特殊处理
            $t_bl = 0.001;
            if($bl < $t_bl)
            {
                $bl = 0;
            }
            $product[$k]['order']['bl'] = $bl;

            $product[$k]['order']['uid'] = $uid;
            $product[$k]['order']['order_name'] = $this->makeOrder();
            $product[$k]['order']['trade_no'] = $trade_no;
            $product[$k]['order']['billno'] = $billno;
            $product[$k]['order']['time'] = $time;
            $product[$k]['order']['pay_time'] = $pay_time;
            $product[$k]['order']['update_pay_time'] = $update_pay_time;
            $product[$k]['order']['pay_name'] = $pay_name;
            $product[$k]['order']['pay_parent_id'] = $pay_parent_id;
            $product[$k]['order']['pay_id'] = $pay_id;
            $product[$k]['order']['shtime'] = $shtime;
            $product[$k]['order']['stime'] = $stime;
            $product[$k]['order']['goods_money'] = $goods_money;
            $product[$k]['order']['jf_money'] = number_format($this->calBl($p_jf_money,$bl),2,'.','');
            $product[$k]['order']['method_money'] = number_format($this->calBl($p_method_money,$bl),2,'.','');
            $product[$k]['order']['post_discount'] = number_format($this->calBl($p_post_discount,$bl),2,'.','');
            $product[$k]['order']['card_money'] = number_format($this->calBl($p_card_money, $bl),2,'.','');
            $product[$k]['order']['use_card'] = '';
            $product[$k]['order']['pmoney'] = $goods_money;
            $product[$k]['order']['pay_status'] = $pay_status;
            $product[$k]['order']['bank_discount'] = number_format($this->calBl($p_bank_discount,$bl),2,'.','');
            $product[$k]['order']['fp'] = $fp;
            $product[$k]['order']['fp_dz'] = $fp_dz;
            $product[$k]['order']['operation_id'] = $operation_id;
            $product[$k]['order']['score'] = number_format($this->calBl($p_score,$bl),2,'.','');
            $product[$k]['order']['address_id'] = $address_id;
            $product[$k]['order']['use_jf'] = number_format($this->calBl($p_use_jf,$bl),2,'.','');
            $product[$k]['order']['order_status'] = $order_status;
            $product[$k]['order']['invoice_money'] = number_format($this->calBl($p_invoice_money,$bl),2,'.','');
            $product[$k]['order']['channel'] = $channel;
            $product[$k]['order']['use_money_deduction'] = number_format($this->calBl($p_use_money_deduction,$bl),2,'.','');
            $product[$k]['order']['order_type'] = $order_type;
            $product[$k]['order']['sales_channel'] = $sales_channel;
            $product[$k]['order']['pay_discount'] = $discount;
            $product[$k]['order']['new_pay_discount'] = number_format($this->calBl($p_new_pay_discount,$bl),2,'.','');
            $product[$k]['order']['fresh_discount'] = number_format($this->calBl($p_fresh_discount,$bl),2,'.','');
            $product[$k]['order']['jd_discount'] = number_format($this->calBl($p_jd_discount,$bl),2,'.','');
            $product[$k]['order']['version'] = $version;
            $product[$k]['order']['sync_status'] = $sync_status;
            $product[$k]['order']['last_modify_time'] = $last_modify_time;
            //$product[$k]['order']['cang_id'] = $cang_id;
            if(!empty($val['cang_id']))
            {
                $product[$k]['order']['cang_id'] = $val['cang_id'];
            }
            else
            {
                $product[$k]['order']['cang_id'] = $cang_id;
            }
            $product[$k]['order']['deliver_type'] = $deliver_type;
            $product[$k]['order']['sheet_show_price'] = $sheet_show_price;
            $product[$k]['order']['show_status'] = $show_status;
            $product[$k]['order']['store_id'] = $store_id;
            $product[$k]['order']['fresh_no'] = $fresh_no;
            $product[$k]['order']['fp_id_no'] = $fp_id_no;
            $product[$k]['order']['no_stock'] = $no_stock;
            $product[$k]['order']['kp_type'] = $kp_type;
            $product[$k]['order']['balance_payed'] = $balance_payed;

            //money
            $product[$k]['order']['money'] = number_format($this->calMoney($product[$k]['order']),2,'.','');

            $product[$k]['order']['address'] = $order_address;
            $product[$k]['order']['invoice'] = $order_invoice;
            $product[$k]['order']['product'] = $product[$k]['product'];
            //unset($product[$k]['order']['bl']);
            unset($product[$k]['product']);
        }

        //重新构建
        $is_cd = 0;

        //KEY
        $product_key = '';
        foreach($product as $k =>$v)
        {
            $product_key = $k;
        }

        $s_card_money = 0;
        $s_jf_money = 0;
        $s_method_money = 0;
        $s_bank_discount = 0;
        $s_score = 0;
        $s_use_jf = 0;
        $s_invoice_money = 0;
        $s_use_money_deduction = 0;
        $s_new_pay_discount = 0;
        $s_post_discount = 0;
        $s_fresh_discount = 0;
        $s_jd_discount = 0;

        $pack_count = count($product);
        foreach($product as $k =>$v)
        {
            foreach($v['order']['product'] as $key=>$val)
            {
                //单品
                if($is_sign_card == 1)
                {
                    if(in_array($val['product_id'],$p_card_arr) && $is_cd == 0)
                    {
                        $product[$k]['order']['card_money'] = $p_card_money;
                        $product[$k]['order']['use_card'] = $use_card;
                        $is_cd =1;
                    }
                    elseif(empty($product[$k]['order']['use_card']))
                    {
                        $product[$k]['order']['card_money'] = 0;
                    }
                }
                else
                {
                    //全场
                    if($pack_count == 1)
                    {
                        $product[$k]['order']['use_card'] = $use_card;
                    }
                    else
                    {
                        $product[$k]['order']['use_card'] = '';
                    }
                }
            }

            if($k != $product_key)
            {
                $s_card_money += $product[$k]['order']['card_money'];
                $s_jf_money += $product[$k]['order']['jf_money'];
                $s_method_money += $product[$k]['order']['method_money'];
                $s_post_discount += $product[$k]['order']['post_discount'];
                $s_bank_discount += $product[$k]['order']['bank_discount'];
                $s_score += $product[$k]['order']['score'];
                $s_use_jf += $product[$k]['order']['use_jf'];
                $s_invoice_money += $product[$k]['order']['invoice_money'];
                $s_use_money_deduction += $product[$k]['order']['use_money_deduction'];
                $s_new_pay_discount += $product[$k]['order']['new_pay_discount'];
                $s_fresh_discount += $product[$k]['order']['fresh_discount'];
                $s_jd_discount += $product[$k]['order']['jd_discount'];
            }

            $product[$k]['order']['money'] = number_format($this->calMoney($product[$k]['order']),2,'.','');
        }

        //检验
        $product[$product_key]['order']['card_money'] = $p_card_money - $s_card_money;
        $product[$product_key]['order']['jf_money'] = $p_jf_money - $s_jf_money;
        $product[$product_key]['order']['method_money'] = $p_method_money- $s_method_money;
        $product[$product_key]['order']['post_discount'] = $p_post_discount- $s_post_discount;
        $product[$product_key]['order']['bank_discount'] = $p_bank_discount - $s_bank_discount;
        $product[$product_key]['order']['score'] = $p_score - $s_score;
        $product[$product_key]['order']['use_jf'] = $p_use_jf - $s_use_jf;
        $product[$product_key]['order']['invoice_money'] = $p_invoice_money - $s_invoice_money;
        $product[$product_key]['order']['use_money_deduction'] = $p_use_money_deduction - $s_use_money_deduction;
        $product[$product_key]['order']['new_pay_discount'] = $p_new_pay_discount - $s_new_pay_discount;
        $product[$product_key]['order']['fresh_discount'] = $p_fresh_discount - $s_fresh_discount;
        $product[$product_key]['order']['jd_discount'] = $p_jd_discount - $s_jd_discount;
        foreach($product as $k =>$v)
        {
            $product[$k]['order']['money'] = number_format($this->calMoney($product[$k]['order']),2,'.','');
        }

        //验证
        $son_money = 0;
        $is_false = 0;
        foreach($product as $k =>$v)
        {
            $son_money += $v['order']['money'];
            if($v['order']['money'] < 0)
            {
                $is_false = 1;
            }
        }
        $son_money = number_format($son_money,2,'.','');

        //积点
        $son_jd = array();

        //写入数据
        if($p_money != $son_money || $is_false == 1)
        {
            $this->splitLog($params['order_name'],$product);
            $this->ci->load->library("notifyv1");
            $send_params = [
                "mobile"  => '15216691217',
                "message" => "拆单金额错误,订单号:".$params['order_name'].',触发时间:'.date('Y-m-d H:i:s'),
            ];
            $this->ci->notifyv1->send('sms','send',$send_params);
            return array('code'=>300,'msg'=>'拆单金额错误');
        }
        else
        {
            foreach($product as $k=>$v)
            {
                $check_order_name = $this->ci->order_model->dump(array('order_name' =>$v['order']['order_name']));
                if(empty($check_order_name))
                {
                    $son_order_name = $v['order']['order_name'];
                }
                else
                {
                    $son_order_name = $this->makeOrder();
                }

                $son_order = array(
                    'uid'=>$v['order']['uid'],
                    'order_name'=>$son_order_name,
                    'trade_no'=>$v['order']['trade_no'],
                    'billno'=>$v['order']['billno'],
                    'time'=>$v['order']['time'],
                    'pay_time'=>$v['order']['pay_time'],
                    'update_pay_time'=>$v['order']['update_pay_time'],
                    'pay_name'=>$v['order']['pay_name'],
                    'pay_parent_id'=>$v['order']['pay_parent_id'],
                    'pay_id'=>$v['order']['pay_id'],
                    'shtime'=>$v['order']['shtime'],
                    'stime'=>$v['order']['stime'],
                    'goods_money'=>$v['order']['goods_money'],
                    'jf_money'=>$v['order']['jf_money'],
                    'method_money'=>$v['order']['method_money'],
                    'post_discount'=>$v['order']['post_discount'],
                    'card_money'=>$v['order']['card_money'],
                    'use_card'=>$v['order']['use_card'],
                    'pmoney'=>$v['order']['pmoney'],
                    'pay_status'=>$v['order']['pay_status'],
                    'bank_discount'=>$v['order']['bank_discount'],
                    'fp'=>$v['order']['fp'],
                    'fp_dz'=>$v['order']['fp_dz'],
                    'operation_id'=>$v['order']['operation_id'],
                    'score'=>$v['order']['score'],
                    'address_id'=>$v['order']['address_id'],
                    'use_jf'=>$v['order']['use_jf'],
                    'order_status'=>$v['order']['order_status'],
                    'invoice_money'=>$v['order']['invoice_money'],
                    'channel'=>$v['order']['channel'],
                    'use_money_deduction'=>$v['order']['use_money_deduction'],
                    'order_type'=>$v['order']['order_type'],
                    'sales_channel'=>$v['order']['sales_channel'],
                    'pay_discount'=>$v['order']['pay_discount'],
                    'new_pay_discount'=>$v['order']['new_pay_discount'],
                    'version'=>$v['order']['version'],
                    'sync_status'=>$v['order']['sync_status'],
                    'last_modify_time'=>$v['order']['last_modify_time'],
                    'cang_id'=>$v['order']['cang_id'],
                    'deliver_type'=>$v['order']['deliver_type'],
                    'sheet_show_price'=>$v['order']['sheet_show_price'],
                    'show_status'=>$v['order']['show_status'],
                    'money'=>$v['order']['money'],
                    'p_order_id'=>$p_order_id,
                    'store_id'=>$v['order']['store_id'],
                    'fresh_discount'=>$v['order']['fresh_discount'],
                    'jd_discount'=>$v['order']['jd_discount'],
                    'fresh_no'=>$v['order']['fresh_no'],
                    'fp_id_no'=>$v['order']['fp_id_no'],
                    'no_stock'=>$v['order']['no_stock'],
                    'kp_type'=>$v['order']['kp_type'],
                    'balance_payed'=>$v['order']['balance_payed'],
                );

                //极速达
                if($v['order']['order_type'] == 3 || $v['order']['order_type'] == 4)
                {
                    $time_arr = explode('-',$son_order['stime']);
                    $s_time = strtotime($time_arr[0]);
                    $e_time = strtotime($time_arr[1]);
                    $m_time = $e_time - $s_time;

                    if($m_time >0 && $m_time < 3600)
                    {
                        if($this->use29Min === true){
                            $now_time = date('H:i');
                            $end_time = date('H:i',time()+1800);
                            $son_order['stime'] = $now_time.'-'.$end_time;
                        }else{
                            $now_time = date('H:i',time()+1800);
                            $end_time = date('H:i',time()+3600);
                            $son_order['stime'] = $now_time.'-'.$end_time;
                        }
                    }
                }

                //自提拆单
                if($v['order']['order_type'] == 4)
                {
                    $son_order['pickup_store_id'] = $p_pickup_store_id;
                    $son_order['pickup_code'] = $p_pickup_code;
                }

                $order_id = $this->ci->order_model->addOrder($son_order);

                //o2o
                $o2o_id = 0;
                $order_type = $v['order']['order_type'];
                if($order_type == 3 || $order_type == 4)
                {
                    $o2o_order = array(
                        'p_order_id' => $order_id,
                        'uid' => $son_order['uid'],
                        'order_name' => $son_order['order_name'],
                        'store_id' => 0,//老版实体门店,忽设置
                        'money' => $son_order['money'],
                        'goods_money' => $son_order['goods_money'],
                        'jf_money' => $son_order['jf_money'],
                        'method_money' => $son_order['method_money'],
                        'post_discount' => $son_order['post_discount'],
                        'card_money' => $son_order['card_money'],
                        'pmoney' => $son_order['pmoney'],
                        'pay_status' => $son_order['pay_status'],
                        'operation_id' => $son_order['operation_id'],
                        'score' => $son_order['score'],
                        'use_card' => $son_order['use_card'],
                        'sync_status' => $son_order['sync_status'],
                        'address' => '',
                        'send_type' => 1,
                        'pay_discount' => $son_order['pay_discount'],
                        'use_money_deduction' => $son_order['use_money_deduction'],
                        'fresh_discount' => $son_order['fresh_discount'],
                        'jd_discount' => $son_order['jd_discount'],
                        'fresh_no' => $son_order['fresh_no'],
                        'no_stock' => $son_order['no_stock'],
                    );
                    $o2o_id = $this->ci->o2o_model->createChildOrder($o2o_order);
                }

                //地址
                $son_address = $v['order']['address'];
                $son_address['order_id'] = $order_id;
                unset($son_address['id']);
                $this->ci->order_model->addOrderAddr($son_address);

                //发票
                if(!empty($v['order']['invoice']))
                {
                    $son_invoice = $v['order']['invoice'];
                    $son_invoice['order_id'] = $order_id;
                    unset($son_invoice['id']);
                    $this->ci->order_model->addOrderInvoice($son_invoice);
                }

                //电子发票
                if(!empty($order_einvoice))
                {
                    if(!empty($order_einvoice['mobile']))
                    {
                        $mobile = $order_einvoice['mobile'];
                    }
                    else
                    {
                        $mobile = $order_address['mobile'];
                    }
                    $this->ci->order_model->addDfp($son_order['order_name'],$mobile,$order_einvoice['dfp']);
                }

                //商品
                $son_product = $v['order']['product'];
                $this->orderInsertPro($v['order']['uid'],$order_id,$son_product,$order_type,$o2o_id,$p_order_id,$son_order['order_name']);

                //积点
                if($son_order['jd_discount'] > 0)
                {
                    $jd = array();
                    $jd['order_name'] = $son_order['order_name'];
                    $jd['amount'] = $son_order['jd_discount'];
                    $jd['bl'] = $v['order']['bl'];
                    array_push($son_jd,$jd);
                }
                else if($son_order['pay_parent_id'] == '17')
                {
                    $jd = array();
                    $jd['order_name'] = $son_order['order_name'];
                    $jd['amount'] = $son_order['money'];
                    $jd['bl'] = $v['order']['bl'];
                    array_push($son_jd,$jd);
                }
            }

            //积点
            if(count($son_jd) >0)
            {
                $this->ci->load->model('order_jd_model');
                $order_jd = $this->ci->order_jd_model->getList('id,order_name,oms_no,amount,trade_type',array('order_name'=>$params['order_name']));

                if(empty($order_jd))
                {
                    $this->ci->load->library("notifyv1");
                    $send_params = [
                        "mobile"  => '15216691217',
                        "message" => "积点金额错误,订单号:".$params['order_name'].',触发时间:'.date('Y-m-d H:i:s'),
                    ];
                    $this->ci->notifyv1->send('sms','send',$send_params);
                    return array('code'=>300,'msg'=>'积点金额错误');
                }

                if(count($order_jd) == 1)
                {
                    foreach($son_jd as $k=>$v)
                    {
                        $jd_arr = array();
                        $jd_arr['order_name'] = $v['order_name'];
                        $jd_arr['oms_no'] = $order_jd[0]['oms_no'];
                        $jd_arr['amount'] = $v['amount'];
                        $jd_arr['trade_type'] = $order_jd[0]['trade_type'];
                        $jd_arr['b2o_order_name'] = $params['order_name'];
                        $this->ci->order_jd_model->add($jd_arr);
                    }
                }
                else if(count($order_jd) > 1)
                {
                    if(count($son_jd) == 1)
                    {
                        $son_key = count($son_jd);
                        foreach($order_jd as $k=>$v)
                        {
                            $oms_no = $v['oms_no'];
                            $amount = $v['amount'];
                            $trade_type = $v['trade_type'];
                            $order_name = $params['order_name'];
                            $s_o_amount = 0;
                            foreach($son_jd as $key=>$val)
                            {
                                $num_amount = number_format($amount * $val['bl'],2,'.','');
                                $jd_arr = array();
                                $jd_arr['order_name'] = $val['order_name'];
                                $jd_arr['oms_no'] = $oms_no;
                                $jd_arr['amount'] = $num_amount;
                                $jd_arr['trade_type'] = $trade_type;
                                $jd_arr['b2o_order_name'] = $order_name;
                                if(($key+1) != $son_key)
                                {
                                    $s_o_amount += (float)$num_amount;
                                }
                                if(($key+1) == $son_key)
                                {
                                    $jd_arr['amount'] = $amount - $s_o_amount;
                                }
                                $this->ci->order_jd_model->add($jd_arr);
                            }
                        }
                    }
                    else
                    {
                        $jd_all = array();
                        $son_key = count($son_jd);
                        foreach($order_jd as $k=>$v)
                        {
                            $oms_no = $v['oms_no'];
                            $amount = $v['amount'];
                            $trade_type = $v['trade_type'];
                            $order_name = $params['order_name'];
                            $s_o_amount = 0;
                            foreach($son_jd as $key=>$val)
                            {
                                $num_amount = number_format($amount * $val['bl'],2,'.','');
                                $jd_arr = array();
                                $jd_arr['order_name'] = $val['order_name'];
                                $jd_arr['oms_no'] = $oms_no;
                                $jd_arr['amount'] = $num_amount;
                                $jd_arr['trade_type'] = $trade_type;
                                $jd_arr['b2o_order_name'] = $order_name;
                                if(($key+1) != $son_key)
                                {
                                    $s_o_amount += (float)$num_amount;
                                }
                                if(($key+1) == $son_key)
                                {
                                    $jd_arr['amount'] = $amount - $s_o_amount;
                                }
                                $jd_arr['order_amount'] = $val['amount'];
                                array_push($jd_all,$jd_arr);
                            }
                        }

                        foreach($son_jd as $key=>$val)
                        {
                            $total_amount = 0;
                            $jd_key = 0;
                            foreach($jd_all as $k=>$v)
                            {
                                if($val['order_name'] == $v['order_name'])
                                {
                                    $total_amount += $v['amount'];
                                    $jd_key = $k;
                                }
                            }
                            $jd_all[$jd_key]['total_amount'] = $total_amount;
                        }

                        foreach($jd_all as $k=>$v)
                        {
                            if(isset($v['total_amount']))
                            {
                                if($v['total_amount'] != $v['order_amount'])
                                {
                                    if($v['total_amount'] > $v['order_amount'])
                                    {
                                        $diff = $v['total_amount'] - $v['order_amount'];
                                        $jd_all[$k]['amount'] = $v['amount'] - $diff;
                                    }
                                    else if($v['total_amount'] < $v['order_amount'])
                                    {
                                        $diff = $v['order_amount'] - $v['total_amount'];
                                        $jd_all[$k]['amount'] = $v['amount'] + $diff;
                                    }
                                }
                            }
                        }

                        foreach($jd_all as $key=>$val)
                        {
                            $add = array();
                            $add['order_name'] = $val['order_name'];
                            $add['oms_no'] = $val['oms_no'];
                            $add['amount'] = $val['amount'];
                            $add['trade_type'] = $val['trade_type'];
                            $add['b2o_order_name'] = $val['b2o_order_name'];

                            $this->ci->order_jd_model->add($add);
                        }
                    }
                }
            }

            //拆单状态
            $data = array(
                'p_order_id' =>'1'
            );
            $where = array(
                'order_name' => $params['order_name'],
                'p_order_id' =>'0',
            );

            $up_rows = $this->ci->b2o_parent_order_model->update_order($data, $where);
            if($up_rows == false)
            {
                $this->ci->db->trans_rollback();
                $this->splitLog($params['order_name'],$product);
                return array("code" => "300", "msg" => "拆单失败");
            }
        }

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();

            $this->splitLog($params['order_name'],$product);
            return array("code" => "300", "msg" => "拆单失败");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code'=>200,'msg'=>'succ');
    }

    public function orderImperfectSplitRbac($params){
        ini_set('memory_limit','512M');
        //必要参数验证start
        $required_fields = array(
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        $gift = $this->giftSplit($params['order_name']);
        if($gift['code'] == '200')
        {
            return array('code'=>'200','msg'=>'succ');
        }
        
        //事务 start
        $this->ci->db->trans_begin();

        $order = $this->ci->b2o_parent_order_model->dump(array('order_name' => $params['order_name'],'pay_status'=>'1','order_status' =>'1','p_order_id'=>'0'));
        if (empty($order)) {
            $msg = array('code'=>'300','msg'=>'订单不存在');
            return $msg;
        }

        if($order['order_type'] == 9){
            return array('code'=>300,'msg'=>'送礼订单不拆');
        }

        //子单统一数据
        $base_son_order = array(
            'uid'=>$order['uid'],
            'trade_no'=>$order['trade_no'],
            'billno'=>$order['billno'],
            'time'=>$order['time'],
            'pay_time'=>$order['pay_time'],
            'update_pay_time'=>$order['update_pay_time'],
            'pay_name'=>$order['pay_name'],
            'pay_parent_id'=>$order['pay_parent_id'],
            'pay_id'=>$order['pay_id'],
            'pay_status'=>$order['pay_status'],
            'fp'=>$order['fp'],
            'fp_dz'=>$order['fp_dz'],
            'operation_id'=>$order['operation_id'],
            'address_id'=>$order['address_id'],
            'order_status'=>$order['order_status'],
            'channel'=>$order['channel'],
            'sales_channel'=>$order['sales_channel'],
            'version'=>$order['version'],
            'sync_status'=>$order['sync_status'],
            'last_modify_time'=>$order['last_modify_time'],
            'sheet_show_price'=>$order['sheet_show_price'],
            'show_status'=>$order['show_status'],
            'p_order_id'=>$order['id'],
            'fresh_no'=>$order['fresh_no'],
            'fp_id_no'=>$order['fp_id_no'],
            'no_stock'=>$order['no_stock'],
            'kp_type'=>$order['kp_type'],
            'balance_payed'=>$order['balance_payed'],
        );



        $card_p_ids = array();
        if($order['use_card']){
            $card_info = $this->ci->card_model->dump(array('card_number'=>$order['use_card']));
            if(trim($card_info['product_id'])) 
                $card_p_ids = explode(',', $card_info['product_id']);
        }

        $order_address = $this->ci->b2o_parent_order_address_model->dump(array('order_id' =>$order['id']));
        if (empty($order_address)) {
            $msg = array('code'=>'300','msg'=>'订单地址异常');
            return $msg;
        }

        $order_invoice = $this->ci->b2o_parent_order_invoice_model->dump(array('order_id' =>$order['id']));
        $order_einvoice = $this->ci->order_einvoices_model->dump(array('order_name' =>$params['order_name']));

        $order_package = $this->ci->b2o_parent_order_model->get_order_package($params['order_name']);
        if (empty($order_package)) {
            $msg = array('code'=>'300','msg'=>'订单包裹异常');
            return $msg;
        }
        
        $spilt_order = array();
        $spilt_order['money'] = bcsub($order['money'] , $order['bank_discount'] , 2);
        $spilt_order['method_money'] = $order['method_money'] > 0 ? '-'.$order['method_money'] : 0;
        $spilt_order['card_money'] = $order['card_money'];
        $spilt_order['jf_money'] = $order['jf_money'];
        $spilt_order['post_discount'] = $order['post_discount'];
        $spilt_order['bank_discount'] = $order['bank_discount'];
        $spilt_order['invoice_money'] = $order['invoice_money'] > 0 ? '-'.$order['invoice_money']: 0;
        $spilt_order['use_money_deduction'] = $order['use_money_deduction'];
        $spilt_order['new_pay_discount'] = $order['new_pay_discount'];
        //$spilt_order['pay_discount'] = $order['pay_discount']; //购物车营销和积点特殊处理
        $spilt_order['pro_card_money'] = $order['pro_card_money'];
        $spilt_order['fresh_discount'] = $order['fresh_discount'];
        $spilt_order['score'] = $order['score'];

        $this->ci->load->model('order_jd_model');
        $order_jd = $this->ci->order_jd_model->getList('id,order_name,oms_no,amount,trade_type',array('order_name'=>$order['order_name']));
        $jd_keys = array();
        $all_jd_amount = 0;
        foreach ($order_jd as $jd) {
            $spilt_order['jd_'.$jd['oms_no']] = $jd['amount'];
            $jd_keys[] = 'jd_'.$jd['oms_no'];
            $all_jd_amount = bcadd($all_jd_amount, $jd['amount'], 2);
        }

        if($order['pay_parent_id'] == 17){ //纯积点支付
            $spilt_order['money'] = bcsub($spilt_order['money'], $all_jd_amount , 2);
        }

        $items = array();
        $packages = json_decode($order_package['content'],true);
        $all_amount = 0;
        $son_order_products = array();
        foreach ($packages as $package) {
            foreach ($package['item'] as $product_info) {
                $item_id = str_replace('-', '_', $package['tag']).'|'.$product_info['store_id']."|".$product_info['product_id']."|".$product_info['type'];
                $items[$item_id]['id'] = $item_id;

                $items[$item_id]['amount'] = bcadd($items[$item_id]['amount'], $product_info['amount'],2);
                $items[$item_id]['discount'] = bcadd($items[$item_id]['discount'], $product_info['discount'],2);//购物车营销


                $items[$item_id]['money'] = 1;
                $items[$item_id]['card_money'] = 1;
                if($product_info['card_limit'] == 1){
                    $items[$item_id]['card_money'] = 0;
                }
                if($card_p_ids && !in_array($product_info['product_id'], $card_p_ids)){
                    $items[$item_id]['card_money'] = 0;
                }
                $items[$item_id]['jf_money'] = 1;
                if($product_info['jf_limit'] == 1){
                    $items[$item_id]['jf_money'] = 0;
                }
                $items[$item_id]['method_money'] = 2;
                if(isset($package['store']) && $package['store']['is_select'] == 1){ //自提包裹运费不分摊
                    $items[$item_id]['method_money'] = 0;
                }
                $items[$item_id]['bank_discount'] = 1;
                if($order['invoice_money'] > 0 || $items[$item_id]['method_money'] != 0){
                    $items[$item_id]['post_discount'] = 2;
                }else{
                    $items[$item_id]['post_discount'] = 0;
                }
                $items[$item_id]['invoice_money'] = 2;
                $items[$item_id]['use_money_deduction'] = 1;
                $items[$item_id]['new_pay_discount'] = 1;
                $items[$item_id]['pro_card_money'] = 1;
                $items[$item_id]['fresh_discount'] = 1;
                foreach ($jd_keys as $jdk) {
                    $items[$item_id][$jdk] = 1;
                }
                $all_amount = bcadd($all_amount, $items[$item_id]['amount'], 2);
                $son_order_products[$package['tag']."|".$product_info['store_id']][] = $product_info;
            } 
        }
        $new_packages = array();
        foreach ($packages as $package) {
            $new_packages[$package['tag']] = $package;
        }
        $split_result= imperfect_split($spilt_order,$items);
        $from_script = isset($params['from_script'])?$params['from_script']:0;
        if(empty($split_result)){
            if($from_script == 0){
                $this->ci->load->library("notifyv1");
                $send_params = [
                    "mobile"  => '13524780797',
                    "message" => "拆单金额错误,订单号:".$params['order_name'].',触发时间:'.date('Y-m-d H:i:s'),
                ];
                $this->ci->notifyv1->send('sms','send',$send_params);
            }
            return array('code'=>300,'msg'=>'拆单失败');
        }
        $son_orders = array();
        foreach ($split_result as $item_id => $value) {
            $item_keys = explode('|', $item_id);
            $package_tag = str_replace('_', '-', $item_keys[0]);
            $store_id = $item_keys[1];

            $son_orders[$package_tag."|".$store_id]['money'] = bcadd($son_orders[$package_tag."|".$store_id]['money'], bcadd($value['money'], $value['bank_discount'],2),2);
            $son_orders[$package_tag."|".$store_id]['card_money'] = bcadd($son_orders[$package_tag."|".$store_id]['card_money'], $value['card_money'],2);
            $son_orders[$package_tag."|".$store_id]['jf_money'] = bcadd($son_orders[$package_tag."|".$store_id]['jf_money'], $value['jf_money'],2);
            $son_orders[$package_tag."|".$store_id]['method_money'] = bcadd($son_orders[$package_tag."|".$store_id]['method_money'], $value['method_money'],2);
            $son_orders[$package_tag."|".$store_id]['post_discount'] = bcadd($son_orders[$package_tag."|".$store_id]['post_discount'], $value['post_discount'],2);
            $son_orders[$package_tag."|".$store_id]['bank_discount'] = bcadd($son_orders[$package_tag."|".$store_id]['bank_discount'], $value['bank_discount'],2);
            $son_orders[$package_tag."|".$store_id]['invoice_money'] = bcadd($son_orders[$package_tag."|".$store_id]['invoice_money'], $value['invoice_money'],2);
            $son_orders[$package_tag."|".$store_id]['use_money_deduction'] = bcadd($son_orders[$package_tag."|".$store_id]['use_money_deduction'], $value['use_money_deduction'],2);
            $son_orders[$package_tag."|".$store_id]['new_pay_discount'] = bcadd($son_orders[$package_tag."|".$store_id]['new_pay_discount'], $value['new_pay_discount'],2);
            $son_orders[$package_tag."|".$store_id]['pay_discount'] = bcadd($son_orders[$package_tag."|".$store_id]['pay_discount'], $value['discount'],2);

            $son_orders[$package_tag."|".$store_id]['pro_card_money'] = bcadd($son_orders[$package_tag."|".$store_id]['pro_card_money'], $value['pro_card_money'],2);
            $son_orders[$package_tag."|".$store_id]['fresh_discount'] = bcadd($son_orders[$package_tag."|".$store_id]['fresh_discount'], $value['fresh_discount'],2);
            $son_orders[$package_tag."|".$store_id]['goods_money'] = bcadd($son_orders[$package_tag."|".$store_id]['goods_money'], $value['amount'],2);

            $son_orders[$package_tag."|".$store_id]['score'] = bcadd($son_orders[$package_tag."|".$store_id]['score'], $value['score'],2);
            foreach ($jd_keys as $jdk) {
                $son_orders[$package_tag."|".$store_id][$jdk] = bcadd($son_orders[$package_tag."|".$store_id][$jdk], $value[$jdk],2);
            }
        }
        $this->ci->load->model('warehouse_model');
        foreach ($son_orders as $key => $son_order) {
            $k_info = explode('|', $key);
            $package_tag = $k_info[0];
            $store_id = $k_info[1];
            $jd_discount = 0;
            $son_order_jd = array();
            foreach ($jd_keys as $jdk) {
                $jd_discount = bcadd($jd_discount, $son_order[$jdk], 2);
                $son_order_jd[$jdk] = $son_order[$jdk];
                unset($son_order[$jdk]);
            }
            
            $real_son_order = array_merge($base_son_order,$son_order);
            if($order['pay_parent_id'] == 17){ //纯积点支付
                $real_son_order['money'] = bcadd($real_son_order['money'], $jd_discount, 2);
                $real_son_order['jd_discount'] = 0;
            }else{
                $real_son_order['jd_discount'] = $jd_discount;
            }
            $son_order_name = $this->makeOrder();

            $real_son_order['order_name'] = $son_order_name;
            $real_son_order['use_card'] = ($real_son_order['card_money'] > 0) ? $order['use_card'] : '';
            $real_son_order['pmoney'] = $real_son_order['goods_money'];
            $real_son_order['score'] = ceil($real_son_order['score']);
            $real_son_order['use_jf'] = bcmul($real_son_order['jf_money'], 100);

            if($new_packages[$package_tag]['package_type'] == 3){
                $real_son_order['order_type'] = 5;
            }elseif($new_packages[$package_tag]['package_type'] == 1){
                if(isset($new_packages[$package_tag]['store']) && $new_packages[$package_tag]['store']['is_select'] == 1){
                    $real_son_order['order_type'] = 4;
                }else{
                    $real_son_order['order_type'] = 3;
                }
            }else{
                $real_son_order['order_type'] = 1;
            }
            $real_son_order['shtime'] = $new_packages[$package_tag]['chose_sendtime']['shtime'];
            $real_son_order['stime'] = $new_packages[$package_tag]['chose_sendtime']['stime'];
            if(in_array($real_son_order['order_type'], array(3,4))){
                $time_arr = explode('-',$real_son_order['stime']);
                $s_time = strtotime($time_arr[0]);
                $e_time = strtotime($time_arr[1]);
                $m_time = $e_time - $s_time;

                if($m_time >0 && $m_time < 3600)
                {
                    if($this->use29Min === true){
                        $now_time = date('H:i');
                        $end_time = date('H:i',time()+1800);
                        $real_son_order['stime'] = $now_time.'-'.$end_time;
                    }else{
                        $now_time = date('H:i',time()+1800);
                        $end_time = date('H:i',time()+3600);
                        $real_son_order['stime'] = $now_time.'-'.$end_time;
                    }
                }
            }
            $real_son_order['cang_id'] = $new_packages[$package_tag]['cang_id'];
            $warehouse_info = $this->ci->warehouse_model->getWarehouseByID($send_ware_id);
            $real_son_order['deliver_type'] = $warehouse_info['send_type'];
            $real_son_order['store_id'] = $store_id;
            
            if($real_son_order['order_type'] == 4){
                $real_son_order['pickup_store_id'] = $order['pickup_store_id'];
                $real_son_order['pickup_code'] = $order['pickup_code'];
            }
            $son_order_id = $this->ci->order_model->addOrder($real_son_order);

            //o2o
            $o2o_id = 0;
            $order_type = $real_son_order['order_type'];
            if($order_type == 3 || $order_type == 4)
            {
                $o2o_order = array(
                    'p_order_id' => $son_order_id,
                    'uid' => $real_son_order['uid'],
                    'order_name' => $real_son_order['order_name'],
                    'store_id' => 0,//老版实体门店,忽设置
                    'money' => $real_son_order['money'],
                    'goods_money' => $real_son_order['goods_money'],
                    'jf_money' => $real_son_order['jf_money'],
                    'method_money' => $real_son_order['method_money'],
                    'post_discount' => $real_son_order['post_discount'],
                    'card_money' => $real_son_order['card_money'],
                    'pmoney' => $real_son_order['pmoney'],
                    'pay_status' => $real_son_order['pay_status'],
                    'operation_id' => $real_son_order['operation_id'],
                    'score' => $real_son_order['score'],
                    'use_card' => $real_son_order['use_card'],
                    'sync_status' => $real_son_order['sync_status'],
                    'address' => '',
                    'send_type' => 1,
                    'pay_discount' => $real_son_order['pay_discount'],
                    'use_money_deduction' => $real_son_order['use_money_deduction'],
                    'fresh_discount' => $real_son_order['fresh_discount'],
                    'jd_discount' => $real_son_order['jd_discount'],
                    'fresh_no' => $real_son_order['fresh_no'],
                    'no_stock' => $real_son_order['no_stock'],
                );
                $o2o_id = $this->ci->o2o_model->createChildOrder($o2o_order);
            }

            //地址
            $son_address = $order_address;
            $son_address['order_id'] = $son_order_id;
            unset($son_address['id']);
            $this->ci->order_model->addOrderAddr($son_address);

            //发票
            if(!empty($order_invoice))
            {
                $son_invoice = $order_invoice;
                $son_invoice['order_id'] = $son_order_id;
                unset($son_invoice['id']);
                $this->ci->order_model->addOrderInvoice($son_invoice);
            }

            //电子发票
            if(!empty($order_einvoice))
            {
                if(!empty($order_einvoice['mobile']))
                {
                    $mobile = $order_einvoice['mobile'];
                }
                else
                {
                    $mobile = $order_address['mobile'];
                }
                $this->ci->order_model->addDfp($real_son_order['order_name'],$mobile,$order_einvoice['dfp']);
            }

            //商品
            $son_product = $son_order_products[$key];
            $this->orderInsertPro($order['uid'],$son_order_id,$son_product,$order_type,$o2o_id,$order['id'],$real_son_order['order_name']);
            
            foreach($son_order_jd as $jdk => $jd_amount){
                $jd_key = explode('_', $jdk);
                $add = array();
                $add['order_name'] = $real_son_order['order_name'];
                $add['oms_no'] = $jd_key[1];
                $add['amount'] = $jd_amount;
                $add['trade_type'] = $order_jd[0]['trade_type'];
                $add['b2o_order_name'] = $order['order_name'];
                $this->ci->order_jd_model->add($add);
            }
        }
        //拆单状态
        $data = array(
            'p_order_id' =>'1'
        );
        $where = array(
            'order_name' => $params['order_name'],
            'p_order_id' =>'0',
        );
        $up_rows = $this->ci->b2o_parent_order_model->update_order($data, $where);
        if($up_rows == false)
        {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "拆单失败");
        }
        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "拆单失败");
        } else {
            $this->ci->db->trans_commit();
        }
        return array('code'=>200,'msg'=>'succ');
    }

    /*
     * 银行秒杀排队
     */
    public function orderLimit()
    {
        $res = array('code'=>'200','msg'=>'succ');

        $bank_order_limit = $this->ci->config->item('bank_order_limit');
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
                $ordercount = $redis->get('order_limit_count');

                if($rand != 5 && $ordercount >= $limit_count && $is_open == 1)
                {
                    $res = array('code'=>'321','msg'=>'当前抢购人数太多啦，系统正在奋力处理中，请稍后重试~','data'=>array('time'=>$time));
                }
            }
        }

        return $res;
    }

    /*
     * 更新用户信息
     */
    private function setUserInfo($uid)
    {
        $this->ci->load->bll('apiuser');
        $api_user = array();
        $api_user['uid'] = $uid;
        $this->ci->bll_apiuser->set($api_user);
    }

    /*
     * 绑定闪鲜卡
     */
    public function bindCard($params)
    {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'card_no' => array('required' => array('code' => '300', 'msg' => 'card_no can not be null')),
            'card_pwd' => array('required' => array('code' => '500', 'msg' => 'card_pwd can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        $user = $this->ci->user_model->selectUser('mobile', array('id' => $uid));

        if(empty($user['mobile']))
        {
            return array('code'=>'300','msg'=>'使用闪鲜卡需要果园账号绑定手机号码');
        }

        $data = array();
        $data['user_id'] = $uid;
        $data['mobile'] = $user['mobile'];
        $data['card_no'] = $params['card_no'];
        $data['card_pwd'] = $params['card_pwd'];

        $this->ci->load->bll('apisd');
        $res = $this->ci->bll_apisd->doBind($data);

        return $res;
    }

    /*
     * 解绑闪鲜卡
     */
    public function unbindCard($params)
    {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'card_no' => array('required' => array('code' => '300', 'msg' => 'card_no can not be null')),
            'bind_id' => array('required' => array('code' => '500', 'msg' => 'bind_id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        $data = array();
        $data['user_id'] = $uid;
        $data['card_no'] = $params['card_no'];
        $data['bind_id'] = $params['bind_id'];

        $this->ci->load->bll('apisd');
        $res = $this->ci->bll_apisd->unBind($data);

        return $res;
    }

    /*
     * 获取闪鲜卡
     */
    public function getCardList($params)
    {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'type' => array('required' => array('code' => '500', 'msg' => 'type can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        $data = array();
        $data['user_id'] = $uid;

        $this->ci->load->bll('apisd');
        $res = $this->ci->bll_apisd->getCardList($data);

        if($res['code'] != '200')
        {
            //return array('code'=>$res['code'],'msg'=>$res['msg']);
            $rt['list'] = array();
            $rt['count'] = 0;
            return array('code'=>200,'msg'=>'','data'=>$rt);
        }

        $list = $res['data'];
        $count = count($list);
        $time = time();
        if($params['type'] == 0) //正常
        {
            $arr = array();
            if($count > 0)
            {
                foreach($list as $k=>$v)
                {
                    if($v['card_state'] == 0 && $v['card_balance'] >0 && $time <= $v['exp_time'] )
                    {
                        $v['exp_time'] = date('Y-m-d',$v['exp_time']);
                        array_push($arr,$v);
                    }
                }
            }
            unset($res['data']);
            $res['data']['list'] = $arr;
            $res['data']['count'] = count($arr);
        }
        else if($params['type'] == 1) //不可使用
        {
            $arr = array();
            if($count > 0)
            {
                foreach($list as $k=>$v)
                {
                    if($v['card_state'] != 0 || $v['card_balance'] <= 0 || $time >= $v['exp_time'])
                    {
                        $v['exp_time'] = date('Y-m-d',$v['exp_time']);
                        array_push($arr,$v);
                    }
                }
            }
            unset($res['data']);
            $res['data']['list'] = $arr;
            $res['data']['count'] = count($arr);
        }

        return $res;
    }

    /*
     * 使用闪鲜卡
     */
    public function usefc($params)
    {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'card_no' => array('required' => array('code' => '300', 'msg' => 'card_no can not be null')),
            'bind_id' => array('required' => array('code' => '500', 'msg' => 'bind_id can not be null')),
       );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        //事务 start
        $this->ci->db->trans_begin();

        $fresh = array();
        $fresh['user_id'] = $uid;
        $fresh['card_no'] = $params['card_no'];
        $fresh['bind_id'] = $params['bind_id'];
        $this->ci->load->bll('apisd');
        $fresh_info = $this->ci->bll_apisd->doQuery($fresh);
        $fresh_res = $fresh_info['data'];

        if(empty($fresh_res))
        {
            return array('code'=>300,'msg'=>'选择的闪鲜卡未绑定');
        }

        if($fresh_info['code'] != 200)
        {
            return array('code'=>$fresh_info['code'],'msg'=>$fresh_info['msg']);
        }

        $card_money = $fresh_res['card_balance'];
        $card_state = $fresh_res['card_state'];

        if($card_state != 0)
        {
            return array('code'=>300,'msg'=>'选择的闪鲜卡不可使用');
        }

        if($card_money <= 0)
        {
            return array('code'=>300,'msg'=>'选择的闪鲜卡金额不足');
        }

        //使用金额验证
        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        $orderDetail = $this->b2oOrderDetails($uid, $params['source'], $params['device_id'],$params);

        $order_money = $orderDetail['money'] + $orderDetail['fresh_discount'];
        if($card_money > $order_money)
        {
            $fresh_discount = $order_money;
        }
        else
        {
            $fresh_discount = $card_money;
        }

        $data = array(
            'fresh_discount' => $fresh_discount,
            'fresh_no'=> $fresh_res['card_no']
        );
        $where = array(
            'id' => $order_id,
            'order_status' => 0
        );
        $this->ci->b2o_parent_order_model->update_order($data, $where);

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "使用闪鲜卡失败,请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => "succ", 'uid' => $uid);
    }

    /*
     * 取消使用闪鲜卡
     */
    public function cancelUsefc($params)
    {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'card_no' => array('required' => array('code' => '300', 'msg' => 'card_no can not be null')),
            'bind_id' => array('required' => array('code' => '500', 'msg' => 'bind_id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        //事务 start
        $this->ci->db->trans_begin();

        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        $data = array(
            'fresh_discount' => '0.00',
            'fresh_no'=>'',
        );
        $where = array(
            'id' => $order_id,
            'order_status' => 0
        );
        $this->ci->b2o_parent_order_model->update_order($data, $where);

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "取消使用闪鲜卡失败,请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array("code" => "200", "msg" => "succ", "uid" => $uid);
    }

    /*
     * 使用闪鲜卡
     */
    public function addTrade($params)
    {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'money' => array('required' => array('code' => '300', 'msg' => 'money can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        //事务 start
        $this->ci->db->trans_begin();

        $money = $params['money'];
        if($money <= 0)
        {
            return array("code" => "300", "msg" => "充值金额不能小于0");
        }

        $payment = '微信支付';
        $msg = 'App充值单';
        $this->ci->load->model('trade_model');
        $trade_info = array("trade_number" => "",
            "uid" => $uid,
            "money" => $money,
            "payment" => $payment,
            "status" => "等待支付",
            "time" => date('Y-m-d H:i:s'),
            "has_deal" => 0,
            "type" => 'income',
            "msg" => $msg
        );
        $trade_number = $this->ci->trade_model->generate_trade($trade_info);
        if(!$trade_number){
            return array("code" => "300", "msg" => "创建充值失败,请重试");
        }

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "创建充值失败,请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array("code" => "200", "msg" => "succ", "data" => array('trade_no'=>$trade_number));
    }


    /*
     * 订单配送时间列表
     */
    public function sendTimeList($params)
    {
        //必要参数验证
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
            'package_id' => array('required' => array('code' => '500', 'msg' => 'package_id can not be null')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }

        //获取订单信息
        $order_name = $params['order_name'];
        $package_id = intval($params['package_id']);
        $bs = mb_substr($order_name,0,1);

        $send_time_list = array();
        if($bs == 'P')
        {
            $order = $this->ci->b2o_parent_order_model->dump(array('order_name' =>$order_name,'order_status' =>'1','uid'=>$uid));
            if (empty($order)) {
                return array('code'=>'300','msg'=>'订单不存在');
            }

            if($order['operation_id'] == 5)
            {
                return array('code'=>'300','msg'=>'订单已取消，无法修改时间');
            }

            if($order['pay_status'] == 1 || !in_array($order['operation_id'], array(0,1)))
            {
                return array('code'=>'300','msg'=>'您的订单已开始出库，无法修改时间');
            }

            $user_addr = $this->ci->b2o_parent_order_model->get_user_addr($uid,$order['address_id']);
            if(empty($user_addr))
            {
                return array("code" => "300", "msg" => "您选择的下单地址错误，请重新选择");
            }

            $gps_res  = $this->getTmsStore($user_addr['lonlat'],$user_addr['area_adcode']);
            $open_flash_send = $params['open_package'] == 1 ? true : false;

            $order_package = $this->ci->b2o_parent_order_model->get_order_package($order_name);
            $b2o_package = json_decode($order_package['content'],true);

            $this->ci->load->model('b2o_order_cart_model');
            $b2o_cart = $this->ci->b2o_order_cart_model->dump(array('order_name' =>$order['order_name']));
            $cart_info = array();
            $cart_info['products'] = json_decode($b2o_cart['products'],true);
            $package =  $this->package($cart_info,$user_addr['area_adcode'],$gps_res['data']['delivery_code'],$open_flash_send,$gps_res['data']['tms_region_type'],$gps_res['data']['is_day_night'],$gps_res['data']['delivery_end_time']);
            if(isset($package['code']))
            {
                return array('code'=>$package['code'],'msg'=>$package['msg']);
            }

            foreach ($b2o_package as $old_package) {
                if($old_package['tag'] == $package[$package_id]['tag']){
                    if(isset($old_package['store']) && $old_package['store']['is_select'] == 1){
                        if(empty($package[$package_id]['zt_send_time'])){
                            return array("code" => "300", "msg" => "无可选时间段，无法修改时间");
                        }
                        $package[$package_id]['send_time'] = $package[$package_id]['zt_send_time'];
                    }
                }
            }
            if(empty($package[$package_id]['send_time'])){
                return array("code" => "300", "msg" => "无可选时间段，无法修改时间");
            }
            $send_time_list = $package[$package_id];
        }
        else
        {
            $order = $this->ci->order_model->dump(array('order_name' =>$order_name,'order_status' =>'1','uid'=>$uid));
            if (empty($order)) {
                return array('code'=>'300','msg'=>'订单不存在');
            }

            if($order['operation_id'] == 5)
            {
                return array('code'=>'300','msg'=>'订单已取消，无法修改时间');
            }

            if(!in_array($order['operation_id'], array(0,1)) || !in_array($order['order_type'], array(1,3))) {
                return array('code' => '300', 'msg' => '您的订单已开始出库，无法修改时间');
            }

            $user_addr = $this->ci->order_model->get_user_addr($uid,$order['address_id']);
            if(empty($user_addr))
            {
                return array("code" => "300", "msg" => "您选择的下单地址错误，请重新选择");
            }

            $gps_res  = $this->getTmsStore($user_addr['lonlat'],$user_addr['area_adcode']);
            $open_flash_send = $params['open_package'] == 1 ? true : false;

            $package =  $this->editSendTime($order['id'],$user_addr['area_adcode'],$gps_res['data']['delivery_code'],$open_flash_send,$gps_res['data']['tms_region_type'],$gps_res['data']['is_day_night'],$gps_res['data']['delivery_end_time']);
            if(isset($package['code']))
            {
                return array('code'=>$package['code'],'msg'=>$package['msg']);
            }

            if($package == false)
            {
                return array('code'=>'300','msg'=>'您的订单已开始出库，无法修改时间');
            }
            else
            {
                if($order['order_type'] == 4){
                    if(empty($package[$package_id]['zt_send_time'])){
                        return array("code" => "300", "msg" => "无可选时间段，无法修改时间");
                    }
                    $package[$package_id]['send_time'] = $package[$package_id]['zt_send_time'];
                }
                if(empty($package[$package_id]['send_time'])){
                    return array("code" => "300", "msg" => "无可选时间段，无法修改时间");
                }
                $send_time_list = $package[$package_id];
            }
        }

        return array("code" => "200", "msg" => "succ", "data" => $send_time_list);
    }


    /*
     * 订单配送时间列表
     */
    public function changeSendTime($params)
    {
        //必要参数验证
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
            'package_id' => array('required' => array('code' => '500', 'msg' => 'package_id can not be null')),
            'package_send_times' => array('required' => array('code' => '500', 'msg' => 'package_send_times can not be null')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }

        //获取订单信息
        $order_name = $params['order_name'];
        $package_id = intval($params['package_id']);
        $bs = mb_substr($order_name,0,1);

        $package_send_times = json_decode($params['package_send_times'],true);

        if($bs == 'P')
        {

            $order = $this->ci->b2o_parent_order_model->dump(array('order_name' =>$order_name,'order_status' =>'1','uid'=>$uid));
            if (empty($order)) {
                return array('code'=>'300','msg'=>'订单不存在');
            }

            if($order['pay_status'] == 1 || !in_array($order['operation_id'], array(0,1)))
            {
                return array('code'=>'300','msg'=>'您的订单已开始出库，无法修改时间');
            }

            $user_addr = $this->ci->b2o_parent_order_model->get_user_addr($uid,$order['address_id']);
            if(empty($user_addr))
            {
                return array("code" => "300", "msg" => "您选择的下单地址错误，请重新选择");
            }

            $gps_res  = $this->getTmsStore($user_addr['lonlat'],$user_addr['area_adcode']);
            $open_flash_send = $params['open_package'] == 1 ? true : false;

            $order_package = $this->ci->b2o_parent_order_model->get_order_package($order_name);
            $b2o_package = json_decode($order_package['content'],true);
            $b2o_cart = $b2o_package[$package_id]['item'];

            $cart_info = array();
            $cart_info['products'] = $b2o_cart;
            $package =  $this->package($cart_info,$user_addr['area_adcode'],$gps_res['data']['delivery_code'],$open_flash_send,$gps_res['data']['tms_region_type'],$gps_res['data']['is_day_night'],$gps_res['data']['delivery_end_time']);
            if(isset($package['code']))
            {
                return array('code'=>$package['code'],'msg'=>$package['msg']);
            }

            $is_reset_sendtime = 0;
            $reset_package = array();
            foreach($package as $key=>$val)
            {
                $shtime = $package_send_times[$key]['shtime'];
                $stime = $package_send_times[$key]['stime'];
                $is_flash = $package_send_times[$key]['is_flash'];
                $checkPackage = $this->checkPackageSendTime($package[$key],$shtime,$stime,$is_flash);
                if(empty($checkPackage))
                {
                    $is_reset_sendtime = 1;
                    $p_num = $key+1;
                    array_push($reset_package,$p_num);
                }
                else
                {
                    $package[$key]['chose_sendtime'] = array(
                        'tag'=>$package[$key]['tag'],
                        'shtime'=>$checkPackage['shtime'],
                        'stime'=>$checkPackage['stime'],
                    );
                }
            }

            if($is_reset_sendtime == 1)
            {
                $str_reset_package = implode(',',$reset_package);
                return array("code" => "300", "msg" => "您选择配送时间包裹".$str_reset_package."已过期,请重新选择配送时间");
            }

            //构建package
            foreach($b2o_package as $key=>$val)
            {
                if($key == $package_id)
                {
                    $b2o_package[$key]['send_time'] = $package[0]['send_time'];
                    $b2o_package[$key]['chose_sendtime'] = $package[0]['chose_sendtime'];
                }
            }

            $this->ci->b2o_parent_order_model->init_order_package($order_name,$b2o_package);
        }
        else
        {
            $order = $this->ci->order_model->dump(array('order_name' =>$order_name,'order_status' =>'1','uid'=>$uid));
            if (empty($order)) {
                return array('code'=>'300','msg'=>'订单不存在');
            }

            if(!in_array($order['operation_id'], array(0,1)) || !in_array($order['order_type'], array(1,3)))
            {
                return array('code'=>'300','msg'=>'您的订单已开始出库，无法修改时间');
            }

            $user_addr = $this->ci->order_model->get_user_addr($uid,$order['address_id']);
            if(empty($user_addr))
            {
                return array("code" => "300", "msg" => "您选择的下单地址错误，请重新选择");
            }

            $gps_res  = $this->getTmsStore($user_addr['lonlat'],$user_addr['area_adcode']);
            $open_flash_send = $params['open_package'] == 1 ? true : false;

            $package =  $this->editSendTime($order['id'],$user_addr['area_adcode'],$gps_res['data']['delivery_code'],$open_flash_send,$gps_res['data']['tms_region_type'],$gps_res['data']['is_day_night'],$gps_res['data']['delivery_end_time']);
            if(isset($package['code']))
            {
                return array('code'=>$package['code'],'msg'=>$package['msg']);
            }

            if($package == false)
            {
                return array('code'=>'300','msg'=>'您的订单已开始出库，无法修改时间');
            }

            $is_reset_sendtime = 0;
            $reset_package = array();
            foreach($package as $key=>$val)
            {
                $shtime = $package_send_times[$key]['shtime'];
                $stime = $package_send_times[$key]['stime'];
                $is_flash = $package_send_times[$key]['is_flash'];
                $checkPackage = $this->checkPackageSendTime($package[$key],$shtime,$stime,$is_flash);
                if(empty($checkPackage))
                {
                    $is_reset_sendtime = 1;
                    $p_num = $key+1;
                    array_push($reset_package,$p_num);
                }
            }

            if($is_reset_sendtime == 1)
            {
                $str_reset_package = implode(',',$reset_package);
                return array("code" => "300", "msg" => "您选择配送时间包裹".$str_reset_package."已过期,请重新选择配送时间");
            }

            $p_shtime = $package_send_times[0]['shtime'];
            $p_stime = $package_send_times[0]['stime'];

            //未同步
            if($order['sync_status'] != 1)
            {
                return array('code'=>'300','msg'=>'订单处理中，请稍后修改');
            }


            //黑名单用户
            $black_change_time = $this->ci->config->item('black_change_time');
            if(in_array($order['uid'],$black_change_time))
            {
                return array('code'=>'300','msg'=>'订单无法修改时间');
            }

            //rpc
            $this->ci->load->bll('apirpc');
            if($order['order_type'] == 1)
            {
                $api_rpc = array();
                $api_rpc['order_name'] = $order['order_name'];
                $api_rpc['shtime'] = $p_shtime;
                $api_rpc['stime'] = $p_stime;
                $rpc = $this->ci->bll_apirpc->b2c($api_rpc);
            }
            else
            {
                $api_rpc = array();
                $api_rpc['order_name'] = $order['order_name'];
                $api_rpc['shtime'] = $p_shtime;
                $api_rpc['stime'] = $p_stime;
                $rpc = $this->ci->bll_apirpc->o2o($api_rpc);
            }

            if($rpc == 0)
            {
                return array('code'=>'300','msg'=>'您的订单已开始出库，无法修改时间');
            }

            $this->ci->order_model->sendtime_reset($order['id'],$p_shtime,$p_stime);
        }

        //构建
        $res = array();
        $res['package_id'] = $package_id;
        $res['package_send_times'] = $package_send_times;

        return array("code" => "200", "msg" => "succ","data"=>$res);
    }


    /*
     * 获取用户积点
     */
    public function getJd($uid)
    {
        $data = array();
        $data['uid'] = $uid;
        $this->ci->load->bll('apijd');
        $res = $this->ci->bll_apijd->doDepositList($data);
        if($res['code'] == 200)
        {
            $total = $res['total_balance'];
        }
        else
        {
            $total = 0;
        }

        return $total;
    }

    /*
     * 使用积点
     */
    public function usejd($params)
    {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        //事务 start
        $this->ci->db->trans_begin();

        $jd_money = $this->getJd($uid);
        if($jd_money <= 0)
        {
            return array('code'=>300,'msg'=>'选择的积点不足');
        }

        //使用金额验证
        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        $orderDetail = $this->b2oOrderDetails($uid, $params['source'], $params['device_id'],$params);

        $order_money = $orderDetail['money'] + $orderDetail['jd_discount'];
        if($jd_money > $order_money)
        {
            $jd_discount = $order_money;
        }
        else
        {
            $jd_discount = $jd_money;
        }

        $data = array(
            'jd_discount' => $jd_discount,
        );
        $where = array(
            'id' => $order_id,
            'order_status' => 0
        );
        $this->ci->b2o_parent_order_model->update_order($data, $where);

        $this->use_jd_obj = true;
        $this->jd_discount = $jd_discount;

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "使用积点失败,请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array('code' => '200', 'msg' => "succ", 'uid' => $uid);
    }

    /*
     * 取消使用积点
     */
    public function cancelUsejd($params)
    {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        //事务 start
        $this->ci->db->trans_begin();

        $order_id = $this->ci->b2o_parent_order_model->get_order_id($uid);
        $data = array(
            'jd_discount' => '0.00',
        );
        $where = array(
            'id' => $order_id,
            'order_status' => 0
        );
        $this->ci->b2o_parent_order_model->update_order($data, $where);

        $this->use_jd_obj = true;
        $this->jd_discount = 0;

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
            return array("code" => "300", "msg" => "取消使用积点失败,请重试");
        } else {
            $this->ci->db->trans_commit();
        }

        return array("code" => "200", "msg" => "succ", "uid" => $uid);
    }


    /*
     * o2o取消订单
     */
    private function o2oCancel($order_name)
    {
        $data = array();
        $data['order_name'] = $order_name;

        $this->ci->load->bll('apirpc');
        $res = $this->ci->bll_apirpc->o2oCancel($data);

        //返还秒杀
        $order = $this->ci->order_model->dump(array('order_name' => $order_name));
        if($order['p_order_id'] >0 && $res  == 1)
        {
            $b2o = $this->ci->b2o_parent_order_model->dump(array('id' => $order['p_order_id']));
            $b2o_pro = $this->ci->order_product_model->getList('id,product_id',array('order_id'=>$order['id']));

            $p_order_name = $b2o['order_name'];
            $this->ci->load->model('ms_log_v2_model');
            $log = $this->ci->ms_log_v2_model->getList('id,product_id',array('order_name' => $p_order_name,'is_del'=>0));

            $log_pros = array_column($log,'product_id');
            $p_pros = array_column($b2o_pro,'product_id');

            $ms = array();
            foreach($p_pros as $k=>$v)
            {
                if(in_array($v,$log_pros))
                {
                    array_push($ms,$v);
                }
            }

            if(count($ms) >0)
            {
                foreach($ms as $key=>$val)
                {
                    $this->ci->ms_log_v2_model->update_order_del($p_order_name,$val);
                }
            }
        }

        return $res;
    }


    /*
     * 订单修改地址列表
     */
    public function sendAddrList($params)
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
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }

        //获取订单信息
        $order_name = $params['order_name'];
        $bs = mb_substr($order_name,0,1);

        $send_addr_list = array();

        if($bs == 'P')
        {
            $order = $this->ci->b2o_parent_order_model->dump(array('order_name' =>$order_name,'order_status' =>'1','uid'=>$uid));
            if (empty($order)) {
                return array('code'=>'300','msg'=>'订单不存在');
            }

            if($order['operation_id'] == 5)
            {
                return array('code'=>'300','msg'=>'订单已取消，无法修改地址');
            }

            $user_addr = $this->ci->b2o_parent_order_model->get_user_addr($uid,$order['address_id']);
            if(empty($user_addr))
            {
                return array("code" => "300", "msg" => "您选择的下单地址错误，请重新选择");
            }

            $u_province_adcode = $user_addr['province_adcode'];
            $u_city_adcode = $user_addr['city_adcode'];

            $use_case = isset($params['use_case']) ? $params['use_case'] : '';
            $addr_list = $this->ci->user_model->geta_user_address($uid, '', $use_case, $params['source']);

            $can_arr = array();
            $not_can_arr = array();

            foreach($addr_list as $k=>$v)
            {
                if($v['province_adcode'] ==  $u_province_adcode && $v['city_adcode'] == $u_city_adcode)
                {
                    array_push($can_arr,$addr_list[$k]);
                }
                else
                {
                    array_push($not_can_arr,$addr_list[$k]);
                }
            }

            $send_addr_list['can_arr'] = array_reverse($can_arr);
            $send_addr_list['nocan_arr'] = array_reverse($not_can_arr);
        }
        else
        {
            $order = $this->ci->order_model->dump(array('order_name' =>$order_name,'order_status' =>'1','uid'=>$uid));
            if (empty($order)) {
                return array('code'=>'300','msg'=>'订单不存在');
            }

            if($order['operation_id'] == 5)
            {
                return array('code'=>'300','msg'=>'订单已取消，无法修改地址');
            }

            if(!in_array($order['operation_id'], array(0,1)) || $order['order_type'] != 1)
            {
                return array('code'=>'300','msg'=>'您的订单已开始出库，无法修改地址');
            }

            $user_addr = $this->ci->order_model->get_user_addr($uid,$order['address_id']);
            if(empty($user_addr))
            {
                return array("code" => "300", "msg" => "您选择的下单地址错误，请重新选择");
            }

            $u_province_adcode = $user_addr['province_adcode'];
            $u_city_adcode = $user_addr['city_adcode'];

            $use_case = isset($params['use_case']) ? $params['use_case'] : '';
            $addr_list = $this->ci->user_model->geta_user_address($uid, '', $use_case, $params['source']);

            $can_arr = array();
            $not_can_arr = array();

            foreach($addr_list as $k=>$v)
            {
                if($v['province_adcode'] ==  $u_province_adcode && $v['city_adcode'] == $u_city_adcode)
                {
                    array_push($can_arr,$addr_list[$k]);
                }
                else
                {
                    array_push($not_can_arr,$addr_list[$k]);
                }
            }

            $send_addr_list['can_arr'] = array_reverse($can_arr);
            $send_addr_list['nocan_arr'] = array_reverse($not_can_arr);
        }

        return array("code" => "200", "msg" => "succ", "data" => $send_addr_list);
    }

    /*
     * 订单修改地址列表
     */
    public function changeSendAddr($params)
    {
        //必要参数验证
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'order_name' => array('required' => array('code' => '500', 'msg' => 'order_name can not be null')),
            'address_id' => array('required' => array('code' => '500', 'msg' => 'address_id can not be null')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }

        //获取订单信息
        $order_name = $params['order_name'];
        $address_id = intval($params['address_id']);
        $bs = mb_substr($order_name,0,1);

        $this->ci->load->model('user_address_model');

        if($bs == 'P')
        {
            $order = $this->ci->b2o_parent_order_model->dump(array('order_name' =>$order_name,'order_status' =>'1','uid'=>$uid));
            if (empty($order)) {
                return array('code'=>'300','msg'=>'订单不存在');
            }

            $user_addr = $this->ci->user_address_model->dump(array('id' =>$address_id));
            if (empty($user_addr)) {
                return array('code'=>'300','msg'=>'地址不存在');
            }

            //事务 start
            $this->ci->db->trans_begin();

            $data = array(
                'address_id' => $address_id,
                'change_addr_status'=>0,
            );
            $where = array(
                'id' => $order['id'],
            );
            $this->ci->b2o_parent_order_model->update_order($data, $where);

            $region = '';
            if (is_numeric($user_addr['area']))
            {
                $this->ci->load->model("region_model");
                $this->ci->region_model->region = '';
                $region = $this->ci->region_model->get_region($user_addr['area']);
                $address = $region . $user_addr['address_name'] . $user_addr['address'];
            }
            else
            {
                $address = $user_addr['area'] . $user_addr['address_name'] . $user_addr['address'];
            }

            $data_addr = array(
                'position' => $region,
                'address' => $address,
                'name' => $user_addr['name'],
                'mobile' => $user_addr['mobile'],
                'province' => $user_addr['province'],
                'city' => $user_addr['city'],
                'area' => $user_addr['area'],
                'lonlat'=>$user_addr['lonlat'],
            );
            $where_addr = array(
                'order_id' => $order['id'],
            );
            $this->ci->b2o_parent_order_address_model->update_order_address($data_addr, $where_addr);

            //事务 end
            if ($this->ci->db->trans_status() === FALSE) {
                $this->ci->db->trans_rollback();
                return array("code" => "300", "msg" => "修改地址失败，请重试");
            } else {
                $this->ci->db->trans_commit();
            }

        }
        else
        {
            $order = $this->ci->order_model->dump(array('order_name' =>$order_name,'order_status' =>'1','uid'=>$uid));
            if (empty($order)) {
                return array('code'=>'300','msg'=>'订单不存在');
            }

            if(!in_array($order['operation_id'], array(0,1)) || $order['order_type'] != 1)
            {
                return array('code'=>'300','msg'=>'您的订单已开始出库，无法修改时间');
            }

            $user_addr = $this->ci->user_address_model->dump(array('id' =>$address_id));
            if (empty($user_addr)) {
                return array('code'=>'300','msg'=>'地址不存在');
            }

            //事务 start
            $this->ci->db->trans_begin();

            $data = array(
                'address_id' => $address_id,
                'change_addr_status'=>0,
            );
            $where = array(
                'id' => $order['id'],
            );
            $this->ci->order_model->update_order($data, $where);

            $region = '';
            if (is_numeric($user_addr['area']))
            {
                $this->ci->load->model("region_model");
                $this->ci->region_model->region = '';
                $region = $this->ci->region_model->get_region($user_addr['area']);
                $address = $region . $user_addr['address_name'] . $user_addr['address'];
            }
            else
            {
                $address = $user_addr['area'] . $user_addr['address_name'] . $user_addr['address'];
            }

            $data_addr = array(
                'position' => $region,
                'address' => $address,
                'name' => $user_addr['name'],
                'mobile' => $user_addr['mobile'],
                'province' => $user_addr['province'],
                'city' => $user_addr['city'],
                'area' => $user_addr['area'],
                'lonlat'=>$user_addr['lonlat'],
            );
            $where_addr = array(
                'order_id' => $order['id'],
            );
            $this->ci->load->model('order_address_model');
            $this->ci->order_address_model->update_order_address($data_addr, $where_addr);

            //未同步
            if($order['sync_status'] != 1)
            {
                return array('code'=>'300','msg'=>'订单处理中，请稍后修改');
            }

            //rpc
            $this->ci->load->bll('apirpc');
            $api_rpc = array();
            $api_rpc['order_name'] = $order['order_name'];
            $api_rpc['province'] = $user_addr['province_name'];
            $api_rpc['city'] = $user_addr['city_name'];
            $api_rpc['area'] = $user_addr['area_name'];
            $api_rpc['address'] = $user_addr['address'];
            $rpc = $this->ci->bll_apirpc->b2cChangeAddr($api_rpc);

            if($rpc == 0)
            {
                return array('code'=>'300','msg'=>'您的订单已开始出库，无法修改地址');
            }

            //事务 end
            if ($this->ci->db->trans_status() === FALSE) {
                $this->ci->db->trans_rollback();
                return array("code" => "300", "msg" => "修改地址失败，请重试");
            } else {
                $this->ci->db->trans_commit();
            }
        }

        $res = array();
        $res['name'] = $data_addr['name'];
        $res['address'] = $data_addr['address'];
        $res['mobile'] = $data_addr['mobile'];
        $res['isShowChangeAddressButton'] = 0;

        return array("code" => "200", "msg" => "succ","data"=>$res);
    }


    /*
     * 查询订单商品
     */
    public function searchProductOrder($params)
    {
        //必要参数验证
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
            'product_name' => array('required' => array('code' => '500', 'msg' => 'product_name can not be null')),
        );

        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //获取session信息
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }


        //获取订单列表
        $page = !empty($params['page']) ? $params['page'] : 1;
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = ($page - 1) * $limit;

        //构建查询
        $filter = $this->_setOrderFilterSearch($params,$uid);

        //返回
        $result = $this->ci->order_model->parentOrderListSearch($filter['fields'], $filter['where'], $filter['where_in'], $filter['order_by'], $limit, $offset,$params['product_name']);
        $result = $this->_setOrderStruct($result, $params);
        $total = $this->ci->order_model->parentCountOrderListSearch("id", $filter['where'],  $filter['where_in'],'',$params['product_name']);
        $data = array('code'=>'200','msg'=>'','list' => $result, 'countOrder' => $total);

        return $data;
    }


    /*
     *  拆单 － 送礼
     */
    public function giftSplit($order_name)
    {
        $msg = array('code'=>'300','msg'=>'fail');

        //事务 start
        $this->ci->db->trans_begin();

        $order = $this->ci->b2o_parent_order_model->dump(array('order_name' =>$order_name,'pay_status'=>'1','order_status' =>'1','order_type'=>'9'));
        if (!empty($order)) {

            $spilt_order = array();
            $spilt_order['money'] = $order['money'];
            $spilt_order['use_money_deduction'] = $order['use_money_deduction'];

            $where = array(
                'p_order_id' => $order['id'],
                'order_status' => 1,
                'order_type'=>9,
            );
            $son_orders = $this->ci->order_model->getList('*',$where);
            $items = array();
            foreach ($son_orders as $key => $s_order) {
                $items[$s_order['id']] = array('id'=>$s_order['id'],'amount'=>$s_order['goods_money'],'discount'=>0,'money'=>1,'use_money_deduction'=>1);
            }
            $splits_son_order = imperfect_split($spilt_order,$items);

            foreach ($splits_son_order as $s_order_id => $value) {
                $update_data = array();
                $update_data['money'] = $value['money'];
                $update_data['use_money_deduction'] = $value['use_money_deduction'];
                $update_data['pay_status'] = 1;
                $update_data['pay_name'] = $order['pay_name'];
                $update_data['pay_parent_id'] = $order['pay_parent_id'];
                $update_data['pay_id'] = $order['pay_id'];
                $where = array('id' => $s_order_id,'p_order_id'=>$order['id']);
                $this->ci->order_model->update_order($update_data, $where);
            }

            // $data = array(
            //     'pay_status' =>1,
            //     //'show_status'=>1,
            //     'pay_name'=>$order['pay_name'],
            //     'pay_parent_id'=>$order['pay_parent_id'],
            //     'pay_id'=>$order['pay_id'],
            // );
            // $where = array(
            //     'p_order_id' => $order['id'],
            //     'order_status' => 1,
            //     'order_type'=>9,
            // );
            // $this->ci->order_model->update_order($data, $where);

            $msg = array('code'=>'200','msg'=>'succ');
        }

        //事务 end
        if ($this->ci->db->trans_status() === FALSE) {
            $this->ci->db->trans_rollback();
        } else {
            $this->ci->db->trans_commit();
        }

        return $msg;
    }

    /*
     * 使用自提
     */
    public function useSelfPick($params)
    {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }

        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        return array('code' => '200', 'msg' => "succ", 'uid' => $uid);
    }

    /*
     * 取消使用自提
     */
    public function cancelSelfPick($params)
    {
        //必要参数验证start
        $required_fields = array(
            'connect_id' => array('required' => array('code' => '500', 'msg' => 'connect id can not be null')),
        );
        if ($alert_msg = check_required($params, $required_fields)) {
            return array('code' => $alert_msg['code'], 'msg' => $alert_msg['msg']);
        }
        //必要参数验证end

        //获取session信息start
        $uid_result = $this->get_uid_by_connect_id($params['connect_id']);
        if ($uid_result['code'] != '200') {
            return $uid_result;
        } else {
            $uid = $uid_result['msg'];
        }
        //获取session信息end

        return array("code" => "200", "msg" => "succ", "uid" => $uid);
    }

       //$parent_type 1父单  2子单
    public function returnPostage($order_name,$parent_type = 2){
        $this->ci->load->model('b2o_parent_order_model');
        $this->ci->load->model('order_postage_model');
        $this->ci->load->model('postage_log_model');
        $this->ci->load->model('order_model');
        $p_order = array();
        if($parent_type == 1){
            $p_order = $this->ci->b2o_parent_order_model->dump(array('order_name'=>$order_name));
        }else{
            $s_order = $this->ci->order_model->dump(array('order_name'=>$order_name));
            $p_order_id = $s_order['p_order_id'];
            if(empty($p_order_id)){  //无父单的订单不会有邮费特权
                return true;
            }
            $other_son_orders = $this->ci->order_model->getList('*',array('p_order_id'=>$p_order_id,'operation_id !='=>5,'order_name !='=>$order_name));
            if($other_son_orders){ //其他未取消的子单
                return true;
            }
            $p_order = $this->ci->b2o_parent_order_model->dump(array('id'=>$p_order_id));
        }
        if(empty($p_order)){
            return true;
        }
        $log = $this->ci->postage_log_model->dump(array('order_name'=>$p_order['order_name']));
        if(empty($log)){ //未使用邮费特权
            return true;
        }
        //下单时使用的邮费特权  todo 不一定准
        $post_orders = $this->ci->order_postage_model->getList("*",array('uid'=>$p_order['uid'],'postage_status'=>1,'start_time <= '=>$p_order['time'],'end_time >= '=>$p_order['time']),0,1,'time desc');
        if(empty($post_orders)){
            return true;
        }
        $post_order = $post_orders[0];
        $res = $this->ci->order_postage_model->returnPostage($post_order['id'],1);
        if(!$res){
            return false;
        }
        $post_log = array(
            'uid'=>$p_order['uid'],
            'order_name'=>$p_order["order_name"],
            'time'=>date('Y-m-d H:i:s'),
            'remark'=>'订单号' .$p_order["order_name"]."取消，返还1次特权",
            'start_time'=>$post_order['start_time'],
            'end_time'=>$post_order['end_time'],
            'times'=>$post_order['times'],
            'available_times'=>$post_order['available_times'] + 1,
        );
        $res = $this->ci->postage_log_model->addPostLog($post_log);
        return $res;
    }

    //$parent_type 1父单  2子单
    public function reducePostage($order_name,$parent_type = 2){
        $this->ci->load->model('b2o_parent_order_model');
        $this->ci->load->model('order_postage_model');
        $this->ci->load->model('postage_log_model');
        $this->ci->load->model('order_model');
        $p_order = array();
        if($parent_type == 1){
            $p_order = $this->ci->b2o_parent_order_model->dump(array('order_name'=>$order_name));
        }else{
            $s_order = $this->ci->order_model->dump(array('order_name'=>$order_name));
            $p_order_id = $s_order['p_order_id'];
            if(empty($p_order_id)){  //无父单的订单不会有邮费特权
                return true;
            }
            $p_order = $this->ci->b2o_parent_order_model->dump(array('id'=>$p_order_id));
        }
        if(empty($p_order)){
            return true;
        }
        $logs = $this->ci->postage_log_model->getList("*",array('order_name'=>$p_order['order_name']));
        if(empty($logs)){ //未使用邮费特权
            return true;
        }
        $reducetimes = 0;
        $returntimes = 0;
        foreach ($logs as $key => $value) {
            if($value['remark'] == '订单号' .$p_order["order_name"]."取消，返还1次特权"){
                $returntimes ++;
            }else{
                $reducetimes ++;
            }
        }
        if($reducetimes > $returntimes){ //扣减次数大于返还次数 ， 不再扣减
            return true;
        }
        //下单时使用的邮费特权  todo 不一定准
        $post_orders = $this->ci->order_postage_model->getList("*",array('uid'=>$p_order['uid'],'postage_status'=>1,'start_time <= '=>$p_order['time'],'end_time >= '=>$p_order['time']),0,1,'time desc');
        if(empty($post_orders)){
            return true;
        }
        $post_order = $post_orders[0];
        $res = $this->ci->order_postage_model->reducePostage($post_order['id'],1);
        if(!$res){
            return false;
        }
        $post_log = array(
            'uid'=>$p_order['uid'],
            'order_name'=>$p_order["order_name"],
            'time'=>date('Y-m-d H:i:s'),
            'remark'=>'订单号' .$p_order["order_name"].'恢复取消，扣除1次特权',
            'start_time'=>$post_order['start_time'],
            'end_time'=>$post_order['end_time'],
            'times'=>$post_order['times'],
            'available_times'=>$post_order['available_times'] - 1,
        );
        $res = $this->ci->postage_log_model->addPostLog($post_log);
        return $res;
    }

    //$parent_type 1父单  2子单
    public function returnCard($card_number,$order_name,$parent_type = 2){
        $this->ci->load->model('b2o_parent_order_model');
        $this->ci->load->model('order_model');
        $order = array();
        if($parent_type == 1){
            $order = $this->ci->b2o_parent_order_model->dump(array('order_name'=>$order_name,'use_card'=>$card_number));
        }else{
            $order = $this->ci->order_model->dump(array('order_name'=>$order_name,'use_card'=>$card_number));
            if(empty($order)){
                return true;
            }
            $p_order_id = $order['p_order_id'];
            if(empty($p_order_id)){  //无父单的订单

            }else{
                $other_son_orders = $this->ci->order_model->getList('*',array('p_order_id'=>$p_order_id,'use_card'=>$card_number,'operation_id !='=>5,'order_name !='=>$order_name));
                if($other_son_orders){ //其他未取消的子单
                    return true;
                }
            }
        }
        if(empty($order)){
            return true;
        }
        $this->ci->load->bll('card', null, 'bll_card');
        return $this->ci->bll_card->return_card($order['uid'], $order['use_card'], $order['order_name'], "订单{$order['order_name']}取消");
    }
}
