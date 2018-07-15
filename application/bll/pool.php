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
class Pool
{
    public function __construct()
    {
        $this->ci = &get_instance();
    }

    /**
     * 请求订单池取消
     *
     * @return void
     * @author 
     **/
    public function pool_order_cancel($order_name,$channel,$sync_status,$is_enterprise,$order_type)
    {
        $return = array('status' => 'succ');
        if ($sync_status == 2) return array('status' => 'fail','msg' => '订单确认中，请稍后操作');
        if ($sync_status != 1 || !defined('POOL_ORDER_STATUS_URL')) return $return;


        $chId = $this->get_pool_channel($channel,$is_enterprise);
        if($order_type==6) $chId=10;
        if($order_type==7) $chId=12;
        // $chId = $channel;
        // if ($chId == '3') $chId = 1;
        // if ($chId == '5') $chId = 2;
        // if ($chId == '8') $chId = 9;

        // if ($is_enterprise) {
        //     $chId = 8;
        // }

        $data = array(
            'orderNo' => $order_name,
            'state'   => 'cancel',
            'chId'    => (int) $chId,
            'oms_refund' => 1,
        );

        $this->ci->load->bll('rpc/request');

        $log = array(
            'rpc_desc' => '订单取消',
            'obj_type' => 'order_cancel',
            'obj_name' => $order_name,
            );
        $this->ci->bll_rpc_request->set_rpc_log($log);
        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_ORDER_STATUS_URL,$data,'POST',6);

        if ($rs['result'] != 1) {
            return array('status' => 'fail','msg' => '订单已审核，不能取消');
        }

        return $return;
    }

    /**
     * 请求订单池确认收货
     *
     * @return void
     * @author 
     **/
    public function pool_order_confirm($order_name,$channel,$sync_status,$is_enterprise,$order_type)
    {
        $return = array('status' => 'succ');

        if ($sync_status != 1 || !defined('POOL_ORDER_STATUS_URL')) return $return;



            



        $chId = $this->get_pool_channel($channel,$is_enterprise);
        if($order_type == 6) $chId=10;
        if($order_type == 7) $chId=12;
        

        
        $data = array(
            'orderNo' => $order_name,
            'state'   => 'confirm',
            'chId'    => (int) $chId,
        );

        $this->ci->load->bll('rpc/request');

        $log = array(
            'rpc_desc' => '订单确认收货',
            'obj_type' => 'order_confirm',
            'obj_name' => $order_name,
            );
        $this->ci->bll_rpc_request->set_rpc_log($log);
        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_ORDER_STATUS_URL,$data,'POST',6);

        if ($rs['result'] != 1) {
            return array('status' => 'fail','msg' => '订单确认收货失败');
        }

        return $return;
    }

    /**
     * 抓取OMS运单号
     *
     * @return void
     * @author 
     **/
    public function getExpNo($order_names)
    {
        if (!$order_names) return false;

        $data = array('nos'=>$order_names);

        $this->ci->load->bll('rpc/request');

        $log = array(
            'rpc_desc' => '抓取运单号',
            'obj_type' => 'order',             
        );
        $this->ci->bll_rpc_request->set_rpc_log($log);

        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_GETEXPNO_URL,$data);

        if ($rs == false) return false;

        return $rs['expnos'];
    }

    /**
     * 跨境通审核
     * 
     * @param Array $orders [{no:"15020785623",state:1},{no:"15020785625",state:0}]
     * @return void
     * @author 
     **/
    public function kjt_status($orders)
    {
        if (!$orders) return false;

        $data = array('avs'=>$orders);


        $this->ci->load->bll('rpc/request');

        $log = array(
            'rpc_desc' => '跨境通审核状态',
            'obj_type' => 'order',
        );
        $this->ci->bll_rpc_request->set_rpc_log($log);
        
        $rs = $this->ci->bll_rpc_request->realtime_call(POOL_APPROVAL_URL,$data);

        if ($rs == false) return false;

        return true;
    }

    public function get_pool_channel($channel,$is_enterprise)
    {
        switch ($channel) {
            case '3':
                $channel = 1;
                break;
            case '5':
                $channel = 2;
                break;
            case '8':
                $channel = 10;
                break;
            case '9':
                $channel = 11;
                break;
            case '11':
                $channel = 16;
                break;
        }

        if ($is_enterprise) {
            $channel = 8;
        }

        return (int) $channel;
    }
}