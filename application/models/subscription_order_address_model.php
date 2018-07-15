<?php
/**
 * 周期购订单配送地址模型
 */
class Subscription_order_address_model extends MY_Model {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取表名
     */
    public function table_name() {
        return 'subscription_order_address';
    }

    /**
     * 添加配送地址
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
     * 订购订单配送地址明细
     *
     * @params $id int 配送地址ID
     * @params $order_id int 订单ID
     */
    public function detail($id, $order_id) {
        if (!$id && !$order_id) {
            return [];
        }
        $wheres = ['order_id' => $order_id];
        if ($id) {
            $wheres = ['id' => $id];
        }
        $result = $this->db->from($this->table_name())->where($wheres)->get()->row_array();
        return $result;
    }

    /**
     * 删除订单配送地址
     *
     * @params $id int 配送地址ID
     * @params $order_id int 订单ID
     */
    public function delete($id, $order_id) {
        if (!$id && !$order_id) {
            return false;
        }
        $wheres = ['order_id' => $order_id];
        if ($id) {
            $wheres = ['id' => $id];
        }
        $result = $this->db->delete($this->table_name(), $wheres);
        return ($result == true) ? $this->db->affected_rows() : false;
    }
}
