<?php if (!defined('BASEPATH')) exit ('No direct script access allowed');

class O2o_model extends MY_Model
{
    /**
     * o2o订单
     * @param array $param
     * @return int
     */
    public function createChildOrder($param = array())
    {
        $data = $param + array(
            'p_order_id' => null,
            'uid' => 0,
            'order_name' => null,
            'store_id' => 0,//老版实体门店,忽设置
            'money' => '0.00',
            'goods_money' => '0.00',
            'jf_money' => 0,
            'method_money' => 0,
            'card_money' => '0.00',
            'pmoney' => '0.00',
            'pay_status' => 0,
            'operation_id' => 0,
            'score' => '0.00',
            'use_card' => '',
            'sync_status' => 0,
            'address' => '',
            'send_type' => 1,
            'pay_discount' => '0.00',
            'use_money_deduction' => '0.00',
        );

        $rs = $this->db->insert("o2o_child_order", $data);
        return $rs ? $this->db->insert_id() : 0;
    }

    /**
     * o2o 订单商品关系
     * @param array $param
     * @return int
     */
    public function createChildOrderProduct($param = array())
    {
        $data = $param + [
                'order_product_id' => 0,
                'store_id' => 0,//老版虚拟门店，新版忽设置
                'c_order_id' => 0
        ];
        $rs = $this->db->insert("o2o_order_product_extra", $data);
        return $rs ? $this->db->insert_id() : 0;
    }
}