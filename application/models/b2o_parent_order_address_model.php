<?php
class B2o_parent_order_address_model extends MY_Model {

	function B2o_parent_order_address_model() {
		parent::__construct();
	}

    public function table_name()
    {
        return 'b2o_parent_order_address';
    }

    /*
   * 更新订单地址
   */
    function update_order_address($data,$where){
        $this->_filter($where);
        $this->db->update('b2o_parent_order_address', $data);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }
}
