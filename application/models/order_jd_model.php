<?php
class Order_jd_model extends MY_Model {

    public function table_name(){
        return 'order_jd';
    }

    public function add($data){
        $insert_data = $data;
        $insert_data['create_time'] = date('Y-m-d H:i:s');
        $this->db->insert('order_jd',$insert_data);
    }
}