<?php
class B2o_product_group_model extends MY_Model {

	function B2o_product_group_model() {
		parent::__construct();
	}

    public function table_name()
    {
        return 'b2o_product_group';
    }

    /*
	* 获取组合商品
	*/
    function getGroupList()
    {
        $sql = "select id,product_id,g_product_id,g_price,g_qty,channel from ttgy_b2o_product_group";
        $result = $this->db->query($sql)->result_array();
        return $result;
    }
}
