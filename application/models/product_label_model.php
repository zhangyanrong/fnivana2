<?php

class Product_label_model extends MY_Model
{
    public function table_name()
    {
        return 'product_label';
    }

    /**
     * 获取商品标签
     */
    public function getProductLabel($productId = 0)
    {
        if (!$productId) {
            return;
        }
        $where['lr.product_id'] = $productId;

		$this->db->select("l.id, l.name, l.sort, l.parent_id, l.is_recommend, lr.product_id")
                 ->from("product_label_relation AS lr")
                 ->join("product_label AS l", "lr.label_id = l.id", "left")
                 ->where("l.name IS NOT NULL");
        $this->_filter($where);
        $result = $this->db->get()->result_array();
		return $result ? $result : [];
    }

    public function getTopLabel()
    {
        $where = [
            'parent_id' => 0,
            'is_show' => 1,
        ];

        $this->db->select('id, name')
                 ->from($this->table_name())
                 ->where($where)
                 ->order_by('sort');
        $result = $this->db->get()->result_array();
		return $result ? $result : [];
    }
}
