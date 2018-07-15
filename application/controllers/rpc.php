<?php
/**
 * 与订单池的交互
 *
 * PHP version 5.4
 *
 * @category  PHP
 * @package   Controllers
 * @author    pax <chenping@fruitday.com>
 * @copyright 2014 fruitday
 * @version   GIT: $Id: Order_rpc.php 1 2014-08-01 16:02:08Z pax $
 * @link      http://www.fruitday.com
 **/
class Rpc extends CI_Controller
{

    /**
     * 往订单池推送
     *
     * @return void
     * @author
     **/
    public function push_order()
    {
        if (php_sapi_name() !== 'cli') return ;

        $this->load->bll('pool/order');

        $order_names = func_get_args();

        $a_orders = $this->bll_pool_order->get_push_orders($order_names);

        if (!$a_orders) return ;

        $this->load->bll('rpc/request');

        $orders_arr = array_chunk($a_orders,200,true);

        foreach ($orders_arr as $key => $orders) {
            $orderids = array_keys($orders);

            if ($orderids) {
                $this->bll_pool_order->set_sync($orderids,'2');
            }

            // 金额校验
            $orders = $this->bll_pool_order->check_order($orders);
            if (!$orders) return ;

            if ($this->bll_pool_order->rpc_log) $this->bll_rpc_request->set_rpc_log($this->bll_pool_order->rpc_log);

            $response = $this->bll_rpc_request->realtime_call(POOL_ORDER_URL,array_values($orders));

             if ($response['result'] != '1' && $orderids) {
                 // $this->bll_pool_order->set_sync($orderids,'0');
             }

            // 同时推送oms测试环境
            // $this->load->bll('rpc/request54','bll_rpc_request54');
            // $this->bll_rpc_request54->realtime_call('http://122.144.167.54:38080/api/official/ordersync',array_values($orders),'POST',6);
        }
    }


    /**
     * 往订单池推送团购订单
     *
     * @return void
     * @author
     **/
    public function push_group_order()
    {
        if (php_sapi_name() !== 'cli') return ;

        $this->load->bll('pool/order');


        $this->load->model('group_model');
        $order_names_arr = $this->group_model->get_finish_orders();

        if(empty($order_names_arr)){
            return;
        }else{
            $order_names = array();
            foreach ($order_names_arr as $key => $value) {
                $order_names[] = $value['order_name'];
            }
        }

        $a_orders = $this->bll_pool_order->get_push_orders($order_names);

        if (!$a_orders) return ;

        $this->load->bll('rpc/request');

        $orders_arr = array_chunk($a_orders,100,true);

        foreach ($orders_arr as $key => $orders) {
            $orderids = array_keys($orders);

            if ($orderids) {
                $this->bll_pool_order->set_sync($orderids,'2');
            }

            // 金额校验
            $orders = $this->bll_pool_order->check_order($orders);
            if (!$orders) return ;

            if ($this->bll_pool_order->rpc_log) $this->bll_rpc_request->set_rpc_log($this->bll_pool_order->rpc_log);

            $response = $this->bll_rpc_request->realtime_call(POOL_ORDER_URL,array_values($orders));

             if ($response['result'] != '1' && $orderids) {
                 // $this->bll_pool_order->set_sync($orderids,'0');
             }

            // 同时推送oms测试环境
            $this->load->bll('rpc/request54','bll_rpc_request54');
            $this->bll_rpc_request54->realtime_call('http://122.144.167.54:38080/api/official/ordersync',array_values($orders),'POST',6);
        }
    }

    /**
     * 推送发票
     *
     * @return void
     * @author
     **/
    public function push_invoice()
    {
        if (php_sapi_name() !== 'cli') return ;

        $this->load->bll('pool/invoice');

        $invoice_list = $this->bll_pool_invoice->get_push_invoice();

        if ($invoice_list) {
            $this->load->bll('rpc/request');

            if ($this->bll_pool_invoice->rpc_log) $this->bll_rpc_request->set_rpc_log($this->bll_pool_invoice->rpc_log);

            $response = $this->bll_rpc_request->realtime_call(POOL_INVOICE_URL,array_values($invoice_list));

            if ($response['result'] == '1') {
                $this->load->model('trade_invoice_model');

                $this->trade_invoice_model->update(array('sync_erp' => '1'),array('id' => array_keys($invoice_list)));
            }
        }
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function push_trade()
    {
        if (php_sapi_name() !== 'cli') return ;

        $filter = array();
        $order_names = func_get_args();
        if ($order_names){
          $filter['trade_number'] = $order_names;
        }

        $this->load->bll('pool/recharge');

        $recharges = $this->bll_pool_recharge->get_push_data($filter);

        if (!$recharges) return ;

        $this->load->bll('rpc/request');
        $this->load->model('trade_model');
        foreach ($recharges as $recharge) {
            $this->bll_rpc_request->set_rpc_log( array(
                'rpc_desc' => '充值记录推送','obj_type'=>'trade','obj_name'=>$recharge['trancode'],
            ));

            $rs = $this->bll_rpc_request->realtime_call(POOL_RECHARGE_URL,$recharge);

            if ($rs==false) continue ;

            $this->trade_model->update(array('sync_erp'=>'1'),array('trade_number'=>$recharge['trancode']));
        }


    }

    /**
     * 查看请求结构
     *
     * @return void
     * @author
     **/
    public function get_order_format()
    {
        $order_names = func_get_args();

        if (!$order_names) exit('no orders');

        $this->load->bll('pool/order');

        $orders = $this->bll_pool_order->get_push_orders($order_names,false);

        print_r($orders);exit;
    }

    public function push_feepay(){
        //if (php_sapi_name() !== 'cli') return ;

        $order_names = func_get_args();

        if (!$order_names) exit('no orders');

        $this->load->bll('pool/order');
        $this->load->model('order_model');

        foreach ($order_names as $key => $order_name) {
            $order = $this->order_model->dump(array('order_name'=>$order_name));

            $this->bll_pool_order->syncfee($order['id']);
        }
    }

    public function push_part_order($order_name)
    {
        //if (php_sapi_name() !== 'cli') return ;

        $this->load->bll('pool/order');

        if(empty($order_name)) return;

        $order_names = array($order_name);

        $orders = $this->bll_pool_order->get_push_orders($order_names);

        if (!$orders) return ;
        
        $orders = $this->bll_pool_order->check_order($orders);
        if (!$orders) return ;

        $this->load->bll('rpc/request');

        $orderids = array_keys($orders);

        if ($orderids) {
            $this->bll_pool_order->set_sync($orderids,'2');
        }

        if ($this->bll_pool_order->rpc_log) $this->bll_rpc_request->set_rpc_log($this->bll_pool_order->rpc_log);

        $response = $this->bll_rpc_request->realtime_call(POOL_ORDER_URL,array_values($orders));

         if ($response['result'] != '1' && $orderids) {
             //$this->bll_pool_order->set_sync($orderids,'0');
         }
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function finishorder()
    {
        $order_names = func_get_args();

        if (!$order_names) exit('no orders');

        $this->load->bll('pool/order');
        $this->load->model('order_model');

        foreach ($order_names as $key => $order_name) {
            $order = $this->order_model->dump(array('order_name'=>$order_name));
            $filter = array(
                'orderNo' => $order_name,
                'score' => $order['score'],
            );
            $rs = $this->bll_pool_order->finish($filter);
            var_dump($rs);
        }

    }

    public function push_transaction (){
        //if (php_sapi_name() !== 'cli') return ;
        $order_names = func_get_args();
        $this->load->bll('rpc/request');
        //$this->load->bll('rpc/request54','bll_rpc_request54');
        $this->load->bll('pool/recharge');
        $recharge = $this->bll_pool_recharge->get_trade_new($order_names);
        if(empty($recharge)) return;
        $trade_data = $this->format_trade_data($recharge,2);
        $t_names = array();
        foreach ($trade_data as $value) {
            $t_names[] = $value['feeNum'];
        }

        $this->bll_rpc_request->set_rpc_log( array(
                'rpc_desc' => '交易记录推送','obj_type'=>'transaction',
            ));

        $data['ofvoLst'] = $trade_data;
        $rs = $this->bll_rpc_request->realtime_call(POOL_TRANSACTION_URL,$data);

        if ($rs==false) return ;

        $this->load->model('trade_model');

        $this->trade_model->update(array('sync_erp'=>'2'),array('trade_number'=>$t_names));
    }

    public function push_order_transaction(){
        $order_names = func_get_args();
        $this->load->bll('rpc/request');
        //$this->load->bll('rpc/request54','bll_rpc_request54');
        $this->load->bll('pool/order');
        $this->load->model('order_model');

        $orders = $this->bll_pool_order->get_order_trade($order_names);
        if(empty($orders)) return;
        $order_data = $this->format_trade_data($orders,1);
        $o_names = array();
        foreach ($order_data as $value) {
            $o_names[] = $value['feeNum'];
        }

        $data['ofvoLst'] = $order_data;
        $this->bll_rpc_request->set_rpc_log( array(
                'rpc_desc' => '交易记录推送','obj_type'=>'transaction',
            ));
        $rs = $this->bll_rpc_request->realtime_call(POOL_TRANSACTION_URL,$data);

        if ($rs==false) return ;

        $this->order_model->update(array('sync_erp'=>'1'),array('order_name'=>$o_names));
    }

    /**
     * 周期购订单支付
     */
    public function push_s_order_transaction(){
        $order_names = func_get_args();
        $this->load->bll('rpc/request');
        //$this->load->bll('rpc/request54','bll_rpc_request54');
        $this->load->bll('pool/order');
        $this->load->model('order_model');
        $this->load->model('subscription_model');
        $this->load->model('user_model');


        $orders = $this->subscription_model->orderTrade($order_names);

        if(empty($orders)) return;
        $order_data = $this->format_trade_data($orders,3);
        $o_names = array();
        foreach ($order_data as $value) {
            $o_names[] = $value['feeNum'];
            $this->user_model->upgrade_rank($value['buyerId']);
        }

        $data['ofvoLst'] = $order_data;
        $this->bll_rpc_request->set_rpc_log( array(
            'rpc_desc' => '交易记录推送','obj_type'=>'transaction',
        ));

        $rs = $this->bll_rpc_request->realtime_call(POOL_TRANSACTION_URL,$data);

        if ($rs==false) return ;

        $this->subscription_model->syncErp($o_names);
    }

    /**
     * 自动创建周期购配送订单
     */
    public function create_s_delivery_order()
    {
        $this->load->model('subscription_model');
        $this->subscription_model->createDeliveryOrder();
    }

    public function format_trade_data($trade,$type){
        $this->load->bll('pool/order');
        $format_data = array();
        $paym = $this->config->item("paym");
        $payment = $this->config->item("payment");
        foreach ($trade as $value) {
            if($value['money'] == 0 ) continue;
            $data = array();
            switch ($type) {
                case '1':  //订单
                    $data['feeNum'] = $value['order_name'];
                    $data['billNo'] = $value['billno']?$value['billno']:$value['order_name'];
                    $data['payTime'] = ($value['update_pay_time'] && $value['update_pay_time'] != '0000-00-00 00:00:00')?$value['update_pay_time']:$value['time'];
                    $data['buyerId'] = $value['uid'];
                    $data['buyerName'] = $value['username'];
                    $data['feeType'] = 1;
                    $data['orderFrom'] = 'B2C';
                    if(in_array($value['order_type'], array(3,4))){
                        $data['orderFrom'] = 'O2O';
                        $c_order = $this->bll_pool_order->getO2oChildOrderInfo($value['id']);
                    }
                    $pay_data = $this->format_payMethod($value['pay_parent_id'],$value['pay_id']);
                    $payInfo = array();
                    $payInfo['payMethod'] = $pay_data['payMethod'];
                    $payInfo['payPlatform'] = $pay_data['payPlatform'];
                    $payInfo['totalAmt'] = bcsub($value['money'], $value['bank_discount'], 2);
                    $payInfo['payRid'] = $value['trade_no'];
                    $payInfo['cardMerchantCode'] = '';
                    $payInfo['bank_discount'] = $value['bank_discount'];
                    if($pay_data['payMethod'] == 5){
                        $payInfo['cardMerchantCode'] = $this->bll_pool_order->getProCard($value['order_name']);
                    }
                    if(in_array($value['order_type'], array(3,4))){
                        if($c_order){
                            foreach ($c_order as $c_info) {
                                $payInfo['business'][] = array('merchantCode'=>$c_info['code'],'amountFee'=>$c_info['money']);
                            }
                        }else{
                            $payInfo['business'] = array(array('merchantCode'=>'FD001','amountFee'=>$value['money']));
                        }
                    }
                    $data['payment'][] = $payInfo;
                    if($value['use_money_deduction']>0){
                        $payInfo = array();
                        $payInfo['payMethod'] = 9;
                        $payInfo['payPlatform'] = '';
                        $payInfo['totalAmt'] = $value['use_money_deduction'];
                        $payInfo['payRid'] = '';
                        $payInfo['cardMerchantCode'] = '';
                        if(in_array($value['order_type'], array(3,4))){
                            if($c_order){
                                foreach ($c_order as $c_info) {
                                    $payInfo['business'][] = array('merchantCode'=>$c_info['code'],'amountFee'=>0);
                                }
                            }else{
                                $payInfo['business'] = array(array('merchantCode'=>'FD001','amountFee'=>$value['use_money_deduction']));
                            }
                        }
                        $data['payment'][] = $payInfo;
                    }
                    if($value['jf_money']>0){
                        $payInfo = array();
                        $payInfo['payMethod'] = 8;
                        $payInfo['payPlatform'] = '';
                        $payInfo['totalAmt'] = $value['jf_money'];
                        $payInfo['payRid'] = '';
                        $payInfo['cardMerchantCode'] = '';
                        if(in_array($value['order_type'], array(3,4))){
                            if($c_order){
                                foreach ($c_order as $c_info) {
                                    $payInfo['business'][] = array('merchantCode'=>$c_info['code'],'amountFee'=>$c_info['jf_money']);
                                }
                            }else{
                                $payInfo['business'] = array(array('merchantCode'=>'FD001','amountFee'=>$value['jf_money']));
                            }
                        }
                        $data['payment'][] = $payInfo;
                    }
                    if($value['card_money']>0){
                        $payInfo = array();
                        $payInfo['payMethod'] = 12;
                        $payInfo['payPlatform'] = '';
                        $payInfo['totalAmt'] = $value['card_money'];
                        $payInfo['payRid'] = '';
                        $payInfo['cardMerchantCode'] = $value['use_card'];
                        if(in_array($value['order_type'], array(3,4))){
                            if($c_order){
                                foreach ($c_order as $c_info) {
                                    $payInfo['business'][] = array('merchantCode'=>$c_info['code'],'amountFee'=>$c_info['card_money']);
                                }
                            }else{
                                $payInfo['business'] = array(array('merchantCode'=>'FD001','amountFee'=>$value['card_money']));
                            }
                        }
                        $data['payment'][] = $payInfo;
                    }
                    break;
                case '2':  //余额充值,扣款
                    $data['feeNum'] = $value['trade_number'];
                    $data['feeType'] = 3;
                    $data['payTime'] = ($value['update_pay_time'] && $value['update_pay_time'] != '0000-00-00 00:00:00')?$value['update_pay_time']:$value['time'];
                    $data['buyerId'] = $value['uid'];
                    $data['buyerName'] = $value['username'];
                    $payInfo = array();
                    $payInfo['totalAmt'] = $value['money'];
                    $payInfo['payRid'] = $value['trade_no'];
                    $payInfo['payMethod'] = (int) $paym[$value['payment']];
                    $payInfo['payPlatform'] = (int) $payment[$value['payment']];
                    $payInfo['cardMerchantCode'] = $value['card_number'];
                    $data['payment'][] = $payInfo;
                    break;
                case '3':  //周期购订单
                    $data['feeNum'] = $value['order_name'];
                    $data['billNo'] = $value['billno']?$value['billno']:$value['order_name'];
                    $data['payTime'] = ($value['update_pay_time'] && $value['update_pay_time'] != '0000-00-00 00:00:00')?$value['update_pay_time']:$value['time'];
                    $data['buyerId'] = $value['uid'];
                    $data['buyerName'] = $value['username'];
                    $data['feeType'] = 4;//周期购类型

                    $pay_data = $this->format_payMethod($value['pay_parent_id'],$value['pay_id']);
                    $payInfo = array();
                    $payInfo['payMethod'] = $pay_data['payMethod'];
                    $payInfo['payPlatform'] = $pay_data['payPlatform'];
                    $payInfo['totalAmt'] = bcsub($value['money'], $value['bank_discount'], 2);
                    $payInfo['payRid'] = $value['trade_no'];
                    $payInfo['cardMerchantCode'] = '';
                    $payInfo['bank_discount'] = $value['bank_discount'];
                    if($pay_data['payMethod'] == 5){
                        $payInfo['cardMerchantCode'] = $this->bll_pool_order->getProCard($value['order_name']);
                    }
                    $data['payment'][] = $payInfo;
                    if($value['use_money_deduction']>0){
                        $payInfo = array();
                        $payInfo['payMethod'] = 9;
                        $payInfo['payPlatform'] = '';
                        $payInfo['totalAmt'] = $value['use_money_deduction'];
                        $payInfo['payRid'] = '';
                        $payInfo['cardMerchantCode'] = '';
                        $data['payment'][] = $payInfo;
                    }

                    break;
                default:
                    return;
                    break;
            }
            $data and $format_data[] = $data;
        }
        return $format_data;
    }

    private function format_payMethod($pay_parent_id,$pay_id){
        $pay_data = array();
        $online_pay = $this->config->item("oms_online_pay");
        $pay_data['payMethod'] = $online_pay[$pay_parent_id]['way_id'];
        $pay_data['payPlatform'] = $online_pay[$pay_parent_id]['children_platform_id'][$pay_id] ? $online_pay[$pay_parent_id]['children_platform_id'][$pay_id] : $online_pay[$pay_parent_id]['platform_id'];
        // switch ($pay_parent_id) {
        //     case 1:  //支付宝
        //         $pay_data['payMethod'] = 1;
        //         $pay_data['payPlatform'] = 1003;
        //         break;
        //     case 2:  //联华OK会员卡在线支付
        //         $pay_data['payMethod'] = 1;
        //         $pay_data['payPlatform'] = 1002;
        //         break;
        //     case 3:  //在线银行卡
        //         $pay_data['payMethod'] = 1;
        //         switch ($pay_id) {
        //             case "00021":
        //                 $pay_data['payPlatform'] = 1006;
        //                 break;
        //             case "00102":
        //                 $pay_data['payPlatform'] = 1011;
        //                 break;
        //             case "00103":
        //                 $pay_data['payPlatform'] = 1015;
        //                 break;
        //             case "00105":
        //                 $pay_data['payPlatform'] = 1016;
        //                 break;
        //             case "00003":
        //                 $pay_data['payPlatform'] = 1007;
        //                 break;
        //             case "00005":
        //                 $pay_data['payPlatform'] = 1004;
        //                 break;
        //             case "00101":
        //                 $pay_data['payPlatform'] = 1012;
        //                 break;
        //             case "00100":
        //                 $pay_data['payPlatform'] = 1010;
        //                 break;
        //             default:
        //                 $pay_data['payPlatform'] = 1001;
        //                 break;
        //         }
        //         break;
        //     case 5:  //余额
        //         $pay_data['payMethod'] = 9;
        //         $pay_data['payPlatform'] = '';
        //         break;
        //     case 6:  //提货券
        //         $pay_data['payMethod'] = 5;
        //         $pay_data['payPlatform'] = 5001;
        //         break;
        //     case 7: //微信开发平台
        //         $pay_data['payMethod'] = 1;
        //         $pay_data['payPlatform'] = 1005;
        //         break;
        //     case 8:
        //         $pay_data['payMethod'] = 1;
        //         $pay_data['payPlatform'] = 1014;
        //         break;
        //     case 9:
        //         $pay_data['payMethod'] = 1;
        //         $pay_data['payPlatform'] = 1008;
        //         break;
        //     case 10:
        //         $pay_data['payMethod'] = 1;
        //         $pay_data['payPlatform'] = 1013;
        //         break;
        //     case 11:
        //         $pay_data['payMethod'] = 1;
        //         $pay_data['payPlatform'] = 1017;
        //         break;
        //     default:
        //         # code...
        //         break;
        // }
        return $pay_data;
    }

    function pushOmsCancelOrder(){
        if (php_sapi_name() !== 'cli') return ;
        $this->load->bll('pool/order');
        $this->bll_pool_order->pushOmsCancelOrder();
        //echo 'finish';
    }

    function checkOrderStatusSendLogistics(){
        header("Content-Type: text/html;charset=utf-8");
        $params = func_get_args();
        $res = $this->checkOrderStatus(array('sendLogistics',$params[0]));
        if($params[0]){
            if($res && $res['result'] && $res['result'] == 1){
                $msg = '成功';
            }else{
                $msg = '失败，'.$res['errorMsg']['msg'];
            }
            echo $params[0]." 发货".$msg;
        }
    }

    function checkOrderStatusFinish(){
        header("Content-Type: text/html;charset=utf-8");
        $params = func_get_args();
        $res = $this->checkOrderStatus(array('finish',$params[0]));
        if($params[0]){
            if($res && $res['result'] && $res['result'] == 1){
                $msg = '成功';
            }else{
                $msg = '失败，'.$res['errorMsg']['msg'];
            }
            echo $params[0]." 完成".$msg;
        }
    }

    function checkOrderStatus($params){
        //if (php_sapi_name() !== 'cli') return ;
        // $params = func_get_args();

        $type = $params[0];
        $order_name = isset($params[1])?$params[1]:'';
        $this->load->bll('pool/order');
        $this->load->bll('rpc/request');
        $orders = $this->bll_pool_order->getCheckStatusOrders($type,$order_name);
        if(empty($orders)) return array('result'=>0,'errorMsg'=>array('msg'=>'无此订单或订单无法执行此操作'));
        $res = array();
        switch ($type) {
            case 'sendLogistics':
                $response = $this->bll_rpc_request->realtime_call(POOL_SENDSTATE_URL,array_values($orders));
                if(empty($response['orders'])){
                    return array('result'=>0,'errorMsg'=>array('msg'=>'OMS无法执行此操作'));
                }
                $res = $this->bll_pool_order->batchSendLogistics($response);
                break;
            case 'finish':
                $response = $this->bll_rpc_request->realtime_call(POOL_FINISHSTATE_URL,array_values($orders));
                if(empty($response['orders'])){
                    return array('result'=>0,'errorMsg'=>array('msg'=>'OMS无法执行此操作'));
                }
                $res = $this->bll_pool_order->batchFinish($response);
                break;
            default:
                # code...
                break;
        }
        return $res;
    }

    /*推送大客户收款*/
    public function sendComplanyService(){
        $this->load->bll('pool/complany');
        $this->load->bll('rpc/request');

        $sendList = $this->bll_pool_complany->sendComplanyService();
        if (empty($sendList)) {
            return ;
        }
        //测试环境$response = $this->bll_rpc_request->realtime_call('http://122.144.167.61:38080/fruitday-soa/official/lgcPayOrderCall',array_values($sendList));
        $response = $this->bll_rpc_request->realtime_call(POOL_SENDSERVICE_URL,array_values($sendList));
        $this->bll_pool_complany->updateSynErp($response);
    }

    public function getOmsWarehouse(){
        $this->load->bll('pool/hypostatic_warehouse');
        $this->load->bll('rpc/request');
        $rs = $this->bll_rpc_request->realtime_call_oms(OMS_WAREHOUSE_URL,$data,'JSON',6,base64_decode(OMS_WAREHOUSE_AESKEY),OMS_WAREHOUSE_SHAKEY);
        if(empty($rs) || empty($rs['list']))
            return;
        // echo "<pre>";
        // print_r($rs);
        $this->bll_pool_hypostatic_warehouse->addWarehouse($rs['list']);
    }

    public function getOmsStock(){
        //if (php_sapi_name() !== 'cli') return ;
        $this->load->library('curl',null,'http_curl');
        $data = array();
        $data['security_token'] = OMS_PRODUCT_STOCK_SECURITY_TOKEN;
        $rs = $this->http_curl->request(OMS_PRODUCT_STOCK_URL,$data,'POST',array('timeout' => 180));
        $response = json_decode($rs['response'],true);
        if(empty($response) || empty($response['datas']))
            return;
        $datas = $response['datas'];
        $inner_codes = array();
        foreach ($datas as $key => $value) {
            $inner_codes[] = $value['code'];
        }
        $this->load->model('product_model');
        $this->load->model('product_warehouse_stock_model');
        $this->load->model('hypostatic_warehouse_model');
        $ph_warehouse = $this->hypostatic_warehouse_model->getList();
        $ph_ware_code = array();
        foreach ($ph_warehouse as $key => $value) {
            $ph_ware_code[$value['code']] = $value['id'];
        }
        $res = $this->product_model->getProductnoByInnerCode($inner_codes);
        $filter = array();
        foreach ($res as $key => $value) {
            $filter[$value['inner_code']][] = $value['code'];
        }
        $stock_info = array();
        foreach ($datas as $key => $value) {
            if(isset($filter[$value['code']]) && $filter[$value['code']]){
                foreach ($filter[$value['code']] as $k => $v) {
                    $stock_info_one = array();
                    $stock_info_one['product_no'] = $product_no = $v;
                    $stock_info_one['ph_warehouse_id'] = $ph_warehouse_id = $ph_ware_code[$value['wh']];
                    $stock_info_one['stock'] = $stock = (intval($value['count'])>=0)?intval($value['count']):0;
                    if(!$product_no || !$ph_warehouse_id) continue;
                    $this->product_warehouse_stock_model->setProductStock($product_no,$ph_warehouse_id,$stock);
                    $stock_info[] = $stock_info_one;
                }
            }
        }
        $stock_info && $this->product_warehouse_stock_model->addProductStock($stock_info);
    }

    //推送物流评价
    public function push_evaluation(){
        if (php_sapi_name() !== 'cli') return ;

        $this->load->model('evaluation_model');

        $order_names = func_get_args();

        $evaluations = $this->evaluation_model->get_push_evaluations($order_names);

        if (!$evaluations) return ;

        $format_datas = array();
        $push_ids = array();
        foreach ($evaluations as $key => $value) {
            $format_one = array();
            $format_one['orderNo'] = $value['order_id'];
            $format_one['comment'] = $value['remark'];
            $format_one['attitude'] = $value['score_service'];
            $format_one['image'] = $value['score_show'];
            $format_one['ontime'] = $value['score_time'];
            $format_one['time'] = $value['time'];
            $format_datas[] = $format_one;
            $push_ids[] = $value['id'];
        }
        $this->load->bll('rpc/request');
        $this->bll_rpc_request->set_rpc_log( array(
                'rpc_desc' => '物流评价推送','obj_type'=>'evaluation',
            ));
        $response = $this->bll_rpc_request->realtime_call(POOL_EVALUATION_URL,array_values($format_datas),'POST',20);

        if ($response['result'] == '1') {
            $this->evaluation_model->set_sync($push_ids,'1');
        }
    }

    //推送商品评价
    public function push_comment(){
        if (php_sapi_name() !== 'cli') return ;

        $this->load->model('comment_model');

        $comments = $this->comment_model->get_push_comments();

        if (!$comments) return ;

        $format_datas = array();
        $push_ids = array();
        foreach ($comments as $key => $value) {
            $format_one = array();
            $format_one['commentId'] = (int)$value['id'];
            $format_one['orderNo'] = $value['order_name'];
            $format_one['prodName'] = $value['product_name'];
            $format_one['comment'] = $value['content'];
            $format_one['average'] = $value['star'];
            $format_one['texture'] = $value['star_eat'];
            $format_one['looks'] = $value['star_show'];
            $format_one['time'] = $value['time'];
            $format_datas[] = $format_one;
            $push_ids[] = $value['id'];
        }
        $this->load->bll('rpc/request');
        $this->bll_rpc_request->set_rpc_log( array(
                'rpc_desc' => '商品评论推送','obj_type'=>'productComment',
            ));
        $response = $this->bll_rpc_request->realtime_call(POOL_COMMENT_URL,array_values($format_datas),'POST',20);

        if ($response['result'] == '1') {
            $this->comment_model->set_sync($push_ids,'1');
        }
    }

    public function getCityShopProductSapInfo(){
        if (php_sapi_name() !== 'cli') return ;
        $this->load->model('cityshop_product_model');
        $products = $this->cityshop_product_model->getSyncProductInfo();
        if(empty($products)) return;
        $sap_codes = array();
        foreach ($products as $key => $value) {
            $sap_codes[] = $value['sap_code'];
        }
        $sap_codes = array_unique($sap_codes);
        $this->cityshop_product_model->setSyncedCityshopProducts($sap_codes);
        $this->load->bll('rpc/request');
        $response = $this->bll_rpc_request->realtime_call(POOL_SAP_PRO_URL,array_values($sap_codes),'POST',20);
        if ($response['result'] == '1') {
            $list = $response['list'];
            $set_sync_codes = array();
            foreach ($list as $key => $value) {
                $sap_code = $value['prodCode'];
                $data_one = array();
                $data_one['cn_name'] = $value['productName'];
                $data_one['en_name'] = $value['materialTextEn'];
                $data_one['cn_brand'] = $value['brandText'];
                $data_one['en_brand'] = $value['brandTextEn'];
                $data_one['cn_single_name'] = $value['productName'];
                $data_one['en_single_name'] = $value['materialTextEn'];
                $data_one['cn_spec'] = $value['prodStan'];
                //$data_one['en_spec'] = $value[''];
                $data_one['cn_madein'] = $value['originCountryText'];
                $data_one['en_madein'] = $value['originCountryTextEn'];
                $data_one['storage_method'] = $value['tempCndn'];
                $data_one['cate_code'] = $value['matGrp'];
                $data_one['unit'] = $value['unitText'];
                $data_one['expiration_date'] = $value['shelfLife'];
                $data_one['tax_type '] = $value['taxClass'];
                $data_one['tax'] = $value['taxClassText'];
                $data_one['pro_class'] = $value['prodClass'];
                $data_one['sync_oms'] = 1;
                $this->cityshop_product_model->updateCityShopTTGYProducts($data_one,$sap_code);
                $set_sync_codes[] = $sap_code;
            }
        }
    }
}
