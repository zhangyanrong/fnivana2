<?php
class Order_product_model extends MY_Model
{

    public function table_name()
    {
        return 'order_product';
    }

    public function getProductsByOrderId($orderId)
    {
        $this->db->select('product_name as name,order_product.product_id as sku,type as category,price,qty as quantity, total_money, commision');
        $this->db->from('order_product');
        $this->db->join('pro_commision', 'order_product.product_id=pro_commision.product_id', 'left');
        $this->db->where(array('order_id' => $orderId));
        $query = $this->db->get();
        $products = $query->result_array();
        return $products;
    }

    public function getOrderProductList($orderId, $field = 'product.id,product.iscard')
    {
        $this->db->select($field);
        $this->db->from('order_product');
        $this->db->join('product', 'order_product.product_id=product.id', 'left');
        $this->db->where(array('order_product.order_id' => $orderId));
        $query = $this->db->get();
        $products = $query->result_array();
        return $products;
    }

    public function getOrderSkuList($orderId){
        $this->db->select('product_id,product_no,qty');
        $this->db->from('order_product');
        $this->db->where(array('order_id'=>$orderId));
        $query = $this->db->get();
        $products = $query->result_array();

        $sku_arr = array();
        foreach ($products as $key => $value) {
            $this->db->select('id');
            $this->db->from('product_price');
            $this->db->where(array('product_id'=>$value['product_id'],'product_no'=>$value['product_no']));
            $query = $this->db->get();
            $sku = $query->row_array();
            $sku_arr[$sku['id']] = $value['qty'];
        }

        return $sku_arr;
    }

    /**
     * 获取订单普通商品
     */
    public function get_products($orderId)
    {
        $this->db->select('id,order_id,product_name,product_id,product_no,qty,type');
        $this->db->from('order_product');
        $this->db->where(array('type'=>1,'order_id' => $orderId));
        $result = $this->db->get()->result_array();
        return $result;
    }


    /**
     * 获取订单－所有商品
     */
    public function get_all_product($orderId)
    {
        $this->db->select('id,order_id,product_name,product_id,product_no,qty,type');
        $this->db->from('order_product');
        $this->db->where(array('order_id' => $orderId));
        $result = $this->db->get()->result_array();
        return $result;
    }
}