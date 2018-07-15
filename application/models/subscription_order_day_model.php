<?php
/**
 * 周期购订单配送日模型
 */
class Subscription_order_day_model extends MY_Model {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取表名
     */
    public function table_name() {
        return 'subscription_order_day';
    }

    /**
     * 添加配送日
     *
     * @params $data array 订单配送地址数据
     */
     public function add($data) {
         if (!$data OR !is_array($data)) {
             return 0;
         }
         $insert_query = $this->db->insert_string($this->table_name(), $data);
         $query = $this->db->query($insert_query);
         $id = $this->db->insert_id();
         return $id;
    }

    /**
     * 批量添加配送日
     *
     * @params $data array 二维数组，订单配送日数据
     */
    public function add_batch($data) {
        if (!$data OR !is_array($data)) {
            return 0;
        }
        return $this->db->insert_batch($this->table_name(), $data);
    }
}
