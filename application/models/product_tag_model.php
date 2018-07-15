<?php

class Product_tag_model extends MY_Model
{
	public function table_name()
    {
		return 'product_tag';
	}

    public function selectTagName($id)
    {
		$this->db->select('name');
		$this->db->from('product_tag');
		$this->db->where(array('id' => $id));
		$query = $this->db->get();
		$result = $query->row_array();
		return $result['name'];
	}
}
