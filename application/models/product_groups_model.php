<?php
class Product_groups_model extends MY_Model {
	public function table_name(){
		return 'product_groups';
	}

    public function get_product_groups($product_ids)
    {
        //组合商品只能是单一规格
        $this->db->select('g.product_id, g.g_qty, g.g_price, pp.price, pp.volume, pp.unit, pp.product_no, pp.inner_code, p.product_name');
        $this->db->from('ttgy_product_groups g');
        $this->db->join('ttgy_product p', 'p.id=g.g_product_id');
        $this->db->join('ttgy_product_price pp', 'pp.product_id=p.id');
        $this->db->where_in('g.product_id', $product_ids);
        $result = $this->db->get()->result_array();
        return $result;
    }
}