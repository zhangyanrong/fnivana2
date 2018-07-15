<?php
class B2o_parent_order_product_model extends MY_Model {

	function B2o_parent_order_product_model() {
		parent::__construct();
	}

    public function table_name()
    {
        return 'b2o_parent_order_product';
    }

    public function getOrderProductList($orderId, $field = 'product.id,product.iscard')
    {
        $this->db->select($field);
        $this->db->from('b2o_parent_order_product');
        $this->db->join('product', 'b2o_parent_order_product.product_id=product.id', 'left');
        $this->db->where(array('b2o_parent_order_product.order_id' => $orderId));
        $query = $this->db->get();
        $products = $query->result_array();
        return $products;
    }

    public function getOrderSkuList($orderId){
        $this->db->select('product_id,product_no,qty,sid');
        $this->db->from('b2o_parent_order_product');
        $this->db->where(array('order_id'=>$orderId));
        $query = $this->db->get();
        $products = $query->result_array();
        return $products;
    }

    /**
     * 获取订单普通商品
     */
    public function get_products($orderId)
    {
        $this->db->select('id,order_id,product_name,product_id,product_no,qty,type,sid');
        $this->db->from('b2o_parent_order_product');
        $this->db->where(array('type'=>1,'order_id' => $orderId));
        $result = $this->db->get()->result_array();
        return $result;
    }

    public function getProductsByOrderId($orderId)
    {
        $this->db->select('product_name as name,b2o_parent_order_product.product_id as sku,type as category,price,qty as quantity, total_money, commision');
        $this->db->from('b2o_parent_order_product');
        $this->db->join('pro_commision', 'b2o_parent_order_product.product_id=pro_commision.product_id', 'left');
        $this->db->where(array('order_id' => $orderId));
        $query = $this->db->get();
        $products = $query->result_array();
        return $products;
    }
}
