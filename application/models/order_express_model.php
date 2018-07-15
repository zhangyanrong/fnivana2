<?php

class Order_express_model extends MY_Model
{
    public function table_name()
    {
        return 'order_express';
    }

    /*
    *查看订单信息根据ID
    */
    function getInfoById($id){
        $this->db->select('*');
        $this->db->from('order_express');
        $this->db->where('id',$id);
        $order_info = $this->db->get()->row_array();
        return $order_info;
    }

    /*
     * 变更支付方式
     */
    function set_ordre_payment($pay_name,$pay_parent_id,$pay_id,$order_id){
        $sql = "update ttgy_order_express set pay_name='".$pay_name."',pay_parent_id=".$pay_parent_id.",pay_id='".$pay_id."',version=version+1 where id=".$order_id;
        $this->db->query($sql);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }

    /*
    * 更新数据
    */
    function update_express($data,$where){
        $this->_filter($where);
        $this->db->update('order_express', $data);
        if(!$this->db->affected_rows()){
            return false;
        }
        return true;
    }
}
