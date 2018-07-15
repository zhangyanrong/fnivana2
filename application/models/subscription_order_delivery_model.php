<?php
/**
 * 周期购父子订单关联模型
 */
class Subscription_order_delivery_model extends MY_Model {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取表名
     */
    public function table_name() {
        return 'subscription_order_delivery';
    }

    /**
     * 父子订单关联列表
     *
     * @params $order_id int 定购父订单ID
     */
    public function get_list($order_id) {
        if (!$order_id) {
            return [];
        }
        $rows = $this->db->from($this->table_name())->where(['order_id' => $order_id])->get()->result_array();
        return $rows;
    }

    /**
     * 统计子订单总数
     *
     * @params $order_id int 定购父订单ID
     */
    public function get_total($order_id) {
        if (!$order_id) {
            return 0;
        }
        $total = $this->db->from($this->table_name())->where(['order_id' => $order_id])->count_all_results();
        return $total;
    }
}
