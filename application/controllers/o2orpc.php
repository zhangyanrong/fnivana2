<?php
/**
 * 与o2o订单池的交互
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
class O2oRpc extends CI_Controller
{
    /**
     * 往o2o订单池推送
     *
     * @return void
     * @author
     **/
    public function push_order()
    {
        if (php_sapi_name() !== 'cli') return ;

        $this->load->bll('pool/o2o/order');
        $this->load->bll('rpc/o2o/request');

        //$order_names = func_get_args();
        $a_orders = $this->bll_pool_o2o_order->get_push_orders();

        if (!$a_orders) return ;

        $orders_arr = array_chunk($a_orders,100,true);

        foreach ($orders_arr as $key => $orders) {
            $orderids = array_keys($orders);
            if ($orderids) {
                $this->bll_pool_o2o_order->set_sync($orderids,'2');
            }

            // 金额校验
            $orders = $this->bll_pool_o2o_order->check_order($orders);

            if (!$orders) return ;

            if ($this->bll_pool_o2o_order->rpc_log) $this->bll_rpc_o2o_request->set_rpc_log($this->bll_pool_o2o_order->rpc_log);

            $params = array(
                'url' => POOL_O2O_OMS_URL,
                'method' => 'order.save',
                'data' => array_values($orders),
            );
            $response = $this->bll_rpc_o2o_request->realtime_call($params,6);
            if($response === false && !empty($orderids)){
                $this->bll_pool_o2o_order->set_sync($orderids,'0');
            }
        }
    }

    /**
     * 同步商户
     */
    public function pullSeller(){
        $this->load->bll('pool/o2o/seller');
        $this->bll_pool_o2o_seller->pull();
    }

    /**
     * 初始化门店code
     */
    public function initStoreCode(){
        return;
        $this->load->bll('pool/o2o/store');
        $rs = $this->bll_pool_o2o_store->pull(array('updateCode' => true));
        echo '<pre>' . print_r($rs, true) . '</pre>';die;
    }

    /**
     * 同步门店
     */
    public function pullStore()
    {
        $this->load->bll('pool/o2o/store');
        $this->bll_pool_o2o_store->pull(array(
            'startTime' => date('Y-m-d H:i:s', time() - 60 * 10)
        ));
    }

    /**
     * 推送省市区到OMS
     */
    public function pushArea(){
        $this->load->bll('pool/o2o/area');
        $this->bll_pool_o2o_area->push();
    }

    /**
     * 初始化OMS楼宇ID(一次性)
     */
    public function initBuildingID(){
        $this->load->bll('pool/o2o/region');
        $this->bll_pool_o2o_region->initBuildingID();
    }

    /**
     * 修复订单数据
     */
    public function child_order(){

        return;
        $sql = "SELECT o.id p_order_id,o.uid, o.order_name, st.physical_store_id store_id, s.building_id, o.money, o.goods_money, o.jf_money, o.method_money, o.card_money, o.pmoney, o.pay_status, o.operation_id,o.score, o.use_card, o.sync_status, s.address FROM ttgy_order o
                LEFT JOIN ttgy_o2o_order_extra s on s.order_id = o.id
                LEFT JOIN ttgy_o2o_store  st on st.id = s.store_id
                LEFT JOIN ttgy_o2o_child_order c on c.p_order_id = o.id
                WHERE o.order_type in (3, 4) and c.p_order_id is null and o.time > '2015-10-29' and st.physical_store_id in(12,2,67)";
        $rs = $this->db->query($sql)->result_array();

        foreach($rs as $key => $value){
            $this->db->insert('ttgy_o2o_child_order', $value);
            $c_order_id = $this->db->insert_id();
            echo $c_order_id . "\n";
            if($c_order_id){
                $sql = 'select p.id, e.store_id from ttgy_order_product p left join ttgy_o2o_order_extra e on e.order_id=p.order_id where p.order_id = ?';
                $rs = $this->db->query($sql, array($value['p_order_id']))->result_array();
                foreach($rs as $v){
                    $this->db->insert('ttgy_o2o_order_product_extra', array(
                        'c_order_id' => $c_order_id,
                        'order_product_id' => $v['id'],
                        'store_id' => $v['store_id'],
                    ));
                }
            }
        }
    }
}
