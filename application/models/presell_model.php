<?php
class Presell_model extends MY_Model {
	public function table_name(){
		return 'advance_sales';
	}

    /**
     * 获取预售期间的预售商品信息
     */
    public function get_list($id)
    {
        $now_data = date("Y-m-d H:i:s");
        $this->db->select('product_id,send_date,ordercount,is_only,max_num');
        $this->db->from('advance_sales');
        $this->db->where(array('start <='=>$now_data,'end >='=>$now_data,'product_id'=>$id));
        $result = $this->db->get()->result_array();
        return $result;
    }

    /**
     * 更新预售商品购买数量
     * @param int $productid
     * @param int $ordercount
     * @return bool
     */
    public function update_count($productid,$ordercount)
    {
        $data = [
            'ordercount' => $ordercount,
        ];
        $this->db->where('product_id', $productid);
        return $this->db->update('advance_sales', $data);
    }
}