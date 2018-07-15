<?php
class Order_address_model extends MY_Model {

    public function table_name(){
        return 'order_address';
    }

    /**
     * 订单配送地址明细
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

    /*
   * 更新订单地址
   */
    function update_order_address($data,$where){
        $this->_filter($where);
        $this->db->update('order_address', $data);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }
}