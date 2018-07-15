<?php
class Ms_log_v2_model extends MY_Model {
    public function table_name() {
        return 'ms_log_v2';
    }

    /**
     *  删除状态 － 更新
     * @param string $order_name
     * @return bool
     */
    public function update_del($order_name)
    {
        $data = [
            'is_del' =>1,
        ];
        $this->db->where('order_name', $order_name);
        return $this->db->update('ms_log_v2', $data);
    }

    /**
     *  删除状态 － 更新
     * @param string $order_name
     * @return bool
     */
    public function update_order_del($order_name,$product_id)
    {
        $data = [
            'is_del' =>1,
        ];
        $where = array(
            'order_name'=>$order_name,
            'product_id'=>$product_id,
        );
        $this->db->where($where);
        return $this->db->update('ms_log_v2', $data);
    }
}
