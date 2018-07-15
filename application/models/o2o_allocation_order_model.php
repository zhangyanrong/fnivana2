<?php

class O2o_allocation_order_model extends MY_Model
{

    public function table_name()
    {
        return 'o2o_allocation_order';
    }

    public function sync_succ($orders)
    {
        if (empty($orders)) {
            return array('result' => 0, 'msg' => '订单更新失败');
        }

        if (!isset($orders[0])) {
            $orders = array($orders);
        }
        foreach ($orders as $order) {
            if ($order['sync_status'] == 1) {
                continue;
            }
            $this->db->query('update ttgy_o2o_allocation_order set sync_status = 1 where id=?', array($order['id']));
            $content = json_decode($order['content'], true);
            foreach ($content as $prod) {
                $p = $this->db->query('select cart_tag from ttgy_product where id=?', array($prod['product_id']))->row_array();
                if ($p['cart_tag'] == '买1送1') {
                    $prod['num'] = floor($prod['num'] / 2);
                }
                $this->db->query('update ttgy_o2o_store_goods set stock = stock + ?, qty_allocation=0 where store_id = ? and product_id=?', array($prod['num'], $order['store_id'], $prod['product_id']));
            }
        }
        return array('result' => 1, 'msg' => '');
    }
}