<?php
class o2o_order_product_extra_model extends MY_Model {

    public function table_name(){
        return 'o2o_order_product_extra';
    }

    public function get_child_order_product($child_order_ids)
    {
        $this->db->select('p.*,e.c_order_id,e.store_id');
        $this->db->from('ttgy_o2o_order_product_extra e');
        $this->db->join('ttgy_order_product p', 'p.id=e.order_product_id');
        $this->db->where_in('e.c_order_id', $child_order_ids);
        $result = $this->db->get()->result_array();
        return $result;
    }

    
}